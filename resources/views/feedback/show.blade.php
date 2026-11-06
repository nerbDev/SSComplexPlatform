<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Event feedback · Subic Sports Complex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --green-950:#07231a;--green-700:#146245;--green-600:#178a5e;--green-500:#1fae74;
            --green-100:#e8f7ef;--green-050:#f3fbf7;--orange-500:#ef8d3d;--orange-100:#fdece0;
            --ink-900:#0e1c17;--ink-600:#34443d;--ink-400:#55655e;--line:#e4ece8;
        }
        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{
            margin:0;font-family:'Manrope',-apple-system,BlinkMacSystemFont,sans-serif;color:var(--ink-900);
            background:linear-gradient(180deg, rgba(7,35,26,.66), rgba(7,35,26,.78)), url('{{ asset('images/hero/SSCfrontview.jpg') }}') center / cover no-repeat fixed, var(--green-950);
            display:flex;align-items:center;justify-content:center;padding:16px;
            -webkit-font-smoothing:antialiased;
        }
        .shell{
            width:100%;max-width:520px;height:min(760px, calc(100vh - 32px));display:flex;flex-direction:column;
            background:rgba(255,255,255,.88);backdrop-filter:blur(22px) saturate(160%);-webkit-backdrop-filter:blur(22px) saturate(160%);
            border:1px solid rgba(255,255,255,.5);border-radius:22px;box-shadow:0 24px 60px -18px rgba(0,0,0,.55);overflow:hidden;
        }
        .head{padding:16px 18px;background:var(--green-950);color:#fff;display:flex;align-items:center;gap:12px;}
        .head .logo{width:38px;height:38px;border-radius:50%;background:#fff;object-fit:contain;flex-shrink:0;}
        .head .t{font-weight:800;font-size:14.5px;line-height:1.2;}
        .head .s{font-size:11.5px;opacity:.7;font-weight:600;margin-top:2px;}
        .head .switch{margin-left:auto;font-size:11.5px;font-weight:700;color:#cdeedd;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:999px;padding:6px 11px;cursor:pointer;font-family:inherit;white-space:nowrap;}

        .chat{flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth;}
        .msg{max-width:84%;padding:11px 14px;border-radius:16px;font-size:14px;line-height:1.5;animation:pop .2s ease;word-wrap:break-word;white-space:pre-line;}
        .msg.bot{align-self:flex-start;background:#fff;border:1px solid var(--line);border-bottom-left-radius:5px;}
        .msg.me{align-self:flex-end;background:var(--green-600);color:#fff;border-bottom-right-radius:5px;}
        .typing{align-self:flex-start;background:#fff;border:1px solid var(--line);border-radius:16px;border-bottom-left-radius:5px;padding:12px 16px;display:flex;gap:4px;}
        .typing i{width:6px;height:6px;border-radius:50%;background:var(--ink-400);animation:blink 1s infinite;}
        .typing i:nth-child(2){animation-delay:.15s;} .typing i:nth-child(3){animation-delay:.3s;}
        @keyframes blink{0%,60%,100%{opacity:.25}30%{opacity:1}}
        @keyframes pop{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}

        .tray{padding:12px 14px 14px;border-top:1px solid var(--line);background:rgba(255,255,255,.7);}
        .chips{display:flex;flex-wrap:wrap;gap:8px;}
        .chip{border:1px solid #cdeedd;background:var(--green-100);color:var(--green-700);font-family:inherit;font-weight:700;font-size:13px;padding:9px 13px;border-radius:999px;cursor:pointer;transition:background .15s;}
        .chip:hover{background:#d6f1e3;}
        .chip.on{background:var(--green-600);color:#fff;border-color:var(--green-600);}
        .chip.star{font-size:15px;padding:8px 12px;}
        .chip.go{background:var(--ink-900);color:#fff;border-color:var(--ink-900);}
        .chip.ghost{background:#fff;color:var(--ink-600);border-color:var(--line);}
        .row{display:flex;gap:8px;}
        .row input{flex:1;padding:11px 13px;border:1px solid var(--line);border-radius:12px;font-family:inherit;font-size:14px;background:#fff;color:var(--ink-900);min-width:0;}
        .row button{padding:0 16px;border:none;border-radius:12px;background:var(--green-600);color:#fff;font-weight:800;font-family:inherit;cursor:pointer;}
        .tray .hint{font-size:11.5px;color:var(--ink-400);font-weight:600;margin-top:8px;}

        /* plain form fallback */
        .form{display:none;flex:1;overflow-y:auto;padding:20px;}
        .form h2{margin:0 0 4px;font-size:17px;}
        .form p{margin:0 0 16px;color:var(--ink-600);font-size:13px;line-height:1.5;}
        .stars{display:flex;gap:8px;margin-bottom:16px;}
        .stars label{flex:1;}
        .stars input{position:absolute;opacity:0;pointer-events:none;}
        .stars span{display:block;text-align:center;padding:12px 0;border-radius:12px;border:1px solid var(--line);background:#fff;font-weight:800;cursor:pointer;}
        .stars input:checked + span{background:var(--green-600);color:#fff;border-color:var(--green-600);}
        .form textarea{width:100%;min-height:110px;padding:12px;border:1px solid var(--line);border-radius:12px;font-family:inherit;font-size:14px;resize:vertical;margin-bottom:14px;}
        .form .submit{width:100%;padding:13px;border:none;border-radius:12px;background:var(--green-600);color:#fff;font-weight:800;font-size:14px;font-family:inherit;cursor:pointer;}
        .form .err{color:#b4502c;font-weight:700;font-size:12.5px;margin-bottom:10px;}
        body.mode-form .chat,body.mode-form .tray{display:none;}
        body.mode-form .form{display:block;}

        .thanks{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px;gap:10px;}
        .thanks .big{font-size:46px;}
        .thanks h2{margin:0;font-size:20px;}
        .thanks p{margin:0;color:var(--ink-600);font-size:14px;line-height:1.55;max-width:340px;}
    </style>
</head>
<body>
<div class="shell">
    <div class="head">
        <img class="logo" src="{{ asset('images/logo/subic_lgu.png') }}" alt="" onerror="this.style.display='none'">
        <div>
            <div class="t">Subic Sports Complex</div>
            <div class="s">Event feedback</div>
        </div>
        @unless($done)<button type="button" class="switch" id="modeSwitch">Use simple form</button>@endunless
    </div>

    @if($done)
        <div class="thanks">
            <div class="big">💚</div>
            <h2>Thank you{{ session('thanks') ? '' : ' — we already have your feedback' }}!</h2>
            <p>Your feedback on <strong>{{ $event }}</strong> helps us keep the Subic Sports Complex running well for everyone.</p>
        </div>
    @else
        {{-- Chatbot --}}
        <div class="chat" id="chat" aria-live="polite"></div>
        <div class="tray" id="tray"></div>

        {{-- Plain form (fallback) --}}
        <form class="form" method="POST" action="{{ $submitUrl }}" id="plainForm">
            @csrf
            <input type="hidden" name="mode" value="form">
            <input type="hidden" name="src" value="{{ $src }}">
            <h2>How was “{{ $event }}”?</h2>
            <p>{{ $facility }} · {{ $eventDate }}</p>
            @error('rating')<div class="err">Please pick a rating from 1 to 5.</div>@enderror
            <div class="stars">
                @foreach([1, 2, 3, 4, 5] as $n)
                    <label><input type="radio" name="rating" value="{{ $n }}" required @checked((int) old('rating', $preRating) === $n)><span>{{ $n }} ★</span></label>
                @endforeach
            </div>
            <textarea name="comments" maxlength="2000" placeholder="Anything you'd like to tell us? (optional)">{{ old('comments') }}</textarea>
            <button type="submit" class="submit">Send feedback</button>
        </form>
    @endif
</div>

@unless($done)
<script>
    const CFG = @json([
        'first' => $firstName, 'event' => $event, 'facility' => $facility, 'date' => $eventDate,
        'pre' => $preRating, 'src' => $src, 'url' => $submitUrl,
    ]);
    const CSRF = @json(csrf_token());

    const chat = document.getElementById('chat');
    const tray = document.getElementById('tray');
    const state = { rating: CFG.pre || null, tags: [], comment: '' };

    const POSITIVE = ['Friendly staff', 'Clean facility', 'Good equipment', 'Easy booking', 'Value for money', 'Something else'];
    const NEGATIVE = ['Cleanliness', 'Damaged or missing equipment', 'Staff service', 'Facility condition', 'Booking or payment', 'Waiting time', 'Something else'];
    const STAR_LABELS = {1: 'Poor', 2: 'Fair', 3: 'Okay', 4: 'Good', 5: 'Excellent'};

    // ---- rule-based keyword replies (no AI: a fixed list of rules, first match wins)
    const RULES = [
        { re: /refund|money back|reimburs/i, reply: "About refunds: SSC doesn't refund cancelled bookings, but refunds for special cases (like damage or a facility problem on our side) are reviewed by the Operations Admin. I've added your note so the team can see it." },
        { re: /reschedul|change (the )?date|move (the )?date|postpone/i, reply: "Rescheduling a paid booking carries an additional 10% fee, and you can do it from My Appointments. I've added your note to the feedback." },
        { re: /dirty|unclean|smell|trash|toilet|comfort room|\bcr\b/i, reply: "Thanks for flagging the cleanliness — I'll pass it on to the facility team." },
        { re: /broken|damag|leak|slippery|not working|faulty|aircon|air.?con|light/i, reply: "Sorry about that. I've noted the facility problem so the team can look into it." },
        { re: /rude|staff|service|ignored|slow/i, reply: "Thank you for telling us about the service. Your comment goes straight to the team." },
        { re: /great|amazing|excellent|thank|love|perfect|awesome|good/i, reply: "That's lovely to hear — thank you!" },
    ];

    function scrollDown(){ chat.scrollTop = chat.scrollHeight; }
    function say(text, who = 'bot'){ const d = document.createElement('div'); d.className = 'msg ' + who; d.textContent = text; chat.appendChild(d); scrollDown(); }
    function clearTray(){ tray.innerHTML = ''; }
    function wait(ms){ return new Promise(r => setTimeout(r, ms)); }

    async function botSay(text, delay = 550){
        const t = document.createElement('div'); t.className = 'typing'; t.innerHTML = '<i></i><i></i><i></i>'; chat.appendChild(t); scrollDown();
        await wait(delay);
        t.remove(); say(text, 'bot');
    }

    function chips(items, onPick, opts = {}){
        clearTray();
        const box = document.createElement('div'); box.className = 'chips';
        items.forEach(it => {
            const b = document.createElement('button'); b.type = 'button';
            b.className = 'chip ' + (it.cls || ''); b.textContent = it.label;
            b.onclick = () => onPick(it, b); box.appendChild(b);
        });
        tray.appendChild(box);
        if (opts.hint) { const h = document.createElement('div'); h.className = 'hint'; h.textContent = opts.hint; tray.appendChild(h); }
    }

    // ---- conversation flow: rating -> detail -> comment -> confirm -> submit
    async function start(){
        await botSay(`Hi ${CFG.first}! 👋 Thanks for hosting “${CFG.event}” at ${CFG.facility} on ${CFG.date}.`);
        if (state.rating) {
            say(`${state.rating} — ${STAR_LABELS[state.rating]}`, 'me');
            await botSay(`Thanks for the ${state.rating}-star rating!`, 400);
            return askDetail();
        }
        await botSay('How would you rate your overall experience?');
        askRating();
    }

    function askRating(){
        chips([1, 2, 3, 4, 5].map(n => ({ label: `${n} ★ ${STAR_LABELS[n]}`, n, cls: 'star' })), async it => {
            state.rating = it.n; clearTray();
            say(`${it.n} — ${STAR_LABELS[it.n]}`, 'me');
            await botSay(it.n >= 4 ? 'Wonderful, thank you!' : (it.n === 3 ? 'Thanks for being honest.' : "I'm sorry it wasn't a great experience."), 450);
            askDetail();
        });
    }

    async function askDetail(){
        const positive = state.rating >= 4;
        await botSay(positive ? 'What stood out the most? Pick any that apply, then tap Done.' : 'What went wrong? Pick any that apply, then tap Done.');
        const list = positive ? POSITIVE : NEGATIVE;
        const picked = new Set();
        chips(list.map(l => ({ label: l })).concat([{ label: 'Done ✓', cls: 'go', done: true }]), async (it, btn) => {
            if (it.done) {
                state.tags = [...picked]; clearTray();
                say(state.tags.length ? state.tags.join(', ') : 'Nothing in particular', 'me');
                return askComment();
            }
            if (picked.has(it.label)) { picked.delete(it.label); btn.classList.remove('on'); }
            else { picked.add(it.label); btn.classList.add('on'); }
        });
    }

    async function askComment(){
        await botSay('Anything else you’d like to tell us? You can type it below or skip.');
        clearTray();
        const row = document.createElement('div'); row.className = 'row';
        row.innerHTML = '<input type="text" id="txt" maxlength="500" placeholder="Type your comment…" autocomplete="off"><button type="button" id="send">Send</button>';
        tray.appendChild(row);
        const skip = document.createElement('div'); skip.className = 'chips'; skip.style.marginTop = '10px';
        skip.innerHTML = '<button type="button" class="chip ghost" id="skip">Skip</button>';
        tray.appendChild(skip);

        const txt = document.getElementById('txt');
        const sendIt = async () => {
            const v = txt.value.trim(); if (!v) return;
            state.comment = v; clearTray(); say(v, 'me');
            const rule = RULES.find(r => r.re.test(v));
            await botSay(rule ? rule.reply : 'Thank you — I’ve added that to your feedback.', 600);
            askConfirm();
        };
        document.getElementById('send').onclick = sendIt;
        txt.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); sendIt(); } });
        document.getElementById('skip').onclick = async () => { clearTray(); say('Skip', 'me'); askConfirm(); };
        txt.focus();
    }

    async function askConfirm(){
        const lines = [`Rating: ${state.rating}/5 (${STAR_LABELS[state.rating]})`];
        if (state.tags.length) lines.push((state.rating >= 4 ? 'Highlights: ' : 'Issues: ') + state.tags.join(', '));
        if (state.comment) lines.push('Comment: ' + state.comment);
        await botSay('Here’s what I’ll send:\n' + lines.join('\n'));
        chips([{ label: 'Send feedback', cls: 'go', act: 'send' }, { label: 'Start over', cls: 'ghost', act: 'again' }], it => it.act === 'send' ? submit() : restart());
    }

    function composeComments(){
        const parts = [];
        if (state.tags.length) parts.push((state.rating >= 4 ? 'Highlights: ' : 'Issues: ') + state.tags.join(', '));
        if (state.comment) parts.push('Comment: ' + state.comment);
        return parts.join('\n');
    }

    async function submit(){
        clearTray(); say('Send feedback', 'me');
        try {
            const res = await fetch(CFG.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ rating: state.rating, comments: composeComments(), mode: 'chatbot', src: CFG.src }),
            });
            if (!res.ok) throw new Error('bad status');
            await botSay(state.rating <= 2 ? 'Thank you — your feedback was sent. Our team may follow up on the issues you mentioned. 💚' : 'Thank you — your feedback was sent. See you again at the Subic Sports Complex! 💚');
        } catch (e) {
            await botSay('Sorry, something went wrong sending that. Please try the simple form instead.');
            chips([{ label: 'Use simple form', cls: 'go' }], () => setMode('form'));
        }
    }

    function restart(){ chat.innerHTML = ''; state.rating = null; state.tags = []; state.comment = ''; start(); }

    // ---- chatbot <-> plain form toggle
    const sw = document.getElementById('modeSwitch');
    function setMode(mode){
        document.body.classList.toggle('mode-form', mode === 'form');
        sw.textContent = mode === 'form' ? 'Use chat' : 'Use simple form';
    }
    sw.addEventListener('click', () => setMode(document.body.classList.contains('mode-form') ? 'chat' : 'form'));
    @if($errors->any()) setMode('form'); @endif

    start();
</script>
@endunless
</body>
</html>