<?php

namespace App\Http\Controllers\Admin\OperationBuilding;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\IncidentReport;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OB Admin event reports: one report per event that was scheduled or has finished.
 * (Whole-facility analytics belong to Sprint 12 — this is the per-event view.)
 */
class EventReportController extends Controller
{
    private const TYPE_LABELS = [
        'appointment'      => 'Appointment',
        'room_reservation' => 'Room Reservation',
        'free_use'         => 'Free Use',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'facility_id' => ['nullable', 'integer'],
            'status'      => ['nullable', 'in:finished,scheduled,all'],
            'q'           => ['nullable', 'string', 'max:100'],
        ]);

        $page = $this->baseQuery($request)
            ->with(['client', 'facility', 'payment'])
            ->withCount([
                'assignedStaff as staff_count' => fn($q) => $q->where('staff_assignments.status', 'assigned'),
                'incidentReports',
                'visitorLogs',
                'feedback',
            ])
            ->orderByDesc('event_date')->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        $rows = $page->getCollection()->map(fn(Appointment $a) => $this->row($a))->values()->all();

        $summary = [
            'events'    => $this->baseQuery($request)->count(),
            'attendees' => (int) $this->baseQuery($request)->sum('expected_attendees'),
            'billed'    => (float) Payment::whereIn('appointment_id', $this->baseQuery($request)->select('appointments.id'))->sum('total_amount'),
            'incidents' => IncidentReport::whereIn('appointment_id', $this->baseQuery($request)->select('appointments.id'))->count(),
        ];

        return view('admin.ob.event-reports.index', [
            'rows'       => $rows,
            'page'       => $page,
            'summary'    => $summary,
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'filters'    => [
                'status'      => $request->query('status', 'finished'),
                'from'        => $request->query('from'),
                'to'          => $request->query('to'),
                'facility_id' => $request->query('facility_id'),
                'q'           => trim((string) $request->query('q', '')),
            ],
        ]);
    }

    public function show(Appointment $appointment): View
    {
        abort_unless(in_array($appointment->status, ['scheduled', 'finished'], true), 404);

        $appointment->load([
            'client', 'facility', 'payment', 'verifiedBy', 'feedback',
            'assignedStaff' => fn($q) => $q->wherePivot('status', 'assigned'),
            'incidentReports.damageDecisions',
            'refunds',
        ]);

        return view('admin.ob.event-reports.show', [
            'appointment'  => $appointment,
            'typeLabel'    => self::TYPE_LABELS[$appointment->booking_type] ?? ucfirst((string) $appointment->booking_type),
            'visitorCount' => $appointment->visitorLogs()->count(),
            'time'         => Carbon::parse($appointment->start_time)->format('g:i A') . ' – ' . Carbon::parse($appointment->end_time)->format('g:i A'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $appointments = $this->baseQuery($request)
            ->with(['client', 'facility', 'payment'])
            ->withCount([
                'assignedStaff as staff_count' => fn($q) => $q->where('staff_assignments.status', 'assigned'),
                'incidentReports',
                'visitorLogs',
            ])
            ->orderByDesc('event_date')->orderByDesc('start_time')
            ->limit(5000)->get();

        return response()->streamDownload(function () use ($appointments) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel opens UTF-8 correctly
            fputcsv($out, ['Date', 'Time', 'Activity', 'Client', 'Facility', 'Type', 'Status', 'Expected attendees', 'Staff assigned', 'Visitor log entries', 'Payment total', 'Payment status', 'Incidents']);

            foreach ($appointments as $a) {
                $r = $this->row($a);
                fputcsv($out, [
                    $r['date'], $r['time'], $r['activity'], $r['client'], $r['facility'], $r['type'], $r['status_label'],
                    $r['attendees'], $r['staff_count'], $r['visitor_count'],
                    $r['payment_total'] !== null ? number_format($r['payment_total'], 2, '.', '') : '',
                    $r['payment_status'], $r['incidents'],
                ]);
            }
            fclose($out);
        }, 'event-reports-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function baseQuery(Request $request): Builder
    {
        $statuses = match ($request->query('status', 'finished')) {
            'scheduled' => ['scheduled'],
            'all'       => ['scheduled', 'finished'],
            default     => ['finished'],
        };

        $q = trim((string) $request->query('q', ''));

        return Appointment::query()
            ->whereIn('status', $statuses)
            ->when($request->filled('from'), fn($x) => $x->whereDate('event_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn($x) => $x->whereDate('event_date', '<=', $request->query('to')))
            ->when($request->filled('facility_id'), fn($x) => $x->where('facility_id', (int) $request->query('facility_id')))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('activity_title', 'like', "%{$q}%")
                      ->orWhereHas('client', fn($c) => $c->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"));
                });
            });
    }

    private function row(Appointment $a): array
    {
        return [
            'id'             => $a->id,
            'date'           => $a->event_date ? $a->event_date->format('M j, Y') : '—',
            'time'           => Carbon::parse($a->start_time)->format('g:i A') . ' – ' . Carbon::parse($a->end_time)->format('g:i A'),
            'activity'       => $a->activity_title,
            'client'         => trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: '—',
            'facility'       => $a->facility->name ?? '—',
            'type'           => self::TYPE_LABELS[$a->booking_type] ?? ucfirst((string) $a->booking_type),
            'is_free'        => $a->track === 'free_use',
            'status'         => $a->status,
            'status_label'   => ucfirst($a->status),
            'attendees'      => $a->expected_attendees,
            'staff_count'    => (int) ($a->staff_count ?? 0),
            'visitor_count'  => (int) ($a->visitor_logs_count ?? 0),
            'payment_total'  => $a->payment ? (float) $a->payment->total_amount : null,
            'payment_status' => $a->payment ? ucfirst(str_replace(['_', '-'], ' ', (string) $a->payment->status)) : ($a->track === 'free_use' ? 'Free use' : '—'),
            'incidents'      => (int) ($a->incident_reports_count ?? 0),
            'has_feedback'   => (int) ($a->feedback_count ?? 0) > 0,
            'show_url'       => route('admin.ob.event-reports.show', $a->id),
        ];
    }
}