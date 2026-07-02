<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESPAW / Pet Help — Talk to a trusted vet, anytime | Pet telehealth</title>
    <meta name="description" content="RESPAW / Pet Help is a pet telehealth platform. Talk to a licensed vet by video or audio, get prescriptions, and keep your pet's health records in one place. Vets: onboard, set your hours, and earn on your schedule.">
    <meta name="theme-color" content="#FF6B1F">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="RESPAW / Pet Help — Talk to a trusted vet, anytime">
    <meta property="og:description" content="Pet telehealth: video/audio vet consultations, prescriptions, pet records and secure payments. For pet owners and veterinarians.">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind + GSAP (CDN — fine for now; move to a build step before scale) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    @verbatim
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary:      '#FF6B1F',
              primaryDark:  '#E55A0E',
              primarySoft:  '#FFF1E8',
              vet:          '#1B6CA8',
              vetDark:      '#0F4A75',
              vetLight:     '#3B8ECA',
              vetAccent:    '#00C9A7',
              vetSoft:      '#EAF3FA',
              ink:          '#1A1A1A',
              slate700:     '#374151',
              slate500:     '#6B7280',
              canvas:       '#F8F8F6',
              coral:        '#FF8A5C',
            },
            fontFamily: {
              display: ['Fraunces', 'Georgia', 'serif'],
              sans:    ['Inter', 'system-ui', 'sans-serif'],
            },
          }
        }
      }
    </script>
    <style>
      html { scroll-behavior: smooth; }
      body { font-family: 'Inter', system-ui, sans-serif; color:#1A1A1A; background:#F8F8F6; }
      h1,h2,h3,.font-display { font-family:'Fraunces', Georgia, serif; }

      /* Accent variable that switches with audience */
      :root { --accent:#FF6B1F; --accent-dark:#E55A0E; --accent-soft:#FFF1E8; }
      body.vet-mode { --accent:#1B6CA8; --accent-dark:#0F4A75; --accent-soft:#EAF3FA; }
      .accent-bg { background-color: var(--accent); }
      .accent-text { color: var(--accent-dark); }
      .accent-btn { background-color: var(--accent); color:#fff; transition: transform .15s ease, background-color .3s ease; }
      .accent-btn:hover { background-color: var(--accent-dark); }
      .accent-btn:active { transform: scale(.98); }
      .accent-ring:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

      .hero-grad { background: linear-gradient(135deg,#FF6B1F 0%,#FF8A4C 50%,#FFB07A 100%); }
      .vet-grad  { background: linear-gradient(135deg,#1B6CA8 0%,#2E86C1 60%,#00C9A7 100%); }

      /* Soft drifting blobs */
      .blob { position:absolute; border-radius:50%; filter: blur(60px); opacity:.5; z-index:0; }
      @keyframes drift { 0%{transform:translate(0,0)} 50%{transform:translate(20px,-24px)} 100%{transform:translate(0,0)} }
      .blob-anim { animation: drift 14s ease-in-out infinite; }

      @keyframes floaty { 0%{transform:translateY(0)} 50%{transform:translateY(-10px)} 100%{transform:translateY(0)} }
      .floaty { animation: floaty 5s ease-in-out infinite; }

      @keyframes softPulse { 0%,100%{ box-shadow:0 0 0 0 rgba(255,138,92,.5) } 50%{ box-shadow:0 0 0 14px rgba(255,138,92,0) } }
      .soft-pulse { animation: softPulse 2.6s ease-in-out infinite; }

      /* Reveal (GSAP toggles .is-in; CSS fallback if JS/motion off) */
      .reveal { opacity:0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
      .reveal.is-in { opacity:1; transform:none; }

      /* Audience toggle thumb */
      .seg { position:relative; background:#F1EEEA; border-radius:999px; padding:4px; display:inline-flex; }
      .seg .thumb { position:absolute; top:4px; bottom:4px; width:calc(50% - 4px); border-radius:999px; background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.08); transition: transform .35s cubic-bezier(.4,0,.2,1); z-index:0; }
      body.vet-mode .seg .thumb { transform: translateX(100%); }
      .seg button { position:relative; z-index:1; padding:.6rem 1.25rem; border-radius:999px; font-weight:600; color:#6B7280; transition:color .3s ease; }
      .seg button.on { color:#1A1A1A; }

      /* Audience content cross-fade */
      .aud { transition: opacity .35s ease, transform .35s ease; }
      .aud-hide { opacity:0; transform: translateY(8px); position:absolute; pointer-events:none; }

      .card-hover { transition: transform .2s ease, box-shadow .2s ease; }
      .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,.08); }

      .step.active { border-color: var(--accent); background: var(--accent-soft); }
      .step.active .step-num { background: var(--accent); color:#fff; }

      details > summary { list-style:none; cursor:pointer; }
      details > summary::-webkit-details-marker { display:none; }
      details[open] .faq-chev { transform: rotate(180deg); }
      .faq-chev { transition: transform .25s ease; }

      @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior:auto; }
        .reveal { opacity:1 !important; transform:none !important; transition:none; }
        .blob-anim, .floaty, .soft-pulse { animation:none !important; }
        * { transition:none !important; }
      }
    </style>
    @endverbatim
</head>
<body class="antialiased text-ink">

<!-- ============ HEADER ============ -->
<header id="site-header" class="fixed top-0 inset-x-0 z-50 transition-all duration-300">
  <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
    <a href="#top" class="flex items-center gap-2 font-display font-bold text-lg text-ink">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl accent-bg text-white">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 8.5a2 2 0 1 1-.001-3.999A2 2 0 0 1 7 8.5Zm10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM4.5 13a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm15 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM12 22c-3 0-5-1.8-5-4.2 0-1.9 1.6-3 2.7-4 .9-.8 1.3-1.8 2.3-1.8s1.4 1 2.3 1.8c1.1 1 2.7 2.1 2.7 4C17 20.2 15 22 12 22Z"/></svg>
      </span>
      <span>RESPAW <span class="text-slate500 font-normal">/ Pet Help</span></span>
    </a>
    <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate700">
      <a href="#how" class="hover:text-ink">How it works</a>
      <a href="#emergency" class="hover:text-ink">Emergency</a>
      <a href="#features" class="hover:text-ink">Features</a>
      <a href="#for-vets" class="hover:text-ink">For vets</a>
      <a href="#faq" class="hover:text-ink">FAQ</a>
    </nav>
    <div class="flex items-center gap-3">
      <a href="#for-vets" data-cross="vet" class="hidden sm:inline text-sm font-semibold accent-text">Are you a vet? →</a>
      <a href="#waitlist" class="accent-btn accent-ring rounded-xl px-4 py-2 text-sm font-semibold">Get the app</a>
    </div>
  </div>
</header>

<main id="top">

<!-- ============ HERO ============ -->
<section class="relative overflow-hidden pt-28 pb-20 md:pt-36 md:pb-28">
  <div class="blob blob-anim hero-grad" style="width:420px;height:420px;top:-80px;right:-60px;"></div>
  <div class="blob blob-anim" style="width:360px;height:360px;bottom:-120px;left:-80px;background:#00C9A7;opacity:.25;animation-delay:-4s;"></div>

  <div class="max-w-6xl mx-auto px-5 grid md:grid-cols-2 gap-12 items-center relative z-10">
    <div>
      <!-- Audience toggle -->
      <div class="seg mb-7 reveal" role="tablist" aria-label="Choose audience">
        <span class="thumb" aria-hidden="true"></span>
        <button id="tab-owner" class="on" role="tab" aria-selected="true">I have a pet</button>
        <button id="tab-vet" role="tab" aria-selected="false">I'm a vet</button>
      </div>

      <!-- Owner hero -->
      <div id="hero-owner" class="aud">
        <h1 class="text-4xl md:text-5xl font-bold leading-tight tracking-tight">
          Talk to a trusted vet,<br><span class="accent-text">without leaving home.</span>
        </h1>
        <p class="mt-5 text-lg text-slate700 max-w-md">
          RESPAW / Pet&nbsp;Help is pet telehealth. Book a video or audio consultation with a licensed vet,
          get a prescription, and keep your pet's health records all in one place.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <a href="#waitlist" class="accent-btn accent-ring rounded-xl px-6 py-3 font-semibold">Book a consultation</a>
          <a href="#how" class="rounded-xl px-6 py-3 font-semibold border border-slate-200 bg-white hover:bg-canvas transition">How it works</a>
        </div>
      </div>

      <!-- Vet hero -->
      <div id="hero-vet" class="aud aud-hide">
        <h1 class="text-4xl md:text-5xl font-bold leading-tight tracking-tight">
          Treat more pets.<br><span class="accent-text">Earn on your schedule.</span>
        </h1>
        <p class="mt-5 text-lg text-slate700 max-w-md">
          Join as a verified vet, set your own availability, run online consultations, and get paid to your
          wallet — we handle scheduling, records and payments for you.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <a href="#for-vets" class="accent-btn accent-ring rounded-xl px-6 py-3 font-semibold">Join as a vet</a>
          <a href="#for-vets" class="rounded-xl px-6 py-3 font-semibold border border-slate-200 bg-white hover:bg-canvas transition">See vet benefits</a>
        </div>
      </div>

      <div class="mt-8 flex items-center gap-5 text-sm text-slate500 reveal">
        <span class="flex items-center gap-1.5"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-vetAccent"><path d="M20 6 9 17l-5-5"/></svg> Verified vets</span>
        <span class="flex items-center gap-1.5"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-vetAccent"><path d="M20 6 9 17l-5-5"/></svg> Secure payments</span>
        <span class="flex items-center gap-1.5"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-vetAccent"><path d="M20 6 9 17l-5-5"/></svg> Records you own</span>
      </div>
    </div>

    <!-- Hero illustration: phone with vet video call -->
    <div class="relative flex justify-center reveal">
      <div class="floaty relative">
        <!-- Phone frame -->
        <div class="w-[270px] h-[540px] rounded-[2.5rem] bg-ink p-3 shadow-2xl">
          <div class="w-full h-full rounded-[2rem] bg-white overflow-hidden relative">
            <div class="hero-grad h-40 relative flex items-end justify-center pb-3">
              <!-- vet on call avatar -->
              <div class="absolute top-3 right-3 h-20 w-16 rounded-lg bg-white/90 flex items-center justify-center">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#1B6CA8" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
              </div>
              <div class="text-white text-center">
                <svg class="mx-auto mb-1" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="m23 7-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                <p class="text-xs font-semibold">Dr. Sharma • live</p>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <div class="flex items-center gap-2">
                <span class="h-9 w-9 rounded-full bg-primarySoft flex items-center justify-center">🐕</span>
                <div><p class="text-sm font-semibold">Bruno</p><p class="text-[10px] text-slate500">Labrador • 3 yrs</p></div>
              </div>
              <div class="rounded-xl border border-slate-200 p-3">
                <p class="text-[10px] uppercase tracking-wide text-slate500">Prescription</p>
                <p class="text-xs mt-1 text-slate700">Sent to Bruno's records ✓</p>
              </div>
              <div class="rounded-xl bg-primarySoft p-3">
                <p class="text-[10px] uppercase tracking-wide accent-text font-semibold">Payment</p>
                <p class="text-xs mt-1 text-slate700">₹300 paid securely ✓</p>
              </div>
            </div>
          </div>
        </div>
        <!-- floating chips -->
        <div class="absolute -left-6 top-24 bg-white rounded-xl shadow-lg px-3 py-2 text-xs font-semibold floaty" style="animation-delay:-1.5s">📋 Records saved</div>
        <div class="absolute -right-6 bottom-28 bg-white rounded-xl shadow-lg px-3 py-2 text-xs font-semibold floaty" style="animation-delay:-3s">💊 e-Prescription</div>
      </div>
    </div>
  </div>
  <!-- IMAGE SLOT: replace phone illustration above with a real hero photo (happy owner + pet on a video call) when licensed assets are ready. -->
</section>

<!-- ============ STATS BAR ============ -->
<section class="border-y border-slate-200 bg-white">
  <div class="max-w-6xl mx-auto px-5 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <div class="reveal"><div class="text-3xl font-display font-bold accent-text" data-count="12000" data-suffix="+">0</div><p class="text-sm text-slate500 mt-1">Consultations ready to serve</p></div>
    <div class="reveal"><div class="text-3xl font-display font-bold accent-text" data-count="500" data-suffix="+">0</div><p class="text-sm text-slate500 mt-1">Verified vets onboarding</p></div>
    <div class="reveal"><div class="text-3xl font-display font-bold accent-text" data-count="4.8" data-suffix="★">0</div><p class="text-sm text-slate500 mt-1">Target app rating</p></div>
    <div class="reveal"><div class="text-3xl font-display font-bold accent-text" data-count="24" data-suffix="/7">0</div><p class="text-sm text-slate500 mt-1">Advice, day or night</p></div>
  </div>
  <!-- NOTE: figures above are illustrative placeholders — swap for real metrics before launch. -->
</section>

<!-- ============ HOW IT WORKS — OWNERS ============ -->
<section id="how" class="py-20 md:py-28">
  <div class="max-w-6xl mx-auto px-5">
    <div class="text-center max-w-2xl mx-auto reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-widest text-primaryDark bg-primarySoft rounded-full px-3 py-1">For pet owners</span>
      <h2 class="text-3xl md:text-4xl font-bold mt-4">How it works</h2>
      <p class="text-slate700 mt-3">From worried to reassured in four simple steps — multiple pets, one profile.</p>
    </div>

    <div class="mt-14 grid md:grid-cols-2 gap-8 items-start">
      <!-- Stepper -->
      <ol id="owner-steps" class="space-y-4">
        <li class="step active card-hover cursor-pointer border-2 border-slate-200 rounded-2xl p-5 bg-white flex gap-4" data-step="0">
          <span class="step-num shrink-0 h-9 w-9 rounded-full bg-slate-200 text-slate700 font-bold flex items-center justify-center">1</span>
          <div><h3 class="font-semibold text-lg">Find a vet</h3><p class="text-slate700 text-sm mt-1">Search by need, language, or specialization and pick a licensed vet that fits.</p></div>
        </li>
        <li class="step card-hover cursor-pointer border-2 border-slate-200 rounded-2xl p-5 bg-white flex gap-4" data-step="1">
          <span class="step-num shrink-0 h-9 w-9 rounded-full bg-slate-200 text-slate700 font-bold flex items-center justify-center">2</span>
          <div><h3 class="font-semibold text-lg">Book &amp; pay securely</h3><p class="text-slate700 text-sm mt-1">Choose instant or scheduled, then pay safely with Razorpay. Auto-refund if a consult can't be completed.</p></div>
        </li>
        <li class="step card-hover cursor-pointer border-2 border-slate-200 rounded-2xl p-5 bg-white flex gap-4" data-step="2">
          <span class="step-num shrink-0 h-9 w-9 rounded-full bg-slate-200 text-slate700 font-bold flex items-center justify-center">3</span>
          <div><h3 class="font-semibold text-lg">Consult by video or audio</h3><p class="text-slate700 text-sm mt-1">Join a private call and describe what's wrong. Chat is available too.</p></div>
        </li>
        <li class="step card-hover cursor-pointer border-2 border-slate-200 rounded-2xl p-5 bg-white flex gap-4" data-step="3">
          <span class="step-num shrink-0 h-9 w-9 rounded-full bg-slate-200 text-slate700 font-bold flex items-center justify-center">4</span>
          <div><h3 class="font-semibold text-lg">Get your prescription &amp; records</h3><p class="text-slate700 text-sm mt-1">Prescriptions and notes are saved to your pet's profile — vaccinations, history, and lab reports all with you.</p></div>
        </li>
      </ol>

      <!-- Step visual -->
      <div class="sticky top-24 reveal">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 min-h-[340px] flex items-center justify-center">
          <div id="owner-visual" class="text-center"></div>
        </div>
        <div class="text-center mt-6">
          <a href="#waitlist" class="accent-btn accent-ring rounded-xl px-6 py-3 font-semibold inline-block">Book a consultation</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ EMERGENCY ============ -->
<section id="emergency" class="py-20 md:py-28 bg-white relative overflow-hidden">
  <div class="blob" style="width:320px;height:320px;top:-60px;left:-60px;background:#FF8A5C;opacity:.18;"></div>
  <div class="max-w-6xl mx-auto px-5 relative z-10">
    <div class="text-center max-w-2xl mx-auto reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-widest text-white rounded-full px-3 py-1" style="background:#FF8A5C;">A vet in your pocket</span>
      <h2 class="text-3xl md:text-4xl font-bold mt-4">Even when it can't wait</h2>
      <p class="text-slate700 mt-3">Stay calm and know what to do. Free vet-approved first-aid tips are always available — and when you need more, talk to a vet now by video.</p>
    </div>

    <div class="mt-14 grid md:grid-cols-3 gap-6" id="emergency-ladder">
      <div class="reveal card-hover rounded-2xl border border-slate-200 p-6 bg-canvas">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center mb-4" style="background:#FFF1E8;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/><path d="M9 12h6M12 9v6"/></svg>
        </div>
        <h3 class="font-semibold text-lg">Know what to do</h3>
        <p class="text-slate700 text-sm mt-2">Vet-approved first aid for common emergencies — poisoning, choking, seizures, heatstroke, bleeding and more. Clear "do this now / don't do this / get to a vet if…" guidance that works even offline.</p>
        <span class="inline-block mt-3 text-xs font-semibold text-vetAccent">Free • always available</span>
      </div>

      <div class="reveal card-hover rounded-2xl border-2 border-coral p-6 bg-white soft-pulse">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center mb-4" style="background:#FF8A5C;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="m23 7-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
        </div>
        <h3 class="font-semibold text-lg">Talk to a vet now</h3>
        <p class="text-slate700 text-sm mt-2">One tap connects you to the first available vet by live video or audio. Even a two-minute answer can be the difference — and your payment is held and refunded if no vet connects.</p>
        <span class="inline-block mt-3 text-xs font-semibold" style="color:#E55A0E;">Premium consult</span>
      </div>

      <div class="reveal card-hover rounded-2xl border border-slate-200 p-6 bg-canvas">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center mb-4" style="background:#EAF3FA;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1B6CA8" stroke-width="2"><path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <h3 class="font-semibold text-lg">Find a 24/7 clinic nearby</h3>
        <p class="text-slate700 text-sm mt-2">When hands-on care is needed, we point you to nearby emergency clinics — directly, or when your vet refers you in person.</p>
        <span class="inline-block mt-3 text-xs font-semibold text-vet">Directory &amp; map</span>
      </div>
    </div>

    <p class="text-center text-xs text-slate500 mt-8 max-w-xl mx-auto">
      First-aid tips are informational only and <strong>not a substitute for emergency veterinary care.</strong>
      In a life-threatening emergency, contact your nearest veterinary clinic immediately.
    </p>
    <div class="text-center mt-6">
      <a href="#for-vets" data-cross="vet" class="text-sm font-semibold text-vet hover:text-vetDark">Vets: go on-call and earn a higher share on emergency consults →</a>
    </div>
  </div>
</section>

<!-- ============ FEATURES ============ -->
<section id="features" class="py-20 md:py-28">
  <div class="max-w-6xl mx-auto px-5">
    <div class="text-center max-w-2xl mx-auto reveal">
      <h2 class="text-3xl md:text-4xl font-bold">Everything your pet's health needs</h2>
      <p class="text-slate700 mt-3">One platform for care, records, and payments — built for pet parents and the vets who treat them.</p>
    </div>

    <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- feature cards -->
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><path d="m23 7-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>
        <h3 class="font-semibold text-lg">Online consultations</h3>
        <p class="text-slate700 text-sm mt-2">Video, audio or chat with a licensed vet — instant or scheduled.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></div>
        <h3 class="font-semibold text-lg">Pet profiles &amp; records</h3>
        <p class="text-slate700 text-sm mt-2">Breed, age, allergies, vaccinations, lab reports and full consult history.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
        <h3 class="font-semibold text-lg">Appointments</h3>
        <p class="text-slate700 text-sm mt-2">Book, reschedule or cancel — online or in-clinic, with reminders.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></div>
        <h3 class="font-semibold text-lg">Secure payments &amp; wallet</h3>
        <p class="text-slate700 text-sm mt-2">Pay safely via Razorpay; vets get transparent payouts to their wallet.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><path d="M12 2 15 9l7 .5-5.5 4.5L18 21l-6-3.8L6 21l1.5-7L2 9.5 9 9z"/></svg></div>
        <h3 class="font-semibold text-lg">Reviews &amp; ratings</h3>
        <p class="text-slate700 text-sm mt-2">Real ratings from real pet parents help you choose with confidence.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-6">
        <div class="h-11 w-11 rounded-xl bg-primarySoft flex items-center justify-center mb-4"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><path d="M12 2v20M2 7h20M2 17h20"/></svg></div>
        <h3 class="font-semibold text-lg">Subscriptions</h3>
        <p class="text-slate700 text-sm mt-2">Plans for families who consult often — more care, better value.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS — VETS ============ -->
<section id="for-vets" class="py-20 md:py-28 relative overflow-hidden" style="background:#EAF3FA;">
  <div class="blob" style="width:360px;height:360px;bottom:-100px;right:-80px;background:#00C9A7;opacity:.2;"></div>
  <div class="max-w-6xl mx-auto px-5 relative z-10">
    <div class="text-center max-w-2xl mx-auto reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-widest text-white bg-vet rounded-full px-3 py-1">For veterinarians</span>
      <h2 class="text-3xl md:text-4xl font-bold mt-4">Grow your practice online</h2>
      <p class="text-slate700 mt-3">Fill idle hours, reach clients beyond your locality, and let us handle scheduling, records and payments.</p>
    </div>

    <div class="mt-14 grid md:grid-cols-4 gap-5">
      <div class="reveal card-hover rounded-2xl bg-white p-6 border border-vetSoft">
        <span class="h-9 w-9 rounded-full bg-vet text-white font-bold flex items-center justify-center mb-4">1</span>
        <h3 class="font-semibold">Apply &amp; get verified</h3>
        <p class="text-slate700 text-sm mt-2">Sign up and upload your KYC documents. Admin reviews and approves your account.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white p-6 border border-vetSoft">
        <span class="h-9 w-9 rounded-full bg-vet text-white font-bold flex items-center justify-center mb-4">2</span>
        <h3 class="font-semibold">Set your availability</h3>
        <p class="text-slate700 text-sm mt-2">Manage slots, holidays and hours. Toggle on-call to receive emergency requests.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white p-6 border border-vetSoft">
        <span class="h-9 w-9 rounded-full bg-vet text-white font-bold flex items-center justify-center mb-4">3</span>
        <h3 class="font-semibold">Consult &amp; treat</h3>
        <p class="text-slate700 text-sm mt-2">Accept bookings and instant consults, write notes and issue prescriptions.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white p-6 border border-vetSoft">
        <span class="h-9 w-9 rounded-full bg-vet text-white font-bold flex items-center justify-center mb-4">4</span>
        <h3 class="font-semibold">Get paid</h3>
        <p class="text-slate700 text-sm mt-2">Earnings land in your wallet; request payouts on the normal cycle. Earn a higher share on emergencies.</p>
      </div>
    </div>

    <div class="mt-12 grid md:grid-cols-3 gap-4 text-sm">
      <div class="reveal flex items-start gap-3 bg-white/70 rounded-xl p-4"><span class="text-vetAccent">✓</span> Extra income from idle hours</div>
      <div class="reveal flex items-start gap-3 bg-white/70 rounded-xl p-4"><span class="text-vetAccent">✓</span> Reach clients beyond your clinic</div>
      <div class="reveal flex items-start gap-3 bg-white/70 rounded-xl p-4"><span class="text-vetAccent">✓</span> Build your reputation with reviews</div>
    </div>

    <div class="text-center mt-10">
      <a href="#waitlist" class="rounded-xl px-6 py-3 font-semibold text-white inline-block" style="background:#1B6CA8;">Join as a vet</a>
    </div>
  </div>
</section>

<!-- ============ APP SHOWCASE ============ -->
<section class="py-20 md:py-28">
  <div class="max-w-6xl mx-auto px-5 grid md:grid-cols-2 gap-12 items-center">
    <div class="reveal">
      <h2 class="text-3xl md:text-4xl font-bold">Two apps, one connected platform</h2>
      <p class="text-slate700 mt-4">The pet-owner app and the vet app talk to the same secure backend — consultations, records, payments and notifications stay in sync in real time.</p>
      <ul class="mt-6 space-y-3 text-slate700">
        <li class="flex gap-3"><span class="accent-text font-bold">•</span> Real-time video/audio consults powered by Agora</li>
        <li class="flex gap-3"><span class="accent-text font-bold">•</span> Private, secure medical records with signed access</li>
        <li class="flex gap-3"><span class="accent-text font-bold">•</span> Multi-device push notifications so nothing is missed</li>
      </ul>
      <!-- IMAGE SLOT: drop real app screenshots into the phone frames on the right when available. -->
    </div>
    <div class="flex justify-center gap-4 reveal">
      <div class="w-[180px] h-[380px] rounded-[2rem] bg-ink p-2.5 shadow-xl floaty">
        <div class="h-full w-full rounded-[1.6rem] bg-white overflow-hidden">
          <div class="hero-grad h-16 flex items-center justify-center text-white text-xs font-semibold">Owner app</div>
          <div class="p-3 space-y-2">
            <div class="h-16 rounded-lg bg-primarySoft"></div>
            <div class="h-3 rounded bg-slate-200 w-3/4"></div>
            <div class="h-3 rounded bg-slate-200 w-1/2"></div>
            <div class="h-20 rounded-lg bg-canvas border border-slate-200"></div>
          </div>
        </div>
      </div>
      <div class="w-[180px] h-[380px] rounded-[2rem] bg-ink p-2.5 shadow-xl floaty mt-8" style="animation-delay:-2s">
        <div class="h-full w-full rounded-[1.6rem] bg-white overflow-hidden">
          <div class="vet-grad h-16 flex items-center justify-center text-white text-xs font-semibold">Vet app</div>
          <div class="p-3 space-y-2">
            <div class="h-16 rounded-lg bg-vetSoft"></div>
            <div class="h-3 rounded bg-slate-200 w-2/3"></div>
            <div class="h-3 rounded bg-slate-200 w-1/2"></div>
            <div class="h-20 rounded-lg bg-canvas border border-slate-200"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ TRUST ============ -->
<section class="py-16 bg-white border-y border-slate-200">
  <div class="max-w-5xl mx-auto px-5 grid sm:grid-cols-3 gap-8 text-center">
    <div class="reveal">
      <div class="h-12 w-12 mx-auto rounded-xl bg-vetSoft flex items-center justify-center mb-3"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1B6CA8" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></div>
      <h3 class="font-semibold">Verified, licensed vets</h3>
      <p class="text-slate700 text-sm mt-1">Every vet is KYC-checked and approved before they can take consultations.</p>
    </div>
    <div class="reveal">
      <div class="h-12 w-12 mx-auto rounded-xl bg-primarySoft flex items-center justify-center mb-3"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <h3 class="font-semibold">Private &amp; secure records</h3>
      <p class="text-slate700 text-sm mt-1">Your pet's data is stored privately with signed, temporary access.</p>
    </div>
    <div class="reveal">
      <div class="h-12 w-12 mx-auto rounded-xl bg-vetSoft flex items-center justify-center mb-3"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1B6CA8" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></div>
      <h3 class="font-semibold">Payment protection</h3>
      <p class="text-slate700 text-sm mt-1">Secure gateway payments, with automatic refunds if a consult can't be completed.</p>
    </div>
  </div>
</section>

<!-- ============ PRICING ============ -->
<section id="pricing" class="py-20 md:py-28">
  <div class="max-w-5xl mx-auto px-5">
    <div class="text-center max-w-2xl mx-auto reveal">
      <h2 class="text-3xl md:text-4xl font-bold">Simple, transparent pricing</h2>
      <p class="text-slate700 mt-3">Pay only for what you need. Final pricing is being confirmed for launch.</p>
    </div>
    <div class="mt-12 grid md:grid-cols-3 gap-6">
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-7">
        <h3 class="font-semibold text-lg">Pay per consult</h3>
        <p class="text-slate500 text-sm mt-1">For occasional care</p>
        <p class="mt-4 text-3xl font-display font-bold accent-text">Per visit</p>
        <p class="text-slate700 text-sm mt-3">One video/audio consultation with a licensed vet, plus prescription and records.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border-2 border-primary p-7 relative">
        <span class="absolute -top-3 left-6 text-xs font-semibold text-white accent-bg rounded-full px-3 py-1">Best value</span>
        <h3 class="font-semibold text-lg">Subscription</h3>
        <p class="text-slate500 text-sm mt-1">For families who consult often</p>
        <p class="mt-4 text-3xl font-display font-bold accent-text">Plans</p>
        <p class="text-slate700 text-sm mt-3">Recurring plans with more consults and added benefits for multi-pet homes.</p>
      </div>
      <div class="reveal card-hover rounded-2xl bg-white border border-slate-200 p-7">
        <h3 class="font-semibold text-lg">Emergency consult</h3>
        <p class="text-slate500 text-sm mt-1">When it can't wait</p>
        <p class="mt-4 text-3xl font-display font-bold" style="color:#E55A0E;">Premium</p>
        <p class="text-slate700 text-sm mt-3">Priority, on-demand access to the first available vet. Held and refunded if none connects.</p>
      </div>
    </div>
    <p class="text-center text-xs text-slate500 mt-6">Vets keep the majority of each consult fee with transparent commission — and a higher share on emergencies.</p>
  </div>
</section>

<!-- ============ WAITLIST / DOWNLOAD ============ -->
<section id="waitlist" class="py-20 md:py-28 bg-white">
  <div class="max-w-3xl mx-auto px-5 text-center reveal">
    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-primaryDark bg-primarySoft rounded-full px-3 py-1">Coming soon</span>
    <h2 class="text-3xl md:text-4xl font-bold mt-4">Be first to know at launch</h2>
    <p class="text-slate700 mt-3">The RESPAW / Pet&nbsp;Help apps are coming soon to the App&nbsp;Store and Google&nbsp;Play. Leave your email and we'll tell you the moment they're live.</p>

    <form id="waitlist-form" class="mt-8 flex flex-col sm:flex-row gap-3 justify-center max-w-md mx-auto">
      <!-- TODO (backend): POST this to a Laravel waitlist endpoint (e.g. /api/v1/waitlist) to persist leads. Currently shows a client-side confirmation only. -->
      <label for="wl-email" class="sr-only">Email address</label>
      <input id="wl-email" type="email" required placeholder="you@example.com"
             class="accent-ring flex-1 rounded-xl border border-slate-200 px-4 py-3 outline-none">
      <button type="submit" class="accent-btn accent-ring rounded-xl px-6 py-3 font-semibold">Notify me</button>
    </form>
    <p id="wl-msg" class="text-sm font-semibold text-vetAccent mt-3 hidden">Thanks! We'll email you at launch. 🐾</p>

    <div class="mt-8 flex flex-wrap justify-center gap-3 text-sm">
      <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-slate500"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 2H7a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V5a3 3 0 0 0-3-3Zm-5 19a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg> App Store — coming soon</span>
      <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-slate500"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3.5 13 12 3 20.5v-17Zm11.5 9.8 2.9 2.5-9.3 5.3 6.4-7.8Zm0-2.6-6.4-7.8 9.3 5.3-2.9 2.5ZM17.8 12l3 1.7c.9.5.9 1.8 0 2.3l-.2.1-2.4-2 2.4-2.1Z"/></svg> Google Play — coming soon</span>
    </div>
    <p class="text-xs text-slate500 mt-3">Vets welcome too — join the waitlist and we'll invite you to onboard early.</p>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section id="faq" class="py-20 md:py-28">
  <div class="max-w-3xl mx-auto px-5">
    <h2 class="text-3xl md:text-4xl font-bold text-center reveal">Frequently asked questions</h2>
    <div class="mt-10 space-y-3">
      <details class="reveal group bg-white rounded-2xl border border-slate-200 p-5">
        <summary class="flex justify-between items-center font-semibold"><span><span class="text-xs font-semibold text-primaryDark bg-primarySoft rounded px-2 py-0.5 mr-2">Owners</span>What can a vet help with online?</span><svg class="faq-chev" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
        <p class="text-slate700 text-sm mt-3">Advice on symptoms, behaviour, diet, minor illnesses, follow-ups and whether an in-person visit is needed. The vet can issue a prescription and save it to your pet's records. For hands-on treatment, you'll be referred to a clinic.</p>
      </details>
      <details class="reveal group bg-white rounded-2xl border border-slate-200 p-5">
        <summary class="flex justify-between items-center font-semibold"><span><span class="text-xs font-semibold text-primaryDark bg-primarySoft rounded px-2 py-0.5 mr-2">Owners</span>How does the emergency option work?</span><svg class="faq-chev" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
        <p class="text-slate700 text-sm mt-3">You always have free, vet-approved first-aid tips. If you need more, "talk to a vet now" connects you to the first available vet by video. If no vet connects, your payment is refunded, and we point you to nearby 24/7 clinics. It is not a substitute for emergency veterinary care.</p>
      </details>
      <details class="reveal group bg-white rounded-2xl border border-slate-200 p-5">
        <summary class="flex justify-between items-center font-semibold"><span><span class="text-xs font-semibold text-primaryDark bg-primarySoft rounded px-2 py-0.5 mr-2">Owners</span>Is my pet's data private?</span><svg class="faq-chev" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
        <p class="text-slate700 text-sm mt-3">Yes. Records and documents are stored privately with signed, temporary access — your data stays yours.</p>
      </details>
      <details class="reveal group bg-white rounded-2xl border border-slate-200 p-5">
        <summary class="flex justify-between items-center font-semibold"><span><span class="text-xs font-semibold text-vet bg-vetSoft rounded px-2 py-0.5 mr-2">Vets</span>How do I get verified and paid?</span><svg class="faq-chev" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
        <p class="text-slate700 text-sm mt-3">Sign up, upload your KYC documents, and get approved by our team. Earnings from each consult land in your in-app wallet, which you can pay out to your bank on the normal payout cycle.</p>
      </details>
      <details class="reveal group bg-white rounded-2xl border border-slate-200 p-5">
        <summary class="flex justify-between items-center font-semibold"><span><span class="text-xs font-semibold text-vet bg-vetSoft rounded px-2 py-0.5 mr-2">Vets</span>Can I set my own hours?</span><svg class="faq-chev" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></summary>
        <p class="text-slate700 text-sm mt-3">Fully. Manage your slots, holidays and working hours, and toggle on-call when you want to receive emergency requests for a higher share.</p>
      </details>
    </div>
  </div>
</section>

<!-- ============ FINAL CTA ============ -->
<section class="py-16">
  <div class="max-w-5xl mx-auto px-5">
    <div class="hero-grad rounded-3xl p-10 md:p-14 text-center text-white reveal relative overflow-hidden">
      <h2 class="text-3xl md:text-4xl font-bold">Care that's always within reach</h2>
      <p class="mt-3 text-white/90 max-w-xl mx-auto">Whether you're caring for a pet or caring for a living as a vet — RESPAW / Pet Help connects you.</p>
      <div class="mt-8 flex flex-wrap gap-3 justify-center">
        <a href="#waitlist" class="bg-white text-primaryDark rounded-xl px-6 py-3 font-semibold hover:bg-canvas transition">Book a consultation</a>
        <a href="#for-vets" class="bg-vetDark text-white rounded-xl px-6 py-3 font-semibold hover:bg-vet transition">Join as a vet</a>
      </div>
    </div>
  </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="bg-ink text-slate-300 py-14">
  <div class="max-w-6xl mx-auto px-5 grid md:grid-cols-4 gap-8">
    <div>
      <div class="flex items-center gap-2 font-display font-bold text-lg text-white mb-3">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg accent-bg text-white">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M7 8.5a2 2 0 1 1-.001-3.999A2 2 0 0 1 7 8.5Zm10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM4.5 13a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm15 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM12 22c-3 0-5-1.8-5-4.2 0-1.9 1.6-3 2.7-4 .9-.8 1.3-1.8 2.3-1.8s1.4 1 2.3 1.8c1.1 1 2.7 2.1 2.7 4C17 20.2 15 22 12 22Z"/></svg>
        </span> RESPAW / Pet Help
      </div>
      <p class="text-sm text-slate-400">Pet telehealth — trusted care for your pet, anytime.</p>
    </div>
    <div>
      <h4 class="text-white font-semibold mb-3 text-sm">For pet owners</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="#how" class="hover:text-white">How it works</a></li>
        <li><a href="#emergency" class="hover:text-white">Emergency care</a></li>
        <li><a href="#features" class="hover:text-white">Features</a></li>
        <li><a href="#waitlist" class="hover:text-white">Book a consultation</a></li>
      </ul>
    </div>
    <div>
      <h4 class="text-white font-semibold mb-3 text-sm">For vets</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="#for-vets" class="hover:text-white">Why join</a></li>
        <li><a href="#for-vets" class="hover:text-white">How onboarding works</a></li>
        <li><a href="#waitlist" class="hover:text-white">Join as a vet</a></li>
      </ul>
    </div>
    <div>
      <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="#faq" class="hover:text-white">FAQ</a></li>
        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
      </ul>
    </div>
  </div>
  <div class="max-w-6xl mx-auto px-5 mt-10 pt-6 border-t border-white/10 text-sm text-slate-400 flex flex-col sm:flex-row justify-between gap-3">
    <p>&copy; <span id="year"></span> RESPAW / Pet Help. All rights reserved.</p>
    <p>Apps coming soon to the App Store &amp; Google Play.</p>
  </div>
</footer>

@verbatim
<script>
(function () {
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Year
  document.getElementById('year').textContent = new Date().getFullYear();

  // ---- Audience toggle ----
  var tabOwner = document.getElementById('tab-owner');
  var tabVet   = document.getElementById('tab-vet');
  var heroOwner = document.getElementById('hero-owner');
  var heroVet   = document.getElementById('hero-vet');

  function setAudience(vet) {
    document.body.classList.toggle('vet-mode', vet);
    tabOwner.classList.toggle('on', !vet);
    tabVet.classList.toggle('on', vet);
    tabOwner.setAttribute('aria-selected', String(!vet));
    tabVet.setAttribute('aria-selected', String(vet));
    heroOwner.classList.toggle('aud-hide', vet);
    heroVet.classList.toggle('aud-hide', !vet);
  }
  tabOwner.addEventListener('click', function(){ setAudience(false); });
  tabVet.addEventListener('click', function(){ setAudience(true); });
  // Cross-links flip the toggle for cohesion
  Array.prototype.forEach.call(document.querySelectorAll('[data-cross="vet"]'), function(el){
    el.addEventListener('click', function(){ setAudience(true); });
  });

  // ---- Owner step visuals ----
  var visuals = [
    '<svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg><p class="mt-4 font-semibold">Search &amp; pick a licensed vet</p>',
    '<svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg><p class="mt-4 font-semibold">Book &amp; pay securely</p>',
    '<svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="1.5"><path d="m23 7-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg><p class="mt-4 font-semibold">Video or audio consult</p>',
    '<svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#E55A0E" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg><p class="mt-4 font-semibold">Prescription saved to records</p>'
  ];
  var visualBox = document.getElementById('owner-visual');
  var steps = Array.prototype.slice.call(document.querySelectorAll('#owner-steps .step'));
  function activateStep(i) {
    steps.forEach(function(s, idx){ s.classList.toggle('active', idx === i); });
    if (visualBox) visualBox.innerHTML = visuals[i];
  }
  steps.forEach(function(s){
    s.addEventListener('mouseenter', function(){ activateStep(parseInt(s.dataset.step,10)); });
    s.addEventListener('click', function(){ activateStep(parseInt(s.dataset.step,10)); });
  });
  activateStep(0);

  // ---- Sticky header style on scroll ----
  var header = document.getElementById('site-header');
  function onScroll(){
    if (window.scrollY > 20) header.classList.add('bg-white/90','backdrop-blur','shadow-sm');
    else header.classList.remove('bg-white/90','backdrop-blur','shadow-sm');
  }
  onScroll(); window.addEventListener('scroll', onScroll, { passive:true });

  // ---- Waitlist form (client-side confirmation; wire to backend later) ----
  var form = document.getElementById('waitlist-form');
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var msg = document.getElementById('wl-msg');
    msg.classList.remove('hidden');
    form.reset();
  });

  // ---- Animations ----
  if (reduce || !window.gsap) {
    // Reveal everything immediately
    Array.prototype.forEach.call(document.querySelectorAll('.reveal'), function(el){ el.classList.add('is-in'); });
    // Set counters to final value
    Array.prototype.forEach.call(document.querySelectorAll('[data-count]'), function(el){
      var t = +el.dataset.count;
      el.textContent = (t % 1 === 0 ? t.toLocaleString() : t.toFixed(1)) + (el.dataset.suffix || '');
    });
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  // Reveal on scroll
  Array.prototype.forEach.call(document.querySelectorAll('.reveal'), function(el){
    ScrollTrigger.create({
      trigger: el, start: 'top 88%',
      onEnter: function(){ el.classList.add('is-in'); }
    });
  });

  // Animated counters
  Array.prototype.forEach.call(document.querySelectorAll('[data-count]'), function(el){
    var target = +el.dataset.count, suffix = el.dataset.suffix || '';
    var obj = { v: 0 };
    ScrollTrigger.create({
      trigger: el, start: 'top 90%', once: true,
      onEnter: function(){
        gsap.to(obj, { v: target, duration: 1.6, ease: 'power2.out',
          onUpdate: function(){
            var val = obj.v;
            el.textContent = (target % 1 === 0 ? Math.round(val).toLocaleString() : val.toFixed(1)) + suffix;
          }
        });
      }
    });
  });
})();
</script>
@endverbatim
</body>
</html>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           