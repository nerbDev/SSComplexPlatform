<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Whole-facility reporting & analytics (Sprint 12). Every method takes the same filter:
 * a date range (event dates, except "requests" which count when they were created) and an optional facility.
 *
 * "Events" = appointments that are scheduled or finished. Cancelled ones are counted separately.
 * Utilization assumes the operating hours in config('ssc.operating_hours') until SSC confirms real ones.
 */
class FacilityAnalyticsService
{
    public const HEAT_FROM = 6;   // first hour shown in the heatmaps (6 AM)
    public const HEAT_TO   = 22;  // last hour shown (10 PM)

    private const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    // ------------------------------------------------------------------ summary + volume

    public function summary(Carbon $from, Carbon $to, ?int $facility): array
    {
        $events = fn() => $this->events($from, $to, $facility);

        $requests = Appointment::query()
            ->whereDate('created_at', '>=', $from->toDateString())->whereDate('created_at', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('facility_id', $facility))->count();

        $cancelled = Appointment::query()->where('status', 'cancelled')
            ->whereDate('event_date', '>=', $from->toDateString())->whereDate('event_date', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('facility_id', $facility))->count();

        $eventCount = $events()->count();

        return [
            'requests'    => $requests,
            'events'      => $eventCount,
            'finished'    => $events()->where('status', 'finished')->count(),
            'cancelled'   => $cancelled,
            'cancel_rate' => ($eventCount + $cancelled) > 0 ? round($cancelled / ($eventCount + $cancelled) * 100) : null,
            'attendees'   => (int) $events()->sum('expected_attendees'),
            'hours'       => round((float) $events()->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) / 3600 as h')->value('h'), 1),
            'billed'      => (float) Payment::whereIn('appointment_id', $events()->select('appointments.id'))->sum('total_amount'),
            'days'        => $from->diffInDays($to) + 1,
        ];
    }

    /** Requests (by created date) and events (by event date) per day / week / month, gaps filled with 0. */
    public function volume(Carbon $from, Carbon $to, ?int $facility): array
    {
        $days  = $from->diffInDays($to) + 1;
        $gran  = $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');

        $expr = fn(string $col) => match ($gran) {
            'day'   => "DATE({$col})",
            'week'  => "DATE(DATE_SUB(DATE({$col}), INTERVAL WEEKDAY({$col}) DAY))",
            'month' => "DATE_FORMAT({$col}, '%Y-%m-01')",
        };

        $requests = Appointment::query()
            ->whereDate('created_at', '>=', $from->toDateString())->whereDate('created_at', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('facility_id', $facility))
            ->selectRaw($expr('created_at') . ' as b, COUNT(*) as c')->groupBy('b')->pluck('c', 'b')->all();

        $events = $this->events($from, $to, $facility)
            ->selectRaw($expr('event_date') . ' as b, COUNT(*) as c')->groupBy('b')->pluck('c', 'b')->all();

        $cursor = match ($gran) {
            'day'   => $from->copy()->startOfDay(),
            'week'  => $from->copy()->startOfWeek(),
            'month' => $from->copy()->startOfMonth(),
        };

        $labels = $req = $evt = [];
        while ($cursor->lte($to)) {
            $key      = $cursor->toDateString();
            $labels[] = $gran === 'month' ? $cursor->format('M Y') : $cursor->format('M j');
            $req[]    = (int) ($requests[$key] ?? 0);
            $evt[]    = (int) ($events[$key] ?? 0);
            $gran === 'day' ? $cursor->addDay() : ($gran === 'week' ? $cursor->addWeek() : $cursor->addMonth());
        }

        $statuses = Appointment::query()
            ->whereDate('event_date', '>=', $from->toDateString())->whereDate('event_date', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('facility_id', $facility))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $tracks = $this->events($from, $to, $facility)->selectRaw('track, COUNT(*) as c')->groupBy('track')->pluck('c', 'track')->all();

        return [
            'granularity' => $gran,
            'labels'      => $labels,
            'requests'    => $req,
            'events'      => $evt,
            'max'         => max(1, max($req ?: [0]), max($evt ?: [0])),
            'statuses'    => $statuses,
            'tracks'      => $tracks,
        ];
    }

    // ------------------------------------------------------------------ per facility

