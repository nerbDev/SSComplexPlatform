@extends('layouts.admin')

@section('title', 'Audit Trail')

@push('styles')
    @include('admin.ob._styles')
    <style>
        .p-info{background:#e6f0fb;color:#2b5d9b;}
        .desc{max-width:420px;}
        .desc .t{overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
        .code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;color:var(--ink-600);}
    </style>
@endpush

@section('content')

<div class="note-banner green">
    A read-only record of who did what and when. Entries can't be edited or deleted from here. Status changes are captured automatically,
    alongside the descriptive entries each module writes.
</div>

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Entries</div><div class="val">{{ number_format($stats['entries']) }}</div><div class="hint">Matching the filters</div></div>
    <div class="card ob-stat"><div class="lbl">People involved</div><div class="val">{{ $stats['people'] }}</div><div class="hint">Distinct signed-in users</div></div>
    <div class="card ob-stat"><div class="lbl">Status changes</div><div class="val">{{ $stats['status'] }}</div><div class="hint">Appointment transitions</div></div>
    <div class="card ob-stat"><div class="lbl">Failed sign-ins</div><div class="val">{{ $stats['failed'] }}</div><div class="hint">Wrong email or password</div></div>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Audit trail</div>
        <a class="btn-sm b-dark" href="{{ route('admin.audit.export', request()->query()) }}">Export CSV</a>
    </div>

    <div class="filter-bar">
        <div class="chips">
            <a class="chip {{ !$filters['category'] ? 'active' : '' }}" href="{{ route('admin.audit.index', array_filter(request()->except('category', 'page'))) }}">All</a>
            @foreach($categories as $key => $label)
                <a class="chip {{ $filters['category'] === $key ? 'active' : '' }}" href="{{ route('admin.audit.index', array_filter(array_merge(request()->except('page'), ['category' => $key]))) }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('admin.audit.index') }}">
            @if($filters['category'])<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
            <div class="f"><label>From</label><input class="f-input" type="date" name="from" value="{{ $filters['from'] }}"></div>
            <div class="f"><label>To</label><input class="f-input" type="date" name="to" value="{{ $filters['to'] }}"></div>
            <div class="f"><label>Role</label>
                <select class="f-input" name="role">
                    <option value="">Anyone</option>
                    @foreach(['admin' => 'Admin', 'staff' => 'Staff', 'client' => 'Client', 'guest' => 'Guest / system'] as $k => $l)<option value="{{ $k }}" @selected($filters['role'] === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>User</label>
                <select class="f-input" name="user_id">
                    <option value="">Everyone</option>
                    @foreach($users as $u)<option value="{{ $u['id'] }}" @selected((string) $filters['user_id'] === (string) $u['id'])>{{ $u['label'] }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Description or action"></div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('admin.audit.index') }}">Reset</a>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:860px;">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>What happened</th><th>Subject</th><th></th></tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td style="white-space:nowrap;">{{ $r['when'] }}</td>
                        <td><strong>{{ $r['user'] }}</strong><br><span class="sub">{{ $r['role'] }}</span></td>
                        <td><span class="pill p-{{ $r['tone'] }}">{{ $r['action_label'] }}</span></td>
                        <td class="desc"><div class="t" title="{{ $r['description'] }}">{{ $r['description'] ?: '—' }}</div></td>
                        <td>{{ $r['subject'] }}</td>
                        <td><button type="button" class="btn-sm b-ghost" onclick="openEntry({{ $r['id'] }})">Details</button></td>
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
            <div class="e-icon">🧾</div>
            <div class="e-title">No entries match</div>
            <div class="e-sub">Try a wider date range or clear the filters.</div>
        </div>
    @endif
</div>

<div class="ap-overlay" id="entryModal">
    <div class="ap-box">
        <div class="ap-title" id="entryTitle">Entry</div>
        <div id="entryBody"></div>
        <div class="ap-actions"><button type="button" class="b-close" onclick="document.getElementById('entryModal').classList.remove('show')">Close</button></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const esc = v => (v === null || v === undefined || v === '') ? '—' : String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const modal = document.getElementById('entryModal');
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('show'); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.classList.remove('show'); });

    function openEntry(id){
        const r = ROWS.find(x => x.id === id); if (!r) return;
        const items = [['Entry #', r.id], ['When', r.when_full], ['User', r.user], ['Role', r.role], ['Action', r.action], ['Description', r.description], ['Subject', r.subject], ['IP address', r.ip]];
        document.getElementById('entryTitle').textContent = r.action_label;
        document.getElementById('entryBody').innerHTML = items.map(([l, v]) => `<div class="d-row"><span class="d-label">${l}</span><span class="d-value" style="white-space:pre-line;">${esc(v)}</span></div>`).join('');
        modal.classList.add('show');
    }
</script>
@endpush