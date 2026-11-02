<?php

namespace App\Http\Controllers\Admin\OperationBuilding;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DamageDecision;
use App\Models\EquipmentInventory;
use App\Models\IncidentReport;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * OB Admin damaged items — the authority-to-act side.
 *
 * Staff capture the raw incident (Sprint 10) into incident_reports. Here the OB Admin reviews it,
 * records one or more decisions (charge client / waive / repair / replace / no damage) and resolves it.
 * Decisions only RECORD what was decided: they don't change inventory conditions (that stays with the
 * Sports/Activity Admin) and don't collect money (payments happen on-site).
 */
class DamagedItemController extends Controller
{
    private const STATUS_LABELS = [
        'open'         => 'Open',
        'under_review' => 'Under review',
        'resolved'     => 'Resolved',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'status'  => ['nullable', 'in:open,under_review,resolved'],
            'refund'  => ['nullable', 'boolean'],
            'q'       => ['nullable', 'string', 'max:100'],
        ]);

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $page = IncidentReport::query()
            ->with(['facility', 'appointment', 'reportedBy'])
            ->withCount('damageDecisions')
            ->withSum('damageDecisions as decided_total', 'approved_amount')
            ->when($status, fn($x) => $x->where('status', $status))
            ->when($request->boolean('refund'), fn($x) => $x->where('refund_related', true))
            ->when($q !== '', fn($x) => $x->where(function ($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")->orWhere('damaged_items', 'like', "%{$q}%");
            }))
            ->orderByRaw("case status when 'open' then 0 when 'under_review' then 1 else 2 end")
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $rows = $page->getCollection()->map(fn(IncidentReport $i) => [
            'id'            => $i->id,
            'facility'      => $i->facility->name ?? '—',
            'event'         => $i->appointment->activity_title ?? null,
            'reported_by'   => trim(($i->reportedBy->first_name ?? '') . ' ' . ($i->reportedBy->last_name ?? '')) ?: '—',
            'reported_at'   => $i->created_at ? $i->created_at->format('M j, Y') : '—',
            'items'         => $i->damaged_items ?: $i->description,
            'estimated'     => $i->estimated_cost !== null ? (float) $i->estimated_cost : null,
            'decisions'     => (int) $i->damage_decisions_count,
            'decided_total' => (float) ($i->decided_total ?? 0),
            'refund'        => (bool) $i->refund_related,
            'status'        => $i->status,
            'status_label'  => self::STATUS_LABELS[$i->status] ?? ucfirst(str_replace('_', ' ', $i->status)),
            'url'           => route('admin.ob.damaged-items.show', $i->id),
        ])->values()->all();

        $counts = IncidentReport::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        return view('admin.ob.damaged-items.index', [
            'rows'          => $rows,
            'page'          => $page,
            'counts'        => $counts,
            'unresolvedCost' => (float) IncidentReport::where('status', '!=', 'resolved')->sum('estimated_cost'),
            'statusLabels'  => self::STATUS_LABELS,
            'activeStatus'  => $status,
            'refundOnly'    => $request->boolean('refund'),
            'search'        => $q,
        ]);
    }

    public function show(IncidentReport $incident): View
    {
        $incident->load([
            'facility', 'reportedBy', 'reviewedBy',
            'appointment.client', 'appointment.payment',
            'damageDecisions.equipment', 'damageDecisions.decidedBy',
            'refunds',
        ]);

        $equipment = EquipmentInventory::query()
            ->where(fn($q) => $q->where('facility_id', $incident->facility_id)->orWhereNull('facility_id'))
            ->orderBy('item_name')->get();

        $appointment  = $incident->appointment;
        $activeRefund = $appointment ? $appointment->refunds()->whereIn('status', Refund::ACTIVE)->latest('id')->first() : null;

        return view('admin.ob.damaged-items.show', [
            'incident'     => $incident,
            'equipment'    => $equipment,
            'decisions'    => $incident->damageDecisions,
            'decidedTotal' => (float) $incident->damageDecisions->sum('approved_amount'),
            'decisionTypes' => DamageDecision::DECISIONS,
            'statusLabel'  => self::STATUS_LABELS[$incident->status] ?? ucfirst($incident->status),
            'locked'       => $incident->status === 'resolved',
            'activeRefund' => $activeRefund,
            'canOpenRefund' => $appointment && $appointment->payment && !$activeRefund,
            'reasons'      => Refund::REASONS,
        ]);
    }

    public function startReview(IncidentReport $incident): RedirectResponse
    {
        if ($incident->status !== 'open') {
            return back()->with('error', 'Only open reports can be moved to review.');
        }

        $incident->update(['status' => 'under_review', 'reviewed_by' => Auth::id()]);
        AuditLog::record('ob.damage.review_started', $incident, "OB Admin started reviewing incident #{$incident->id}");

        return back()->with('status', 'Marked as under review.');
    }

    public function storeDecision(Request $request, IncidentReport $incident): RedirectResponse
    {
        if ($incident->status === 'resolved') {
            return back()->with('error', 'This report is resolved. Reopen it to change decisions.');
        }

        $data = $request->validate([
            'decision'               => ['required', 'in:' . implode(',', array_keys(DamageDecision::DECISIONS))],
            'equipment_inventory_id' => ['nullable', 'integer', 'exists:equipment_inventory,id'],
            'item_label'             => ['nullable', 'string', 'max:150'],
            'quantity_affected'      => ['nullable', 'integer', 'min:1', 'max:100000'],
            'approved_amount'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['decision'] === 'charge_client' && !isset($data['approved_amount'])) {
            return back()->withErrors(['approved_amount' => 'Enter the amount to charge the client.'])->withInput();
        }

        if ($data['decision'] !== 'no_damage' && empty($data['equipment_inventory_id']) && empty($data['item_label'])) {
            return back()->withErrors(['item_label' => 'Pick an inventory item or type the item name.'])->withInput();
        }

        DB::transaction(function () use ($incident, $data) {
            $decision = $incident->damageDecisions()->create($data + ['decided_by' => Auth::id()]);

            if ($incident->status === 'open') {
                $incident->update(['status' => 'under_review', 'reviewed_by' => Auth::id()]);
            }

            AuditLog::record(
                'ob.damage.decision_added',
                $incident,
                "Incident #{$incident->id}: {$decision->decision_label}"
                    . ($decision->approved_amount !== null ? ' — ₱' . number_format((float) $decision->approved_amount, 2) : '')
            );
        });

        return back()->with('status', 'Decision recorded.');
    }

    public function destroyDecision(IncidentReport $incident, DamageDecision $decision): RedirectResponse
    {
        abort_unless($decision->incident_report_id === $incident->id, 404);

        if ($incident->status === 'resolved') {
            return back()->with('error', 'This report is resolved. Reopen it to change decisions.');
        }

        $label = $decision->decision_label;
        $decision->delete();

        AuditLog::record('ob.damage.decision_removed', $incident, "Incident #{$incident->id}: removed decision \"{$label}\"");

        return back()->with('status', 'Decision removed.');
    }

    public function resolve(Request $request, IncidentReport $incident): RedirectResponse
    {
        if ($incident->status === 'resolved') {
            return back()->with('error', 'This report is already resolved.');
        }

        if (!$incident->damageDecisions()->exists()) {
            return back()->with('error', 'Record at least one decision (even "No damage found") before resolving.');
        }

        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:2000']]);

        $incident->update([
            'status'           => 'resolved',
            'resolution_notes' => $data['resolution_notes'],
            'reviewed_by'      => $incident->reviewed_by ?? Auth::id(),
            'reviewed_at'      => now(),
        ]);

        AuditLog::record('ob.damage.resolved', $incident, "Incident #{$incident->id} resolved");

        return back()->with('status', 'Report resolved.');
    }

    public function reopen(IncidentReport $incident): RedirectResponse
    {
        if ($incident->status !== 'resolved') {
            return back()->with('error', 'Only resolved reports can be reopened.');
        }

        $incident->update(['status' => 'under_review']);
        AuditLog::record('ob.damage.reopened', $incident, "Incident #{$incident->id} reopened");

        return back()->with('status', 'Report reopened for changes.');
    }
}