    public function facilities(Carbon $from, Carbon $to, ?int $facility): array
    {
        $open  = (int) config('ssc.operating_hours.open', 8);
        $close = (int) config('ssc.operating_hours.close', 20);
        $days  = $from->diffInDays($to) + 1;
        $avail = max(1, ($close - $open) * $days);

        $agg = $this->events($from, $to, $facility)
            ->selectRaw('facility_id, COUNT(*) as events, COALESCE(SUM(expected_attendees), 0) as attendees,
                         COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) / 3600 as hours')
            ->groupBy('facility_id')->get()->keyBy('facility_id');

        $cancelled = Appointment::query()->where('status', 'cancelled')
            ->whereDate('event_date', '>=', $from->toDateString())->whereDate('event_date', '<=', $to->toDateString())
            ->selectRaw('facility_id, COUNT(*) as c')->groupBy('facility_id')->pluck('c', 'facility_id')->all();

        $billed = Payment::query()
            ->join('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->whereIn('appointments.status', ['scheduled', 'finished'])
            ->whereDate('appointments.event_date', '>=', $from->toDateString())->whereDate('appointments.event_date', '<=', $to->toDateString())
            ->selectRaw('appointments.facility_id, COALESCE(SUM(payments.total_amount), 0) as t')
            ->groupBy('appointments.facility_id')->pluck('t', 'facility_id')->all();

        return Facility::query()->when($facility, fn($q) => $q->where('id', $facility))->orderBy('name')->get()
            ->map(function (Facility $f) use ($agg, $cancelled, $billed, $avail) {
                $a     = $agg->get($f->id);
                $hours = round((float) ($a->hours ?? 0), 1);

                return [
                    'id'          => $f->id,
                    'name'        => $f->name,
                    'capacity'    => (int) $f->capacity,
                    'events'      => (int) ($a->events ?? 0),
                    'attendees'   => (int) ($a->attendees ?? 0),
                    'hours'       => $hours,
                    'utilization' => min(100, round($hours / $avail * 100)),
                    'cancelled'   => (int) ($cancelled[$f->id] ?? 0),
                    'billed'      => (float) ($billed[$f->id] ?? 0),
                ];
            })->all();
    }

    // ------------------------------------------------------------------ usage / peak hours

    /** Hours booked per weekday × hour (from bookings). Matrix: [isoWeekday 1..7][hour] = hours booked. */
    public function bookedHeatmap(Carbon $from, Carbon $to, ?int $facility): array
    {
        $matrix = $this->emptyMatrix();

        $this->events($from, $to, $facility)->orderBy('event_date')->limit(5000)
            ->get(['event_date', 'start_time', 'end_time'])
            ->each(function ($e) use (&$matrix) {
                $dow   = $e->event_date->dayOfWeekIso;
                $start = $this->minutes($e->start_time);
                $end   = $this->minutes($e->end_time);

                for ($h = self::HEAT_FROM; $h <= self::HEAT_TO; $h++) {
                    $overlap = min($end, ($h + 1) * 60) - max($start, $h * 60);
                    if ($overlap > 0) {
                        $matrix[$dow][$h] += $overlap / 60;
                    }
                }
            });

        return $this->finishMatrix($matrix, 1);
    }

    /** People signing in per weekday × hour (from the staff visitor log). Matrix: [isoWeekday][hour] = people. */
    public function visitHeatmap(Carbon $from, Carbon $to, ?int $facility): array
    {
        $matrix = $this->emptyMatrix();
        $stats  = ['entries' => 0, 'people' => 0, 'avg_stay' => null];

        if (!Schema::hasTable('visitor_logs')) {
            return $this->finishMatrix($matrix, 0) + ['stats' => $stats];
        }

        $hasParty = Schema::hasColumn('visitor_logs', 'party_size');

        $logs = DB::table('visitor_logs')
            ->when($facility, fn($q) => $q->whereIn('appointment_id', Appointment::select('id')->where('facility_id', $facility)))
            ->where('time_in', '>=', $from->copy()->startOfDay())->where('time_in', '<=', $to->copy()->endOfDay());

        (clone $logs)->get(['time_in', $hasParty ? 'party_size' : DB::raw('1 as party_size')])->each(function ($l) use (&$matrix, &$stats) {
            $t = Carbon::parse($l->time_in);
            $h = $t->hour;
            $stats['entries']++;
            $stats['people'] += (int) $l->party_size;
            if ($h >= self::HEAT_FROM && $h <= self::HEAT_TO) {
                $matrix[$t->dayOfWeekIso][$h] += (int) $l->party_size;
            }
        });

        $avg = (clone $logs)->whereNotNull('time_out')->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as m')->value('m');
        $stats['avg_stay'] = $avg !== null ? (int) round($avg) : null;

        return $this->finishMatrix($matrix, 0) + ['stats' => $stats];
    }

    // ------------------------------------------------------------------ staffing

    public function staffing(Carbon $from, Carbon $to, ?int $facility): array
    {
        if (!Schema::hasTable('staff_assignments')) {
            return ['rows' => [], 'assignments' => 0, 'avg_per_event' => null, 'unstaffed' => [], 'unstaffed_count' => 0];
        }

        $base = fn() => DB::table('staff_assignments as sa')
            ->join('appointments as a', 'a.id', '=', 'sa.appointment_id')
            ->where('sa.status', 'assigned')->whereIn('a.status', ['scheduled', 'finished'])
            ->whereDate('a.event_date', '>=', $from->toDateString())->whereDate('a.event_date', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('a.facility_id', $facility));

        $rows = $base()->join('users as u', 'u.id', '=', 'sa.staff_id')
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->selectRaw('u.first_name, u.last_name, COUNT(DISTINCT a.id) as events,
                         COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(a.end_time, a.start_time))), 0) / 3600 as hours')
            ->orderByDesc('hours')->get()
            ->map(fn($r) => ['name' => trim($r->first_name . ' ' . $r->last_name), 'events' => (int) $r->events, 'hours' => round((float) $r->hours, 1)])->all();

