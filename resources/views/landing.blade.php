<style>

:root {
    --navy-deep:   #0B1B2C;
    --court-navy:  #10263F;
    --court-navy-2:#173252;
    --amber:       #C9843F;
    --coral:       #E2603A;
    --ivory:       #EFEAE0;
    --paper:       #F7F5F0;
    --slate:       #2B333B;
    --slate-soft:  #5B6672;
    --line:        rgba(255, 255, 255, 0.14);
    --line-dark:   rgba(16, 38, 63, 0.12);

    --font-display: 'Oswald', 'Arial Narrow', sans-serif;
    --font-body:    'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

    --nav-height: 4.5rem;
}

* { box-sizing: border-box; }

html, body {
    margin: 0;
    padding: 0;
    font-family: var(--font-body);
    color: var(--slate);
    background: var(--paper);
    scroll-behavior: smooth;
}

h1, h2, h3, h4 {
    font-family: var(--font-display);
    margin: 0;
    color: inherit;
}

p { margin: 0; }
ul { list-style: none; margin: 0; padding: 0; }
a { color: inherit; text-decoration: none; }
button { font-family: inherit; cursor: pointer; border: none; background: none; }

@media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}

/* ==========================================================================
   Nav
   ========================================================================== */
.ssc-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--nav-height);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    background: linear-gradient(to bottom, rgba(11, 27, 44, 0.55), transparent);
    transition: background 0.3s ease;
}

.ssc-nav.is-scrolled {
    background: var(--navy-deep);
    box-shadow: 0 1px 0 var(--line);
}

