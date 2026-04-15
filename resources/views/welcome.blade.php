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
        :root { --grid: rgba(36,32,29,0.08); --progress: 0%; }
        html { scroll-behavior: smooth; }
        body {
            background: radial-gradient(circle at 20% 10%, #fff8ef 0%, #f7f3ed 35%, #efe7dd 100%);
            color: #161412;
            overflow-x: hidden;
        }

        /* ── Scroll progress bar ── */
        #scroll-bar {
            position: fixed; top: 0; left: 0; height: 3px;
            width: var(--progress);
            background: linear-gradient(90deg, #b86f3f, #d89d62, #4f6957);
            z-index: 9999; transition: width 0.08s linear;
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
        .reveal { opacity:0; transform:translateY(36px); transition:opacity .72s cubic-bezier(.22,1,.36,1),transform .72s cubic-bezier(.22,1,.36,1); }
        .reveal-left { opacity:0; transform:translateX(-44px); transition:opacity .72s cubic-bezier(.22,1,.36,1),transform .72s cubic-bezier(.22,1,.36,1); }
        .reveal-right { opacity:0; transform:translateX(44px); transition:opacity .72s cubic-bezier(.22,1,.36,1),transform .72s cubic-bezier(.22,1,.36,1); }
        .reveal-scale { opacity:0; transform:scale(0.93); transition:opacity .72s cubic-bezier(.22,1,.36,1),transform .72s cubic-bezier(.22,1,.36,1); }
        .reveal.show,.reveal-left.show,.reveal-right.show,.reveal-scale.show { opacity:1; transform:none; }

        /* ── Ticker ── */
        .ticker { animation: marquee 28s linear infinite; }
        @keyframes marquee { from { transform:translateX(0); } to { transform:translateX(-50%); } }

        /* ── Float orb ── */
        .orb { animation: float 7s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-10px);} }

        /* ── Mesh drift ── */
        @keyframes mesh-drift { 0%{transform:translate3d(0,0,0) scale(1);} 100%{transform:translate3d(-2%,2%,0) scale(1.08);} }

        /* ── Step pulse ring ── */
        @keyframes pulse-ring {
            0%{box-shadow:0 0 0 0 rgba(184,111,63,.5);}
            70%{box-shadow:0 0 0 14px rgba(184,111,63,0);}
            100%{box-shadow:0 0 0 0 rgba(184,111,63,0);}
        }
        .step-num { animation: pulse-ring 2.6s ease-out infinite; }

        /* ── Card lift ── */
        .card-lift { transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s ease; }
        .card-lift:hover { transform:translateY(-7px); box-shadow:0 36px 80px rgba(22,20,18,.15); }

        /* ── Testimonial gradient border ── */
        .testi-card {
            background: linear-gradient(#fff,#fff) padding-box, linear-gradient(135deg,rgba(184,111,63,.28),rgba(79,105,87,.22)) border-box;
            border:1px solid transparent;
        }

        /* ── Nav underline ── */
        .nav-link { position:relative; padding-bottom:2px; }
        .nav-link::after { content:''; position:absolute; bottom:0; left:0; width:0; height:1px; background:#b86f3f; transition:width .3s ease; }
        .nav-link:hover::after { width:100%; }

        /* ── Back-to-top ── */
        #back-top {
            position:fixed; bottom:28px; right:28px;
            width:46px; height:46px;
            display:flex; align-items:center; justify-content:center;
            border-radius:50%; background:#161412; color:#f7f3ed;
            opacity:0; pointer-events:none; transform:translateY(14px);
            transition:opacity .35s ease,transform .35s ease,background .2s ease;
            z-index:900; cursor:pointer; border:none;
        }
        #back-top.visible { opacity:1; pointer-events:auto; transform:translateY(0); }
        #back-top:hover { background:#b86f3f; }

        /* ── App store badge ── */
        .store-btn {
            display:inline-flex; align-items:center; gap:10px;
            padding:11px 20px; border-radius:14px;
            border:1px solid rgba(255,255,255,.22);
            background:rgba(255,255,255,.08); backdrop-filter:blur(10px);
            color:#f7f3ed; text-decoration:none;
            transition:background .3s ease,border-color .3s ease,transform .3s ease;
        }
        .store-btn:hover { background:rgba(255,255,255,.18); border-color:rgba(255,255,255,.4); transform:translateY(-2px); }

        /* ── Stat number ── */
        .stat-count { font-variant-numeric:tabular-nums; }

        /* ── Parallax ── */
        #hero-img { will-change:transform; }

        /* ── Gradient text ── */
        @keyframes gradient-shift {
            0%{background-position:0% 50%;}
            50%{background-position:100% 50%;}
            100%{background-position:0% 50%;}
        }
        .gradient-text {
            background:linear-gradient(135deg,#b86f3f,#d89d62,#4f6957,#b86f3f);
            background-size:300% 300%;
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
            animation:gradient-shift 5s ease infinite;
        }

        /* ── Shimmer card ── */
        @keyframes shimmer { 0%{transform:translateX(-100%);} 100%{transform:translateX(200%);} }
        .shimmer-card { position:relative; overflow:hidden; }
        .shimmer-card::after {
            content:''; position:absolute; top:0; left:0;
            width:40%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
            transform:translateX(-100%);
            animation:shimmer 4s ease-in-out infinite 1s;
            pointer-events:none;
        }

        /* ── Glow pulse CTA ── */
        @keyframes glow-pulse {
            0%,100%{box-shadow:0 0 0 0 rgba(184,111,63,0);}
            50%{box-shadow:0 0 28px 6px rgba(184,111,63,.28);}
        }
        .cta-glow { animation:glow-pulse 3s ease-in-out infinite; }

        /* ── Ping live dot ── */
        @keyframes ping { 75%,100%{transform:scale(2.2);opacity:0;} }
        .ping-ring {
            position:relative; display:inline-flex;
            align-items:center; justify-content:center;
        }
        .ping-ring::before {
            content:''; position:absolute; inset:0; border-radius:50%;
            background:rgba(79,105,87,.6);
            animation:ping 1.8s cubic-bezier(0,0,.2,1) infinite;
        }

        /* ── Wave underline decoration ── */
        .wave-line {
            display:inline-block; position:relative;
        }
        .wave-line::after {
            content:''; position:absolute; bottom:-4px; left:0;
            width:100%; height:3px;
            background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='3'%3E%3Cpath d='M0 1.5 Q5 0 10 1.5 Q15 3 20 1.5' fill='none' stroke='%23b86f3f' stroke-width='1.5'/%3E%3C/svg%3E") repeat-x;
            background-size:20px 3px;
        }

        /* ── FAQ Accordion ── */
        .faq-content {
            max-height:0; overflow:hidden; opacity:0;
            transition:max-height .55s cubic-bezier(.22,1,.36,1),opacity .4s ease,padding .3s ease;
        }
        .faq-content.open { max-height:260px; opacity:1; }
        .faq-icon { transition:transform .4s cubic-bezier(.22,1,.36,1); }
        .faq-item.open .faq-icon { transform:rotate(45deg); }
        .faq-item { border-bottom:1px solid rgba(36,32,29,.1); }

        /* ── Blog filter tabs ── */
        .filter-btn {
            padding:7px 18px; border-radius:999px; font-size:12px;
            border:1px solid rgba(36,32,29,.15);
            transition:all .25s ease; cursor:pointer; background:transparent;
            color:#24201d;
        }
        .filter-btn.active,.filter-btn:hover { background:#161412; color:#f7f3ed; border-color:#161412; }

        /* ── Blog article hide/show ── */
        .blog-article { transition:opacity .4s ease,transform .4s ease; }
        .blog-article.hidden { opacity:0; pointer-events:none; position:absolute; transform:scale(0.96); }

        /* ── Pricing card popular ── */
        .pricing-popular {
            background:linear-gradient(#fff,#fff) padding-box,
                        linear-gradient(135deg,#b86f3f,#d89d62,#4f6957) border-box;
            border:2px solid transparent;
            transform:scale(1.04);
        }

        /* ── Pet type card ── */
        .pet-emoji { transition:transform .4s cubic-bezier(.22,1,.36,1); display:inline-block; }
        .pet-card:hover .pet-emoji { transform:scale(1.25) rotate(-8deg); }
        .pet-card { cursor:pointer; }

        /* ── Mobile nav ── */
        #mobile-menu {
            max-height:0; overflow:hidden;
            transition:max-height .5s cubic-bezier(.22,1,.36,1);
        }
        #mobile-menu.open { max-height:360px; }

        /* ── Video play button ── */
        @keyframes play-ring { 0%{transform:scale(1);opacity:.7;} 100%{transform:scale(1.7);opacity:0;} }
        .play-ring {
            position:absolute; inset:0; border-radius:50%;
            border:2px solid rgba(255,255,255,.5);
            animation:play-ring 2s ease-out infinite;
        }
        .play-ring-2 {
            position:absolute; inset:0; border-radius:50%;
            border:2px solid rgba(255,255,255,.3);
            animation:play-ring 2s ease-out infinite .7s;
        }
        .play-btn {
            transition:transform .3s ease,box-shadow .3s ease;
        }
        .play-btn:hover { transform:scale(1.1); box-shadow:0 16px 48px rgba(22,20,18,.3); }

        /* ── Stagger delay helpers ── */
        .d1{transition-delay:.06s!important;} .d2{transition-delay:.12s!important;}
        .d3{transition-delay:.18s!important;} .d4{transition-delay:.24s!important;}
        .d5{transition-delay:.30s!important;} .d6{transition-delay:.36s!important;}

        /* ── Category badge ── */
        .badge-wellness  { background:rgba(79,105,87,.12); color:#3d5445; }
        .badge-emergency { background:rgba(184,111,63,.12); color:#9a5c32; }
        .badge-nutrition { background:rgba(216,157,98,.15); color:#a07838; }
        .badge-routine   { background:rgba(36,32,29,.08);  color:#24201d; }
        .badge-behavior  { background:rgba(99,81,62,.1);   color:#634e3e; }

        /* ── Trust logo ── */
        .trust-logo { filter:grayscale(1) opacity(.5); transition:filter .3s ease; }
        .trust-logo:hover { filter:grayscale(0) opacity(1); }

        /* ── Number glow on hover ── */
        .stat-box:hover .stat-count { color:#b86f3f; }
        .stat-box { transition:transform .3s ease; }
        .stat-box:hover { transform:translateY(-4px); }
    </style>
</head>
<body class="font-body antialiased selection:bg-clay selection:text-cream">

    <div id="scroll-bar"></div>

    <button id="back-top" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 15l7-7 7 7"/></svg>
    </button>

    <div class="relative min-h-screen overflow-hidden">
        <div class="mesh"></div>
        <div class="grid-overlay"></div>

        <!-- ═══════════════ NAV ═══════════════ -->
        <header class="sticky top-0 z-50 px-4 lg:px-8 pt-4">
            <nav class="max-w-7xl mx-auto rounded-full border border-dusk/10 bg-cream/85 backdrop-blur-xl shadow-soft">
                <div class="flex items-center justify-between px-6 py-3.5">
                    <a href="#" class="font-display text-2xl italic tracking-wide">Respaw</a>

                    <div class="hidden md:flex items-center gap-7 text-sm text-dusk/80">
                        <a href="#features"     class="nav-link hover:text-ink transition-colors">Features</a>
                        <a href="#how-it-works" class="nav-link hover:text-ink transition-colors">How it works</a>
                        <a href="#pricing"      class="nav-link hover:text-ink transition-colors">Pricing</a>
                        <a href="#safety"       class="nav-link hover:text-ink transition-colors">Safety</a>
                        <a href="#testimonials" class="nav-link hover:text-ink transition-colors">Reviews</a>
                        <a href="#journal"      class="nav-link hover:text-ink transition-colors">Journal</a>
                        <a href="#faq"          class="nav-link hover:text-ink transition-colors">FAQ</a>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="/login"  class="hidden sm:inline-flex text-sm px-4 py-2 rounded-full border border-dusk/20 hover:border-dusk/40 transition-colors">Log In</a>
                        <a href="/app"    class="text-sm px-4 py-2 rounded-full bg-ink text-cream hover:bg-clay transition-colors cta-glow">Open App</a>
                        <!-- Mobile hamburger -->
                        <button id="menu-toggle" class="md:hidden p-2 rounded-full hover:bg-dusk/10 transition-colors" aria-label="Toggle menu">
                            <svg id="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <!-- Mobile menu -->
                <div id="mobile-menu" class="md:hidden px-6 pb-0">
                    <div class="py-4 flex flex-col gap-4 text-sm text-dusk/80 border-t border-dusk/10">
                        <a href="#features"     class="hover:text-ink transition-colors">Features</a>
                        <a href="#how-it-works" class="hover:text-ink transition-colors">How it works</a>
                        <a href="#pricing"      class="hover:text-ink transition-colors">Pricing</a>
                        <a href="#safety"       class="hover:text-ink transition-colors">Safety</a>
                        <a href="#testimonials" class="hover:text-ink transition-colors">Reviews</a>
                        <a href="#journal"      class="hover:text-ink transition-colors">Journal</a>
                        <a href="#faq"          class="hover:text-ink transition-colors">FAQ</a>
                        <a href="/login"        class="hover:text-ink transition-colors">Log In</a>
                    </div>
                </div>
            </nav>
        </header>

        <main class="relative z-10 max-w-7xl mx-auto px-6 lg:px-10 pt-10 pb-20">

            <!-- ═══════════════ HERO ═══════════════ -->
            <section class="grid lg:grid-cols-12 gap-10 items-end">
                <div class="lg:col-span-7 reveal show">
                    <div class="inline-flex items-center gap-2 rounded-full border border-clay/25 bg-clay/8 px-4 py-1.5 mb-6">
                        <span class="ping-ring relative w-2 h-2 rounded-full bg-moss"></span>
                        <p class="text-xs tracking-[0.18em] text-clay font-medium uppercase">Emergency Pet Care Platform</p>
                    </div>
                    <h1 class="font-display text-[3.2rem] sm:text-[4.2rem] lg:text-[5.5rem] leading-[0.9] text-ink">
                        Quiet confidence
                        <span class="italic gradient-text">when it matters most.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-dusk/80 leading-relaxed">
                        Respaw gives you a calm emergency workflow: live SOS routing, instant vet matching,
                        and medical records your clinic can trust — available 24/7.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="#safety" class="rounded-xl bg-clay px-6 py-3 text-cream text-sm font-medium hover:bg-[#a85e31] transition-colors shadow-md cta-glow">
                            Start with a Safety Check
                        </a>
                        <a href="#how-it-works" class="rounded-xl border border-dusk/20 bg-cream/80 px-6 py-3 text-sm hover:bg-cream transition-colors flex items-center gap-2">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            See how SOS works
                        </a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-8 text-sm text-dusk/70">
                        <div class="stat-box">
                            <p class="text-2xl font-semibold text-ink stat-count" data-target="4800" data-suffix="+">0</p>
                            <p class="mt-0.5">Pet owners helped</p>
                        </div>
                        <div class="stat-box">
                            <p class="text-2xl font-semibold text-ink stat-count" data-target="320" data-suffix="+">0</p>
                            <p class="mt-0.5">Verified clinics</p>
                        </div>
                        <div class="stat-box">
                            <p class="text-2xl font-semibold text-moss stat-count" data-target="2" data-suffix=" min avg">0</p>
                            <p class="mt-0.5">Response time</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 reveal-right d2">
                    <div class="relative rounded-3xl border border-dusk/15 bg-cream/90 p-4 shadow-soft overflow-hidden shimmer-card">
                        <img id="hero-img"
                            src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80"
                            alt="Dog and cat relaxing together"
                            class="h-[420px] w-full object-cover rounded-2xl">
                        <div class="orb absolute -bottom-5 -left-5 rounded-2xl border border-dusk/10 bg-white/90 px-4 py-3 shadow-lg">
                            <p class="text-xs text-dusk/70">Nearest vet response</p>
                            <p class="text-xl font-semibold text-moss">2 min 18 sec</p>
                        </div>
                        <div class="absolute -top-4 -right-4 rounded-2xl border border-dusk/10 bg-white/90 px-4 py-3 shadow-lg text-xs orb" style="animation-delay:1.5s">
                            <p class="text-dusk/70">Active SOS sessions</p>
                            <p class="text-lg font-semibold text-clay flex items-center gap-1.5">
                                <span class="ping-ring relative w-1.5 h-1.5 rounded-full bg-clay"></span>14 live
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ TICKER ═══════════════ -->
            <section class="mt-16 overflow-hidden rounded-2xl border border-dusk/10 bg-cream/80 py-3">
                <div class="flex w-[200%] ticker text-xs sm:text-sm tracking-wide text-dusk/80">
                    <p class="w-1/2 whitespace-nowrap px-6">24/7 emergency routing &nbsp;•&nbsp; verified clinics &nbsp;•&nbsp; one-tap family alert &nbsp;•&nbsp; encrypted medical history &nbsp;•&nbsp; location-aware care &nbsp;•&nbsp; real-time vet dispatch &nbsp;•&nbsp; pet medical timeline &nbsp;•&nbsp; telehealth vet chat &nbsp;•&nbsp; health analytics &nbsp;•&nbsp; multi-pet support &nbsp;•&nbsp; vaccination reminders &nbsp;•&nbsp; prescription tracking</p>
                    <p class="w-1/2 whitespace-nowrap px-6">24/7 emergency routing &nbsp;•&nbsp; verified clinics &nbsp;•&nbsp; one-tap family alert &nbsp;•&nbsp; encrypted medical history &nbsp;•&nbsp; location-aware care &nbsp;•&nbsp; real-time vet dispatch &nbsp;•&nbsp; pet medical timeline &nbsp;•&nbsp; telehealth vet chat &nbsp;•&nbsp; health analytics &nbsp;•&nbsp; multi-pet support &nbsp;•&nbsp; vaccination reminders &nbsp;•&nbsp; prescription tracking</p>
                </div>
            </section>

            <!-- ═══════════════ TRUSTED BY ═══════════════ -->
            <section class="mt-20 reveal">
                <p class="text-center text-xs uppercase tracking-[0.22em] text-dusk/50 mb-8">Trusted by 4,800+ pet families across 40+ cities</p>
                <div class="flex flex-wrap justify-center items-center gap-10 opacity-70">
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">PetFirst</div>
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">VetCare+</div>
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">AnimaHealth</div>
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">PawClinic</div>
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">HealPet</div>
                    <div class="trust-logo text-lg font-display font-semibold text-dusk/60 tracking-wide">FurWell</div>
                </div>
            </section>

            <!-- ═══════════════ FEATURES ═══════════════ -->
            <section id="features" class="mt-28">
                <div class="text-center mb-14 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">Everything you need</p>
                    <h2 class="font-display text-5xl">Built for the moments<br>that <span class="wave-line">catch you off guard</span>.</h2>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d1">
                        <div class="w-10 h-10 rounded-xl bg-clay/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.03 1.21 2 2 0 012 .03h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">SOS</p>
                        <h3 class="mt-3 font-display text-3xl">Live Dispatch</h3>
                        <p class="mt-3 text-sm text-dusk/80">Automatic clinic matching based on urgency, distance, and on-duty vets — with real-time ETA updates.</p>
                        <a href="#safety" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d2">
                        <div class="w-10 h-10 rounded-xl bg-moss/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Records</p>
                        <h3 class="mt-3 font-display text-3xl">Medical Timeline</h3>
                        <p class="mt-3 text-sm text-dusk/80">Vaccines, notes, and prescriptions in one encrypted timeline shared instantly with your care team.</p>
                        <a href="#journal" class="mt-5 inline-flex items-center gap-1 text-xs text-moss hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d3">
                        <div class="w-10 h-10 rounded-xl bg-amber/15 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Support</p>
                        <h3 class="mt-3 font-display text-3xl">Vet Chat</h3>
                        <p class="mt-3 text-sm text-dusk/80">Symptom guidance, triage steps, and what to do before you arrive — live with a licensed vet.</p>
                        <a href="#journal" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d4">
                        <div class="w-10 h-10 rounded-xl bg-clay/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b86f3f" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Family</p>
                        <h3 class="mt-3 font-display text-3xl">Family Alerts</h3>
                        <p class="mt-3 text-sm text-dusk/80">One tap notifies your whole household — nobody is left out of the loop during an emergency.</p>
                        <a href="#safety" class="mt-5 inline-flex items-center gap-1 text-xs text-clay hover:underline">Learn more <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d5">
                        <div class="w-10 h-10 rounded-xl bg-moss/10 flex items-center justify-center mb-4">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-dusk/60">Privacy</p>
                        <h3 class="mt-3 font-display text-3xl">End-to-End Encryption</h3>
                        <p class="mt-3 text-sm text-dusk/80">Your pet's health data is encrypted at rest and in transit. We never sell or share it.</p>
                        <a href="#faq" class="mt-5 inline-flex items-center gap-1 text-xs text-moss hover:underline">Privacy policy <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    </article>

                    <article class="reveal card-lift shimmer-card rounded-3xl border border-dusk/10 bg-white/70 p-7 d6">
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
                    <div class="hidden md:block absolute top-10 left-[calc(16.66%+2rem)] right-[calc(16.66%+2rem)] h-px bg-gradient-to-r from-clay/30 via-moss/30 to-amber/30 z-0"></div>

                    <div class="reveal flex flex-col items-center text-center d1">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-clay text-cream flex items-center justify-center font-display text-4xl mb-6">1</div>
                        <h3 class="font-display text-2xl mb-2">Tap SOS</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">One button triggers the emergency flow. No login screens, no forms to fill in a crisis.</p>
                    </div>

                    <div class="reveal flex flex-col items-center text-center d3">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-moss text-cream flex items-center justify-center font-display text-4xl mb-6">2</div>
                        <h3 class="font-display text-2xl mb-2">We Find a Vet</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">The platform pings nearby verified clinics and sends your pet's medical profile automatically.</p>
                    </div>

                    <div class="reveal flex flex-col items-center text-center d5">
                        <div class="step-num relative z-10 w-20 h-20 rounded-full bg-amber text-ink flex items-center justify-center font-display text-4xl mb-6">3</div>
                        <h3 class="font-display text-2xl mb-2">Get Guided</h3>
                        <p class="text-sm text-dusk/75 max-w-[220px]">Real-time triage guidance while your family is alerted and a vet slot is confirmed.</p>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ DEMO / PRODUCT TOUR ═══════════════ -->
            <section class="mt-28 reveal-scale">
                <div class="rounded-[2rem] overflow-hidden relative bg-ink">
                    <div class="absolute inset-0" style="background:radial-gradient(circle at 30% 50%,rgba(184,111,63,.18),transparent 55%),radial-gradient(circle at 75% 20%,rgba(79,105,87,.15),transparent 45%);"></div>
                    <div class="relative z-10 px-8 py-14 md:px-14 grid md:grid-cols-2 gap-12 items-center">
                        <div class="reveal-left">
                            <p class="uppercase tracking-[0.2em] text-xs text-amber/80 mb-4">Product tour</p>
                            <h2 class="font-display text-5xl text-cream leading-tight">See Respaw in<br><span class="italic text-amber">action.</span></h2>
                            <p class="mt-5 text-cream/70 max-w-sm leading-relaxed">Watch how a real emergency unfolds on Respaw — from the SOS tap to confirmed clinic in under 3 minutes.</p>
                            <ul class="mt-8 space-y-3 text-sm text-cream/80">
                                <li class="flex items-center gap-3"><span class="w-5 h-5 rounded-full bg-clay/20 border border-clay/40 flex items-center justify-center shrink-0"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>Zero-friction SOS activation</li>
                                <li class="flex items-center gap-3"><span class="w-5 h-5 rounded-full bg-clay/20 border border-clay/40 flex items-center justify-center shrink-0"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>Live clinic availability map</li>
                                <li class="flex items-center gap-3"><span class="w-5 h-5 rounded-full bg-clay/20 border border-clay/40 flex items-center justify-center shrink-0"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>Auto medical profile sharing</li>
                                <li class="flex items-center gap-3"><span class="w-5 h-5 rounded-full bg-clay/20 border border-clay/40 flex items-center justify-center shrink-0"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>Real-time family notifications</li>
                            </ul>
                        </div>
                        <div class="reveal-right flex justify-center items-center">
                            <div class="relative">
                                <!-- Video thumbnail placeholder -->
                                <img src="https://images.unsplash.com/photo-1576201836106-db1758fd1c97?auto=format&fit=crop&w=800&q=80" alt="Demo thumbnail" class="w-full max-w-sm h-64 object-cover rounded-2xl opacity-70">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <button class="play-btn relative w-16 h-16 rounded-full bg-white/95 flex items-center justify-center shadow-soft">
                                        <div class="play-ring"></div>
                                        <div class="play-ring-2"></div>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#161412"><path d="M8 5v14l11-7z"/></svg>
                                    </button>
                                </div>
                                <div class="absolute bottom-4 left-4 right-4 rounded-xl bg-black/50 backdrop-blur-md px-4 py-2 text-cream text-xs flex items-center justify-between">
                                    <span>Emergency SOS walkthrough</span>
                                    <span class="text-cream/60">2:48</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ PET TYPES ═══════════════ -->
            <section class="mt-28">
                <div class="text-center mb-12 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">All pets welcome</p>
                    <h2 class="font-display text-5xl">Care designed for<br>every companion.</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div class="pet-card reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-6 text-center d1">
                        <div class="text-4xl mb-3"><span class="pet-emoji">🐕</span></div>
                        <p class="font-display text-2xl">Dogs</p>
                        <p class="text-xs text-dusk/60 mt-1">All breeds</p>
                    </div>
                    <div class="pet-card reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-6 text-center d2">
                        <div class="text-4xl mb-3"><span class="pet-emoji">🐈</span></div>
                        <p class="font-display text-2xl">Cats</p>
                        <p class="text-xs text-dusk/60 mt-1">Indoor & outdoor</p>
                    </div>
                    <div class="pet-card reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-6 text-center d3">
                        <div class="text-4xl mb-3"><span class="pet-emoji">🐇</span></div>
                        <p class="font-display text-2xl">Rabbits</p>
                        <p class="text-xs text-dusk/60 mt-1">& small mammals</p>
                    </div>
                    <div class="pet-card reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-6 text-center d4">
                        <div class="text-4xl mb-3"><span class="pet-emoji">🦜</span></div>
                        <p class="font-display text-2xl">Birds</p>
                        <p class="text-xs text-dusk/60 mt-1">Avian specialists</p>
                    </div>
                    <div class="pet-card reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-6 text-center d5 col-span-2 md:col-span-4 lg:col-span-1">
                        <div class="text-4xl mb-3"><span class="pet-emoji">🦎</span></div>
                        <p class="font-display text-2xl">Exotic</p>
                        <p class="text-xs text-dusk/60 mt-1">Reptiles & more</p>
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
                        <a href="/app" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-clay px-6 py-3 text-sm text-cream hover:bg-[#a85e31] transition-colors cta-glow">
                            Try Safety Mode
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="reveal-right rounded-2xl border border-white/15 bg-white/10 p-6 backdrop-blur-md">
                        <ul class="space-y-3 text-sm">
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="ping-ring relative w-2 h-2 rounded-full bg-amber"></span>Urgency triage</span>
                                <span class="text-amber font-medium">Ready</span>
                            </li>
                            <li class="flex justify-between items-center border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="ping-ring relative w-2 h-2 rounded-full bg-amber"></span>Closest clinic pinged</span>
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
                                <span class="flex items-center gap-2"><span class="ping-ring relative w-2 h-2 rounded-full bg-moss"></span>Vet chat open</span>
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

                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <article class="testi-card reveal card-lift rounded-3xl p-7 d1">
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

                    <article class="testi-card reveal card-lift rounded-3xl p-7 d3">
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

                    <article class="testi-card reveal card-lift rounded-3xl p-7 d5">
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

                <!-- Extra testimonials row -->
                <div class="grid md:grid-cols-2 gap-6">
                    <article class="testi-card reveal card-lift rounded-3xl p-7 d2">
                        <div class="flex items-center gap-3 mb-5">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=80&q=80" alt="Sara" class="w-11 h-11 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium text-ink">Sara K.</p>
                                <p class="text-xs text-dusk/60">Maine Coon & Bengal owner</p>
                            </div>
                        </div>
                        <p class="text-sm text-dusk/80 leading-relaxed">"I was travelling when my cat had a seizure. My husband used Respaw, I got the family alert, and we were both on the vet call from different cities within minutes. That coordination was everything."</p>
                        <div class="mt-4 text-amber text-xs tracking-widest">★★★★★</div>
                    </article>

                    <article class="testi-card reveal card-lift rounded-3xl p-7 d4">
                        <div class="flex items-center gap-3 mb-5">
                            <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=80&q=80" alt="David" class="w-11 h-11 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium text-ink">David N.</p>
                                <p class="text-xs text-dusk/60">Rabbit & bird keeper</p>
                            </div>
                        </div>
                        <p class="text-sm text-dusk/80 leading-relaxed">"Exotic pet owners get ignored by most apps. Respaw actually has avian and small mammal specialists in their network. Found a rabbit-savvy vet at 11 PM on a Sunday."</p>
                        <div class="mt-4 text-amber text-xs tracking-widest">★★★★★</div>
                    </article>
                </div>
            </section>

            <!-- ═══════════════ PRICING ═══════════════ -->
            <section id="pricing" class="mt-28">
                <div class="text-center mb-14 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">Simple pricing</p>
                    <h2 class="font-display text-5xl">The right plan for<br>every pet family.</h2>
                    <p class="mt-4 text-dusk/70 max-w-xl mx-auto text-sm">Start free. Upgrade when your pet needs more. Cancel anytime.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 items-start">
                    <!-- Free -->
                    <div class="reveal card-lift rounded-3xl border border-dusk/10 bg-white/70 p-8 d1">
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-3">Essentials</p>
                        <div class="flex items-end gap-1 mb-2">
                            <span class="font-display text-6xl">Free</span>
                        </div>
                        <p class="text-sm text-dusk/70 mb-8">Forever. No card required.</p>
                        <a href="/app" class="block w-full text-center rounded-xl border border-dusk/20 px-6 py-3 text-sm hover:bg-cream transition-colors mb-8">Get started free</a>
                        <ul class="space-y-3 text-sm text-dusk/80">
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Up to 3 pets</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Basic health records</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Clinic finder</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Vaccination reminders</li>
                            <li class="flex items-center gap-2 opacity-40"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#24201d" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>SOS emergency routing</li>
                            <li class="flex items-center gap-2 opacity-40"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#24201d" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>Vet chat</li>
                        </ul>
                    </div>

                    <!-- Care (popular) -->
                    <div class="reveal card-lift pricing-popular rounded-3xl bg-white p-8 d3 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-clay px-4 py-1 text-cream text-xs font-medium tracking-wide whitespace-nowrap">Most popular</div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-3">Care</p>
                        <div class="flex items-end gap-1 mb-2">
                            <span class="font-display text-6xl">$9</span>
                            <span class="text-sm text-dusk/60 mb-3">/month</span>
                        </div>
                        <p class="text-sm text-dusk/70 mb-8">Billed monthly. Cancel anytime.</p>
                        <a href="/app" class="block w-full text-center rounded-xl bg-clay px-6 py-3 text-sm text-cream hover:bg-[#a85e31] transition-colors mb-8 cta-glow">Start 14-day free trial</a>
                        <ul class="space-y-3 text-sm text-dusk/80">
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Unlimited pets</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Full medical timeline</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>SOS emergency routing</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Vet chat (24/7)</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Family alerts (up to 5)</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f6957" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Health analytics</li>
                        </ul>
                    </div>

                    <!-- Clinic / Practice -->
                    <div class="reveal card-lift rounded-3xl border border-dusk/10 bg-dusk text-cream p-8 d5">
                        <p class="text-xs uppercase tracking-[0.18em] text-cream/50 mb-3">Practice</p>
                        <div class="flex items-end gap-1 mb-2">
                            <span class="font-display text-6xl">$49</span>
                            <span class="text-sm text-cream/60 mb-3">/month</span>
                        </div>
                        <p class="text-sm text-cream/70 mb-8">For clinics and multi-pet facilities.</p>
                        <a href="#for-clinics" class="block w-full text-center rounded-xl border border-white/25 px-6 py-3 text-sm text-cream hover:bg-white/10 transition-colors mb-8">Contact sales</a>
                        <ul class="space-y-3 text-sm text-cream/80">
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Everything in Care</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Clinic patient dashboard</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Multi-user access</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>API & EHR integration</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>SOS dispatch priority</li>
                            <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Analytics & reporting</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ FOR CLINICS ═══════════════ -->
            <section id="for-clinics" class="mt-28">
                <div class="rounded-[2rem] border border-dusk/15 overflow-hidden">
                    <div class="grid lg:grid-cols-2">
                        <div class="reveal-left p-10 lg:p-14 bg-cream/60">
                            <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-4">For veterinary practices</p>
                            <h2 class="font-display text-5xl leading-tight">Your clinic,<br>always <span class="italic text-moss">ready.</span></h2>
                            <p class="mt-5 text-dusk/75 max-w-lg leading-relaxed">Join the Respaw clinic network. Receive pre-screened emergency cases, view patient medical profiles before they arrive, and reduce intake friction on your busiest nights.</p>
                            <div class="mt-10 grid grid-cols-2 gap-6 text-sm">
                                <div class="stat-box">
                                    <p class="text-3xl font-display stat-count" data-target="320" data-suffix="+">0</p>
                                    <p class="text-dusk/60 mt-1">Verified clinics</p>
                                </div>
                                <div class="stat-box">
                                    <p class="text-3xl font-display stat-count" data-target="98" data-suffix="%">0</p>
                                    <p class="text-dusk/60 mt-1">Case acceptance rate</p>
                                </div>
                                <div class="stat-box">
                                    <p class="text-3xl font-display stat-count" data-target="4" data-suffix=" min">0</p>
                                    <p class="text-dusk/60 mt-1">Avg intake reduction</p>
                                </div>
                                <div class="stat-box">
                                    <p class="text-3xl font-display stat-count" data-target="40" data-suffix="+">0</p>
                                    <p class="text-dusk/60 mt-1">Cities covered</p>
                                </div>
                            </div>
                            <a href="#pricing" class="mt-10 inline-flex items-center gap-2 rounded-xl bg-moss px-6 py-3 text-sm text-cream hover:bg-[#3d5445] transition-colors">
                                Join as a clinic
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                        <div class="reveal-right bg-moss p-10 lg:p-14">
                            <p class="uppercase tracking-[0.18em] text-xs text-cream/60 mb-6">What clinics get</p>
                            <ul class="space-y-5">
                                <li class="flex gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-cream font-medium text-sm">Instant case previews</p>
                                        <p class="text-cream/65 text-xs mt-1">See the pet's profile, urgency level, and owner notes before they arrive.</p>
                                    </div>
                                </li>
                                <li class="flex gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-cream font-medium text-sm">Pre-loaded medical history</p>
                                        <p class="text-cream/65 text-xs mt-1">Full vaccination, prescription, and visit history — no forms to fill at intake.</p>
                                    </div>
                                </li>
                                <li class="flex gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2" stroke-linecap="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-cream font-medium text-sm">Capacity management</p>
                                        <p class="text-cream/65 text-xs mt-1">Set your available slots and auto-decline when full — zero manual coordination.</p>
                                    </div>
                                </li>
                                <li class="flex gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d89d62" stroke-width="2" stroke-linecap="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-cream font-medium text-sm">Practice analytics</p>
                                        <p class="text-cream/65 text-xs mt-1">Monthly dashboards on case volume, species mix, and peak emergency hours.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ JOURNAL / BLOG ═══════════════ -->
            <section id="journal" class="mt-28">
                <div class="flex items-end justify-between gap-6 mb-10 reveal">
                    <div>
                        <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-2">From the journal</p>
                        <h2 class="font-display text-5xl">Care knowledge<br>you can <span class="wave-line">trust</span>.</h2>
                    </div>
                    <a href="/journal" class="text-sm border-b border-dusk/30 hover:border-dusk transition-colors flex items-center gap-1 shrink-0">
                        Read all
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <!-- Category Filters -->
                <div class="flex flex-wrap gap-2 mb-8 reveal">
                    <button class="filter-btn active" data-filter="all">All articles</button>
                    <button class="filter-btn" data-filter="wellness">Wellness</button>
                    <button class="filter-btn" data-filter="emergency">Emergency</button>
                    <button class="filter-btn" data-filter="nutrition">Nutrition</button>
                    <button class="filter-btn" data-filter="routine">Routine</button>
                    <button class="filter-btn" data-filter="behavior">Behavior</button>
                </div>

                <!-- Featured article -->
                <article class="blog-article reveal card-lift rounded-3xl overflow-hidden border border-dusk/10 bg-white/80 mb-6" data-category="wellness">
                    <div class="grid lg:grid-cols-2">
                        <img src="https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1400&q=80" alt="Dog portrait" class="h-72 lg:h-full w-full object-cover">
                        <div class="p-8 lg:p-10 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="category-badge badge-wellness">Wellness</span>
                                <span class="text-xs text-dusk/50">8 min read</span>
                            </div>
                            <h3 class="font-display text-4xl leading-tight">Preparing your home after emergency treatment</h3>
                            <p class="mt-3 text-sm text-dusk/75 leading-relaxed">What to set up, what to remove, and how to build a recovery space your pet actually feels safe in after a stressful vet visit.</p>
                            <a href="/journal/emergency-home-prep" class="mt-6 inline-flex items-center gap-1 text-xs text-clay hover:underline font-medium">Read article <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </div>
                </article>

                <!-- Blog grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" id="blog-grid">

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d1" data-category="emergency">
                        <img src="https://images.unsplash.com/photo-1587300003388-59208cc962cb?auto=format&fit=crop&w=800&q=80" alt="Dog pain signs" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-emergency">Emergency</span>
                                <span class="text-xs text-dusk/50">6 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Signs your dog is in pain: a vet-approved guide</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">Subtle behavioural cues that signal discomfort — before it becomes a crisis.</p>
                            <a href="/journal/dog-pain-signs" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d2" data-category="behavior">
                        <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=800&q=80" alt="Frightened cat" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-behavior">Behavior</span>
                                <span class="text-xs text-dusk/50">4 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">The right way to approach a frightened cat</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">Body language, timing, and the one mistake almost every owner makes.</p>
                            <a href="/journal/frightened-cat" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d3" data-category="wellness">
                        <img src="https://images.unsplash.com/photo-1601758003122-53c40e686a19?auto=format&fit=crop&w=800&q=80" alt="Pet vitals" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-wellness">Wellness</span>
                                <span class="text-xs text-dusk/50">5 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">How to check your pet's vital signs at home</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">Pulse, temperature, breathing rate — what's normal and what triggers a call.</p>
                            <a href="/journal/pet-vital-signs" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d4" data-category="emergency">
                        <img src="https://images.unsplash.com/photo-1576201836106-db1758fd1c97?auto=format&fit=crop&w=800&q=80" alt="Emergency vet" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-emergency">Emergency</span>
                                <span class="text-xs text-dusk/50">7 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">When should you call an emergency vet?</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">A decision framework for the symptoms that can't wait until morning.</p>
                            <a href="/journal/when-emergency-vet" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d5" data-category="routine">
                        <img src="https://images.unsplash.com/photo-1587300003388-59208cc962cb?auto=format&fit=crop&w=800&q=80" alt="Pet first aid kit" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-routine">Routine</span>
                                <span class="text-xs text-dusk/50">5 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Building a pet first-aid kit</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">The 12 items every pet home needs — and the 3 things that actually make the difference.</p>
                            <a href="/journal/pet-first-aid-kit" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d6" data-category="routine">
                        <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=800&q=80" alt="Vaccination" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-routine">Routine</span>
                                <span class="text-xs text-dusk/50">6 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Understanding vaccination schedules</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">Core vs non-core vaccines, booster timing, and why the schedule matters more than the brand.</p>
                            <a href="/journal/vaccination-schedules" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d2" data-category="nutrition">
                        <img src="https://images.unsplash.com/photo-1589924691995-400dc9a28a39?auto=format&fit=crop&w=800&q=80" alt="Pet food" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-nutrition">Nutrition</span>
                                <span class="text-xs text-dusk/50">5 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Reading pet food labels — a plain-language guide</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">What the first five ingredients tell you, and what marketing language to ignore.</p>
                            <a href="/journal/pet-food-labels" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d3" data-category="routine">
                        <img src="https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=800&q=80" alt="Medication reminders" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-routine">Routine</span>
                                <span class="text-xs text-dusk/50">4 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Medication reminders that actually work</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">Build a repeatable weekly rhythm for meds, checkups, and follow-up notes.</p>
                            <a href="/journal/medication-reminders" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                    <article class="blog-article reveal card-lift rounded-3xl border border-dusk/10 bg-white/80 overflow-hidden d4" data-category="behavior">
                        <img src="https://images.unsplash.com/photo-1560807707-8cc77767d783?auto=format&fit=crop&w=800&q=80" alt="Pet anxiety" class="h-44 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="category-badge badge-behavior">Behavior</span>
                                <span class="text-xs text-dusk/50">6 min read</span>
                            </div>
                            <h3 class="font-display text-2xl leading-snug mt-1">Recognising anxiety in dogs and cats</h3>
                            <p class="mt-2 text-xs text-dusk/70 leading-relaxed">The difference between stress and fear — and what to do about each.</p>
                            <a href="/journal/pet-anxiety" class="mt-4 inline-flex items-center gap-1 text-xs text-clay hover:underline">Read <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                        </div>
                    </article>

                </div>

                <div class="text-center mt-10 reveal">
                    <a href="/journal" class="inline-flex items-center gap-2 rounded-xl border border-dusk/20 bg-cream/80 px-8 py-3 text-sm hover:bg-cream transition-colors">
                        View all articles
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </section>

            <!-- ═══════════════ FAQ ═══════════════ -->
            <section id="faq" class="mt-28">
                <div class="text-center mb-14 reveal">
                    <p class="uppercase tracking-[0.22em] text-xs text-dusk/60 mb-3">Questions answered</p>
                    <h2 class="font-display text-5xl">Everything you<br>need to know.</h2>
                </div>

                <div class="max-w-3xl mx-auto reveal">
                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">Is Respaw available 24/7, including holidays?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">Yes. The SOS emergency routing, live vet chat, and clinic dispatch system operate 24 hours a day, 365 days a year — including all public holidays. Our clinic network maintains minimum on-duty coverage requirements to ensure response times stay under 3 minutes.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">How fast is the emergency response?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">Our average first response time — from SOS tap to a vet confirming — is 2 minutes. Clinic ETA varies by location, but we always show you a confirmed slot before you leave home so there's no guesswork on arrival.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">What happens if there's no clinic nearby?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">If no physical clinic is reachable within a safe time frame, we immediately connect you with a licensed telehealth vet who can provide triage guidance, assess severity, and coordinate transport to the nearest available clinic. You're never left without support.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">Is my pet's health data private and secure?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">Absolutely. All medical data is AES-256 encrypted at rest and in transit. We are HIPAA-aligned for veterinary health records. We never sell, share, or use your pet's data for advertising. Clinics can only access records when you explicitly activate an SOS or share a QR profile.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">Can I add multiple pets and family members?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">The free plan supports up to 3 pets. The Care plan allows unlimited pets. Family members can be added as secondary contacts who receive alert notifications — up to 5 on Care, unlimited on Practice. Each family member gets a read-only view of the pet's timeline.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">Does Respaw work outside my country?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">Emergency SOS routing is currently available in 40+ cities across India. Medical records, vet chat, and the health timeline work globally. We're actively expanding our clinic network — you can check coverage for your city in the app or sign up to be notified when we launch in a new area.</p>
                        </div>
                    </div>

                    <div class="faq-item py-5 px-1 border-b-0">
                        <button class="faq-trigger w-full flex items-center justify-between gap-4 text-left">
                            <span class="font-medium text-ink text-base">Can I cancel my subscription at any time?</span>
                            <span class="faq-icon w-8 h-8 rounded-full border border-dusk/20 flex items-center justify-center shrink-0 text-dusk/60">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                        <div class="faq-content">
                            <p class="text-sm text-dusk/75 leading-relaxed pt-3 pb-1">Yes. Cancel from your account settings at any time with no penalty. You keep access until the end of your billing period. All your pet's medical data is yours — you can export a full PDF archive before or after cancelling.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ APP DOWNLOAD ═══════════════ -->
            <section class="mt-20 reveal-scale">
                <div class="rounded-[2rem] overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-moss via-[#3a5244] to-ink"></div>
                    <div class="absolute inset-0 opacity-25" style="background-image:radial-gradient(circle at 72% 28%,rgba(216,157,98,.6),transparent 50%);"></div>
                    <div class="relative z-10 px-8 py-14 md:px-14 grid md:grid-cols-2 gap-10 items-center">
                        <div class="reveal-left">
                            <p class="uppercase tracking-[0.2em] text-xs text-amber/80 mb-3">Available everywhere</p>
                            <h2 class="font-display text-5xl text-cream leading-tight">Calm care,<br>in your pocket.</h2>
                            <p class="mt-4 text-cream/70 max-w-sm">Download Respaw on iOS or Android. Emergency features work even on slow connections.</p>
                            <div class="mt-8 flex flex-wrap gap-4">
                                <a href="#" class="store-btn">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                    <div class="text-left"><p class="text-[10px] opacity-70">Download on the</p><p class="text-sm font-medium">App Store</p></div>
                                </a>
                                <a href="#" class="store-btn">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.76c.3.17.64.24.99.2l12.6-12.6L13.23 7.8 3.18 23.76zm17.49-9.89l-2.7-1.56-3.03 3.03 3.03 3.03 2.73-1.58c.78-.45.78-1.47-.03-1.92zM2.07 1.52C2.02 1.7 2 1.89 2 2.1V21.9c0 .21.02.4.07.57l.06.06L13.16 11.5l.01-.01L2.13 1.46l-.06.06zm11.74 10L2.22.81c.33-.19.71-.25 1.07-.14l13.12 7.58-2.6 2.27z"/></svg>
                                    <div class="text-left"><p class="text-[10px] opacity-70">Get it on</p><p class="text-sm font-medium">Google Play</p></div>
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
                            <h2 class="font-display text-5xl mt-3">Get updates without<br>the spam vibe.</h2>
                            <p class="text-dusk/75 mt-3 max-w-xl">One monthly digest — product updates, emergency care tips, and local vet events near you.</p>
                        </div>
                        <form class="lg:col-span-5 flex flex-col sm:flex-row gap-3" onsubmit="return false;">
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
                <div class="grid grid-cols-2 md:grid-cols-5 gap-10 mb-14">
                    <div class="col-span-2 md:col-span-2">
                        <a href="#" class="font-display text-2xl italic">Respaw</a>
                        <p class="mt-3 text-sm text-dusk/70 max-w-[240px] leading-relaxed">Calm emergency care for the pets you love most. Available 24/7 across 40+ cities.</p>
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
                            <a href="#" aria-label="LinkedIn" class="text-dusk/50 hover:text-clay transition-colors">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                            </a>
                        </div>
                        <div class="mt-6 flex items-center gap-2 text-xs text-dusk/60">
                            <span class="ping-ring relative w-2 h-2 rounded-full bg-moss"></span>
                            All systems operational
                        </div>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-4">Product</p>
                        <ul class="space-y-3 text-sm text-dusk/75">
                            <li><a href="#features"     class="hover:text-ink transition-colors">Features</a></li>
                            <li><a href="#how-it-works" class="hover:text-ink transition-colors">How it works</a></li>
                            <li><a href="#safety"       class="hover:text-ink transition-colors">Safety Mode</a></li>
                            <li><a href="#pricing"      class="hover:text-ink transition-colors">Pricing</a></li>
                            <li><a href="#for-clinics"  class="hover:text-ink transition-colors">For Clinics</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-dusk/50 mb-4">Resources</p>
                        <ul class="space-y-3 text-sm text-dusk/75">
                            <li><a href="#journal" class="hover:text-ink transition-colors">Care Journal</a></li>
                            <li><a href="#faq"     class="hover:text-ink transition-colors">FAQ</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">Emergency Guide</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">Vet Directory</a></li>
                            <li><a href="#"        class="hover:text-ink transition-colors">API Docs</a></li>
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
            const d = document.documentElement;
            const pct = d.scrollHeight - window.innerHeight > 0
                ? (window.scrollY / (d.scrollHeight - window.innerHeight)) * 100 : 0;
            d.style.setProperty('--progress', pct + '%');
        }

        /* ── Back-to-top ── */
        const backTop = document.getElementById('back-top');
        function updateBackTop() { backTop.classList.toggle('visible', window.scrollY > 500); }

        /* ── Parallax hero ── */
        const heroImg = document.getElementById('hero-img');
        function updateParallax() {
            if (heroImg) heroImg.style.transform = 'translateY(' + (window.scrollY * 0.14) + 'px)';
        }

        window.addEventListener('scroll', () => {
            updateProgress(); updateBackTop(); updateParallax();
        }, { passive: true });

        /* ── Intersection observer — reveal ── */
        const revealObs = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('show'); revealObs.unobserve(e.target); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale').forEach(el => revealObs.observe(el));

        /* ── Animated counters ── */
        function animateCounter(el) {
            const target = parseInt(el.dataset.target, 10);
            const suffix = el.dataset.suffix || '';
            const dur = 1600, start = performance.now();
            (function step(now) {
                const p = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(eased * target).toLocaleString() + suffix;
                if (p < 1) requestAnimationFrame(step);
            })(start);
        }
        const cntObs = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); cntObs.unobserve(e.target); } });
        }, { threshold: 0.4 });
        document.querySelectorAll('.stat-count[data-target]').forEach(el => cntObs.observe(el));

        /* ── Smooth anchor scroll ── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', function(e) {
                const id = this.getAttribute('href');
                if (id === '#') return;
                const target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                const navH = document.querySelector('header').offsetHeight + 16;
                window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - navH, behavior: 'smooth' });
                // Close mobile menu
                document.getElementById('mobile-menu').classList.remove('open');
            });
        });

        /* ── Mobile menu toggle ── */
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
        });

        /* ── FAQ accordion ── */
        document.querySelectorAll('.faq-trigger').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const content = item.querySelector('.faq-content');
                const isOpen = item.classList.contains('open');
                // Close all
                document.querySelectorAll('.faq-item').forEach(fi => {
                    fi.classList.remove('open');
                    fi.querySelector('.faq-content').classList.remove('open');
                });
                // Open clicked if it was closed
                if (!isOpen) {
                    item.classList.add('open');
                    content.classList.add('open');
                }
            });
        });

        /* ── Blog category filter ── */
        const filterBtns = document.querySelectorAll('.filter-btn');
        const blogArticles = document.querySelectorAll('#blog-grid .blog-article');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const filter = btn.dataset.filter;
                blogArticles.forEach(article => {
                    if (filter === 'all' || article.dataset.category === filter) {
                        article.style.display = '';
                        article.style.opacity = '0';
                        article.style.transform = 'translateY(16px)';
                        requestAnimationFrame(() => requestAnimationFrame(() => {
                            article.style.transition = 'opacity .4s ease, transform .4s ease';
                            article.style.opacity = '1';
                            article.style.transform = 'translateY(0)';
                        }));
                    } else {
                        article.style.transition = 'opacity .25s ease';
                        article.style.opacity = '0';
                        setTimeout(() => { article.style.display = 'none'; }, 250);
                    }
                });
            });
        });

        /* ── Init ── */
        updateProgress();
        updateBackTop();
    </script>
</body>
</html>
