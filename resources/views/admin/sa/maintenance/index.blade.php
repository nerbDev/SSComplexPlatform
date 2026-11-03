@extends('layouts.admin')

@section('title', 'Maintenance')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Due this week</div><div class="val">{{ $stats['this_week'] }}</div><div class="hint">Scheduled or in progress</div></div>
    <div class="card ob-stat"><div class="lbl">In progress</div><div class="val">{{ $stats['in_progress'] }}</div><div class="hint">Work currently underway</div></div>
    <div class="card ob-stat"><div class="lbl">Overdue</div><div class="val">{{ $stats['overdue'] }}</div><div class="hint">Scheduled date already passed</div></div>
    <div class="card ob-stat"><div class="lbl">Completed this month</div><div class="val">{{ $stats['done_month'] }}</div><div class="hint">Marked done</div></div>
</div>

<div class="view-tabs">
    <a class="view-tab {{ $tab === 'upcoming' ? 'active' : '' }}" href="{{ route('admin.sa.maintenance.index') }}">Upcoming <span class="n">{{ $counts['upcoming'] }}</span></a>
    <a class="view-tab {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('admin.sa.maintenance.index', ['tab' => 'history']) }}">History <span class="n">{{ $counts['history'] }}</span></a>
    <a class="view-tab {{ $tab === 'issues' ? 'active' : '' }}" href="{{ route('admin.sa.maintenance.index', ['tab' => 'issues']) }}">Reported issues <span class="n">{{ $counts['issues'] }}@if($counts['issues_high']) · {{ $counts['issues_high'] }} high @endif</span></a>
</div>

