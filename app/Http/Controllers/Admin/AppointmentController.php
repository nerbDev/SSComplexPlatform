<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin appointment monitor.
 *
 *  - index():     every appointment, any status, filterable by status + search.
 *  - scheduled(): only status=scheduled (the "Scheduled Appointments" list).
 *  - schedule():  moves a ready appointment into status=scheduled.
 *
 * "Ready to schedule" = the client has finished their side of the track:
 *   paid track      -> receipt_confirmed
 *   free_use track  -> form_submitted
 * Adjust SCHEDULABLE below if your flow also schedules from other statuses.
 */
class AppointmentController extends Controller
{
    /** Statuses the admin is allowed to schedule from. */
    private const SCHEDULABLE = ['receipt_confirmed', 'form_submitted'];

    /** Filter chips, in pipeline order. key => label */
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
        return $this->listing($request, 'all');
    }

    public function scheduled(Request $request): View
    {
        return $this->listing($request, 'scheduled');
    }

    public function schedule(Request $request, Appointment $appointment): RedirectResponse
    {
        if (!in_array($appointment->status, self::SCHEDULABLE, true)) {
            return back()->with('status', 'That appointment is not ready to schedule — refresh to see its current status.');
        }

        $data = $request->validate(['note' => 'nullable|string|max:500']);
        $note = $data['note'] ?? null;
        $from = $appointment->status;

        $appointment->update([
            'status' => 'scheduled',
            'notes'  => $note
                ? trim($appointment->notes . " | Admin note (scheduled): {$note}")
                : $appointment->notes,
        ]);

        AuditLog::record(
            'admin.appointments.scheduled',
            $appointment,
            "Admin moved appointment from {$from} to scheduled" . ($note ? " — {$note}" : '')
        );

        return redirect()
            ->route('admin.appointments.scheduled')
            ->with('status', "{$appointment->activity_title} — added to Scheduled Appointments.");
    }

    private function listing(Request $request, string $mode): View
    {
        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $query = Appointment::with(['client', 'facility', 'payment', 'commitmentAcknowledgment', 'verifiedBy'])
            ->withCount(['staffAssignments as assigned_count' => fn($q) => $q->where('status', 'assigned')])
            ->when($mode === 'scheduled', fn($x) => $x->where('status', 'scheduled'))
            ->when($mode === 'all' && $status && isset(self::STATUS_LABELS[$status]), fn($x) => $x->where('status', $status))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('activity_title', 'like', "%{$q}%")
                      ->orWhereHas('client', fn($c) => $c
                          ->where('first_name', 'like', "%{$q}%")
                          ->orWhere('last_name', 'like', "%{$q}%"));
                });
            });

        $mode === 'scheduled'
            ? $query->orderBy('event_date')->orderBy('start_time')
            : $query->orderByDesc('created_at');

        $page = $query->paginate(15)->withQueryString();

        $rows = $page->getCollection()->map(fn(Appointment $a) => $this->present($a))->values()->all();

        $counts = Appointment::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        return view('admin.appointments.index', [
            'mode'          => $mode,
            'appointments'  => $page,
            'rows'          => $rows,
            'counts'        => $counts,
            'totalCount'    => array_sum($counts),
            'statusLabels'  => self::STATUS_LABELS,
            'activeStatus'  => $mode === 'all' ? $status : null,
            'search'        => $q,
            'schedulable'   => self::SCHEDULABLE,
        ]);
    }

    /** Flatten one appointment (with all details) for the table + Review modal. */
    private function present(Appointment $a): array
    {
        $client  = $a->client;
        $payment = $a->payment;
        $ack     = $a->commitmentAcknowledgment;

        $canSchedule = in_array($a->status, self::SCHEDULABLE, true);

        // Soft warning: another already-scheduled appointment overlaps this one
        // in the same facility. Only checked for rows that can be scheduled.
        $conflicts = 0;
        if ($canSchedule && $a->facility_id && $a->event_date) {
            $conflicts = Appointment::forFacilityOnDate($a->facility_id, $a->event_date->toDateString())
                ->where('status', 'scheduled')
                ->where('id', '!=', $a->id)
                ->where('start_time', '<', $a->end_time)
                ->where('end_time', '>', $a->start_time)
                ->count();
        }

        $money = fn($v) => $v === null ? null : '₱' . number_format((float) $v, 2);
        $file  = fn($path) => $path ? asset('storage/' . ltrim($path, '/')) : null; // assumes the `public` disk + storage:link
        $dt    = fn($v) => $v ? Carbon::parse($v)->format('M j, Y g:i A') : null;

        return [
            'id'                 => $a->id,
            'status'             => $a->status,
            'status_label'       => self::STATUS_LABELS[$a->status] ?? ucfirst(str_replace('_', ' ', $a->status)),
            'can_schedule'       => $canSchedule,
            'is_scheduled'       => $a->status === 'scheduled',
            'assigned_count'     => (int) ($a->assigned_count ?? 0),
            'staff_url'          => route('admin.appointments.staff.index', $a->id),
            'conflicts'          => $conflicts,

            'client_name'        => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'Unnamed client',
            'contact_number'     => $client->phone_number ?? null,
            'email'              => $client->email ?? null,
            'barangay'           => $client->barangay ?? null,

            'activity'           => $a->activity_title,
            'facility'           => $a->facility->name ?? null,
            'date'               => $a->event_date ? $a->event_date->format('M j, Y') : null,
            'time'               => ($a->start_time ? Carbon::parse($a->start_time)->format('g:i A') : '—')
                                    . ' – ' . ($a->end_time ? Carbon::parse($a->end_time)->format('g:i A') : '—'),
            'type_label'         => self::TYPE_LABELS[$a->booking_type] ?? ucfirst((string) $a->booking_type),
            'track'              => $a->track,
            'rate_type'          => $a->rate_type ? ucfirst(str_replace('_', ' ', $a->rate_type)) : null,
            'aircon'             => $a->aircon === null ? null : ($a->aircon ? 'Yes' : 'No'),
            'expected_attendees' => $a->expected_attendees,
            'notes'              => $a->notes,
            'requested_at'       => $dt($a->created_at),

            'verified_by'        => $a->verifiedBy
                                    ? trim(($a->verifiedBy->first_name ?? '') . ' ' . ($a->verifiedBy->last_name ?? ''))
                                    : null,
            'verified_at'        => $dt($a->verified_at),

            'payment' => $payment ? [
                'status'        => ucfirst(str_replace(['_', '-'], ' ', (string) $payment->status)),
                'total'         => $money($payment->total_amount),
                'downpayment'   => $money($payment->downpayment_amount),
                'balance'       => $money($payment->balance_amount),
                'balance_due'   => $payment->balance_due_date ? $payment->balance_due_date->format('M j, Y') : null,
                'gcash_ref'     => $payment->gcash_reference,
                'paid_at'       => $dt($payment->paid_at),
                'receipt_confirmed_at' => $dt($payment->receipt_confirmed_at),
                'receipt_url'   => $file($payment->receipt_path),
                'proof_url'     => $file($payment->proof_of_payment_path),
                'client_notes'  => $payment->client_notes,
            ] : null,

            'approval_form_url'  => $file($a->approval_form_path),
            'approval_form_at'   => $dt($a->approval_form_submitted_at),
            'commitment_at'      => $ack ? $dt($ack->created_at) : null,
        ];
    }
}