@extends('layouts.admin')

@section('title', 'Event Reports')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Events in this report</div><div class="val">{{ number_format($summary['events']) }}</div><div class="hint">{{ ['finished' => 'Finished', 'scheduled' => 'Scheduled', 'all' => 'Scheduled + finished'][$filters['status']] ?? 'Finished' }}</div></div>
    <div class="card ob-stat"><div class="lbl">Expected attendees</div><div class="val">{{ number_format($summary['attendees']) }}</div><div class="hint">Sum of expected attendees</div></div>
    <div class="card ob-stat"><div class="lbl">Payments on file</div><div class="val">₱{{ number_format($summary['billed'], 2) }}</div><div class="hint">Total of recorded payments</div></div>
    <div class="card ob-stat"><div class="lbl">Incident reports</div><div class="val">{{ number_format($summary['incidents']) }}</div><div class="hint">Linked to these events</div></div>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Event reports</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('event', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('admin.ob.event-reports.index') }}">
            <div class="f"><label>Show</label>
                <select class="f-input" name="status">
                    <option value="finished" @selected($filters['status'] === 'finished')>Finished events</option>
                    <option value="scheduled" @selected($filters['status'] === 'scheduled')>Scheduled events</option>
                    <option value="all" @selected($filters['status'] === 'all')>Scheduled + finished</option>
                </select>
            </div>
            <div class="f"><label>From</label><input class="f-input" type="date" name="from" value="{{ $filters['from'] }}"></div>
            <div class="f"><label>To</label><input class="f-input" type="date" name="to" value="{{ $filters['to'] }}"></div>
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $filters['facility_id'] === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Client or activity"></div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.ob.event-reports.index') }}">Reset</a>
        </form>
        <a class="btn-sm b-dark" href="{{ route('admin.ob.event-reports.export', request()->query()) }}">Export CSV</a>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead>
                <tr>
                    <th>Date & time</th><th>Event</th><th>Facility</th><th>Type</th>
                    <th class="num">Attendees</th><th class="num">Staff</th><th class="num">Payment</th>
                    <th class="num">Incidents</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['date'] }}<br><span class="sub">{{ $r['time'] }}</span></td>
                        <td><strong>{{ $r['activity'] }}</strong><br><span class="sub">{{ $r['client'] }}</span></td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                        <td>{{ $r['type'] }}</td>
                        <td class="num">{{ $r['attendees'] ?? '—' }}</td>
                        <td class="num">{{ $r['staff_count'] }}</td>
                        <td class="num">@if($r['payment_total'] !== null)₱{{ number_format($r['payment_total'], 2) }}<br>@endif<span class="sub">{{ $r['payment_status'] }}</span></td>
                        <td class="num">@if($r['incidents'])<span class="pill p-warn">{{ $r['incidents'] }}</span>@else<span class="sub">0</span>@endif</td>
                        <td><span class="pill p-{{ $r['status'] }}">{{ $r['status_label'] }}</span></td>
                        <td><a class="btn-sm b-ghost" href="{{ $r['show_url'] }}">View report</a></td>
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
            <div class="e-icon">📊</div>
            <div class="e-title">No events match these filters</div>
            <div class="e-sub">Finished events appear here once the admin marks them finished. Try "Scheduled + finished".</div>
        </div>
    @endif
</div>
@endsection