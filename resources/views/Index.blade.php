<style>

:root {
    --navy-deep:   #0B1B2C;
    --court-navy:  #10263F;
    --court-navy-2:#173252;
    --amber:       #C9843F;
    --coral:       #E2603A;
    --paper:       #F7F5F0;
    --slate:       #2B333B;
    --slate-soft:  #5B6672;
    --line:        rgba(255, 255, 255, 0.14);

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
    background: var(--navy-deep);
}

h1, h2, h3 { font-family: var(--font-display); margin: 0; color: inherit; }
p { margin: 0; }
ul { list-style: none; margin: 0; padding: 0; }
a { color: inherit; text-decoration: none; }
button { font-family: inherit; cursor: pointer; border: none; background: none; }

/* ==========================================================================
   Nav (shared with the landing page)
   ========================================================================== */
.ssc-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: var(--nav-height);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
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

.ssc-nav__dot { color: var(--amber); }

.ssc-nav__logo {
    height: 2.1rem;
    width: auto;
    object-fit: contain;
    display: block;
}

.ssc-nav__links { display: flex; align-items: center; gap: 0.5rem; }

.ssc-nav__link {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.95rem;
    padding: 0.5rem 0.9rem;
    border-radius: 999px;
    transition: color 0.2s ease, background 0.2s ease;
}
.ssc-nav__link:hover { color: #fff; }
.ssc-nav__link.is-active { color: #fff; background: rgba(255, 255, 255, 0.12); }

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
    display: block; height: 2px; width: 100%;
    background: #fff; border-radius: 2px;
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.ssc-nav__toggle.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.ssc-nav__toggle.is-open span:nth-child(2) { opacity: 0; }
.ssc-nav__toggle.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

@media (max-width: 720px) {
    .ssc-nav__toggle { display: flex; }
    .ssc-nav__logo { height: 1.8rem; }
    .ssc-nav__links {
        position: fixed; top: var(--nav-height); left: 0; right: 0;
        flex-direction: column; align-items: stretch;
        background: var(--navy-deep);
        padding: 1rem 1.5rem 1.5rem; gap: 0.25rem;
        transform: translateY(-8px); opacity: 0; pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .ssc-nav__links.is-open { transform: translateY(0); opacity: 1; pointer-events: auto; }
    .ssc-nav__link { text-align: left; }
    .ssc-nav__cta { margin-left: 0; text-align: center; }
}

/* ==========================================================================
   Auth page background
   ========================================================================== */
.ssc-auth-page {
    min-height: 100dvh;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: calc(var(--nav-height) + 2rem) 1.25rem 2.5rem;
    overflow: hidden;
}

.ssc-auth-bg__photo {
    position: fixed;
    inset: 0;
    z-index: -2;
    background-size: cover;
    background-position: center;
}

.ssc-auth-bg {
    position: fixed;
    inset: 0;
    z-index: -1;
    background:
        radial-gradient(circle at 80% 20%, rgba(201, 132, 63, 0.25), transparent 45%),
        radial-gradient(circle at 15% 85%, rgba(226, 96, 58, 0.15), transparent 40%),
        linear-gradient(135deg, rgba(16, 38, 63, 0.6) 0%, rgba(11, 27, 44, 0.68) 65%);
}

.ssc-auth-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(90deg, transparent, transparent 118px, rgba(255,255,255,0.04) 118px, rgba(255,255,255,0.04) 120px);
}

/* ==========================================================================
   Glassmorphism sliding card
   Structure/animation pattern: two form panels + a sliding overlay
   ========================================================================== */
.ssc-auth-container {
    position: relative;
    width: 100%;
    max-width: 860px;
    min-height: 540px;
    border-radius: 22px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);
}

.ssc-auth-panel {
    position: absolute;
    top: 0;
    left: 0;
    width: 50%;
    height: 100%;
    display: flex;
    align-items: center;
    padding: 2.75rem 3rem;
    transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
}

.ssc-auth-panel--signin { z-index: 2; opacity: 1; }
.ssc-auth-panel--signup { z-index: 1; opacity: 0; }

.ssc-auth-container.is-signup .ssc-auth-panel--signin {
    transform: translateX(100%);
    opacity: 0;
}

.ssc-auth-container.is-signup .ssc-auth-panel--signup {
    transform: translateX(100%);
    opacity: 1;
    z-index: 5;
}

.ssc-auth-form { width: 100%; }

.ssc-auth-form h2 {
    color: #fff;
    font-size: 1.7rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
}

.ssc-auth-form__hint {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.ssc-field { margin-bottom: 1.1rem; }

.ssc-field label {
    display: block;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.75);
    margin-bottom: 0.4rem;
}

.ssc-field input {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 0.95rem;
    font-family: inherit;
    backdrop-filter: blur(6px);
    transition: border-color 0.2s ease, background 0.2s ease;
}

.ssc-field input::placeholder { color: rgba(255, 255, 255, 0.4); }

.ssc-field input:focus {
    outline: none;
    border-color: var(--amber);
    background: rgba(255, 255, 255, 0.14);
}

.ssc-field-row {
    display: flex;
    gap: 0.9rem;
}
.ssc-field-row .ssc-field { flex: 1; }

.ssc-field-error {
    color: #ffb4a3;
    font-size: 0.8rem;
    margin-top: 0.35rem;
}

.ssc-auth-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.ssc-auth-row a {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
}
.ssc-auth-row a:hover { color: var(--amber); }

.ssc-auth-submit {
    width: 100%;
    padding: 0.85rem 1.5rem;
    border-radius: 999px;
    background: var(--coral);
    color: #fff;
    font-size: 0.98rem;
    font-weight: 500;
    transition: background 0.2s ease, transform 0.15s ease;
}
.ssc-auth-submit:hover { background: #c94f2b; transform: translateY(-1px); }

.ssc-auth-switch {
    display: none;
    text-align: center;
    margin-top: 1.25rem;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.65);
}
.ssc-auth-switch button {
    color: var(--amber);
    font-weight: 500;
}

/* Overlay panel that slides across the card */
.ssc-auth-overlay-container {
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: transform 0.6s ease-in-out;
    z-index: 20;
}

.ssc-auth-container.is-signup .ssc-auth-overlay-container {
    transform: translateX(-100%);
}

.ssc-auth-overlay {
    position: relative;
    left: -100%;
    width: 200%;
    height: 100%;
    background:
        linear-gradient(135deg, rgba(16, 38, 63, 0.85), rgba(226, 96, 58, 0.75));
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #fff;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
}

.ssc-auth-container.is-signup .ssc-auth-overlay {
    transform: translateX(50%);
}

.ssc-auth-overlay-panel {
    position: absolute;
    top: 0;
    height: 100%;
    width: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 2.75rem;
    transition: transform 0.6s ease-in-out;
}

.ssc-auth-overlay-panel--left {
    left: 0;
    transform: translateX(-20%);
}
.ssc-auth-container.is-signup .ssc-auth-overlay-panel--left {
    transform: translateX(0);
}

.ssc-auth-overlay-panel--right {
    right: 0;
    transform: translateX(0);
}
.ssc-auth-container.is-signup .ssc-auth-overlay-panel--right {
    transform: translateX(20%);
}

.ssc-auth-overlay-panel h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.ssc-auth-overlay-panel p {
    font-size: 0.92rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 1.5rem;
}

.ssc-auth-overlay-panel button {
    padding: 0.7rem 1.9rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    color: #fff;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.2s ease, color 0.2s ease;
}
.ssc-auth-overlay-panel button:hover {
    background: #fff;
    color: var(--court-navy);
}

/* ==========================================================================
   Mobile — stack instead of slide (the slide effect needs the side-by-side
   width the overlay pattern relies on)
   ========================================================================== */
@media (max-width: 760px) {
    .ssc-auth-container { min-height: 0; }

    .ssc-auth-overlay-container { display: none; }

    .ssc-auth-panel {
        position: relative;
        width: 100%;
        display: none;
        padding: 2.25rem 1.5rem;
        transform: none !important;
        opacity: 1 !important;
    }

    .ssc-auth-panel--signin { display: flex; }
    .ssc-auth-container.is-signup .ssc-auth-panel--signin { display: none; }
    .ssc-auth-container.is-signup .ssc-auth-panel--signup { display: flex; z-index: 2; }

    .ssc-auth-switch { display: block; }
}

</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In / Sign Up — Subic Sports Complex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="ssc-nav" id="sscNav">
        <a href="{{ route('landing') }}" class="ssc-nav__brand">
            <span>SSC<span class="ssc-nav__dot">·</span>Subic Sports Complex</span>
            <img src="{{ asset('images/logo/subic_lgu.png') }}" alt="Municipality of Subic logo" class="ssc-nav__logo">
        </a>

        <button class="ssc-nav__toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="navLinks">
            <span></span><span></span><span></span>
        </button>

        <div class="ssc-nav__links" id="navLinks">
            <a href="{{ route('landing') }}" class="ssc-nav__link">Home</a>
            <a href="{{ route('landing') }}#facilities" class="ssc-nav__link">Facilities</a>
            <a href="{{ route('landing') }}#visit" class="ssc-nav__link">Visit</a>
            <a href="{{ route('account.show', ['mode' => 'signup']) }}" class="ssc-nav__cta">Start a Booking</a>
        </div>
    </nav>

    <div class="ssc-auth-page">
        <div class="ssc-auth-bg__photo" style="background-image:url('{{ asset($bgImage ?? '') }}')" aria-hidden="true"></div>
        <div class="ssc-auth-bg" aria-hidden="true"></div>

        <div class="ssc-auth-container {{ $activeMode === 'signup' ? 'is-signup' : '' }}" id="authContainer">

            <!-- Sign in -->
            <div class="ssc-auth-panel ssc-auth-panel--signin">
                <div class="ssc-auth-form">
                    <h2>Welcome back</h2>
                    <p class="ssc-auth-form__hint">Sign in to manage your bookings.</p>

                    <form method="POST" action="{{ route('account.login') }}">
                        @csrf
                        <input type="hidden" name="form_type" value="signin">

                        <div class="ssc-field">
                            <label for="signinEmail">Email</label>
                            <input type="email" id="signinEmail" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
                            @error('email', 'signin')
                                <p class="ssc-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="ssc-field">
                            <label for="signinPassword">Password</label>
                            <input type="password" id="signinPassword" name="password" placeholder="••••••••" required autocomplete="current-password">
                            @error('password', 'signin')
                                <p class="ssc-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="ssc-auth-row">
                            <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.85rem; color:rgba(255,255,255,0.7);">
                                <input type="checkbox" name="remember" style="width:auto;">
                                Remember me
                            </label>
                            <a href="#">Forgot password?</a>
                        </div>

                        <button type="submit" class="ssc-auth-submit">Sign In</button>
                    </form>

                    <p class="ssc-auth-switch">
                        New here? <button type="button" data-toggle="signup">Create an account</button>
                    </p>
                </div>
            </div>

            <!-- Sign up -->
            <div class="ssc-auth-panel ssc-auth-panel--signup">
                <div class="ssc-auth-form">
                    <h2>Create your account</h2>
                    <p class="ssc-auth-form__hint">One account to book any facility at SSC.</p>

                    <form method="POST" action="{{ route('account.register') }}">
                        @csrf
                        <input type="hidden" name="form_type" value="signup">

                        <div class="ssc-field-row">
                            <div class="ssc-field">
                                <label for="signupFirstName">First name</label>
                                <input type="text" id="signupFirstName" name="first_name" value="{{ old('first_name') }}" placeholder="Juan" required>
                                @error('first_name', 'signup')
                                    <p class="ssc-field-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="ssc-field">
                                <label for="signupLastName">Last name</label>
                                <input type="text" id="signupLastName" name="last_name" value="{{ old('last_name') }}" placeholder="Dela Cruz" required>
                                @error('last_name', 'signup')
                                    <p class="ssc-field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="ssc-field">
                            <label for="signupEmail">Email</label>
                            <input type="email" id="signupEmail" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
                            @error('email', 'signup')
                                <p class="ssc-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="ssc-field">
                            <label for="signupPhone">Phone number</label>
                            <input type="text" id="signupPhone" name="phone_number" value="{{ old('phone_number') }}" placeholder="09XX XXX XXXX">
                            @error('phone_number', 'signup')
                                <p class="ssc-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="ssc-field-row">
                            <div class="ssc-field">
                                <label for="signupPassword">Password</label>
                                <input type="password" id="signupPassword" name="password" placeholder="At least 8 characters" required autocomplete="new-password">
                                @error('password', 'signup')
                                    <p class="ssc-field-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="ssc-field">
                                <label for="signupPasswordConfirm">Confirm password</label>
                                <input type="password" id="signupPasswordConfirm" name="password_confirmation" placeholder="Re-enter password" required autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="ssc-auth-submit">Sign Up</button>
                    </form>

                    <p class="ssc-auth-switch">
                        Already have an account? <button type="button" data-toggle="signin">Sign in</button>
                    </p>
                </div>
            </div>

            <!-- Sliding overlay (desktop only) -->
            <div class="ssc-auth-overlay-container">
                <div class="ssc-auth-overlay">
                    <div class="ssc-auth-overlay-panel ssc-auth-overlay-panel--left">
                        <h3>Already booked with us?</h3>
                        <p>Sign in to check your request status, rebook a favorite facility, or update your details.</p>
                        <button type="button" data-toggle="signin">Sign In</button>
                    </div>
                    <div class="ssc-auth-overlay-panel ssc-auth-overlay-panel--right">
                        <h3>New to SSC?</h3>
                        <p>Create an account to start a booking request for Function 1, Function 2, Function 3, or the Whole Court.</p>
                        <button type="button" data-toggle="signup">Sign Up</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navToggle = document.getElementById('navToggle');
        const navLinks  = document.getElementById('navLinks');

        navToggle.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('is-open');
            navToggle.classList.toggle('is-open', isOpen);
            navToggle.setAttribute('aria-expanded', String(isOpen));
        });

        const container = document.getElementById('authContainer');

        document.querySelectorAll('[data-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                container.classList.toggle('is-signup', btn.dataset.toggle === 'signup');
            });
        });
    });
</script>