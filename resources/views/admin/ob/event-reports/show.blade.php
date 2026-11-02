@extends('layouts.admin')

@section('title', 'Event Report')

@push('styles')
    @include('admin.ob._styles')
    <style>
        .rep-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:14px;}
        .rep-title{font-size:19px;font-weight:800;letter-spacing:-.2px;}
        @media print{
            .sidebar,.sidebar-overlay,.hamburger-btn,.topbar-actions,.no-print,.back-link{display:none !important;}
            body::before{display:none !important;}
            body{background:#fff !important;}
            .main{max-width:none;padding:0;}
            .page-title{color:#000 !important;text-shadow:none !important;}
            .card{background:#fff !important;backdrop-filter:none !important;-webkit-backdrop-filter:none !important;box-shadow:none !important;border:1px solid #ddd !important;break-inside:avoid;}
            .two-col{grid-template-columns:1fr 1fr;}
        }
    </style>
@endpush

@php
    $a = $appointment;
    $p = $a->payment;
    $money = fn($v) => '₱' . number_format((float) $v, 2);
    $clientName = trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: '—';
@endphp

@section('content')
<a class="back-link" href="{{ route('admin.ob.event-reports.index') }}">← Back to event reports</a>

<div class="card" style="margin-bottom:18px;">
    <div class="rep-head">
        <div>
            <div class="rep-title">{{ $a->activity_title }}</div>
            <div class="sub" style="margin-top:3px;">{{ $a->facility->name ?? '—' }} · {{ $a->event_date->format('F j, Y') }} · {{ $time }}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="pill p-{{ $a->status }}">{{ ucfirst($a->status) }}</span>
            <button type="button" class="btn-sm b-dark no-print" onclick="window.print()">Print report</button>
        </div>
    </div>

    <div class="two-col">
        <div>
            <div class="section-h" style="margin-top:0;">Event</div>
            <div class="d-row"><span class="d-label">Client</span><span class="d-value">{{ $clientName }}</span></div>
            <div class="d-row"><span class="d-label">Contact</span><span class="d-value">{{ $a->client->phone_number ?? '—' }}</span></div>
            <div class="d-row"><span class="d-label">Type</span><span class="d-value">{{ $typeLabel }}</span></div>
            <div class="d-row"><span class="d-label">Expected attendees</span><span class="d-value">{{ $a->expected_attendees ?? '—' }}</span></div>
            <div class="d-row"><span class="d-label">Visitor log entries</span><span class="d-value">{{ $visitorCount }}</span></div>
            <div class="d-row"><span class="d-label">Verified by</span><span class="d-value">{{ $a->verifiedBy ? trim($a->verifiedBy->first_name . ' ' . $a->verifiedBy->last_name) : '—' }}</span></div>
            <div class="d-row"><span class="d-label">Client feedback</span><span class="d-value">{{ $a->feedback ? 'Submitted' : 'None yet' }}</span></div>
        </div>
        <div>
            <div class="section-h" style="margin-top:0;">Payment</div>
            @if($p)
                <div class="d-row"><span class="d-label">Base amount</span><span class="d-value">{{ $money($p->base_amount) }}</span></div>
                <div class="d-row"><span class="d-label">Ingress (before)</span><span class="d-value">{{ $money($p->ingress_before) }}</span></div>
                <div class="d-row"><span class="d-label">Ingress (after)</span><span class="d-value">{{ $money($p->ingress_after) }}</span></div>
                <div class="d-row"><span class="d-label">Tax</span><span class="d-value">{{ $money($p->tax_amount) }}</span></div>
                <div class="d-row"><span class="d-label">Total</span><span class="d-value">{{ $money($p->total_amount) }}</span></div>
                <div class="d-row"><span class="d-label">Status</span><span class="d-value">{{ ucfirst(str_replace(['_', '-'], ' ', (string) $p->status)) }}</span></div>
                <div class="d-row"><span class="d-label">GCash reference</span><span class="d-value">{{ $p->gcash_reference ?? '—' }}</span></div>
            @else
                <div class="empty-mini">{{ $a->track === 'free_use' ? 'Free-use event — no payment.' : 'No payment recorded.' }}</div>
            @endif
        </div>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <div class="card-head"><div class="card-title">Assigned staff</div><span class="dropdown-chip">{{ $a->assignedStaff->count() }}</span></div>
        @forelse($a->assignedStaff as $s)
            <div class="d-row"><span class="d-value" style="text-align:left;">{{ trim($s->first_name . ' ' . $s->last_name) }}</span><span class="d-label">{{ $s->phone_number ?? '—' }}</span></div>
        @empty
            <div class="empty-mini">No staff were assigned to this event.</div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-head"><div class="card-title">Refunds</div><span class="dropdown-chip">{{ $a->refunds->count() }}</span></div>
        @forelse($a->refunds as $r)
            <div class="d-row">
                <span class="d-value" style="text-align:left;">{{ $money($r->refund_amount) }} <span class="sub">({{ $r->refund_percent }}% · {{ $r->reason_label }})</span></span>
                <span class="pill p-{{ $r->status }}">{{ ucfirst($r->status) }}</span>
            </div>
        @empty
            <div class="empty-mini">No refunds for this event.</div>
        @endforelse
    </div>
</div>

<div class="card" style="margin-top:18px;">
    <div class="card-head"><div class="card-title">Incidents & damage decisions</div><span class="dropdown-chip">{{ $a->incidentReports->count() }}</span></div>
    @forelse($a->incidentReports as $i)
        <div class="d-row" style="flex-direction:column;gap:6px;">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <strong>Incident #{{ $i->id }}</strong>
                <span class="pill p-{{ $i->status }}">{{ ucfirst(str_replace('_', ' ', $i->status)) }}</span>
            </div>
            <div class="sub" style="font-size:12.5px;">{{ $i->description }}</div>
            <div class="sub">
                Estimated cost: {{ $i->estimated_cost !== null ? $money($i->estimated_cost) : '—' }}
                · Decided: {{ $money($i->damageDecisions->sum('approved_amount')) }} across {{ $i->damageDecisions->count() }} {{ \Illuminate\Support\Str::plural('decision', $i->damageDecisions->count()) }}
            </div>
            <a class="no-print" style="color:var(--green-700);font-weight:700;font-size:12.5px;" href="{{ route('admin.ob.damaged-items.show', $i->id) }}">Open incident →</a>
        </div>
    @empty
        <div class="empty-mini">No incidents were reported for this event.</div>
    @endforelse
</div>
@endsection