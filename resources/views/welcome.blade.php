<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESPAW | Pet Care, Reimagined</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink:   '#161412',
                        dusk:  '#24201d',
                        fog:   '#ebe6de',
                        clay:  '#b86f3f',
                        amber: '#d89d62',
                        moss:  '#4f6957',
                        cream: '#f7f3ed',
                    },
                    fontFamily: {
                        display: ['Cormorant Garamond', 'serif'],
                        body:    ['Sora', 'sans-serif'],
                    },
                    boxShadow: {
                        soft: '0 25px 70px rgba(22,20,18,0.14)',
                    },
                },
            },
        };
    </script>
    <style>
        /* ── Base ── */
        :root { --grid: rgba(36,32,29,0.08); --progress: 0%; }

        html { scroll-behavior: smooth; }

        body {
            background: radial-gradient(circle at 20% 10%, #fff8ef 0%, #f7f3ed 35%, #efe7dd 100%);
            color: #161412;
            overflow-x: hidden;
        }

        /* ── Scroll progress bar ── */
        #scroll-bar {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            width: var(--progress);
            background: linear-gradient(90deg, #b86f3f, #d89d62, #4f6957);
            z-index: 9999;
            transition: width 0.08s linear;
            border-radius: 0 2px 2px 0;
        }

        /* ── Background mesh ── */
        .mesh {
            position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 8% 18%, rgba(184,111,63,.22), transparent 30%),
                radial-gradient(circle at 86% 10%, rgba(79,105,87,.2), transparent 32%),
                radial-gradient(circle at 70% 80%, rgba(216,157,98,.2), transparent 40%);
            animation: mesh-drift 16s ease-in-out infinite alternate;
        }

        .grid-overlay {
            position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(to right, var(--grid) 1px, transparent 1px),
                linear-gradient(to bottom, var(--grid) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(circle at center, black 35%, transparent 85%);
        }

        /* ── Reveal animations ── */
        .reveal {
            opacity: 0;
            transform: translateY(36px);
            transition: opacity 0.72s cubic-bezier(.22,1,.36,1), transform 0.72s cubic-bezier(.22,1,.36,1);
        }
        .reveal-left {
            opacity: 0;
            transform: translateX(-44px);
            transition: opacity 0.72s cubic-bezier(.22,1,.36,1), transform 0.72s cubic-bezier(.22,1,.36,1);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(44px);
            transition: opacity 0.72s cubic-bezier(.22,1,.36,1), transform 0.72s cubic-bezier(.22,1,.36,1);
        }
        .reveal-scale {
            opacity: 0;
            transform: scale(0.93);
            transition: opacity 0.72s cubic-bezier(.22,1,.36,1), transform 0.72s cubic-bezier(.22,1,.36,1);
        }
        .reveal.show,
        .reveal-left.show,
        .reveal-right.show,
        .reveal-scale.show {
            opacity: 1;
            transform: none;
        }

        /* ── Ticker ── */
        .ticker { animation: marquee 24s linear infinite; }
        @keyframes marquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        /* ── Float orb ── */
        .orb { animation: float 7s ease-in-out infinite; }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-10px); }
        }

        /* ── Mesh drift ── */
        @keyframes mesh-drift {
            0%   { transform: translate3d(0,0,0) scale(1); }
            100% { transform: translate3d(-2%,2%,0) scale(1.08); }
        }

        /* ── Step number pulse ring ── */
        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0 rgba(184,111,63,.5); }
            70%  { box-shadow: 0 0 0 14px rgba(184,111,63,0); }
            100% { box-shadow: 0 0 0 0 rgba(184,111,63,0); }
        }
        .step-num { animation: pulse-ring 2.6s ease-out infinite; }

        /* ── Card hover lift ── */
        .card-lift {
            transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease;
        }
        .card-lift:hover {
            transform: translateY(-7px);
            box-shadow: 0 36px 80px rgba(22,20,18,.15);
        }

        /* ── Testimonial gradient border ── */
        .testi-card {
            background:
                linear-gradient(#fff, #fff) padding-box,
                linear-gradient(135deg, rgba(184,111,63,.28), rgba(79,105,87,.22)) border-box;
            border: 1px solid transparent;
        }

        /* ── Nav link underline ── */
        .nav-link { position: relative; padding-bottom: 2px; }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 1px;
            background: #b86f3f;
            transition: width .3s ease;
        }
        .nav-link:hover::after { width: 100%; }

        /* ── Back-to-top ── */
        #back-top {
            position: fixed;
            bottom: 28px; right: 28px;
            width: 46px; height: 46px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: #161412;
            color: #f7f3ed;
            opacity: 0;
            pointer-events: none;
            transform: translateY(14px);
            transition: opacity .35s ease, transform .35s ease, background .2s ease;
            z-index: 900;
            cursor: pointer;
            border: none;
        }
        #back-top.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
        #back-top:hover  { background: #b86f3f; }

        /* ── App store badge ── */
        .store-btn {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 11px 20px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.22);
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(10px);
            color: #f7f3ed;
            text-decoration: none;
            transition: background .3s ease, border-color .3s ease;
        }
        .store-btn:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.4); }

        /* ── Stat number ── */
        .stat-count { font-variant-numeric: tabular-nums; }

        /* ── Parallax wrapper ── */
        #hero-img { will-change: transform; }
    </style>
