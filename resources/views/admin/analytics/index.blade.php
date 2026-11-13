@extends('layouts.admin')

@section('title', 'Facility Analytics')

@push('styles')
    @include('admin.ob._styles')
    <style>
        .chart{display:flex;align-items:flex-end;gap:3px;height:210px;padding-top:8px;overflow-x:auto;}
        .col{flex:1 0 14px;min-width:14px;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:4px;}
        .pair{flex:1;width:100%;display:flex;align-items:flex-end;justify-content:center;gap:2px;}
        .pair i{display:block;flex:1;max-width:16px;border-radius:4px 4px 0 0;min-height:0;}
        .b-req{background:var(--orange-500);} .b-evt{background:var(--green-500);}
        .col span{font-size:9.5px;font-weight:700;color:var(--ink-600);white-space:nowrap;height:12px;}
        .legend-inline{display:flex;gap:14px;font-size:12px;font-weight:700;color:var(--ink-600);margin-bottom:6px;flex-wrap:wrap;}
        .legend-inline i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;vertical-align:-1px;}

        .heat{display:grid;grid-template-columns:42px repeat(var(--cols), minmax(22px,1fr));gap:3px;font-size:10.5px;min-width:560px;}
        .heat .h{font-weight:800;color:var(--ink-600);text-align:center;padding:2px 0;}
        .heat .d{font-weight:800;color:var(--ink-600);display:flex;align-items:center;}
        .heat .c{height:26px;border-radius:5px;background:rgba(14,28,23,.06);position:relative;}
        .tabs-mini{display:flex;gap:6px;margin-bottom:10px;}
        .tabs-mini button{font-family:inherit;font-size:12px;font-weight:700;padding:6px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink-600);cursor:pointer;}
        .tabs-mini button.on{background:var(--ink-900);color:#fff;border-color:var(--ink-900);}
        .peak{display:flex;justify-content:space-between;gap:10px;font-size:13px;padding:6px 0;border-bottom:1px solid var(--line);}
        .peak:last-child{border-bottom:none;}
        .meter{height:8px;border-radius:99px;background:rgba(14,28,23,.08);overflow:hidden;min-width:80px;}
        .meter i{display:block;height:100%;background:var(--green-500);border-radius:99px;}
        .meter i.hi{background:var(--orange-500);}
        .sec{color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);font-size:13px;font-weight:800;margin:22px 0 10px;letter-spacing:.02em;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
        .csv{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.88);color:var(--green-700);}
        @media print{
            .sidebar,.sidebar-overlay,.hamburger-btn,.topbar-actions,.no-print,.csv{display:none !important;}
            body::before{display:none !important;}
            body{background:#fff !important;}
            .main{max-width:none;padding:0;}
            .page-title,.sec{color:#000 !important;text-shadow:none !important;}
            .card{background:#fff !important;backdrop-filter:none !important;-webkit-backdrop-filter:none !important;box-shadow:none !important;border:1px solid #ddd !important;break-inside:avoid;}
            #heatVisits{display:block !important;}
        }
    </style>
@endpush

@php
    $money = fn($v) => '₱' . number_format((float) $v, 2);
    $q = request()->query();
    $hrs = range($heatFrom, $heatTo);
    $fmtHour = fn($h) => (($h % 12) ?: 12) . ($h >= 12 ? ' PM' : ' AM');
    $step = max(1, (int) ceil(count($volume['labels']) / 12));
    $heatCell = function ($v, $max) { return $max > 0 && $v > 0 ? 'background:rgba(31,174,116,' . round(0.15 + 0.85 * ($v / $max), 2) . ')' : ''; };
    $gran = ['day' => 'per day', 'week' => 'per week', 'month' => 'per month'][$volume['granularity']];
@endphp

@section('content')

<div class="card" style="margin-bottom:6px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <form class="filter-form" method="GET" action="{{ route('admin.analytics.index') }}">
            <div class="f"><label>From</label><input class="f-input" type="date" name="from" value="{{ $from }}"></div>
            <div class="f"><label>To</label><input class="f-input" type="date" name="to" value="{{ $to }}"></div>
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $facilityId === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.analytics.index') }}">Last 30 days</a>
        </form>
        <button type="button" class="btn-sm b-dark no-print" onclick="window.print()">Print report</button>
    </div>
</div>

{{-- ============ Appointment volume ============ --}}
<div class="sec">Appointment volume <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'volume']) }}">CSV</a></div>
<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Requests received</div><div class="val">{{ number_format($summary['requests']) }}</div><div class="hint">Created in this period</div></div>
    <div class="card ob-stat"><div class="lbl">Events held / scheduled</div><div class="val">{{ number_format($summary['events']) }}</div><div class="hint">{{ $summary['finished'] }} finished</div></div>
    <div class="card ob-stat"><div class="lbl">Cancellations</div><div class="val">{{ $summary['cancelled'] }}</div><div class="hint">{{ $summary['cancel_rate'] !== null ? $summary['cancel_rate'] . '% of events booked' : 'No bookings in range' }}</div></div>
    <div class="card ob-stat"><div class="lbl">Payments on file</div><div class="val">{{ $money($summary['billed']) }}</div><div class="hint">{{ number_format($summary['attendees']) }} expected attendees · {{ $summary['hours'] }} h booked</div></div>
