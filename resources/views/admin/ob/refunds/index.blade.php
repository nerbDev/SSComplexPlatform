@extends('layouts.admin')

@section('title', 'Refunds')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="note-banner">
    Standard policy: a client who cancels on their own gets <strong>{{ $standardPolicy }}% refund</strong>. Refunds here are exceptions the OB Admin approves
    (SSC cancellation, force majeure, incident or overpayment). Payments are collected on-site, so "Released" records the voucher / OR number of the on-site payout.
</div>

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Pending approval</div><div class="val">{{ $counts['pending'] ?? 0 }}</div><div class="hint">₱{{ number_format($stats['pending_amount'], 2) }}</div></div>
    <div class="card ob-stat"><div class="lbl">Approved, not released</div><div class="val">{{ $counts['approved'] ?? 0 }}</div><div class="hint">₱{{ number_format($stats['approved_amount'], 2) }} to pay out</div></div>
    <div class="card ob-stat"><div class="lbl">Released</div><div class="val">{{ $counts['released'] ?? 0 }}</div><div class="hint">₱{{ number_format($stats['released_amount'], 2) }} paid back</div></div>
    <div class="card ob-stat"><div class="lbl">Rejected</div><div class="val">{{ $counts['rejected'] ?? 0 }}</div><div class="hint">Declined requests</div></div>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Refund requests</div>
        <button type="button" class="btn-sm b-primary" style="padding:8px 14px;" onclick="openNew()">+ New refund</button>
    </div>

    <div class="filter-bar">
        <div class="chips">
            <a class="chip {{ !$activeStatus ? 'active' : '' }}" href="{{ route('admin.ob.refunds.index', array_filter(['q' => $search])) }}">All</a>
            @foreach($statusLabels as $key => $label)
                <a class="chip {{ $activeStatus === $key ? 'active' : '' }}" href="{{ route('admin.ob.refunds.index', array_filter(['status' => $key, 'q' => $search])) }}">{{ $label }}<span class="n">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </div>
        <form class="filter-form" method="GET" action="{{ route('admin.ob.refunds.index') }}">
            @if($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
            <input class="f-input" type="search" name="q" value="{{ $search }}" placeholder="Search client or activity">
            <button class="f-btn" type="submit">Search</button>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead>
                <tr><th>#</th><th>Event / client</th><th>Reason</th><th class="num">Payment</th><th class="num">Refund</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['id'] }}</strong></td>
                        <td><strong>{{ $r['activity'] }}</strong><br><span class="sub">{{ $r['client'] }} · {{ $r['facility'] }}</span></td>
                        <td>{{ $r['reason_type'] }}</td>
                        <td class="num">₱{{ number_format($r['payment_total'], 2) }}</td>
                        <td class="num"><strong>₱{{ number_format($r['amount'], 2) }}</strong><br><span class="sub">{{ $r['percent'] }}%</span></td>
                        <td><span class="pill p-{{ $r['status'] }}">{{ $r['status_label'] }}</span></td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn-sm b-ghost" onclick="openDetails({{ $r['id'] }})">Details</button>
                                @if($r['status'] === 'pending')
                                    <button type="button" class="btn-sm b-primary" onclick="openAction({{ $r['id'] }}, 'approve')">Approve</button>
                                    <button type="button" class="btn-sm b-danger" onclick="openAction({{ $r['id'] }}, 'reject')">Reject</button>
                                @elseif($r['status'] === 'approved')
                                    <button type="button" class="btn-sm b-dark" onclick="openAction({{ $r['id'] }}, 'release')">Release</button>
                                @endif
                            </div>
                        </td>
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
            <div class="e-icon">💸</div>
            <div class="e-title">No refunds yet</div>
            <div class="e-sub">Open one from a cancelled appointment, a flagged damage report, or "+ New refund".</div>
        </div>
    @endif
</div>

{{-- New refund modal --}}
<div class="ap-overlay" id="newModal">
    <div class="ap-box">
        <div class="ap-title">New refund request</div>
        <div class="ap-text">Starts as pending; it needs approval before it can be released.</div>
        <form method="POST" action="{{ route('admin.ob.refunds.store') }}">
            @csrf
            @if(!empty($prefill['incident_report_id']))<input type="hidden" name="incident_report_id" value="{{ $prefill['incident_report_id'] }}">@endif
            <div class="fld">
                <label>Appointment (paid)</label>
                <select name="appointment_id" required>
                    <option value="">Select an appointment…</option>
                    @foreach($eligible as $e)<option value="{{ $e['id'] }}" @selected((string) ($prefill['appointment_id'] ?? old('appointment_id')) === (string) $e['id'])>{{ $e['label'] }}</option>@endforeach
                </select>
                <div class="hint">Only appointments with a recorded payment and no active refund are listed.</div>
            </div>
            <div class="fld"><label>Reason</label>
                <select name="reason_type" required>
                    @foreach($reasons as $key => $label)<option value="{{ $key }}" @selected(old('reason_type') === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="fld"><label>Refund percent of the payment</label><input type="number" name="refund_percent" min="1" max="100" value="{{ old('refund_percent', 100) }}" required></div>
            <div class="fld"><label>Details (optional)</label><textarea name="reason" maxlength="1000">{{ old('reason') }}</textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('newModal')">Cancel</button>
                <button type="submit" class="b-go">Create request</button>
            </div>
        </form>
    </div>
</div>

{{-- Approve / reject / release modal (filled by JS) --}}
<div class="ap-overlay" id="actionModal">
    <div class="ap-box">
        <div class="ap-title" id="actionTitle"></div>
        <div class="ap-text" id="actionText"></div>
        <form method="POST" id="actionForm">
            @csrf
            <div class="fld" id="refField" style="display:none;"><label>Release reference (voucher / OR no.)</label><input type="text" name="release_reference" id="refInput" maxlength="100"></div>
            <div class="fld" id="noteField" style="display:none;"><label id="noteLabel">Note</label><textarea name="note" id="noteInput" maxlength="1000"></textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('actionModal')">Cancel</button>
                <button type="submit" class="b-go" id="actionGo">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- Details modal (filled by JS) --}}
