@extends('layouts.admin')

@section('title', 'Inventory & Maintenance Reports')

@push('styles')
    @include('admin.ob._styles')
    <style>
        .bar{height:8px;border-radius:99px;background:rgba(14,28,23,.08);overflow:hidden;min-width:90px;}
        .bar i{display:block;height:100%;border-radius:99px;background:var(--green-500);}
        @media print{
            .sidebar,.sidebar-overlay,.hamburger-btn,.topbar-actions,.no-print{display:none !important;}
            body::before{display:none !important;}
            body{background:#fff !important;}
            .main{max-width:none;padding:0;}
            .page-title{color:#000 !important;text-shadow:none !important;}
            .card{background:#fff !important;backdrop-filter:none !important;-webkit-backdrop-filter:none !important;box-shadow:none !important;border:1px solid #ddd !important;break-inside:avoid;}
        }
    </style>
@endpush

@php
    $m = fn($v) => '₱' . number_format((float) $v, 2);
    $maxType = collect($byType)->max('total') ?: 1;
@endphp

@section('content')

<div class="note-banner green no-print">
    Scoped to inventory and maintenance. Inventory figures are a snapshot of today; maintenance and damaged-item figures follow the date range below.
    Event, payment and refund reporting lives in the OB Admin's Event Reports.
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <form class="filter-form" method="GET" action="{{ route('admin.sa.reports.index') }}">
            <div class="f"><label>From</label><input class="f-input" type="date" name="from" value="{{ $from }}"></div>
            <div class="f"><label>To</label><input class="f-input" type="date" name="to" value="{{ $to }}"></div>
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $facilityId === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.sa.reports.index') }}">Reset</a>
        </form>
        <div style="display:flex;gap:8px;" class="no-print">
            <a class="btn-sm b-dark" href="{{ route('admin.sa.reports.export', request()->query()) }}">Export maintenance CSV</a>
            <button type="button" class="btn-sm b-ghost" onclick="window.print()">Print</button>
        </div>
    </div>
</div>

<div class="section-h" style="color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);font-size:13px;margin:6px 0 10px;">Inventory snapshot (today)</div>
<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Item types</div><div class="val">{{ number_format($inventory['item_types']) }}</div><div class="hint">{{ number_format($inventory['units']) }} units stored</div></div>
    <div class="card ob-stat"><div class="lbl">In good condition</div><div class="val">{{ number_format($inventory['good']) }}</div><div class="hint">units</div></div>
    <div class="card ob-stat"><div class="lbl">Damaged / in maintenance</div><div class="val">{{ number_format($inventory['damaged']) }} / {{ number_format($inventory['maintenance']) }}</div><div class="hint">units</div></div>
    <div class="card ob-stat"><div class="lbl">Not checked in 30+ days</div><div class="val">{{ number_format($inventory['stale']) }}</div><div class="hint">item types</div></div>
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="card-head"><div class="card-title">Oldest unchecked items</div></div>
    @if(count($staleItems))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:520px;">
            <thead><tr><th>Item</th><th>Facility</th><th class="num">Qty</th><th>Last checked</th></tr></thead>
            <tbody>
                @foreach($staleItems as $s)
                    <tr><td><strong>{{ $s['item'] }}</strong></td><td><span class="unit-pill">{{ $s['facility'] }}</span></td><td class="num">{{ number_format($s['qty']) }}</td><td>{{ $s['last'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-mini">Every item has been checked in the last 30 days.</div>
    @endif
</div>

<div class="section-h" style="color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);font-size:13px;margin:6px 0 10px;">Maintenance ({{ \Carbon\Carbon::parse($from)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }})</div>
<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Jobs scheduled</div><div class="val">{{ $maintenance['total'] }}</div><div class="hint">{{ $maintenance['open'] }} still open</div></div>
    <div class="card ob-stat"><div class="lbl">Completed</div><div class="val">{{ $maintenance['completed'] }}</div><div class="hint">{{ $maintenance['rate'] !== null ? $maintenance['rate'] . '% of closed jobs' : 'No closed jobs yet' }}</div></div>
    <div class="card ob-stat"><div class="lbl">Overdue</div><div class="val">{{ $maintenance['overdue'] }}</div><div class="hint">Scheduled date passed, not started</div></div>
    <div class="card ob-stat"><div class="lbl">Average delay</div><div class="val">{{ $maintenance['avg_delay'] !== null ? number_format((float) $maintenance['avg_delay'], 1) . ' d' : '—' }}</div><div class="hint">Completion vs scheduled date</div></div>
</div>

<div class="two-col" style="margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">By type</div></div>
        @forelse($byType as $t)
            <div class="d-row" style="align-items:center;">
                <span class="d-label" style="flex:0 0 150px;">{{ $t['label'] }}</span>
                <div class="bar" style="flex:1;"><i style="width:{{ round($t['total'] / $maxType * 100) }}%"></i></div>
                <span class="d-value" style="flex:0 0 80px;">{{ $t['completed'] }}/{{ $t['total'] }}</span>
            </div>
        @empty
            <div class="empty-mini">No maintenance in this range.</div>
        @endforelse
        <div class="sub" style="margin-top:6px;">Completed / scheduled</div>
    </div>

    <div class="card">
        <div class="card-head"><div class="card-title">By facility</div></div>
        @if($byFacility->count())
            <div class="table-scroll">
            <table class="ob-table" style="min-width:380px;">
                <thead><tr><th>Facility</th><th class="num">Jobs</th><th class="num">Done</th><th class="num">Open</th></tr></thead>
                <tbody>
                    @foreach($byFacility as $f)
                        <tr><td><strong>{{ $f->facility }}</strong></td><td class="num">{{ $f->total }}</td><td class="num">{{ (int) $f->completed }}</td><td class="num">{{ (int) $f->open_jobs }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="empty-mini">No maintenance in this range.</div>
        @endif
    </div>
</div>

<div class="section-h" style="color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);font-size:13px;margin:6px 0 10px;">Staff-reported issues (by date reported)</div>
<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Issues reported</div><div class="val">{{ $issues['total'] }}</div><div class="hint">{{ $issues['open'] }} still open or in progress</div></div>
    <div class="card ob-stat"><div class="lbl">Resolved</div><div class="val">{{ $issues['resolved'] }}</div><div class="hint">{{ $issues['total'] ? round($issues['resolved'] / $issues['total'] * 100) . '% of reported' : 'No issues in range' }}</div></div>
    <div class="card ob-stat"><div class="lbl">Avg. time to resolve</div><div class="val">{{ $issues['avg_hours'] !== null ? ($issues['avg_hours'] >= 48 ? number_format($issues['avg_hours'] / 24, 1) . ' d' : number_format((float) $issues['avg_hours'], 0) . ' h') : '—' }}</div><div class="hint">Reported to resolved</div></div>
    <div class="card ob-stat"><div class="lbl">High severity</div><div class="val">{{ (int) ($issues['by_severity']['high']->total ?? 0) }}</div><div class="hint">{{ (int) ($issues['by_severity']['high']->resolved ?? 0) }} resolved</div></div>
</div>

<div class="two-col" style="margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">By severity</div></div>
        @foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label)
            @php $row = $issues['by_severity'][$key] ?? null; @endphp
            <div class="d-row"><span class="d-label">{{ $label }}</span><span class="d-value">{{ (int) ($row->resolved ?? 0) }} resolved / {{ (int) ($row->total ?? 0) }} reported</span></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">High-severity, still unresolved</div></div>
        @forelse($issues['open_high'] as $h)
            <div class="d-row"><span class="d-value" style="text-align:left;">{{ $h['title'] }}<br><span class="sub">{{ $h['facility'] }} · since {{ $h['since'] }}</span></span><span class="pill p-bad">{{ $h['status'] }}</span></div>
        @empty
            <div class="empty-mini">No unresolved high-severity issues.</div>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-head"><div class="card-title">Most damaged items</div><span class="dropdown-chip">From OB Admin damage decisions</span></div>
    @if(count($damaged))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:520px;">
            <thead><tr><th>Item</th><th class="num">Decisions</th><th class="num">Units affected</th><th class="num">Amount decided</th></tr></thead>
            <tbody>
                @foreach($damaged as $d)
                    <tr><td><strong>{{ $d->item }}</strong></td><td class="num">{{ $d->decisions }}</td><td class="num">{{ number_format($d->qty) }}</td><td class="num">{{ $m($d->amount) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-mini">No damage decisions in this range. This list fills in as the OB Admin resolves incident reports.</div>
    @endif
</div>
@endsection