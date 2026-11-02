@extends('layouts.admin')

@section('title', 'Damage Report #' . $incident->id)

@push('styles')
    @include('admin.ob._styles')
@endpush

@php
    $money = fn($v) => '₱' . number_format((float) $v, 2);
    $a = $incident->appointment;
    $reporter = $incident->reportedBy ? trim($incident->reportedBy->first_name . ' ' . $incident->reportedBy->last_name) : '—';
@endphp

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<a class="back-link" href="{{ route('admin.ob.damaged-items.index') }}">← Back to damaged items</a>

<div class="two-col">
    <div class="stack">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Incident #{{ $incident->id }}</div>
                <span class="pill p-{{ $incident->status }}">{{ $statusLabel }}</span>
            </div>

            <div class="d-row"><span class="d-label">Facility</span><span class="d-value">{{ $incident->facility->name ?? '—' }}</span></div>
            <div class="d-row"><span class="d-label">Event</span><span class="d-value">{{ $a ? $a->activity_title : '—' }}</span></div>
            @if($a)
                <div class="d-row"><span class="d-label">Client</span><span class="d-value">{{ trim(($a->client->first_name ?? '') . ' ' . ($a->client->last_name ?? '')) ?: '—' }}</span></div>
                <div class="d-row"><span class="d-label">Event date</span><span class="d-value">{{ $a->event_date ? $a->event_date->format('M j, Y') : '—' }}</span></div>
            @endif
            <div class="d-row"><span class="d-label">Reported by</span><span class="d-value">{{ $reporter }}</span></div>
            <div class="d-row"><span class="d-label">Reported on</span><span class="d-value">{{ $incident->created_at ? $incident->created_at->format('M j, Y g:i A') : '—' }}</span></div>
            <div class="d-row"><span class="d-label">Staff cost estimate</span><span class="d-value">{{ $incident->estimated_cost !== null ? $money($incident->estimated_cost) : '—' }}</span></div>

            <div class="section-h">What happened</div>
            <div style="font-size:13px;line-height:1.55;">{{ $incident->description }}</div>

            @if($incident->damaged_items)
                <div class="section-h">Damaged items (as reported)</div>
                <div style="font-size:13px;line-height:1.55;white-space:pre-line;">{{ $incident->damaged_items }}</div>
            @endif

            @if($incident->photo_path)
                <div class="section-h">Photo</div>
                <a class="btn-sm b-ghost" href="{{ asset('storage/' . ltrim($incident->photo_path, '/')) }}" target="_blank" rel="noopener">View photo</a>
            @endif

            @if($incident->status === 'resolved')
                <div class="section-h">Resolution</div>
                <div style="font-size:13px;line-height:1.55;">{{ $incident->resolution_notes }}</div>
                <div class="sub" style="margin-top:6px;">
                    Resolved {{ $incident->reviewed_at ? $incident->reviewed_at->format('M j, Y g:i A') : '' }}
                    @if($incident->reviewedBy) by {{ trim($incident->reviewedBy->first_name . ' ' . $incident->reviewedBy->last_name) }}@endif
                </div>
            @endif
        </div>

        @if($incident->refund_related)
            <div class="card">
                <div class="card-head"><div class="card-title">Refund</div><span class="pill p-warn">Flagged by staff</span></div>
                @if($activeRefund)
                    <div class="d-row"><span class="d-label">Refund #{{ $activeRefund->id }}</span><span class="d-value">{{ $money($activeRefund->refund_amount) }} · <span class="pill p-{{ $activeRefund->status }}">{{ ucfirst($activeRefund->status) }}</span></span></div>
                    <div style="margin-top:10px;"><a class="btn-sm b-ghost" href="{{ route('admin.ob.refunds.index') }}">Go to refunds</a></div>
                @elseif($canOpenRefund)
                    <div class="empty-mini">This incident was flagged as refund related. Open a refund request tied to it.</div>
                    <button type="button" class="btn-sm b-primary" onclick="document.getElementById('refundModal').classList.add('show')">Open refund</button>
                @else
                    <div class="empty-mini">{{ $a ? 'This event has no recorded payment to refund.' : 'This incident isn\'t linked to an event, so there is no payment to refund.' }}</div>
                @endif
            </div>
        @endif
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Decisions</div>
                <span class="dropdown-chip">{{ $decisions->count() }} · {{ $money($decidedTotal) }}</span>
            </div>

            @forelse($decisions as $d)
                <div class="d-row" style="flex-direction:column;gap:5px;">
                    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
                        <strong>{{ $d->decision_label }}</strong>
                        <span class="d-value">{{ $d->approved_amount !== null ? $money($d->approved_amount) : '—' }}</span>
                    </div>
                    <div class="sub" style="font-size:12.5px;">
                        {{ $d->equipment->item_name ?? $d->item_label ?? 'No specific item' }}@if($d->quantity_affected) × {{ $d->quantity_affected }}@endif
                    </div>
                    @if($d->notes)<div class="sub">{{ $d->notes }}</div>@endif
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="sub">{{ $d->decidedBy ? trim($d->decidedBy->first_name . ' ' . $d->decidedBy->last_name) : '—' }} · {{ $d->created_at ? $d->created_at->format('M j, g:i A') : '' }}</span>
                        @if(!$locked)
                            <form method="POST" action="{{ route('admin.ob.damaged-items.decisions.destroy', ['incident' => $incident->id, 'decision' => $d->id]) }}" onsubmit="return confirm('Remove this decision?');">
                                @csrf @method('DELETE')
                                <button class="btn-sm b-danger" type="submit">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-mini">No decisions recorded yet.</div>
            @endforelse
        </div>

        @unless($locked)
            <div class="card">
                <div class="card-head"><div class="card-title">Add a decision</div></div>
                <form method="POST" action="{{ route('admin.ob.damaged-items.decisions.store', $incident->id) }}">
                    @csrf
                    <div class="fld">
                        <label>Decision</label>
                        <select name="decision" id="decisionType" required>
                            @foreach($decisionTypes as $key => $label)<option value="{{ $key }}" @selected(old('decision') === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="fld">
                        <label>Inventory item (optional)</label>
                        <select name="equipment_inventory_id">
                            <option value="">— not in inventory / type below —</option>
                            @foreach($equipment as $e)<option value="{{ $e->id }}" @selected((string) old('equipment_inventory_id') === (string) $e->id)>{{ $e->item_name }}{{ $e->facility_id ? '' : ' (shared)' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="fld">
                        <label>Item name (if not in the inventory)</label>
                        <input type="text" name="item_label" value="{{ old('item_label') }}" maxlength="150" placeholder="e.g. Folding chairs">
                        @error('item_label')<div class="err">{{ $message }}</div>@enderror
                    </div>
                    <div class="fld-row">
                        <div class="fld"><label>Quantity affected</label><input type="number" name="quantity_affected" min="1" value="{{ old('quantity_affected') }}"></div>
                        <div class="fld"><label>Amount (₱)</label><input type="number" name="approved_amount" min="0" step="0.01" value="{{ old('approved_amount') }}" placeholder="0.00">
                            <div class="hint" id="amountHint">Required when charging the client.</div>
                            @error('approved_amount')<div class="err">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="fld"><label>Notes (optional)</label><textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea></div>
                    <button type="submit" class="btn-sm b-primary" style="padding:10px 18px;">Record decision</button>
                </form>
            </div>

            <div class="card">
                <div class="card-head"><div class="card-title">Review status</div></div>
                @if($incident->status === 'open')
                    <form method="POST" action="{{ route('admin.ob.damaged-items.review', $incident->id) }}" style="margin-bottom:10px;">
                        @csrf <button class="btn-sm b-dark" type="submit">Start review</button>
                    </form>
                @endif
                <button type="button" class="btn-sm b-primary" style="padding:10px 18px;" onclick="document.getElementById('resolveModal').classList.add('show')">Resolve report</button>
                <div class="sub" style="margin-top:8px;">Resolving needs at least one decision (use "No damage found" if nothing was damaged).</div>
            </div>
        @else
            <div class="card">
                <div class="card-head"><div class="card-title">This report is resolved</div></div>
                <form method="POST" action="{{ route('admin.ob.damaged-items.reopen', $incident->id) }}">
                    @csrf <button class="btn-sm b-ghost" type="submit">Reopen to edit decisions</button>
                </form>
            </div>
        @endunless
    </div>
</div>

{{-- Resolve modal --}}
<div class="ap-overlay" id="resolveModal">
    <div class="ap-box">
        <div class="ap-title">Resolve this report?</div>
        <div class="ap-text">Summarise the outcome. Decisions are locked until you reopen the report.</div>
        <form method="POST" action="{{ route('admin.ob.damaged-items.resolve', $incident->id) }}">
            @csrf
            <div class="fld"><label>Resolution notes</label><textarea name="resolution_notes" required maxlength="2000">{{ old('resolution_notes') }}</textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="document.getElementById('resolveModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="b-go">Resolve</button>
            </div>
        </form>
    </div>
</div>

{{-- Open refund modal --}}
@if($incident->refund_related && $canOpenRefund)
<div class="ap-overlay" id="refundModal">
    <div class="ap-box">
        <div class="ap-title">Open a refund</div>
        <div class="ap-text">Payment on file: {{ $money($a->payment->total_amount) }}. The request starts as pending and still needs approval.</div>
        <form method="POST" action="{{ route('admin.ob.refunds.store') }}">
            @csrf
            <input type="hidden" name="appointment_id" value="{{ $a->id }}">
            <input type="hidden" name="incident_report_id" value="{{ $incident->id }}">
            <input type="hidden" name="reason_type" value="incident">
            <div class="fld"><label>Refund percent of the payment</label><input type="number" name="refund_percent" min="1" max="100" value="100" required></div>
            <div class="fld"><label>Reason</label><textarea name="reason" maxlength="1000">Related to incident #{{ $incident->id }}</textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="document.getElementById('refundModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="b-go">Create refund request</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    const type = document.getElementById('decisionType');
    const hint = document.getElementById('amountHint');
    if (type && hint) {
        const hints = {
            charge_client: 'Required — the amount the client will be charged.',
            repair: 'Approved repair cost (optional).',
            replace: 'Approved replacement cost (optional).',
            waive: 'Leave empty — nothing is charged.',
            no_damage: 'Leave empty — nothing was damaged.'
        };
        const sync = () => { hint.textContent = hints[type.value] || ''; };
        type.addEventListener('change', sync); sync();
    }
</script>
@endpush