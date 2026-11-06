<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\FeedbackRequest;
use App\Services\FeedbackService;
use Illuminate\Console\Command;

/**
 * php artisan feedback:send
 *
 * Emails the feedback prompt for events that finished a while ago and haven't been asked yet, plus one reminder
 * for requests that are still unanswered. Meant to run hourly from the scheduler (see routes/console.php).
 *
 *   php artisan feedback:send --dry-run         list who would get an email
 *   php artisan feedback:send --appointment=12  send (or resend) for one appointment
 */
class SendFeedbackRequests extends Command
{
    protected $signature = 'feedback:send {--appointment= : Send to one appointment id} {--dry-run : Only list who would be emailed}';

    protected $description = 'Email post-event feedback prompts (and one reminder) to clients';

    public function handle(FeedbackService $feedback): int
    {
        $cfg      = config('ssc.feedback');
        $dry      = (bool) $this->option('dry-run');
        $sent     = 0;
        $skipped  = 0;

        if ($id = $this->option('appointment')) {
            $appointment = Appointment::with(['client', 'facility'])->find($id);

            if (!$appointment) {
                $this->error("Appointment #{$id} not found.");
                return self::FAILURE;
            }

            if ($dry) {
                $this->line("Would email {$appointment->client->email} for #{$appointment->id}.");
                return self::SUCCESS;
            }

            $ok = $feedback->send($appointment, $feedback->ensureRequest($appointment)->sent_at !== null);
            $this->info($ok ? "Sent for #{$appointment->id}." : "Client of #{$appointment->id} has no email address.");

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        // 1) first prompt: finished events that ended long enough ago, within the window, never asked
        $newEvents = Appointment::with(['client', 'facility'])
            ->where('status', 'finished')
            ->whereRaw('TIMESTAMP(event_date, end_time) <= ?', [now()->subHours($cfg['send_after_hours'])])
            ->whereRaw('TIMESTAMP(event_date, end_time) >= ?', [now()->subDays($cfg['window_days'])])
            ->whereDoesntHave('feedback')
            ->whereNotIn('id', FeedbackRequest::query()->select('appointment_id'))
            ->get();

        foreach ($newEvents as $a) {
            if ($dry) {
                $this->line("Would email {$a->client->email} — \"{$a->activity_title}\" (#{$a->id})");
                continue;
            }
            $feedback->send($a) ? $sent++ : $skipped++;
        }

        // 2) one reminder for unanswered requests
        $reminders = FeedbackRequest::with('appointment.client', 'appointment.facility')
            ->whereNotNull('sent_at')->whereNull('reminded_at')->whereNull('completed_at')
            ->where('sent_at', '<=', now()->subDays($cfg['reminder_after_days']))
            ->get();

        foreach ($reminders as $r) {
            $a = $r->appointment;
            if (!$a || $a->feedback()->exists()) {
                continue;
            }
            if ($dry) {
                $this->line("Would remind {$a->client->email} — \"{$a->activity_title}\" (#{$a->id})");
                continue;
            }
            $feedback->send($a, true) ? $sent++ : $skipped++;
        }

        $this->info($dry ? 'Dry run finished.' : "Feedback emails sent: {$sent}" . ($skipped ? " ({$skipped} skipped — no email address)" : ''));

        return self::SUCCESS;
    }
}