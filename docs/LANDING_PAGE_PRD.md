# Landing Page — PRD & Design Doc

> **RESPAW / Pet Help — marketing website (single dual-audience landing page)**
> Status: **Draft for approval** · Owner: Prince · Date: 2026-06-29
> Scope of this doc: **PRD + design direction only.** The build happens after sign-off.

This document defines the requirements and design direction for **one** public marketing page that serves **both** pet owners and veterinarians, routing each audience to its own call-to-action. It is grounded in the actual product (`docs/OVERVIEW.md`, `docs/DOCUMENTATION.md`, `docs/USER_APP_SCREENS.md`, `docs/VET_APP_SCREENS.md`, `docs/EMERGENCY_FEATURE_SPEC.md`, and the app theme files).

> **Creative intent (this revision):** the page should feel **alive, visual, and modern** — rich imagery and illustration throughout, scroll-triggered motion, micro-interactions, an animated audience toggle and "how it works" stepper — while staying **trustworthy** for a pet-healthcare brand. Vibrant and friendly, never sterile; calm and credible in clinical/payment moments.

---

## 1. Overview, Goal & Target Audiences

### 1.1 What we're building

A single, responsive, **visually rich** marketing landing page (the public face of the platform — separate from the apps) that:

- Explains what RESPAW / Pet Help is to first-time visitors, with imagery and motion doing as much work as copy.
- Presents **two value propositions on one page** — for pet owners and for vets — without splitting into two separate pages.
- Routes each audience to the correct next action via a **dual-CTA strategy**.
- Drives app installs and vet sign-ups.

### 1.2 Primary goal

Convert visitors into one of two actions:

| Audience | Primary CTA | Where it leads |
|---|---|---|
| **Pet owner** | "Book a consultation" / "Get the app" | App Store / Play Store (or waitlist if pre-launch) |
| **Veterinarian** | "Join as a vet" / "Start earning" | Vet application flow / vet waitlist |

### 1.3 Target audiences

**A. Pet owners (pet parents) — the primary audience.**
People with a dog, cat, or other pet who need veterinary advice quickly, want to avoid an unnecessary clinic trip, or want their pet's health records in one place. Pain points: hard to reach a vet after hours, travel stress for the pet, no single record of vaccinations/prescriptions, uncertainty about whether a symptom is urgent.

**B. Veterinarians / clinics — the supply side.**
Licensed vets who want extra income through online consultations, fill idle hours, and reach clients beyond their locality, with payouts and scheduling handled for them. Pain points: under-utilised time, no easy channel for remote consults, payment + record-keeping overhead.

> The marketplace only works if **both sides** grow. The page must sell to owners (demand) and recruit vets (supply) with equal clarity, while keeping owners as the visual default since they are the larger-volume audience.

### 1.4 Dual-CTA strategy (core requirement)

One shared narrative, two clearly signposted paths:

1. **Persistent audience switch in the hero** — an **animated** segmented toggle ("I have a pet" / "I'm a vet") that swaps hero headline, sub-copy, imagery, accent color, and primary CTA in place. Default: **pet owner**.
2. **Two dedicated "how it works" tracks** further down — one for owners, one for vets — each ending in its own CTA. Both always visible on scroll (no toggle required), so a vet who lands still finds their path.
3. **Sticky header CTA** reflecting the selected audience, always exposing a secondary cross-link ("Are you a vet? →" / "Are you a pet owner? →").
4. **Footer** repeats both CTAs explicitly.

### 1.5 Scope guardrails

- **In scope (feature on the page):** telehealth video/audio consultations, pet profiles & medical records, appointment booking, prescriptions/records, wallet & payments (Razorpay), reviews, subscriptions, vet KYC onboarding & earnings, **and the new telehealth Emergency feature** (see §3.x and §3a).
- **OUT of scope — do NOT feature:** the old **SOS dispatch / "vet comes to your home"** home-visit idea. The new Emergency feature deliberately **replaces** it with a telehealth-appropriate model (first-aid tips + "talk to a vet now" + nearby-clinic directory). No "we send a vet to your door" messaging anywhere.
- Out of scope for this doc: the actual build, final copy polish, asset production, blog/CMS pages, full legal-page content (stubs/links only).

---

## 2. Information Architecture / Page Sections

Single long-scroll page, top to bottom:

