@extends('layouts.staff')

@section('title', 'My Assignments')

@push('styles')
<style>
    .view-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;}
    .view-tab{font-size:13px;font-weight:700;padding:8px 14px;border-radius:10px;color:var(--ink-600);background:#fff;border:1px solid var(--line);}
    .view-tab.active{background:var(--green-600);color:#fff;border-color:var(--green-600);}
    .view-tab .n{margin-left:6px;font-size:11px;opacity:.8;}

    .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .as-table{width:100%;border-collapse:collapse;min-width:720px;}
    .as-table th{text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;padding:9px 10px;border-bottom:1px solid var(--line);white-space:nowrap;}
    .as-table td{padding:13px 10px;font-size:13px;border-bottom:1px solid var(--line);vertical-align:middle;}
    .as-table tr:last-child td{border-bottom:none;}
    .sub{font-size:11.5px;color:var(--ink-400);font-weight:600;}
    .unit-pill{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);}
    .today-tag{font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--orange-100);color:#a85b1f;margin-left:6px;}
    .team-count{font-size:12.5px;font-weight:700;}
    .view-btn{font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;background:#fff;color:var(--green-700);border:1px solid var(--green-100);}
    .view-btn:hover{background:var(--green-050);}

    .pager{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:12.5px;color:var(--ink-400);font-weight:600;}
    .pager a{font-weight:700;color:var(--green-700);padding:6px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;}
    .pager span.off{padding:6px 12px;border:1px solid var(--line);border-radius:9px;opacity:.45;}

    .ap-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .ap-overlay.show{display:flex;}
    .ap-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:480px;max-height:88vh;overflow-y:auto;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .ap-title{font-size:16px;font-weight:800;margin-bottom:4px;}
    .ap-section{font-size:12px;font-weight:800;color:var(--green-700);margin:18px 0 4px;}
    .d-row{display:flex;justify-content:space-between;gap:16px;font-size:13px;padding:7px 0;border-bottom:1px solid var(--line);}
    .d-row:last-child{border-bottom:none;}
    .d-label{color:var(--ink-400);font-weight:600;flex-shrink:0;}
    .d-value{font-weight:700;text-align:right;word-break:break-word;}
    .team-row{display:flex;justify-content:space-between;gap:12px;font-size:13px;padding:8px 0;border-bottom:1px solid var(--line);}
    .team-row:last-child{border-bottom:none;}
    .team-row .nm{font-weight:700;}
    .team-row .you{font-size:10.5px;font-weight:700;background:var(--green-100);color:var(--green-700);border-radius:999px;padding:2px 8px;margin-left:6px;}
    .team-row .ph{color:var(--ink-400);font-weight:600;}
    .ap-actions{margin-top:18px;}
    .ap-actions button{width:100%;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);background:#fff;color:var(--ink-900);}
</style>
@endpush

@section('content')

<div class="view-tabs">
    <a class="view-tab {{ $view === 'upcoming' ? 'active' : '' }}" href="{{ route('staff.assignments.index') }}">Upcoming <span class="n">{{ $counts['upcoming'] }}</span></a>
    <a class="view-tab {{ $view === 'past' ? 'active' : '' }}" href="{{ route('staff.assignments.index', ['view' => 'past']) }}">Past <span class="n">{{ $counts['past'] }}</span></a>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $view === 'past' ? 'Past assignments' : 'Upcoming assignments' }}</div>
        <div class="dropdown-chip">{{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}</div>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="as-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Facility</th>
                    <th>Date & Time</th>
                    <th>Client</th>
                    <th>Team</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td><strong>{{ $row['activity'] }}</strong>@if($row['is_today'])<span class="today-tag">Today</span>@endif<br><span class="sub">{{ $row['type_label'] }}</span></td>
                        <td><span class="unit-pill">{{ $row['facility'] }}</span></td>
                        <td>{{ $row['date'] }}<br><span class="sub">{{ $row['time'] }}</span></td>
                        <td>{{ $row['client_name'] }}</td>
                        <td><span class="team-count">{{ count($row['team']) }} staff</span></td>
                        <td><button type="button" class="view-btn" onclick="openTeam({{ $row['id'] }})">View team</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="pager">
            <div>Page {{ $appointments->currentPage() }} of {{ $appointments->lastPage() }}</div>
            <div style="display:flex;gap:8px;">
                @if($appointments->previousPageUrl())<a href="{{ $appointments->previousPageUrl() }}">Previous</a>@else<span class="off">Previous</span>@endif
                @if($appointments->nextPageUrl())<a href="{{ $appointments->nextPageUrl() }}">Next</a>@else<span class="off">Next</span>@endif
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">🗓</div>
            <div class="e-title">{{ $view === 'past' ? 'No past assignments' : 'No upcoming assignments' }}</div>
            <div class="e-sub">Appointments the admin assigns you to will show up here.</div>
        </div>
    @endif
</div>

<div class="ap-overlay" id="teamModal">
    <div class="ap-box">
        <div class="ap-title" id="teamTitle">Assignment</div>
        <div id="teamBody"></div>
        <div class="ap-actions"><button type="button" onclick="closeTeam()">Close</button></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const modal = document.getElementById('teamModal');

    function esc(v){
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function openTeam(id){
        const r = ROWS.find(x => x.id === id);
        if (!r) return;

        const details = [
            ['Facility', r.facility],
            ['Date', r.date],
            ['Time', r.time],
            ['Type', r.type_label],
            ['Client', r.client_name],
            ['Expected attendees', r.expected_attendees],
            ['Status', r.status],
        ].map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value">${esc(v)}</span></div>`).join('');

        const team = r.team.map(t =>
            `<div class="team-row"><span class="nm">${esc(t.name)}${t.is_me ? '<span class="you">You</span>' : ''}</span><span class="ph">${esc(t.phone)}</span></div>`
        ).join('');

        document.getElementById('teamTitle').textContent = r.activity || 'Assignment';
        document.getElementById('teamBody').innerHTML =
            `<div class="ap-section">Details</div>${details}` +
            `<div class="ap-section">Assigned staff (${r.team.length})</div>${team || '<div class="sub">No one assigned.</div>'}`;
        modal.classList.add('show');
    }
    function closeTeam(){ modal.classList.remove('show'); }
    modal.addEventListener('click', e => { if (e.target === modal) closeTeam(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTeam(); });
</script>
@endpush