@if($tab === 'issues')
<div class="card">
    <div class="card-head">
        <div class="card-title">Issues reported by staff</div>
        <div class="dropdown-chip">{{ $page->total() }} {{ \Illuminate\Support\Str::plural('issue', $page->total()) }}</div>
    </div>

    <div class="filter-bar">
        <div class="chips">
            @foreach(['active' => 'Active', 'open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'all' => 'All'] as $key => $label)
                <a class="chip {{ $issueStatus === $key ? 'active' : '' }}" href="{{ route('admin.sa.maintenance.index', array_filter(['tab' => 'issues', 'status' => $key, 'severity' => $issueSeverity, 'facility_id' => $facilityId, 'q' => $search])) }}">{{ $label }}</a>
            @endforeach
        </div>
        <form class="filter-form" method="GET" action="{{ route('admin.sa.maintenance.index') }}">
            <input type="hidden" name="tab" value="issues">
            <input type="hidden" name="status" value="{{ $issueStatus }}">
            <div class="f"><label>Severity</label>
                <select class="f-input" name="severity">
                    <option value="">Any</option>
                    @foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $k => $l)<option value="{{ $k }}" @selected($issueSeverity === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $facilityId === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $search }}" placeholder="Title or details"></div>
            <button class="f-btn" type="submit">Apply</button>
        </form>
    </div>

    @if(count($issueRows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead><tr><th>Reported</th><th>Facility</th><th>Issue</th><th>Reported by</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($issueRows as $i)
                    <tr>
                        <td>{{ $i['reported_at'] }}</td>
                        <td><span class="unit-pill">{{ $i['facility'] }}</span></td>
                        <td>
                            <strong>{{ $i['title'] }}</strong>
                            <span class="pill {{ ['high' => 'p-bad', 'medium' => 'p-warn', 'low' => 'p-gray'][$i['severity']] ?? 'p-gray' }}" style="margin-left:4px;">{{ ucfirst($i['severity']) }}</span>
                            <div class="trunc sub" title="{{ $i['description'] }}">{{ $i['description'] }}</div>
                            @if($i['schedule_id'])<span class="pill p-under_review" style="margin-top:3px;">Job scheduled</span>@endif
                        </td>
                        <td>{{ $i['reporter'] }}</td>
                        <td><span class="pill p-{{ ['open' => 'open', 'in_progress' => 'under_review', 'resolved' => 'resolved'][$i['status']] ?? 'gray' }}">{{ $i['status_label'] }}</span></td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn-sm b-ghost" onclick="openIssue({{ $i['id'] }})">Details</button>
                                @if($i['status'] !== 'resolved')
                                    @if(!$i['schedule_id'])<button type="button" class="btn-sm b-primary" onclick="scheduleFromIssue({{ $i['id'] }})">Schedule</button>@endif
                                    @if($i['status'] === 'open')
                                        <form method="POST" action="{{ $i['progress_url'] }}">@csrf<button class="btn-sm b-dark" type="submit">In progress</button></form>
                                    @endif
                                    <form method="POST" action="{{ $i['resolve_url'] }}" onsubmit="return confirm('Mark this issue as resolved?');">@csrf<button class="btn-sm b-danger" type="submit">Resolve</button></form>
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
            <div class="e-icon">📝</div>
            <div class="e-title">No reported issues</div>
            <div class="e-sub">Maintenance reports staff file will appear here, ready to schedule or resolve.</div>
        </div>
    @endif
</div>
@else
<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $tab === 'history' ? 'Completed & cancelled maintenance' : 'Planned maintenance' }}</div>
        <button type="button" class="btn-sm b-primary" style="padding:8px 14px;" onclick="openForm()">+ Schedule maintenance</button>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('admin.sa.maintenance.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $facilityId === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $search }}" placeholder="Title or item"></div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.sa.maintenance.index', ['tab' => $tab]) }}">Reset</a>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table">
            <thead><tr><th>Date & time</th><th>Maintenance</th><th>Facility / item</th><th>Assigned</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['date'] }}<br><span class="sub">{{ $r['time'] }}</span>@if($r['overdue'])<br><span class="pill p-bad" style="margin-top:3px;">Overdue</span>@endif</td>
                        <td><strong>{{ $r['title'] }}</strong><br><span class="sub">{{ $r['type_label'] }}</span>
                            @if(count($r['conflicts']))<br><span class="pill p-warn" style="margin-top:3px;" title="{{ implode('; ', $r['conflicts']) }}">Overlaps {{ count($r['conflicts']) }}</span>@endif
                        </td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span>@if($r['equipment'])<br><span class="sub">{{ $r['equipment'] }}</span>@endif</td>
                        <td>{{ $r['assigned'] ?? '—' }}</td>
                        <td><span class="pill p-{{ ['scheduled' => 'open', 'in_progress' => 'under_review', 'completed' => 'resolved', 'cancelled' => 'cancelled'][$r['status']] ?? 'gray' }}">{{ $r['status_label'] }}</span></td>
                        <td>
                            <div class="row-actions">
                                @if($r['status'] === 'scheduled')
                                    <form method="POST" action="{{ $r['start_url'] }}" onsubmit="return confirm('Start this maintenance now?{{ $r['equipment'] ? ' The item will be marked under maintenance.' : '' }}');">@csrf<button class="btn-sm b-dark" type="submit">Start</button></form>
                                    <button type="button" class="btn-sm b-ghost" onclick="openForm({{ $r['id'] }})">Edit</button>
                                    <button type="button" class="btn-sm b-primary" onclick="openComplete({{ $r['id'] }})">Complete</button>
                                    <button type="button" class="btn-sm b-danger" onclick="openCancel({{ $r['id'] }})">Cancel</button>
                                @elseif($r['status'] === 'in_progress')
                                    <button type="button" class="btn-sm b-primary" onclick="openComplete({{ $r['id'] }})">Complete</button>
                                @else
                                    <button type="button" class="btn-sm b-ghost" onclick="openDetails({{ $r['id'] }})">Details</button>
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
            <div class="e-icon">🔧</div>
            <div class="e-title">{{ $tab === 'history' ? 'Nothing in the history yet' : 'No maintenance planned' }}</div>
            <div class="e-sub">{{ $tab === 'history' ? 'Completed and cancelled jobs will be listed here.' : 'Use "+ Schedule maintenance" to plan facility upkeep or equipment repair.' }}</div>
        </div>
    @endif
</div>

@endif

