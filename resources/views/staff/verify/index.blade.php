@extends('layouts.staff')

@section('title', 'Verify')

@push('styles')
<style>
    .verify-table{width:100%;border-collapse:collapse;}
    .verify-table th{text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;padding:9px 10px;border-bottom:1px solid var(--line);white-space:nowrap;}
    .verify-table td{padding:13px 10px;font-size:13px;border-bottom:1px solid var(--line);vertical-align:middle;}
    .verify-table tr:last-child td{border-bottom:none;}
    .unit-pill{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);}
    .type-pill{font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#eef2f0;color:var(--ink-600);white-space:nowrap;}
    .type-pill.free-use{background:var(--orange-100);color:#a85b1f;}

    .row-actions{display:flex;gap:6px;flex-wrap:wrap;}
    .row-actions button{
        font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;border:1px solid transparent;
        white-space:nowrap;
    }
    .btn-verify{background:var(--green-600);color:#fff;}
    .btn-verify:hover{background:var(--green-700);}
    .btn-reschedule{background:#fff;color:var(--orange-500);border-color:rgba(239,141,61,.4);}
    .btn-reschedule:hover{background:var(--orange-100);}
    .btn-cancel-row{background:#fff;color:#b4502c;border-color:#f4e4de;}
    .btn-cancel-row:hover{background:#f4e4de;}

    .details-btn{
        font-size:11.5px;font-weight:700;color:var(--green-700);background:none;border:none;cursor:pointer;
        text-decoration:underline;padding:0;
    }

    /* action confirm modal (shared) */
    .modal-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .modal-overlay.show{display:flex;}
    .modal-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:400px;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .modal-box .modal-title{font-size:16px;font-weight:800;margin-bottom:8px;}
    .modal-box .modal-text{font-size:13px;color:var(--ink-600);line-height:1.5;margin-bottom:16px;}
    .modal-box textarea{
        width:100%;min-height:70px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;
        font-family:inherit;font-size:13px;background:var(--green-050);margin-bottom:16px;resize:vertical;
    }
    .modal-actions{display:flex;gap:10px;}
    .modal-actions button{flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);}
    .btn-modal-cancel{background:#fff;color:var(--ink-900);}
    .btn-modal-confirm{color:#fff;border-color:transparent;}

    /* details modal */
    .detail-row{display:flex;justify-content:space-between;font-size:13px;padding:7px 0;border-bottom:1px solid var(--line);}
    .detail-row:last-child{border-bottom:none;}
    .detail-row .d-label{color:var(--ink-400);font-weight:600;}
    .detail-row .d-value{font-weight:700;text-align:right;}
</style>
@endpush

@section('content')

<div class="card">
    <div class="card-head">
        <div class="card-title">Pending Appointments & Reservations</div>
        <div class="dropdown-chip">{{ count($pending) }} waiting</div>
    </div>

    @if(!empty($pending))
        <div class="table-scroll">
        <table class="verify-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Activity</th>
                    <th>Facility</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $row)
                    <tr>
                        <td><strong>{{ $row['client_name'] }}</strong><br><span class="muted" style="font-size:11.5px;">{{ $row['contact_number'] }}</span></td>
                        <td>{{ $row['activity'] }}</td>
                        <td><span class="unit-pill">{{ $row['facility'] }}</span></td>
                        <td class="muted">{{ $row['date_time'] }}</td>
                        <td><span class="type-pill {{ $row['track'] === 'free_use' ? 'free-use' : '' }}">{{ $row['type_label'] }}</span></td>
                        <td><button type="button" class="details-btn" onclick="showDetails({{ $row['id'] }})">Details</button></td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn-verify" onclick="confirmAction({{ $row['id'] }}, 'verify')">Verify</button>
                                <button type="button" class="btn-reschedule" onclick="confirmAction({{ $row['id'] }}, 'reschedule')">Reschedule</button>
                                <button type="button" class="btn-cancel-row" onclick="confirmAction({{ $row['id'] }}, 'cancel')">Cancel</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">✔</div>
            <div class="e-title">Nothing pending</div>
            <div class="e-sub">New appointment and reservation requests will show up here for review.</div>
        </div>
    @endif
</div>

{{-- ---------- action confirm modal (shared: Verify / Reschedule / Cancel) ---------- --}}
<div class="modal-overlay" id="actionModal">
    <div class="modal-box">
        <div class="modal-title" id="actionModalTitle">Confirm</div>
        <div class="modal-text" id="actionModalText"></div>

        <form method="POST" id="actionForm">
            @csrf
            <textarea name="note" id="actionNote" placeholder="Optional note..." style="display:none;"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('actionModal')">Never mind</button>
                <button type="submit" class="btn-modal-confirm" id="actionConfirmBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- ---------- details modal ---------- --}}
<div class="modal-overlay" id="detailsModal">
    <div class="modal-box">
        <div class="modal-title">Request Details</div>
        <div id="detailsBody"></div>
        <div class="modal-actions" style="margin-top:18px;">
            <button type="button" class="btn-modal-cancel" onclick="closeModal('detailsModal')">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const PENDING = @json($pending);
    const ROUTES = {
        verify:     @json(url('/staff/verify')),
    };

    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(o => o.classList.remove('show')); });

    const ACTION_CONFIG = {
        verify:     { title: 'Verify this request?',   text: 'This moves it forward so the client can pay (or attach their approval form, for free-use).', color: 'var(--green-600)', label: 'Verify', note: false },
        reschedule: { title: 'Mark as rescheduled?',    text: 'The client will need to be informed of the new date separately — this only updates the status for now.', color: 'var(--orange-500)', label: 'Mark Rescheduled', note: true },
        cancel:     { title: 'Cancel this request?',    text: 'This cannot be undone from this screen.', color: '#b4502c', label: 'Cancel Request', note: true },
    };

    function confirmAction(id, action) {
        const cfg = ACTION_CONFIG[action];
        document.getElementById('actionModalTitle').textContent = cfg.title;
        document.getElementById('actionModalText').textContent = cfg.text;
        document.getElementById('actionConfirmBtn').textContent = cfg.label;
        document.getElementById('actionConfirmBtn').style.background = cfg.color;
        document.getElementById('actionNote').style.display = cfg.note ? 'block' : 'none';
        document.getElementById('actionForm').action = `${ROUTES.verify}/${id}/${action}`;
        openModal('actionModal');
    }

    function showDetails(id) {
        const row = PENDING.find(r => r.id === id);
        if (!row) return;

        const fields = [
            ['Client', row.client_name],
            ['Contact', row.contact_number],
            ['Barangay', row.barangay],
            ['Activity', row.activity],
            ['Facility', row.facility],
            ['Date & Time', row.date_time],
            ['Type', row.type_label],
            ['Expected Attendees', row.expected_attendees ?? '—'],
        ];

        document.getElementById('detailsBody').innerHTML = fields.map(([label, value]) =>
            `<div class="detail-row"><span class="d-label">${label}</span><span class="d-value">${value ?? '—'}</span></div>`
        ).join('');

        openModal('detailsModal');
    }
</script>
@endpush