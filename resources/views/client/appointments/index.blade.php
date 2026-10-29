@extends('layouts.client')

@section('title', 'My Appointments')

@push('styles')
<style>
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;}
    .flash.ok{background:var(--green-100);color:#0f4433;border:1px solid #cdeedd;}
    .flash.err{background:#f4e4de;color:#b4502c;border:1px solid #ecd2c8;}

    .view-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;}
    .view-tab{font-size:13px;font-weight:700;padding:8px 14px;border-radius:10px;color:var(--ink-600);background:rgba(255,255,255,.85);border:1px solid var(--line);}
    .view-tab.active{background:var(--green-600);color:#fff;border-color:var(--green-600);}
    .view-tab .n{margin-left:6px;font-size:11px;opacity:.8;}

    .ap-table{width:100%;border-collapse:collapse;min-width:860px;}
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

    .row-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
    .row-actions button,.row-actions a.btn{font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;border:1px solid transparent;white-space:nowrap;display:inline-block;}
    .btn-resched{background:var(--green-600);color:#fff;}
    .btn-resched:hover{background:var(--green-700);}
    .btn-cancel-row{background:#fff;color:#b4502c;border-color:#f4e4de;}
    .btn-cancel-row:hover{background:#f4e4de;}
    .btn-continue{background:var(--ink-900);color:#fff;}
    .open-tag{display:block;font-size:11px;font-weight:700;color:#a85b1f;margin-bottom:5px;}

    .pager{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:12.5px;color:var(--ink-400);font-weight:600;}
    .pager a{font-weight:700;color:var(--green-700);padding:6px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;}
    .pager span.off{padding:6px 12px;border:1px solid var(--line);border-radius:9px;opacity:.45;}

    .ap-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .ap-overlay.show{display:flex;}
    .ap-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:420px;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .ap-icon{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:12px;}
    .ap-icon.warn{background:#f4e4de;color:#b4502c;}
    .ap-icon.info{background:var(--orange-100);color:var(--orange-500);}
    .ap-title{font-size:16px;font-weight:800;margin-bottom:6px;}
    .ap-which{font-size:12.5px;color:var(--ink-400);font-weight:600;margin-bottom:12px;}
    .ap-text{font-size:13px;color:var(--ink-600);line-height:1.55;margin-bottom:14px;}
    .ap-callout{border-radius:10px;padding:11px 13px;font-size:12.5px;font-weight:700;margin-bottom:14px;}
    .ap-callout.red{background:#f4e4de;color:#b4502c;}
    .ap-callout.amber{background:var(--orange-100);color:#a85b1f;}
    .ap-box textarea{width:100%;min-height:64px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13px;background:var(--green-050);margin-bottom:14px;resize:vertical;}
    .ap-actions{display:flex;gap:10px;}
    .ap-actions button,.ap-actions a{flex:1;text-align:center;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);}
    .ap-actions .b-close{background:#fff;color:var(--ink-900);}
    .ap-actions .b-danger{background:#b4502c;color:#fff;border-color:transparent;}
    .ap-actions .b-go{background:var(--green-600);color:#fff;border-color:transparent;}
</style>
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

<div class="view-tabs">
    <a class="view-tab {{ $tab === 'active' ? 'active' : '' }}" href="{{ route('client.appointments.index') }}">Active <span class="n">{{ $counts['active'] }}</span></a>
    <a class="view-tab {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('client.appointments.index', ['tab' => 'history']) }}">History <span class="n">{{ $counts['history'] }}</span></a>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $tab === 'history' ? 'Past appointments' : 'My appointments' }}</div>
        <div class="dropdown-chip">{{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}</div>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ap-table">
            <thead>
                <tr>
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
                        <td><strong>{{ $row['activity'] }}</strong></td>
                        <td><span class="unit-pill">{{ $row['facility'] }}</span></td>
                        <td>{{ $row['date'] }}<br><span class="sub">{{ $row['time'] }}</span></td>
                        <td><span class="type-pill {{ $row['is_free'] ? 'free-use' : '' }}">{{ $row['type_label'] }}</span></td>
                        <td><span class="st st-{{ $row['status'] }}">{{ $row['status_label'] }}</span></td>
                        <td class="sub" style="font-size:12.5px;">{{ $row['payment_label'] }}</td>
                        <td>
                            @if($row['open_reschedule'])<span class="open-tag">{{ $row['open_reschedule'] }}</span>@endif
                            <div class="row-actions">
                                @if($row['continue_url'])
                                    <a class="btn btn-continue" href="{{ $row['continue_url'] }}">Continue reschedule</a>
                                @elseif($row['can_reschedule'])
                                    <button type="button" class="btn-resched" onclick="openReschedule({{ $row['id'] }})">Reschedule</button>
                                @endif
                                @if($row['can_cancel'])
                                    <button type="button" class="btn-cancel-row" onclick="openCancel({{ $row['id'] }})">Cancel</button>
                                @endif
                                @if(!$row['can_cancel'] && !$row['can_reschedule'] && !$row['continue_url'])
                                    <span class="sub">—</span>
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
            <div class="e-title">{{ $tab === 'history' ? 'No past appointments' : 'No active appointments' }}</div>
            <div class="e-sub">{{ $tab === 'history' ? 'Finished and cancelled appointments will show here.' : 'Book a new reservation to get started.' }}</div>
        </div>
    @endif
</div>

{{-- Cancel notice modal --}}
<div class="ap-overlay" id="cancelModal">
    <div class="ap-box">
        <div class="ap-icon warn">⚠</div>
        <div class="ap-title">Cancel this appointment?</div>
        <div class="ap-which" id="cancelWhich"></div>
        <div class="ap-callout red">No refund will be given if you cancel this appointment.</div>
        <div class="ap-text">Cancelling can't be undone. If you only need a different date, use Reschedule instead (an additional {{ $feePercent }}% fee applies for paid appointments).</div>
        <form method="POST" id="cancelForm">
            @csrf
            <textarea name="reason" placeholder="Reason for cancelling (optional)"></textarea>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('cancelModal')">Keep appointment</button>
                <button type="submit" class="b-danger">Yes, cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Reschedule notice modal --}}
<div class="ap-overlay" id="rescheduleModal">
    <div class="ap-box">
        <div class="ap-icon info">↻</div>
        <div class="ap-title">Reschedule this appointment?</div>
        <div class="ap-which" id="reschedWhich"></div>
        <div class="ap-callout amber" id="reschedCallout"></div>
        <div class="ap-text" id="reschedText"></div>
        <div class="ap-actions">
            <button type="button" class="b-close" onclick="closeModal('rescheduleModal')">Never mind</button>
            <a class="b-go" id="reschedGo" href="#">Continue</a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const FEE_PERCENT = @json($feePercent);

    function esc(v){
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    function openCancel(id){
        const r = ROWS.find(x => x.id === id);
        if (!r) return;
        document.getElementById('cancelWhich').textContent = `${r.activity} · ${r.date}, ${r.time}`;
        document.getElementById('cancelForm').action = r.cancel_url;
        openModal('cancelModal');
    }

    function openReschedule(id){
        const r = ROWS.find(x => x.id === id);
        if (!r) return;
        document.getElementById('reschedWhich').textContent = `${r.activity} · ${r.date}, ${r.time}`;

        const callout = document.getElementById('reschedCallout');
        const text = document.getElementById('reschedText');

        if (r.is_free) {
            callout.textContent = 'No rescheduling fee for free-use appointments.';
            text.textContent = 'Pick a new date and time. Once changed, the Admin will schedule your appointment again for the new date.';
        } else {
            callout.textContent = r.fee_preview
                ? `An additional ${FEE_PERCENT}% fee (${r.fee_preview}) is required to reschedule.`
                : `An additional ${FEE_PERCENT}% fee of your total is required to reschedule.`;
            text.textContent = 'You will pick a new date and time, then pay the fee through GCash. Your current slot stays reserved until the fee payment is confirmed.';
        }

        document.getElementById('reschedGo').href = r.reschedule_url;
        openModal('rescheduleModal');
    }
</script>
@endpush