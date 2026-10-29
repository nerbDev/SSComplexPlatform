<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Order Slip — Rescheduling Fee</title>
    <style>
        /* DejaVu Sans ships with dompdf and includes the peso sign. */
        body{font-family:'DejaVu Sans',sans-serif;font-size:12px;color:#0e1c17;}
        h1{font-size:17px;margin:0 0 2px;}
        .sub{color:#5b6b64;margin-bottom:18px;}
        table{width:100%;border-collapse:collapse;margin-bottom:14px;}
        td{padding:7px 4px;border-bottom:1px solid #e4ece8;vertical-align:top;}
        td.l{color:#5b6b64;width:38%;}
        td.v{font-weight:bold;text-align:right;}
        .total td{border-top:2px solid #0e1c17;border-bottom:none;font-size:14px;font-weight:bold;}
        .foot{margin-top:22px;font-size:10.5px;color:#5b6b64;}
        .head{margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #178a5e;}
    </style>
</head>
<body>
    <div class="head">
        <h1>Subic Sports Complex</h1>
        <div class="sub">Payment Order Slip — Rescheduling Fee</div>
    </div>

    <table>
        <tr><td class="l">Slip no.</td><td class="v">RF-{{ str_pad($fee->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td class="l">Paid by</td><td class="v">{{ $payeeName ?: '—' }}</td></tr>
        <tr><td class="l">Facility</td><td class="v">{{ $facility->name }}</td></tr>
        <tr><td class="l">Activity</td><td class="v">{{ $appointment->activity_title }}</td></tr>
        <tr>
            <td class="l">Original slot</td>
            <td class="v">{{ $fee->original_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($fee->original_start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($fee->original_end_time)->format('g:i A') }}</td>
        </tr>
        <tr>
            <td class="l">New slot</td>
            <td class="v">{{ $fee->new_date->format('M j, Y') }}, {{ \Carbon\Carbon::parse($fee->new_start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($fee->new_end_time)->format('g:i A') }}</td>
        </tr>
        <tr><td class="l">Total booking value</td><td class="v">₱{{ number_format($fee->base_total, 2) }}</td></tr>
        <tr><td class="l">Rescheduling fee rate</td><td class="v">{{ $fee->fee_percent }}%</td></tr>
        <tr><td class="l">GCash reference</td><td class="v">{{ $fee->gcash_reference }}</td></tr>
        <tr class="total"><td class="l">Amount paid</td><td class="v">₱{{ number_format($fee->fee_amount, 2) }}</td></tr>
    </table>

    <div class="foot">
        Generated {{ now()->format('M j, Y g:i A') }}. This slip records the client's submitted GCash payment; the transaction is still subject to staff verification.
    </div>
</body>
</html>