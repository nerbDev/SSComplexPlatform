<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin staff assignment for appointments that are status=scheduled.
 *
 *  - index():    one page per appointment: who's assigned (with Cancel) and
 *                who's available (with Assign). Doubles as the "edit" screen,
 *                so the admin can come back any time and add/cancel staff.
 *  - assign():   adds a staff member (re-activates a previously cancelled one).
 *  - unassign(): cancels the assignment (row is kept, status = cancelled).
 *
 * Uses the existing staff_assignments pivot (appointment_id, staff_id,
 * start_time, end_time, status). Status values used here:
 *   'assigned'  -> active assignment
 *   'cancelled' -> admin cancelled it
 * If your staff_assignments.status column is an enum with different values,
 * change the two constants below.
 */
class AppointmentStaffController extends Controller
{
    private const ASSIGNED  = 'assigned';
    private const CANCELLED = 'cancelled';

    public function index(Appointment $appointment): View|RedirectResponse
    {
        if ($redirect = $this->requireScheduled($appointment)) {
            return $redirect;
        }

        $appointment->load(['client', 'facility']);

        $assigned = $appointment->assignedStaff()
            ->wherePivot('status', self::ASSIGNED)
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $assignedIds = $assigned->pluck('id')->all();
        $busy        = $this->busyStaff($appointment); // [staff_id => other activity title]

        $everyone = User::where('role', 'staff')->orderBy('first_name')->orderBy('last_name')->get();

        $available   = $everyone->reject(fn($u) => in_array($u->id, $assignedIds) || isset($busy[$u->id]))->values();
        $unavailable = $everyone->filter(fn($u) => !in_array($u->id, $assignedIds) && isset($busy[$u->id]))->values();

        return view('admin.appointments.staff', [
            'appointment' => $appointment,
            'assigned'    => $assigned,
            'available'   => $available,
            'unavailable' => $unavailable,
            'busy'        => $busy,
            'when'        => [
                'date' => $appointment->event_date ? $appointment->event_date->format('M j, Y') : '—',
                'time' => Carbon::parse($appointment->start_time)->format('g:i A') . ' – ' . Carbon::parse($appointment->end_time)->format('g:i A'),
            ],
        ]);
    }

    public function assign(Appointment $appointment, User $staff): RedirectResponse
    {
        if ($redirect = $this->requireScheduled($appointment)) {
            return $redirect;
        }

        if ($staff->role !== 'staff') {
            return back()->with('error', 'Only staff accounts can be assigned.');
        }

        $alreadyAssigned = $appointment->assignedStaff()
            ->wherePivot('status', self::ASSIGNED)
            ->where('users.id', $staff->id)
            ->exists();

        if ($alreadyAssigned) {
            return back()->with('error', "{$this->name($staff)} is already assigned to this appointment.");
        }

        // Server-side re-check so a stale page can't double-book someone.
        if (isset($this->busyStaff($appointment)[$staff->id])) {
            return back()->with('error', "{$this->name($staff)} was just assigned to another event at the same time.");
        }

        // syncWithoutDetaching inserts a new row, or updates (re-activates) an existing
        // cancelled one, without touching anyone else's assignment.
        $appointment->assignedStaff()->syncWithoutDetaching([
            $staff->id => [
                'start_time' => $appointment->start_time,
                'end_time'   => $appointment->end_time,
                'status'     => self::ASSIGNED,
                'assigned_by' => Auth::id(),
            ],
        ]);

        AuditLog::record(
            'admin.appointments.staff_assigned',
            $appointment,
            "Admin assigned {$this->name($staff)} to \"{$appointment->activity_title}\""
        );

        return back()->with('status', "{$this->name($staff)} assigned.");
    }

    public function unassign(Appointment $appointment, User $staff): RedirectResponse
    {
        if ($redirect = $this->requireScheduled($appointment)) {
            return $redirect;
        }

        $isAssigned = $appointment->assignedStaff()
            ->wherePivot('status', self::ASSIGNED)
            ->where('users.id', $staff->id)
            ->exists();

        if (!$isAssigned) {
            return back()->with('error', "{$this->name($staff)} is not currently assigned to this appointment.");
        }

        $appointment->assignedStaff()->updateExistingPivot($staff->id, ['status' => self::CANCELLED]);

        AuditLog::record(
            'admin.appointments.staff_unassigned',
            $appointment,
            "Admin cancelled {$this->name($staff)}'s assignment on \"{$appointment->activity_title}\""
        );

        return back()->with('status', "{$this->name($staff)}'s assignment was cancelled.");
    }

    /** Staff can only be edited while the appointment is still `scheduled`. */
    private function requireScheduled(Appointment $appointment): ?RedirectResponse
    {
        if ($appointment->status !== 'scheduled') {
            return redirect()
                ->route('admin.appointments.index')
                ->with('status', 'Staff can only be assigned to appointments that are scheduled.');
        }

        return null;
    }

    /**
     * Staff who are already actively assigned to ANOTHER scheduled appointment that
     * overlaps this one's date/time. Returns [staff_id => that appointment's title].
     */
    private function busyStaff(Appointment $appointment): array
    {
        if (!$appointment->event_date) {
            return [];
        }

        return DB::table('staff_assignments')
            ->join('appointments', 'appointments.id', '=', 'staff_assignments.appointment_id')
            ->where('staff_assignments.status', self::ASSIGNED)
            ->where('appointments.id', '!=', $appointment->id)
            ->where('appointments.status', 'scheduled')
            ->whereDate('appointments.event_date', $appointment->event_date->toDateString())
            ->where('appointments.start_time', '<', $appointment->end_time)
            ->where('appointments.end_time', '>', $appointment->start_time)
            ->pluck('appointments.activity_title', 'staff_assignments.staff_id')
            ->all();
    }

    private function name(User $u): string
    {
        return trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: "Staff #{$u->id}";
    }
}