<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Updated for the real schema introduced in Sprint 2 (see
     * ReservationController/PaymentController):
     *   - appointments.facility_id (FK to facilities) replaces the old
     *     guessed `function_unit` string column.
     *   - appointments.activity_title replaces the old `activity` column.
     *   - appointments.status now uses the real pipeline:
     *     pending -> verified -> payment_submitted -> receipt_confirmed ->
     *     scheduled -> finished, with cancelled/rescheduled as branches.
     *   - users.barangay is still assumed (nullable string) — not yet a
     *     real column as far as this controller knows; guarded as before.
     *
     * Every query is still guarded with Schema::hasTable()/hasColumn() so
     * the dashboard renders a clean zero-state before these tables/columns
     * exist or are empty.
     */

    /** Statuses that count as "occupying" a facility's time today. */
    private const OCCUPYING_STATUSES = ['pending', 'verified', 'payment_submitted', 'receipt_confirmed', 'form_submitted', 'scheduled', 'finished'];

    /** Facility accent colors for the dashboard cards — cosmetic only, not stored in the DB. */
    private const FACILITY_COLORS = ['var(--green-500)', 'var(--green-700)', 'var(--orange-500)', 'var(--ink-600)'];

    public function index()
    {
        $hasAppointments = Schema::hasTable('appointments');
        $hasFacilities = Schema::hasTable('facilities');
        $hasBarangay = Schema::hasTable('users') && Schema::hasColumn('users', 'barangay');

        return view('admin.dashboard', [
            'clientsPerBarangay'      => $this->clientsPerBarangay($hasBarangay),
            'appointmentsPerBarangay' => $this->appointmentsPerBarangay($hasAppointments, $hasBarangay),
            'appointmentsThisWeek'    => $this->appointmentsThisWeek($hasAppointments),
            'scheduleDays'            => $this->scheduleDays($hasAppointments),
            'scheduleAppointments'    => $this->scheduleAppointments($hasAppointments, $hasFacilities),
            'weekOverview'            => $this->weekOverview($hasAppointments),
            'availableHours'          => $this->availableHours($hasAppointments, $hasFacilities),
            'roomStatus'              => $this->roomStatus($hasAppointments, $hasFacilities),
            'pendingReportsCount'     => 0,
        ]);
    }

    private function clientsPerBarangay(bool $hasBarangay): array
    {
        if (!$hasBarangay) {
            return ['total' => 0, 'trend' => 0, 'series' => [], 'top_barangay' => null];
        }

        $total = DB::table('users')->where('role', 'client')->distinct()->count('id');

        $byBarangay = DB::table('users')
            ->where('role', 'client')
            ->whereNotNull('barangay')
            ->select('barangay', DB::raw('count(*) as total'))
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'total' => $total,
            'trend' => 0, // wire up to last month's count once historical data exists
            'series' => $this->toSparkline($byBarangay->pluck('total')->toArray()),
            'top_barangay' => optional($byBarangay->first())->barangay,
        ];
    }

    private function appointmentsPerBarangay(bool $hasAppointments, bool $hasBarangay): array
    {
        if (!$hasAppointments || !$hasBarangay) {
            return ['total' => 0, 'trend' => 0, 'series' => [], 'top_barangay' => null];
        }

        $rows = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->whereNotNull('users.barangay')
            ->select('users.barangay', DB::raw('count(*) as total'))
            ->groupBy('users.barangay')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'total' => $rows->sum('total'),
            'trend' => 0,
            'series' => $this->toSparkline($rows->pluck('total')->toArray()),
            'top_barangay' => optional($rows->first())->barangay,
        ];
    }

    private function appointmentsThisWeek(bool $hasAppointments): array
    {
        if (!$hasAppointments) {
            return ['total' => 0, 'trend' => 0, 'series' => array_fill(0, 7, 0)];
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $series = [];
        for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
            $series[] = DB::table('appointments')
                ->whereDate('event_date', $d->toDateString())
                ->count();
        }

        $prevWeekTotal = DB::table('appointments')
            ->whereBetween('event_date', [
                $startOfWeek->copy()->subWeek()->toDateString(),
                $endOfWeek->copy()->subWeek()->toDateString(),
            ])->count();

        $total = array_sum($series);
        $trend = $prevWeekTotal > 0 ? round((($total - $prevWeekTotal) / $prevWeekTotal) * 100) : 0;

        return [
            'total' => $total,
            'trend' => $trend,
            'series' => $this->toSparkline($series),
        ];
    }

    private function scheduleDays(bool $hasAppointments): array
    {
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $startOfWeek = Carbon::now()->startOfWeek();

        $days = [];
        foreach ($labels as $i => $label) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = $hasAppointments
                ? DB::table('appointments')->whereDate('event_date', $date->toDateString())->count()
                : 0;

            $days[] = [
                'label' => $label,
                'date' => $date->toDateString(),
                'count' => $count,
                'is_selected' => $date->isToday(),
            ];
        }

        return $days;
    }

    private function scheduleAppointments(bool $hasAppointments, bool $hasFacilities): array
    {
        if (!$hasAppointments || !$hasFacilities) {
            return [];
        }

        $rows = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->join('facilities', 'facilities.id', '=', 'appointments.facility_id')
            ->whereDate('appointments.event_date', Carbon::today()->toDateString())
            ->orderBy('appointments.start_time')
            ->select(
                'appointments.start_time',
                'appointments.activity_title',
                'appointments.status',
                'facilities.name as facility_name',
                'users.first_name',
                'users.last_name'
            )
            ->limit(10)
            ->get();

        return $rows->map(function ($row) {
            $name = trim($row->first_name . ' ' . $row->last_name);
            $initials = collect(explode(' ', $name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

            return [
                'time'         => Carbon::parse($row->start_time)->format('g:i A'),
                'client_name'  => $name ?: 'Unnamed client',
                'initials'     => $initials ?: '—',
                'activity'     => $row->activity_title,
                'unit'         => $row->facility_name,
                'status'       => ucfirst(str_replace('_', ' ', $row->status)),
                'status_class' => $this->statusPillClass($row->status),
            ];
        })->toArray();
    }

    /**
     * Maps the real status pipeline onto the dashboard's existing 3
     * status-pill styles (confirmed / pending / completed). Swap this for
     * a dedicated class per status once the Blade CSS grows to support them.
     */
    private function statusPillClass(string $status): string
    {
        return match (strtolower($status)) {
            'verified', 'payment_submitted', 'receipt_confirmed', 'form_submitted', 'scheduled' => 'confirmed',
            'finished', 'completed' => 'completed',
            default => 'pending', // pending, cancelled, rescheduled
        };
    }

    private function weekOverview(bool $hasAppointments): array
    {
        if (!$hasAppointments) {
            return ['total' => 0, 'paid' => 0, 'free_use' => 0, 'paid_pct' => 0, 'free_use_pct' => 0];
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $paid = DB::table('appointments')
            ->whereBetween('event_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('track', 'paid')->count();

        $freeUse = DB::table('appointments')
            ->whereBetween('event_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('track', 'free_use')->count();

        $total = $paid + $freeUse;

        return [
            'total' => $total,
            'paid' => $paid,
            'free_use' => $freeUse,
            'paid_pct' => $total > 0 ? round(($paid / $total) * 100) : 0,
            'free_use_pct' => $total > 0 ? round(($freeUse / $total) * 100) : 0,
        ];
    }

    private function availableHours(bool $hasAppointments, bool $hasFacilities): array
    {
        if (!$hasFacilities) {
            return [];
        }

        $facilities = DB::table('facilities')->where('is_active', true)->orderBy('name')->get();
        $openHours = 12; // 8am–8pm; adjust once real operating hours are confirmed

        return $facilities->map(function ($facility, $i) use ($hasAppointments, $openHours) {
            $bookedHours = 0;

            if ($hasAppointments) {
                $bookedHours = DB::table('appointments')
                    ->where('facility_id', $facility->id)
                    ->whereDate('event_date', Carbon::today()->toDateString())
                    ->whereIn('status', self::OCCUPYING_STATUSES)
                    ->get(['start_time', 'end_time'])
                    ->sum(fn($row) => Carbon::parse($row->start_time)->diffInHours(Carbon::parse($row->end_time)));
            }

            $availableHours = max(0, $openHours - $bookedHours);
            $utilization = $openHours > 0 ? min(100, round(($bookedHours / $openHours) * 100)) : 0;

            return [
                'name' => $facility->name,
                'capacity' => $facility->capacity,
                'color' => self::FACILITY_COLORS[$i % count(self::FACILITY_COLORS)],
                'booked_hours' => $bookedHours,
                'available_hours' => $availableHours,
                'utilization' => $utilization,
                'status' => $availableHours > 0 ? 'Open' : 'Full',
            ];
        })->toArray();
    }

    private function roomStatus(bool $hasAppointments, bool $hasFacilities): array
    {
        $hours = $this->availableHours($hasAppointments, $hasFacilities);

        return array_map(fn($u) => [
            'name' => $u['name'],
            'percent' => $u['utilization'],
        ], $hours);
    }

    private function toSparkline(array $values): array
    {
        if (empty($values)) {
            return [];
        }
        $max = max($values) ?: 1;
        return array_map(fn($v) => (int) round(($v / $max) * 100), $values);
    }
}