1. **Sticky header / nav** — logo, anchor links (How it works · Features · Emergency · For vets · Pricing · FAQ), audience-aware primary CTA + secondary cross-link.
2. **Hero** — dual-audience headline with the animated "I have a pet / I'm a vet" toggle, primary CTA, animated supporting visual, trust strip.
3. **Social proof bar** — animated stats/counters + rating/store/press placeholders.
4. **How it works — for pet owners** — interactive 4-step stepper (find a vet → book & pay → video/audio consult → prescription & records). Owner CTA.
5. **Emergency / "talk to a vet anytime"** — the trust differentiator: vet-approved first-aid tips (always available) + instant "talk to a vet now." Telehealth-framed; no home visit. (See §3a.)
6. **How it works / benefits — for vets** — interactive 4-step stepper (apply & verify KYC → set availability → consult & treat → get paid). Vet CTA.
7. **Features grid (shared)** — telehealth consults, pet profiles & records, appointments, secure payments & wallet, prescriptions, reviews, subscriptions.
8. **App showcase** — animated, real-feeling app mockups (phone frames) of the owner app and vet app; looping consultation mockup.
9. **Why trust us / safety & trust** — verified/KYC vets, secure private records, secure payments, licensed professionals.
10. **Pricing / subscription mention** — light-touch: pay-per-consult, owner subscriptions, transparent vet payouts; note emergency consults are a premium tier.
11. **App download CTAs** — App Store + Play Store badges (owner); vet app / vet application (vets).
12. **FAQ** — mixed owner + vet questions in one animated accordion, tagged by audience.
13. **Final CTA band** — both CTAs side by side.
14. **Footer** — both CTAs, nav, legal links, contact, social, app badges, language note.

**Navigation anchors:** `#how-it-works` · `#emergency` · `#features` · `#for-vets` · `#pricing` · `#faq`. Smooth-scroll with sticky-header offset.

---

## 3. Content / Messaging Outline (per section, dual-audience framing)

