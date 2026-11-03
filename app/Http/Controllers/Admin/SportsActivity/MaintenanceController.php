<?php

namespace App\Http\Controllers\Admin\SportsActivity;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\ReservationController;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\EquipmentInventory;
use App\Models\Facility;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sports/Activity Admin maintenance overview + scheduling.
 *
 *  scheduled -> in_progress -> completed, or scheduled -> cancelled.
 *
 * Scheduling warns about overlaps with booked events and other maintenance in the same facility
 * (the admin can still force it). If a job is linked to an inventory item, starting it marks the
 * item "under_maintenance" and completing it sets the condition the item was left in
 * (this admin owns the inventory, so that's their call).
 */
class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'tab'         => ['nullable', 'in:upcoming,history,issues'],
            'facility_id' => ['nullable', 'integer'],
            'q'           => ['nullable', 'string', 'max:100'],
        ]);

        $tab = in_array($request->query('tab'), ['history', 'issues'], true) ? $request->query('tab') : 'upcoming';
        $q   = trim((string) $request->query('q', ''));

        if ($tab === 'issues') {
            return $this->issues($request, $q);
        }

        $query = MaintenanceSchedule::query()
            ->with(['facility', 'equipment', 'assignedTo'])
            ->when($request->filled('facility_id'), fn($x) => $x->where('facility_id', (int) $request->query('facility_id')))
            ->when($q !== '', fn($x) => $x->where(fn($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhereHas('equipment', fn($e) => $e->where('item_name', 'like', "%{$q}%"))));

        if ($tab === 'history') {
            $query->whereIn('status', ['completed', 'cancelled'])->orderByDesc('scheduled_date')->orderByDesc('start_time');
        } else {
            $query->whereIn('status', MaintenanceSchedule::OPEN)->orderBy('scheduled_date')->orderBy('start_time');
        }

        $page = $query->paginate(12)->withQueryString();
        $today = Carbon::today();

        $rows = $page->getCollection()->map(function (MaintenanceSchedule $m) use ($today) {
            $open = in_array($m->status, MaintenanceSchedule::OPEN, true);

            return [
                'id'             => $m->id,
                'title'          => $m->title,
                'description'    => $m->description,
                'type'           => $m->type,
                'type_label'     => $m->type_label,
                'facility_id'    => $m->facility_id,
                'facility'       => $m->facility->name ?? '—',
                'equipment_id'   => $m->equipment_inventory_id,
                'equipment'      => $m->equipment->item_name ?? null,
                'report_id'      => $m->maintenance_report_id,
                'date'           => $m->scheduled_date->format('M j, Y'),
                'date_value'     => $m->scheduled_date->toDateString(),
                'start'          => substr($m->start_time, 0, 5),
                'end'            => substr($m->end_time, 0, 5),
                'time'           => Carbon::parse($m->start_time)->format('g:i A') . ' – ' . Carbon::parse($m->end_time)->format('g:i A'),
                'assigned_id'    => $m->assigned_to,
                'assigned'       => $m->assignedTo ? trim($m->assignedTo->first_name . ' ' . $m->assignedTo->last_name) : null,
                'status'         => $m->status,
                'status_label'   => MaintenanceSchedule::STATUSES[$m->status] ?? ucfirst($m->status),
                'overdue'        => $m->status === 'scheduled' && $m->scheduled_date->lt($today),
                'conflicts'      => $open ? $this->conflicts($m->facility_id, $m->scheduled_date->toDateString(), $m->start_time, $m->end_time, $m->id) : [],
                'outcome'        => $m->outcome_condition,
                'notes'          => $m->completion_notes,
                'completed_at'   => $m->completed_at ? $m->completed_at->format('M j, Y g:i A') : null,
                'has_item'       => (bool) $m->equipment_inventory_id,
                'edit_url'       => route('admin.sa.maintenance.update', $m->id),
                'start_url'      => route('admin.sa.maintenance.start', $m->id),
                'complete_url'   => route('admin.sa.maintenance.complete', $m->id),
                'cancel_url'     => route('admin.sa.maintenance.cancel', $m->id),
            ];
        })->values()->all();

        return view('admin.sa.maintenance.index', $this->shared($request) + [
            'tab'        => $tab,
            'rows'       => $rows,
            'issueRows'  => [],
            'page'       => $page,
            'search'     => $q,
        ]);
    }

    /** "Reported issues" tab: maintenance reports staff filed, with a way to schedule a job for each. */
    private function issues(Request $request, string $q): View
    {
        $status   = $request->query('status', 'active');
        $severity = $request->query('severity');

        $page = DB::table('maintenance_reports as mr')
            ->join('facilities as f', 'f.id', '=', 'mr.facility_id')
            ->join('users as u', 'u.id', '=', 'mr.reported_by')
            ->leftJoin('users as ru', 'ru.id', '=', 'mr.resolved_by')
            ->when($status === 'active', fn($x) => $x->whereIn('mr.status', ['open', 'in_progress']))
            ->when(in_array($status, ['open', 'in_progress', 'resolved'], true), fn($x) => $x->where('mr.status', $status))
            ->when(in_array($severity, ['low', 'medium', 'high'], true), fn($x) => $x->where('mr.severity', $severity))
            ->when($request->filled('facility_id'), fn($x) => $x->where('mr.facility_id', (int) $request->query('facility_id')))
            ->when($q !== '', fn($x) => $x->where(fn($w) => $w->where('mr.title', 'like', "%{$q}%")->orWhere('mr.description', 'like', "%{$q}%")))
            ->select('mr.*', 'f.name as facility_name', 'u.first_name as rep_first', 'u.last_name as rep_last', 'ru.first_name as res_first', 'ru.last_name as res_last')
            ->selectSub(fn($sub) => $sub->from('maintenance_schedules as ms')->select('ms.id')
                ->whereColumn('ms.maintenance_report_id', 'mr.id')->whereIn('ms.status', MaintenanceSchedule::OPEN)
                ->orderByDesc('ms.id')->limit(1), 'schedule_id')
            ->orderByRaw("case mr.status when 'open' then 0 when 'in_progress' then 1 else 2 end")
            ->orderByRaw("case mr.severity when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByDesc('mr.created_at')
            ->paginate(12)->withQueryString();

        $issueRows = $page->getCollection()->map(fn($r) => [
            'id'           => $r->id,
            'title'        => $r->title,
            'description'  => $r->description,
            'severity'     => $r->severity,
            'status'       => $r->status,
            'status_label' => ucfirst(str_replace('_', ' ', $r->status)),
            'facility_id'  => $r->facility_id,
            'facility'     => $r->facility_name,
            'reporter'     => trim(($r->rep_first ?? '') . ' ' . ($r->rep_last ?? '')) ?: '—',
            'reported_at'  => $r->created_at ? Carbon::parse($r->created_at)->format('M j, Y') : '—',
            'resolved_by'  => trim(($r->res_first ?? '') . ' ' . ($r->res_last ?? '')) ?: null,
            'resolved_at'  => $r->resolved_at ? Carbon::parse($r->resolved_at)->format('M j, Y g:i A') : null,
            'photo_url'    => $r->photo_path ? asset('storage/' . ltrim($r->photo_path, '/')) : null,
            'schedule_id'  => $r->schedule_id,
            'progress_url' => route('admin.sa.maintenance.reports.progress', $r->id),
            'resolve_url'  => route('admin.sa.maintenance.reports.resolve', $r->id),
        ])->values()->all();

        return view('admin.sa.maintenance.index', $this->shared($request) + [
            'tab'          => 'issues',
            'rows'         => [],
            'issueRows'    => $issueRows,
            'page'         => $page,
            'search'       => $q,
            'issueStatus'  => $status,
            'issueSeverity' => $severity,
        ]);
    }

    /** Data every tab of the maintenance page needs (stats, counts, form options). */
    private function shared(Request $request): array
    {
        $today     = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd   = Carbon::now()->endOfWeek()->toDateString();

        return [
            'facilityId' => $request->query('facility_id'),
            'stats'      => [
                'this_week'   => MaintenanceSchedule::whereIn('status', MaintenanceSchedule::OPEN)->whereBetween('scheduled_date', [$weekStart, $weekEnd])->count(),
                'in_progress' => MaintenanceSchedule::where('status', 'in_progress')->count(),
                'overdue'     => MaintenanceSchedule::where('status', 'scheduled')->whereDate('scheduled_date', '<', $today->toDateString())->count(),
                'done_month'  => MaintenanceSchedule::where('status', 'completed')->where('completed_at', '>=', Carbon::now()->startOfMonth())->count(),
            ],
            'counts'     => [
                'upcoming' => MaintenanceSchedule::whereIn('status', MaintenanceSchedule::OPEN)->count(),
                'history'  => MaintenanceSchedule::whereIn('status', ['completed', 'cancelled'])->count(),
                'issues'   => DB::table('maintenance_reports')->whereIn('status', ['open', 'in_progress'])->count(),
                'issues_high' => DB::table('maintenance_reports')->whereIn('status', ['open', 'in_progress'])->where('severity', 'high')->count(),
            ],
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'equipment'  => EquipmentInventory::orderBy('item_name')->get(['id', 'item_name', 'facility_id'])
                ->map(fn($e) => ['id' => $e->id, 'name' => $e->item_name, 'facility_id' => $e->facility_id])->values()->all(),
            'staff'      => User::where('role', 'staff')->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name)])->values()->all(),
            'types'      => MaintenanceSchedule::TYPES,
            'prefill'    => [
                'equipment_id' => $request->query('equipment_id'),
                'facility_id'  => $request->query('facility_id'),
            ],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($error = $this->conflictError($request, $data, null)) {
            return back()->withErrors(['scheduled_date' => $error])->withInput();
        }

        $m = MaintenanceSchedule::create($data + ['status' => 'scheduled', 'created_by' => Auth::id()]);

        AuditLog::record('sa.maintenance.scheduled', $m, "Maintenance \"{$m->title}\" scheduled for {$m->scheduled_date->format('M j, Y')}");

        return redirect()->route('admin.sa.maintenance.index')->with('status', 'Maintenance scheduled.');
    }

    public function update(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        if ($schedule->status !== 'scheduled') {
            return back()->with('error', 'Only maintenance that has not started can be edited.');
        }

        $data = $this->validated($request);

        if ($error = $this->conflictError($request, $data, $schedule->id)) {
            return back()->withErrors(['scheduled_date' => $error])->withInput()->with('reopen_edit', $schedule->id);
        }

        $schedule->update($data);
        AuditLog::record('sa.maintenance.updated', $schedule, "Maintenance \"{$schedule->title}\" updated");

        return redirect()->route('admin.sa.maintenance.index')->with('status', 'Maintenance updated.');
    }

    public function start(MaintenanceSchedule $schedule): RedirectResponse
    {
        if ($schedule->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled maintenance can be started.');
        }

        DB::transaction(function () use ($schedule) {
            $schedule->update(['status' => 'in_progress', 'started_at' => now()]);

            if ($schedule->equipment) {
                $schedule->equipment->update(['condition' => 'under_maintenance']);
            }

            if ($schedule->maintenance_report_id) {
                DB::table('maintenance_reports')->where('id', $schedule->maintenance_report_id)->where('status', 'open')
                    ->update(['status' => 'in_progress', 'updated_at' => now()]);
            }

            AuditLog::record('sa.maintenance.started', $schedule, "Maintenance \"{$schedule->title}\" started");
        });

        return back()->with('status', 'Maintenance started.' . ($schedule->equipment ? ' The item is now marked under maintenance.' : ''));
    }

    public function complete(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        if (!in_array($schedule->status, MaintenanceSchedule::OPEN, true)) {
            return back()->with('error', 'This maintenance is already closed.');
        }

        $data = $request->validate([
            'outcome_condition' => [$schedule->equipment_inventory_id ? 'required' : 'nullable', 'in:good,damaged'],
            'completion_notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($schedule, $data) {
            $schedule->update([
                'status'            => 'completed',
                'started_at'        => $schedule->started_at ?? now(),
                'completed_at'      => now(),
                'outcome_condition' => $data['outcome_condition'] ?? null,
                'completion_notes'  => $data['completion_notes'] ?? null,
            ]);

            if ($schedule->equipment && !empty($data['outcome_condition'])) {
                $schedule->equipment->update(['condition' => $data['outcome_condition'], 'last_checked_at' => now()]);
            }

            if ($schedule->maintenance_report_id) {
                DB::table('maintenance_reports')->where('id', $schedule->maintenance_report_id)->where('status', '!=', 'resolved')
                    ->update(['status' => 'resolved', 'resolved_by' => Auth::id(), 'resolved_at' => now(), 'updated_at' => now()]);
            }

            AuditLog::record('sa.maintenance.completed', $schedule, "Maintenance \"{$schedule->title}\" completed");
        });

        return back()->with('status', 'Maintenance marked as completed.' . ($schedule->maintenance_report_id ? ' The linked issue report was resolved.' : ''));
    }

    public function cancel(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        if ($schedule->status !== 'scheduled') {
            return back()->with('error', 'Only maintenance that has not started can be cancelled. Complete it instead.');
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $schedule->update(['status' => 'cancelled', 'completion_notes' => $data['reason']]);
        AuditLog::record('sa.maintenance.cancelled', $schedule, "Maintenance \"{$schedule->title}\" cancelled — {$data['reason']}");

        return back()->with('status', 'Maintenance cancelled.');
    }

    // ------------------------------------------------------------------ staff-filed issue reports

    public function progressReport(MaintenanceReport $report): RedirectResponse
    {
        if ($report->status !== 'open') {
            return back()->with('error', 'Only open reports can be marked in progress.');
        }

        $report->forceFill(['status' => 'in_progress'])->save();
        AuditLog::record('sa.maintenance_report.in_progress', $report, "Maintenance report #{$report->id} marked in progress");

        return back()->with('status', 'Issue marked as in progress.');
    }

    public function resolveReport(MaintenanceReport $report): RedirectResponse
    {
        if ($report->status === 'resolved') {
            return back()->with('error', 'This issue is already resolved.');
        }

        $report->forceFill(['status' => 'resolved', 'resolved_by' => Auth::id(), 'resolved_at' => now()])->save();
        AuditLog::record('sa.maintenance_report.resolved', $report, "Maintenance report #{$report->id} resolved");

        return back()->with('status', 'Issue marked as resolved.');
    }

    // ------------------------------------------------------------------ helpers

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'facility_id'            => ['required', 'integer', 'exists:facilities,id'],
            'equipment_inventory_id' => ['nullable', 'integer', 'exists:equipment_inventory,id'],
            'maintenance_report_id'  => ['nullable', 'integer', 'exists:maintenance_reports,id'],
            'type'                   => ['required', Rule::in(array_keys(MaintenanceSchedule::TYPES))],
            'title'                  => ['required', 'string', 'max:150'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'scheduled_date'         => ['required', 'date', 'after_or_equal:today'],
            'start_time'             => ['required', 'date_format:H:i'],
            'end_time'               => ['required', 'date_format:H:i', 'after:start_time'],
            'assigned_to'            => ['nullable', Rule::exists('users', 'id')->where('role', 'staff')],
            'ignore_conflicts'       => ['nullable', 'boolean'],
        ]);

        if (!empty($data['equipment_inventory_id'])) {
            $item = EquipmentInventory::find($data['equipment_inventory_id']);
            if ($item && $item->facility_id !== null && (int) $item->facility_id !== (int) $data['facility_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'equipment_inventory_id' => 'That item belongs to a different facility.',
                ]);
            }
        }

        unset($data['ignore_conflicts']);

        return $data;
    }

    private function conflictError(Request $request, array $data, ?int $exceptId): ?string
    {
        if ($request->boolean('ignore_conflicts')) {
            return null;
        }

        $conflicts = $this->conflicts(
            (int) $data['facility_id'],
            $data['scheduled_date'],
            $data['start_time'] . ':00',
            $data['end_time'] . ':00',
            $exceptId
        );

        return $conflicts
            ? 'This overlaps: ' . implode('; ', $conflicts) . '. Tick "Schedule anyway" to keep it.'
            : null;
    }

    /** Booked events and other open maintenance in the same facility at the same time. @return string[] */
    private function conflicts(int $facilityId, string $date, string $start, string $end, ?int $exceptId = null): array
    {
        $events = Appointment::forFacilityOnDate($facilityId, $date)
            ->whereIn('status', ReservationController::ACTIVE_STATUSES)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get(['activity_title', 'start_time', 'end_time'])
            ->map(fn($a) => 'event "' . $a->activity_title . '" (' . Carbon::parse($a->start_time)->format('g:i A') . '–' . Carbon::parse($a->end_time)->format('g:i A') . ')');

        $others = MaintenanceSchedule::where('facility_id', $facilityId)
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', MaintenanceSchedule::OPEN)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get(['title'])
            ->map(fn($m) => 'maintenance "' . $m->title . '"');

        return $events->merge($others)->values()->all();
    }
}