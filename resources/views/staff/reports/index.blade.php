@extends('layouts.staff')

@section('title', 'Reports')

@push('styles')
    {{-- shared panel styles (tabs, tables, pills, modals, forms) --}}
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="note-banner green">
    <strong>Incident / damage</strong> reports go to the OB Admin. <strong>Facility condition</strong> reports (things that need fixing, like a slippery floor) go to the Sports &amp; Activity Admin.
    Add a photo when you can.
</div>

<div class="view-tabs">
    <a class="view-tab {{ $tab === 'incident' ? 'active' : '' }}" href="{{ route('staff.reports.index', array_filter(['tab' => 'incident', 'scope' => $scope === 'all' ? 'all' : null])) }}">Incident / damage <span class="n">{{ $counts['incident'] }} mine</span></a>
    <a class="view-tab {{ $tab === 'maintenance' ? 'active' : '' }}" href="{{ route('staff.reports.index', array_filter(['tab' => 'maintenance', 'scope' => $scope === 'all' ? 'all' : null])) }}">Facility condition <span class="n">{{ $counts['maintenance'] }} mine</span></a>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">{{ $tab === 'incident' ? 'Incident & damage reports' : 'Facility condition reports' }}</div>
        <button type="button" class="btn-sm b-primary" style="padding:8px 14px;" onclick="openNew()">+ New {{ $tab === 'incident' ? 'incident report' : 'condition report' }}</button>
    </div>

    <div class="filter-bar">
        <div class="chips">
            <a class="chip {{ $scope === 'mine' ? 'active' : '' }}" href="{{ route('staff.reports.index', array_filter(['tab' => $tab, 'facility_id' => $facilityId])) }}">My reports</a>
            <a class="chip {{ $scope === 'all' ? 'active' : '' }}" href="{{ route('staff.reports.index', array_filter(['tab' => $tab, 'scope' => 'all', 'facility_id' => $facilityId])) }}">All staff</a>
        </div>
        <form class="filter-form" method="GET" action="{{ route('staff.reports.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id" onchange="this.form.submit()">
                    <option value="">All facilities</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $facilityId === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:760px;">
            @if($tab === 'incident')
                <thead><tr><th>Reported</th><th>Facility / event</th><th>What happened</th><th class="num">Est. cost</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r['reported_at'] }}@if($scope === 'all')<br><span class="sub">{{ $r['reporter'] }}</span>@endif</td>
                            <td><span class="unit-pill">{{ $r['facility'] }}</span>@if($r['event'])<br><span class="sub">{{ $r['event'] }}</span>@endif</td>
                            <td><div class="trunc" title="{{ $r['description'] }}">{{ $r['description'] }}</div>@if($r['refund'])<span class="pill p-warn" style="margin-top:3px;">Refund related</span>@endif</td>
                            <td class="num">{{ $r['cost'] !== null ? '₱' . number_format($r['cost'], 2) : '—' }}</td>
                            <td><span class="pill p-{{ $r['status'] }}">{{ $r['status_label'] }}</span></td>
                            <td><button type="button" class="btn-sm b-ghost" onclick="openDetails({{ $r['id'] }})">Details</button></td>
                        </tr>
                    @endforeach
                </tbody>
            @else
                <thead><tr><th>Reported</th><th>Facility</th><th>Issue</th><th>Severity</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r['reported_at'] }}@if($scope === 'all')<br><span class="sub">{{ $r['reporter'] }}</span>@endif</td>
                            <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                            <td><strong>{{ $r['title'] }}</strong><div class="trunc sub" title="{{ $r['description'] }}">{{ $r['description'] }}</div></td>
                            <td><span class="pill {{ ['high' => 'p-bad', 'medium' => 'p-warn', 'low' => 'p-gray'][$r['severity']] ?? 'p-gray' }}">{{ ucfirst($r['severity']) }}</span></td>
                            <td><span class="pill p-{{ ['open' => 'open', 'in_progress' => 'under_review', 'resolved' => 'resolved'][$r['status']] ?? 'gray' }}">{{ $r['status_label'] }}</span></td>
                            <td><button type="button" class="btn-sm b-ghost" onclick="openDetails({{ $r['id'] }})">Details</button></td>
                        </tr>
                    @endforeach
                </tbody>
            @endif
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
            <div class="e-icon">{{ $tab === 'incident' ? '🛠' : '🔧' }}</div>
            <div class="e-title">No reports here yet</div>
            <div class="e-sub">Use "+ New" to file one. {{ $scope === 'mine' ? 'Switch to "All staff" to see what others already reported.' : '' }}</div>
        </div>
    @endif
</div>

