@extends('layouts.admin')

@section('title', 'Cancellations')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="note-banner">
    Standard policy: clients who cancel on their own get <strong>{{ $standardPolicy }}% refund</strong>. Use the Active tab to cancel on a client's behalf
    and, only when SSC is at fault or for force majeure, open an approved refund at the same time.
</div>

<div class="view-tabs">
    <a class="view-tab {{ $tab === 'cancelled' ? 'active' : '' }}" href="{{ route('admin.ob.cancellations.index') }}">Cancelled <span class="n">{{ $counts['cancelled'] }}</span></a>
    <a class="view-tab {{ $tab === 'active' ? 'active' : '' }}" href="{{ route('admin.ob.cancellations.index', ['tab' => 'active']) }}">Active (can be cancelled) <span class="n">{{ $counts['active'] }}</span></a>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $tab === 'cancelled' ? 'Cancelled appointments' : 'Active appointments' }}</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('appointment', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <div></div>
        <form class="filter-form" method="GET" action="{{ route('admin.ob.cancellations.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input class="f-input" type="search" name="q" value="{{ $search }}" placeholder="Search client or activity">
            <button class="f-btn" type="submit">Search</button>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead>
                @if($tab === 'cancelled')
                    <tr><th>Event / client</th><th>Date</th><th>Cancelled</th><th>Reason</th><th class="num">Paid</th><th>Refund</th><th></th></tr>
                @else
                    <tr><th>Event / client</th><th>Facility</th><th>Date & time</th><th>Status</th><th class="num">Paid</th><th></th></tr>
                @endif
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['activity'] }}</strong><br><span class="sub">{{ $r['client'] }}</span></td>
                        @if($tab === 'cancelled')
                            <td>{{ $r['date'] }}<br><span class="sub">{{ $r['facility'] }}</span></td>
                            <td>{{ $r['cancelled_at'] ?? '—' }}<br><span class="sub">by {{ $r['cancelled_role'] === 'client' ? 'client' : ($r['cancelled_by'] ?: ($r['cancelled_role'] ?: 'unknown')) }}</span></td>
                            <td><div class="trunc" title="{{ $r['reason'] }}">{{ $r['reason'] ?: '—' }}</div></td>
                            <td class="num">{{ $r['has_payment'] ? '₱' . number_format($r['payment_total'], 2) : '—' }}</td>
                            <td>
                                @if($r['refund_status'])
                                    <span class="pill p-{{ $r['refund_status'] }}">{{ ucfirst($r['refund_status']) }}</span><br><span class="sub">₱{{ number_format($r['refund_amount'], 2) }}</span>
                                @else
                                    <span class="sub">{{ $r['has_payment'] ? 'No refund' : '—' }}</span>
                                @endif
                            </td>
                            <td>@if($r['can_open_refund'])<a class="btn-sm b-ghost" href="{{ route('admin.ob.refunds.index', ['appointment_id' => $r['id']]) }}">Open refund</a>@endif</td>
                        @else
                            <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                            <td>{{ $r['date'] }}<br><span class="sub">{{ $r['time'] }}</span></td>
                            <td><span class="pill p-{{ $r['status'] === 'scheduled' ? 'scheduled' : 'gray' }}">{{ $r['status_label'] }}</span></td>
                            <td class="num">{{ $r['has_payment'] ? '₱' . number_format($r['payment_total'], 2) : '—' }}</td>
                            <td><button type="button" class="btn-sm b-danger" onclick="openCancel({{ $r['id'] }})">Cancel</button></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="pager">
            <div>Page {{ $page->currentPage() }} of {{ $page->lastPage() }}</div>
            <div style="display:flex;gap:8px;">
                @if($page->previousPageUrl())<a href="{{ $page->previousPageUrl() }}">Previous</a>@else<span class="off">Previous</span>@endif
                @if($page->nextPageUrl())<a href="{{ $page->nextPageUrl() }}">Next</a>@else<span class="off">Next</span>@endif
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">🗂</div>
            <div class="e-title">{{ $tab === 'cancelled' ? 'No cancellations yet' : 'No active appointments' }}</div>
            <div class="e-sub">{{ $tab === 'cancelled' ? 'Cancelled appointments (by clients or by you) will be listed here.' : 'Nothing is open to cancel right now.' }}</div>
        </div>
    @endif
</div>

{{-- Cancel on behalf modal --}}
<div class="ap-overlay" id="cancelModal">
    <div class="ap-box">
        <div class="ap-title">Cancel this appointment?</div>
        <div class="ap-text" id="cancelWhich"></div>
        <form method="POST" id="cancelForm">
            @csrf
            <div class="fld"><label>Reason (required)</label><textarea name="reason" required maxlength="500"></textarea></div>

            <div id="refundBlock">
                <label class="check-row"><input type="checkbox" name="open_refund" value="1" id="openRefund"> <span>Also open an approved refund (SSC at fault / force majeure)</span></label>
                <div id="refundFields" style="display:none;">
                    <div class="fld-row">
                        <div class="fld"><label>Refund reason</label>
                            <select name="refund_reason_type">
                                <option value="ssc_cancellation">{{ $reasons['ssc_cancellation'] }}</option>
                                <option value="force_majeure">{{ $reasons['force_majeure'] }}</option>
                            </select>
                        </div>
                        <div class="fld"><label>Refund percent</label><input type="number" name="refund_percent" min="1" max="100" value="100"></div>
                    </div>
                    <div class="hint" id="refundHint" style="font-size:11.5px;font-weight:600;color:var(--ink-600);margin-bottom:12px;"></div>
                </div>
            </div>
            <div class="sub" id="noPayNote" style="display:none;margin-bottom:12px;">This appointment has no recorded payment, so there is nothing to refund.</div>

            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('cancelModal')">Keep appointment</button>
                <button type="submit" class="b-red">Cancel appointment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const money = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    const openRefund = document.getElementById('openRefund');
    const refundFields = document.getElementById('refundFields');
    const percentInput = refundFields.querySelector('[name=refund_percent]');
    const hint = document.getElementById('refundHint');
    let currentTotal = 0;

    function updateHint(){
        const p = parseInt(percentInput.value, 10) || 0;
        hint.textContent = currentTotal ? `Refund amount: ${money(currentTotal * Math.min(Math.max(p, 0), 100) / 100)} of ${money(currentTotal)} paid.` : '';
    }
    openRefund.addEventListener('change', () => { refundFields.style.display = openRefund.checked ? '' : 'none'; });
    percentInput.addEventListener('input', updateHint);

    function openCancel(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        document.getElementById('cancelWhich').textContent = `${r.activity} — ${r.client} · ${r.date}, ${r.time}`;
        document.getElementById('cancelForm').action = r.cancel_url;

        openRefund.checked = false; refundFields.style.display = 'none';
        document.getElementById('refundBlock').style.display = r.has_payment ? '' : 'none';
        document.getElementById('noPayNote').style.display = r.has_payment ? 'none' : '';
        currentTotal = r.has_payment ? r.payment_total : 0;
        percentInput.value = 100; updateHint();

        document.getElementById('cancelModal').classList.add('show');
    }
</script>
@endpush