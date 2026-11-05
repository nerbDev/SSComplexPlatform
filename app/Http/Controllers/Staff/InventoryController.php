<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EquipmentInventory;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff equipment inventory (stocks) entry — feeds the Sports/Activity Admin's inventory overview.
 * Staff can add items, correct quantity/condition, and mark an item as checked.
 * (No delete on purpose: set the quantity to 0 instead, so history in the reports isn't lost.)
 */
class InventoryController extends Controller
{
    private const CONDITIONS = [
        'good'              => 'Good',
        'damaged'           => 'Damaged',
        'under_maintenance' => 'Under maintenance',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'facility_id' => ['nullable', 'string', 'max:10'],
            'condition'   => ['nullable', 'in:good,damaged,under_maintenance'],
            'q'           => ['nullable', 'string', 'max:100'],
        ]);

        $facility  = $request->query('facility_id');
        $condition = $request->query('condition');
        $q         = trim((string) $request->query('q', ''));

        $page = EquipmentInventory::query()
            ->with('facility')
            ->when($facility === 'shared', fn($x) => $x->whereNull('facility_id'))
            ->when($facility && $facility !== 'shared', fn($x) => $x->where('facility_id', (int) $facility))
            ->when($condition, fn($x) => $x->where('condition', $condition))
            ->when($q !== '', fn($x) => $x->where('item_name', 'like', "%{$q}%"))
            ->orderBy('item_name')
            ->paginate(15)->withQueryString();

        $rows = $page->getCollection()->map(fn(EquipmentInventory $e) => [
            'id'           => $e->id,
            'item'         => $e->item_name,
            'facility_id'  => $e->facility_id,
            'facility'     => $e->facility->name ?? 'Shared / general stock',
            'quantity'     => (int) $e->quantity,
            'unit'         => $e->unit,
            'condition'    => $e->condition,
            'condition_label' => self::CONDITIONS[$e->condition] ?? ucfirst(str_replace('_', ' ', (string) $e->condition)),
            'last_checked' => $e->last_checked_at ? $e->last_checked_at->format('M j, Y') : 'Never',
            'update_url'   => route('staff.inventory.update', $e->id),
            'check_url'    => route('staff.inventory.check', $e->id),
        ])->values()->all();

        return view('staff.inventory.index', [
            'rows'       => $rows,
            'page'       => $page,
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'conditions' => self::CONDITIONS,
            'filters'    => ['facility_id' => $facility, 'condition' => $condition, 'q' => $q],
            'totals'     => [
                'items' => EquipmentInventory::count(),
                'units' => (int) EquipmentInventory::sum('quantity'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $item = EquipmentInventory::create($data + ['last_checked_at' => now()]);

        AuditLog::record('staff.inventory.added', $item, "Inventory item \"{$item->item_name}\" added (qty {$item->quantity})");

        return redirect()->route('staff.inventory.index')->with('status', "\"{$item->item_name}\" added to the inventory.");
    }

    public function update(Request $request, EquipmentInventory $item): RedirectResponse
    {
        $data = $this->validated($request, $item->id);

        $before = "qty {$item->quantity}, {$item->condition}";
        $item->update($data + ['last_checked_at' => now()]);

        AuditLog::record('staff.inventory.updated', $item, "Inventory item \"{$item->item_name}\" updated ({$before} → qty {$item->quantity}, {$item->condition})");

        return redirect()->route('staff.inventory.index')->with('status', "\"{$item->item_name}\" updated.");
    }

    public function check(EquipmentInventory $item): RedirectResponse
    {
        $item->update(['last_checked_at' => now()]);
        AuditLog::record('staff.inventory.checked', $item, "Inventory item \"{$item->item_name}\" checked");

        return back()->with('status', "\"{$item->item_name}\" marked as checked.");
    }

    private function validated(Request $request, ?int $ignoreId): array
    {
        $data = $request->validate([
            'item_name'   => ['required', 'string', 'max:255'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'quantity'    => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit'        => ['nullable', 'string', 'max:30'],
            'condition'   => ['required', Rule::in(array_keys(self::CONDITIONS))],
        ]);

        $duplicate = EquipmentInventory::query()
            ->whereRaw('LOWER(item_name) = ?', [mb_strtolower($data['item_name'])])
            ->where(fn($q) => empty($data['facility_id']) ? $q->whereNull('facility_id') : $q->where('facility_id', $data['facility_id']))
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'item_name' => 'That item already exists for this facility — edit the existing entry instead.',
            ]);
        }

        $data['facility_id'] = $data['facility_id'] ?? null;

        return $data;
    }
}