@extends('layouts.admin')

@section('title', $mode === 'scheduled' ? 'Scheduled Appointments' : 'Appointments')

@push('styles')
<style>
    .flash{background:var(--green-100);color:var(--green-800);border:1px solid #cdeedd;border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;}

    .view-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;}
    .view-tab{font-size:13px;font-weight:700;padding:8px 14px;border-radius:10px;color:var(--ink-600);background:#fff;border:1px solid var(--line);}
    .view-tab.active{background:var(--green-600);color:#fff;border-color:var(--green-600);}
    .view-tab .n{margin-left:6px;font-size:11px;opacity:.8;}

    .filter-bar{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:14px;}
    .chips{display:flex;gap:6px;flex-wrap:wrap;}
    .chip{font-size:12px;font-weight:700;padding:6px 11px;border-radius:999px;background:var(--green-050);border:1px solid var(--line);color:var(--ink-600);}
    .chip.active{background:var(--ink-900);color:#fff;border-color:var(--ink-900);}
    .chip .n{margin-left:5px;color:var(--ink-400);font-weight:600;}
    .chip.active .n{color:#cfd9d4;}
    .search-form{display:flex;gap:8px;}
    .search-form input{padding:8px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13px;background:var(--green-050);min-width:210px;}
    .search-form button{padding:8px 14px;border-radius:10px;border:none;background:var(--ink-900);color:#fff;font-weight:700;font-size:12.5px;cursor:pointer;}

    .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .ap-table{width:100%;border-collapse:collapse;min-width:820px;}
    .ap-table th{text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;padding:9px 10px;border-bottom:1px solid var(--line);white-space:nowrap;}
    .ap-table td{padding:13px 10px;font-size:13px;border-bottom:1px solid var(--line);vertical-align:middle;}
    .ap-table tr:last-child td{border-bottom:none;}
    .sub{font-size:11.5px;color:var(--ink-400);font-weight:600;}

    .unit-pill{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);}
    .type-pill{font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#eef2f0;color:var(--ink-600);white-space:nowrap;}
    .type-pill.free-use{background:var(--orange-100);color:#a85b1f;}

    .st{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;white-space:nowrap;display:inline-block;}
    .st-pending{background:var(--orange-100);color:#a85b1f;}
    .st-verified{background:#e6f0fb;color:#2b5d9b;}
    .st-payment_submitted{background:#f1e9fb;color:#6a3fa0;}
    .st-receipt_confirmed,.st-form_submitted{background:var(--green-100);color:var(--green-700);}
    .st-scheduled{background:var(--green-600);color:#fff;}
    .st-finished{background:#eef2f0;color:var(--ink-600);}
    .st-rescheduled{background:#fff4d6;color:#8a6a00;}
    .st-cancelled{background:#f4e4de;color:#b4502c;}

    .row-actions{display:flex;gap:6px;}
    .row-actions button{font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;border:1px solid transparent;white-space:nowrap;}
    .btn-review{background:#fff;color:var(--green-700);border-color:var(--green-100);}
    .btn-review:hover{background:var(--green-050);}
    .btn-schedule{background:var(--green-600);color:#fff;}
    .btn-schedule:hover{background:var(--green-700);}
    .btn-staff{display:inline-block;font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;white-space:nowrap;background:var(--ink-900);color:#fff;}
    .btn-staff.done{background:#fff;color:var(--green-700);border:1px solid var(--green-100);}

    .pager{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:12.5px;color:var(--ink-400);font-weight:600;}
    .pager a{font-weight:700;color:var(--green-700);padding:6px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;}
    .pager span.off{padding:6px 12px;border:1px solid var(--line);border-radius:9px;opacity:.45;}

    /* modals (own classes so they don't fight the layout's logout modal) */
    .ap-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .ap-overlay.show{display:flex;}
    .ap-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:560px;max-height:88vh;overflow-y:auto;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .ap-box.sm{max-width:400px;}
    .ap-title{font-size:16px;font-weight:800;margin-bottom:4px;}
    .ap-text{font-size:13px;color:var(--ink-600);line-height:1.5;margin-bottom:14px;}
    .ap-section{font-size:12px;font-weight:800;color:var(--green-700);margin:18px 0 4px;}
    .d-row{display:flex;justify-content:space-between;gap:16px;font-size:13px;padding:7px 0;border-bottom:1px solid var(--line);}
    .d-row:last-child{border-bottom:none;}
    .d-label{color:var(--ink-400);font-weight:600;flex-shrink:0;}
    .d-value{font-weight:700;text-align:right;word-break:break-word;}
    .d-value a{color:var(--green-700);text-decoration:underline;}
    .warn{background:var(--orange-100);color:#a85b1f;border-radius:10px;padding:10px 12px;font-size:12.5px;font-weight:600;margin:12px 0 0;}
    .ap-box textarea{width:100%;min-height:70px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13px;background:var(--green-050);margin-bottom:16px;resize:vertical;}
    .ap-actions{display:flex;gap:10px;margin-top:18px;}
    .ap-actions button{flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);}
    .ap-actions .b-close{background:#fff;color:var(--ink-900);}
    .ap-actions .b-go{background:var(--green-600);color:#fff;border-color:transparent;}
</style>
@endpush

@section('content')

@if(session('status'))
    <div class="flash">{{ session('status') }}</div>
@endif

<div class="view-tabs">
    <a class="view-tab {{ $mode === 'all' ? 'active' : '' }}" href="{{ route('admin.appointments.index') }}">All appointments <span class="n">{{ $totalCount }}</span></a>
    <a class="view-tab {{ $mode === 'scheduled' ? 'active' : '' }}" href="{{ route('admin.appointments.scheduled') }}">Scheduled <span class="n">{{ $counts['scheduled'] ?? 0 }}</span></a>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $mode === 'scheduled' ? 'Scheduled Appointments' : 'Appointment Monitor' }}</div>
        <div class="dropdown-chip">{{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('result', $appointments->total()) }}</div>
    </div>

    <div class="filter-bar">
        @if($mode === 'all')
            <div class="chips">
                <a class="chip {{ !$activeStatus ? 'active' : '' }}" href="{{ route('admin.appointments.index', array_filter(['q' => $search])) }}">All<span class="n">{{ $totalCount }}</span></a>
                @foreach($statusLabels as $key => $label)
                    <a class="chip {{ $activeStatus === $key ? 'active' : '' }}" href="{{ route('admin.appointments.index', array_filter(['status' => $key, 'q' => $search])) }}">{{ $label }}<span class="n">{{ $counts[$key] ?? 0 }}</span></a>
                @endforeach
            </div>
        @else
            <div></div>
        @endif

        <form class="search-form" method="GET" action="{{ $mode === 'scheduled' ? route('admin.appointments.scheduled') : route('admin.appointments.index') }}">
            @if($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search client or activity">
            <button type="submit">Search</button>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ap-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Activity</th>
                    <th>Facility</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td><strong>{{ $row['client_name'] }}</strong><br><span class="sub">{{ $row['contact_number'] ?? '—' }}</span></td>
                        <td>{{ $row['activity'] }}</td>
                        <td><span class="unit-pill">{{ $row['facility'] ?? '—' }}</span></td>
                        <td>{{ $row['date'] ?? '—' }}<br><span class="sub">{{ $row['time'] }}</span></td>
                        <td><span class="type-pill {{ $row['track'] === 'free_use' ? 'free-use' : '' }}">{{ $row['type_label'] }}</span></td>
                        <td><span class="st st-{{ $row['status'] }}">{{ $row['status_label'] }}</span></td>
                        <td>{{ $row['payment']['status'] ?? '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn-review" onclick="openReview({{ $row['id'] }})">Review</button>
                                @if($row['can_schedule'])
                                    <button type="button" class="btn-schedule" onclick="openSchedule({{ $row['id'] }})">Schedule</button>
                                @endif
                                @if($row['is_scheduled'])
                                    <a class="btn-staff {{ $row['assigned_count'] ? 'done' : '' }}" href="{{ $row['staff_url'] }}">
                                        {{ $row['assigned_count'] ? 'Edit staff (' . $row['assigned_count'] . ')' : 'Assign staff' }}
                                    </a>
                                @endif
                            </div>
                        </td>
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
            <div class="e-icon">📅</div>
            <div class="e-title">{{ $mode === 'scheduled' ? 'Nothing scheduled yet' : 'No appointments found' }}</div>
            <div class="e-sub">
                {{ $mode === 'scheduled' ? 'Appointments you schedule from the monitor will appear here.' : 'Try another status or search term.' }}
            </div>
        </div>
    @endif
</div>

{{-- Review modal --}}
<div class="ap-overlay" id="reviewModal">
    <div class="ap-box">
        <div class="ap-title" id="reviewTitle">Review appointment</div>
        <div id="reviewBody"></div>
        <div class="ap-actions">
            <button type="button" class="b-close" onclick="closeModal('reviewModal')">Close</button>
            <button type="button" class="b-go" id="reviewScheduleBtn" style="display:none;">Schedule</button>
        </div>
    </div>
</div>

{{-- Schedule confirm modal --}}
<div class="ap-overlay" id="scheduleModal">
    <div class="ap-box sm">
        <div class="ap-title">Schedule this appointment?</div>
        <div class="ap-text" id="scheduleText"></div>
        <form method="POST" id="scheduleForm">
            @csrf
            <textarea name="note" placeholder="Optional note..."></textarea>
            <div class="ap-actions" style="margin-top:0;">
                <button type="button" class="b-close" onclick="closeModal('scheduleModal')">Never mind</button>
                <button type="submit" class="b-go">Schedule</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const SCHEDULE_URL = @json(url('/admin/appointments'));

    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    // Escape anything coming from client input before putting it in innerHTML.
    function esc(v){
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function link(url, label){
        return url ? `<a href="${esc(url)}" target="_blank" rel="noopener">${label}</a>` : '—';
    }
    function section(title, pairs){
        const rows = pairs.map(([l, v, raw]) =>
            `<div class="d-row"><span class="d-label">${l}</span><span class="d-value">${raw ? v : esc(v)}</span></div>`
        ).join('');
        return `<div class="ap-section">${title}</div>${rows}`;
    }

    function openReview(id){
        const r = ROWS.find(x => x.id === id);
        if (!r) return;

        let html = '';
        html += section('Client', [
            ['Name', r.client_name],
            ['Contact', r.contact_number],
            ['Email', r.email],
            ['Barangay', r.barangay],
        ]);
        html += section('Booking', [
            ['Activity', r.activity],
            ['Facility', r.facility],
            ['Date', r.date],
            ['Time', r.time],
            ['Type', r.type_label],
            ['Rate', r.rate_type],
            ['Aircon', r.aircon],
            ['Expected attendees', r.expected_attendees],
            ['Requested on', r.requested_at],
            ['Status', r.status_label],
        ]);

        if (r.payment) {
            const p = r.payment;
            html += section('Payment', [
                ['Payment status', p.status],
                ['Total', p.total],
                ['Downpayment', p.downpayment],
                ['Balance', p.balance],
                ['Balance due', p.balance_due],
                ['GCash reference', p.gcash_ref],
                ['Paid on', p.paid_at],
                ['Receipt confirmed', p.receipt_confirmed_at],
                ['Payment Order Slip', link(p.receipt_url, 'View'), true],
                ['Proof of payment', link(p.proof_url, 'View'), true],
                ['Client note', p.client_notes],
            ]);
        } else if (r.track === 'free_use') {
            html += section('Free-use approval', [
                ['Approval form', link(r.approval_form_url, 'View'), true],
                ['Submitted on', r.approval_form_at],
            ]);
        }

        html += section('Verification & commitment', [
            ['Verified by', r.verified_by],
            ['Verified on', r.verified_at],
            ['Commitment form acknowledged', r.commitment_at],
        ]);
        html += section('Notes', [['Notes', r.notes]]);

        if (r.conflicts > 0) {
            html += `<div class="warn">Heads up: ${r.conflicts} scheduled appointment(s) already overlap this time in the same facility.</div>`;
        }

        document.getElementById('reviewTitle').textContent = r.activity || 'Review appointment';
        document.getElementById('reviewBody').innerHTML = html;

        const btn = document.getElementById('reviewScheduleBtn');
        if (r.can_schedule) {
            btn.style.display = '';
            btn.onclick = () => { closeModal('reviewModal'); openSchedule(id); };
        } else {
            btn.style.display = 'none';
        }
        openModal('reviewModal');
    }

    function openSchedule(id){
        const r = ROWS.find(x => x.id === id);
        if (!r) return;
        document.getElementById('scheduleText').textContent =
            `${r.activity} — ${r.client_name}, ${r.date} ${r.time}. ` +
            (r.conflicts > 0 ? `Note: ${r.conflicts} scheduled appointment(s) already overlap this slot. ` : '') +
            'It will move to Scheduled Appointments.';
        document.getElementById('scheduleForm').action = `${SCHEDULE_URL}/${id}/schedule`;
        openModal('scheduleModal');
    }
</script>
@endpush