<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0e1c17; margin: 0; padding: 30px; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { font-size: 14px; margin: 0; }
        .header h2 { font-size: 13px; margin: 2px 0 0; }
        .header h3 { font-size: 12px; margin: 10px 0 0; text-decoration: underline; }
        .meta-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; font-size: 12px; }
        .meta-table td.label { width: 110px; font-weight: bold; }
        table.billing { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.billing th, table.billing td { border: 1px solid #333; padding: 6px 8px; font-size: 11.5px; text-align: left; }
        table.billing th { background: #f0f0f0; }
        table.billing td.amount, table.billing th.amount { text-align: right; }
        .total-row td { font-weight: bold; }
        .section-title { font-weight: bold; font-size: 12px; margin: 16px 0 6px; }
        .note { font-size: 10.5px; color: #555; margin-top: 18px; }
        .footer { margin-top: 30px; font-size: 10.5px; }
        .sig-line { margin-top: 40px; border-top: 1px solid #333; width: 220px; padding-top: 4px; font-size: 10.5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MUNICIPALITY OF SUBIC</h1>
        <h2>SUBIC SPORTS COMPLEX</h2>
        <h3>PAYMENT ORDER SLIP</h3>
    </div>

    <table class="meta-table">
        <tr><td class="label">Reference No.</td><td>SSC-{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</td>
            <td class="label">Date Issued</td><td>{{ now()->format('F j, Y') }}</td></tr>
        <tr><td class="label">Booking Type</td><td>{{ ucfirst(str_replace('_', ' ', $appointment->booking_type)) }}</td>
            <td class="label">Status</td><td>{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</td></tr>
    </table>

    <div class="section-title">I. APPLICATION DETAILS</div>
    <table class="meta-table">
        <tr><td class="label">Event</td><td colspan="3">{{ $appointment->activity_title }}</td></tr>
        <tr><td class="label">Facility</td><td>{{ $facility->name }}</td>
            <td class="label">Date</td><td>{{ $appointment->event_date->format('F j, Y') }}</td></tr>
        <tr><td class="label">Time</td><td colspan="3">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</td></tr>
    </table>

    <div class="section-title">II. BILLING DETAILS</div>
    <table class="billing">
        <thead>
            <tr>
                <th>Mode of Rent</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $appointment->rate_type)) }}{{ $appointment->aircon !== null ? ($appointment->aircon ? ' (with aircon)' : ' (without aircon)') : '' }}</td>
                <td class="amount">₱{{ number_format($payment->base_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Ingress (before event)</td>
                <td class="amount">₱{{ number_format($payment->ingress_before, 2) }}</td>
            </tr>
            <tr>
                <td>Ingress (after event)</td>
                <td class="amount">₱{{ number_format($payment->ingress_after, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="amount">₱{{ number_format($payment->total_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>{{ $appointment->booking_type === 'room_reservation' ? "Downpayment ({$payment->downpayment_percent}%)" : 'Full Payment' }}</td>
                <td class="amount">₱{{ number_format($payment->downpayment_amount, 2) }}</td>
            </tr>
            @if($appointment->booking_type === 'room_reservation')
                <tr>
                    <td>Remaining Balance — due {{ optional($payment->balance_due_date)->format('F j, Y') }}</td>
                    <td class="amount">₱{{ number_format($payment->balance_amount, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">III. PAYMENT REFERENCE</div>
    <table class="meta-table">
        <tr><td class="label">GCash Reference</td><td colspan="3">{{ $payment->gcash_reference }}</td></tr>
    </table>

    <p class="note">
        This Payment Order Slip is a system-generated reference for the amount submitted above. It is not yet an official
        Municipal receipt until verified by SSC staff. Cancellations are non-refundable ({{ \App\Http\Controllers\Client\ReservationController::CANCELLATION_REFUND_PERCENT }}% cashback).
        Rescheduling incurs an additional {{ \App\Http\Controllers\Client\ReservationController::RESCHEDULING_FEE_PERCENT }}% fee.
    </p>

    <div class="footer">
        <div class="sig-line">Received by (SSC Staff)</div>
    </div>
</body>
</html>