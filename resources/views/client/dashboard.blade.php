@extends('layouts.client')

@section('title', 'Dashboard')

@push('styles')
<style>
    /* ---------- Readable text colors (scoped to dashboard sections) ---------- */
    .section{
        margin-bottom:18px;
        min-width:0;
        --t-strong:#0f1f18;   /* headings / key values   */
        --t-body:#22352c;     /* normal table text       */
        --t-soft:#43564d;     /* secondary / muted text  */
    }
    .section .muted{color:var(--t-soft);}
    .section .card-head{flex-wrap:wrap;gap:8px;}

    /* ---------- Announcements carousel (flip cards, green theme) ---------- */
    .announce-carousel{display:flex;align-items:center;gap:10px;min-width:0;}
    .announce-track{
        display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;
        scroll-behavior:smooth;padding:4px 2px 10px;scrollbar-width:none;flex:1;min-width:0;
        -webkit-overflow-scrolling:touch;
    }
    .announce-track::-webkit-scrollbar{display:none;}
    .announce-slide{flex:0 0 clamp(170px, 44%, 230px);scroll-snap-align:start;perspective:1400px;}

    .flip-card{position:relative;width:100%;aspect-ratio:3/4;cursor:pointer;}
    .flip-card__inner{position:relative;width:100%;height:100%;transform-style:preserve-3d;transition:transform .55s cubic-bezier(.4,.2,.2,1);}
    .flip-card.is-flipped .flip-card__inner{transform:rotateY(180deg);}
    .flip-card__front,.flip-card__back{position:absolute;inset:0;backface-visibility:hidden;-webkit-backface-visibility:hidden;border-radius:14px;box-shadow:0 10px 24px rgba(14,28,23,.18);}
    .flip-card__front{background-size:cover;background-position:center;background-color:var(--green-800);display:flex;align-items:flex-end;}
    .flip-card__front::after{content:'';position:absolute;inset:0;border-radius:inherit;background:linear-gradient(to top, rgba(7,35,26,.9), transparent 55%);}
    .flip-card__hint{position:relative;z-index:1;width:100%;text-align:center;padding:.55rem .6rem;font-size:12px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.5);}
    .flip-card__back{transform:rotateY(180deg);background:var(--green-950);color:#fff;padding:16px 14px;display:flex;flex-direction:column;overflow:hidden;}
    .flip-card__status{align-self:flex-start;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;padding:3px 9px;border-radius:999px;margin-bottom:9px;}
    .flip-card__status.status-open{background:rgba(31,174,116,.28);color:#6ee7a0;}
    .flip-card__status.status-full{background:rgba(239,141,61,.28);color:#ffb26b;}
    .flip-card__status.status-ongoing{background:rgba(255,255,255,.18);color:#fff;}
    .flip-card__status.status-cancelled{background:rgba(180,80,44,.32);color:#f4a888;}
    .flip-card__status.status-finished{background:rgba(255,255,255,.14);color:#dbe7e1;}
    .flip-card__back h3{font-size:14px;font-weight:800;line-height:1.3;margin-bottom:7px;color:#fff;}
    .flip-card__meta{font-size:12px;color:rgba(255,255,255,.92);margin-bottom:4px;}
    .flip-card__desc{font-size:12px;line-height:1.55;color:rgba(255,255,255,.85);margin-top:6px;flex:1;overflow-y:auto;}
    .flip-card__close{align-self:flex-start;margin-top:10px;font-size:11.5px;font-weight:700;color:#ffb26b;border-bottom:1px solid rgba(255,178,107,.5);}

    .carousel-arrow{
        flex:0 0 auto;width:34px;height:34px;border-radius:50%;background:var(--green-050);
        border:1px solid var(--line);color:var(--t-strong);font-size:16px;display:flex;
        align-items:center;justify-content:center;cursor:pointer;
    }
    .carousel-arrow:hover{background:var(--green-100);}

    /* ---------- My Activities ---------- */
    .status-tabs{display:flex;gap:6px;flex-wrap:wrap;}
    .status-tab{
        font-size:12.5px;font-weight:700;color:var(--t-body);padding:8px 13px;border-radius:9px;
        cursor:pointer;background:var(--green-050);border:1px solid var(--line);white-space:nowrap;
        user-select:none;-webkit-tap-highlight-color:transparent;
    }
    .status-tab.active{background:var(--green-600);color:#fff;border-color:var(--green-600);}
    .status-tab .count{margin-left:5px;font-size:11px;opacity:.9;}

    .activity-table{width:100%;border-collapse:collapse;margin-top:14px;}
    .activity-table th{
        text-align:left;font-size:11.5px;color:var(--t-soft);font-weight:800;
        text-transform:uppercase;letter-spacing:.03em;padding:9px 10px;border-bottom:2px solid var(--line);
    }
    .activity-table td{padding:13px 10px;font-size:13.5px;color:var(--t-body);border-bottom:1px solid var(--line);vertical-align:middle;}
    .activity-table td strong{color:var(--t-strong);}
    .activity-table tr:last-child td{border-bottom:none;}
    .unit-pill{font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);white-space:nowrap;display:inline-block;}
    .activity-action-btn{
        display:inline-block;font-size:12px;font-weight:700;padding:7px 14px;border-radius:999px;
        background:var(--green-600);color:#fff;white-space:nowrap;border:none;cursor:pointer;font-family:inherit;text-decoration:none;text-align:center;
    }
    .activity-action-btn:hover{background:var(--green-700);color:#fff;}
    .activity-action-note{
        display:inline-block;font-size:12px;font-weight:600;color:var(--t-soft);
        font-style:italic;
    }
    .activity-empty-filter{display:none;text-align:center;padding:26px 10px;font-size:13px;font-weight:600;color:var(--t-soft);}

    /* ---------- Attach Form modal ---------- */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(7,35,26,.55);align-items:center;justify-content:center;z-index:300;padding:16px;}
    .modal-overlay.show{display:flex;}
    .modal-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:420px;max-height:calc(100vh - 32px);overflow-y:auto;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);text-align:left;}
    .modal-box .modal-title{font-size:16px;font-weight:800;margin-bottom:4px;color:var(--t-strong);}
    .modal-box .modal-sub{font-size:13px;color:var(--t-soft);margin-bottom:18px;}
    .af-field{margin-bottom:14px;}
    .af-field label{display:block;font-size:13px;font-weight:700;color:var(--t-body);margin-bottom:6px;}
    .af-field input{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:14px;background:var(--green-050);color:var(--t-strong);}
    .af-actions{display:flex;gap:10px;margin-top:18px;}
    .af-actions button{flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:14px;cursor:pointer;border:1px solid var(--line);}
    .af-cancel{background:#fff;color:var(--t-strong);}
    .af-submit{background:var(--green-600);border-color:var(--green-600);color:#fff;}

    /* ---------- Unavailable days & hours ---------- */
    .unavail-list{display:flex;flex-direction:column;gap:10px;}
    .unavail-row{
        display:flex;align-items:center;gap:12px;padding:11px 12px;border:1px solid var(--line);
        border-radius:12px;background:var(--green-050);min-width:0;
    }
    .unavail-date{
        flex:0 0 auto;width:52px;height:52px;border-radius:10px;background:#fff;border:1px solid var(--line);
        display:flex;flex-direction:column;align-items:center;justify-content:center;
    }
    .unavail-date .d{font-size:16px;font-weight:800;line-height:1;color:var(--t-strong);}
    .unavail-date .m{font-size:10px;font-weight:700;color:var(--t-soft);text-transform:uppercase;}
    .unavail-info{flex:1;min-width:0;}
    .unavail-info .title{font-size:13.5px;font-weight:700;color:var(--t-strong);overflow-wrap:anywhere;}
    .unavail-info .sub{font-size:12.5px;color:var(--t-soft);margin-top:2px;overflow-wrap:anywhere;}

    /* ---------- Rates & activity types ---------- */
    .rates-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:22px;}
    .rates-grid > div{min-width:0;}
    .rates-table{width:100%;border-collapse:collapse;}
    .rates-table th{
        text-align:left;font-size:11.5px;color:var(--t-soft);font-weight:800;
        text-transform:uppercase;letter-spacing:.03em;padding:9px 10px;border-bottom:2px solid var(--line);
    }
    .rates-table td{padding:12px 10px;font-size:13.5px;color:var(--t-body);border-bottom:1px solid var(--line);}
    .rates-table tr:last-child td{border-bottom:none;}
    .rate-unit{display:flex;align-items:center;gap:9px;font-weight:700;color:var(--t-strong);}
    .rate-dot{width:9px;height:9px;border-radius:3px;background:var(--green-500);flex:0 0 auto;}
    .activity-chips{display:flex;flex-wrap:wrap;gap:8px;}
    .activity-chip{
        font-size:12.5px;font-weight:700;padding:7px 13px;border-radius:999px;
        background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);
    }
    .rates-subhead{font-size:12.5px;font-weight:800;color:var(--t-body);margin-bottom:10px;}

    /* =====================================================
       RESPONSIVE
       ===================================================== */
    @media (max-width:900px){
        .rates-grid{grid-template-columns:minmax(0,1fr);gap:18px;}
    }

    @media (max-width:640px){
        /* carousel: swipe instead of arrows, a bit tighter */
        .carousel-arrow{display:none;}
        .announce-slide{flex-basis:min(68%, 220px);}

        /* tabs: single swipeable row instead of a messy wrap */
        .status-tabs{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;padding-bottom:4px;-webkit-overflow-scrolling:touch;}
        .status-tabs::-webkit-scrollbar{display:none;}
        .status-tab{flex:0 0 auto;}

        /* TABLES -> STACKED CARDS (activities + rates) */
        .activity-table,.activity-table tbody,.activity-table tr,.activity-table td,
        .rates-table,.rates-table tbody,.rates-table tr,.rates-table td{display:block;width:100%;}
        .activity-table thead,.rates-table thead{
            position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;
        }
        .activity-table,.rates-table{margin-top:12px;}

        .activity-table tr,.rates-table tr{
            border:1px solid var(--line);border-radius:12px;padding:4px 14px;margin-bottom:12px;background:#fff;
        }
        .activity-table td,.rates-table td{
            display:flex;align-items:center;justify-content:space-between;gap:14px;
            padding:10px 0;text-align:right;border-bottom:1px dashed var(--line);
        }
        .activity-table tr:last-child td,.rates-table tr:last-child td{border-bottom:1px dashed var(--line);}
        .activity-table td:last-child,.rates-table td:last-child{border-bottom:none;}

        .activity-table td::before,.rates-table td::before{
            content:attr(data-label);flex:0 0 34%;text-align:left;
            font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:var(--t-soft);
        }
        .activity-table td[data-label=""]::before{display:none;}

        /* action cell: full-width tappable button */
        .activity-table td[data-label=""]{display:block;padding:12px 0 8px;text-align:center;}
        .activity-table td[data-label=""] .activity-action-btn{display:block;width:100%;padding:11px 14px;font-size:13px;}
        .activity-table td[data-label=""] .activity-action-note{display:block;text-align:center;}

        .rate-unit{justify-content:flex-end;}

        /* modal: comfortable on phones, 16px inputs avoid iOS zoom */
        .modal-box{padding:20px;}
        .af-field input{font-size:16px;}
    }

    @media (max-width:380px){
        .unavail-row{padding:10px;gap:10px;}
        .unavail-date{width:46px;height:46px;}
        .af-actions{flex-direction:column-reverse;}
    }
</style>
@endpush

@section('content')

{{-- ---------- Announcements & Promos carousel (admin-editable, dynamic) ---------- --}}
<div class="card section">
    <div class="card-head">
        <div class="card-title">Announcements & Promos</div>
        <div class="dropdown-chip">Tap a poster for details</div>
    </div>

    @if(!empty($posters))
        <div class="announce-carousel">
            <button type="button" class="carousel-arrow" id="announcePrev" aria-label="Previous">‹</button>

            <div class="announce-track" id="announceTrack">
                @foreach($posters as $poster)
                    <div class="announce-slide">
                        <div class="flip-card">
                            <div class="flip-card__inner">
                                <div class="flip-card__front" style="background-image:url('{{ $poster['image'] ? asset($poster['image']) : '' }}')" role="button" tabindex="0">
                                    <span class="flip-card__hint">Tap for details</span>
                                </div>
                                <div class="flip-card__back" role="button" tabindex="0">
                                    <span class="flip-card__status status-{{ strtolower($poster['status'] ?? 'open') }}">{{ $poster['status'] ?? 'Open' }}</span>
                                    <h3>{{ $poster['title'] ?? 'Untitled event' }}</h3>
                                    @if(!empty($poster['date']) || !empty($poster['time']))
                                        <p class="flip-card__meta">📅 {{ $poster['date'] ?? '' }}@if(!empty($poster['time'])) · {{ $poster['time'] }}@endif</p>
                                    @endif
                                    @if(!empty($poster['unit']))
                                        <p class="flip-card__meta">📍 {{ $poster['unit'] }}</p>
                                    @endif
                                    @if(!empty($poster['description']))
                                        <p class="flip-card__desc">{{ $poster['description'] }}</p>
                                    @endif
                                    <span class="flip-card__close">Back to poster</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="carousel-arrow" id="announceNext" aria-label="Next">›</button>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">📣</div>
            <div class="e-title">No announcements right now</div>
            <div class="e-sub">Posters added by the admin will show up here.</div>
        </div>
    @endif
</div>

{{-- ---------- My Activities ---------- --}}
<div class="card section">
    <div class="card-head">
        <div class="card-title">My Activities</div>
    </div>

    <div class="status-tabs" id="statusTabs">
        <div class="status-tab active" data-status="all">All <span class="count">{{ $activityCounts['all'] ?? 0 }}</span></div>
        <div class="status-tab" data-status="ongoing">Ongoing <span class="count">{{ $activityCounts['ongoing'] ?? 0 }}</span></div>
        <div class="status-tab" data-status="rescheduled">Re-scheduled <span class="count">{{ $activityCounts['rescheduled'] ?? 0 }}</span></div>
        <div class="status-tab" data-status="cancelled">Cancelled <span class="count">{{ $activityCounts['cancelled'] ?? 0 }}</span></div>
        <div class="status-tab" data-status="finished">Finished <span class="count">{{ $activityCounts['finished'] ?? 0 }}</span></div>
    </div>

    @if(!empty($activities))
        <div class="table-scroll">
        <table class="activity-table" id="activityTable">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Unit</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                    <tr data-status="{{ $activity['status_key'] }}">
                        <td data-label="Activity"><strong>{{ $activity['activity'] }}</strong></td>
                        <td data-label="Unit"><span class="unit-pill">{{ $activity['unit'] }}</span></td>
                        <td data-label="Date & Time" class="muted">{{ $activity['date_time'] }}</td>
                        <td data-label="Status"><span class="status-pill {{ $activity['status_key'] }}">{{ $activity['status_label'] }}</span></td>
                        <td data-label="">
                            @php($action = $activity['action'])
                            @if($action['type'] === 'link')
                                <a href="{{ $action['route'] }}" class="activity-action-btn">{{ $action['label'] }}</a>
                            @elseif($action['type'] === 'modal')
                                <button type="button" class="activity-action-btn" onclick="openAttachFormModal({{ $action['appointment_id'] }})">{{ $action['label'] }}</button>
                            @elseif($action['type'] === 'text')
                                <span class="activity-action-note">{{ $action['label'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="activity-empty-filter" id="activityEmptyFilter">No activities under this status.</div>
    @else
        <div class="empty-state">
            <div class="e-icon">🗓️</div>
            <div class="e-title">No activities yet</div>
            <div class="e-sub">Once you book an appointment, it'll show up here.</div>
        </div>
    @endif
</div>

{{-- ---------- Unavailable Days & Hours ---------- --}}
<div class="card section">
    <div class="card-head">
        <div class="card-title">Unavailable Days & Hours</div>
        <div class="dropdown-chip">Upcoming</div>
    </div>

    @if(!empty($unavailableSlots))
        <div class="unavail-list">
            @foreach($unavailableSlots as $slot)
                <div class="unavail-row">
                    <div class="unavail-date">
                        <span class="d">{{ $slot['day'] }}</span>
                        <span class="m">{{ $slot['month'] }}</span>
                    </div>
                    <div class="unavail-info">
                        <div class="title">{{ $slot['label'] }}</div>
                        <div class="sub">{{ $slot['time_range'] }}@if($slot['unit']) · {{ $slot['unit'] }}@endif @if($slot['reason']) — {{ $slot['reason'] }}@endif</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">🚫</div>
            <div class="e-title">No blocked dates right now</div>
            <div class="e-sub">All units are open for booking on their regular hours.</div>
        </div>
    @endif
</div>

{{-- ---------- Rates & Activity Types ---------- --}}
<div class="card section">
    <div class="card-head">
        <div class="card-title">Rates & Activity Types</div>
    </div>

    <div class="rates-grid">
        <div>
            <div class="rates-subhead">Rate per hour</div>
            @if(!empty($rates))
                <div class="table-scroll">
                <table class="rates-table">
                    <thead>
                        <tr><th>Unit</th><th>Rate / hr</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        @foreach($rates as $rate)
                            <tr>
                                <td data-label="Unit"><div class="rate-unit"><span class="rate-dot"></span>{{ $rate['unit'] }}</div></td>
                                <td data-label="Rate / hr" class="muted">{{ $rate['rate'] }}</td>
                                <td data-label="Note" class="muted">{{ $rate['note'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="e-icon">₱</div>
                    <div class="e-title">Rates not published yet</div>
                    <div class="e-sub">Check back once the admin sets pricing.</div>
                </div>
            @endif
        </div>

        <div>
            <div class="rates-subhead">Allowed activity types</div>
            @if(!empty($activityTypes))
                <div class="activity-chips">
                    @foreach($activityTypes as $type)
                        <span class="activity-chip">{{ $type }}</span>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="e-icon">🎯</div>
                    <div class="e-title">No activity types listed</div>
                    <div class="e-sub">The admin hasn't published a list yet.</div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Attach Form modal (free-use track) — shared modal, JS points its form
     action at whichever appointment's "Attach Form" button was clicked --}}
<div class="modal-overlay" id="attachFormModal">
    <div class="modal-box">
        <div class="modal-title">Attach Approval Form</div>
        <div class="modal-sub">Upload your Subic Administration Office / Mayor's approval form so Staff can review it.</div>

        <form method="POST" id="attachFormForm" enctype="multipart/form-data">
            @csrf

            <div class="af-field">
                <label>Attachment</label>
                <input type="file" name="attachment" accept="image/*,.pdf" required>
            </div>

            <div class="af-field">
                <label>Date & Time Submitted</label>
                <input type="datetime-local" name="date_time_submitted" required>
            </div>

            <div class="af-field">
                <label>Who Submitted</label>
                <input type="text" name="who_submitted" placeholder="Full name of the person who submitted the form" required>
            </div>

            <div class="af-actions">
                <button type="button" class="af-cancel" onclick="closeModal('attachFormModal')">Cancel</button>
                <button type="submit" class="af-submit">Submit</button>
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
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(o => o.classList.remove('show')); });

    function openAttachFormModal(appointmentId) {
        const form = document.getElementById('attachFormForm');
        form.action = `{{ url('/client/reservations') }}/${appointmentId}/approval-form`;

        // default the submitted-at field to right now, in the local datetime-local format
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        form.querySelector('input[name="date_time_submitted"]').value = now.toISOString().slice(0, 16);

        openModal('attachFormModal');
    }

    // announcements carousel: arrows + flip-to-detail (same pattern as the landing page)
    (function(){
        const track = document.getElementById('announceTrack');
        if (!track) return;
        const prev = document.getElementById('announcePrev');
        const next = document.getElementById('announceNext');
        const slides = Array.from(track.children);

        function scrollByStep(dir){
            const center = track.scrollLeft + track.clientWidth / 2;
            let closest = 0, dist = Infinity;
            slides.forEach((s, i) => {
                const d = Math.abs(center - (s.offsetLeft + s.clientWidth / 2));
                if (d < dist){ dist = d; closest = i; }
            });
            const i = Math.min(Math.max(closest + dir, 0), slides.length - 1);
            slides[i].scrollIntoView({ behavior:'smooth', inline:'start', block:'nearest' });
        }
        if (prev) prev.addEventListener('click', () => scrollByStep(-1));
        if (next) next.addEventListener('click', () => scrollByStep(1));

        slides.forEach((slide) => {
            const card = slide.querySelector('.flip-card');
            if (!card) return;
            const front = card.querySelector('.flip-card__front');
            const back = card.querySelector('.flip-card__back');
            const toggle = () => card.classList.toggle('is-flipped');
            const onKey = (e) => { if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); toggle(); } };
            front.addEventListener('click', toggle);
            front.addEventListener('keydown', onKey);
            back.addEventListener('click', toggle);
            back.addEventListener('keydown', onKey);
        });
    })();

    // my activities: status tab filter (client-side, no reload)
    (function(){
        const tabs = document.querySelectorAll('#statusTabs .status-tab');
        const rows = document.querySelectorAll('#activityTable tbody tr');
        const emptyMsg = document.getElementById('activityEmptyFilter');
        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');
                const status = tab.dataset.status;
                let visible = 0;
                rows.forEach((row) => {
                    const show = (status === 'all' || row.dataset.status === status);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                if (emptyMsg) emptyMsg.style.display = visible === 0 ? 'block' : 'none';
            });
        });
    })();
</script>
@endpush