.ssc-nav__brand {
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.ssc-nav__dot {
    color: var(--amber);
}

.ssc-nav__logo {
    height: 2.1rem;
    width: auto;
    object-fit: contain;
    display: block;
}

.ssc-nav__links {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ssc-nav__link {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.95rem;
    padding: 0.5rem 0.9rem;
    border-radius: 999px;
    transition: color 0.2s ease, background 0.2s ease;
}

.ssc-nav__link:hover { color: #fff; }

.ssc-nav__link.is-active {
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
}

.ssc-nav__cta {
    margin-left: 0.75rem;
    padding: 0.55rem 1.2rem;
    background: var(--coral);
    color: #fff;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 999px;
    transition: background 0.2s ease;
}

.ssc-nav__cta:hover { background: #c94f2b; }

.ssc-nav__toggle {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 2.25rem;
    height: 2.25rem;
}

.ssc-nav__toggle span {
    display: block;
    height: 2px;
    width: 100%;
    background: #fff;
    border-radius: 2px;
    transition: transform 0.25s ease, opacity 0.25s ease;
}

.ssc-nav__toggle.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.ssc-nav__toggle.is-open span:nth-child(2) { opacity: 0; }
.ssc-nav__toggle.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ==========================================================================
   Snap scroller — the 3 horizontal sections, stacked full-viewport
   ========================================================================== */
.ssc-scroller {
    height: 100dvh;
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
}

.ssc-section {
    height: 100dvh;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* section index number, echoing the 3-part sequence */
.ssc-section::after {
    content: attr(data-index);
    position: fixed;
    right: 1.75rem;
    bottom: 1.75rem;
    font-family: var(--font-display);
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    color: rgba(255, 255, 255, 0.55);
    z-index: 40;
    pointer-events: none;
}

.ssc-hero::after,
.ssc-facilities::after { color: rgba(255, 255, 255, 0.55); }

.ssc-visit::after { color: rgba(255, 255, 255, 0.55); }

/* ==========================================================================
   Section 1 — Hero
   ========================================================================== */
.ssc-hero {
    background: var(--navy-deep);
    color: #fff;
    overflow: hidden;
}

/* background photo layer — path comes from the controller via $heroImage */
.ssc-hero__photo {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    z-index: 0;
}

.ssc-hero__bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 78% 30%, rgba(201, 132, 63, 0.22), transparent 45%),
        linear-gradient(90deg, rgba(11, 27, 44, 0.90) 0%, rgba(11, 27, 44, 0.55) 55%, rgba(11, 27, 44, 0.30) 100%);
    z-index: 1;
}

.ssc-hero__bg::before {
    /* faint court-line motif */
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        repeating-linear-gradient(90deg, transparent, transparent 118px, rgba(255,255,255,0.05) 118px, rgba(255,255,255,0.05) 120px);
    opacity: 0.5;
}

/* wraps text + carousel side by side; falls back to a single centered
   column (original behaviour) when there are no posters */
.ssc-hero__inner {
    position: relative;
    z-index: 2;
    width: 100%;
    margin: 0 auto;
    padding: 0 1.75rem;
}

.ssc-hero__inner:not(.has-carousel) {
    max-width: 42rem;
}

.ssc-hero__inner.has-carousel {
    max-width: 1400px;
    display: flex;
    align-items: center;
    gap: 2.5rem;
}

.ssc-hero__inner.has-carousel .ssc-hero__content {
    flex: 0 0 40%;
    max-width: 40%;
}

.ssc-hero__content {
    width: 100%;
}

.ssc-hero__kicker {
    font-size: 0.9rem;
    color: var(--amber);
    font-weight: 500;
    margin-bottom: 1rem;
}

.ssc-hero__title {
    font-size: clamp(2.1rem, 5.2vw, 3.4rem);
    line-height: 1.08;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin-bottom: 1.25rem;
}

.ssc-hero__sub {
    font-size: 1.05rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.78);
    max-width: 34rem;
    margin-bottom: 2rem;
}

.ssc-hero__inner.has-carousel .ssc-hero__sub {
    max-width: none;
}

.ssc-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.ssc-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.85rem 1.6rem;
    border-radius: 999px;
    font-size: 0.98rem;
    font-weight: 500;
    transition: transform 0.15s ease, background 0.2s ease, border-color 0.2s ease;
}

.ssc-btn--primary {
    background: var(--coral);
    color: #fff;
}
.ssc-btn--primary:hover { background: #c94f2b; transform: translateY(-1px); }

.ssc-btn--ghost {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.4);
}
.ssc-btn--ghost:hover { border-color: #fff; transform: translateY(-1px); }

/* ---------- Hero poster carousel (only rendered when $posters isn't empty) ---------- */
.ssc-hero__carousel-wrap {
    flex: 0 0 56%;
    max-width: 56%;
}

.ssc-hero-carousel {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
}

.ssc-hero-carousel__track {
    display: flex;
    gap: 1.25rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    padding: 0.5rem 0.25rem 1rem;
    scrollbar-width: none;
    flex: 1;
    min-width: 0;
}

.ssc-hero-carousel__track::-webkit-scrollbar { display: none; }

.ssc-hero-carousel__slide {
    flex: 0 0 auto;
    width: min(72%, 300px);
    scroll-snap-align: center;
    perspective: 1600px;
}

/* flip / morph card: front = poster image, back = event detail box */
.flip-card {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    cursor: pointer;
}

.flip-card__inner {
    position: relative;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.6s cubic-bezier(.4, .2, .2, 1);
}

.flip-card.is-flipped .flip-card__inner {
    transform: rotateY(180deg);
}

.flip-card__front,
.flip-card__back {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 16px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.3);
}

.flip-card__front {
    background-size: cover;
    background-position: center;
    background-color: var(--court-navy-2);
    display: flex;
    align-items: flex-end;
}

.flip-card__front::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(to top, rgba(11, 27, 44, 0.82), transparent 48%);
}

.flip-card__hint {
    position: relative;
    z-index: 1;
    width: 100%;
    text-align: center;
    padding: 0.65rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #fff;
}

.flip-card__back {
    transform: rotateY(180deg);
    background: var(--court-navy);
    color: #fff;
    padding: 1.5rem 1.3rem;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.flip-card__status {
    align-self: flex-start;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.28rem 0.7rem;
    border-radius: 999px;
    margin-bottom: 0.9rem;
}

.flip-card__status.status-open    { background: rgba(74, 222, 128, 0.16); color: #4ade80; }
.flip-card__status.status-full    { background: rgba(226, 96, 58, 0.2);   color: var(--coral); }
.flip-card__status.status-ongoing { background: rgba(201, 132, 63, 0.22); color: var(--amber); }

.flip-card__back h3 {
    font-size: 1.15rem;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 0.65rem;
}

.flip-card__meta {
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.75);
    margin-bottom: 0.35rem;
}

.flip-card__desc {
    font-size: 0.84rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.65);
    margin-top: 0.5rem;
    flex: 1;
    overflow-y: auto;
}