{{-- New incident / damage report --}}
<div class="ap-overlay" id="incidentModal">
    <div class="ap-box" style="max-width:520px;">
        <div class="ap-title">New incident / damage report</div>
        <div class="ap-text">Describe what happened. The OB Admin reviews it and decides what to do.</div>
        <form method="POST" action="{{ route('staff.reports.incident.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="fld-row">
                <div class="fld"><label>Facility</label>
                    <select name="facility_id" id="iFacility" required>
                        <option value="">Select…</option>
                        @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) old('facility_id') === (string) $f->id)>{{ $f->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fld"><label>Related event (optional)</label>
                    <select name="appointment_id" id="iEvent"><option value="">— none —</option></select>
                    @error('appointment_id')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="fld"><label>What happened</label><textarea name="description" required maxlength="3000" placeholder="e.g. The basketball ring on court 2 snapped during the event.">{{ old('description') }}</textarea></div>
            <div class="fld"><label>Damaged items (optional)</label><textarea name="damaged_items" maxlength="2000" placeholder="One per line, e.g. Basketball ring ×1">{{ old('damaged_items') }}</textarea></div>
            <div class="fld-row">
                <div class="fld"><label>Estimated cost (₱, optional)</label><input type="number" name="estimated_cost" min="0" step="0.01" value="{{ old('estimated_cost') }}"></div>
                <div class="fld"><label>Photo (optional)</label><input type="file" name="photo" accept="image/*" capture="environment"></div>
            </div>
            <label class="check-row"><input type="checkbox" name="refund_related" value="1" @checked(old('refund_related'))> <span>The client may need a refund because of this (an event must be selected)</span></label>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('incidentModal')">Cancel</button>
                <button type="submit" class="b-go">Submit report</button>
            </div>
        </form>
    </div>
</div>

{{-- New facility condition report --}}
<div class="ap-overlay" id="maintenanceModal">
    <div class="ap-box" style="max-width:520px;">
        <div class="ap-title">New facility condition report</div>
        <div class="ap-text">Something that needs fixing or attention. The Sports &amp; Activity Admin schedules the work.</div>
        <form method="POST" action="{{ route('staff.reports.maintenance.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="fld-row">
                <div class="fld"><label>Facility</label>
                    <select name="facility_id" required>
                        <option value="">Select…</option>
                        @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) old('facility_id') === (string) $f->id)>{{ $f->name }}</option>@endforeach
                    </select>
                </div>
                <div class="fld"><label>Severity</label>
                    <select name="severity" required>
                        @foreach($severities as $k => $l)<option value="{{ $k }}" @selected(old('severity', 'low') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="fld"><label>Issue</label><input type="text" name="title" maxlength="150" required value="{{ old('title') }}" placeholder="e.g. Slippery floor near the entrance"></div>
            <div class="fld"><label>Details</label><textarea name="description" required maxlength="3000">{{ old('description') }}</textarea></div>
            <div class="fld"><label>Photo (optional)</label><input type="file" name="photo" accept="image/*" capture="environment"></div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('maintenanceModal')">Cancel</button>
                <button type="submit" class="b-go">Submit report</button>
            </div>
        </form>
    </div>
</div>

{{-- Details modal --}}
<div class="ap-overlay" id="detailsModal">
    <div class="ap-box">
        <div class="ap-title" id="detailsTitle">Report</div>
        <div id="detailsBody"></div>
        <div class="ap-actions"><button type="button" class="b-close" onclick="closeModal('detailsModal')">Close</button></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const TAB = @json($tab);
    const ROWS = @json($rows);
    const EVENTS = @json($events);
    const REOPEN = @json(($errors->any() || session('reopen')) ? (old('title') !== null ? 'maintenance' : 'incident') : null);
    const OLD_EVENT = @json(old('appointment_id'));

    const $ = id => document.getElementById(id);
    const esc = v => (v === null || v === undefined || v === '') ? '—' : String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function openModal(id){ $(id).classList.add('show'); }
    function closeModal(id){ $(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    function fillEvents(){
        const sel = $('iEvent'), fac = $('iFacility').value;
        sel.innerHTML = '<option value="">— none —</option>';
        EVENTS.filter(e => String(e.facility_id) === String(fac)).forEach(e => {
            const o = document.createElement('option'); o.value = e.id; o.textContent = e.label;
            if (String(OLD_EVENT) === String(e.id)) o.selected = true; sel.appendChild(o);
        });
    }
    $('iFacility').addEventListener('change', fillEvents); fillEvents();

    function openNew(){ openModal(TAB === 'incident' ? 'incidentModal' : 'maintenanceModal'); }
    if (REOPEN) openModal(REOPEN === 'maintenance' ? 'maintenanceModal' : 'incidentModal');

    function openDetails(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        let items;
        if (TAB === 'incident') {
            items = [['Facility', r.facility], ['Event', r.event], ['Reported by', r.reporter], ['Reported on', r.reported_at], ['Status', r.status_label],
                ['What happened', r.description], ['Damaged items', r.items], ['Estimated cost', r.cost !== null ? '₱' + Number(r.cost).toLocaleString('en-PH', {minimumFractionDigits: 2}) : null],
                ['Refund related', r.refund ? 'Yes' : 'No'], ['Resolution', r.resolution]];
            $('detailsTitle').textContent = 'Incident #' + r.id;
        } else {
            items = [['Facility', r.facility], ['Severity', r.severity], ['Reported by', r.reporter], ['Reported on', r.reported_at], ['Status', r.status_label], ['Details', r.description]];
            $('detailsTitle').textContent = r.title;
        }
        let html = items.map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value" style="white-space:pre-line;">${esc(v)}</span></div>`).join('');
        if (r.photo_url) html += `<div style="margin-top:10px;"><a class="btn-sm b-ghost" href="${esc(r.photo_url)}" target="_blank" rel="noopener">View photo</a></div>`;
        $('detailsBody').innerHTML = html;
        openModal('detailsModal');
    }
</script>
@endpush