{{-- Create / edit modal --}}
<div class="ap-overlay" id="formModal">
    <div class="ap-box" style="max-width:520px;">
        <div class="ap-title" id="formTitle">Schedule maintenance</div>
        <div class="ap-text">Overlaps with booked events or other maintenance are flagged before saving.</div>
        <form method="POST" id="mForm" action="{{ route('admin.sa.maintenance.store') }}">
            @csrf
            <input type="hidden" name="_method" id="mMethod" value="POST">
            <input type="hidden" name="_mode" id="mMode" value="{{ old('_mode', 'create') }}">
            <input type="hidden" name="_id" id="mId" value="{{ old('_id') }}">
            <input type="hidden" name="maintenance_report_id" id="mReport" value="{{ old('maintenance_report_id') }}">
            <div class="note-banner green" id="mReportNote" style="display:none;margin-bottom:12px;">Linked to a staff-reported issue: starting this job moves the issue to in progress, and completing it resolves the issue.</div>

            <div class="fld-row">
                <div class="fld"><label>Facility</label>
                    <select name="facility_id" id="mFacility" required>
                        <option value="">Select…</option>
                        @foreach($facilities as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach
                    </select>
                    @error('facility_id')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="fld"><label>Inventory item (optional)</label>
                    <select name="equipment_inventory_id" id="mEquipment"><option value="">— none —</option></select>
                    @error('equipment_inventory_id')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Type</label>
                    <select name="type" id="mType" required>@foreach($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div class="fld"><label>Title</label><input type="text" name="title" id="mTitle" maxlength="150" required placeholder="e.g. Court floor resurfacing">
                    @error('title')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Date</label><input type="date" name="scheduled_date" id="mDate" min="{{ now()->toDateString() }}" required>
                    @error('scheduled_date')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="fld"><label>Assigned staff (optional)</label>
                    <select name="assigned_to" id="mAssigned"><option value="">— unassigned —</option>@foreach($staff as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach</select>
                </div>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Start time</label><input type="time" name="start_time" id="mStart" required></div>
                <div class="fld"><label>End time</label><input type="time" name="end_time" id="mEnd" required>
                    @error('end_time')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="fld"><label>Details (optional)</label><textarea name="description" id="mDesc" maxlength="2000"></textarea></div>
            <label class="check-row"><input type="checkbox" name="ignore_conflicts" value="1" id="mIgnore"> <span>Schedule anyway if it overlaps a booked event or other maintenance</span></label>

            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('formModal')">Cancel</button>
                <button type="submit" class="b-go" id="mSubmit">Schedule</button>
            </div>
        </form>
    </div>
</div>

{{-- Complete modal --}}
<div class="ap-overlay" id="completeModal">
    <div class="ap-box">
        <div class="ap-title">Mark as completed</div>
        <div class="ap-text" id="completeWhich"></div>
        <form method="POST" id="completeForm">
            @csrf
            <div class="fld" id="outcomeField"><label>Condition the item was left in</label>
                <select name="outcome_condition" id="outcomeSelect"><option value="good">Good</option><option value="damaged">Still damaged</option></select>
                <div class="hint">Updates the item's condition in the inventory and records today as its last check.</div>
            </div>
            <div class="fld"><label>Completion notes (optional)</label><textarea name="completion_notes" maxlength="2000"></textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('completeModal')">Cancel</button>
                <button type="submit" class="b-go">Mark completed</button>
            </div>
        </form>
    </div>
</div>

{{-- Cancel modal --}}
<div class="ap-overlay" id="cancelModal">
    <div class="ap-box">
        <div class="ap-title">Cancel this maintenance?</div>
        <div class="ap-text" id="cancelWhich"></div>
        <form method="POST" id="cancelForm">
            @csrf
            <div class="fld"><label>Reason (required)</label><textarea name="reason" required maxlength="500"></textarea></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('cancelModal')">Keep it</button>
                <button type="submit" class="b-red">Cancel maintenance</button>
            </div>
        </form>
    </div>
</div>

{{-- Details modal (history rows) --}}
<div class="ap-overlay" id="detailsModal">
    <div class="ap-box">
        <div class="ap-title" id="detailsTitle">Maintenance</div>
        <div id="detailsBody"></div>
        <div class="ap-actions"><button type="button" class="b-close" onclick="closeModal('detailsModal')">Close</button></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const EQUIP = @json($equipment);
    const STORE_URL = @json(route('admin.sa.maintenance.store'));
    const PREFILL = @json($prefill);
    const ISSUES = @json($issueRows);
    const OLD = {
        mode: @json(old('_mode')), id: @json(old('_id')),
        facility: @json(old('facility_id')), equipment: @json(old('equipment_inventory_id')),
        type: @json(old('type')), title: @json(old('title')), date: @json(old('scheduled_date')),
        report: @json(old('maintenance_report_id')),
        start: @json(old('start_time')), end: @json(old('end_time')), assigned: @json(old('assigned_to')), desc: @json(old('description')),
    };

    const $ = id => document.getElementById(id);
    const esc = v => (v === null || v === undefined || v === '') ? '—' : String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function openModal(id){ $(id).classList.add('show'); }
    function closeModal(id){ $(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    function fillEquipment(facilityId, selected){
        const sel = $('mEquipment');
        sel.innerHTML = '<option value="">— none —</option>';
        EQUIP.filter(e => e.facility_id === null || String(e.facility_id) === String(facilityId)).forEach(e => {
            const o = document.createElement('option'); o.value = e.id; o.textContent = e.name + (e.facility_id === null ? ' (shared)' : '');
            if (String(selected) === String(e.id)) o.selected = true; sel.appendChild(o);
        });
    }
    $('mFacility').addEventListener('change', () => fillEquipment($('mFacility').value, ''));

    function setForm(v){
        $('mFacility').value = v.facility ?? ''; fillEquipment(v.facility, v.equipment);
        $('mType').value = v.type || 'routine'; $('mTitle').value = v.title || ''; $('mDate').value = v.date || '';
        $('mStart').value = v.start || ''; $('mEnd').value = v.end || ''; $('mAssigned').value = v.assigned ?? ''; $('mDesc').value = v.desc || '';
        $('mReport').value = v.report ?? ''; $('mReportNote').style.display = v.report ? '' : 'none';
        $('mIgnore').checked = false;
    }

    function openForm(id){
        if (id) {
            const r = ROWS.find(x => x.id === id); if (!r) return;
            $('formTitle').textContent = 'Edit maintenance'; $('mSubmit').textContent = 'Save changes';
            $('mForm').action = r.edit_url; $('mMethod').value = 'PUT'; $('mMode').value = 'edit'; $('mId').value = r.id;
            setForm({report: r.report_id, facility: r.facility_id, equipment: r.equipment_id, type: r.type, title: r.title, date: r.date_value, start: r.start, end: r.end, assigned: r.assigned_id, desc: r.description});
        } else {
            $('formTitle').textContent = 'Schedule maintenance'; $('mSubmit').textContent = 'Schedule';
            $('mForm').action = STORE_URL; $('mMethod').value = 'POST'; $('mMode').value = 'create'; $('mId').value = '';
            setForm({facility: PREFILL.facility_id || '', equipment: PREFILL.equipment_id || ''});
        }
        openModal('formModal');
    }

    function scheduleFromIssue(id){
        const i = ISSUES.find(x => x.id === id); if (!i) return;
        openForm();
        setForm({report: i.id, facility: i.facility_id, type: 'repair', title: i.title, desc: 'Reported issue: ' + i.description});
    }

    function openIssue(id){
        const i = ISSUES.find(x => x.id === id); if (!i) return;
        const items = [['Facility', i.facility], ['Severity', i.severity], ['Status', i.status_label], ['Reported by', i.reporter], ['Reported on', i.reported_at],
            ['Description', i.description], ['Resolved by', i.resolved_by], ['Resolved on', i.resolved_at]];
        let html = items.map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value">${esc(v)}</span></div>`).join('');
        if (i.photo_url) html += `<div style="margin-top:10px;"><a class="btn-sm b-ghost" href="${esc(i.photo_url)}" target="_blank" rel="noopener">View photo</a></div>`;
        $('detailsTitle').textContent = i.title;
        $('detailsBody').innerHTML = html;
        openModal('detailsModal');
    }

    function openComplete(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        $('completeWhich').textContent = `${r.title} — ${r.facility}${r.equipment ? ' · ' + r.equipment : ''}`;
        $('completeForm').action = r.complete_url;
        $('outcomeField').style.display = r.has_item ? '' : 'none';
        $('outcomeSelect').disabled = !r.has_item;
        openModal('completeModal');
    }

    function openCancel(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        $('cancelWhich').textContent = `${r.title} — ${r.date}, ${r.time}`;
        $('cancelForm').action = r.cancel_url;
        openModal('cancelModal');
    }

    function openDetails(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        const items = [['Facility', r.facility], ['Item', r.equipment], ['Type', r.type_label], ['Date', r.date], ['Time', r.time],
            ['Assigned', r.assigned], ['Status', r.status_label], ['Left in condition', r.outcome], ['Completed', r.completed_at], ['Notes', r.notes], ['Details', r.description]];
        $('detailsTitle').textContent = r.title;
        $('detailsBody').innerHTML = items.map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value">${esc(v)}</span></div>`).join('');
        openModal('detailsModal');
    }

    // Reopen the form after a validation error, or open it pre-filled from "Schedule maintenance" on the inventory page.
    if (OLD.mode) {
        const r = OLD.mode === 'edit' ? ROWS.find(x => String(x.id) === String(OLD.id)) : null;
        if (OLD.mode === 'edit' && r) { openForm(r.id); } else { openForm(); }
        setForm({report: OLD.report, facility: OLD.facility, equipment: OLD.equipment, type: OLD.type, title: OLD.title, date: OLD.date, start: OLD.start, end: OLD.end, assigned: OLD.assigned, desc: OLD.desc});
    } else if (PREFILL.equipment_id) {
        openForm();
    }
</script>
@endpush