</div>

<div class="two-col" style="grid-template-columns:2.2fr 1fr;margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">Requests and events {{ $gran }}</div></div>
        <div class="legend-inline"><span><i class="b-req"></i>Requests created</span><span><i class="b-evt"></i>Events held</span></div>
        <div class="chart">
            @foreach($volume['labels'] as $i => $label)
                <div class="col" title="{{ $label }}: {{ $volume['requests'][$i] }} requests, {{ $volume['events'][$i] }} events">
                    <div class="pair">
                        <i class="b-req" style="height:{{ $volume['requests'][$i] ? max(3, round($volume['requests'][$i] / $volume['max'] * 100)) : 0 }}%"></i>
                        <i class="b-evt" style="height:{{ $volume['events'][$i] ? max(3, round($volume['events'][$i] / $volume['max'] * 100)) : 0 }}%"></i>
                    </div>
                    <span>{{ $i % $step === 0 ? $label : '' }}</span>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Breakdown</div></div>
        <div class="section-h" style="margin-top:0;">By status (event date in range)</div>
        @forelse($volume['statuses'] as $status => $count)
            <div class="d-row"><span class="d-label">{{ ucfirst(str_replace('_', ' ', $status)) }}</span><span class="d-value">{{ $count }}</span></div>
        @empty
            <div class="empty-mini">No appointments in range.</div>
        @endforelse
        <div class="section-h">Events by track</div>
        <div class="d-row"><span class="d-label">Paid</span><span class="d-value">{{ $volume['tracks']['paid'] ?? 0 }}</span></div>
        <div class="d-row"><span class="d-label">Free use</span><span class="d-value">{{ $volume['tracks']['free_use'] ?? 0 }}</span></div>
    </div>
</div>

{{-- ============ Facility usage ============ --}}
<div class="sec">Facility usage <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'facilities']) }}">CSV</a></div>
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <div class="card-title">Usage by facility</div>
        <span class="dropdown-chip" title="Booked hours ÷ operating hours ({{ $openHours['open'] }}:00–{{ $openHours['close'] }}:00) × days in range">Utilization assumes {{ $openHours['open'] }}:00–{{ $openHours['close'] }}:00 daily</span>
    </div>
    <div class="table-scroll">
    <table class="ob-table" style="min-width:760px;">
        <thead><tr><th>Facility</th><th class="num">Events</th><th class="num">Hours booked</th><th>Utilization</th><th class="num">Attendees</th><th class="num">Cancelled</th><th class="num">Payments</th></tr></thead>
        <tbody>
            @forelse($byFacility as $f)
                <tr>
                    <td><strong>{{ $f['name'] }}</strong><br><span class="sub">Capacity {{ number_format($f['capacity']) }}</span></td>
                    <td class="num">{{ $f['events'] }}</td>
                    <td class="num">{{ $f['hours'] }}</td>
                    <td><div style="display:flex;align-items:center;gap:8px;"><div class="meter" style="flex:1;"><i class="{{ $f['utilization'] >= 80 ? 'hi' : '' }}" style="width:{{ $f['utilization'] }}%"></i></div><strong>{{ $f['utilization'] }}%</strong></div></td>
                    <td class="num">{{ number_format($f['attendees']) }}</td>
                    <td class="num">{{ $f['cancelled'] }}</td>
                    <td class="num">{{ $money($f['billed']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="sub">No facilities found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- ============ Peak hours ============ --}}
<div class="sec">Usage &amp; peak hours
    <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'peak-bookings']) }}">CSV · bookings</a>
    <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'peak-visits']) }}">CSV · visits</a>
