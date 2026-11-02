@extends('layouts.admin')

@section('title', 'Ingress Fee & Tax')

@push('styles')
    @include('admin.ob._styles')
@endpush

@php $m = fn($v) => '₱' . number_format((float) $v, 2); @endphp

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif

<div class="note-banner">
    Oversight view only — it shows what was recorded on each payment and never recomputes it. The tax rule is still to be confirmed with SSC / the Treasurer's Office.
    A payment is flagged <strong>Check</strong> when its total doesn't equal base + ingress (before and after) + tax.
</div>

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Payments</div><div class="val">{{ number_format($totals->payments_count) }}</div><div class="hint">{{ $flagged }} flagged for checking</div></div>
    <div class="card ob-stat"><div class="lbl">Base amount</div><div class="val">{{ $m($totals->base) }}</div><div class="hint">Facility rate charges</div></div>
    <div class="card ob-stat"><div class="lbl">Ingress fees</div><div class="val">{{ $m($totals->ingress_before + $totals->ingress_after) }}</div><div class="hint">Before {{ $m($totals->ingress_before) }} · after {{ $m($totals->ingress_after) }}</div></div>
    <div class="card ob-stat"><div class="lbl">Tax</div><div class="val">{{ $m($totals->tax) }}</div><div class="hint">Total billed {{ $m($totals->total) }}</div></div>
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="card-head"><div class="card-title">By facility</div></div>
    @if($byFacility->count())
        <div class="table-scroll">
        <table class="ob-table" style="min-width:640px;">
            <thead><tr><th>Facility</th><th class="num">Payments</th><th class="num">Base</th><th class="num">Ingress</th><th class="num">Tax</th><th class="num">Total</th></tr></thead>
            <tbody>
                @foreach($byFacility as $f)
                    <tr>
                        <td><strong>{{ $f->facility }}</strong></td>
                        <td class="num">{{ $f->payments_count }}</td>
                        <td class="num">{{ $m($f->base) }}</td>
                        <td class="num">{{ $m($f->ingress) }}</td>
                        <td class="num">{{ $m($f->tax) }}</td>
                        <td class="num"><strong>{{ $m($f->total) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-mini">No payments in this range.</div>
    @endif
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Payments</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('payment', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('admin.ob.ingress.index') }}">
            <div class="f"><label>Recorded from</label><input class="f-input" type="date" name="from" value="{{ $filters['from'] }}"></div>
            <div class="f"><label>To</label><input class="f-input" type="date" name="to" value="{{ $filters['to'] }}"></div>
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $filters['facility_id'] === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Payment status</label>
                <select class="f-input" name="status">
                    <option value="">Any</option>
                    @foreach($statuses as $s)<option value="{{ $s }}" @selected($filters['status'] === $s)>{{ ucfirst(str_replace(['_', '-'], ' ', $s)) }}</option>@endforeach
                </select>
            </div>
            <label class="f-check"><input type="checkbox" name="flagged" value="1" @checked($filters['flagged'])> Only flagged</label>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.ob.ingress.index') }}">Reset</a>
        </form>
        <a class="btn-sm b-dark" href="{{ route('admin.ob.ingress.export', request()->query()) }}">Export CSV</a>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:980px;">
            <thead>
                <tr><th>Recorded</th><th>Event</th><th>Facility</th><th class="num">Base</th><th class="num">Ingress before</th><th class="num">Ingress after</th><th class="num">Tax</th><th class="num">Total</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['recorded'] }}</td>
                        <td><strong>{{ $r['activity'] }}</strong><br><span class="sub">{{ $r['client'] }} · {{ $r['event_date'] }}</span></td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                        <td class="num">{{ $m($r['base']) }}</td>
                        <td class="num">{{ $m($r['ingress_before']) }}</td>
                        <td class="num">{{ $m($r['ingress_after']) }}</td>
                        <td class="num">{{ $m($r['tax']) }}</td>
                        <td class="num"><strong>{{ $m($r['total']) }}</strong></td>
                        <td>{{ $r['status'] }}@if($r['mismatch'])<br><span class="pill p-warn">Check</span>@endif</td>
                        <td>@if($r['receipt_url'])<a class="btn-sm b-ghost" href="{{ $r['receipt_url'] }}" target="_blank" rel="noopener">Slip</a>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="pager">
            <div>Page {{ $page->currentPage() }} of {{ $page->lastPage() }}</div>
            <div style="display:flex;gap:8px;">
                @if($page->previousPageUrl())<a href="{{ $page->previousPageUrl() }}">Previous</a>@else<span class="off">Previous</span>@endif
                @if($page->nextPageUrl())<a href="{{ $page->nextPageUrl() }}">Next</a>@else<span class="off">Next</span>@endif
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">🧾</div>
            <div class="e-title">No payments found</div>
            <div class="e-sub">Payments appear once clients submit them. Try widening the date range.</div>
        </div>
    @endif
</div>
@endsection