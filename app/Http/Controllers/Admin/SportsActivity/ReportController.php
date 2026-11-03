<?php

namespace App\Http\Controllers\Admin\SportsActivity;

use App\Http\Controllers\Controller;
use App\Models\EquipmentInventory;
use App\Models\Facility;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sports/Activity Admin reports — scoped to inventory and maintenance only
 * (the OB Admin's event reports are a separate module).
 *
 * The inventory section is a snapshot of right now; the maintenance and damaged-items
 * sections follow the date range.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $facilityId  = $request->filled('facility_id') ? (int) $request->query('facility_id') : null;
        $stale       = now()->subDays(30);

        // ---- inventory snapshot (not date-filtered)
        $invBase = fn() => EquipmentInventory::query()->when($facilityId, fn($q) => $q->where('facility_id', $facilityId));

        $byCondition = $invBase()->selectRaw('`condition` as cond, COUNT(*) as items, COALESCE(SUM(quantity), 0) as units')
            ->groupBy('condition')->get()->keyBy('cond');

        $inventory = [
            'item_types' => $invBase()->count(),
            'units'      => (int) $invBase()->sum('quantity'),
            'good'       => (int) ($byCondition['good']->units ?? 0),
            'damaged'    => (int) ($byCondition['damaged']->units ?? 0),
            'maintenance' => (int) ($byCondition['under_maintenance']->units ?? 0),
            'stale'      => $invBase()->where(fn($w) => $w->whereNull('last_checked_at')->orWhere('last_checked_at', '<', $stale))->count(),
        ];

        $staleItems = $invBase()->with('facility')
            ->where(fn($w) => $w->whereNull('last_checked_at')->orWhere('last_checked_at', '<', $stale))
            ->orderByRaw('last_checked_at is null desc')->orderBy('last_checked_at')
            ->limit(10)->get()
            ->map(fn($e) => [
                'item'     => $e->item_name,
                'facility' => $e->facility->name ?? 'Shared / general stock',
                'qty'      => (int) $e->quantity,
                'last'     => $e->last_checked_at ? $e->last_checked_at->format('M j, Y') : 'Never',
            ])->all();

        // ---- maintenance in range
        $mBase = fn(): Builder => $this->maintenanceQuery($from, $to, $facilityId);

        $statusCounts = $mBase()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
        $total        = array_sum($statusCounts);
        $closed       = ($statusCounts['completed'] ?? 0) + ($statusCounts['cancelled'] ?? 0);

        $maintenance = [
            'total'     => $total,
            'completed' => $statusCounts['completed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'open'      => ($statusCounts['scheduled'] ?? 0) + ($statusCounts['in_progress'] ?? 0),
            'overdue'   => $mBase()->where('status', 'scheduled')->whereDate('scheduled_date', '<', Carbon::today()->toDateString())->count(),
            'rate'      => $closed > 0 ? round((($statusCounts['completed'] ?? 0) / $closed) * 100) : null,
            'avg_delay' => $mBase()->where('status', 'completed')->whereNotNull('completed_at')
                ->selectRaw('AVG(DATEDIFF(DATE(completed_at), scheduled_date)) as d')->value('d'),
        ];

        $byType = $mBase()->selectRaw('type, COUNT(*) as total, SUM(status = "completed") as completed')
            ->groupBy('type')->orderByDesc('total')->get()
            ->map(fn($r) => ['label' => MaintenanceSchedule::TYPES[$r->type] ?? ucfirst((string) $r->type), 'total' => (int) $r->total, 'completed' => (int) $r->completed])->all();

        $byFacility = $mBase()->join('facilities', 'facilities.id', '=', 'maintenance_schedules.facility_id')
            ->selectRaw('facilities.name as facility, COUNT(*) as total, SUM(maintenance_schedules.status = "completed") as completed, SUM(maintenance_schedules.status IN ("scheduled","in_progress")) as open_jobs')
            ->groupBy('facilities.id', 'facilities.name')->orderBy('facilities.name')->get();

        // ---- staff-filed maintenance reports (issues), date-filtered by when they were reported
        $issueBase = fn() => DB::table('maintenance_reports')
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId));

        $issueStatus = $issueBase()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
        $issues = [
            'total'      => array_sum($issueStatus),
            'open'       => ($issueStatus['open'] ?? 0) + ($issueStatus['in_progress'] ?? 0),
            'resolved'   => $issueStatus['resolved'] ?? 0,
            'avg_hours'  => $issueBase()->where('status', 'resolved')->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as h')->value('h'),
            'by_severity' => $issueBase()->selectRaw('severity, COUNT(*) as total, SUM(status = "resolved") as resolved')
                ->groupBy('severity')->get()->keyBy('severity'),
            // Not date-filtered: anything high-severity that is still unresolved right now.
            'open_high'  => DB::table('maintenance_reports as mr')->join('facilities as f', 'f.id', '=', 'mr.facility_id')
                ->where('mr.severity', 'high')->where('mr.status', '!=', 'resolved')
                ->when($facilityId, fn($q) => $q->where('mr.facility_id', $facilityId))
                ->orderBy('mr.created_at')->limit(8)
                ->get(['mr.title', 'mr.status', 'mr.created_at', 'f.name as facility'])
                ->map(fn($r) => ['title' => $r->title, 'facility' => $r->facility, 'status' => ucfirst(str_replace('_', ' ', $r->status)),
                    'since' => Carbon::parse($r->created_at)->format('M j, Y')])->all(),
        ];

        // ---- most damaged items (from OB Admin damage decisions), date-filtered
        $damaged = [];
        if (Schema::hasTable('damage_decisions')) {
            $damaged = DB::table('damage_decisions')
                ->leftJoin('equipment_inventory', 'equipment_inventory.id', '=', 'damage_decisions.equipment_inventory_id')
                ->where('damage_decisions.decision', '!=', 'no_damage')
                ->whereDate('damage_decisions.created_at', '>=', $from->toDateString())
                ->whereDate('damage_decisions.created_at', '<=', $to->toDateString())
                ->when($facilityId, fn($q) => $q->where('equipment_inventory.facility_id', $facilityId))
                ->selectRaw("COALESCE(equipment_inventory.item_name, damage_decisions.item_label, 'Unspecified item') as item,
                             COUNT(*) as decisions,
                             COALESCE(SUM(damage_decisions.quantity_affected), 0) as qty,
                             COALESCE(SUM(damage_decisions.approved_amount), 0) as amount")
                ->groupBy(DB::raw("COALESCE(equipment_inventory.item_name, damage_decisions.item_label, 'Unspecified item')"))
                ->orderByDesc('decisions')->limit(10)->get()->all();
        }

        return view('admin.sa.reports.index', [
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'facilityId'  => $facilityId,
            'facilities'  => Facility::orderBy('name')->get(['id', 'name']),
            'inventory'   => $inventory,
            'staleItems'  => $staleItems,
            'maintenance' => $maintenance,
            'issues'      => $issues,
            'byType'      => $byType,
            'byFacility'  => $byFacility,
            'damaged'     => $damaged,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $facilityId  = $request->filled('facility_id') ? (int) $request->query('facility_id') : null;

        $rows = $this->maintenanceQuery($from, $to, $facilityId)
            ->with(['facility', 'equipment', 'assignedTo'])
            ->orderBy('scheduled_date')->orderBy('start_time')->limit(5000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Start', 'End', 'Facility', 'Item', 'Type', 'Title', 'Status', 'Assigned to', 'Outcome', 'Completed at', 'Notes']);

            foreach ($rows as $m) {
                fputcsv($out, [
                    $m->scheduled_date->format('Y-m-d'), substr($m->start_time, 0, 5), substr($m->end_time, 0, 5),
                    $m->facility->name ?? '', $m->equipment->item_name ?? '', $m->type_label, $m->title,
                    MaintenanceSchedule::STATUSES[$m->status] ?? $m->status,
                    $m->assignedTo ? trim($m->assignedTo->first_name . ' ' . $m->assignedTo->last_name) : '',
                    $m->outcome_condition, $m->completed_at ? $m->completed_at->format('Y-m-d H:i') : '', $m->completion_notes,
                ]);
            }
            fclose($out);
        }, 'maintenance-report-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function range(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'facility_id' => ['nullable', 'integer']]);

        $from = $request->filled('from') ? Carbon::parse($request->query('from')) : Carbon::now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->query('to')) : Carbon::now();

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }

    private function maintenanceQuery(Carbon $from, Carbon $to, ?int $facilityId): Builder
    {
        return MaintenanceSchedule::query()
            ->whereDate('scheduled_date', '>=', $from->toDateString())
            ->whereDate('scheduled_date', '<=', $to->toDateString())
            ->when($facilityId, fn($q) => $q->where('maintenance_schedules.facility_id', $facilityId));
    }
}