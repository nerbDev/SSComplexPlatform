@extends('layouts.admin')

@section('title', 'Assign Staff')

@push('styles')
<style>
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;}
    .flash.ok{background:var(--green-100);color:var(--green-800);border:1px solid #cdeedd;}
    .flash.err{background:#f4e4de;color:#b4502c;border:1px solid #ecd2c8;}

    .back-link{display:inline-block;font-size:13px;font-weight:700;color:var(--green-700);margin-bottom:14px;}

    .summary{margin-bottom:18px;}
    .summary-title{font-size:18px;font-weight:800;letter-spacing:-.2px;margin-bottom:12px;}
    .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;}
    .summary-grid .lbl{font-size:11px;font-weight:700;color:var(--ink-400);margin-bottom:3px;}
    .summary-grid .val{font-size:13.5px;font-weight:700;}

    .staff-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;}
    @media (max-width: 900px){ .staff-grid{grid-template-columns:1fr;} }

    .count-pill{font-size:12px;font-weight:700;color:var(--ink-600);background:var(--green-050);border:1px solid var(--line);padding:5px 10px;border-radius:9px;}

    .staff-row{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--line);}
    .staff-row:last-child{border-bottom:none;}
    .s-avatar{width:34px;height:34px;border-radius:10px;background:var(--green-100);color:var(--green-700);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
    .s-info{flex:1;min-width:0;}
    .s-name{font-size:13.5px;font-weight:700;}
    .s-sub{font-size:11.5px;color:var(--ink-400);font-weight:600;}
    .staff-row.is-busy{opacity:.6;}
    .staff-row.is-busy .s-avatar{background:#eef2f0;color:var(--ink-600);}

    .btn-assign,.btn-unassign{font-size:12px;font-weight:700;padding:7px 14px;border-radius:999px;cursor:pointer;border:1px solid transparent;white-space:nowrap;}
    .btn-assign{background:var(--green-600);color:#fff;}
    .btn-assign:hover{background:var(--green-700);}
    .btn-unassign{background:#fff;color:#b4502c;border-color:#f4e4de;}
    .btn-unassign:hover{background:#f4e4de;}

    .find{width:100%;padding:9px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13px;background:var(--green-050);margin-bottom:6px;}
    .sub-head{font-size:12px;font-weight:800;color:var(--ink-600);margin:18px 0 2px;}

    .ap-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .ap-overlay.show{display:flex;}
    .ap-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:400px;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .ap-title{font-size:16px;font-weight:800;margin-bottom:8px;}
    .ap-text{font-size:13px;color:var(--ink-600);line-height:1.5;margin-bottom:18px;}
    .ap-actions{display:flex;gap:10px;}
    .ap-actions button{flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);}
    .ap-actions .b-close{background:#fff;color:var(--ink-900);}
    .ap-actions .b-danger{background:#b4502c;color:#fff;border-color:transparent;}
</style>
@endpush

@section('content')

@php
    $initials = fn($u) => strtoupper(substr($u->first_name ?? '', 0, 1) . substr($u->last_name ?? '', 0, 1)) ?: '—';
    $fullName = fn($u) => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: 'Staff #' . $u->id;
@endphp

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif

<a class="back-link" href="{{ route('admin.appointments.scheduled') }}">← Back to scheduled appointments</a>

<div class="card summary">
    <div class="summary-title">{{ $appointment->activity_title }}</div>
    <div class="summary-grid">
        <div><div class="lbl">Client</div><div class="val">{{ trim(($appointment->client->first_name ?? '') . ' ' . ($appointment->client->last_name ?? '')) ?: '—' }}</div></div>
        <div><div class="lbl">Facility</div><div class="val">{{ $appointment->facility->name ?? '—' }}</div></div>
        <div><div class="lbl">Date</div><div class="val">{{ $when['date'] }}</div></div>
        <div><div class="lbl">Time</div><div class="val">{{ $when['time'] }}</div></div>
        <div><div class="lbl">Expected attendees</div><div class="val">{{ $appointment->expected_attendees ?? '—' }}</div></div>
    </div>
</div>

<div class="staff-grid">
    {{-- Assigned --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">Assigned staff</div>
            <span class="count-pill">{{ $assigned->count() }}</span>
        </div>

        @forelse($assigned as $u)
            <div class="staff-row">
                <div class="s-avatar">{{ $initials($u) }}</div>
                <div class="s-info">
                    <div class="s-name">{{ $fullName($u) }}</div>
                    <div class="s-sub">{{ $u->phone_number ?? 'No contact number' }}</div>
                </div>
                <button type="button" class="btn-unassign" onclick="openCancel({{ $u->id }}, @js($fullName($u)))">Cancel</button>
            </div>
        @empty
            <div class="empty-state">
                <div class="e-icon">👥</div>
                <div class="e-title">No staff assigned yet</div>
                <div class="e-sub">Pick from the available staff to add them to this appointment.</div>
            </div>
        @endforelse
    </div>

    {{-- Available --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">Available staff</div>
            <span class="count-pill">{{ $available->count() }}</span>
        </div>

        @if($available->count() > 3)
            <input type="search" class="find" id="staffFind" placeholder="Search staff name">
        @endif

        <div id="availableList">
            @forelse($available as $u)
                <div class="staff-row" data-name="{{ strtolower($fullName($u)) }}">
                    <div class="s-avatar">{{ $initials($u) }}</div>
                    <div class="s-info">
                        <div class="s-name">{{ $fullName($u) }}</div>
                        <div class="s-sub">{{ $u->phone_number ?? 'No contact number' }}</div>
                    </div>
                    <form method="POST" action="{{ route('admin.appointments.staff.assign', ['appointment' => $appointment->id, 'staff' => $u->id]) }}">
                        @csrf
                        <button type="submit" class="btn-assign">Assign</button>
                    </form>
                </div>
            @empty
                <div class="empty-state">
                    <div class="e-icon">🙌</div>
                    <div class="e-title">No available staff</div>
                    <div class="e-sub">Everyone is either assigned here or busy with another event at this time.</div>
                </div>
            @endforelse
        </div>

        @if($unavailable->count())
            <div class="sub-head">Busy at this time</div>
            @foreach($unavailable as $u)
                <div class="staff-row is-busy">
                    <div class="s-avatar">{{ $initials($u) }}</div>
                    <div class="s-info">
                        <div class="s-name">{{ $fullName($u) }}</div>
                        <div class="s-sub">Assigned to: {{ $busy[$u->id] }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Cancel assignment modal --}}
<div class="ap-overlay" id="cancelModal">
    <div class="ap-box">
        <div class="ap-title">Cancel this assignment?</div>
        <div class="ap-text" id="cancelText"></div>
        <form method="POST" id="cancelForm">
            @csrf
            @method('DELETE')
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeCancel()">Keep assigned</button>
                <button type="submit" class="b-danger">Cancel assignment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const BASE = @json(url('/admin/appointments/' . $appointment->id . '/staff'));
    const modal = document.getElementById('cancelModal');

    function openCancel(staffId, name){
        document.getElementById('cancelText').textContent =
            name + ' will be removed from this appointment and will show as available again.';
        document.getElementById('cancelForm').action = BASE + '/' + staffId;
        modal.classList.add('show');
    }
    function closeCancel(){ modal.classList.remove('show'); }
    modal.addEventListener('click', e => { if (e.target === modal) closeCancel(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCancel(); });

    const find = document.getElementById('staffFind');
    if (find) {
        find.addEventListener('input', () => {
            const q = find.value.trim().toLowerCase();
            document.querySelectorAll('#availableList .staff-row').forEach(r => {
                r.style.display = r.dataset.name.includes(q) ? '' : 'none';
            });
        });
    }
</script>
@endpush