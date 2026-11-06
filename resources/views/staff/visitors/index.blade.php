@extends('layouts.staff')

@section('title', 'Visitor Log')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('warning'))<div class="note-banner">{{ session('warning') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="ob-stats">
    <div class="card ob-stat"><div class="lbl">Inside now</div><div class="val">{{ $stats['inside_people'] }}</div><div class="hint">{{ $stats['inside_entries'] }} {{ \Illuminate\Support\Str::plural('entry', $stats['inside_entries']) }} signed in</div></div>
    <div class="card ob-stat"><div class="lbl">Visits today</div><div class="val">{{ $stats['visits_today'] }}</div><div class="hint">Sign-ins recorded</div></div>
    <div class="card ob-stat"><div class="lbl">People today</div><div class="val">{{ $stats['people_today'] }}</div><div class="hint">Including each party</div></div>
    <div class="card ob-stat"><div class="lbl">Events today</div><div class="val">{{ count($events) }}</div><div class="hint">Scheduled bookings</div></div>
</div>

@if(count($stale))
<div class="note-banner">
    <strong>{{ count($stale) }} {{ \Illuminate\Support\Str::plural('entry', count($stale)) }} from earlier days {{ count($stale) === 1 ? 'was' : 'were' }} never signed out.</strong>
    Sign them out below so the analytics stay accurate (they're closed at the event's end time, not now).
    <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px;">
        @foreach($stale as $s)
            <form method="POST" action="{{ $s['out_url'] }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                @csrf
                <span>{{ $s['day'] }} {{ $s['time_in'] }} — <strong>{{ $s['name'] }}</strong>@if($s['event']) ({{ $s['event'] }})@endif</span>
                <button class="btn-sm b-dark" type="submit">Sign out</button>
            </form>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <div class="card-head">
        <div class="card-title">Visitor log · {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if($isToday && $stats['inside_entries'])
                <form method="POST" action="{{ route('staff.visitors.signout-all') }}" onsubmit="return confirm('Sign out everyone still inside?');">
                    @csrf @if($eventFilter)<input type="hidden" name="appointment_id" value="{{ $eventFilter }}">@endif
                    <button class="btn-sm b-danger" type="submit" style="padding:8px 14px;">Sign out everyone{{ $eventFilter ? ' (this event)' : '' }}</button>
                </form>
            @endif
            <button type="button" class="btn-sm b-primary" style="padding:8px 14px;" onclick="openSignIn()">+ Sign in a visitor</button>
        </div>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('staff.visitors.index') }}">
            <div class="f"><label>Date</label><input class="f-input" type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"></div>
            <div class="f"><label>Event</label>
                <select class="f-input" name="appointment_id">
                    <option value="">All visits</option>
                    @foreach($events as $e)<option value="{{ $e['id'] }}" @selected((string) $eventFilter === (string) $e['id'])>{{ $e['label'] }}</option>@endforeach
                </select>
            </div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('staff.visitors.index') }}">Today</a>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:760px;">
            <thead><tr><th>Visitor</th><th>Event</th><th class="num">People</th><th>In</th><th>Out</th><th>Logged by</th><th></th></tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['name'] }}</strong>@if($r['organizer'])<span class="pill p-ok" style="margin-left:6px;">Organizer</span>@endif
                            @if($r['organization'])<br><span class="sub">{{ $r['organization'] }}</span>@endif</td>
                        <td>@if($r['event']){{ $r['event'] }}<br><span class="sub">{{ $r['facility'] }}</span>@else<span class="sub">Walk-in</span>@endif</td>
                        <td class="num">{{ $r['party'] }}</td>
                        <td>{{ $r['time_in'] }}</td>
                        <td>@if($r['inside'])<span class="pill p-open">Inside</span>@else{{ $r['time_out'] }}<br><span class="sub">{{ $r['duration'] }}</span>@endif</td>
                        <td>{{ $r['logged_by'] }}</td>
                        <td>@if($r['inside'])<form method="POST" action="{{ $r['out_url'] }}">@csrf<button class="btn-sm b-dark" type="submit">Sign out</button></form>@endif</td>
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
            <div class="e-icon">🚪</div>
            <div class="e-title">No visits logged</div>
            <div class="e-sub">Use "+ Sign in a visitor" when someone arrives at the desk.</div>
        </div>
    @endif
</div>

<div class="ap-overlay" id="signInModal">
    <div class="ap-box">
        <div class="ap-title">Sign in a visitor</div>
        <div class="ap-text">For an event, sign in the organizer / group leader and how many people came with them.</div>
        <form method="POST" action="{{ route('staff.visitors.store') }}">
            @csrf
            <div class="fld"><label>Related event (optional — leave empty for a walk-in)</label>
                <select name="appointment_id" id="vEvent">
                    <option value="">— Walk-in —</option>
                    @foreach($events as $e)<option value="{{ $e['id'] }}" data-booker="{{ $e['booker'] }}" data-expected="{{ $e['expected'] }}" @selected((string) old('appointment_id') === (string) $e['id'])>{{ $e['label'] }}</option>@endforeach
                </select>
                @error('appointment_id')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fld"><label>Name</label><input type="text" name="visitor_name" id="vName" required maxlength="255" value="{{ old('visitor_name') }}">
                <div class="hint" id="bookerHint" style="display:none;"><a href="#" onclick="useBooker();return false;" style="color:var(--green-700);text-decoration:underline;">Use the booker's name</a></div>
            </div>
            <div class="fld"><label>Organization (optional)</label><input type="text" name="organization" maxlength="255" value="{{ old('organization') }}"></div>
            <div class="fld-row">
                <div class="fld"><label>Number of people</label><input type="number" name="party_size" id="vParty" min="1" max="5000" required value="{{ old('party_size', 1) }}"></div>
                <div class="fld"><label>&nbsp;</label><label class="check-row" style="margin-top:8px;"><input type="checkbox" name="is_organizer" value="1" id="vOrganizer" @checked(old('is_organizer'))> <span>Event organizer / leader</span></label></div>
            </div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="document.getElementById('signInModal').classList.remove('show')">Cancel</button>
                <button type="submit" class="b-go">Sign in</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const modal = document.getElementById('signInModal');
    const ev = document.getElementById('vEvent');
    function openSignIn(){ modal.classList.add('show'); syncEvent(); }
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('show'); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.classList.remove('show'); });

    function syncEvent(){
        const opt = ev.options[ev.selectedIndex];
        document.getElementById('bookerHint').style.display = ev.value ? '' : 'none';
        document.getElementById('vOrganizer').checked = !!ev.value && document.getElementById('vOrganizer').checked;
    }
    function useBooker(){
        const opt = ev.options[ev.selectedIndex];
        document.getElementById('vName').value = opt.dataset.booker || '';
        document.getElementById('vOrganizer').checked = true;
        if (opt.dataset.expected) document.getElementById('vParty').value = opt.dataset.expected;
    }
    ev.addEventListener('change', syncEvent);
    @if($errors->any() && old('visitor_name') !== null) openSignIn(); @endif
</script>
@endpush