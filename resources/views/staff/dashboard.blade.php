@extends('layouts.staff')

@section('title', 'Dashboard')

@push('styles')
<style>
    .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:18px;}
    @media (max-width:900px){ .grid-3{grid-template-columns:repeat(2,1fr);} }
    @media (max-width:640px){ .grid-3{grid-template-columns:1fr;} }

    .stat-value{font-size:26px;font-weight:800;letter-spacing:-.4px;margin:2px 0 6px;}
    .stat-label{font-size:12.5px;font-weight:700;color:var(--ink-600);}

    /* ---------- Announcements carousel (flip cards) ---------- */
    .announce-carousel{display:flex;align-items:center;gap:10px;}
    .announce-track{
        display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;
        scroll-behavior:smooth;padding:4px 2px 10px;scrollbar-width:none;flex:1;min-width:0;
    }
    .announce-track::-webkit-scrollbar{display:none;}
    .announce-slide{flex:0 0 auto;width:min(30%, 220px);scroll-snap-align:start;perspective:1400px;}
    @media (max-width:900px){ .announce-slide{width:min(46%, 220px);} }
    @media (max-width:600px){ .announce-slide{width:min(70%, 220px);} }

    .flip-card{position:relative;width:100%;aspect-ratio:3/4;cursor:pointer;}
    .flip-card__inner{position:relative;width:100%;height:100%;transform-style:preserve-3d;transition:transform .55s cubic-bezier(.4,.2,.2,1);}
    .flip-card.is-flipped .flip-card__inner{transform:rotateY(180deg);}
    .flip-card__front,.flip-card__back{position:absolute;inset:0;backface-visibility:hidden;-webkit-backface-visibility:hidden;border-radius:14px;box-shadow:0 10px 24px rgba(14,28,23,.18);}
    .flip-card__front{background-size:cover;background-position:center;background-color:var(--green-800);display:flex;align-items:flex-end;}
    .flip-card__front::after{content:'';position:absolute;inset:0;border-radius:inherit;background:linear-gradient(to top, rgba(7,35,26,.85), transparent 50%);}
    .flip-card__hint{position:relative;z-index:1;width:100%;text-align:center;padding:.55rem .6rem;font-size:11.5px;font-weight:700;color:#fff;}
    .flip-card__back{transform:rotateY(180deg);background:var(--green-950);color:#fff;padding:16px 14px;display:flex;flex-direction:column;overflow:hidden;}
    .flip-card__status{align-self:flex-start;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;padding:3px 9px;border-radius:999px;margin-bottom:9px;}
    .flip-card__status.status-open{background:rgba(31,174,116,.25);color:#4ade80;}
    .flip-card__status.status-full{background:rgba(239,141,61,.25);color:var(--orange-500);}
    .flip-card__status.status-ongoing{background:rgba(255,255,255,.15);color:#fff;}
    .flip-card__status.status-cancelled{background:rgba(180,80,44,.28);color:#e79471;}
    .flip-card__status.status-finished{background:rgba(255,255,255,.1);color:#c7d6cf;}
    .flip-card__back h3{font-size:14px;font-weight:800;line-height:1.3;margin-bottom:7px;}
    .flip-card__meta{font-size:11.5px;color:rgba(255,255,255,.75);margin-bottom:4px;}
    .flip-card__desc{font-size:11.5px;line-height:1.5;color:rgba(255,255,255,.6);margin-top:6px;flex:1;overflow-y:auto;}
    .flip-card__close{align-self:flex-start;margin-top:10px;font-size:11px;font-weight:700;color:var(--orange-500);border-bottom:1px solid rgba(239,141,61,.4);}

    .carousel-arrow{
        flex:0 0 auto;width:34px;height:34px;border-radius:50%;background:var(--green-050);
        border:1px solid var(--line);color:var(--ink-900);font-size:16px;display:flex;
        align-items:center;justify-content:center;cursor:pointer;
    }
    .carousel-arrow:hover{background:var(--green-100);}

    /* ---------- Pending queue preview ---------- */
    .queue-table{width:100%;border-collapse:collapse;}
    .queue-table th{text-align:left;font-size:11px;color:var(--ink-400);font-weight:700;padding:8px 10px;border-bottom:1px solid var(--line);}
    .queue-table td{padding:12px 10px;font-size:13px;border-bottom:1px solid var(--line);}
    .queue-table tr:last-child td{border-bottom:none;}
    .unit-pill{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);}
    .type-pill{font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#eef2f0;color:var(--ink-600);}
    .view-all-link{font-size:12.5px;font-weight:700;color:var(--green-700);}
</style>
@endpush

@section('content')

{{-- ---------- Announcements & Promos carousel ---------- --}}
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <div class="card-title">Announcements & Promos</div>
        <div class="dropdown-chip">Tap a poster for details</div>
    </div>

    @if(!empty($posters))
        <div class="announce-carousel">
            <button type="button" class="carousel-arrow" id="announcePrev">‹</button>
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
            <button type="button" class="carousel-arrow" id="announceNext">›</button>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">📣</div>
            <div class="e-title">No announcements right now</div>
            <div class="e-sub">Posters added by the admin will show up here.</div>
        </div>
    @endif
</div>

{{-- ---------- Stat cards ---------- --}}
<div class="grid-3">
    <div class="card">
        <div class="stat-label">Pending Verification</div>
        <div class="stat-value">{{ $pendingCount }}</div>
        <div class="muted" style="font-size:12px;">Awaiting your review</div>
    </div>
    <div class="card">
        <div class="stat-label">Verified Today</div>
        <div class="stat-value">{{ $verifiedTodayCount }}</div>
        <div class="muted" style="font-size:12px;">Moved forward today</div>
    </div>
    <div class="card">
        <div class="stat-label">This Week's Events</div>
        <div class="stat-value">{{ $thisWeekCount }}</div>
        <div class="muted" style="font-size:12px;">Across all facilities</div>
    </div>
</div>

{{-- ---------- Pending queue preview ---------- --}}
<div class="card">
    <div class="card-head">
        <div class="card-title">Needs Verification</div>
        <a href="{{ route('staff.verify.index') }}" class="view-all-link">View all →</a>
    </div>

    @if(!empty($pendingPreview))
        <div class="table-scroll">
        <table class="queue-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Activity</th>
                    <th>Facility</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingPreview as $row)
                    <tr>
                        <td><strong>{{ $row['client_name'] }}</strong></td>
                        <td>{{ $row['activity'] }}</td>
                        <td><span class="unit-pill">{{ $row['facility'] }}</span></td>
                        <td class="muted">{{ $row['date_time'] }}</td>
                        <td><span class="type-pill">{{ $row['type_label'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">✔</div>
            <div class="e-title">Nothing pending</div>
            <div class="e-sub">New appointment and reservation requests will show up here.</div>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
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
</script>
@endpush