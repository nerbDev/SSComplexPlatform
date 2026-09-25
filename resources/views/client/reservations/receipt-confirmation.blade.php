@extends('layouts.client')

@section('title', 'Receipt Confirmation')

@push('styles')
<style>
    .rc-wrap{max-width:560px;margin:0 auto;}
    .receipt-preview{
        border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.65);
        padding:18px;margin-bottom:18px;text-align:center;
    }
    .receipt-icon{font-size:34px;margin-bottom:8px;}
    .receipt-name{font-weight:800;font-size:13.5px;margin-bottom:4px;}
    .receipt-sub{font-size:12px;color:var(--ink-400);margin-bottom:14px;}
    .receipt-link{
        display:inline-block;padding:10px 18px;border-radius:10px;background:var(--green-600);
        color:#fff;font-weight:700;font-size:13px;
    }
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .field input[type=file],.field textarea{
        width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;
        font-family:inherit;font-size:13.5px;background:rgba(255,255,255,.7);
    }
    .field textarea{min-height:70px;resize:vertical;}
    .field-hint{font-size:11.5px;color:var(--ink-400);margin-top:5px;}
    .field-error{color:var(--orange-500);font-size:11.5px;font-weight:700;margin-top:6px;}
    .btn-submit{width:100%;margin-top:6px;padding:13px 0;border-radius:11px;border:none;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;cursor:pointer;}
    .btn-submit:hover{background:#146245;}
</style>
@endpush

@section('content')
<div class="rc-wrap card">
    <div class="card-title" style="margin-bottom:16px;">Step 4 — Receipt Confirmation</div>

    <div class="receipt-preview">
        <div class="receipt-icon">🧾</div>
        <div class="receipt-name">Payment Order Slip generated</div>
        <div class="receipt-sub">Reference: {{ $payment->gcash_reference }} · ₱{{ number_format($payment->downpayment_amount, 2) }}</div>
        @if($payment->receipt_path)
            <a href="{{ asset('storage/' . $payment->receipt_path) }}" target="_blank" class="receipt-link">View Payment Order Slip</a>
        @endif
    </div>

    <form method="POST" action="{{ route('client.reservations.receipt-confirmation.store', $appointment) }}" enctype="multipart/form-data">
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

        <button type="submit" class="btn-submit">Confirm & Submit for Scheduling</button>
    </form>
</div>
@endsection