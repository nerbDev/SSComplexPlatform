@extends('layouts.client')

@section('title', 'Reschedule Appointment')

@push('styles')
<style>
    .rs-wrap{max-width:560px;margin:0 auto;}
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;}
    .flash.ok{background:var(--green-100);color:#0f4433;border:1px solid #cdeedd;}
    .flash.err{background:#f4e4de;color:#b4502c;border:1px solid #ecd2c8;}
    .summary-box{background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:18px;}
    .summary-row{display:flex;justify-content:space-between;gap:14px;font-size:13.5px;padding:6px 0;}
    .summary-row .muted-label{color:var(--ink-400);}
    .summary-row span:last-child{font-weight:700;text-align:right;}
    .note-chip{background:var(--orange-100);color:#a85b1f;border:1px solid rgba(239,141,61,.35);border-radius:10px;padding:11px 14px;font-size:12.5px;font-weight:600;margin-bottom:18px;}
    .note-chip.green{background:var(--green-100);color:var(--green-700);border-color:#cdeedd;}
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13.5px;background:rgba(255,255,255,.7);}
    .field-hint{font-size:11.5px;color:var(--ink-400);margin-top:5px;font-weight:600;}
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:6px;}
    .busy-box{font-size:12px;font-weight:600;color:var(--ink-600);background:var(--green-050);border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:16px;display:none;}
    .busy-box.show{display:block;}
    .busy-box.block{background:#f4e4de;color:#b4502c;border-color:#ecd2c8;}
    .busy-box .chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
    .busy-box .chip{background:#fff;border:1px solid var(--line);border-radius:999px;padding:3px 10px;font-weight:700;}
    .btn-submit{width:100%;margin-top:6px;padding:13px 0;border-radius:11px;border:none;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;cursor:pointer;}
    .btn-submit:hover{background:#146245;}
    .btn-submit:disabled{opacity:.5;cursor:not-allowed;}
    .back-link{display:block;text-align:center;margin-top:14px;font-size:13px;font-weight:700;color:var(--green-700);}
</style>
@endpush

@php
    $startC = \Carbon\Carbon::parse($appointment->start_time);
    $endC   = \Carbon\Carbon::parse($appointment->end_time);
    $hours  = intdiv($duration, 60);
    $mins   = $duration % 60;
    $lenLabel = trim(($hours ? $hours . ' hr ' : '') . ($mins ? $mins . ' min' : ''));
@endphp

@section('content')
<div class="rs-wrap card">
    @if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

    <div class="card-title" style="margin-bottom:4px;">{{ $appointment->facility->name }}</div>
    <div class="summary-box" style="margin-top:12px;">
        <div class="summary-row"><span class="muted-label">Activity</span><span>{{ $appointment->activity_title }}</span></div>
        <div class="summary-row"><span class="muted-label">Current date</span><span>{{ $appointment->event_date->format('M j, Y') }}</span></div>
        <div class="summary-row"><span class="muted-label">Current time</span><span>{{ $startC->format('g:i A') }} – {{ $endC->format('g:i A') }}</span></div>
        <div class="summary-row"><span class="muted-label">Length</span><span>{{ $lenLabel }}</span></div>
        @if($openFee && $openFee->new_date)
            <div class="summary-row"><span class="muted-label">Requested new slot</span><span>{{ $openFee->new_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($openFee->new_start_time)->format('g:i A') }}</span></div>
        @endif
    </div>

    @if($isFree)
        <div class="note-chip green">Free-use appointments have no rescheduling fee. After you pick a new date, the Admin will schedule it again.</div>
    @else
        <div class="note-chip">
            A {{ $percent }}% additional fee (₱{{ number_format($feeAmount, 2) }}) applies. You'll pay it on the next step; your current slot stays reserved until that payment is confirmed.
            @if($openFee && $openFee->status === 'submitted')
                <br>You already paid this fee — changing the date here won't charge you again.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('client.appointments.reschedule.store', $appointment) }}" id="rsForm">
        @csrf

        <div class="field">
            <label>New date</label>
            <input type="date" name="new_date" id="newDate" value="{{ old('new_date', $openFee?->new_date?->toDateString()) }}" min="{{ now()->toDateString() }}" required>
            @error('new_date')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div class="busy-box" id="busyBox"></div>

        <div class="field">
            <label>New start time</label>
            <input type="time" name="start_time" id="newStart" value="{{ old('start_time', $openFee ? substr($openFee->new_start_time, 0, 5) : substr($appointment->start_time, 0, 5)) }}" required>
            <div class="field-hint" id="endHint">Your slot keeps the same length ({{ $lenLabel }}).</div>
            @error('start_time')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-submit" id="rsSubmit">{{ $isFree ? 'Reschedule' : 'Continue to payment' }}</button>
    </form>

    <a class="back-link" href="{{ route('client.appointments.index') }}">← Back to my appointments</a>
</div>
@endsection

@push('scripts')
<script>
    const AVAIL_URL = @json(route('client.appointments.reschedule.availability', $appointment));
    const DURATION = @json($duration);

    const dateEl = document.getElementById('newDate');
    const startEl = document.getElementById('newStart');
    const busyBox = document.getElementById('busyBox');
    const endHint = document.getElementById('endHint');
    const submitBtn = document.getElementById('rsSubmit');

    let busy = [];
    let wholeDayBlocked = false;

    const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const fmt = t => { const [h, m] = t.split(':').map(Number); const ap = h >= 12 ? 'PM' : 'AM'; return ((h % 12) || 12) + ':' + String(m).padStart(2, '0') + ' ' + ap; };
    const fmtMin = mins => fmt(String(Math.floor(mins / 60)).padStart(2, '0') + ':' + String(mins % 60).padStart(2, '0'));

    function esc(v){ return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function validate(){
        let ok = true;
        let msg = `Your slot keeps the same length (${@json($lenLabel)}).`;

        if (startEl.value) {
            const s = toMin(startEl.value);
            const e = s + DURATION;
            if (e >= 24 * 60) {
                ok = false; msg = 'That start time would run past midnight.';
            } else {
                msg = `New slot: ${fmt(startEl.value)} – ${fmtMin(e)}`;
                if (busy.some(r => toMin(r.start) < e && toMin(r.end) > s)) {
                    ok = false; msg += ' — overlaps an existing booking.';
                }
            }
        } else { ok = false; }

        if (wholeDayBlocked) ok = false;
        if (!dateEl.value) ok = false;

        endHint.textContent = msg;
        endHint.style.color = ok ? '' : 'var(--orange-500)';
        submitBtn.disabled = !ok;
    }

    async function loadAvailability(){
        busyBox.className = 'busy-box';
        busy = []; wholeDayBlocked = false;
        if (!dateEl.value) { validate(); return; }

        try {
            const res = await fetch(AVAIL_URL + '?date=' + encodeURIComponent(dateEl.value), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            busy = data.busy_ranges || [];
            wholeDayBlocked = !!data.whole_day_blocked;

            if (wholeDayBlocked) {
                busyBox.className = 'busy-box show block';
                busyBox.textContent = data.block_reason || 'This facility is not available on that date.';
            } else if (busy.length) {
                busyBox.className = 'busy-box show';
                busyBox.innerHTML = 'Already booked on this date:<div class="chips">' +
                    busy.map(r => `<span class="chip">${esc(fmt(r.start))} – ${esc(fmt(r.end))}</span>`).join('') + '</div>';
            }
        } catch (e) { /* availability is also re-checked by the server on submit */ }

        validate();
    }

    dateEl.addEventListener('change', loadAvailability);
    startEl.addEventListener('input', validate);
    loadAvailability();
</script>
@endpush