> Voice & tone (from the repo's Master Brand Brief): warm, friendly, trustworthy — "like a vet who remembers your dog's name." Plain, conversational, sentence-case headlines. First-person friendly ("your pet"). Confident and calm in clinical/payment moments — reassuring, never alarmist, even in the Emergency section.

### Header
- Logo: **RESPAW / Pet Help**. Nav links + audience-aware CTA. Default CTA: "Get the app." Cross-link: "Are you a vet? →".

### Hero
- **Toggle:** `I have a pet` (default) | `I'm a vet`.
- **Owner state:** Headline *"Talk to a trusted vet, without leaving home."* Sub: *"Book a video or audio consultation, get a prescription, and keep your pet's health records in one place."* Primary CTA: **Book a consultation.** Secondary: "How it works."
- **Vet state:** Headline *"Treat more pets. Earn on your schedule."* Sub: *"Join as a verified vet, set your own availability, run online consultations, and get paid — we handle scheduling, records, and payments."* Primary CTA: **Join as a vet.** Secondary: "See vet benefits."

### Social proof bar
- Animated counters: consultations completed, verified vets, average rating, languages supported. Real numbers once available; otherwise qualitative (see open questions).

### How it works — for pet owners
- Steps: **1. Find a vet** → **2. Book & pay** securely → **3. Consult by video or audio** → **4. Get your prescription & records.** Support line: *"Multiple pets, one profile. Vaccinations, prescriptions, and consult history always with you."* CTA: **Book a consultation.**

### Emergency / "talk to a vet anytime" (new — see §3a for full treatment)
- Headline: *"A vet in your pocket — even when it can't wait."*
- Sub: *"Free vet-approved first-aid tips for common pet emergencies, always available — and when you need more, talk to a vet now by video."*
- Three calm layers: **Know what to do** (first-aid tips) · **Talk to a vet now** (instant live consult) · **Find a 24/7 clinic** (nearby directory). Persistent reassuring disclaimer: *"Not a substitute for emergency veterinary care."*
- CTA: **Get the app** (owners). Vet cross-note: *"Vets — go on-call and earn more on emergency consults. →"**

### How it works / benefits — for vets
- Steps: **1. Apply & get verified** (quick KYC) → **2. Set your availability** → **3. Consult & treat** → **4. Get paid.** Benefits: extra income from idle hours; reach beyond your locality; scheduling/records/payments handled; build reputation with reviews; **earn a premium share on emergency consults.** CTA: **Join as a vet.**

### Features grid (shared)
- **Online consultations** — video, audio, or chat with a licensed vet.
- **Pet profiles & records** — breed, age, allergies, vaccinations, lab reports, history.
- **Appointments** — book, reschedule, cancel; online or in-clinic.
- **Prescriptions & notes** — issued digitally, stored on the pet's record.
- **Secure payments & wallet** — pay safely; vets get transparent payouts.
- **Reviews** — real ratings from real pet parents.
- **Subscriptions** — plans for families who consult often.

### App showcase
- Owner app and vet app shown in phone frames; a looping mock of a consultation in progress.

### Why trust us
- Verified, licensed, KYC-checked vets; private secure records (your data); secure payments + automatic protection if a consult can't be completed.

### Pricing / subscription
- Owners: pay-per-consult; subscription plans; **emergency consults are a premium tier.** Vets: keep the majority of each fee, transparent commission, payouts to wallet; **higher share on emergencies.** Keep light — "see plans."

### App download CTAs
- Owner: App Store + Play Store. Vet: "Apply to join" + vet app.

### FAQ (mixed, tagged)
- *Owners:* What can a vet help with online? How does the emergency option work? Is my pet's data private? How do payments/refunds work? In-clinic visits? Languages?
- *Vets:* How do I get verified? How/when do I get paid? Can I set my own hours? How do emergency consults & on-call work? What does it cost to join?

### Final CTA band
- Two buttons: **Book a consultation** · **Join as a vet.**

### Footer
- Both CTAs, nav anchors, Privacy & Terms, contact, social, app badges.

---

## 3a. Emergency Section — Detailed Treatment (trust differentiator)

This is the page's strongest trust/differentiation moment. Frame it as **calm preparedness**, not panic. It mirrors the three telehealth layers from `EMERGENCY_FEATURE_SPEC.md` — **but the marketing page only promises what the platform reliably delivers, and never implies a physical home visit.**

**Layout idea:** a horizontal 3-card "ladder," each card a layer, connected by a subtle animated line that draws in on scroll.

1. **Know what to do (free, always available).** Vet-approved first-aid guidance for common emergencies (poisoning, choking, seizure, heatstroke, bleeding, breathing trouble, and more). "Do this now / Don't do this / Get to a vet immediately if…" Works even offline in the app.
2. **Talk to a vet now.** One tap connects you to the first available vet by live video/audio. *"Even a two-minute answer can be the difference."* (Premium consult; payment held and auto-refunded if no vet connects — mention reassurance, not mechanics.)
3. **Find a 24/7 clinic nearby.** When hands-on care is needed, we point you to nearby emergency clinics — directly or when your vet refers you in person.

**Dual framing:** below the cards, a vet-side line: *"Vets: switch on-call to receive emergency requests and earn a higher share."* → vet CTA.

**Required disclaimer (persistent, visible):** *"First-aid tips are informational only and not a substitute for emergency veterinary care."*

**Hard rule:** no "SOS," no "we send a vet to your home," no ambulance/dispatch imagery. The promise is **information + a live vet + a clinic pointer**, all telehealth-consistent.

---

## 4. Visual / Brand Direction (creative, concrete)

### 4.1 Existing brand assets in the repo

Two palettes exist in code: the **Master Brand Brief** + user app use **warm orange `#FF6B1F`** (user app `#ff6b2c`); the **vet app** uses **clinical blue `#1B6CA8`** + teal. **Decision (settled):** orange is the **master/creative identity**; the **vet sections use blue/teal as a deliberate "for professionals" accent.** This turns the existing inconsistency into an intentional dual-audience signal.

### 4.2 Full color palette

**Brand / owner (master):**
- `--primary` `#FF6B1F` — owner CTAs, key accents, hero (backgrounds & 24px+ display).
- `--primary-dark` `#E55A0E` — hover/pressed; **orange text ≥14px on white must use this** (contrast).
- `--primary-soft` `#FFF1E8` — tints, chips, section backgrounds.
- `--primary-glow` `rgba(255,107,31,0.35)` — glow/shadow behind hero art & CTAs.

**Vet accent (professional):**
- `--vet` `#1B6CA8` · `--vet-dark` `#0F4A75` · `--vet-light` `#3B8ECA`.
- `--vet-accent` (teal) `#00C9A7` · `--vet-accent-light` `#4DDEC7`.
- `--vet-soft` `#EAF3FA` — vet-section background tint.

**Neutrals:**
- `--ink` `#1A1A1A` · `--slate-700` `#374151` · `--slate-500` `#6B7280` · `--slate-200` `#E5E7EB`.
- `--canvas` `#F8F8F6` (page bg) · `--white` `#FFFFFF` (cards).

**Functional:**
- `--success` `#10B981` · `--gold` `#F59E0B` (ratings) · `--info` `#0EA5E9`.
- `--alert` `#EF4444` — reserved for form errors only. The Emergency section uses **warm/coral tones, not alarm-red**, to stay calm (e.g. a soft coral `#FF8A5C` derived from primary). True red is never a section theme.

### 4.3 Gradients & illustration style

- **Hero gradient (owner):** warm, soft — `linear-gradient(135deg, #FF6B1F 0%, #FF8A4C 50%, #FFB07A 100%)`, used behind art and in large display accents, never under body text.
- **Vet gradient:** `linear-gradient(135deg, #1B6CA8 0%, #2E86C1 60%, #00C9A7 100%)` for the "For vets" band and vet toggle state.
- **Mesh/blob backgrounds:** soft, blurred organic blobs in `--primary-soft` / `--vet-soft` drifting slowly behind sections for depth (very low opacity, GPU-friendly).
- **Illustration style:** friendly, rounded, semi-flat with gentle gradients and soft shadows — happy pets, owners, and vets. Consistent 2px line-icon set (Lucide/Phosphor) matching the apps. Rounded cards (radius 16–24px), generous whitespace, soft tints, minimal hard shadows.
- **Don'ts (from brief):** no glassmorphism, no purple gradients, no wall-to-wall corporate blue (blue is the vet accent only).

### 4.4 Typography
- Display/hero: **Fraunces** (or Recoleta) — characterful, warm. UI/body: **Inter** (or SF Pro). Sentence-case headings. Web scale: hero ~48–64px desktop, h1 32–40, h2 28, body 16–18, line-height 1.2 headings / 1.5 body.

---

## 4b. Imagery Plan (what goes where + sourcing)

> Imagery is a first-class element, not decoration. Every major section gets a purposeful visual.

**Placement map:**
- **Hero (owner):** warm photo/illustration of a happy owner + pet with a phone showing a vet on a video call; soft gradient + blob backdrop; small floating UI chips ("Prescription sent ✓", "₹ paid securely").
- **Hero (vet state):** a friendly vet on a video consult / reviewing records on a tablet; blue-accented backdrop.
- **Social proof bar:** small pet avatars / partner or store badges.
- **How it works (owners):** one illustration per step (search, pay, video consult, prescription/record) — consistent character set.
- **Emergency section:** calm, reassuring illustration trio — a person reading first-aid tips, a live vet on call, a map/clinic pin. Warm coral palette, no alarm imagery.
- **How it works (vets):** illustrations per step (KYC/verify badge, availability calendar, consult, wallet/payout).
- **Features grid:** one line-icon per card (no heavy photos).
- **App showcase:** real-feeling, high-fidelity **app mockups in phone frames** (owner + vet), plus a short looping consultation mock. Build these from the actual app screens (`USER_APP_SCREENS.md` / `VET_APP_SCREENS.md`) so they're accurate, not generic.
- **Trust section:** verified-vet portrait + security/record iconography.
- **Footer:** subtle pet silhouette / paw motif.

**Sourcing approach (recommended, with licensing):**
1. **Custom illustration set (preferred for brand cohesion)** — commission or build a small consistent set in the rounded semi-flat style. Best brand fit; full rights. Highest effort/cost.
2. **AI-generated illustration/imagery (fast, on-brand, low cost)** — generate hero art, per-section illustrations, and emergency visuals to the style spec. *Licensing note:* use a tool whose terms grant commercial use and avoid styles imitating identifiable living artists; keep generation prompts/records. Good middle ground; review for anatomy/quality (pets especially).
3. **Stock photography (for real pet/vet photos where authenticity matters)** — use licensed libraries (e.g. paid stock); confirm commercial license + model/property releases; avoid overused images. Use sparingly, mainly hero and trust.
4. **App mockups** — generate from the real app UI (screenshots or rebuilt frames), not generic stock phones, so the product looks real.

**Recommendation:** **AI-generated illustrations for the consistent illustration set + section art**, **licensed stock or a short custom shoot for one or two authentic hero/trust photos**, and **real app-screen mockups** for the showcase. Document the license for every asset used.

**Performance:** all imagery in modern formats (WebP/AVIF), responsive `srcset`, **lazy-load** everything below the fold, explicit width/height to avoid layout shift, compress aggressively.

---

## 5. Animation & Interactivity (per section)

> Principle: motion guides attention and adds delight without hurting performance or accessibility. **All non-essential motion must respect `prefers-reduced-motion`** (reduce to instant/opacity-only). Animations are transform/opacity-based (GPU-friendly), short (150–500ms), and eased naturally. Nothing blocks content or the toggle from working.

**Global / cross-section:**
- **Scroll-triggered reveals** — sections and cards fade/slide up as they enter the viewport (staggered for grids).
- **Sticky header** — shrinks/gains a subtle shadow on scroll; CTA stays reachable.
- **Micro-interactions** — buttons scale 0.98 on press + color shift; cards lift slightly on hover; links underline-grow; focus rings always visible.
- **Background blobs** — slow, looping drift (very low intensity).

**Hero:**
- **Animated entrance** — headline words stagger in; hero art floats gently (subtle parallax on mouse/scroll); floating UI chips drift.
- **Audience toggle** — animated thumb slides between "I have a pet" / "I'm a vet"; on switch, headline/sub/CTA **cross-fade + slide**, accent color **transitions orange↔blue**, and hero art swaps with a soft fade. Smooth, ~300–400ms.

**Social proof:** **counters animate** from 0 to value when scrolled into view (once).

**How it works (owners & vets):** **interactive stepper** — steps reveal in sequence on scroll; hovering/clicking a step highlights it and updates the paired illustration; optional auto-advance with a progress indicator. Connecting line draws in.

**Emergency:** the 3-layer "ladder" — connecting line draws on scroll; cards reveal in sequence; the "Talk to a vet now" card has a gentle, calm pulse (slow, not alarming) to signal availability.

**App showcase:** phone mockups slide/parallax in; the **consultation mock loops** (e.g. call connecting → talking → "prescription sent"); subtle screen glow.

**Features grid:** staggered reveal; icon micro-animation on hover.

**FAQ:** smooth accordion expand/collapse (height + fade); chevron rotates.

**Final CTA band:** gentle gradient shift / shimmer on the buttons to draw the eye.

**Performance & a11y guardrails:** lazy-init animation observers (IntersectionObserver); avoid animating layout properties; cap simultaneous animations; test on mid-range mobile; **full `prefers-reduced-motion` fallback** (content appears instantly, loops disabled); never gate content behind animation; keep total JS for motion lightweight.

---

## 6. Functional Requirements

- **Responsive** — mobile-first; the hero toggle, steppers, emergency ladder, and dual CTAs must work well stacked on small screens.
- **App store links** — App Store + Google Play badges (real URLs once available; "coming soon" state otherwise — see open questions).
- **Lead capture / waitlist** — **recommended** if pre-launch: simple form with two intents — owner waitlist (email) and vet interest (name, email, city, license # optional). Decision needed on lead destination (open questions). Demote to "notify me" if apps are already live.
- **Analytics** — page-load, scroll depth, **toggle interactions**, stepper engagement, and **per-audience CTA click tracking** (owner vs vet). Event taxonomy e.g. `cta_click {audience, location, label}`, `toggle_switch {to}`. Tool TBD (GA4 / Plausible / PostHog).
- **SEO basics** — semantic HTML, single `<h1>`, title/meta targeting both "online vet consultation / pet telehealth / emergency vet advice" and "earn as a vet online," Open Graph + Twitter cards, favicon, sitemap.xml, robots.txt, fast LCP, alt text on all imagery, structured data (Organization + FAQPage).
- **Performance & a11y** — Lighthouse ≥90 across the board; WCAG 2.1 AA contrast (orange body text uses `#E55A0E`); keyboard-navigable toggle/stepper/accordion; `prefers-reduced-motion` honored; lazy-loaded media; no CLS.
- **Privacy/consent** — cookie/analytics consent banner if analytics cookies are used; Privacy & Terms links.
- **Forms** — client + server validation; spam protection (honeypot/captcha) on the lead form.

---

## 7. Tech Approach Options (re-weighed for heavy interactivity)

The added imagery and animation raise the interactivity bar, so the trade-off is re-evaluated.

### Option A — Static HTML + Tailwind + lightweight JS motion libs
Vanilla/Alpine.js for the toggle/accordion + a motion library: **GSAP** (with ScrollTrigger) or **AOS** for scroll reveals; **Lenis** optional for smooth scroll.
- **Pros:** still the fastest to ship and cheapest to host; best Core Web Vitals/SEO (no hydration); GSAP handles everything described here (staggered reveals, counters, toggle cross-fades, stepper, looping mock) without a framework; one file, easy to serve from Laravel `public/`. GSAP core is free and small.
- **Cons:** orchestrating many coordinated animations in vanilla JS is more manual than in a component framework; state for the toggle/stepper is hand-rolled; can get unwieldy if interactivity keeps growing.

### Option B — React / Next.js + Framer Motion
- **Pros:** component state makes the toggle/stepper/loops clean and maintainable; **Framer Motion** is ergonomic for exactly this kind of motion and `prefers-reduced-motion`; Next.js gives SSG + image optimization (great for the heavy imagery) and easy growth into more pages.
- **Cons:** heavier toolchain/hosting; bundle/hydration cost to manage for performance; overkill if it stays one page; more maintenance for a solo dev.

### Recommendation
**Option A — static HTML + Tailwind + GSAP (ScrollTrigger)**, with Alpine.js for the toggle/accordion state. Reasons: even with this much motion, GSAP covers every interaction described while keeping a single, fast, SEO-strong page that ships quickly and serves from the existing Laravel `public/`; the brand tokens map directly to a Tailwind config; the lead form posts to one small Laravel endpoint we already have the backend for. **Switch to Option B (Next.js + Framer Motion) if** the motion/state grows substantially or marketing expands into multiple pages (blog, careers, localized variants) — content, tokens, and asset plan carry over. Either way: lazy-load imagery, ship WebP/AVIF, and gate non-essential motion behind `prefers-reduced-motion`.

---

## 8. Success Metrics

- **Primary conversions:** owner — installs / "Book a consultation" clicks (waitlist pre-launch); vet — "Join as a vet" submissions / application starts.
- **Conversion rate by audience** — owner vs vet CTA CTR tracked separately.
- **Engagement with the new interactions** — toggle-interaction rate, stepper engagement, emergency-section reach/CTA (a proxy for the differentiator landing).
- **Scroll depth** — % reaching Emergency, "For vets," and FAQ.
- **Bounce rate / time on page.**
- **Technical:** Lighthouse ≥90 (Perf/SEO/A11y/Best-practices); LCP < 2.5s mobile despite rich media.
- **SEO:** indexed for target queries on both sides within ~8 weeks.

Suggested first-90-day targets (confirm with user): page→CTA CTR ≥ 8% owners / ≥ 4% vets; ≥ 60% scroll past "For vets"; ≥ 30% interact with the toggle.

---

## 9. Open Questions for the User

1. **Launch status:** apps live in stores now, or pre-launch? Decides store badges vs. waitlist and whether lead capture is primary.
2. **Lead capture destination:** new Laravel endpoint + DB table, email inbox, Google Sheet, or CRM?
3. **Real numbers for social proof / counters:** can we cite real stats, or use qualitative claims / omit at launch?
4. **Pricing transparency:** show exact consult/subscription/**emergency** fees, or "starting from" / "see plans"? Publish the vet commission/payout split?
5. **Emergency messaging comfort:** OK with the calm, telehealth-only framing (tips + live vet + clinic pointer) and the persistent "not a substitute for emergency vet care" disclaimer? Any legal review needed before publishing first-aid claims?
6. **Imagery sourcing:** approve **AI-generated illustration set + a little licensed stock/custom photo + real app mockups**? Budget for a custom illustration set or shoot?
7. **Brand reconciliation:** confirm **orange master + blue/teal vet accent** (already settled here) — anything to adjust?
8. **Motion intensity:** how lively do you want it — "subtle and tasteful" or "bold and playful"? (Affects animation tuning.)
9. **Domain & hosting:** which domain, and served from Laravel `public/` or a separate static host?
10. **Geography & language:** single market (India, given Razorpay) or multi-region? Languages beyond English at launch?
11. **Logo & assets:** finalized logo / brand mark / photography available, or placeholders for the build?
12. **Name on the page:** "RESPAW," "Pet Help," or "RESPAW / Pet Help"?
13. **Analytics tool & consent:** preferred analytics and whether a cookie consent banner is required for the target market.

---

*Next step: on approval of this PRD (and answers to the open questions — especially #1–#6), proceed to build the landing page per the recommended tech approach.*
