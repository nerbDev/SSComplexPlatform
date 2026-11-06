<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Staff desk: visitor / user sign-in and sign-out per visit.
 *
 * A sign-in records who arrived, their organization, how many people came with them and — when it's tied to a
 * booking — whether this person is the event organizer / group leader. time_in / time_out + party_size are the
 * data source for Sprint 12's peak-hours analytics. appointment_id stays empty for walk-ins.
 */
class VisitorLogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'date'           => ['nullable', 'date'],
            'appointment_id' => ['nullable', 'integer'],
        ]);

        $date    = $request->filled('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $isToday = $date->isToday();

        $dayQuery = fn() => VisitorLog::query()
            ->whereDate('time_in', $date->toDateString())
            ->when($request->filled('appointment_id'), fn($q) => $q->where('appointment_id', (int) $request->query('appointment_id')));

        $page = $dayQuery()->with(['appointment.facility', 'loggedBy'])
            ->orderByRaw('time_out is not null')->orderByDesc('time_in')
            ->paginate(20)->withQueryString();

        $rows = $page->getCollection()->map(fn(VisitorLog $v) => $this->row($v))->values()->all();

        $stale = VisitorLog::with('appointment')->whereNull('time_out')
            ->where('time_in', '<', Carbon::today())->orderBy('time_in')->limit(20)->get()
            ->map(fn(VisitorLog $v) => $this->row($v))->all();

        $events = Appointment::with(['client', 'facility'])
            ->whereDate('event_date', Carbon::today())->whereIn('status', ['scheduled', 'finished'])
            ->orderBy('start_time')->get()
            ->map(fn(Appointment $a) => [
                'id'      => $a->id,
                'label'   => Carbon::parse($a->start_time)->format('g:i A') . ' · ' . $a->activity_title . ' (' . ($a->facility->name ?? '—') . ')',
                'booker'  => trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')),
                'expected' => $a->expected_attendees,
            ])->values()->all();

        return view('staff.visitors.index', [
            'rows'      => $rows,
            'page'      => $page,
            'stale'     => $stale,
            'events'    => $events,
            'date'      => $date->toDateString(),
            'isToday'   => $isToday,
            'eventFilter' => $request->query('appointment_id'),
            'stats'     => [
                'inside_entries'  => VisitorLog::whereNull('time_out')->whereDate('time_in', Carbon::today())->count(),
                'inside_people'   => (int) VisitorLog::whereNull('time_out')->whereDate('time_in', Carbon::today())->sum('party_size'),
                'visits_today'    => VisitorLog::whereDate('time_in', Carbon::today())->count(),
                'people_today'    => (int) VisitorLog::whereDate('time_in', Carbon::today())->sum('party_size'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visitor_name'   => ['required', 'string', 'max:255'],
            'organization'   => ['nullable', 'string', 'max:255'],
            'party_size'     => ['required', 'integer', 'min:1', 'max:5000'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'is_organizer'   => ['nullable', 'boolean'],
        ]);

        $appointment = null;
        if (!empty($data['appointment_id'])) {
            $appointment = Appointment::with('facility')->find($data['appointment_id']);

            if (!$appointment->event_date->isToday() || !in_array($appointment->status, ['scheduled', 'finished'], true)) {
                return back()->withErrors(['appointment_id' => 'That booking isn\'t happening today.'])->withInput();
            }
        }

        VisitorLog::create([
            'appointment_id' => $appointment?->id,
            'visitor_name'   => $data['visitor_name'],
            'organization'   => $data['organization'] ?? null,
            'party_size'     => $data['party_size'],
            'is_organizer'   => $appointment ? $request->boolean('is_organizer') : false,
            'time_in'        => now(),
            'logged_by'      => Auth::id(),
        ]);

        $redirect = redirect()->route('staff.visitors.index')->with('status', "{$data['visitor_name']} signed in.");

        // Non-blocking safety check against the facility's safe capacity (hard limit minus the safety buffer).
        if ($appointment && $appointment->facility) {
            $inside = (int) VisitorLog::whereNull('time_out')
                ->whereHas('appointment', fn($q) => $q->where('facility_id', $appointment->facility_id))
                ->sum('party_size');
            $safe = $appointment->facility->safe_capacity;

            if ($inside > $safe) {
                $redirect->with('warning', "Heads up: {$inside} people are now signed in at {$appointment->facility->name}, above its safe capacity of {$safe}.");
            }
        }

        return $redirect;
    }

    public function signOut(VisitorLog $visitor): RedirectResponse
    {
        if ($visitor->time_out) {
            return back()->with('error', 'This visitor is already signed out.');
        }

        $visitor->update(['time_out' => $this->closingTime($visitor)]);

        return back()->with('status', "{$visitor->visitor_name} signed out.");
    }

    /** Sign out everyone still inside today — optionally only for one event. */
    public function signOutAll(Request $request): RedirectResponse
    {
        $data = $request->validate(['appointment_id' => ['nullable', 'integer']]);

        $count = VisitorLog::whereNull('time_out')
            ->whereDate('time_in', Carbon::today())
            ->when(!empty($data['appointment_id']), fn($q) => $q->where('appointment_id', (int) $data['appointment_id']))
            ->update(['time_out' => now(), 'updated_at' => now()]);

        return back()->with('status', $count ? "{$count} " . ($count === 1 ? 'entry' : 'entries') . ' signed out.' : 'Nobody was signed in.');
    }

    /**
     * Entries left open from an earlier day get closed at the event's end time (or 3 hours after arrival)
     * rather than "now", so a forgotten sign-out doesn't turn into a multi-day visit in the analytics.
     */
    private function closingTime(VisitorLog $v): Carbon
    {
        if ($v->time_in->isToday()) {
            return now();
        }

        $end = $v->appointment
            ? Carbon::parse($v->appointment->event_date->toDateString() . ' ' . $v->appointment->end_time)
            : null;

        return ($end && $end->gt($v->time_in)) ? $end : $v->time_in->copy()->addHours(3);
    }

    private function row(VisitorLog $v): array
    {
        $out = $v->time_out;

        return [
            'id'          => $v->id,
            'name'        => $v->visitor_name,
            'organization' => $v->organization,
            'party'       => (int) $v->party_size,
            'organizer'   => (bool) $v->is_organizer,
            'event'       => $v->appointment->activity_title ?? null,
            'facility'    => $v->appointment->facility->name ?? null,
            'time_in'     => $v->time_in->format('g:i A'),
            'day'         => $v->time_in->format('M j'),
            'time_out'    => $out ? $out->format('g:i A') : null,
            'duration'    => $out ? $this->duration($v->time_in, $out) : null,
            'inside'      => $out === null,
            'logged_by'   => $v->loggedBy ? trim($v->loggedBy->first_name . ' ' . $v->loggedBy->last_name) : '—',
            'out_url'     => route('staff.visitors.signout', $v->id),
        ];
    }

    private function duration(Carbon $in, Carbon $out): string
    {
        $mins = max(0, (int) $in->diffInMinutes($out));

        return $mins >= 60 ? intdiv($mins, 60) . ' h ' . ($mins % 60) . ' m' : $mins . ' m';
    }
}