<div class="ap-overlay" id="detailsModal">
    <div class="ap-box">
        <div class="ap-title" id="detailsTitle">Refund</div>
        <div id="detailsBody"></div>
        <div class="ap-actions"><button type="button" class="b-close" onclick="closeModal('detailsModal')">Close</button></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const SHOW_NEW = @json(!empty($prefill['appointment_id']) || !empty($prefill['incident_report_id']));

    const esc = v => (v === null || v === undefined || v === '') ? '—' : String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    function openNew(){ openModal('newModal'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });
    if (SHOW_NEW) openNew();

    function openAction(id, action){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        const form = document.getElementById('actionForm');
        const refField = document.getElementById('refField'), noteField = document.getElementById('noteField');
        const refInput = document.getElementById('refInput'), noteInput = document.getElementById('noteInput');
        const go = document.getElementById('actionGo');

        refField.style.display = 'none'; noteField.style.display = 'none';
        refInput.required = false; noteInput.required = false; refInput.value = ''; noteInput.value = '';
        go.className = 'b-go';

        const who = `${r.activity} — ${r.client} (${money(r.amount)})`;

        if (action === 'approve') {
            document.getElementById('actionTitle').textContent = 'Approve this refund?';
            document.getElementById('actionText').textContent = who + '. It can be released once the client has been paid back on-site.';
            form.action = r.approve_url; go.textContent = 'Approve';
        } else if (action === 'reject') {
            document.getElementById('actionTitle').textContent = 'Reject this refund?';
            document.getElementById('actionText').textContent = who;
            noteField.style.display = ''; noteInput.required = true;
            document.getElementById('noteLabel').textContent = 'Reason for rejecting';
            form.action = r.reject_url; go.textContent = 'Reject'; go.className = 'b-red';
        } else {
            document.getElementById('actionTitle').textContent = 'Mark as released?';
            document.getElementById('actionText').textContent = who + '. Enter the on-site reference for the payout.';
            refField.style.display = ''; refInput.required = true;
            noteField.style.display = ''; document.getElementById('noteLabel').textContent = 'Note (optional)';
            form.action = r.release_url; go.textContent = 'Mark released';
        }
        openModal('actionModal');
    }

    function openDetails(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        const rows = [
            ['Event', r.activity], ['Client', r.client], ['Facility', r.facility], ['Event date', r.event_date],
            ['Reason', r.reason_type], ['Details', r.reason],
            ['Payment total', money(r.payment_total)], ['Refund', `${money(r.amount)} (${r.percent}%)`],
            ['Status', r.status_label],
            ['Requested by', r.requested_by], ['Requested on', r.requested_at],
            ['Decided by', r.decided_by], ['Decided on', r.decided_at], ['Decision note', r.decision_note],
            ['Release reference', r.release_ref], ['Released by', r.released_by], ['Released on', r.released_at],
        ];
        let html = rows.map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value">${esc(v)}</span></div>`).join('');
        if (r.incident_url) html += `<div style="margin-top:10px;"><a class="btn-sm b-ghost" href="${esc(r.incident_url)}">Open linked incident</a></div>`;
        document.getElementById('detailsTitle').textContent = 'Refund #' + r.id;
        document.getElementById('detailsBody').innerHTML = html;
        openModal('detailsModal');
    }
</script>
@endpush