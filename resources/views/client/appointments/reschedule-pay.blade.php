@extends('layouts.client')

@section('title', 'Pay Rescheduling Fee')

@push('styles')
<style>
    .pay-wrap{max-width:560px;margin:0 auto;}
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;background:var(--green-100);color:#0f4433;border:1px solid #cdeedd;}
    .summary-box{background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:18px;}
    .summary-row{display:flex;justify-content:space-between;gap:14px;font-size:13.5px;padding:6px 0;}
    .summary-row.total{font-weight:800;font-size:16px;border-top:1px dashed var(--line);margin-top:8px;padding-top:12px;}
    .summary-row .muted-label{color:var(--ink-400);}
    .summary-row span:last-child{text-align:right;}
    .note-chip{background:var(--orange-100);color:#a85b1f;border:1px solid rgba(239,141,61,.35);border-radius:10px;padding:11px 14px;font-size:12.5px;font-weight:600;margin-bottom:18px;}
    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13.5px;background:rgba(255,255,255,.7);}
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:6px;}
    .btn-submit{width:100%;margin-top:18px;padding:13px 0;border-radius:11px;border:none;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;cursor:pointer;}
    .btn-submit:hover{background:#146245;}
    .links{display:flex;justify-content:space-between;align-items:center;margin-top:16px;gap:10px;flex-wrap:wrap;}
    .links a,.links button{font-size:12.5px;font-weight:700;color:var(--green-700);background:none;border:none;cursor:pointer;padding:0;font-family:inherit;}
    .links button{color:#b4502c;}
</style>
@endpush

@section('content')
<div class="pay-wrap card">
    @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="card-title" style="margin-bottom:4px;">{{ $facility->name }}</div>
    <div class="muted" style="font-size:12.5px;margin-bottom:16px;">{{ $appointment->activity_title }} · Rescheduling fee</div>

    <div class="summary-box">
        <div class="summary-row">
            <span class="muted-label">Current slot</span>
            <span>{{ $appointment->event_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</span>
        </div>
        <div class="summary-row">
            <span class="muted-label">New slot</span>
            <span>{{ $fee->new_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($fee->new_start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($fee->new_end_time)->format('g:i A') }}</span>
        </div>
        <div class="summary-row"><span class="muted-label">Total booking value</span><span>₱{{ number_format($fee->base_total, 2) }}</span></div>
        <div class="summary-row"><span class="muted-label">Rescheduling fee rate</span><span>{{ $fee->fee_percent }}%</span></div>
        <div class="summary-row total"><span>Fee due now</span><span>₱{{ number_format($fee->fee_amount, 2) }}</span></div>
    </div>

    <div class="note-chip">
        Your current slot stays reserved until this fee is confirmed. GCash payment collection isn't wired up in the system yet — send this amount via GCash, then enter the reference number below. Staff will verify the transaction manually.
    </div>

    <form method="POST" action="{{ route('client.appointments.reschedule.pay.store', $appointment) }}">
        @csrf
        <div class="field">
            <label>GCash Reference Number</label>
            <input type="text" name="gcash_reference" value="{{ old('gcash_reference') }}" placeholder="e.g. 1234567890123" required>
            @error('gcash_reference')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-submit">Submit Payment</button>
    </form>

    <div class="links">
        <a href="{{ route('client.appointments.reschedule', $appointment) }}">Change the new date</a>
        <form method="POST" action="{{ route('client.appointments.reschedule.abandon', $appointment) }}" onsubmit="return confirm('Drop this reschedule request? Your original date stays as it is.');">
            @csrf
            <button type="submit">Drop this reschedule</button>
        </form>
    </div>
</div>
@endsection