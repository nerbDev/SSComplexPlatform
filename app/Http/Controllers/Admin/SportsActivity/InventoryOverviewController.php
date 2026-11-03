<?php

namespace App\Http\Controllers\Admin\SportsActivity;

use App\Http\Controllers\Controller;
use App\Models\EquipmentInventory;
use App\Models\Facility;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sports/Activity Admin inventory overview — the admin-side view on top of the inventory
 * staff enter in Sprint 10. Read-only here: counts, conditions, last-checked dates, with a
 * shortcut to schedule maintenance for an item.
 */
class InventoryOverviewController extends Controller
{
    /** An item not checked for this many days (or never) counts as "needs a check". */
    private const STALE_DAYS = 30;

    private const CONDITION_LABELS = [
        'good'              => 'Good',
        'damaged'           => 'Damaged',
        'under_maintenance' => 'Under maintenance',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'facility_id' => ['nullable', 'string', 'max:10'],
            'condition'   => ['nullable', 'in:good,damaged,under_maintenance'],
            'needs_check' => ['nullable', 'boolean'],
            'q'           => ['nullable', 'string', 'max:100'],
        ]);

        $facilityFilter = $request->query('facility_id');
        $condition      = $request->query('condition');
        $q              = trim((string) $request->query('q', ''));
        $stale          = now()->subDays(self::STALE_DAYS);

        $page = EquipmentInventory::query()
            ->with(['facility', 'managedBy'])
            ->when($facilityFilter === 'shared', fn($x) => $x->whereNull('facility_id'))
            ->when($facilityFilter && $facilityFilter !== 'shared', fn($x) => $x->where('facility_id', (int) $facilityFilter))
            ->when($condition, fn($x) => $x->where('condition', $condition))
            ->when($request->boolean('needs_check'), fn($x) => $x->where(fn($w) => $w->whereNull('last_checked_at')->orWhere('last_checked_at', '<', $stale)))
            ->when($q !== '', fn($x) => $x->where('item_name', 'like', "%{$q}%"))
            ->orderByRaw("case `condition` when 'damaged' then 0 when 'under_maintenance' then 1 else 2 end")
            ->orderBy('item_name')
            ->paginate(15)
            ->withQueryString();

        // Next planned maintenance per item on this page.
        $next = MaintenanceSchedule::query()
            ->whereIn('equipment_inventory_id', $page->getCollection()->pluck('id'))
            ->whereIn('status', MaintenanceSchedule::OPEN)
            ->orderBy('scheduled_date')
            ->get(['equipment_inventory_id', 'scheduled_date'])
            ->unique('equipment_inventory_id')
            ->keyBy('equipment_inventory_id');

        $rows = $page->getCollection()->map(function (EquipmentInventory $e) use ($next, $stale) {
            $nextDate = $next[$e->id]->scheduled_date ?? null;

            return [
                'id'           => $e->id,
                'item'         => $e->item_name,
                'facility'     => $e->facility->name ?? 'Shared / general stock',
                'quantity'     => (int) $e->quantity,
                'unit'         => $e->unit,
                'condition'    => $e->condition,
                'condition_label' => self::CONDITION_LABELS[$e->condition] ?? ucfirst(str_replace('_', ' ', (string) $e->condition)),
                'managed_by'   => $e->managedBy ? trim($e->managedBy->first_name . ' ' . $e->managedBy->last_name) : '—',
                'last_checked' => $e->last_checked_at ? $e->last_checked_at->format('M j, Y') : 'Never',
                'needs_check'  => !$e->last_checked_at || $e->last_checked_at->lt($stale),
                'next_maintenance' => $nextDate ? $nextDate->format('M j, Y') : null,
                'schedule_url' => route('admin.sa.maintenance.index', array_filter([
                    'equipment_id' => $e->id,
                    'facility_id'  => $e->facility_id,
                ])),
            ];
        })->values()->all();

        $byCondition = EquipmentInventory::query()
            ->selectRaw('`condition` as cond, COUNT(*) as items, COALESCE(SUM(quantity), 0) as units')
            ->groupBy('condition')->get()->keyBy('cond');

        $byFacility = EquipmentInventory::query()
            ->leftJoin('facilities', 'facilities.id', '=', 'equipment_inventory.facility_id')
            ->groupBy('facilities.id', 'facilities.name')
            ->selectRaw("
                COALESCE(facilities.name, 'Shared / general stock') as facility,
                COUNT(*) as items,
                COALESCE(SUM(equipment_inventory.quantity), 0) as units,
                COALESCE(SUM(CASE WHEN equipment_inventory.`condition` = 'damaged' THEN equipment_inventory.quantity ELSE 0 END), 0) as damaged_units,
                COALESCE(SUM(CASE WHEN equipment_inventory.`condition` = 'under_maintenance' THEN equipment_inventory.quantity ELSE 0 END), 0) as maintenance_units
            ")
            ->orderBy('facility')->get();

        return view('admin.sa.inventory.index', [
            'rows'       => $rows,
            'page'       => $page,
            'stats'      => [
                'item_types'   => EquipmentInventory::count(),
                'total_units'  => (int) EquipmentInventory::sum('quantity'),
                'damaged'      => (int) ($byCondition['damaged']->units ?? 0),
                'maintenance'  => (int) ($byCondition['under_maintenance']->units ?? 0),
                'needs_check'  => EquipmentInventory::where(fn($w) => $w->whereNull('last_checked_at')->orWhere('last_checked_at', '<', $stale))->count(),
            ],
            'byFacility' => $byFacility,
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'conditionLabels' => self::CONDITION_LABELS,
            'staleDays'  => self::STALE_DAYS,
            'filters'    => [
                'facility_id' => $facilityFilter,
                'condition'   => $condition,
                'needs_check' => $request->boolean('needs_check'),
                'q'           => $q,
            ],
        ]);
    }
}