.flip-card__close {
    align-self: flex-start;
    margin-top: 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--amber);
    padding-bottom: 0.15rem;
    border-bottom: 1px solid rgba(201, 132, 63, 0.45);
}

/* ==========================================================================
   Section 2 — Facilities carousel
   ========================================================================== */
.ssc-facilities {
    background: var(--paper);
    padding: 6rem 1.75rem 2rem;
}

.ssc-facilities__header {
    max-width: 34rem;
    margin: 0 auto 2.25rem;
    text-align: center;
}

.ssc-facilities__header h2 {
    font-size: clamp(1.7rem, 3.4vw, 2.3rem);
    font-weight: 600;
    color: var(--court-navy);
    margin-bottom: 0.75rem;
}

.ssc-facilities__header p {
    color: var(--slate-soft);
    line-height: 1.6;
}

.ssc-carousel {
    position: relative;
    max-width: 68rem;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ssc-carousel__track {
    display: flex;
    gap: 1.25rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    padding: 0.5rem 0.25rem 1rem;
    scrollbar-width: none;
}

.ssc-carousel__track::-webkit-scrollbar { display: none; }

.ssc-carousel__slide {
    flex: 0 0 auto;
    width: min(78vw, 340px);
    scroll-snap-align: center;
}

.ssc-carousel__image {
    width: 100%;
    aspect-ratio: 4 / 3;
    border-radius: 14px;
    background-color: var(--court-navy-2);
    background-size: cover;
    background-position: center;
    box-shadow: 0 10px 30px rgba(16, 38, 63, 0.15);
}

.ssc-carousel__caption {
    padding: 0.9rem 0.2rem 0;
}

.ssc-carousel__caption h3 {
    font-size: 1.1rem;
    font-weight: 500;
    color: var(--court-navy);
}

.ssc-carousel__caption p {
    font-size: 0.88rem;
    color: var(--slate-soft);
    margin-top: 0.2rem;
}

.ssc-carousel__arrow {
    flex: 0 0 auto;
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 50%;
    background: #fff;
    border: 1px solid var(--line-dark);
    color: var(--court-navy);
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease, transform 0.15s ease;
}

.ssc-carousel__arrow:hover {
    background: var(--court-navy);
    color: #fff;
    transform: translateY(-1px);
}

/* on the dark hero, the arrows need to read against navy, not white-on-paper */
.ssc-hero-carousel .ssc-carousel__arrow {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.ssc-hero-carousel .ssc-carousel__arrow:hover {
    background: var(--coral);
    border-color: var(--coral);
    color: #fff;
}

.ssc-carousel__dots {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.ssc-carousel__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--line-dark);
    transition: background 0.2s ease, transform 0.2s ease;
}

.ssc-carousel__dot.is-active {
    background: var(--coral);
    transform: scale(1.3);
}

.ssc-hero-carousel__dots .ssc-carousel__dot {
    background: rgba(255, 255, 255, 0.25);
}

.ssc-hero-carousel__dots .ssc-carousel__dot.is-active {
    background: var(--amber);
}

/* ==========================================================================
   Section 3 — Location (map) + footer
   ========================================================================== */
.ssc-visit {
    background: var(--navy-deep);
    color: #fff;
    padding: 0;
}

.ssc-location {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100dvh;
}

@media (min-width: 861px) {
    .ssc-location {
        flex-direction: row;
    }
}

.ssc-location__map {
    position: relative;
    flex: 1 1 55%;
    min-height: 280px;
}

.ssc-location__map iframe {
    width: 100%;
    height: 100%;
    display: block;
}

.ssc-location__badge {
    position: absolute;
    left: 1.25rem;
    bottom: 1.25rem;
    max-width: 240px;
    background: #fff;
    color: var(--court-navy);
    padding: 0.85rem 1rem;
    border-radius: 12px;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    font-size: 0.85rem;
    line-height: 1.4;
}

.ssc-location__pin {
    font-size: 1.15rem;
    line-height: 1;
}

/* ==========================================================================
   Footer
   ========================================================================== */
.ssc-footer {
    flex: 1 1 45%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 2.5rem 1.75rem;
    background: var(--court-navy);
}

.ssc-footer__main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem 1.5rem;
    padding-bottom: 1.75rem;
    border-bottom: 1px solid var(--line);
}

.ssc-footer__brand {
    grid-column: 1 / -1;
}

