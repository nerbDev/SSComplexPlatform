@extends('layouts.client')

@section('title', 'Pay for Reservation')

@push('styles')
<style>
    .pay-wrap{max-width:560px;margin:0 auto;}
    .summary-box{background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:18px;}
    .summary-row{display:flex;justify-content:space-between;font-size:13.5px;padding:6px 0;}
    .summary-row.total{font-weight:800;font-size:16px;border-top:1px dashed var(--line);margin-top:8px;padding-top:12px;}
    .summary-row .muted-label{color:var(--ink-400);}
    .note-chip{background:var(--orange-100);color:#a85b1f;border:1px solid rgba(239,141,61,.35);border-radius:10px;padding:11px 14px;font-size:12.5px;font-weight:600;margin-bottom:18px;}
    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13.5px;background:rgba(255,255,255,.7);}
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:6px;}
    .btn-submit{width:100%;margin-top:18px;padding:13px 0;border-radius:11px;border:none;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;cursor:pointer;}
    .btn-submit:hover{background:#146245;}
</style>
@endpush

@section('content')
<div class="pay-wrap card">
    <div class="card-title" style="margin-bottom:4px;">{{ $facility->name }}</div>
    <div class="muted" style="font-size:12.5px;margin-bottom:16px;">
        {{ $appointment->activity_title }} · {{ $appointment->event_date->format('M j, Y') }},
        {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
    </div>

    <div class="summary-box">
        <div class="summary-row"><span class="muted-label">Room rate</span><span>₱{{ number_format($amount, 2) }}</span></div>
        <div class="summary-row"><span class="muted-label">Ingress (before event)</span><span>₱{{ number_format($ingressBefore, 2) }}</span></div>
        <div class="summary-row"><span class="muted-label">Ingress (after event)</span><span>₱{{ number_format($ingressAfter, 2) }}</span></div>
        <div class="summary-row"><span class="muted-label">Total</span><span>₱{{ number_format($total, 2) }}</span></div>
        <div class="summary-row total">
            <span>{{ $appointment->booking_type === 'room_reservation' ? "Downpayment ({$downpaymentPercent}%)" : 'Full payment due now' }}</span>
            <span>₱{{ number_format($downpayment, 2) }}</span>
        </div>
        @if($appointment->booking_type === 'room_reservation')
            <div class="summary-row">
                <span class="muted-label">Remaining balance — due {{ $balanceDueDate->format('M j, Y') }}</span>
                <span>₱{{ number_format($balance, 2) }}</span>
            </div>
        @endif
    </div>

    <div class="note-chip">
        GCash payment collection isn't wired up in the system yet — send this amount via GCash, then enter the reference number below. Staff will verify the transaction manually.
    </div>

    <form method="POST" action="{{ route('client.reservations.pay.store', $appointment) }}">
        @csrf
        <div class="field">
            <label>GCash Reference Number</label>
            <input type="text" name="gcash_reference" value="{{ old('gcash_reference') }}" placeholder="e.g. 1234567890123" required>
            @error('gcash_reference')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-submit">Submit Payment</button>
    </form>
</div>
@endsection