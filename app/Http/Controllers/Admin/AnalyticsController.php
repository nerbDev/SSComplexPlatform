<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Whole-facility reporting & analytics for both admin types (Sprint 12). Printable from the browser,
 * and every section can be exported as CSV.
 */
class AnalyticsController extends Controller
{
    private const SECTIONS = ['volume', 'facilities', 'peak-bookings', 'peak-visits', 'staffing', 'maintenance'];

    public function __construct(private FacilityAnalyticsService $analytics)
    {
    }

    public function index(Request $request): View
    {
        [$from, $to, $facility] = $this->filters($request);

        return view('admin.analytics.index', [
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'facilityId'  => $facility,
            'facilities'  => Facility::orderBy('name')->get(['id', 'name']),
            'summary'     => $this->analytics->summary($from, $to, $facility),
            'volume'      => $this->analytics->volume($from, $to, $facility),
            'byFacility'  => $this->analytics->facilities($from, $to, $facility),
            'booked'      => $this->analytics->bookedHeatmap($from, $to, $facility),
            'visits'      => $this->analytics->visitHeatmap($from, $to, $facility),
            'staffing'    => $this->analytics->staffing($from, $to, $facility),
            'maintenance' => $this->analytics->maintenance($from, $to, $facility),
            'heatFrom'    => FacilityAnalyticsService::HEAT_FROM,
            'heatTo'      => FacilityAnalyticsService::HEAT_TO,
            'openHours'   => config('ssc.operating_hours'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate(['section' => ['required', 'in:' . implode(',', self::SECTIONS)]]);
        [$from, $to, $facility] = $this->filters($request);
        $section = $request->query('section');

        [$header, $rows] = match ($section) {
            'volume' => (function () use ($from, $to, $facility) {
                $v = $this->analytics->volume($from, $to, $facility);
                $rows = [];
                foreach ($v['labels'] as $i => $label) {
                    $rows[] = [$label, $v['requests'][$i], $v['events'][$i]];
                }
                return [['Period (' . $v['granularity'] . ')', 'Requests created', 'Events held'], $rows];
            })(),
            'facilities' => [
                ['Facility', 'Capacity', 'Events', 'Hours booked', 'Utilization %', 'Attendees (expected)', 'Cancelled', 'Payments on file'],
                array_map(fn($r) => [$r['name'], $r['capacity'], $r['events'], $r['hours'], $r['utilization'], $r['attendees'], $r['cancelled'], number_format($r['billed'], 2, '.', '')],
                    $this->analytics->facilities($from, $to, $facility)),
            ],
            'peak-bookings', 'peak-visits' => (function () use ($section, $from, $to, $facility) {
                $h = $section === 'peak-bookings'
                    ? $this->analytics->bookedHeatmap($from, $to, $facility)
                    : $this->analytics->visitHeatmap($from, $to, $facility);
                $rows = [];
                foreach ($h['matrix'] as $d => $hours) {
                    foreach ($hours as $hour => $value) {
                        $rows[] = [$h['days'][$d], sprintf('%02d:00', $hour), $value];
                    }
                }
                return [['Weekday', 'Hour', $section === 'peak-bookings' ? 'Hours booked' : 'People signed in'], $rows];
            })(),
            'staffing' => [
                ['Staff', 'Events', 'Hours assigned'],
                array_map(fn($r) => [$r['name'], $r['events'], $r['hours']], $this->analytics->staffing($from, $to, $facility)['rows']),
            ],
            'maintenance' => [
                ['Facility', 'Maintenance jobs', 'Completed', 'Issues reported', 'Open issues', 'Incidents', 'Damage decided (PHP)'],
                array_map(fn($r) => [$r['facility'], $r['jobs'], $r['completed'], $r['issues'], $r['open_issues'], $r['incidents'], number_format($r['damage'], 2, '.', '')],
                    $this->analytics->maintenance($from, $to, $facility)['by_facility']),
            ],
        };

        $name = "analytics-{$section}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'facility_id' => ['nullable', 'integer'],
        ]);

        [$from, $to] = $this->analytics->range($request->query('from'), $request->query('to'));

        return [$from, $to, $request->filled('facility_id') ? (int) $request->query('facility_id') : null];
    }
}