.ssc-footer__wordmark {
    font-family: var(--font-display);
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
}

.ssc-footer__address {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.92rem;
}

.ssc-footer__col h4 {
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.55);
    margin-bottom: 0.9rem;
}

.ssc-footer__col li { margin-bottom: 0.6rem; }
.ssc-footer__col a,
.ssc-footer__contact li {
    font-size: 0.92rem;
    color: rgba(255, 255, 255, 0.82);
}
.ssc-footer__col a:hover { color: var(--amber); }

.ssc-footer__bottom {
    padding-top: 1.1rem;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.45);
}

/* ==========================================================================
   Responsive
   ========================================================================== */
@media (max-width: 860px) {
    .ssc-hero { overflow-y: auto; justify-content: flex-start; }

    .ssc-hero__inner.has-carousel {
        flex-direction: column;
        align-items: stretch;
        gap: 1.75rem;
        padding-top: 6rem;
        padding-bottom: 2.5rem;
    }

    .ssc-hero__inner.has-carousel .ssc-hero__content {
        flex: none;
        max-width: 100%;
    }

    .ssc-hero__carousel-wrap {
        flex: none;
        max-width: 100%;
        width: 100%;
    }

    .ssc-hero-carousel__slide { width: min(70vw, 280px); }
}

@media (max-width: 720px) {
    .ssc-nav__toggle { display: flex; }

    .ssc-nav__logo { height: 1.8rem; }

    .ssc-nav__links {
        position: fixed;
        top: var(--nav-height);
        left: 0;
        right: 0;
        flex-direction: column;
        align-items: stretch;
        background: var(--navy-deep);
        padding: 1rem 1.5rem 1.5rem;
        gap: 0.25rem;
        transform: translateY(-8px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .ssc-nav__links.is-open {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .ssc-nav__link { text-align: left; }
    .ssc-nav__cta { margin-left: 0; text-align: center; }

    .ssc-section::after { display: none; }

    .ssc-hero__sub { font-size: 0.98rem; }

    .ssc-facilities { padding-top: 5rem; }

    .ssc-carousel__arrow { display: none; }

    .ssc-visit { overflow-y: auto; }

    .ssc-location { height: auto; min-height: 100dvh; }

    .ssc-location__map { min-height: 220px; }

    .ssc-location__badge {
        left: 0.9rem;
        right: 0.9rem;
        bottom: 0.9rem;
        max-width: none;
    }

    .ssc-footer__main { grid-template-columns: 1fr; }
}

</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subic Sports Complex — Online Appointment & Facility Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="ssc-nav" id="sscNav">
        <a href="#home" class="ssc-nav__brand">
            <span>SSC<span class="ssc-nav__dot">·</span>Subic Sports Complex</span>
            <img src="{{ asset('images/logo/subic_lgu.png') }}" alt="Municipality of Subic logo" class="ssc-nav__logo">
        </a>

        <button class="ssc-nav__toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="navLinks">
            <span></span><span></span><span></span>
        </button>

        <div class="ssc-nav__links" id="navLinks">
            <button class="ssc-nav__link is-active" data-target="home">Home</button>
            <button class="ssc-nav__link" data-target="facilities">Facilities</button>
            <button class="ssc-nav__link" data-target="visit">Visit</button>
            <a href="{{ route('account.show', ['mode' => 'signup']) }}" class="ssc-nav__cta">Start a Booking</a>
        </div>
    </nav>

    <div class="ssc-scroller" id="sscScroller">

        <section class="ssc-section ssc-hero" id="home" data-index="01">
            <div class="ssc-hero__photo" style="background-image:url('{{ asset($heroImage ?? '') }}')" aria-hidden="true"></div>
            <div class="ssc-hero__bg" aria-hidden="true"></div>

            <div class="ssc-hero__inner {{ !empty($posters) ? 'has-carousel' : '' }}">

                <div class="ssc-hero__content">
                    <p class="ssc-hero__kicker">Wawandue, Subic, Zambales</p>
                    <h1 class="ssc-hero__title">Book Subic Sports Complex<br>without the runaround.</h1>
                    <p class="ssc-hero__sub">
                        Reserve Function 1, Function 2, Function 3, or the Lobby and Whole Court online.
                        Check real-time availability, submit your request, and follow it through approval —
                        no more shuttling between the complex and the municipal office.
                    </p>
                    <div class="ssc-hero__actions">
                        <a href="#" class="ssc-btn ssc-btn--primary">Start a Booking</a>
                        <button class="ssc-btn ssc-btn--ghost" data-target="facilities">See what's inside</button>
                    </div>
                </div>

                @if(!empty($posters))
                    <div class="ssc-hero__carousel-wrap">
                        <div class="ssc-hero-carousel">
                            <button class="ssc-carousel__arrow" id="heroCarouselPrev" aria-label="Previous poster">‹</button>

                            <ul class="ssc-hero-carousel__track" id="heroCarouselTrack">
                                @foreach ($posters as $poster)
                                    <li class="ssc-hero-carousel__slide">
                                        <div class="flip-card">
                                            <div class="flip-card__inner">
                                                <div
                                                    class="flip-card__front"
                                                    style="background-image:url('{{ asset($poster['image']) }}')"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="View details for {{ $poster['title'] ?? 'this event' }}"
                                                >
                                                    <span class="flip-card__hint">Tap for details</span>
                                                </div>
                                                <div class="flip-card__back" role="button" tabindex="0" aria-label="Back to poster">
                                                    <span class="flip-card__status status-{{ strtolower($poster['status'] ?? 'open') }}">
                                                        {{ $poster['status'] ?? 'Open' }}
                                                    </span>
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
                                    </li>
                                @endforeach
                            </ul>

                            <button class="ssc-carousel__arrow" id="heroCarouselNext" aria-label="Next poster">›</button>
                        </div>
                        <div class="ssc-carousel__dots ssc-hero-carousel__dots" id="heroCarouselDots"></div>
                    </div>
                @endif

            </div>
        </section>

        <section class="ssc-section ssc-facilities" id="facilities" data-index="02">
            <div class="ssc-facilities__header">
                <h2>What you can book</h2>
                <p>Four spaces, one request form. Availability updates in real time so two events never land on the same slot.</p>
            </div>

            <div class="ssc-carousel" id="sscCarousel">
                <button class="ssc-carousel__arrow ssc-carousel__arrow--prev" id="carouselPrev" aria-label="Previous facility">‹</button>

                <ul class="ssc-carousel__track" id="carouselTrack">
                    @foreach ($facilities as $facility)
                        <li class="ssc-carousel__slide">
                            <div class="ssc-carousel__image" style="background-image:url('{{ asset($facility['image']) }}')"></div>
                            <div class="ssc-carousel__caption">
                                <h3>{{ $facility['name'] }}</h3>
                                <p>{{ $facility['meta'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <button class="ssc-carousel__arrow ssc-carousel__arrow--next" id="carouselNext" aria-label="Next facility">›</button>
            </div>

            <div class="ssc-carousel__dots" id="carouselDots"></div>
        </section>

        <section class="ssc-section ssc-visit" id="visit" data-index="03">
            <div class="ssc-location">

                <div class="ssc-location__map">
                    <iframe
                        src="{{ $location['mapEmbedUrl'] }}"
                        width="600"
                        height="450"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin">
                    </iframe>

                    <div class="ssc-location__badge">
                        <span class="ssc-location__pin">📍</span>
                        <span>
                            <strong>{{ $location['label'] }}</strong><br>
                            {{ $location['address'] }}
                        </span>
                    </div>
                </div>

                <footer class="ssc-footer">
                    <div class="ssc-footer__main">
                        <div class="ssc-footer__brand">
                            <p class="ssc-footer__wordmark">Subic Sports Complex</p>
                            <p class="ssc-footer__address">{{ $location['address'] }}</p>
                        </div>

                        <div class="ssc-footer__col">
                            <h4>Facility</h4>
                            <ul>
                                <li><a href="#home">Home</a></li>
                                <li><a href="#facilities">Facilities</a></li>
                                <li><a href="#">Rates</a></li>
                            </ul>
                        </div>

                        <div class="ssc-footer__col">
                            <h4>Booking</h4>
                            <ul>
                                <li><a href="#">Start a request</a></li>
                                <li><a href="#">Track a request</a></li>
                                <li><a href="#">Free-use process</a></li>
                            </ul>
                        </div>

                        <div class="ssc-footer__col">
                            <h4>Contact</h4>
                            <ul class="ssc-footer__contact">
                                <li>Phone: (placeholder)</li>
                                <li>Email: (placeholder)</li>
                                <li>Office hours: (placeholder)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="ssc-footer__bottom">
                        <p>&copy; {{ date('Y') }} Subic Sports Complex. All rights reserved.</p>
                    </div>
                </footer>

            </div>
        </section>

    </div>

</body>
</html>

<script>

    document.addEventListener('DOMContentLoaded', () => {
        const nav        = document.getElementById('sscNav');
        const navToggle  = document.getElementById('navToggle');
        const navLinks   = document.getElementById('navLinks');
        const scroller   = document.getElementById('sscScroller');
        const sections   = Array.from(document.querySelectorAll('.ssc-section'));
        const navButtons = Array.from(document.querySelectorAll('[data-target]'));


        function goToSection(id) {
            const target = document.getElementById(id);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            closeMobileNav();
        }

        navButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.target;
                if (id) goToSection(id);
            });
        });


        function closeMobileNav() {
            navToggle.classList.remove('is-open');
            navLinks.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        }

        navToggle.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('is-open');
            navToggle.classList.toggle('is-open', isOpen);
            navToggle.setAttribute('aria-expanded', String(isOpen));
        });


        const sectionObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;

                    const id = entry.target.id;

                    document.querySelectorAll('.ssc-nav__link').forEach((link) => {
                        link.classList.toggle('is-active', link.dataset.target === id);
                    });

                    nav.classList.toggle('is-scrolled', id !== 'home');
                });
            },
            { root: scroller, threshold: 0.6 }
        );

        sections.forEach((section) => sectionObserver.observe(section));

        // ---------------------------------------------------------------
        // Reusable horizontal carousel (dots + prev/next), used by both
        // the facilities carousel and the hero poster carousel.
        // ---------------------------------------------------------------
        function initCarousel({ trackId, prevId, nextId, dotsId }) {
            const track = document.getElementById(trackId);
            if (!track) return null;

            const prev = document.getElementById(prevId);
            const next = document.getElementById(nextId);
            const dotsWrap = document.getElementById(dotsId);
            const slides = Array.from(track.children);

            slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.className = 'ssc-carousel__dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                dot.addEventListener('click', () => {
                    slides[i].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                });
                dotsWrap.appendChild(dot);
            });

            const dots = Array.from(dotsWrap.children);

            function setActiveDot(index) {
                dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
            }

            function currentIndex() {
                const trackCenter = track.scrollLeft + track.clientWidth / 2;
                let closest = 0;
                let closestDist = Infinity;
                slides.forEach((slide, i) => {
                    const slideCenter = slide.offsetLeft + slide.clientWidth / 2;
                    const dist = Math.abs(trackCenter - slideCenter);
                    if (dist < closestDist) {
                        closestDist = dist;
                        closest = i;
                    }
                });
                return closest;
            }

            function scrollByStep(dir) {
                const i = Math.min(Math.max(currentIndex() + dir, 0), slides.length - 1);
                slides[i].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }

            if (prev) prev.addEventListener('click', () => scrollByStep(-1));
            if (next) next.addEventListener('click', () => scrollByStep(1));

            let scrollTimeout;
            track.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => setActiveDot(currentIndex()), 80);
            });

            setActiveDot(0);

            return { track, slides };
        }

        // facilities carousel
        initCarousel({
            trackId: 'carouselTrack',
            prevId: 'carouselPrev',
            nextId: 'carouselNext',
            dotsId: 'carouselDots',
        });

        // hero poster carousel + flip-to-details behaviour
        const heroCarousel = initCarousel({
            trackId: 'heroCarouselTrack',
            prevId: 'heroCarouselPrev',
            nextId: 'heroCarouselNext',
            dotsId: 'heroCarouselDots',
        });

        if (heroCarousel) {
            heroCarousel.slides.forEach((slide) => {
                const card = slide.querySelector('.flip-card');
                if (!card) return;

                const front = card.querySelector('.flip-card__front');
                const back = card.querySelector('.flip-card__back');

                const toggle = () => card.classList.toggle('is-flipped');
                const onKey = (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggle();
                    }
                };

                front.addEventListener('click', toggle);
                front.addEventListener('keydown', onKey);
                back.addEventListener('click', toggle);
                back.addEventListener('keydown', onKey);
            });
        }
    });

</script>