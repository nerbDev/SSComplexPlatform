<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\IncidentReport;
use App\Models\MaintenanceReport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff-facing capture (Sprint 10):
 *  - Incident / damage reports (broken ring, damaged chairs…) -> incident_reports.
 *    They land in the OB Admin's Damaged Items panel (Sprint 8) as `open`.
 *  - Facility-condition reports (slippery floor, leaking roof…) -> maintenance_reports.
 *    They land in the Sports/Activity Admin's "Reported issues" tab (Sprint 9) as `open`.
 *
 * Staff only create and read here; reviewing, deciding and resolving belong to the admins.
 */
class ReportingController extends Controller
{
    private const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

    private const INCIDENT_STATUS = ['open' => 'Open', 'under_review' => 'Under review', 'resolved' => 'Resolved'];

    public function index(Request $request): View
    {
        $request->validate([
            'tab'         => ['nullable', 'in:incident,maintenance'],
            'scope'       => ['nullable', 'in:mine,all'],
            'facility_id' => ['nullable', 'integer'],
        ]);

        $tab        = $request->query('tab') === 'maintenance' ? 'maintenance' : 'incident';
        $scope      = $request->query('scope') === 'all' ? 'all' : 'mine';
        $facilityId = $request->filled('facility_id') ? (int) $request->query('facility_id') : null;
        $me         = Auth::id();

        if ($tab === 'incident') {
            $page = IncidentReport::query()
                ->with(['facility', 'appointment', 'reportedBy'])
                ->when($scope === 'mine', fn($q) => $q->where('reported_by', $me))
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->orderByDesc('created_at')->paginate(10)->withQueryString();

            $rows = $page->getCollection()->map(fn(IncidentReport $i) => [
                'id'          => $i->id,
                'facility'    => $i->facility->name ?? '—',
                'event'       => $i->appointment->activity_title ?? null,
                'description' => $i->description,
                'items'       => $i->damaged_items,
                'cost'        => $i->estimated_cost !== null ? (float) $i->estimated_cost : null,
                'refund'      => (bool) $i->refund_related,
                'status'      => $i->status,
                'status_label' => self::INCIDENT_STATUS[$i->status] ?? ucfirst(str_replace('_', ' ', $i->status)),
                'reporter'    => trim(($i->reportedBy->first_name ?? '') . ' ' . ($i->reportedBy->last_name ?? '')) ?: '—',
                'is_mine'     => $i->reported_by === $me,
                'reported_at' => $i->created_at ? $i->created_at->format('M j, Y g:i A') : '—',
                'photo_url'   => $i->photo_path ? asset('storage/' . ltrim($i->photo_path, '/')) : null,
                'resolution'  => $i->status === 'resolved' ? $i->resolution_notes : null,
            ])->values()->all();
        } else {
            $page = DB::table('maintenance_reports as mr')
                ->join('facilities as f', 'f.id', '=', 'mr.facility_id')
                ->join('users as u', 'u.id', '=', 'mr.reported_by')
                ->when($scope === 'mine', fn($q) => $q->where('mr.reported_by', $me))
                ->when($facilityId, fn($q) => $q->where('mr.facility_id', $facilityId))
                ->select('mr.*', 'f.name as facility_name', 'u.first_name', 'u.last_name')
                ->orderByDesc('mr.created_at')->paginate(10)->withQueryString();

            $rows = $page->getCollection()->map(fn($r) => [
                'id'          => $r->id,
                'facility'    => $r->facility_name,
                'title'       => $r->title,
                'description' => $r->description,
                'severity'    => $r->severity,
                'status'      => $r->status,
                'status_label' => ucfirst(str_replace('_', ' ', $r->status)),
                'reporter'    => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: '—',
                'is_mine'     => (int) $r->reported_by === (int) $me,
                'reported_at' => $r->created_at ? Carbon::parse($r->created_at)->format('M j, Y g:i A') : '—',
                'photo_url'   => $r->photo_path ? asset('storage/' . ltrim($r->photo_path, '/')) : null,
            ])->values()->all();
        }

        // Recent events (two weeks back to tomorrow) so an incident can be tied to the booking it happened in.
        $events = Appointment::with('client')
            ->whereIn('status', ['scheduled', 'finished'])
            ->whereBetween('event_date', [Carbon::today()->subDays(14)->toDateString(), Carbon::tomorrow()->toDateString()])
            ->orderByDesc('event_date')->orderByDesc('start_time')->limit(200)->get()
            ->map(fn(Appointment $a) => [
                'id'          => $a->id,
                'facility_id' => $a->facility_id,
                'label'       => $a->event_date->format('M j') . ' · ' . $a->activity_title . ' — '
                    . (trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: 'client'),
            ])->values()->all();

        return view('staff.reports.index', [
            'tab'        => $tab,
            'scope'      => $scope,
            'facilityId' => $facilityId,
            'rows'       => $rows,
            'page'       => $page,
            'counts'     => [
                'incident'    => IncidentReport::where('reported_by', $me)->count(),
                'maintenance' => DB::table('maintenance_reports')->where('reported_by', $me)->count(),
            ],
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'events'     => $events,
            'severities' => self::SEVERITIES,
        ]);
    }

    public function storeIncident(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_id'    => ['required', 'integer', 'exists:facilities,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'description'    => ['required', 'string', 'max:3000'],
            'damaged_items'  => ['nullable', 'string', 'max:2000'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'refund_related' => ['nullable', 'boolean'],
            'photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (!empty($data['appointment_id'])) {
            $appointment = Appointment::find($data['appointment_id']);
            if ($appointment && (int) $appointment->facility_id !== (int) $data['facility_id']) {
                return back()->withErrors(['appointment_id' => 'That event was held in a different facility.'])->withInput()->with('reopen', 'incident');
            }
        }

        if ($request->boolean('refund_related') && empty($data['appointment_id'])) {
            return back()->withErrors(['appointment_id' => 'Pick the event this refund relates to.'])->withInput()->with('reopen', 'incident');
        }

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('incident-photos', 'public') : null;

        $incident = IncidentReport::create([
            'facility_id'    => $data['facility_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'reported_by'    => Auth::id(),
            'description'    => $data['description'],
            'damaged_items'  => $data['damaged_items'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'refund_related' => $request->boolean('refund_related'),
            'photo_path'     => $photoPath,
            'status'         => 'open',
        ]);

        AuditLog::record('staff.incident.reported', $incident, "Incident #{$incident->id} reported" . ($incident->refund_related ? ' (refund related)' : ''));

        return redirect()->route('staff.reports.index', ['tab' => 'incident'])
            ->with('status', 'Incident report submitted. The OB Admin will review it.');
    }

    public function storeMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'severity'    => ['required', Rule::in(array_keys(self::SEVERITIES))],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('maintenance-photos', 'public') : null;

        // forceFill: works whatever the MaintenanceReport model's $fillable says.
        $report = new MaintenanceReport();
        $report->forceFill([
            'facility_id' => $data['facility_id'],
            'reported_by' => Auth::id(),
            'title'       => $data['title'],
            'description' => $data['description'],
            'severity'    => $data['severity'],
            'status'      => 'open',
            'photo_path'  => $photoPath,
        ])->save();

        AuditLog::record('staff.maintenance_report.reported', $report, "Maintenance report #{$report->id} ({$data['severity']}): {$data['title']}");

        return redirect()->route('staff.reports.index', ['tab' => 'maintenance'])
            ->with('status', 'Facility condition report submitted. The Sports & Activity Admin will review it.');
    }
}