</head>
<body class="font-body antialiased selection:bg-clay selection:text-cream">

    <!-- Scroll progress bar -->
    <div id="scroll-bar"></div>

    <!-- Back-to-top button -->
    <button id="back-top" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 15l7-7 7 7"/></svg>
    </button>

    <div class="relative min-h-screen overflow-hidden">
        <div class="mesh"></div>
        <div class="grid-overlay"></div>

        <!-- ═══════════════ NAV ═══════════════ -->
        <header class="sticky top-0 z-50 px-4 lg:px-8 pt-4">
            <nav class="max-w-7xl mx-auto flex items-center justify-between rounded-full border border-dusk/10 bg-cream/80 backdrop-blur-xl px-6 py-3.5 shadow-soft">
                <a href="#" class="font-display text-2xl italic tracking-wide">Respaw</a>

                <div class="hidden md:flex items-center gap-7 text-sm text-dusk/80">
                    <a href="#features"     class="nav-link hover:text-ink transition-colors">Features</a>
                    <a href="#how-it-works" class="nav-link hover:text-ink transition-colors">How it works</a>
                    <a href="#safety"       class="nav-link hover:text-ink transition-colors">Safety</a>
                    <a href="#testimonials" class="nav-link hover:text-ink transition-colors">Reviews</a>
                    <a href="#journal"      class="nav-link hover:text-ink transition-colors">Journal</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/login"  class="text-sm px-4 py-2 rounded-full border border-dusk/20 hover:border-dusk/40 transition-colors">Log In</a>
                    <a href="/app"    class="text-sm px-4 py-2 rounded-full bg-ink text-cream hover:bg-clay transition-colors">Open App</a>
                </div>
            </nav>
        </header>

        <main class="relative z-10 max-w-7xl mx-auto px-6 lg:px-10 pt-10 pb-20">

            <!-- ═══════════════ HERO ═══════════════ -->
            <section class="grid lg:grid-cols-12 gap-10 items-end">
                <div class="lg:col-span-7 reveal show">
                    <p class="uppercase tracking-[0.24em] text-xs text-dusk/70 mb-5">Emergency Pet Care Platform</p>
                    <h1 class="font-display text-[3.2rem] sm:text-[4.2rem] lg:text-[5.5rem] leading-[0.9] text-ink">
                        Quiet confidence
                        <span class="italic text-clay">when it matters most.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-dusk/80 leading-relaxed">
                        Respaw gives you a calm emergency workflow: live SOS routing, instant vet matching,
                        and medical records your clinic can trust — available 24/7.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="#safety" class="rounded-xl bg-clay px-6 py-3 text-cream text-sm font-medium hover:bg-[#a85e31] transition-colors shadow-md">
                            Start with a Safety Check
                        </a>
                        <a href="#how-it-works" class="rounded-xl border border-dusk/20 bg-cream/80 px-6 py-3 text-sm hover:bg-cream transition-colors flex items-center gap-2">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            See how SOS works
                        </a>
                    </div>

                    <!-- Hero stats -->
                    <div class="mt-10 flex flex-wrap gap-8 text-sm text-dusk/70">
                        <div>
                            <p class="text-2xl font-semibold text-ink stat-count" data-target="4800" data-suffix="+">0</p>
                            <p class="mt-0.5">Pet owners helped</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-ink stat-count" data-target="320" data-suffix="+">0</p>
                            <p class="mt-0.5">Verified clinics</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-moss stat-count" data-target="2" data-suffix=" min avg">0</p>
                            <p class="mt-0.5">Response time</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 reveal-right" style="transition-delay:.14s">
                    <div class="relative rounded-3xl border border-dusk/15 bg-cream/90 p-4 shadow-soft overflow-hidden">
                        <img id="hero-img"
                            src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80"
                            alt="Dog and cat relaxing together"
                            class="h-[420px] w-full object-cover rounded-2xl"
                        >
                        <div class="orb absolute -bottom-5 -left-5 rounded-2xl border border-dusk/10 bg-white/90 px-4 py-3 shadow-lg">
                            <p class="text-xs text-dusk/70">Nearest vet response</p>
                            <p class="text-xl font-semibold text-moss">2 min 18 sec</p>
                        </div>
                        <div class="absolute -top-4 -right-4 rounded-2xl border border-dusk/10 bg-white/90 px-4 py-3 shadow-lg text-xs orb" style="animation-delay:1.5s">
                            <p class="text-dusk/70">Active SOS sessions</p>
                            <p class="text-lg font-semibold text-clay">14 live</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ TICKER ═══════════════ -->
            <section class="mt-16 overflow-hidden rounded-2xl border border-dusk/10 bg-cream/80 py-3">
                <div class="flex w-[200%] ticker text-xs sm:text-sm tracking-wide text-dusk/80">
                    <p class="w-1/2 whitespace-nowrap px-6">24/7 emergency routing &nbsp;•&nbsp; verified clinics &nbsp;•&nbsp; one-tap family alert &nbsp;•&nbsp; encrypted medical history &nbsp;•&nbsp; location-aware care &nbsp;•&nbsp; real-time vet dispatch &nbsp;•&nbsp; pet medical timeline &nbsp;•&nbsp; telehealth vet chat &nbsp;•&nbsp; health analytics</p>
                    <p class="w-1/2 whitespace-nowrap px-6">24/7 emergency routing &nbsp;•&nbsp; verified clinics &nbsp;•&nbsp; one-tap family alert &nbsp;•&nbsp; encrypted medical history &nbsp;•&nbsp; location-aware care &nbsp;•&nbsp; real-time vet dispatch &nbsp;•&nbsp; pet medical timeline &nbsp;•&nbsp; telehealth vet chat &nbsp;•&nbsp; health analytics</p>
                </div>
            </section>

            <!-- ═══════════════ FEATURES ═══════════════ -->
            <section id="features" class="mt-28">
                <div class="text-center mb-14 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">Everything you need</p>
                    <h2 class="font-display text-5xl">Built for the moments<br>that catch you off guard.</h2>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.04s">
                        <div class="w-10 h-10 rounded-xl bg-clay/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.03 1.21 2 2 0 012 .03h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">SOS</p>
                        <h3 class="mt-3 font-display text-3xl">Live Dispatch</h3>
                        <p class="mt-3 text-sm text-dusk/80">Automatic clinic matching based on urgency, distance, and on-duty vets — with real-time ETA updates.</p>
                        <a href="#safety" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.10s">
                        <div class="w-10 h-10 rounded-xl bg-moss/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Records</p>
                        <h3 class="mt-3 font-display text-3xl">Medical Timeline</h3>
                        <p class="mt-3 text-sm text-dusk/80">Vaccines, notes, and prescriptions in one encrypted timeline shared instantly with your care team.</p>
                        <a href="#journal" class="mt-5 inline-flex items-center gap-1 text-xs text-moss hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.16s">
                        <div class="w-10 h-10 rounded-xl bg-amber/15 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Support</p>
                        <h3 class="mt-3 font-display text-3xl">Vet Chat</h3>
                        <p class="mt-3 text-sm text-dusk/80">Symptom guidance, triage steps, and what to do before you arrive — live with a licensed vet.</p>
                        <a href="#journal" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.22s">
                        <div class="w-10 h-10 rounded-xl bg-clay/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Family</p>
                        <h3 class="mt-3 font-display text-3xl">Family Alerts</h3>
                        <p class="mt-3 text-sm text-dusk/80">One tap notifies your whole household — nobody is left out of the loop during an emergency.</p>
                        <a href="#safety" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.28s">
                        <div class="w-10 h-10 rounded-xl bg-moss/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Privacy</p>
                        <h3 class="mt-3 font-display text-3xl">End-to-End Encryption</h3>
                        <p class="mt-3 text-sm text-dusk/80">Your pet's health data is encrypted at rest and in transit. We never sell or share it.</p>
                        <a href="#" class="mt-5 inline-flex items-center gap-1 text-xs text-moss hover:underline">Privacy policy <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-7" style="transition-delay:.34s">
                        <div class="w-10 h-10 rounded-xl bg-amber/15 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Insights</p>
                        <h3 class="mt-3 font-display text-3xl">Health Analytics</h3>
                        <p class="mt-3 text-sm text-dusk/80">Track weight, activity, and wellness trends with gentle alerts when something looks off.</p>
                        <a href="#journal" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>
                </div>
            </section>

            <!-- ═══════════════ HOW IT WORKS ═══════════════ -->
            <section id="how-it-works" class="mt-28">
                <div class="text-center mb-16 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">Simple by design</p>
                    <h2 class="font-display text-5xl">From panic to calm<br>in three steps.</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8 relative">
                    <!-- connector line (desktop) -->
                    <div class="hidden md:block absolute top-10 left-[calc(16.66%+2rem)] right-[calc(16.66%+2rem)] h-px bg-dusk/15 z-0"></div>

                    <div class="reveal flex flex-col items-center text-center" style="transition-delay:.06s">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-clay text-cream flex items-center justify-center font-display text-4xl mb-6">1</div>
                        <h3 class="font-display text-2xl mb-2">Tap SOS</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">One button triggers the emergency flow. No login screens, no forms to fill in a crisis.</p>
                    </div>

                    <div class="reveal flex flex-col items-center text-center" style="transition-delay:.16s">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-moss text-cream flex items-center justify-center font-display text-4xl mb-6">2</div>
                        <h3 class="font-display text-2xl mb-2">We Find a Vet</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">The platform pings nearby verified clinics and sends your pet's medical profile automatically.</p>
                    </div>

                    <div class="reveal flex flex-col items-center text-center" style="transition-delay:.26s">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-amber text-ink flex items-center justify-center font-display text-4xl mb-6">3</div>
                        <h3 class="font-display text-2xl mb-2">Get Guided</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">Real-time triage guidance while your family is alerted and a vet slot is confirmed.</p>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ SAFETY ═══════════════ -->
            <section id="safety" class="mt-28 reveal-scale rounded-[2rem] border border-dusk/15 bg-dusk text-cream p-8 lg:p-12">
                <div class="grid lg:grid-cols-2 gap-10 items-center">
                    <div class="reveal-left">
                        <p class="uppercase text-xs tracking-[0.2em] text-amber/90">Safety Mode</p>
                        <h2 class="font-display text-5xl leading-tight mt-4">One screen.<br>Zero panic.</h2>
                        <p class="mt-5 text-cream/80 max-w-lg">We stripped the noisy UI from the emergency flow. Big actions, clear timers, and direct vet contact — no clutter, no confusion.</p>
                        <a href="/app" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-clay px-6 py-3 text-sm text-cream hover:bg-[#a85e31] transition-colors">
                            Try Safety Mode
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="reveal-right rounded-2xl border border-white/15 bg-white/10 p-6 backdrop-blur-md">
                        <ul class="space-y-3 text-sm">
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber inline-block"></span>Urgency triage</span>
                                <span class="text-amber font-medium">Ready</span>
                            </li>
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber inline-block"></span>Closest clinic pinged</span>
                                <span class="text-amber font-medium">3 clinics</span>
                            </li>
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber inline-block"></span>Medical profile shared</span>
                                <span class="text-amber font-medium">Sent</span>
                            </li>
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber inline-block"></span>Family contact alerted</span>
                                <span class="text-amber font-medium">Sent</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-moss inline-block"></span>Vet chat open</span>
                                <span class="text-moss font-medium">Live</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ TESTIMONIALS ═══════════════ -->
            <section id="testimonials" class="mt-28">
                <div class="text-center mb-14 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">From real pet parents</p>
                    <h2 class="font-display text-5xl">Stories that stayed<br>with us.</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <article class="testi-card reveal card-lift rounded-3xl p-7" style="transition-delay:.04s">
                        <div class="flex items-center gap-3 mb-5">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=80&q=80" alt="Marcus" class="w-11 h-11 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium text-ink">Marcus T.</p>
                                <p class="text-xs text-dusk/60">Golden Retriever owner</p>
                            </div>
                        </div>
                        <p class="text-sm text-dusk/80 leading-relaxed">"At 2 AM, Cooper stopped breathing normally. Respaw had a vet on the line within 90 seconds and a clinic 4 minutes away confirmed. He's fine now."</p>
                        <div class="mt-4 text-amber text-xs tracking-widest">★★★★★</div>
                    </article>

                    <article class="testi-card reveal card-lift rounded-3xl p-7" style="transition-delay:.12s">
                        <div class="flex items-center gap-3 mb-5">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=80&q=80" alt="Priya" class="w-11 h-11 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium text-ink">Priya M.</p>
                                <p class="text-xs text-dusk/60">Two cats, one corgi</p>
                            </div>
                        </div>
                        <p class="text-sm text-dusk/80 leading-relaxed">"The medical timeline is worth it alone. Every vet we visit gets a full picture instantly. No more carrying papers or repeating vaccine history at the front desk."</p>
                        <div class="mt-4 text-amber text-xs tracking-widest">★★★★★</div>
                    </article>

                    <article class="testi-card reveal card-lift rounded-3xl p-7" style="transition-delay:.20s">
                        <div class="flex items-center gap-3 mb-5">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=80&q=80" alt="James" class="w-11 h-11 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium text-ink">James R.</p>
                                <p class="text-xs text-dusk/60">Senior dog rescuer</p>
                            </div>
                        </div>
                        <p class="text-sm text-dusk/80 leading-relaxed">"I manage 5 dogs through a rescue. Keeping records across different vet visits was a nightmare. Now it's one timeline per dog — every caregiver has instant access."</p>
                        <div class="mt-4 text-amber text-xs tracking-widest">★★★★★</div>
                    </article>
                </div>
            </section>

            <!-- ═══════════════ JOURNAL ═══════════════ -->
            <section id="journal" class="mt-28 reveal">
                <div class="flex items-end justify-between gap-6 mb-10">
                    <div>
                        <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-2">From the journal</p>
                        <h2 class="font-display text-5xl">Care knowledge<br>you can trust.</h2>
                    </div>
                    <a href="/journal" class="text-sm border-b border-dusk/30 hover:border-dusk transition-colors flex items-center gap-1 shrink-0">
                        Read all
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <article class="lg:col-span-2 reveal card-lift rounded-3xl overflow-hidden border border-dusk/10 bg-white/80">
                        <img src="https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1400&q=80" alt="Dog portrait" class="h-72 w-full object-cover">
                        <div class="p-6">
                            <p class="text-xs uppercase tracking-[0.18em] text-dusk/60">Wellness</p>
                            <h3 class="mt-2 font-display text-4xl">Preparing your home after emergency treatment</h3>
                            <p class="mt-3 text-sm text-dusk/75">What to set up, what to remove, and how to build a recovery space your pet actually feels safe in.</p>
                            <a href="/journal/emergency-home-prep" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read article <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <div class="flex flex-col gap-6">
                        <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 p-6" style="transition-delay:.08s">
                            <p class="text-xs uppercase tracking-[0.18em] text-dusk/60">Routine</p>
                            <h3 class="mt-2 font-display text-3xl">Medication reminders that actually work</h3>
                            <p class="mt-3 text-sm text-dusk/75">Build a repeatable weekly rhythm for meds, checkups, and follow-up notes.</p>
                            <a href="/journal/medication-reminders" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </article>
                        <article class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 p-6" style="transition-delay:.14s">
                            <p class="text-xs uppercase tracking-[0.18em] text-dusk/60">Nutrition</p>
                            <h3 class="mt-2 font-display text-3xl">Reading pet food labels — a plain-language guide</h3>
                            <p class="mt-3 text-sm text-dusk/75">What the first five ingredients tell you, and what marketing language to ignore.</p>
                            <a href="/journal/pet-food-labels" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ APP DOWNLOAD ═══════════════ -->
            <section class="mt-20 reveal-scale">
                <div class="rounded-[2rem] overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-moss via-[#3a5244] to-ink"></div>
                    <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 72% 28%, rgba(216,157,98,.6), transparent 50%);"></div>
                    <div class="relative z-10 px-8 py-14 md:px-14 grid md:grid-cols-2 gap-10 items-center">
                        <div class="reveal-left">
                            <p class="uppercase tracking-[0.2em] text-xs text-amber/80 mb-3">Available everywhere</p>
                            <h2 class="font-display text-5xl text-cream leading-tight">Calm care,<br>in your pocket.</h2>
                            <p class="mt-4 text-cream/70 max-w-sm">Download Respaw on iOS or Android. Emergency features work even on slow connections.</p>
                            <div class="mt-8 flex flex-wrap gap-4">
                                <a href="#" class="store-btn">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                    <div class="text-left">
                                        <p class="text-[10px] opacity-70">Download on the</p>
                                        <p class="text-sm font-medium">App Store</p>
                                    </div>
                                </a>
                                <a href="#" class="store-btn">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.3.17.64.24.99.2l12.6-12.6L13.23 7.8 3.18 23.76zm17.49-9.89l-2.7-1.56-3.03 3.03 3.03 3.03 2.73-1.58c.78-.45.78-1.47-.03-1.92zM2.07 1.52C2.02 1.7 2 1.89 2 2.1V21.9c0 .21.02.4.07.57l.06.06L13.16 11.5l.01-.01L2.13 1.46l-.06.06zm11.74 10L2.22.81c.33-.19.71-.25 1.07-.14l13.12 7.58-2.6 2.27z"/></svg>
                                    <div class="text-left">
                                        <p class="text-[10px] opacity-70">Get it on</p>
                                        <p class="text-sm font-medium">Google Play</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="reveal-right flex justify-center">
                            <img src="https://images.unsplash.com/photo-1601758003122-53c40e686a19?auto=format&fit=crop&w=800&q=80" alt="Person with phone and dog" class="h-64 w-80 object-cover rounded-2xl shadow-soft opacity-80">
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ NEWSLETTER ═══════════════ -->
            <section class="mt-16 reveal">
                <div class="rounded-3xl border border-dusk/15 bg-white/80 px-6 py-10 md:px-10 md:py-12">
                    <div class="grid lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-7">
                            <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Join Early Access</p>
                            <h2 class="font-display text-5xl mt-3">Get updates without the spam vibe.</h2>
                            <p class="text-dusk/75 mt-3 max-w-xl">One monthly digest — product updates, emergency care tips, and local vet events near you.</p>
                        </div>
                        <form class="lg:col-span-5 flex flex-col sm:flex-row gap-3">
                            <input type="email" placeholder="you@example.com" class="w-full rounded-xl border border-dusk/20 bg-cream px-4 py-3 text-sm focus:border-clay focus:ring-0 focus:outline-none">
                            <button type="button" class="rounded-xl bg-ink px-5 py-3 text-sm text-cream hover:bg-clay transition-colors whitespace-nowrap">Subscribe</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <!-- ═══════════════ FOOTER ═══════════════ -->
        <footer class="relative z-10 border-t border-dusk/10 mt-16 bg-cream/50">
            <div class="max-w-7xl mx-auto px-6 lg:px-10 pt-14 pb-10">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-14">
                    <div class="col-span-2 md:col-span-1">
                        <a href="#" class="font-display text-2xl italic">Respaw</a>
                        <p class="mt-3 text-sm text-dusk/70 max-w-[200px]">Calm emergency care for the pets you love most.</p>
                        <div class="mt-5 flex gap-4">
                            <a href="#" aria-label="Twitter/X" class="text-dusk/50 hover:text-clay transition-colors">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="#" aria-label="Instagram" class="text-dusk/50 hover:text-clay transition-colors">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                            </a>
                            <a href="#" aria-label="Facebook" class="text-dusk/50 hover:text-clay transition-colors">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-4">Product</p>
                        <ul class="space-y-3 text-sm text-dusk/75">
                            <li><a href="#features"     class="hover:text-ink transition-colors">Features</a></li>
                            <li><a href="#how-it-works" class="hover:text-ink transition-colors">How it works</a></li>
                            <li><a href="#safety"       class="hover:text-ink transition-colors">Safety Mode</a></li>
                            <li><a href="#"             class="hover:text-ink transition-colors">For Clinics</a></li>
                            <li><a href="#"             class="hover:text-ink transition-colors">Pricing</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-4">Resources</p>
                        <ul class="space-y-3 text-sm text-dusk/75">
                            <li><a href="#journal" class="hover:text-ink transition-colors">Care Journal</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">Emergency Guide</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">Vet Directory</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">API Docs</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">Status</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-4">Company</p>
                        <ul class="space-y-3 text-sm text-dusk/75">
                            <li><a href="#" class="hover:text-ink transition-colors">About</a></li>
                            <li><a href="#" class="hover:text-ink transition-colors">Careers</a></li>
                            <li><a href="#" class="hover:text-ink transition-colors">Press</a></li>
                            <li><a href="#" class="hover:text-ink transition-colors">Contact</a></li>
                            <li><a href="#" class="hover:text-ink transition-colors">Support</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-dusk/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-dusk/55">
                    <p>© 2026 Respaw. Designed for real-world calm.</p>
                    <div class="flex flex-wrap justify-center gap-6">
                        <a href="#" class="hover:text-ink transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-ink transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-ink transition-colors">Cookie Preferences</a>
                        <a href="#" class="hover:text-ink transition-colors">Accessibility</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        /* ── Scroll progress bar ── */
        function updateProgress() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            document.documentElement.style.setProperty('--progress', pct + '%');
        }

        /* ── Back-to-top visibility ── */
        const backTop = document.getElementById('back-top');
        function updateBackTop() {
            backTop.classList.toggle('visible', window.scrollY > 500);
        }

        /* ── Parallax on hero image ── */
        const heroImg = document.getElementById('hero-img');
        function updateParallax() {
            if (!heroImg) return;
            heroImg.style.transform = 'translateY(' + (window.scrollY * 0.16) + 'px)';
        }

        window.addEventListener('scroll', () => {
            updateProgress();
            updateBackTop();
            updateParallax();
        }, { passive: true });

        /* ── Intersection observer — all reveal variants ── */
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.13 });

        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale')
                .forEach((el) => revealObserver.observe(el));

        /* ── Animated stat counters ── */
        function animateCounter(el) {
            const target  = parseInt(el.dataset.target, 10);
            const suffix  = el.dataset.suffix || '+';
            const dur     = 1500;
            const start   = performance.now();
            (function step(now) {
                const p = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
                el.textContent = Math.round(eased * target).toLocaleString() + suffix;
                if (p < 1) requestAnimationFrame(step);
            })(start);
        }

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-count[data-target]')
                .forEach((el) => counterObserver.observe(el));

        /* ── Smooth anchor scroll with sticky-nav offset ── */
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const id = this.getAttribute('href');
                if (id === '#') return;
                const target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                const navH = document.querySelector('header').offsetHeight + 12;
                window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - navH, behavior: 'smooth' });
            });
        });

        /* ── Init ── */
        updateProgress();
        updateBackTop();
    </script>
</body>
</html>
