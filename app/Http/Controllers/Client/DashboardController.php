<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Poster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * NOTE ON ASSUMPTIONS
     * --------------------
     * Same appointments/unavailable_slots/rates/activity_types tables
     * referenced in the admin DashboardController. Two extras assumed here:
     *
     *   - appointments.status is assumed to eventually carry more values
     *     than the admin dashboard's pending/confirmed/completed/cancelled
     *     (e.g. once rescheduling exists). For this "My Activities" list
     *     they're bucketed into exactly the four the client cares about:
     *       ongoing      <- pending, confirmed, ongoing
     *       rescheduled  <- rescheduled
     *       cancelled    <- cancelled
     *       finished     <- completed, finished
     *     Adjust the mapping in statusBucket() once the real enum is set.
     *
     *   - rates.rate_per_hour is a decimal; formatted here as "₱250/hr".
     */

    public function index()
    {
        $hasAppointments = Schema::hasTable('appointments');
        $hasUnavailable  = Schema::hasTable('unavailable_slots');
        $hasRates        = Schema::hasTable('rates');
        $hasActivityTypes = Schema::hasTable('activity_types');

        [$activities, $activityCounts] = $this->myActivities($hasAppointments);

        return view('client.dashboard', [
            'posters'          => $this->posters(),
            'activities'       => $activities,
            'activityCounts'   => $activityCounts,
            'unavailableSlots' => $this->unavailableSlots($hasUnavailable),
            'rates'            => $this->rates($hasRates),
            'activityTypes'    => $this->activityTypes($hasActivityTypes),
        ]);
    }

    private function posters(): array
    {
        if (!Schema::hasTable('posters')) {
            return [];
        }

        return Poster::active()->ordered()->get()->map(function (Poster $poster) {
            return [
                'image'       => $poster->image_path ? 'storage/' . $poster->image_path : null,
                'title'       => $poster->title,
                'date'        => optional($poster->event_date)->format('M j, Y'),
                'time'        => $poster->event_time,
                'unit'        => $poster->unit,
                'description' => $poster->description,
                'status'      => ucfirst($poster->status),
            ];
        })->toArray();
    }

    private function myActivities(bool $hasAppointments): array
    {
        $counts = ['all' => 0, 'ongoing' => 0, 'rescheduled' => 0, 'cancelled' => 0, 'finished' => 0];

        if (!$hasAppointments || !Auth::check()) {
            return [[], $counts];
        }

        $rows = DB::table('appointments')
            ->where('user_id', Auth::id())
            ->orderByDesc('event_date')
            ->orderByDesc('start_time')
            ->limit(50)
            ->get();

        $activities = $rows->map(function ($row) use (&$counts) {
            $bucket = $this->statusBucket($row->status ?? '');
            $counts[$bucket]++;
            $counts['all']++;

            $labels = [
                'ongoing'     => 'Ongoing',
                'rescheduled' => 'Re-scheduled',
                'cancelled'   => 'Cancelled',
                'finished'    => 'Finished',
            ];

            $dateTime = trim(
                (isset($row->event_date) ? Carbon::parse($row->event_date)->format('M j, Y') : '') .
                (isset($row->start_time) ? ' · ' . Carbon::parse($row->start_time)->format('g:i A') : '')
            );

            return [
                'activity'     => $row->activity ?? '—',
                'unit'         => $row->function_unit ?? '—',
                'date_time'    => $dateTime ?: '—',
                'status_key'   => $bucket,
                'status_label' => $labels[$bucket],
            ];
        })->toArray();

        return [$activities, $counts];
    }

    private function statusBucket(string $status): string
    {
        return match (strtolower($status)) {
            'rescheduled', 're-scheduled' => 'rescheduled',
            'cancelled', 'canceled'       => 'cancelled',
            'completed', 'finished'       => 'finished',
            default                       => 'ongoing', // pending, confirmed, ongoing, or anything unrecognized
        };
    }

    private function unavailableSlots(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('unavailable_slots')
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $date = Carbon::parse($row->date);
                $timeRange = ($row->start_time && $row->end_time)
                    ? Carbon::parse($row->start_time)->format('g:i A') . ' – ' . Carbon::parse($row->end_time)->format('g:i A')
                    : 'Whole day';

                return [
                    'day'        => $date->format('d'),
                    'month'      => $date->format('M'),
                    'label'      => $date->format('l, M j, Y'),
                    'time_range' => $timeRange,
                    'unit'       => $row->unit ?? null,
                    'reason'     => $row->reason ?? null,
                ];
            })->toArray();
    }

    private function rates(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('rates')
            ->orderBy('unit')
            ->get()
            ->map(fn($row) => [
                'unit' => $row->unit,
                'rate' => '₱' . number_format((float) $row->rate_per_hour, 0) . '/hr',
                'note' => $row->note ?? null,
            ])->toArray();
    }

    private function activityTypes(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('activity_types')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }
}