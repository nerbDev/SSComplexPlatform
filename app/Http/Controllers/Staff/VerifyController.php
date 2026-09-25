<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Sprint 3's core module: Staff reviews every `pending` appointment or
 * reservation (both tracks — paid and free_use share this one queue) and
 * moves it to `verified`, `cancelled`, or `rescheduled`.
 *
 * NOTE: contact_number/barangay collected in the client's Step 1 aren't
 * stored as their own columns yet — they only ever landed inside
 * Appointment.notes as free text (see ReservationController@store). The
 * Details modal below surfaces that raw note rather than pretending
 * structured fields exist. Worth a follow-up migration to give those two
 * their own columns if Staff needs to search/filter by them later.
 */
class VerifyController extends Controller
{
    private const TYPE_LABELS = [
        'appointment'      => 'Appointment',
        'room_reservation' => 'Room Reservation',
        'free_use'         => 'Free Use',
    ];

    public function index(): View
    {
        $pending = Appointment::with(['client', 'facility'])
            ->where('status', 'pending')
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn(Appointment $a) => [
                'id'                  => $a->id,
                'client_name'         => trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: 'Unnamed client',
                'contact_number'      => $a->client->phone_number ?? '—',
                'notes'               => $a->notes,
                'activity'            => $a->activity_title,
                'facility'            => $a->facility->name ?? '—',
                'date_time'           => Carbon::parse($a->event_date)->format('M j, Y') . ' · ' . Carbon::parse($a->start_time)->format('g:i A'),
                'track'               => $a->track,
                'type_label'          => self::TYPE_LABELS[$a->booking_type] ?? ucfirst($a->booking_type),
                'expected_attendees'  => $a->expected_attendees,
            ])->toArray();

        return view('staff.verify.index', compact('pending'));
    }

    public function verify(Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, 'pending', 'verified', 'staff.verify.verified');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, 'pending', 'cancelled', 'staff.verify.cancelled', $request->input('note'));
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        return $this->transition($appointment, 'pending', 'rescheduled', 'staff.verify.rescheduled', $request->input('note'));
    }

    private function transition(Appointment $appointment, string $expectedFrom, string $to, string $auditAction, ?string $note = null): RedirectResponse
    {
        if ($appointment->status !== $expectedFrom) {
            return back()->with('status', 'That request has already been actioned — refresh to see its current status.');
        }

        $appointment->update([
            'status'      => $to,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'notes'       => $note ? trim($appointment->notes . " | Staff note ({$to}): {$note}") : $appointment->notes,
        ]);

        AuditLog::record($auditAction, $appointment, "Staff set status to {$to}" . ($note ? " — {$note}" : ''));

        $labels = ['verified' => 'Verified', 'cancelled' => 'Cancelled', 'rescheduled' => 'Marked as rescheduled'];

        return redirect()
            ->route('staff.verify.index')
            ->with('status', "{$appointment->activity_title} — {$labels[$to]}.");
    }
}