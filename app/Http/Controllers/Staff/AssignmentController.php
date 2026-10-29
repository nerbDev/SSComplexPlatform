<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Staff-side "My Assignments": the appointments the admin assigned this staff
 * member to, plus the full team (everyone actively assigned) per appointment.
 *
 * Reads the same staff_assignments pivot the admin module writes; an active
 * assignment has status = 'assigned' (cancelled ones are ignored).
 */
class AssignmentController extends Controller
{
    private const ASSIGNED = 'assigned';

    private const TYPE_LABELS = [
        'appointment'      => 'Appointment',
        'room_reservation' => 'Room Reservation',
        'free_use'         => 'Free Use',
    ];

    public function index(Request $request): View
    {
        $view  = $request->query('view') === 'past' ? 'past' : 'upcoming';
        $today = Carbon::today()->toDateString();
        $me    = Auth::id();

        $base = fn() => Appointment::whereIn('status', ['scheduled', 'finished'])
            ->whereHas('staffAssignments', fn($q) => $q->where('staff_id', $me)->where('status', self::ASSIGNED));

        $upcomingQuery = fn($q) => $q->where('event_date', '>=', $today)->where('status', '!=', 'finished');
        $pastQuery     = fn($q) => $q->where(fn($w) => $w->where('event_date', '<', $today)->orWhere('status', 'finished'));

        $counts = [
            'upcoming' => $upcomingQuery($base())->count(),
            'past'     => $pastQuery($base())->count(),
        ];

        $query = $view === 'past' ? $pastQuery($base()) : $upcomingQuery($base());

        $appointments = $query
            ->with([
                'facility',
                'client',
                'assignedStaff' => fn($q) => $q->wherePivot('status', self::ASSIGNED),
            ])
            ->orderBy('event_date', $view === 'past' ? 'desc' : 'asc')
            ->orderBy('start_time')
            ->paginate(12)
            ->withQueryString();

        $rows = $appointments->getCollection()->map(function (Appointment $a) use ($me, $today) {
            return [
                'id'                 => $a->id,
                'activity'           => $a->activity_title,
                'facility'           => $a->facility->name ?? '—',
                'date'               => $a->event_date ? $a->event_date->format('M j, Y') : '—',
                'time'               => Carbon::parse($a->start_time)->format('g:i A') . ' – ' . Carbon::parse($a->end_time)->format('g:i A'),
                'is_today'           => $a->event_date && $a->event_date->toDateString() === $today,
                'type_label'         => self::TYPE_LABELS[$a->booking_type] ?? ucfirst((string) $a->booking_type),
                'expected_attendees' => $a->expected_attendees,
                'client_name'        => trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: '—',
                'status'             => ucfirst($a->status),
                'team'               => $a->assignedStaff->map(fn($u) => [
                    'name'  => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: "Staff #{$u->id}",
                    'phone' => $u->phone_number,
                    'is_me' => $u->id === $me,
                ])->values()->all(),
            ];
        })->values()->all();

        return view('staff.assignments.index', [
            'view'         => $view,
            'counts'       => $counts,
            'appointments' => $appointments,
            'rows'         => $rows,
        ]);
    }
}