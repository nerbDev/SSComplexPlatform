<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * NOTE ON ASSUMPTIONS
     * --------------------
     * This assumes two tables that don't exist yet in the project:
     *   - users.barangay            (nullable string, role = 'client')
     *   - appointments               (id, user_id, function_unit, activity,
     *                                  scheduled_date, start_time, end_time,
     *                                  status, track, created_at)
     *     function_unit values: 'Function 1' | 'Function 2' | 'Function 3' | 'Lobby and Whole Court'
     *     status values:        'pending' | 'confirmed' | 'completed' | 'cancelled'
     *     track values:         'paid' | 'free_use'
     *
     * Every query below is guarded with Schema::hasTable()/hasColumn() so the
     * dashboard renders a clean zero-state even if these don't exist yet or
     * are empty. Rename the table/column names to match your actual schema.
     */

    public function index()
    {
        $hasAppointments = Schema::hasTable('appointments');
        $hasBarangay = Schema::hasTable('users') && Schema::hasColumn('users', 'barangay');

        return view('admin.dashboard', [
            'clientsPerBarangay'      => $this->clientsPerBarangay($hasBarangay),
            'appointmentsPerBarangay' => $this->appointmentsPerBarangay($hasAppointments, $hasBarangay),
            'appointmentsThisWeek'    => $this->appointmentsThisWeek($hasAppointments),
            'scheduleDays'            => $this->scheduleDays($hasAppointments),
            'scheduleAppointments'    => $this->scheduleAppointments($hasAppointments),
            'weekOverview'            => $this->weekOverview($hasAppointments),
            'availableHours'          => $this->availableHours($hasAppointments),
            'roomStatus'              => $this->roomStatus($hasAppointments),
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
                ->whereDate('scheduled_date', $d->toDateString())
                ->count();
        }

        $prevWeekTotal = DB::table('appointments')
            ->whereBetween('scheduled_date', [
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
                ? DB::table('appointments')->whereDate('scheduled_date', $date->toDateString())->count()
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

    private function scheduleAppointments(bool $hasAppointments): array
    {
        if (!$hasAppointments) {
            return [];
        }

        $rows = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->whereDate('scheduled_date', Carbon::today()->toDateString())
            ->orderBy('start_time')
            ->select(
                'appointments.start_time',
                'appointments.activity',
                'appointments.function_unit',
                'appointments.status',
                'users.first_name',
                'users.last_name'
            )
            ->limit(10)
            ->get();

        return $rows->map(function ($row) {
            $name = trim($row->first_name . ' ' . $row->last_name);
            $initials = collect(explode(' ', $name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');

            $statusMap = [
                'confirmed' => 'confirmed',
                'completed' => 'completed',
                'pending'   => 'pending',
            ];

            return [
                'time' => Carbon::parse($row->start_time)->format('g:i A'),
                'client_name' => $name ?: 'Unnamed client',
                'initials' => $initials ?: '—',
                'activity' => $row->activity,
                'unit' => $row->function_unit,
                'status' => ucfirst($row->status),
                'status_class' => $statusMap[$row->status] ?? 'pending',
            ];
        })->toArray();
    }

    private function weekOverview(bool $hasAppointments): array
    {
        if (!$hasAppointments) {
            return ['total' => 0, 'paid' => 0, 'free_use' => 0, 'paid_pct' => 0, 'free_use_pct' => 0];
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $paid = DB::table('appointments')
            ->whereBetween('scheduled_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('track', 'paid')->count();

        $freeUse = DB::table('appointments')
            ->whereBetween('scheduled_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
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

    private function availableHours(bool $hasAppointments): array
    {
        $units = [
            ['name' => 'Function 1', 'capacity' => 300, 'color' => 'var(--green-500)', 'open_hours' => 12],
            ['name' => 'Function 2', 'capacity' => 200, 'color' => 'var(--green-700)', 'open_hours' => 12],
            ['name' => 'Function 3', 'capacity' => 200, 'color' => 'var(--orange-500)', 'open_hours' => 12],
            ['name' => 'Lobby and Whole Court', 'capacity' => 2000, 'color' => 'var(--ink-600)', 'open_hours' => 12],
        ];

        return array_map(function ($unit) use ($hasAppointments) {
            $bookedHours = 0;

            if ($hasAppointments) {
                $bookedHours = DB::table('appointments')
                    ->where('function_unit', $unit['name'])
                    ->whereDate('scheduled_date', Carbon::today()->toDateString())
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->get(['start_time', 'end_time'])
                    ->sum(fn($row) => Carbon::parse($row->start_time)->diffInHours(Carbon::parse($row->end_time)));
            }

            $availableHours = max(0, $unit['open_hours'] - $bookedHours);
            $utilization = $unit['open_hours'] > 0 ? min(100, round(($bookedHours / $unit['open_hours']) * 100)) : 0;

            return [
                'name' => $unit['name'],
                'capacity' => $unit['capacity'],
                'color' => $unit['color'],
                'booked_hours' => $bookedHours,
                'available_hours' => $availableHours,
                'utilization' => $utilization,
                'status' => $availableHours > 0 ? 'Open' : 'Full',
            ];
        }, $units);
    }

    private function roomStatus(bool $hasAppointments): array
    {
        $hours = $this->availableHours($hasAppointments);

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