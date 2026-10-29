<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Client "My Appointments": every appointment the logged-in client made, with
 * Cancel and Reschedule actions. Rescheduling itself lives in RescheduleController.
 *
 * Cancelling = no refund (ReservationController::CANCELLATION_REFUND_PERCENT = 0).
 */
class MyAppointmentController extends Controller
{
    /** Statuses shown under the "History" tab. Everything else is "Active". */
    private const HISTORY = ['finished', 'cancelled', 'rescheduled'];

    private const STATUS_LABELS = [
        'pending'           => 'Pending',
        'verified'          => 'Verified',
        'payment_submitted' => 'Payment submitted',
        'receipt_confirmed' => 'Receipt confirmed',
        'form_submitted'    => 'Form submitted',
        'scheduled'         => 'Scheduled',
        'finished'          => 'Finished',
        'rescheduled'       => 'Rescheduled',
        'cancelled'         => 'Cancelled',
    ];

    private const TYPE_LABELS = [
        'appointment'      => 'Appointment',
        'room_reservation' => 'Room Reservation',
        'free_use'         => 'Free Use',
    ];

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'history' ? 'history' : 'active';

        $mine = fn() => Appointment::where('user_id', Auth::id());

        $counts = [
            'active'  => $mine()->whereNotIn('status', self::HISTORY)->count(),
            'history' => $mine()->whereIn('status', self::HISTORY)->count(),
        ];

        $query = $mine()->with(['facility', 'payment', 'reschedulingFees']);

        if ($tab === 'history') {
            $query->whereIn('status', self::HISTORY)->orderByDesc('event_date')->orderByDesc('start_time');
        } else {
            $query->whereNotIn('status', self::HISTORY)->orderBy('event_date')->orderBy('start_time');
        }

        $page = $query->paginate(10)->withQueryString();
        $rows = $page->getCollection()->map(fn(Appointment $a) => $this->present($a))->values()->all();

        return view('client.appointments.index', [
            'tab'          => $tab,
            'counts'       => $counts,
            'appointments' => $page,
            'rows'         => $rows,
            'feePercent'   => ReservationController::RESCHEDULING_FEE_PERCENT,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        if (!in_array($appointment->status, ReservationController::ACTIVE_STATUSES, true)) {
            return back()->with('error', 'This appointment can no longer be cancelled.');
        }

        $data   = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $reason = $data['reason'] ?? null;
        $from   = $appointment->status;

        DB::transaction(function () use ($appointment, $reason, $from) {
            $appointment->update([
                'status' => 'cancelled',
                'notes'  => $reason
                    ? trim($appointment->notes . " | Client cancellation reason: {$reason}")
                    : $appointment->notes,
            ]);

            // Free up any staff the admin had already assigned.
            DB::table('staff_assignments')
                ->where('appointment_id', $appointment->id)
                ->where('status', 'assigned')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            // Drop an unpaid reschedule request (a paid one stays as a payment record).
            $appointment->reschedulingFees()->where('status', 'pending')->update(['status' => 'cancelled']);

            AuditLog::record(
                'client.appointment.cancelled',
                $appointment,
                "Client cancelled \"{$appointment->activity_title}\" (was {$from}); no refund" . ($reason ? " — {$reason}" : '')
            );
        });

        return redirect()
            ->route('client.appointments.index')
            ->with('status', "\"{$appointment->activity_title}\" was cancelled. As stated, no refund is given for cancellations.");
    }

    private function present(Appointment $a): array
    {
        $isFree = $a->track === 'free_use';
        $openFee = $a->reschedulingFees->whereIn('status', ['pending', 'submitted'])->sortByDesc('id')->first();

        $total = $a->payment?->total_amount;
        $feePreview = (!$isFree && $total)
            ? '₱' . number_format(round((float) $total * ReservationController::RESCHEDULING_FEE_PERCENT / 100, 2), 2)
            : null;

        $continueUrl = null;
        if ($openFee) {
            $continueUrl = $openFee->status === 'pending'
                ? route('client.appointments.reschedule.pay', $a)
                : route('client.appointments.reschedule.receipt', $a);
        }

        if ($isFree) {
            $paymentLabel = 'No payment needed';
        } elseif ($a->payment) {
            $paymentLabel = ucfirst(str_replace(['_', '-'], ' ', (string) $a->payment->status));
        } else {
            $paymentLabel = 'Not paid yet';
        }

        return [
            'id'              => $a->id,
            'activity'        => $a->activity_title,
            'facility'        => $a->facility->name ?? '—',
            'date'            => $a->event_date ? $a->event_date->format('M j, Y') : '—',
            'time'            => \Carbon\Carbon::parse($a->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($a->end_time)->format('g:i A'),
            'type_label'      => self::TYPE_LABELS[$a->booking_type] ?? ucfirst((string) $a->booking_type),
            'is_free'         => $isFree,
            'status'          => $a->status,
            'status_label'    => self::STATUS_LABELS[$a->status] ?? ucfirst(str_replace('_', ' ', $a->status)),
            'payment_label'   => $paymentLabel,
            'can_cancel'      => in_array($a->status, ReservationController::ACTIVE_STATUSES, true),
            'can_reschedule'  => RescheduleController::isEligible($a),
            'fee_preview'     => $feePreview,
            'open_reschedule' => $openFee ? ($openFee->status === 'pending' ? 'Reschedule awaiting payment' : 'Reschedule awaiting receipt confirmation') : null,
            'continue_url'    => $continueUrl,
            'cancel_url'      => route('client.appointments.cancel', $a),
            'reschedule_url'  => route('client.appointments.reschedule', $a),
        ];
    }
}