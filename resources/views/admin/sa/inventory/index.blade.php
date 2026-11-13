@extends('layouts.admin')

@section('title', 'Inventory Overview')

@push('styles')
    {{-- shared panel styles live in the OB Admin partial; both panels reuse them --}}
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

<div class="ob-stats" style="grid-template-columns:repeat(5,1fr);">
    <div class="card ob-stat"><div class="lbl">Item types</div><div class="val">{{ number_format($stats['item_types']) }}</div><div class="hint">Distinct stored items</div></div>
    <div class="card ob-stat"><div class="lbl">Total units</div><div class="val">{{ number_format($stats['total_units']) }}</div><div class="hint">Sum of all quantities</div></div>
    <div class="card ob-stat"><div class="lbl">Damaged units</div><div class="val">{{ number_format($stats['damaged']) }}</div><div class="hint">Need repair or replacing</div></div>
    <div class="card ob-stat"><div class="lbl">Under maintenance</div><div class="val">{{ number_format($stats['maintenance']) }}</div><div class="hint">Units currently out of use</div></div>
    <div class="card ob-stat"><div class="lbl">Needs a check</div><div class="val">{{ number_format($stats['needs_check']) }}</div><div class="hint">Not checked in {{ $staleDays }}+ days</div></div>
</div>
<style>@media (max-width:1180px){.ob-stats{grid-template-columns:repeat(2,1fr) !important;}} @media (max-width:520px){.ob-stats{grid-template-columns:1fr !important;}}</style>

<div class="card" style="margin-bottom:18px;">
    <div class="card-head"><div class="card-title">Stored items by facility</div></div>
    @if($byFacility->count())
        <div class="table-scroll">
        <table class="ob-table" style="min-width:600px;">
            <thead><tr><th>Facility</th><th class="num">Item types</th><th class="num">Units</th><th class="num">Damaged</th><th class="num">Under maintenance</th></tr></thead>
            <tbody>
                @foreach($byFacility as $f)
                    <tr>
                        <td><strong>{{ $f->facility }}</strong></td>
                        <td class="num">{{ $f->items }}</td>
                        <td class="num"><strong>{{ number_format($f->units) }}</strong></td>
                        <td class="num">@if($f->damaged_units)<span class="pill p-bad">{{ $f->damaged_units }}</span>@else<span class="sub">0</span>@endif</td>
                        <td class="num">@if($f->maintenance_units)<span class="pill p-warn">{{ $f->maintenance_units }}</span>@else<span class="sub">0</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-mini">No inventory has been entered yet. Staff add items in Sprint 10's inventory entry.</div>
    @endif
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">All stored items</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('item', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('admin.sa.inventory.index') }}">
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    <option value="shared" @selected($filters['facility_id'] === 'shared')>Shared / general stock</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $filters['facility_id'] === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Condition</label>
                <select class="f-input" name="condition">
                    <option value="">Any</option>
                    @foreach($conditionLabels as $key => $label)<option value="{{ $key }}" @selected($filters['condition'] === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Item name"></div>
            <label class="f-check"><input type="checkbox" name="needs_check" value="1" @checked($filters['needs_check'])> Needs a check</label>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.sa.inventory.index') }}">Reset</a>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead><tr><th>Item</th><th>Facility</th><th class="num">Qty</th><th>Condition</th><th>Last checked</th><th>Next maintenance</th><th>Managed by</th><th></th></tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['item'] }}</strong></td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                        <td class="num"><strong>{{ number_format($r['quantity']) }}</strong>@if($r['unit']) <span class="sub">{{ $r['unit'] }}</span>@endif</td>
                        <td><span class="pill {{ ['good' => 'p-ok', 'damaged' => 'p-bad', 'under_maintenance' => 'p-warn'][$r['condition']] ?? 'p-gray' }}">{{ $r['condition_label'] }}</span></td>
                        <td>{{ $r['last_checked'] }}@if($r['needs_check'])<br><span class="pill p-warn" style="margin-top:3px;">Needs check</span>@endif</td>
                        <td>{{ $r['next_maintenance'] ?? '—' }}</td>
                        <td>{{ $r['managed_by'] }}</td>
                        <td><a class="btn-sm b-ghost" href="{{ $r['schedule_url'] }}">Schedule maintenance</a></td>
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
            <div class="e-icon">📦</div>
            <div class="e-title">No items match</div>
            <div class="e-sub">Items staff record in the inventory entry (Sprint 10) will appear here.</div>
        </div>
    @endif
</div>
@endsection 