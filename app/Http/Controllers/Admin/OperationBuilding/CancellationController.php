<?php

namespace App\Http\Controllers\Admin\OperationBuilding;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\ReservationController;
use App\Models\Appointment;
use App\Models\Refund;
use App\Services\AppointmentCancellationService;
use App\Services\RefundService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * OB Admin cancellations:
 *  - "Cancelled" tab: history of every cancelled appointment (who, when, why, refund status),
 *    with an "Open refund" shortcut for exceptions.
 *  - "Active" tab: cancel an appointment on the client's behalf, optionally opening an
 *    (auto-approved) refund when SSC is at fault / force majeure.
 *
 * Standard policy is unchanged: a client who cancels on their own gets no refund.
 */
class CancellationController extends Controller
{
    public function __construct(
        private AppointmentCancellationService $cancellation,
        private RefundService $refunds
    ) {
    }

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'active' ? 'active' : 'cancelled';
        $q   = trim((string) $request->query('q', ''));

        $query = Appointment::query()
            ->with(['client', 'facility', 'payment', 'cancelledBy', 'refunds'])
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('activity_title', 'like', "%{$q}%")
                      ->orWhereHas('client', fn($c) => $c->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"));
                });
            });

        if ($tab === 'cancelled') {
            $query->where('status', 'cancelled')->orderByDesc('cancelled_at')->orderByDesc('updated_at');
        } else {
            $query->whereIn('status', ReservationController::ACTIVE_STATUSES)->orderBy('event_date')->orderBy('start_time');
        }

        $page = $query->paginate(12)->withQueryString();

        $rows = $page->getCollection()->map(function (Appointment $a) {
            $activeRefund = $a->refunds->whereIn('status', Refund::ACTIVE)->sortByDesc('id')->first();
            $total        = $a->payment ? (float) $a->payment->total_amount : null;

            return [
                'id'            => $a->id,
                'activity'      => $a->activity_title,
                'client'        => trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: '—',
                'facility'      => $a->facility->name ?? '—',
                'date'          => $a->event_date ? $a->event_date->format('M j, Y') : '—',
                'time'          => Carbon::parse($a->start_time)->format('g:i A') . ' – ' . Carbon::parse($a->end_time)->format('g:i A'),
                'status'        => $a->status,
                'status_label'  => ucfirst(str_replace('_', ' ', $a->status)),
                'payment_total' => $total,
                'has_payment'   => $total !== null && $total > 0,
                'cancelled_at'  => $a->cancelled_at ? $a->cancelled_at->format('M j, Y g:i A') : ($a->status === 'cancelled' && $a->updated_at ? $a->updated_at->format('M j, Y') : null),
                'cancelled_by'  => $a->cancelledBy ? trim(($a->cancelledBy->first_name ?? '') . ' ' . ($a->cancelledBy->last_name ?? '')) : null,
                'cancelled_role' => $a->cancelled_by_role,
                'reason'        => $a->cancellation_reason,
                'refund_status' => $activeRefund?->status,
                'refund_amount' => $activeRefund ? (float) $activeRefund->refund_amount : null,
                'can_open_refund' => $total !== null && $total > 0 && !$activeRefund,
                'cancel_url'    => route('admin.ob.cancellations.cancel', $a->id),
            ];
        })->values()->all();

        return view('admin.ob.cancellations.index', [
            'tab'            => $tab,
            'rows'           => $rows,
            'page'           => $page,
            'search'         => $q,
            'counts'         => [
                'cancelled' => Appointment::where('status', 'cancelled')->count(),
                'active'    => Appointment::whereIn('status', ReservationController::ACTIVE_STATUSES)->count(),
            ],
            'reasons'        => Refund::REASONS,
            'standardPolicy' => ReservationController::CANCELLATION_REFUND_PERCENT,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        if (!in_array($appointment->status, ReservationController::ACTIVE_STATUSES, true)) {
            return back()->with('error', 'This appointment can no longer be cancelled.');
        }

        $data = $request->validate([
            'reason'             => ['required', 'string', 'max:500'],
            'open_refund'        => ['nullable', 'boolean'],
            'refund_reason_type' => ['nullable', 'in:ssc_cancellation,force_majeure'],
            'refund_percent'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $openRefund = (bool) ($data['open_refund'] ?? false);

        try {
            DB::transaction(function () use ($appointment, $data, $openRefund) {
                $this->cancellation->cancel($appointment, $data['reason'], Auth::user(), 'admin');

                if ($openRefund) {
                    // The OB Admin is the approver, so a refund opened at cancellation time starts approved.
                    $this->refunds->open(
                        $appointment->fresh('payment'),
                        $data['refund_reason_type'] ?? 'ssc_cancellation',
                        $data['reason'],
                        (int) ($data['refund_percent'] ?? 100),
                        Auth::user(),
                        true
                    );
                }
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.ob.cancellations.index')
            ->with('status', $openRefund
                ? 'Appointment cancelled and a refund was approved. Release it from the Refunds page once paid back.'
                : 'Appointment cancelled. No refund was opened.');
    }
}