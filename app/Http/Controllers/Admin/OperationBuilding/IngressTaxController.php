<?php

namespace App\Http\Controllers\Admin\OperationBuilding;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Payment;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OB Admin ingress fee / tax oversight. READ-ONLY: it reports what was recorded on each payment
 * (base, ingress before/after, tax, total). It never recomputes tax — the exact rule is still
 * to be confirmed with SSC / the Treasurer's Office.
 *
 * A row is flagged "Check" when total != base + ingress before + ingress after + tax,
 * which is the cheapest way to spot a payment whose parts don't add up.
 */
class IngressTaxController extends Controller
{
    private const MISMATCH = 'ABS(payments.total_amount - (payments.base_amount + COALESCE(payments.ingress_before, 0) + COALESCE(payments.ingress_after, 0) + COALESCE(payments.tax_amount, 0))) > 0.009';

    public function index(Request $request): View
    {
        $request->validate([
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'facility_id' => ['nullable', 'integer'],
            'status'      => ['nullable', 'string', 'max:30'],
            'flagged'     => ['nullable', 'boolean'],
        ]);

        $page = $this->rowsQuery($request)
            ->orderByDesc('payments.created_at')
            ->paginate(15)
            ->withQueryString();

        $rows = $page->getCollection()->map(fn($p) => $this->row($p))->values()->all();

        $totals = $this->baseQuery($request)->selectRaw('
            COUNT(*) as payments_count,
            COALESCE(SUM(payments.base_amount), 0) as base,
            COALESCE(SUM(payments.ingress_before), 0) as ingress_before,
            COALESCE(SUM(payments.ingress_after), 0) as ingress_after,
            COALESCE(SUM(payments.tax_amount), 0) as tax,
            COALESCE(SUM(payments.total_amount), 0) as total
        ')->first();

        $flagged = (clone $this->baseQuery($request))->whereRaw(self::MISMATCH)->count();

        $byFacility = $this->baseQuery($request)
            ->join('facilities', 'facilities.id', '=', 'appointments.facility_id')
            ->groupBy('facilities.id', 'facilities.name')
            ->orderBy('facilities.name')
            ->selectRaw('
                facilities.name as facility,
                COUNT(*) as payments_count,
                COALESCE(SUM(payments.base_amount), 0) as base,
                COALESCE(SUM(COALESCE(payments.ingress_before, 0) + COALESCE(payments.ingress_after, 0)), 0) as ingress,
                COALESCE(SUM(payments.tax_amount), 0) as tax,
                COALESCE(SUM(payments.total_amount), 0) as total
            ')->get();

        return view('admin.ob.ingress.index', [
            'rows'       => $rows,
            'page'       => $page,
            'totals'     => $totals,
            'flagged'    => $flagged,
            'byFacility' => $byFacility,
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'statuses'   => Payment::query()->distinct()->orderBy('status')->pluck('status')->all(),
            'filters'    => [
                'from'        => $request->query('from'),
                'to'          => $request->query('to'),
                'facility_id' => $request->query('facility_id'),
                'status'      => $request->query('status'),
                'flagged'     => $request->boolean('flagged'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $payments = $this->rowsQuery($request)->orderByDesc('payments.created_at')->limit(5000)->get();

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Recorded on', 'Event date', 'Activity', 'Client', 'Facility', 'Base amount', 'Ingress before', 'Ingress after', 'Tax', 'Total', 'Payment status', 'GCash reference', 'Needs check']);

            foreach ($payments as $p) {
                $r = $this->row($p);
                fputcsv($out, [
                    $r['recorded'], $r['event_date'], $r['activity'], $r['client'], $r['facility'],
                    $r['base'], $r['ingress_before'], $r['ingress_after'], $r['tax'], $r['total'],
                    $r['status'], $r['gcash'], $r['mismatch'] ? 'yes' : '',
                ]);
            }
            fclose($out);
        }, 'ingress-tax-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** payments joined to appointments (filters applied, no select list). */
    private function baseQuery(Request $request)
    {
        return Payment::query()
            ->join('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->when($request->filled('from'), fn($q) => $q->whereDate('payments.created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn($q) => $q->whereDate('payments.created_at', '<=', $request->query('to')))
            ->when($request->filled('facility_id'), fn($q) => $q->where('appointments.facility_id', (int) $request->query('facility_id')))
            ->when($request->filled('status'), fn($q) => $q->where('payments.status', $request->query('status')))
            ->when($request->boolean('flagged'), fn($q) => $q->whereRaw(self::MISMATCH));
    }

    private function rowsQuery(Request $request)
    {
        return $this->baseQuery($request)
            ->join('facilities', 'facilities.id', '=', 'appointments.facility_id')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->select(
                'payments.*',
                'appointments.activity_title', 'appointments.event_date',
                'facilities.name as facility_name',
                'users.first_name', 'users.last_name'
            );
    }

    private function row($p): array
    {
        $base = (float) $p->base_amount;
        $ib   = (float) $p->ingress_before;
        $ia   = (float) $p->ingress_after;
        $tax  = (float) $p->tax_amount;
        $tot  = (float) $p->total_amount;

        return [
            'recorded'       => $p->created_at ? $p->created_at->format('M j, Y') : '—',
            'event_date'     => $p->event_date ? \Carbon\Carbon::parse($p->event_date)->format('M j, Y') : '—',
            'activity'       => $p->activity_title,
            'client'         => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: '—',
            'facility'       => $p->facility_name,
            'base'           => $base,
            'ingress_before' => $ib,
            'ingress_after'  => $ia,
            'tax'            => $tax,
            'total'          => $tot,
            'status'         => ucfirst(str_replace(['_', '-'], ' ', (string) $p->status)),
            'gcash'          => $p->gcash_reference,
            'mismatch'       => abs($tot - ($base + $ib + $ia + $tax)) > 0.009,
            'receipt_url'    => $p->receipt_path ? asset('storage/' . ltrim($p->receipt_path, '/')) : null,
        ];
    }
}