@extends('layouts.admin')

@section('title', 'Staff Accounts')

@push('styles')
<style>
    .status-note{background:var(--green-100);color:var(--green-700);border:1px solid var(--green-500);border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;margin-bottom:18px;}
    .toolbar{display:flex;justify-content:flex-end;margin-bottom:18px;}
    .staff-table{width:100%;border-collapse:collapse;}
    .staff-table th{text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;padding:9px 10px;border-bottom:1px solid var(--line);}
    .staff-table td{padding:12px 10px;font-size:13px;border-bottom:1px solid var(--line);}
    .staff-table tr:last-child td{border-bottom:none;}
    .staff-name{display:flex;align-items:center;gap:10px;font-weight:700;}
    .staff-avatar{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--green-500),var(--green-700));flex-shrink:0;}

    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
    .form-grid .full{grid-column:1 / -1;}
    .form-field label{display:block;font-size:12px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .form-field input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;font-family:inherit;font-size:13px;background:var(--green-050);}
    .field-error{color:var(--orange-500);font-size:11px;font-weight:700;margin-top:4px;}
</style>
@endpush

@section('content')

@if(session('status'))
    <div class="status-note">{{ session('status') }}</div>
@endif

<div class="toolbar">
    <button type="button" class="btn-primary" onclick="openModal('addStaffModal')">+ Add Staff</button>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Staff Accounts</div>
        <div class="dropdown-chip">{{ $staff->count() }} total</div>
    </div>

    @if($staff->isEmpty())
        <div class="empty-state">
            <div class="e-icon">👤</div>
            <div class="e-title">No staff accounts yet</div>
            <div class="e-sub">Add one and they'll be able to log in and use the Verify module.</div>
        </div>
    @else
        <div class="table-scroll">
        <table class="staff-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th></tr>
            </thead>
            <tbody>
                @foreach($staff as $member)
                    <tr>
                        <td><div class="staff-name"><span class="staff-avatar"></span>{{ $member->first_name }} {{ $member->last_name }}</div></td>
                        <td class="muted">{{ $member->email }}</td>
                        <td class="muted">{{ $member->phone_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>

<div class="modal-overlay" id="addStaffModal">
    <div class="modal-box form-box" style="max-width:460px;text-align:left;">
        <div class="modal-icon">👤</div>
        <div class="modal-title">Add a staff account</div>
        <div class="modal-sub" style="font-size:12.5px;color:var(--ink-400);margin-bottom:18px;">They'll use this email + password to log in and land on the Staff Dashboard.</div>

        <form method="POST" action="{{ route('admin.staff.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field full">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field full">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}">
                </div>
                <div class="form-field">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('addStaffModal')">Cancel</button>
                <button type="submit" class="btn-logout" style="background:var(--green-600);border-color:var(--green-600);">Create Account</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id){ document.getElementById(id).classList.add('show'); }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
    document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    @if($errors->any())
        openModal('addStaffModal');
    @endif
</script>
@endpush