</div>
<div class="two-col" style="grid-template-columns:2.2fr 1fr;margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">Busiest days and hours</div></div>
        <div class="tabs-mini no-print">
            <button type="button" class="on" id="tabBooked" onclick="showHeat('booked')">Booked hours</button>
            <button type="button" id="tabVisits" onclick="showHeat('visits')">Visitors signed in</button>
        </div>

        @foreach(['booked' => [$booked, 'hours booked', 'heatBooked'], 'visits' => [$visits, 'people', 'heatVisits']] as $key => [$h, $unit, $id])
            <div id="{{ $id }}" class="table-scroll" style="{{ $key === 'visits' ? 'display:none;' : '' }}">
                @if($h['max'] > 0)
                    <div class="heat" style="--cols:{{ count($hrs) }}">
                        <div></div>
                        @foreach($hrs as $hr)<div class="h">{{ ($hr % 12) ?: 12 }}{{ $hr >= 12 ? 'p' : 'a' }}</div>@endforeach
                        @foreach($h['days'] as $d => $dayName)
                            <div class="d">{{ $dayName }}</div>
                            @foreach($hrs as $hr)
                                @php $v = $h['matrix'][$d][$hr] ?? 0; @endphp
                                <div class="c" style="{{ $heatCell($v, $h['max']) }}" title="{{ $dayName }} {{ $fmtHour($hr) }}: {{ $v }} {{ $unit }}"></div>
                            @endforeach
                        @endforeach
                    </div>
                    <div class="sub" style="margin-top:8px;">Darker = busier. Hover a cell for the exact figure.</div>
                @else
                    <div class="empty-state" style="padding:24px 10px;">
                        <div class="e-icon">{{ $key === 'booked' ? '📅' : '🚪' }}</div>
                        <div class="e-title">{{ $key === 'booked' ? 'No bookings in this range' : 'No visitor sign-ins in this range' }}</div>
                        @if($key === 'visits')<div class="e-sub">Staff record visitors on the Visitor Log page; this fills in as they do.</div>@endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-head"><div class="card-title">Peak slots</div></div>
        <div class="section-h" style="margin-top:0;">By bookings</div>
        @forelse($booked['peaks'] as $p)
            <div class="peak"><span>{{ $p['day'] }} · {{ $fmtHour($p['hour']) }}</span><strong>{{ $p['value'] }} h</strong></div>
        @empty
            <div class="empty-mini">No data.</div>
        @endforelse
        <div class="section-h">By visitors</div>
        @forelse($visits['peaks'] as $p)
            <div class="peak"><span>{{ $p['day'] }} · {{ $fmtHour($p['hour']) }}</span><strong>{{ $p['value'] }} people</strong></div>
        @empty
            <div class="empty-mini">No visitor data yet.</div>
        @endforelse
        @if($visits['stats']['entries'])
            <div class="sub" style="margin-top:10px;">{{ number_format($visits['stats']['entries']) }} sign-ins · {{ number_format($visits['stats']['people']) }} people
                @if($visits['stats']['avg_stay'] !== null) · average stay {{ $visits['stats']['avg_stay'] }} min @endif</div>
        @endif
    </div>
</div>