        $assignments = (clone $base())->count();
        $eventCount  = $this->events($from, $to, $facility)->count();

        $unstaffedQuery = fn() => $this->events($from, $to, $facility)
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('staff_assignments as s2')
                ->whereColumn('s2.appointment_id', 'appointments.id')->where('s2.status', 'assigned'));

        $unstaffed = $unstaffedQuery()->with('facility')->orderBy('event_date')->limit(8)->get()
            ->map(fn($a) => ['title' => $a->activity_title, 'facility' => $a->facility->name ?? '—', 'date' => $a->event_date->format('M j, Y'), 'status' => ucfirst($a->status)])->all();

        return [
            'rows'            => $rows,
            'assignments'     => $assignments,
            'avg_per_event'   => $eventCount ? round($assignments / $eventCount, 1) : null,
            'unstaffed'       => $unstaffed,
            'unstaffed_count' => $unstaffedQuery()->count(),
        ];
    }

    // ------------------------------------------------------------------ maintenance history

    public function maintenance(Carbon $from, Carbon $to, ?int $facility): array
    {
        $out = [
            'jobs' => ['total' => 0, 'completed' => 0, 'cancelled' => 0, 'open' => 0, 'avg_delay' => null],
            'issues' => ['total' => 0, 'resolved' => 0, 'high' => 0, 'avg_hours' => null],
            'incidents' => ['total' => 0, 'estimated' => 0.0, 'decided' => 0.0, 'refund_related' => 0],
            'by_facility' => [], 'recent' => [],
        ];

        $per = [];
        $touch = function (int $id) use (&$per) {
            return $per[$id] ??= ['jobs' => 0, 'completed' => 0, 'issues' => 0, 'open_issues' => 0, 'incidents' => 0, 'damage' => 0.0];
        };

        if (Schema::hasTable('maintenance_schedules')) {
            $jobs = fn() => DB::table('maintenance_schedules as ms')
                ->whereDate('ms.scheduled_date', '>=', $from->toDateString())->whereDate('ms.scheduled_date', '<=', $to->toDateString())
                ->when($facility, fn($q) => $q->where('ms.facility_id', $facility));

            $st = $jobs()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
            $out['jobs'] = [
                'total'     => array_sum($st),
                'completed' => $st['completed'] ?? 0,
                'cancelled' => $st['cancelled'] ?? 0,
                'open'      => ($st['scheduled'] ?? 0) + ($st['in_progress'] ?? 0),
                'avg_delay' => $jobs()->where('status', 'completed')->whereNotNull('completed_at')
                    ->selectRaw('AVG(DATEDIFF(DATE(completed_at), scheduled_date)) as d')->value('d'),
            ];

            foreach ($jobs()->selectRaw('facility_id, COUNT(*) as t, SUM(status = "completed") as done')->groupBy('facility_id')->get() as $r) {
                $row = $touch((int) $r->facility_id); $row['jobs'] = (int) $r->t; $row['completed'] = (int) $r->done; $per[(int) $r->facility_id] = $row;
            }

            $out['recent'] = $jobs()->join('facilities as f', 'f.id', '=', 'ms.facility_id')->where('ms.status', 'completed')
                ->orderByDesc('ms.completed_at')->limit(10)
                ->get(['ms.title', 'ms.type', 'ms.outcome_condition', 'ms.completed_at', 'f.name as facility'])
                ->map(fn($r) => ['title' => $r->title, 'type' => ucfirst((string) $r->type), 'facility' => $r->facility,
                    'outcome' => $r->outcome_condition, 'when' => Carbon::parse($r->completed_at)->format('M j, Y')])->all();
        }

        if (Schema::hasTable('maintenance_reports')) {
            $iss = fn() => DB::table('maintenance_reports as mr')
                ->whereDate('mr.created_at', '>=', $from->toDateString())->whereDate('mr.created_at', '<=', $to->toDateString())
                ->when($facility, fn($q) => $q->where('mr.facility_id', $facility));

            $st = $iss()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
            $out['issues'] = [
                'total'     => array_sum($st),
                'resolved'  => $st['resolved'] ?? 0,
                'high'      => $iss()->where('severity', 'high')->count(),
                'avg_hours' => $iss()->where('status', 'resolved')->whereNotNull('resolved_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as h')->value('h'),
            ];

            foreach ($iss()->selectRaw('facility_id, COUNT(*) as t, SUM(status != "resolved") as open_n')->groupBy('facility_id')->get() as $r) {
                $row = $touch((int) $r->facility_id); $row['issues'] = (int) $r->t; $row['open_issues'] = (int) $r->open_n; $per[(int) $r->facility_id] = $row;
            }
        }

        if (Schema::hasTable('incident_reports')) {
            $inc = fn() => DB::table('incident_reports as ir')
                ->whereDate('ir.created_at', '>=', $from->toDateString())->whereDate('ir.created_at', '<=', $to->toDateString())
                ->when($facility, fn($q) => $q->where('ir.facility_id', $facility));

            $decided = Schema::hasTable('damage_decisions')
                ? DB::table('damage_decisions')->selectRaw('incident_report_id, COALESCE(SUM(approved_amount), 0) as amt')->groupBy('incident_report_id')->pluck('amt', 'incident_report_id')->all()
                : [];

            $rows = $inc()->get(['ir.id', 'ir.facility_id', 'ir.estimated_cost', 'ir.refund_related']);
            $out['incidents'] = [
                'total'          => $rows->count(),
                'estimated'      => (float) $rows->sum('estimated_cost'),
                'decided'        => (float) $rows->sum(fn($r) => $decided[$r->id] ?? 0),
                'refund_related' => $rows->where('refund_related', 1)->count(),
            ];

            foreach ($rows as $r) {
                $row = $touch((int) $r->facility_id); $row['incidents']++; $row['damage'] += (float) ($decided[$r->id] ?? 0); $per[(int) $r->facility_id] = $row;
            }
        }

        $names = Facility::pluck('name', 'id');
        foreach ($per as $id => $row) {
            $out['by_facility'][] = ['facility' => $names[$id] ?? "Facility #{$id}"] + $row;
        }
        usort($out['by_facility'], fn($a, $b) => strcmp($a['facility'], $b['facility']));

        return $out;
    }

    // ------------------------------------------------------------------ helpers

    public function range(?string $from, ?string $to): array
    {
        $t = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();
        $f = $from ? Carbon::parse($from)->startOfDay() : $t->copy()->subDays(29)->startOfDay();

        if ($f->gt($t)) {
            [$f, $t] = [$t->copy()->startOfDay(), $f->copy()->endOfDay()];
        }
        if ($f->diffInDays($t) > 366) {
            $f = $t->copy()->subDays(366)->startOfDay();
        }

        return [$f, $t];
    }

    private function events(Carbon $from, Carbon $to, ?int $facility): Builder
    {
        return Appointment::query()
            ->whereIn('appointments.status', ['scheduled', 'finished'])
            ->whereDate('appointments.event_date', '>=', $from->toDateString())
            ->whereDate('appointments.event_date', '<=', $to->toDateString())
            ->when($facility, fn($q) => $q->where('appointments.facility_id', $facility));
    }

    private function emptyMatrix(): array
    {
        $m = [];
        foreach (array_keys(self::DAYS) as $d) {
            for ($h = self::HEAT_FROM; $h <= self::HEAT_TO; $h++) {
                $m[$d][$h] = 0;
            }
        }

        return $m;
    }

    /** Adds max / total / top-5 peak cells. $decimals rounds the displayed values. */
    private function finishMatrix(array $matrix, int $decimals): array
    {
        $cells = [];
        $max = 0; $total = 0;

        foreach ($matrix as $d => $hours) {
            foreach ($hours as $h => $v) {
                $matrix[$d][$h] = round($v, $decimals);
                $max = max($max, $v); $total += $v;
                if ($v > 0) {
                    $cells[] = ['day' => self::DAYS[$d], 'hour' => $h, 'value' => round($v, $decimals)];
                }
            }
        }

        usort($cells, fn($a, $b) => $b['value'] <=> $a['value']);

        return ['matrix' => $matrix, 'max' => $max, 'total' => round($total, $decimals), 'peaks' => array_slice($cells, 0, 5), 'days' => self::DAYS];
    }

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}