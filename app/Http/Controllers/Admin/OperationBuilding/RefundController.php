<?php

namespace App\Http\Controllers\Admin\OperationBuilding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\ReservationController;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * OB Admin refund workflow: pending -> approved -> released, or pending -> rejected.
 * Payments are collected on-site, so "release" records the on-site reference (voucher / OR no.).
 */
class RefundController extends Controller
{
    private const STATUS_LABELS = [
        Refund::PENDING  => 'Pending',
        Refund::APPROVED => 'Approved',
        Refund::RELEASED => 'Released',
        Refund::REJECTED => 'Rejected',
    ];

    public function __construct(private RefundService $refunds)
    {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,released,rejected'],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $page = Refund::query()
            ->with(['appointment.client', 'appointment.facility', 'requestedBy', 'decidedBy', 'releasedBy'])
            ->when($status, fn($x) => $x->where('status', $status))
            ->when($q !== '', fn($x) => $x->whereHas('appointment', function ($a) use ($q) {
                $a->where('activity_title', 'like', "%{$q}%")
                  ->orWhereHas('client', fn($c) => $c->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"));
            }))
            ->orderByRaw("case status when 'pending' then 0 when 'approved' then 1 else 2 end")
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $name = fn($u) => $u ? (trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: '—') : null;

        $rows = $page->getCollection()->map(fn(Refund $r) => [
            'id'              => $r->id,
            'activity'        => $r->appointment->activity_title ?? '—',
            'client'          => $name($r->appointment->client ?? null) ?? '—',
            'facility'        => $r->appointment->facility->name ?? '—',
            'event_date'      => $r->appointment && $r->appointment->event_date ? $r->appointment->event_date->format('M j, Y') : '—',
            'reason_type'     => $r->reason_label,
            'reason'          => $r->reason,
            'payment_total'   => (float) $r->payment_total,
            'percent'         => (int) $r->refund_percent,
            'amount'          => (float) $r->refund_amount,
            'status'          => $r->status,
            'status_label'    => self::STATUS_LABELS[$r->status] ?? ucfirst($r->status),
            'requested_by'    => $name($r->requestedBy),
            'requested_at'    => $r->created_at ? $r->created_at->format('M j, Y g:i A') : null,
            'decided_by'      => $name($r->decidedBy),
            'decided_at'      => $r->decided_at ? $r->decided_at->format('M j, Y g:i A') : null,
            'decision_note'   => $r->decision_note,
            'release_ref'     => $r->release_reference,
            'released_by'     => $name($r->releasedBy),
            'released_at'     => $r->released_at ? $r->released_at->format('M j, Y g:i A') : null,
            'incident_url'    => $r->incident_report_id ? route('admin.ob.damaged-items.show', $r->incident_report_id) : null,
            'approve_url'     => route('admin.ob.refunds.approve', $r->id),
            'reject_url'      => route('admin.ob.refunds.reject', $r->id),
            'release_url'     => route('admin.ob.refunds.release', $r->id),
        ])->values()->all();

        $sum = fn(string $s) => (float) Refund::where('status', $s)->sum('refund_amount');
        $cnt = Refund::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $eligible = Appointment::query()
            ->whereHas('payment')
            ->whereDoesntHave('refunds', fn($q) => $q->whereIn('status', Refund::ACTIVE))
            ->with(['client', 'facility', 'payment'])
            ->orderByRaw("case status when 'cancelled' then 0 else 1 end")
            ->orderByDesc('event_date')
            ->limit(100)->get()
            ->map(fn(Appointment $a) => [
                'id'    => $a->id,
                'label' => "#{$a->id} · {$a->activity_title} · " . trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? ''))
                    . ' · ₱' . number_format((float) $a->payment->total_amount, 2) . ($a->status === 'cancelled' ? ' · cancelled' : ''),
            ])->values()->all();

        return view('admin.ob.refunds.index', [
            'rows'          => $rows,
            'page'          => $page,
            'counts'        => $cnt,
            'stats'         => [
                'pending_amount'  => $sum(Refund::PENDING),
                'approved_amount' => $sum(Refund::APPROVED),
                'released_amount' => $sum(Refund::RELEASED),
            ],
            'statusLabels'  => self::STATUS_LABELS,
            'activeStatus'  => $status,
            'search'        => $q,
            'eligible'      => $eligible,
            'reasons'       => Refund::REASONS,
            'standardPolicy' => ReservationController::CANCELLATION_REFUND_PERCENT,
            'prefill'       => [
                'appointment_id'     => $request->query('appointment_id'),
                'incident_report_id' => $request->query('incident_report_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appointment_id'     => ['required', 'integer', 'exists:appointments,id'],
            'incident_report_id' => ['nullable', 'integer', 'exists:incident_reports,id'],
            'reason_type'        => ['required', 'in:' . implode(',', array_keys(Refund::REASONS))],
            'reason'             => ['nullable', 'string', 'max:1000'],
            'refund_percent'     => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $appointment = Appointment::with('payment')->findOrFail($data['appointment_id']);

        try {
            $this->refunds->open(
                $appointment,
                $data['reason_type'],
                $data['reason'] ?? null,
                (int) $data['refund_percent'],
                Auth::user(),
                false,
                $data['incident_report_id'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.ob.refunds.index')->with('status', 'Refund request created and waiting for approval.');
    }

    public function approve(Refund $refund): RedirectResponse
    {
        if ($refund->status !== Refund::PENDING) {
            return back()->with('error', 'Only pending refunds can be approved.');
        }

        $refund->update(['status' => Refund::APPROVED, 'decided_by' => Auth::id(), 'decided_at' => now()]);
        AuditLog::record('ob.refund.approved', $refund, 'Refund #' . $refund->id . ' approved (₱' . number_format((float) $refund->refund_amount, 2) . ')');

        return back()->with('status', 'Refund approved. Release it once the client has been paid back on-site.');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        if ($refund->status !== Refund::PENDING) {
            return back()->with('error', 'Only pending refunds can be rejected.');
        }

        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $refund->update([
            'status'        => Refund::REJECTED,
            'decided_by'    => Auth::id(),
            'decided_at'    => now(),
            'decision_note' => $data['note'],
        ]);
        AuditLog::record('ob.refund.rejected', $refund, 'Refund #' . $refund->id . " rejected — {$data['note']}");

        return back()->with('status', 'Refund rejected.');
    }

    public function release(Request $request, Refund $refund): RedirectResponse
    {
        if ($refund->status !== Refund::APPROVED) {
            return back()->with('error', 'Only approved refunds can be released.');
        }

        $data = $request->validate([
            'release_reference' => ['required', 'string', 'max:100'],
            'note'              => ['nullable', 'string', 'max:1000'],
        ]);

        $refund->update([
            'status'            => Refund::RELEASED,
            'release_reference' => $data['release_reference'],
            'released_by'       => Auth::id(),
            'released_at'       => now(),
            'decision_note'     => $data['note'] ?? $refund->decision_note,
        ]);
        AuditLog::record('ob.refund.released', $refund, 'Refund #' . $refund->id . " released — ref {$data['release_reference']}");

        return back()->with('status', 'Refund marked as released.');
    }
}