{{-- ============ Staffing ============ --}}
<div class="sec">Staffing <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'staffing']) }}">CSV</a></div>
<div class="two-col" style="margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">Workload by staff</div>
            <span class="dropdown-chip">{{ $staffing['avg_per_event'] !== null ? $staffing['avg_per_event'] . ' staff per event on average' : 'No events' }}</span></div>
        @if(count($staffing['rows']))
            @php $maxH = max(1, collect($staffing['rows'])->max('hours')); @endphp
            @foreach($staffing['rows'] as $r)
                <div class="d-row" style="align-items:center;">
                    <span class="d-value" style="text-align:left;flex:0 0 38%;">{{ $r['name'] }}<br><span class="sub">{{ $r['events'] }} {{ \Illuminate\Support\Str::plural('event', $r['events']) }}</span></span>
                    <div class="meter" style="flex:1;"><i style="width:{{ round($r['hours'] / $maxH * 100) }}%"></i></div>
                    <span class="d-value" style="flex:0 0 64px;">{{ $r['hours'] }} h</span>
                </div>
            @endforeach
        @else
            <div class="empty-mini">No staff were assigned to events in this range.</div>
        @endif
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Events without staff</div><span class="pill {{ $staffing['unstaffed_count'] ? 'p-warn' : 'p-ok' }}">{{ $staffing['unstaffed_count'] }}</span></div>
        @forelse($staffing['unstaffed'] as $u)
            <div class="d-row"><span class="d-value" style="text-align:left;">{{ $u['title'] }}<br><span class="sub">{{ $u['facility'] }} · {{ $u['date'] }}</span></span><span class="pill p-gray">{{ $u['status'] }}</span></div>
        @empty
            <div class="empty-mini">Every event in this range has staff assigned.</div>
        @endforelse
    </div>
</div>

{{-- ============ Maintenance history ============ --}}
<div class="sec">Maintenance history <a class="csv" href="{{ route('admin.analytics.export', $q + ['section' => 'maintenance']) }}">CSV</a></div>
<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Maintenance jobs</div><div class="val">{{ $maintenance['jobs']['total'] }}</div><div class="hint">{{ $maintenance['jobs']['completed'] }} completed · {{ $maintenance['jobs']['open'] }} open</div></div>
    <div class="card ob-stat"><div class="lbl">Issues reported by staff</div><div class="val">{{ $maintenance['issues']['total'] }}</div><div class="hint">{{ $maintenance['issues']['resolved'] }} resolved · {{ $maintenance['issues']['high'] }} high severity</div></div>
    <div class="card ob-stat"><div class="lbl">Incident reports</div><div class="val">{{ $maintenance['incidents']['total'] }}</div><div class="hint">{{ $maintenance['incidents']['refund_related'] }} refund related</div></div>
    <div class="card ob-stat"><div class="lbl">Damage decided</div><div class="val">{{ $money($maintenance['incidents']['decided']) }}</div><div class="hint">Staff estimate {{ $money($maintenance['incidents']['estimated']) }}</div></div>
</div>

<div class="two-col" style="grid-template-columns:1.6fr 1fr;margin-bottom:18px;">
    <div class="card">
        <div class="card-head"><div class="card-title">By facility</div></div>
        @if(count($maintenance['by_facility']))
            <div class="table-scroll">
            <table class="ob-table" style="min-width:560px;">
                <thead><tr><th>Facility</th><th class="num">Jobs</th><th class="num">Done</th><th class="num">Issues</th><th class="num">Open</th><th class="num">Incidents</th><th class="num">Damage decided</th></tr></thead>
                <tbody>
                    @foreach($maintenance['by_facility'] as $r)
                        <tr><td><strong>{{ $r['facility'] }}</strong></td><td class="num">{{ $r['jobs'] }}</td><td class="num">{{ $r['completed'] }}</td><td class="num">{{ $r['issues'] }}</td>
                            <td class="num">@if($r['open_issues'])<span class="pill p-warn">{{ $r['open_issues'] }}</span>@else 0 @endif</td>
                            <td class="num">{{ $r['incidents'] }}</td><td class="num">{{ $money($r['damage']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="empty-mini">No maintenance, issues or incidents in this range.</div>
        @endif
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Recently completed</div></div>
        @forelse($maintenance['recent'] as $r)
            <div class="d-row"><span class="d-value" style="text-align:left;">{{ $r['title'] }}<br><span class="sub">{{ $r['facility'] }} · {{ $r['type'] }}</span></span><span class="sub">{{ $r['when'] }}</span></div>
        @empty
            <div class="empty-mini">No completed maintenance in this range.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showHeat(which){
        document.getElementById('heatBooked').style.display = which === 'booked' ? '' : 'none';
        document.getElementById('heatVisits').style.display = which === 'visits' ? '' : 'none';
        document.getElementById('tabBooked').classList.toggle('on', which === 'booked');
        document.getElementById('tabVisits').classList.toggle('on', which === 'visits');
    }
</script>
@endpush