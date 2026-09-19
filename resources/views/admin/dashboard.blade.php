@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<style>
    .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:18px;}
    .grid-2{display:grid;grid-template-columns:2.1fr 1fr;gap:18px;margin-bottom:18px;}
    .grid-2b{display:grid;grid-template-columns:1.7fr 1fr;gap:18px;}
    .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .table-scroll table{min-width:560px;}

    @media (max-width: 1180px){
        .grid-3{grid-template-columns:repeat(2,1fr);}
    }
    @media (max-width: 980px){
        .grid-2{grid-template-columns:1fr;}
        .grid-2b{grid-template-columns:1fr;}
    }
    @media (max-width: 640px){
        .grid-3{grid-template-columns:1fr;}
    }

    .stat-value{font-size:26px;font-weight:800;letter-spacing:-.4px;margin:2px 0 6px;}
    .stat-label{font-size:12.5px;font-weight:700;color:var(--ink-600);}
    .stat-row{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;}
    .spark{display:flex;align-items:flex-end;gap:3px;height:40px;}
    .spark i{
        display:block;width:6px;border-radius:3px 3px 0 0;background:var(--green-100);
    }
    .spark i.hi{background:var(--green-500);}
    .spark i.peak{background:var(--orange-500);}
    .stat-foot{margin-top:10px;font-size:11.5px;color:var(--ink-400);}

    /* day tabs */
    .day-tabs{display:flex;gap:6px;flex-wrap:wrap;}
    .day-tab{
        font-size:12px;font-weight:700;color:var(--ink-600);
        padding:7px 12px;border-radius:9px;cursor:pointer;border:1px solid transparent;
    }
    .day-tab.active{background:var(--green-600);color:#fff;}
    .day-tab .count{
        display:inline-block;margin-left:5px;font-size:10px;
        background:rgba(255,255,255,.25);border-radius:999px;padding:0 5px;
    }
    .day-tab:not(.active) .count{background:var(--green-100);color:var(--green-700);}

    .sched-table{width:100%;border-collapse:collapse;margin-top:6px;}
    .sched-table th{
        text-align:left;font-size:11px;text-transform:none;color:var(--ink-400);
        font-weight:700;padding:8px 10px;border-bottom:1px solid var(--line);
    }
    .sched-table td{
        padding:12px 10px;font-size:13px;border-bottom:1px solid var(--line);
        vertical-align:middle;
    }
    .sched-table tr:last-child td{border-bottom:none;}
    .client-cell{display:flex;align-items:center;gap:9px;font-weight:700;}
    .client-avatar{width:28px;height:28px;border-radius:8px;background:var(--green-100);color:var(--green-700);
        display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;}
    .unit-pill{
        font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;
        background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);
    }
    .status-pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;}
    .status-pill.confirmed{background:var(--green-100);color:var(--green-700);}
    .status-pill.pending{background:var(--orange-100);color:var(--orange-500);}
    .status-pill.completed{background:#eef2f0;color:var(--ink-600);}

    /* week overview card */
    .overview-total{font-size:34px;font-weight:800;letter-spacing:-.5px;}
    .overview-sub{font-size:12px;color:var(--ink-400);font-weight:600;margin-top:2px;}
    .track-row{
        display:flex;align-items:center;gap:12px;margin-top:16px;
        padding-top:14px;border-top:1px dashed var(--line);
    }
    .track-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
    .track-icon.paid{background:var(--green-100);color:var(--green-700);}
    .track-icon.free{background:var(--orange-100);color:var(--orange-500);}
    .track-name{font-size:13px;font-weight:700;}
    .track-count{font-size:11.5px;color:var(--ink-400);}
    .track-pct{margin-left:auto;font-weight:800;font-size:13px;}

    /* available hours table */
    .hours-table{width:100%;border-collapse:collapse;}
    .hours-table th{
        text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;
        padding:8px 10px;border-bottom:1px solid var(--line);
    }
    .hours-table td{padding:12px 10px;font-size:13px;border-bottom:1px solid var(--line);}
    .hours-table tr:last-child td{border-bottom:none;}
    .unit-cell{display:flex;align-items:center;gap:10px;font-weight:700;}
    .unit-dot{width:9px;height:9px;border-radius:3px;}
    .bar-track{width:70px;height:6px;border-radius:99px;background:var(--green-050);overflow:hidden;}
    .bar-fill{height:100%;border-radius:99px;background:var(--green-500);}
    .bar-fill.warn{background:var(--orange-500);}

    /* room status panel */
    .room-item{margin-bottom:16px;}
    .room-item:last-child{margin-bottom:0;}
    .room-top{display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:6px;}
    .room-top .name{font-weight:700;}
    .room-top .pct{font-weight:800;}
    .room-track{height:9px;border-radius:99px;background:var(--green-050);overflow:hidden;}
    .room-fill{height:100%;border-radius:99px;}
</style>
@endpush

@section('content')

<div class="grid-3">
    {{-- Number of Clients per Barangay --}}
    <div class="card">
        <div class="stat-label">Clients per Barangay</div>
        <div class="stat-row">
            <div>
                <div class="stat-value">{{ $clientsPerBarangay['total'] ?? 0 }}</div>
                <span class="trend {{ ($clientsPerBarangay['trend'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    {{ ($clientsPerBarangay['trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($clientsPerBarangay['trend'] ?? 0) }}% than last month
                </span>
            </div>
            <div class="spark">
                @forelse(($clientsPerBarangay['series'] ?? []) as $v)
                    <i style="height:{{ max(4, $v) }}%" class="{{ $loop->last ? 'peak' : 'hi' }}"></i>
                @empty
                    @for($i=0;$i<8;$i++)<i style="height:6%"></i>@endfor
                @endforelse
            </div>
        </div>
        <div class="stat-foot">Top barangay: {{ $clientsPerBarangay['top_barangay'] ?? 'No data yet' }}</div>
    </div>

    {{-- Number of Appointments per Barangay --}}
    <div class="card">
        <div class="stat-label">Appointments per Barangay</div>
        <div class="stat-row">
            <div>
                <div class="stat-value">{{ $appointmentsPerBarangay['total'] ?? 0 }}</div>
                <span class="trend {{ ($appointmentsPerBarangay['trend'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    {{ ($appointmentsPerBarangay['trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($appointmentsPerBarangay['trend'] ?? 0) }}% than last month
                </span>
            </div>
            <div class="spark">
                @forelse(($appointmentsPerBarangay['series'] ?? []) as $v)
                    <i style="height:{{ max(4, $v) }}%" class="{{ $loop->last ? 'peak' : 'hi' }}"></i>
                @empty
                    @for($i=0;$i<8;$i++)<i style="height:6%"></i>@endfor
                @endforelse
            </div>
        </div>
        <div class="stat-foot">Top barangay: {{ $appointmentsPerBarangay['top_barangay'] ?? 'No data yet' }}</div>
    </div>

    {{-- Total Appointments in Current Week --}}
    <div class="card">
        <div class="stat-label">Total Appointments This Week</div>
        <div class="stat-row">
            <div>
                <div class="stat-value">{{ $appointmentsThisWeek['total'] ?? 0 }}</div>
                <span class="trend {{ ($appointmentsThisWeek['trend'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    {{ ($appointmentsThisWeek['trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($appointmentsThisWeek['trend'] ?? 0) }}% than last week
                </span>
            </div>
            <div class="spark">
                @forelse(($appointmentsThisWeek['series'] ?? []) as $v)
                    <i style="height:{{ max(4, $v) }}%" class="{{ $loop->last ? 'peak' : 'hi' }}"></i>
                @empty
                    @for($i=0;$i<7;$i++)<i style="height:6%"></i>@endfor
                @endforelse
            </div>
        </div>
        <div class="stat-foot">Mon – Sun, current week</div>
    </div>
</div>

<div class="grid-2">
    {{-- Appointment Schedule --}}
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Appointment Schedule</div>
            </div>
            <div class="dropdown-chip">This week ▾</div>
        </div>

        <div class="day-tabs">
            @forelse(($scheduleDays ?? []) as $day)
                <div class="day-tab {{ $day['is_selected'] ? 'active' : '' }}">
                    {{ $day['label'] }} <span class="count">{{ $day['count'] }}</span>
                </div>
            @empty
                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $i => $d)
                    <div class="day-tab {{ $i === 0 ? 'active' : '' }}">{{ $d }} <span class="count">0</span></div>
                @endforeach
            @endforelse
        </div>

        @if(!empty($scheduleAppointments))
            <div class="table-scroll">
            <table class="sched-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Activity</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheduleAppointments as $appt)
                        <tr>
                            <td class="muted">{{ $appt['time'] }}</td>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar">{{ $appt['initials'] }}</div>
                                    {{ $appt['client_name'] }}
                                </div>
                            </td>
                            <td>{{ $appt['activity'] }}</td>
                            <td><span class="unit-pill">{{ $appt['unit'] }}</span></td>
                            <td><span class="status-pill {{ $appt['status_class'] }}">{{ $appt['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="empty-state">
                <div class="e-icon">📅</div>
                <div class="e-title">No appointments scheduled</div>
                <div class="e-sub">Bookings for the selected day will appear here.</div>
            </div>
        @endif
    </div>

    {{-- This Week Overview (paid vs free-use) --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">This Week Overview</div>
        </div>
        <div class="overview-total">{{ $weekOverview['total'] ?? 0 }}</div>
        <div class="overview-sub">Total appointments requested this week</div>

        <div class="track-row">
            <div class="track-icon paid">₱</div>
            <div>
                <div class="track-name">Paid Appointments</div>
                <div class="track-count">{{ $weekOverview['paid'] ?? 0 }} bookings</div>
            </div>
            <div class="track-pct">{{ $weekOverview['paid_pct'] ?? 0 }}%</div>
        </div>
        <div class="track-row">
            <div class="track-icon free">✓</div>
            <div>
                <div class="track-name">Free-use Appointments</div>
                <div class="track-count">{{ $weekOverview['free_use'] ?? 0 }} bookings</div>
            </div>
            <div class="track-pct">{{ $weekOverview['free_use_pct'] ?? 0 }}%</div>
        </div>
    </div>
</div>

<div class="grid-2b">
    {{-- Available Hours Today --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">Available Hours Today</div>
            <div class="dropdown-chip">All units ▾</div>
        </div>

        @if(!empty($availableHours))
            <div class="table-scroll">
            <table class="hours-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Capacity</th>
                        <th>Booked</th>
                        <th>Utilization</th>
                        <th>Status</th>
                        <th>Available</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($availableHours as $unit)
                        <tr>
                            <td>
                                <div class="unit-cell">
                                    <span class="unit-dot" style="background: {{ $unit['color'] }}"></span>
                                    {{ $unit['name'] }}
                                </div>
                            </td>
                            <td class="muted">{{ $unit['capacity'] }} pax</td>
                            <td class="muted">{{ $unit['booked_hours'] }} hrs</td>
                            <td>
                                <div class="bar-track">
                                    <div class="bar-fill {{ $unit['utilization'] >= 80 ? 'warn' : '' }}" style="width:{{ $unit['utilization'] }}%"></div>
                                </div>
                            </td>
                            <td><span class="status-pill {{ $unit['status'] === 'Open' ? 'confirmed' : 'pending' }}">{{ $unit['status'] }}</span></td>
                            <td><strong>{{ $unit['available_hours'] }} hrs</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="empty-state">
                <div class="e-icon">🏟️</div>
                <div class="e-title">No facility data yet</div>
                <div class="e-sub">Function units will appear once configured.</div>
            </div>
        @endif
    </div>

    {{-- Facility Occupancy (today) --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">Facility Occupancy</div>
            <div class="dropdown-chip">Today ▾</div>
        </div>

        @if(!empty($roomStatus))
            @foreach($roomStatus as $room)
                <div class="room-item">
                    <div class="room-top">
                        <span class="name">{{ $room['name'] }}</span>
                        <span class="pct">{{ $room['percent'] }}%</span>
                    </div>
                    <div class="room-track">
                        <div class="room-fill" style="width:{{ $room['percent'] }}%; background:{{ $room['percent'] >= 80 ? 'var(--orange-500)' : 'var(--green-500)' }}"></div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <div class="e-icon">📍</div>
                <div class="e-title">No occupancy data</div>
                <div class="e-sub">Occupancy fills in as today's slots get booked.</div>
            </div>
        @endif
    </div>
</div>

@endsection