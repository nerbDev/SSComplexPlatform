<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Subic Sports Complex · Walk-in Display</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --green-950:#07231a;--green-800:#0f4433;--green-700:#146245;--green-600:#178a5e;--green-500:#1fae74;
            --green-100:#e8f7ef;--orange-500:#ef8d3d;--gray-500:#8a99a2;--ink:#eaf6f0;
            --glass:rgba(7,35,26,.58);--glass-line:rgba(255,255,255,.16);
        }
        *{box-sizing:border-box;}
        html{font-size:clamp(14px,1.02vw,26px);}
        html,body{height:100%;margin:0;overflow:hidden;}
        body{
            font-family:'Manrope',-apple-system,BlinkMacSystemFont,sans-serif;color:var(--ink);
            background:var(--green-950);-webkit-font-smoothing:antialiased;cursor:none;
            display:flex;flex-direction:column;
        }
        body.show-cursor{cursor:default;}
        .bg{position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(7,35,26,.72),rgba(7,35,26,.86)),url('{{ $bg }}') center/cover no-repeat;}

        /* header */
        .bar{display:flex;align-items:center;gap:1rem;padding:1rem 1.6rem;flex-shrink:0;}
        .bar .logo{width:3.2rem;height:3.2rem;border-radius:50%;background:#fff;object-fit:contain;flex-shrink:0;}
        .bar .t1{font-size:1.45rem;font-weight:800;letter-spacing:.2px;line-height:1.1;}
        .bar .t2{font-size:.85rem;font-weight:600;opacity:.7;margin-top:.15rem;}
        .clock{margin-left:auto;text-align:right;}
        .clock .time{font-size:2rem;font-weight:800;letter-spacing:-.5px;line-height:1;font-variant-numeric:tabular-nums;}
        .clock .date{font-size:.85rem;font-weight:600;opacity:.75;margin-top:.2rem;}

        /* stage */
        .stage{position:relative;flex:1;min-height:0;margin:0 1.6rem;}
        .scene{
            position:absolute;inset:0;display:flex;flex-direction:column;gap:1rem;
            opacity:0;transform:translateX(6%);pointer-events:none;
            transition:opacity .6s ease, transform .7s cubic-bezier(.22,.8,.3,1);
        }
        .scene.is-active{opacity:1;transform:none;pointer-events:auto;}
        .scene.is-prev{transform:translateX(-6%);}
        .scene-title{font-size:1.15rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;opacity:.85;display:flex;align-items:center;gap:.6rem;flex-shrink:0;}
        .scene-title i{display:block;width:.45rem;height:1.2rem;border-radius:3px;background:var(--green-500);}
        .panel{background:var(--glass);border:1px solid var(--glass-line);border-radius:1.4rem;backdrop-filter:blur(18px) saturate(160%);-webkit-backdrop-filter:blur(18px) saturate(160%);box-shadow:0 20px 50px -20px rgba(0,0,0,.6);}

        /* posters */
        .poster-view{flex:1;min-height:0;overflow:hidden;position:relative;}
        .poster-track{display:flex;height:100%;transition:transform .8s cubic-bezier(.22,.8,.3,1);}
        .poster-slide{flex:0 0 100%;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:0;height:100%;}
        .poster-img{position:relative;overflow:hidden;background:#0b3327;display:flex;align-items:center;justify-content:center;border-radius:1.4rem 0 0 1.4rem;}
        .poster-img::before{content:'';position:absolute;inset:-10%;background:var(--bgimg) center/cover;filter:blur(28px) brightness(.55);}
        .poster-img img{position:relative;max-width:100%;max-height:100%;object-fit:contain;box-shadow:0 12px 40px rgba(0,0,0,.5);}
        .poster-img .noimg{position:relative;font-size:4rem;opacity:.6;}
        .poster-info{padding:2.2rem 2.4rem;display:flex;flex-direction:column;justify-content:center;gap:1rem;min-width:0;}
        .poster-info h2{margin:0;font-size:2.3rem;line-height:1.1;letter-spacing:-.5px;}
        .poster-info .meta{display:flex;flex-direction:column;gap:.5rem;font-size:1.15rem;font-weight:600;}
        .poster-info .meta span{opacity:.9;}
        .poster-info p{margin:0;font-size:1.05rem;line-height:1.55;opacity:.85;display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;overflow:hidden;}
        .poster-dots{position:absolute;left:0;right:0;bottom:.7rem;display:flex;justify-content:center;gap:.45rem;}
        .poster-dots i{width:.55rem;height:.55rem;border-radius:50%;background:rgba(255,255,255,.35);transition:all .3s;}
        .poster-dots i.on{width:1.6rem;border-radius:99px;background:#fff;}
        .welcome{flex:0 0 100%;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;gap:1rem;padding:2rem;}
        .welcome h2{margin:0;font-size:3rem;letter-spacing:-1px;}
        .welcome p{margin:0;font-size:1.3rem;opacity:.8;max-width:42rem;line-height:1.5;}

        .pill{display:inline-block;font-weight:800;font-size:.8rem;padding:.35rem .8rem;border-radius:999px;letter-spacing:.02em;white-space:nowrap;}
        .pill.free{background:rgba(31,174,116,.25);color:#7ff0bf;border:1px solid rgba(31,174,116,.5);}
        .pill.in_use{background:rgba(239,141,61,.25);color:#ffc08a;border:1px solid rgba(239,141,61,.55);}
        .pill.blocked,.pill.maintenance{background:rgba(138,153,162,.25);color:#cdd6db;border:1px solid rgba(138,153,162,.5);}
        .pill.open{background:rgba(31,174,116,.25);color:#7ff0bf;border:1px solid rgba(31,174,116,.5);}
        .pill.full{background:rgba(239,141,61,.25);color:#ffc08a;border:1px solid rgba(239,141,61,.55);}
        .pill.ongoing{background:rgba(110,168,255,.25);color:#b9d6ff;border:1px solid rgba(110,168,255,.55);}

        /* schedule */
        .sched{flex:1;min-height:0;display:grid;grid-template-rows:repeat(var(--rows,4),minmax(0,1fr));gap:.8rem;}
        .srow{padding:.9rem 1.2rem;display:grid;grid-template-columns:15rem minmax(0,1fr);gap:1.4rem;align-items:center;}
        .sname{font-size:1.3rem;font-weight:800;line-height:1.15;}
        .sloc{font-size:.8rem;opacity:.65;font-weight:600;margin:.15rem 0 .45rem;}
        .tl{position:relative;height:1.5rem;border-radius:.6rem;background:rgba(255,255,255,.09);overflow:hidden;}
        .tl .seg{position:absolute;top:0;bottom:0;border-radius:.4rem;}
        .tl .seg.event{background:var(--orange-500);} .tl .seg.event.done{background:rgba(239,141,61,.4);}
        .tl .seg.maintenance{background:#6f7f88;} .tl .seg.blocked{background:repeating-linear-gradient(45deg,#8a99a2,#8a99a2 6px,#6f7f88 6px,#6f7f88 12px);}
        .tl .now{position:absolute;top:-2px;bottom:-2px;width:3px;background:#fff;box-shadow:0 0 8px #fff;z-index:2;}
        .ticks{display:flex;justify-content:space-between;font-size:.68rem;opacity:.55;font-weight:700;margin-top:.25rem;}
        .items{display:flex;flex-wrap:wrap;gap:.4rem .6rem;margin-top:.45rem;}
        .items span{font-size:.85rem;font-weight:700;padding:.2rem .6rem;border-radius:.5rem;background:rgba(255,255,255,.1);}
        .items span.event{border-left:4px solid var(--orange-500);} .items span.maintenance,.items span.blocked{border-left:4px solid var(--gray-500);}
        .items .none{background:none;opacity:.7;font-weight:600;border:0;padding-left:0;}

        /* areas */
        .areas{flex:1;min-height:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));gap:1rem;}
        .acard{display:flex;flex-direction:column;overflow:hidden;min-height:0;}
        .acard .pic{height:42%;min-height:7rem;background:linear-gradient(135deg,var(--green-700),var(--green-950));display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:800;opacity:.95;position:relative;}
        .acard .pic img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
        .acard .body{padding:1rem 1.2rem 1.2rem;display:flex;flex-direction:column;gap:.5rem;min-height:0;}
        .acard h3{margin:0;font-size:1.4rem;letter-spacing:-.3px;}
        .acard .row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;font-size:.9rem;font-weight:700;opacity:.9;}
        .acard p{margin:0;font-size:.95rem;line-height:1.5;opacity:.8;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;}

        /* map */
        .mapwrap{flex:1;min-height:0;display:grid;grid-template-columns:minmax(0,1.9fr) minmax(0,1fr);gap:1rem;}
        .mapbox{padding:1rem;display:flex;align-items:center;justify-content:center;min-height:0;}
        .mapbox svg,.mapbox img{width:100%;height:100%;max-height:100%;object-fit:contain;}
        .legend{padding:1.2rem 1.4rem;display:flex;flex-direction:column;gap:.8rem;overflow:hidden;}
        .legend h4{margin:0;font-size:1rem;letter-spacing:.04em;text-transform:uppercase;opacity:.8;}
        .lrow{display:flex;align-items:center;gap:.7rem;font-size:.95rem;font-weight:700;}
        .lrow i{width:.9rem;height:.9rem;border-radius:.3rem;flex-shrink:0;}
        .rrow{display:flex;flex-direction:column;gap:.1rem;padding:.55rem 0;border-top:1px solid var(--glass-line);}
        .rrow b{font-size:1rem;} .rrow small{font-size:.8rem;opacity:.75;font-weight:600;}

        /* footer */
        .foot{flex-shrink:0;padding:.8rem 1.6rem 1rem;display:flex;align-items:center;gap:1rem;}
        .dots{display:flex;gap:.6rem;}
        .dotbtn{border:1px solid var(--glass-line);background:rgba(255,255,255,.08);color:var(--ink);font-family:inherit;font-weight:700;font-size:.85rem;padding:.45rem .9rem;border-radius:999px;cursor:pointer;}
        .dotbtn.on{background:var(--green-600);border-color:var(--green-500);}
        .progress{flex:1;height:.35rem;border-radius:99px;background:rgba(255,255,255,.12);overflow:hidden;}
        .progress i{display:block;height:100%;width:0;background:var(--green-500);}
        .progress i.run{animation:fill linear forwards;}
        @keyframes fill{from{width:0}to{width:100%}}
        .updated{font-size:.75rem;opacity:.5;font-weight:600;white-space:nowrap;}

        @media (max-width:900px){
            html{font-size:14px;}
            .poster-slide{grid-template-columns:1fr;grid-template-rows:45% 55%;}
            .poster-img{border-radius:1.4rem 1.4rem 0 0;}
            .srow{grid-template-columns:1fr;gap:.5rem;}
            .mapwrap{grid-template-columns:1fr;}
            .updated{display:none;}
        }
    </style>
</head>
<body>
<div class="bg"></div>

<header class="bar">
    @if($logo)<img class="logo" src="{{ $logo }}" alt="">@endif
    <div>
        <div class="t1">Subic Sports Complex</div>
        <div class="t2">Wawandue, Subic, Zambales</div>
    </div>
    <div class="clock"><div class="time" id="clock">--:--</div><div class="date" id="today"></div></div>
</header>

<main class="stage" id="stage">
    <section class="scene" data-scene="posters" data-label="Events & Announcements">
        <div class="scene-title"><i></i> What’s happening</div>
        <div class="panel poster-view" id="posterView"></div>
    </section>

    <section class="scene" data-scene="schedule" data-label="Today’s Schedule">
        <div class="scene-title"><i></i> Today’s schedule · <span id="schedDate"></span></div>
        <div class="sched" id="sched"></div>
    </section>

    <section class="scene" data-scene="areas" data-label="Function Areas">
        <div class="scene-title"><i></i> Function areas</div>
        <div class="areas" id="areas"></div>
    </section>

    <section class="scene" data-scene="map" data-label="Facility Map">
        <div class="scene-title"><i></i> Facility map</div>
        <div class="mapwrap">
            <div class="panel mapbox" id="mapbox"></div>
            <div class="panel legend" id="legend"></div>
        </div>
    </section>
</main>

<footer class="foot">
    <div class="dots" id="dots"></div>
    <div class="progress"><i id="bar"></i></div>
    <div class="updated" id="updated"></div>
</footer>

<script>
    let DATA = @json($data);
    const DATA_URL = @json($dataUrl);
    const MAP_IMAGE = @json($mapImage);

    const SCENE_SECONDS = 16;      // each info scene stays this long
    const POSTER_SECONDS = 7;      // each poster stays this long (posters scene lasts n × this, min SCENE_SECONDS)
    const REFRESH_MS = 60 * 1000;  // re-fetch /display/data
    const RELOAD_MS = 6 * 60 * 60 * 1000; // full page reload (keeps the screen fresh for days)

    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const fmt12 = t => { const [h, m] = t.split(':').map(Number); const hh = h % 24; return ((hh % 12) || 12) + (m ? ':' + String(m).padStart(2, '0') : '') + (hh >= 12 && h < 24 ? ' PM' : ' AM'); };
    const nowMin = () => { const d = new Date(); return d.getHours() * 60 + d.getMinutes(); };

    const scenes = [...document.querySelectorAll('.scene')];
    let cur = -1, timer = null, posterIdx = 0, posterTimer = null;

    // ---------- status of a facility right now (computed in the browser so it stays live)
    function statusFor(f){
        const n = nowMin();
        const live = f.items.filter(i => toMin(i.start) <= n && n < toMin(i.end) && !i.done);
        const blocked = live.find(i => i.type === 'blocked');
        const maint = live.find(i => i.type === 'maintenance');
        const ev = live.find(i => i.type === 'event');
        const next = f.items.filter(i => toMin(i.start) > n && !i.done).sort((a, b) => toMin(a.start) - toMin(b.start))[0];
        if (blocked) return { key: 'blocked', label: 'Not available', detail: blocked.label, until: blocked.end, next };
        if (maint)   return { key: 'maintenance', label: 'Maintenance', detail: 'Facility maintenance', until: maint.end, next };
        if (ev)      return { key: 'in_use', label: 'In use', detail: ev.label, until: ev.end, next };
        return { key: 'free', label: 'Free now', detail: next ? `Next: ${next.label} at ${fmt12(next.start)}` : 'No more bookings today', until: null, next };
    }

    // ---------- scenes
    function renderPosters(){
        const v = $('posterView'); const list = DATA.posters || [];
        if (!list.length) {
            v.innerHTML = `<div class="poster-track"><div class="welcome"><h2>Welcome to Subic Sports Complex</h2><p>Book function rooms, the lobby and the whole court online at our website, or visit the front desk. Check today’s schedule and the facility map on the next screens.</p></div></div>`;
            return;
        }
        v.style.setProperty('--bgimg', 'none');
        v.innerHTML = `<div class="poster-track" id="pTrack">${list.map(p => `
            <div class="poster-slide">
                <div class="poster-img" style="--bgimg:${p.image ? `url('${esc(p.image)}')` : 'none'}">
                    ${p.image ? `<img src="${esc(p.image)}" alt="">` : '<div class="noimg">🏟️</div>'}
                </div>
                <div class="poster-info">
                    <div><span class="pill ${esc(p.status)}">${esc(p.status_label)}</span></div>
                    <h2>${esc(p.title)}</h2>
                    <div class="meta">
                        ${p.date ? `<span>📅 ${esc(p.date)}</span>` : ''}
                        ${p.time ? `<span>🕒 ${esc(p.time)}</span>` : ''}
                        ${p.unit ? `<span>📍 ${esc(p.unit)}</span>` : ''}
                    </div>
                    ${p.description ? `<p>${esc(p.description)}</p>` : ''}
                </div>
            </div>`).join('')}</div>
            <div class="poster-dots" id="pDots">${list.map((_, i) => `<i class="${i === 0 ? 'on' : ''}"></i>`).join('')}</div>`;
        posterIdx = Math.min(posterIdx, list.length - 1);
        showPoster(posterIdx);
    }

    function showPoster(i){
        const t = $('pTrack'); if (!t) return;
        posterIdx = i; t.style.transform = `translateX(-${i * 100}%)`;
        document.querySelectorAll('#pDots i').forEach((d, k) => d.classList.toggle('on', k === i));
    }

    function renderSchedule(){
        $('schedDate').textContent = DATA.today;
        const fs = DATA.facilities || [];
        $('sched').style.setProperty('--rows', Math.max(fs.length, 1));
        if (!fs.length) { $('sched').innerHTML = '<div class="panel srow"><div class="sname">No facilities set up yet.</div></div>'; return; }

        let lo = 6 * 60, hi = 22 * 60;
        fs.forEach(f => f.items.forEach(i => { lo = Math.min(lo, Math.floor(toMin(i.start) / 60) * 60); hi = Math.max(hi, Math.min(24 * 60, Math.ceil(toMin(i.end) / 60) * 60)); }));
        const span = hi - lo, n = nowMin();
        const ticks = []; for (let m = lo; m <= hi; m += 120) ticks.push(`<span>${fmt12(String(Math.floor(m / 60)).padStart(2, '0') + ':00')}</span>`);

        $('sched').innerHTML = fs.map(f => {
            const s = statusFor(f);
            const segs = f.items.map(i => {
                const a = Math.max(toMin(i.start), lo), b = Math.min(toMin(i.end), hi);
                return `<div class="seg ${esc(i.type)}${i.done ? ' done' : ''}" style="left:${(a - lo) / span * 100}%;width:${Math.max(b - a, 0) / span * 100}%"></div>`;
            }).join('');
            const nowLine = (n >= lo && n <= hi) ? `<div class="now" style="left:${(n - lo) / span * 100}%"></div>` : '';
            const items = f.items.length
                ? f.items.map(i => `<span class="${esc(i.type)}">${fmt12(i.start)} – ${i.end === '24:00' ? 'end of day' : fmt12(i.end)} · ${esc(i.label)}${i.done ? ' ✓' : ''}</span>`).join('')
                : '<span class="none">No bookings today — available all day</span>';
            return `<div class="panel srow">
                <div><div class="sname">${esc(f.name)}</div><div class="sloc">${esc(f.location || '')}</div><span class="pill ${s.key}">${s.label}${s.until ? ' until ' + fmt12(s.until) : ''}</span></div>
                <div><div class="tl">${segs}${nowLine}</div><div class="ticks">${ticks.join('')}</div><div class="items">${items}</div></div>
            </div>`;
        }).join('');
    }

    function renderAreas(){
        const fs = DATA.facilities || [];
        $('areas').innerHTML = fs.map(f => {
            const s = statusFor(f);
            return `<div class="panel acard">
                <div class="pic">${f.image ? `<img src="${esc(f.image)}" alt="">` : esc(f.name.split(' ').map(w => w[0]).join('').slice(0, 3))}</div>
                <div class="body">
                    <h3>${esc(f.name)}</h3>
                    <div class="row"><span>👥 Up to ${Number(f.capacity).toLocaleString()} guests</span>${f.location ? `<span>📍 ${esc(f.location)}</span>` : ''}</div>
                    <div><span class="pill ${s.key}">${s.label}</span></div>
                    <p>${esc(f.description || 'Available for events, meetings and sports activities. Ask the front desk or book online.')}</p>
                </div>
            </div>`;
        }).join('') || '<div class="panel acard"><div class="body"><h3>No facilities set up yet.</h3></div></div>';
    }

    // Schematic floor plan (used until a real floor-plan image is placed at public/images/map/facility-map.png)
    const ROOMS = {
        'function-1': { x: 40,  y: 62,  w: 450, h: 150, floor: '2nd floor' },
        'function-2': { x: 510, y: 62,  w: 450, h: 150, floor: '2nd floor' },
        'function-3': { x: 40,  y: 262, w: 450, h: 130, floor: '1st floor' },
        'lobby-and-whole-court': { x: 40, y: 442, w: 920, h: 130, floor: 'Ground floor' },
    };
    const COLORS = { free: '#1fae74', in_use: '#ef8d3d', blocked: '#8a99a2', maintenance: '#8a99a2' };

    function renderMap(){
        const fs = DATA.facilities || [];
        if (MAP_IMAGE) {
            $('mapbox').innerHTML = `<img src="${esc(MAP_IMAGE)}" alt="Facility map">`;
        } else {
            const rooms = fs.filter(f => ROOMS[f.slug]).map(f => {
                const r = ROOMS[f.slug], s = statusFor(f), c = COLORS[s.key];
                return `<g>
                    <rect x="${r.x}" y="${r.y}" width="${r.w}" height="${r.h}" rx="18" fill="${c}" fill-opacity=".9" stroke="#fff" stroke-opacity=".6" stroke-width="2"/>
                    <text x="${r.x + r.w / 2}" y="${r.y + r.h / 2 - 12}" text-anchor="middle" fill="#fff" font-size="30" font-weight="800">${esc(f.name)}</text>
                    <text x="${r.x + r.w / 2}" y="${r.y + r.h / 2 + 18}" text-anchor="middle" fill="#fff" font-size="21" font-weight="700">${esc(s.label)} · ${Number(f.capacity).toLocaleString()} pax</text>
                </g>`;
            }).join('');
            $('mapbox').innerHTML = `<svg viewBox="0 0 1000 620" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Facility floor plan">
                <rect x="10" y="10" width="980" height="600" rx="26" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.18)"/>
                <text x="40" y="46" fill="#cdeedd" font-size="19" font-weight="800" letter-spacing="2">2ND FLOOR</text>
                <text x="40" y="246" fill="#cdeedd" font-size="19" font-weight="800" letter-spacing="2">1ST FLOOR</text>
                <text x="40" y="426" fill="#cdeedd" font-size="19" font-weight="800" letter-spacing="2">GROUND FLOOR</text>
                <rect x="510" y="262" width="450" height="130" rx="18" fill="rgba(255,255,255,.07)" stroke="rgba(255,255,255,.2)" stroke-dasharray="8 8"/>
                <text x="735" y="334" text-anchor="middle" fill="#cdd6db" font-size="22" font-weight="700">Stairs · Corridor</text>
                ${rooms}
                <text x="500" y="604" text-anchor="middle" fill="#cdeedd" font-size="17" font-weight="700">▲ MAIN ENTRANCE</text>
            </svg>`;
        }

        $('legend').innerHTML = `<h4>Right now</h4>
            <div class="lrow"><i style="background:${COLORS.free}"></i> Free</div>
            <div class="lrow"><i style="background:${COLORS.in_use}"></i> In use</div>
            <div class="lrow"><i style="background:${COLORS.blocked}"></i> Not available / maintenance</div>
            ${fs.map(f => { const s = statusFor(f); return `<div class="rrow"><b>${esc(f.name)}</b><small>${esc(f.location || '')}</small><small>${s.label}${s.until ? ' until ' + fmt12(s.until) : ''} — ${esc(s.detail)}</small></div>`; }).join('')}`;
    }

    function renderAll(){ renderPosters(); renderSchedule(); renderAreas(); renderMap(); }

    // ---------- rotation
    function sceneSeconds(i){
        if (scenes[i].dataset.scene === 'posters') return Math.max(SCENE_SECONDS, (DATA.posters || []).length * POSTER_SECONDS);
        return SCENE_SECONDS;
    }

    function go(i){
        i = (i + scenes.length) % scenes.length;
        clearTimeout(timer); clearInterval(posterTimer);
        scenes.forEach((s, k) => { s.classList.toggle('is-active', k === i); s.classList.toggle('is-prev', k < i); });
        cur = i;
        document.querySelectorAll('.dotbtn').forEach((d, k) => d.classList.toggle('on', k === i));

        if (scenes[i].dataset.scene === 'posters') {
            posterIdx = 0; showPoster(0);
            const n = (DATA.posters || []).length;
            if (n > 1) posterTimer = setInterval(() => showPoster((posterIdx + 1) % n), POSTER_SECONDS * 1000);
        }

        const secs = sceneSeconds(i), bar = $('bar');
        bar.classList.remove('run'); void bar.offsetWidth; bar.style.animationDuration = secs + 's'; bar.classList.add('run');
        timer = setTimeout(() => go(i + 1), secs * 1000);
    }

    // ---------- clock, refresh, input
    function tick(){
        const d = new Date();
        $('clock').textContent = d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
        $('today').textContent = d.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    }

    async function refresh(){
        try {
            const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (!res.ok) throw new Error(res.status);
            DATA = await res.json();
            renderAll();
            $('updated').textContent = 'Updated ' + new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
        } catch (e) { /* keep showing the last data; try again next minute */ }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') go(cur + 1);
        if (e.key === 'ArrowLeft') go(cur - 1);
    });
    let touchX = null;
    document.addEventListener('touchstart', e => { touchX = e.changedTouches[0].clientX; }, { passive: true });
    document.addEventListener('touchend', e => {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX; touchX = null;
        if (Math.abs(dx) > 60) go(cur + (dx < 0 ? 1 : -1));
    }, { passive: true });
    document.addEventListener('mousemove', () => { document.body.classList.add('show-cursor'); clearTimeout(window.__c); window.__c = setTimeout(() => document.body.classList.remove('show-cursor'), 3000); });

    $('dots').innerHTML = scenes.map((s, i) => `<button type="button" class="dotbtn" onclick="go(${i})">${esc(s.dataset.label)}</button>`).join('');
    renderAll(); tick(); setInterval(tick, 1000);
    setInterval(() => { renderSchedule(); renderAreas(); renderMap(); }, 30 * 1000); // keep "free now / in use" live between fetches
    setInterval(refresh, REFRESH_MS);
    setTimeout(() => location.reload(), RELOAD_MS);
    go(0);
</script>
</body>
</html>