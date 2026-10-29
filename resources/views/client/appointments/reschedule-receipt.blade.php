@extends('layouts.client')

@section('title', 'Confirm Rescheduling Receipt')

@push('styles')
<style>
    .rc-wrap{max-width:560px;margin:0 auto;}
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;background:var(--green-100);color:#0f4433;border:1px solid #cdeedd;}
    .receipt-preview{border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.65);padding:18px;margin-bottom:18px;text-align:center;}
    .receipt-icon{font-size:34px;margin-bottom:8px;}
    .receipt-name{font-weight:800;font-size:13.5px;margin-bottom:4px;}
    .receipt-sub{font-size:12px;color:var(--ink-400);margin-bottom:14px;}
    .receipt-link{display:inline-block;padding:10px 18px;border-radius:10px;background:var(--green-600);color:#fff;font-weight:700;font-size:13px;}
    .slot-box{background:rgba(255,255,255,.6);border:1px solid var(--line);border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:13px;}
    .slot-box div{display:flex;justify-content:space-between;gap:12px;padding:4px 0;}
    .slot-box .l{color:var(--ink-400);font-weight:600;}
    .slot-box .v{font-weight:700;text-align:right;}
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input[type=file],.field textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13.5px;background:rgba(255,255,255,.7);}
    .field textarea{min-height:70px;resize:vertical;}
    .field-hint{font-size:11.5px;color:var(--ink-400);margin-top:5px;}
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:6px;}
    .btn-submit{width:100%;margin-top:6px;padding:13px 0;border-radius:11px;border:none;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;cursor:pointer;}
    .btn-submit:hover{background:#146245;}
    .back-link{display:block;text-align:center;margin-top:14px;font-size:13px;font-weight:700;color:var(--green-700);}
</style>
@endpush

@section('content')
<div class="rc-wrap card">
    @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="card-title" style="margin-bottom:16px;">Rescheduling — Receipt Confirmation</div>

    <div class="receipt-preview">
        <div class="receipt-icon">🧾</div>
        <div class="receipt-name">Payment Order Slip generated</div>
        <div class="receipt-sub">Reference: {{ $fee->gcash_reference }} · ₱{{ number_format($fee->fee_amount, 2) }} ({{ $fee->fee_percent }}% rescheduling fee)</div>
        @if($fee->receipt_path)
            <a href="{{ asset('storage/' . $fee->receipt_path) }}" target="_blank" class="receipt-link">View Payment Order Slip</a>
        @endif
    </div>

    <div class="slot-box">
        <div><span class="l">Facility</span><span class="v">{{ $appointment->facility->name }}</span></div>
        <div><span class="l">From</span><span class="v">{{ $appointment->event_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</span></div>
        <div><span class="l">To</span><span class="v">{{ $fee->new_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($fee->new_start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($fee->new_end_time)->format('g:i A') }}</span></div>
    </div>

    <form method="POST" action="{{ route('client.appointments.reschedule.receipt.store', $appointment) }}" enctype="multipart/form-data">
        @csrf

        <div class="field">
            <label>Upload your proof of payment (optional)</label>
            <input type="file" name="proof_of_payment" accept="image/*,.pdf">
            <div class="field-hint">A screenshot or photo of your GCash transaction, if you'd like to attach one alongside the generated receipt.</div>
            @error('proof_of_payment')<div class="field-error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Notes (optional)</label>
            <textarea name="client_notes" placeholder="Anything else the Admin should know?">{{ old('client_notes') }}</textarea>
        </div>

        <button type="submit" class="btn-submit">Confirm & Move My Appointment</button>
    </form>

    <a class="back-link" href="{{ route('client.appointments.reschedule', $appointment) }}">Change the new date instead</a>
</div>
@endsection