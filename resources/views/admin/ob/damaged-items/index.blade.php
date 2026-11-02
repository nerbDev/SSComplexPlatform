@extends('layouts.admin')

@section('title', 'Damaged Items')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Open</div><div class="val">{{ $counts['open'] ?? 0 }}</div><div class="hint">Waiting for your review</div></div>
    <div class="card ob-stat"><div class="lbl">Under review</div><div class="val">{{ $counts['under_review'] ?? 0 }}</div><div class="hint">Decisions in progress</div></div>
    <div class="card ob-stat"><div class="lbl">Resolved</div><div class="val">{{ $counts['resolved'] ?? 0 }}</div><div class="hint">Closed with a decision</div></div>
    <div class="card ob-stat"><div class="lbl">Unresolved estimate</div><div class="val">₱{{ number_format($unresolvedCost, 2) }}</div><div class="hint">Staff-estimated cost, not yet resolved</div></div>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Damage & incident reports</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('report', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <div class="chips">
            <a class="chip {{ !$activeStatus && !$refundOnly ? 'active' : '' }}" href="{{ route('admin.ob.damaged-items.index', array_filter(['q' => $search])) }}">All</a>
            @foreach($statusLabels as $key => $label)
                <a class="chip {{ $activeStatus === $key ? 'active' : '' }}" href="{{ route('admin.ob.damaged-items.index', array_filter(['status' => $key, 'q' => $search])) }}">{{ $label }}<span class="n">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
            <a class="chip {{ $refundOnly ? 'active' : '' }}" href="{{ route('admin.ob.damaged-items.index', array_filter(['refund' => 1, 'q' => $search])) }}">Refund related</a>
        </div>
        <form class="filter-form" method="GET" action="{{ route('admin.ob.damaged-items.index') }}">
            @if($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
            @if($refundOnly)<input type="hidden" name="refund" value="1">@endif
            <input class="f-input" type="search" name="q" value="{{ $search }}" placeholder="Search description or items">
            <button class="f-btn" type="submit">Search</button>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead>
                <tr><th>#</th><th>Facility / event</th><th>Reported by</th><th>Damaged items</th><th class="num">Staff estimate</th><th class="num">Decided</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['id'] }}</strong></td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span>@if($r['event'])<br><span class="sub">{{ $r['event'] }}</span>@endif</td>
                        <td>{{ $r['reported_by'] }}<br><span class="sub">{{ $r['reported_at'] }}</span></td>
                        <td><div class="trunc" title="{{ $r['items'] }}">{{ $r['items'] }}</div>@if($r['refund'])<span class="pill p-warn" style="margin-top:4px;">Refund related</span>@endif</td>
                        <td class="num">{{ $r['estimated'] !== null ? '₱' . number_format($r['estimated'], 2) : '—' }}</td>
                        <td class="num">{{ $r['decisions'] ? '₱' . number_format($r['decided_total'], 2) : '—' }}<br><span class="sub">{{ $r['decisions'] }} {{ \Illuminate\Support\Str::plural('decision', $r['decisions']) }}</span></td>
                        <td><span class="pill p-{{ $r['status'] }}">{{ $r['status_label'] }}</span></td>
                        <td><a class="btn-sm {{ $r['status'] === 'resolved' ? 'b-ghost' : 'b-primary' }}" href="{{ $r['url'] }}">{{ $r['status'] === 'resolved' ? 'View' : 'Review' }}</a></td>
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
            <div class="e-icon">🛠</div>
            <div class="e-title">No damage reports</div>
            <div class="e-sub">Reports staff file after an event (Sprint 10) will show up here for your decision.</div>
        </div>
    @endif
</div>
@endsection