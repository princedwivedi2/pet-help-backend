# RESPAW User App — Design Prompts

Copy-paste-ready design prompts for every pet-parent screen. Each prompt is brand-locked, layout-prescriptive, and tool-agnostic — feed them into **v0.dev, Lovable, Bolt, Galileo AI, Uizard, Midjourney, Figma AI, or hand them to a human designer**.

For the underlying screen specs (fields, API, states) see [USER_APP_SCREENS.md](USER_APP_SCREENS.md).

---

## How to use this doc

1. **Always start with the [Master Brand Brief](#master-brand-brief)** — paste it as the first message / system prompt.
2. **Then paste the screen-specific prompt** below it.
3. For Midjourney-style image tools, use the "**MJ shorthand**" line at the bottom of each section.
4. For code-gen tools (v0, Lovable, Bolt), use the full prompt block.

---

## Master Brand Brief

> Use this as a reusable preamble for every screen prompt.

```
You are designing the RESPAW mobile app — a pet-care platform that lets pet parents
find trusted vets nearby, book appointments, run instant video/audio/chat consults,
and keep their pet's health records in one warm, friendly place.

BRAND PERSONALITY
- Warm, friendly, trustworthy — like a vet who remembers your dog's name.
- Confident in clinical moments (SOS, prescriptions) but never cold or sterile.
- Playful in onboarding and content; serious during care + payment.
- Inspirations: Headspace's softness, Cred's craft, Spotify's confidence,
  Apple Health's clarity, Airbnb's warmth.

LOGO
- Wordmark "respaw" in a custom rounded organic display typeface, all lowercase.
- A medical cross integrated into the "r" + a paw print integrated into the "p".
- Hero color is a vibrant warm orange.

COLOR TOKENS (use these exactly)
- primary       #FF6B1F   (signature orange — buttons, CTAs, key accents)
- primary-dark  #E55A0E   (pressed states, hover)
- primary-soft  #FFF1E8   (background tints, banners, chips)
- ink           #1A1A1A   (primary text)
- slate-700     #374151   (secondary text)
- slate-500     #6B7280   (tertiary text, captions)
- slate-200     #E5E7EB   (dividers, hairlines)
- cloud         #F8F8F6   (canvas / app background)
- white         #FFFFFF   (cards)
- success       #10B981   (paid, verified, confirmed)
- alert         #EF4444   (SOS, errors, urgent)
- info          #0EA5E9   (informational tags)
- gold          #F59E0B   (ratings, premium)

TYPOGRAPHY
- Display / brand:    "Fraunces" (or "Recoleta") — used sparingly for hero numbers
- UI primary:         "Inter" or "SF Pro" — body, buttons, labels (400/500/600/700)
- Sizes (mobile):     12 caption · 14 body · 16 lead · 20 h3 · 24 h2 · 32 h1 · 40 hero
- Line height:        1.4 body, 1.2 headings
- Letter spacing:     -0.01em on headings ≥ 24px

SHAPE & SURFACES
- Card radius:        20px (large cards), 16px (medium), 12px (chips, small)
- Button radius:      14px (primary), 999px (pill chips, FAB)
- Shadow:             0 8px 24px rgba(255,107,31,0.12) for primary/orange surfaces
                      0 4px 16px rgba(15,23,42,0.06) for neutral cards
- Border:             1px solid #E5E7EB on neutral cards (no shadow option)

ICONOGRAPHY
- Line icons, 2px stroke, 24×24 px default — Lucide / Phosphor library style.
- Filled variants ONLY for active tab bar items, paw stamps, and status pills.
- Custom illustrations are flat, hand-drawn, slightly imperfect — never 3D-render
  glossy stock pet vectors.

MOTION
- Easing: cubic-bezier(0.4, 0, 0.2, 1) for entrances, (0.4, 0, 1, 1) for exits.
- Durations: 200ms micro, 300ms standard, 450ms hero transitions.
- Use spring physics for the orange "Consult Now" pulse and pet-card scroll.
- Skeletons shimmer at 1.4s loop, never spinners on lists.

LAYOUT GRID
- Mobile-first 390×844 (iPhone 14/15). Safe area top 47, bottom 34.
- 16px outer page padding. 12–16px between blocks. 24px between sections.
- Bottom nav 64px tall, floating with a soft white card on top of cloud bg.
- Sticky CTAs at bottom (height 56px) sit above bottom nav with 16px gap.

VOICE & TONE (microcopy)
- Plain, warm, conversational. "Find a vet near you" > "Search Veterinarians".
- Never alarmist except real emergencies. SOS uses urgent red; everything else stays warm.
- First-person friendly: "Your pets" > "My Pets". Headlines in sentence case.
- Numbers are confident: "1,243 verified vets" not "many".

ACCESSIBILITY
- Minimum touch target 44×44px. Body text ≥ 14px. Contrast ≥ 4.5:1 on text.
- Orange on white ALWAYS uses the dark orange (#E55A0E) for ≥ 14px text;
  vibrant orange #FF6B1F is reserved for CTA backgrounds and 24px+ display.
- Focus rings: 2px outline #FF6B1F + 2px halo offset.

DON'T
- No glassmorphism. No purple gradients. No corporate-blue.
- No stock photos of vets in white coats with stethoscopes.
- No emoji-stuffed empty states. One illustration per state, max.
- No drop-shadow on text. No 3D buttons. No skeuomorphic textures.
```

---

## Reusable component prompts

### Buttons

```
PRIMARY BUTTON — full-width, height 56px, radius 14px, background #FF6B1F,
label #FFFFFF, weight 600, size 16px, letter-spacing 0. Pressed state: scale 0.98
+ background #E55A0E. Loading: 16px white spinner replaces label.

SECONDARY BUTTON — same dimensions, background #FFF1E8, label #E55A0E,
weight 600. No shadow.

GHOST BUTTON — transparent bg, 1px border #E5E7EB, label #1A1A1A, weight 500.

PILL CHIP — height 36px, radius 999px, padding 0 16px, body 14px.
Inactive: bg #F8F8F6, border #E5E7EB, text #374151.
Active: bg #FF6B1F, border #FF6B1F, text #FFFFFF.
```

### Cards

```
PET CARD — 152×184px, white background, radius 20px, soft shadow.
Top: 96×96 circular pet photo centered. Below: pet name 16/600,
species + breed 12/400 #6B7280. Background tint #FFF1E8 on hover.

VET CARD — full-width, height 132px, white background, radius 20px.
Left: 96×96 photo, radius 16. Right: vet name 16/600, clinic 14/500 #374151,
1-line specialization 12/400 #6B7280, rating row (gold star + value + count),
chips row (Open now, Emergency, 24/7). Right edge: distance "1.2 km" 12/600,
right chevron.

APPOINTMENT CARD — radius 16, padding 16, with a vertical 4px orange stripe
on the left edge for upcoming, gray for past. Pet photo 48×48 round +
vet name + date + status pill.
```

### Status pills

```
- requested: bg #FFF1E8, text #E55A0E
- accepted:  bg #ECFDF5, text #10B981
- in_progress: bg #DBEAFE, text #1D4ED8 (animated pulse)
- completed: bg #F0FDF4, text #16A34A, with check icon
- cancelled: bg #FEF2F2, text #B91C1C
- pending payment: bg #FEF3C7, text #B45309
```

### Bottom nav

```
Floating bar 64px tall, 16px below safe-area, mx-4, white background, radius 24,
shadow 0 8px 32px rgba(0,0,0,0.08). Five items evenly spaced:
Home (paw-house), Search (compass), Pets (paw), Bookings (calendar), Profile (avatar).
Active: filled icon + 4px orange dot under it. Inactive: line icon #6B7280.
Center "Consult Now" FAB OPTION: extra 56px circular orange button overlapping
the bar with a soft pulsing aura when vets are online.
```

---

# Per-screen prompts

Each section below contains:
1. **Goal** — what this screen needs to do.
2. **Hero focal point** — the one thing the eye should land on first.
3. **Layout prompt** — copy-paste into v0/Lovable/Bolt.
4. **MJ shorthand** — single-line image-gen prompt.

---

## 1. Splash

**Goal:** ~1.2-second branded boot moment that earns trust before app opens.

**Hero focal point:** the respaw wordmark animating in.

**Layout prompt**

```
Design a Splash screen for the RESPAW pet-care app, mobile 390×844.

Full-bleed background: a soft, slightly-textured warm orange #FF6B1F that subtly
breathes (0.96→1.04 scale, 4-second loop).

Centered: the respaw wordmark in cream #FFF1E8, 240px wide, with a tiny
animation sequence —
  (0–600ms) wordmark fades up from y+12 with elastic ease
  (300–700ms) the medical cross in the "r" pulses once
  (500–900ms) the paw print in the "p" presses down once like a tiny stamp

Below the wordmark, 24px gap, a single line tagline in cream 14/500:
"Find trusted care for your pet"

Bottom safe-area: tiny version label "v1.0.0" in 11/400 cream at 40% opacity,
centered above the home indicator.

No skip button, no spinner. The splash dismisses itself when auth check resolves.

Optional: a barely-visible, 8% opacity scattered paw-print pattern across the
background, used as a recurring brand watermark in other screens too.
```

**MJ shorthand:** `mobile splash screen, vibrant warm orange background, cream wordmark "respaw" with paw + medical cross, centered, soft orange paw watermark pattern at 8% opacity, modern flat design, 9:16, no UI chrome --ar 9:16 --style raw`

---

## 2. Login / Signup

**Goal:** Frictionless first impression. Get the user in within 30 seconds.

**Hero focal point:** the segmented Login/Signup toggle and the email field.

**Layout prompt**

```
Design a Login/Signup screen for RESPAW, mobile 390×844, modern friendly fintech-quality.

Header zone (top 280px): full-width orange #FF6B1F background, organic curved
bottom edge (concave dip in the center, like a smile), with the respaw wordmark
in cream centered at y=120. Below the wordmark, 16px tagline cream:
"Care for your pet, anytime."

Card zone (mid): a single white card, radius 28 top corners, full width pinned
to the bottom of the curve, padding 24, content starts 32px below the card top.

Card content top→bottom:
1. Segmented control "Sign in | Sign up" — pill, height 44, bg #F8F8F6,
   selected segment bg #FFFFFF + soft shadow + ink text 600.
2. Form (Login version):
     - Input "Email or phone" with envelope icon left, 56px height, radius 14,
       1px border #E5E7EB, focus border 2px #FF6B1F.
     - Input "Password" with lock icon left, eye toggle right.
     - Right-aligned "Forgot password?" link 13/500 #E55A0E.
     - Primary button "Sign in" full-width.
     - Divider with "or continue with" centered between two slate-200 lines.
     - Two ghost buttons side-by-side: "Use OTP" (with phone icon) and
       "Continue with Google" (icon left). Equal width.
     - Bottom helper "New to RESPAW? Create an account." with the second
       half as link.

3. Form (Signup version): Full name, Email, Phone (with country code dropdown),
   Password (with strength meter pill: weak/okay/strong in 3 colored segments),
   Checkbox "I agree to T&C and Privacy" — primary button "Create account".

OTP step (drawer slides up from bottom, 60% height):
   - Title "Enter the 6-digit code"
   - Subtitle "We sent it to {masked email/phone}" 14/400 #6B7280
   - 6 separate digit boxes, 48×56 each, radius 12, auto-advance, paste-friendly
   - Resend countdown "Resend in 0:42" → becomes blue link at 0
   - Primary button "Verify"

States:
- Validation errors render UNDER the input, 12/500 #B91C1C with a tiny ⚠ icon.
- Submit button disabled when form invalid: bg #F8F8F6, text #9CA3AF.
- Loading: button shows spinner, all inputs disabled at 60% opacity.

Microcopy: warm, never robotic. "Welcome back" not "Login".
```

**MJ shorthand:** `mobile login screen, warm orange curved header with cream wordmark "respaw", floating white card with segmented Sign in / Sign up toggle, email + password inputs with rounded corners, primary orange button, soft shadows, modern fintech aesthetic, 9:16 --style raw`

---

## 3. Home

**Goal:** Surface the 3 things a returning user needs in 1 second — instant consult, nearby vets, my pets.

**Hero focal point:** the orange "Consult Now in 60 seconds" hero card.

**Layout prompt**

```
Design the Home screen for RESPAW, mobile 390×844.

Background: cloud #F8F8F6.

Sticky top bar (88px including safe area):
- Left: friendly greeting in two lines —
    "Hi, Aanya 👋" (16/600 ink)
    "How's Bruno today?" (13/400 #6B7280, where "Bruno" is the user's primary pet)
- Right: notification bell with a tiny red dot for unread count, then
  circular avatar 40×40.

Below header — search bar:
- Pill, height 52, full width minus 32 padding, white card, radius 999,
  shadow soft. Left: search icon. Placeholder text "Search vets, clinics,
  services". Right: a small red square button with a white SOS heart icon
  (44×44, radius 12) — tap opens SOS modal.

HERO CARD — orange #FF6B1F, full width minus 32, radius 24, padding 24,
height ~150, shadow primary-soft. Layout:
- Left 60% column:
    Eyebrow: "INSTANT CARE" cream 11/700 letter-spaced 0.1em
    Title: "Consult a vet now" cream 22/700 (Fraunces display)
    Subtitle: "Pay only when connected. Auto-refund if not." cream 13/400
    CTA: white pill button "Consult Now" with right arrow, height 40,
         text orange 14/600
- Right 40%: a flat illustration of a small dog on a video call (paw waving),
  cream + dark-orange line work, sized 120×120 floating off the right edge.

Quick actions row — horizontal scroll, 4 cards, each 88×104:
1. "Book Visit"      — calendar icon, bg white, ink text
2. "Find Vets"       — pin icon
3. "My Pets"         — paw icon
4. "Subscriptions"   — sparkle icon
Each card: white bg, radius 16, icon 28 in orange-soft circular bg, label below
13/600 ink. Tap: scale 0.98 + tiny haptic.

Section "Vets near you" — 16/600 ink, 12px gap below.
- Right link "See all" 13/600 #E55A0E
- Horizontal scroll of vet cards (compact 200×220):
    photo top (full width, 120 tall, radius-top 16),
    name 14/700, clinic 12/400 #6B7280,
    distance + rating row,
    "Open now" green chip if applicable.

Section "Your pets" 16/600 — horizontal scroll of pet cards (152×184),
ending with a dashed-border "+" tile to add a new pet.

Section "Tips & Articles" — 3 vertical blog cards stacked:
    cover image left 96×96 radius 12, on the right title 14/600 (2 lines max),
    category chip + reading time below.

Floating bottom nav (described in master brief).

States:
- First-time user with no pets: hero card replaces with "Add your first pet"
  illustration card.
- No location permission: vets section shows "Turn on location" prompt with
  pin illustration.
- Loading: skeleton shapes match the structure exactly, shimmering 1.4s.
```

**MJ shorthand:** `mobile pet care app home screen, soft cream background, large vibrant orange hero card "Consult Now" with friendly dog illustration on video call, horizontal scroll vet cards with rounded photos, my pets cards with circular pet photos, floating bottom nav, modern warm friendly design, 9:16 --style raw`

---

## 4. Search Vets

**Goal:** Powerful filtering without overwhelming the user. List ↔ Map seamless.

**Hero focal point:** the search input + filter chip strip.

**Layout prompt**

```
Design a Search Vets screen for RESPAW, mobile 390×844.

Top bar 56px: back arrow left, search input full-rest pill (white, radius 999,
height 44), small filter button right (44×44, radius 14, white, with a tiny
orange dot if any filter is active).

Filter chip strip (sticky below search, 56px tall, horizontal scroll):
quick chips users can toggle in 1 tap:
  "Available now" (clock dot icon)
  "Emergency" (red pulse dot)
  "Online consult" (video icon)
  "Home visit" (door icon)
  "Top rated" (star)
  "+ Filters" (link to full sheet)

View toggle (right-aligned below chip strip, before results):
  segmented "List | Map" pill, 88px wide, 32px tall.

LIST VIEW:
- Result count: "248 vets within 10 km" 13/500 #6B7280, left-aligned, small.
- Results sorted by selected sort (Distance default). Cards as defined in
  master "VET CARD" spec, with separators 12px gap.
- Each card: tap → vet detail. Long-press → save to favorites (heart pulse).
- Sticky "End of list" or "Show more" pill button when results > 20.

MAP VIEW (full-bleed):
- Mapbox-style soft map, low-saturation, with orange paw-shaped pins.
  Cluster pins show count when zoomed out.
- Bottom carousel: horizontal scroll of compact vet cards (300×128) showing
  photo + name + distance + Book button. Tapping a pin focuses its card and
  vice-versa.
- Top-right tiny round buttons: locate-me, layers, recenter.

FILTER SHEET (slides up bottom drawer, 80% height, white, radius-top 28):
- Header "Filter vets" 18/700, close X right.
- Sections (collapsible, divider between):
    Distance — slider 1–100 km with current value bubble that slides above.
    Availability — toggle row "Open right now"
    Service type — multi-select chips: Clinic visit, Home visit, Online consult.
    Specialization — chip cloud (max 6 visible, "Show more" expands).
    Languages — chip cloud (en, hi, ta, te, kn, mr, …) with country-flag prefix.
    Min rating — star picker, 5 stars left-to-right; tap n stars sets ≥ n.
    Sort by — radio group: Distance, Top rated, Most reviewed.
- Sticky footer: "Reset" ghost button left, "Show 248 vets" primary right
  (count updates live).

EMPTY STATE: friendly illustration of a vet looking through a magnifying
glass over a map, 200×200, cream-orange palette. Headline "No vets match
your filters" 18/700, subtitle "Try widening the distance or removing some
filters" 14/400 #6B7280, ghost button "Clear all filters".
```

**MJ shorthand:** `mobile vet search screen, sticky search bar top, horizontal filter chips strip, list of warm white vet cards with photos and ratings, list-map toggle, soft cream background, modern friendly design, 9:16 --style raw`

---

## 5. Vet Detail

**Goal:** Build trust + book. Reviews and credentials prominent.

**Hero focal point:** the vet's photo + name + rating + Book button.

**Layout prompt**

```
Design a Vet Detail screen for RESPAW, mobile 390×844.

Header (parallax, expands to 280px when scrolled to top):
- Cover photo of the clinic exterior with a soft orange overlay gradient at
  the bottom (orange 0% → 40%).
- Bottom-left of cover: round vet photo 88×88 with 4px white border,
  half overlapping the cover edge.
- Vet name 22/700 ink right of photo, clinic name 14/500 #6B7280 below.
- Rating row: gold star icon + "4.8" 14/600 + "(312 reviews)" 12/500 #6B7280.

Sticky action bar (sits below header on scroll):
- Five icon buttons in a row, each 56×56 white card radius 16, label below:
    "Book" (primary orange filled), "Online", "Call", "Directions", "Save".
- "Book" button has the orange filled style; the rest are ghost icons.

TABS (sticky, full-width, 3 segments): About | Hours | Reviews

ABOUT TAB (default):
- "Quick info" row of 4 chips: years exp · accepted species count · languages count
  · response-time average. Each chip 88×72, white, radius 16, icon top, value
  middle big, label below tiny.
- "Specialization" section: title 16/700, then specialization tags as chips.
- "Services & fees" section: each service row 56px tall — icon left, name +
  description center, fee right "₹500" 14/700 ink. Subtle divider between rows.
- "About the vet" section: bio paragraph 14/400 ink with line height 1.5,
  collapsible at 4 lines with "Read more".
- "Languages" section: chips with country flags.
- "Clinic" section: address card with mini static map preview, tap → directions.
  Inside the card: address 14/500, phone 13/400 with call icon.

HOURS TAB:
- Today's row prominent at top: green dot "Open now • Closes at 8pm" 14/600.
- Weekly grid: 7 rows (Mon–Sun), each row shows the day name left, time range
  right (e.g. "9:00 AM – 8:00 PM"). Closed days show "Closed" gray.
- Holiday/closure callout banner if applicable, soft yellow.

REVIEWS TAB:
- Aggregate header: large 4.8 in Fraunces 48/700, gold stars below, total count.
  Bar chart breakdown for 5/4/3/2/1 stars.
- "Write a review" CTA pill button — only enabled if the user has a completed
  appointment with this vet.
- Review cards: 14/600 reviewer name + verified-badge if completed_appointment,
  date 12/400 #6B7280, gold star row, title 14/600, comment 14/400 with
  read-more.
- Vet's reply card nested below (indented 16, soft orange bg #FFF1E8): "Reply
  from {vet}" 12/600, comment 13/400.

Floating bottom CTA (only on About + Reviews tabs):
- Full-width primary button "Book an appointment" with the next available slot
  preview right-aligned, e.g. "Earliest: Today 4:30 PM".
```

**MJ shorthand:** `mobile vet profile screen, parallax clinic cover photo with orange overlay, large round vet portrait, name + rating + reviews count, action icon row Book/Online/Call/Directions/Save, segmented tabs, services with prices, sticky bottom Book button, modern warm design, 9:16 --style raw`

---

## 6. Booking Slot

**Goal:** Pick a time in 3 taps.

**Hero focal point:** the date + time grid.

**Layout prompt**

```
Design a Booking Slot screen for RESPAW, mobile 390×844.

Top bar 56px: back arrow + title "Book appointment" 16/600 + close X.

Compact vet card at top (full width, 80px tall, white, radius 16):
photo 56×56 left, vet name + clinic stacked center, small "Change" link right.

PET SELECTOR row (titled "For which pet?"):
- Horizontal scroll of pet circle avatars 64×64. Selected pet has 3px orange
  ring + name shown below 12/600 ink. Others 12/500 #6B7280.
- Trailing "+ Add" tile dashed border.

APPOINTMENT TYPE radio cards (3 across, 110×110):
- Clinic visit (clinic icon + "₹500")
- Home visit (house icon + "₹800")
- Online (video icon + "₹400")
Selected card: orange border 2px, background #FFF1E8, icon orange filled.
Subtitle below selector: brief description of the type 12/400 #6B7280.

(If Online selected) MODALITY sub-radio: 3 chips Video / Audio / Chat.

DATE picker — horizontal week strip (sticky):
- 7 day cards 48×64, white. Each shows weekday short (Mon) + day number.
- Today highlighted with a tiny orange dot. Selected day filled orange,
  text white.
- Right arrow chevron pages to next week.

TIME slot grid:
- Section labels: "Morning", "Afternoon", "Evening".
- 3-column grid of time chips 100×44, radius 12, white, border #E5E7EB.
- Available: ink text. Booked: bg #F5F5F5 strikethrough text. Selected: bg
  orange, white text.
- Empty state: "No slots on this day. Try {next available day}." link.

REASON textarea (collapsible card, default collapsed):
- Header "Add a note for the vet (optional)" with + icon.
- Expanded: textarea 80px tall, max 500 chars with counter.

FEE SUMMARY card (bottom, white, padding 16):
- Line items: Consultation fee ₹500, Platform fee ₹49, Total ₹549.
- Toggle (segmented): "Pay ₹49 booking token" | "Pay full ₹549".
- Refund policy mini-link: "Auto-refund guaranteed" with shield icon.

Sticky bottom CTA:
- Primary full-width "Confirm & Pay ₹49" — amount updates live.
- Disabled until pet + slot selected.
```

**MJ shorthand:** `mobile appointment booking screen, compact vet card top, pet avatar selector row, three appointment-type radio cards, weekly date strip, time slot grid morning/afternoon/evening, fee summary, sticky orange Confirm button, modern friendly design, 9:16 --style raw`

---

## 7. Payment

**Goal:** Pay confidently, recover gracefully on failure.

**Hero focal point:** the bold total amount + trust badges.

**Layout prompt**

```
Design a Payment screen for RESPAW, mobile 390×844.

Top bar 56px: back arrow + title "Payment" 16/600.

HERO total card (white, radius 24, padding 24, m-16):
- Eyebrow "Total payable" 12/500 #6B7280
- Amount "₹549" 40/700 Fraunces ink (display font for impact)
- Tiny line "Booking token only — pay rest at clinic" 12/500 #E55A0E
  (only when token mode selected)

Booking summary (collapsible, default collapsed):
- "Booking summary" 14/600 + chevron.
- Expanded shows compact rows: vet, date, type, pet, fees breakdown.

Refund-policy badge row (3 horizontal chips):
- "Verified vets" (shield-check icon green)
- "Auto refund" (rotating-arrow icon)
- "Secure payment" (lock icon)
Each chip: bg #F8F8F6, radius 999, height 28, icon + label 11/600 #374151.

PAYMENT METHODS list:
- Section title "Choose payment method" 14/700.
- Method rows (each 64px, white, radius 16, padding 16):
    - UPI — icon left, label "UPI", "Pay via any UPI app" subtitle, radio right.
    - Cards — Visa/Mastercard/Rupay logo cluster.
    - Net banking
    - Wallets (Paytm, PhonePe icons)
    Selected method has orange 2px border + radio filled orange.

SECURITY footer:
- "Powered by Razorpay" with razorpay logo, 12/500 #6B7280, lock icon left.
- Terms link.

Sticky CTA: full-width primary "Pay ₹549" with right arrow.
- On tap → opens Razorpay native modal.
- After success: full-screen success state (see SUCCESS state below).
- After failure: shake animation + alert pill "Payment failed. Try again?"

SUCCESS STATE (full screen takeover):
- Confetti burst (orange + cream particles, 1.5s).
- Big check icon (in orange filled circle, 96px).
- "Booking confirmed!" 24/700 Fraunces.
- Subtitle "We've notified Dr. Mehta. You'll get a reminder 30 min before."
  14/400 #6B7280 centered.
- Two CTAs: "View appointment" (primary), "Back to home" (ghost).

FAILURE STATE:
- Tilted alert icon in red, gentle.
- "Payment didn't go through" 18/700.
- Reason if known. CTAs: "Try again" (primary), "Use different method" (ghost),
  "Contact support" (text link).
```

**MJ shorthand:** `mobile payment screen, hero total amount displayed huge in serif, booking summary card, payment method list with UPI cards netbanking icons, trust badges row verified-vets auto-refund secure-payment, sticky orange Pay button, success confetti state, modern fintech polish, 9:16 --style raw`

---

## 8. My Appointments

**Goal:** Quick triage of upcoming + past care.

**Hero focal point:** the next upcoming appointment card.

**Layout prompt**

```
Design a My Appointments screen for RESPAW, mobile 390×844.

Top bar 56px: title "Appointments" 18/700, right side a filter icon.

Tabs (segmented, full-width pill below header): Upcoming | Past
Active tab: bg white + soft shadow + ink text. Inactive: text #6B7280.

UPCOMING tab:
- Optional hero "Next visit" card (white, radius 24, padding 20, mx-16):
    - Eyebrow "NEXT VISIT" 11/700 letter-spaced #E55A0E.
    - Big date in Fraunces "Tomorrow, 4:30 PM" 22/700 ink.
    - Vet row: photo 40 round + name + clinic.
    - Pet chip + appointment type chip.
    - Two CTAs: "Get directions" ghost + "Reschedule" ghost.
    - Countdown ribbon at bottom (only if within 24h): "Starts in 14h 22m"
      orange ribbon.
- "All upcoming" section header.
- Appointment cards (smaller, full-width, 88px tall):
    Left: 4px orange stripe, then date block (day + month abbreviated).
    Center: pet photo small + pet name + vet name + clinic + type chip.
    Right: status pill + chevron.
    Tap → Appointment Detail.

PAST tab:
- Same card style, gray stripe instead of orange.
- "Add review" small pill button on the right of cards where status=completed
  and review is missing — prominent if it's been recently completed.

EMPTY UPCOMING state:
- Illustration: a calm dog snoozing on a calendar page.
- "No appointments yet" 18/700.
- "Find a trusted vet near you" 14/400 #6B7280.
- Primary CTA "Find a vet" → opens Search.

APPOINTMENT DETAIL (sub-screen, full width):
- Hero band: similar to next-visit card but at full width.
- Status timeline: horizontal step indicator (Booked → Accepted → In progress
  → Completed). Current step orange filled, completed steps green check,
  pending steps gray.
- Patient (pet) summary card.
- Owner-vet contact row with tap-to-call.
- "What to bring" tip card if applicable.
- "Visit notes" section after completion (read-only of vet's notes).
- "Payment" section with status + razorpay reference.
- Action buttons (sticky bottom, may be 1 or 2):
    Pre-visit:    [Reschedule] [Cancel]
    During:       [Join call]   (only for online + within join window)
    Post-visit:   [Write review] [Book again]
```

**MJ shorthand:** `mobile appointments list screen, segmented Upcoming/Past tabs, big white "Next visit" hero card with date in serif, list of appointment cards each with pet photo and orange stripe accent, status pills, modern friendly health app style, 9:16 --style raw`

---

## 9. Consultation Room

**Goal:** Make a video consult feel safe, controlled, and clinical when it should be.

**Hero focal point:** the vet's video tile + the end button.

**Layout prompt**

```
Design a Consultation Room screen for RESPAW, mobile 390×844, supporting
three modalities: video, audio, chat.

VIDEO MODE:
- Background: full-bleed vet's video stream.
- Top bar (translucent dark gradient overlay):
    Left: small chip "● Live • 02:14" (orange dot pulsing).
    Center: vet name 14/600 white + tiny encrypted-lock icon.
    Right: signal-strength icon (4 bars, color-coded).
- Self-view tile: 96×128, draggable, top-right by default, radius 16,
  2px white border, slight shadow. Long-press → swap with main.
- Vet info pip (top-left, beside the live chip): tap → bottom sheet with
  pet info, allergies, last visit summary (read-only).
- Bottom controls bar (translucent dark bg, 96 tall, safe-area aware):
    Five circular buttons 56px:
      mute (mic), video toggle, speaker toggle, chat panel, end-call (red).
    End call: filled red #EF4444, slightly larger 64px, with subtle pulse.
- Side chat panel (slides in from right, 80% width, frosted-glass-on-orange
  if over the video — but DON'T use glassmorphism on a static UI):
    same chat UX as Chat mode below, with "End consult" pinned at top right.

AUDIO MODE:
- Background: deep cream gradient #FFF1E8 → white, with the vet's avatar
  large 200×200 round in the upper third with a slowly-pulsing orange aura
  matching speech amplitude.
- Below avatar: vet name 20/700 ink, "On call" 14/500 #6B7280, timer big
  Fraunces 32/700 ink.
- Bottom controls identical to video mode, minus the camera button.

CHAT MODE (full chat experience):
- Chat header (sticky, 64px): vet avatar small + name + "● online" green dot
  + timer + "End" pill button right.
- Message list:
    Vet message bubbles: bg white, ink text, left-aligned, radius 20 (top-left
    16), max-width 78%.
    User bubbles: bg orange #FF6B1F, white text, right-aligned, radius 20
    (top-right 16).
    System messages (e.g. "Vet joined", "Connection unstable"): centered
    pill chip, bg #F8F8F6, 12/500 #6B7280.
    Timestamps: tiny 11/400 #6B7280, only on the first of each minute.
- Read receipts: 2 ticks orange when seen.
- Composer (sticky bottom, 64px above safe area):
    Round + button (attachments — image, prescription request),
    full-width input pill (bg #F8F8F6, radius 999, placeholder "Message Dr…"),
    send button (orange circular paper-plane, 40×40).

CONNECTION DEGRADATION:
- Top of screen: amber banner "Connection unstable. Reconnecting…"
- After a real failure: full-screen takeover with friendly illustration of a
  paper-plane mid-flight, "We lost the connection. Trying once more…" + Retry
  button + "End and refund" link.

POST-CONSULT:
- Auto-route to Visit Summary screen (read-only of vet notes + diagnosis +
  prescription) with a "Rate your consult" CTA at the bottom.
```

**MJ shorthand:** `mobile telemedicine video call screen, full-bleed vet video, draggable self-view top right, dark translucent bottom controls with mute/video/end buttons, live timer chip top, calm clinical modern design, audio mode shows large pulsing avatar on cream background, 9:16 --style raw`

---

## 10. Pet Profiles

**Goal:** Gallery of pets that feels personal, not transactional.

**Hero focal point:** the pet card grid with photos.

**Layout prompt**

```
Design a Pet Profiles screen for RESPAW, mobile 390×844.

Top bar 56px: title "Your pets" 18/700 + "+" round button right.

If user has 1+ pets:
- Hero card (full width, white, radius 24, padding 20, mx-16) showcasing the
  primary pet:
    Big circular photo 120×120 centered, with a tiny orange medical-cross
    badge on bottom-right edge (denotes "has records").
    Pet name 22/700 Fraunces ink, centered.
    Sub-line: "{species} • {breed} • {age}" 14/500 #6B7280.
    Quick stats row of 3 mini cards (88×72 each):
      Records · Reminders · Last visit. Each shows count + tiny icon.

- Section "All pets" 14/700.
- 2-column grid of pet cards (per master "PET CARD" spec): photo top circular,
  name + species below. Last tile is dashed-border "+ Add a pet" plus icon.

If user has 0 pets:
- Center illustration of a curious dog peeking over a fence (cream-orange
  palette, 240×240).
- Title "Let's meet your pets" 22/700 Fraunces ink.
- Subtitle "Add up to 5 pets to track records, reminders, and visits."
- Primary CTA "Add your first pet".

ADD/EDIT PET form (full-screen drawer or modal):
- Header "New pet" / "Edit Bruno" + close X.
- Photo placeholder big circle 140×140 with camera icon overlay, "Add photo"
  caption — tap → camera/gallery picker.
- Form fields (each 56 height, radius 14, full-width white card with field
  inside):
    - Name (text)
    - Species (dropdown with cute species icons)
    - Breed (autocomplete, suggestions list)
    - Date of birth (date picker, friendly "8 years young")
    - Sex (radio segmented Male / Female / Unknown)
    - Weight (number with unit toggle kg/lb)
    - Color / markings (text)
    - Allergies (chip multi-add — pre-suggested chips like
      "Beef", "Chicken", "Pollen", "Dust" + "Add custom").
    - Notes (textarea, 96 tall)
- Primary CTA "Save pet". Secondary "Delete" (text-link red, edit mode only,
  with two-step confirm).

DELETE confirmation modal:
- Soft alert: "Delete Bruno's profile?"
- Body: "All medical records and reminders will also be removed.
  This cannot be undone."
- Buttons: "Cancel" ghost, "Delete" red filled.
```

**MJ shorthand:** `mobile pets list screen, hero white card with large circular dog photo and stats, two-column grid of pet cards each with circular photo, dashed add-pet tile, friendly warm design, empty state with curious dog illustration, 9:16 --style raw`

---

## 11. Pet Records

**Goal:** A health timeline that's calm, not clinical-cold.

**Hero focal point:** the pet's avatar + the timeline first item.

**Layout prompt**

```
Design a Pet Records screen for RESPAW, mobile 390×844.

Header (compact, 200px tall, full-bleed):
- Soft orange gradient #FFF1E8 → white background.
- Center: big circular pet photo 120×120 with a colored ring per species type.
- Below: name 22/700, species/breed 14/400 #6B7280.
- Compact stats strip (3 chips): weight · age · vaccinated up-to-date(✓/✗).
- Small "Edit" pencil icon top-right.

Sticky tab bar (full width, 6 tabs scroll horizontally):
Timeline · Vaccinations · Records · Medications · Reminders · Notes

TIMELINE (default):
- Year/month section headers (e.g. "May 2026") sticky, 12/700 #6B7280.
- Event cards on a vertical "spine" with colored dots:
    💉 Vaccination: green spine dot. Title "Rabies booster", date,
       vet name, "View certificate" link.
    💊 Prescription: orange dot. Title + duration + "View".
    🧪 Lab report: blue dot.
    🩺 Vet visit: ink dot. Title + diagnosis snippet.
    🔔 Reminder: gray dot. Title + due date.
    📝 Owner note: cream dot.
- Each card: white, radius 16, padding 14, with the dot on the spine to the
  left and a 1px hairline connecting them.
- Empty state: "No records yet. Records appear after each vet visit."

VACCINATIONS:
- "Up next" card at top with the next due vaccination + due date countdown.
- Vaccination history list. Each row: vaccine name, date given, vet,
  certificate-download button.

RECORDS (documents):
- Grid of doc tiles (2 columns, 168×200): file-type icon, title 14/600,
  date small, size, more-menu (download / delete).
- FAB "+ Upload" bottom right.

MEDICATIONS:
- Toggle: "Active" / "Past".
- Active medication card: bg #FFF1E8, radius 16, padding 14:
    name + dosage + frequency + days remaining (progress bar),
    "Mark taken" pill button + overflow menu (discontinue).
- Past medications: white cards, no progress bar, with end date.

REMINDERS:
- Calendar-style list grouped by week.
- Each reminder card: title, due date, recurring badge, "Complete" check
  button. Overdue rendered with soft red tint and "Overdue 2 days" label.

NOTES:
- Owner's personal notes — sticky-note aesthetic with rotated 0.5° cards,
  cream background, handwritten-feeling display font for headlines optional.
- Each note: title, body snippet, date, edit pencil.

Cross-tab FAB (only on Records, Reminders, Notes tabs): orange round + button.
```

**MJ shorthand:** `mobile pet health records screen, header with circular pet photo on warm gradient, sticky horizontal tabs Timeline/Vaccinations/Records/etc, vertical timeline with colored dots and event cards, soft warm friendly health app design, 9:16 --style raw`

---

## 12. Prescriptions

**Goal:** Easy lookup of every script across every pet.

**Hero focal point:** the most recent prescription with download CTA.

**Layout prompt**

```
Design a Prescriptions screen for RESPAW, mobile 390×844.

Top bar 56px: title "Prescriptions" 18/700.

Filter strip below header: pet selector (horizontal pet avatars, "All pets"
chip first), date range chip, sort chip.

LATEST card hero (mx-16, white, radius 20, padding 20):
- Eyebrow "MOST RECENT" 11/700 #E55A0E.
- Title with the diagnosis e.g. "Skin allergy treatment" 18/700 ink.
- Vet + date row.
- Pet chip with avatar.
- A mini list of medications (3 rows max) with name + dosage.
- Two CTAs: "View full" ghost + "Download PDF" primary small.

LIST of older prescriptions:
- Cards 88px tall: paper-icon left circle, title, vet + date stacked, pet
  chip, right chevron.
- Pull-to-refresh.

PRESCRIPTION DETAIL (sub-screen):
- Header: pet avatar + name + vet info + visit date.
- Diagnosis section (highlighted card #FFF1E8): "Diagnosis" 12/700 #E55A0E
  + body 16/500 ink.
- Medications list: rows with drug name 16/600, dosage 14/500, frequency
  14/500, duration 14/500, "Set reminders" toggle that creates pet reminders.
- Vet notes section (white card).
- Attached PDF preview thumbnail, tap → open viewer.
- Sticky CTA "Download PDF" + share icon.

EMPTY STATE: friendly bottle illustration "No prescriptions yet" with
suggestion "After a vet visit, prescriptions will appear here."
```

**MJ shorthand:** `mobile prescriptions list screen, filter strip with pet avatars, hero "most recent" card with diagnosis title and download button, list of prescription cards each with paper icon, modern warm health app, 9:16 --style raw`

---

## 13. Subscription Plans

**Goal:** Sell the value, make purchase feel painless.

**Hero focal point:** the plan-comparison cards.

**Layout prompt**

```
Design a Subscription Plans screen for RESPAW, mobile 390×844.

Hero (full-bleed top, 240px):
- Soft orange-to-cream gradient.
- Floating illustration of a happy dog wearing a tiny crown, 120×120 left.
- Right: hero copy "Unlock unlimited care" 22/700 Fraunces ink + sub
  "Priority booking, free consults, vet hotline 24/7" 14/400 #374151.

CURRENT PLAN card (only if active):
- White, radius 20, padding 20, mx-16. Subtle gold ribbon top-left "Active".
- Plan name + period (e.g. "Premium · Monthly").
- "Renews on May 28" 12/500 #6B7280.
- Two ghost buttons "Manage" "Cancel".

PLAN GRID (3 plans, vertical stack OR horizontal swiper):
- Each plan card: white, radius 24, padding 24, with the "Premium" plan
  highlighted via orange border 2px + "Most popular" badge top.
- Card content top→bottom:
    Plan name + tagline.
    Price big "₹299" 32/700 Fraunces / "month" 14/500 below.
    Original price strikethrough if sale active, alongside savings chip.
    Feature bullets — each row: orange check icon + feature 14/500 ink.
      e.g. "5 free online consults", "Priority vet response", "10% off vaccinations"
    Primary CTA button: "Choose Premium" with right arrow.

FEATURE COMPARISON table (collapsible "Compare plans" link):
- Three columns: Basic / Premium / Pro.
- Rows: feature name on left, ✓ or — per plan.
- Sticky header on horizontal scroll.

FAQ accordion (3 items collapsible):
- "Can I cancel anytime?" "What happens to my pets after cancel?" "How are
  refunds handled?"

TRUST footer:
- "30,000+ pet parents trust RESPAW" 12/500 with tiny avatar stack.
- "Cancel anytime" + "Money-back guarantee 14 days".

PURCHASE flow:
- Tap a plan → bottom drawer with plan summary + "Proceed to pay" → routes
  to Payment screen with payable_type=subscription preselected.
- After successful payment: full-screen success (confetti, "Welcome to
  Premium 🎉", show new perks unlocked).
```

**MJ shorthand:** `mobile subscription plans screen, hero gradient with crowned dog illustration, three vertical plan cards Basic/Premium/Pro with Premium highlighted in orange, prices in serif font, feature checklist with orange check icons, modern fintech-grade design, 9:16 --style raw`

---

## 14. Blogs

**Goal:** Content that gets read. Editorial polish.

**Hero focal point:** the featured article cover.

**Layout prompt**

```
Design a Blogs / Tips screen for RESPAW, mobile 390×844.

Top bar 56px: title "Pet care tips" 18/700.

Search + categories strip (sticky):
- Search input pill.
- Category chips horizontal scroll: "All", "New parents", "Behavior",
  "Nutrition", "Emergencies", "Senior pets". Active chip orange filled.

FEATURED article hero card (mx-16, full width, 200 tall):
- Full-bleed cover image with gradient overlay bottom (orange→transparent).
- Bottom-left text on image: category chip + title in white 22/700 Fraunces
  (max 2 lines) + author/read-time in 12/500 #FFF1E8.

ARTICLE LIST cards (each 96 tall):
- Cover image 96×96 left, radius 16.
- Center: category chip (small), title 16/600 ink (2 lines max), author + date
  + "5 min read" 12/500 #6B7280.
- Right: bookmark icon (filled orange when saved).
- Tap → Article Detail.

ARTICLE DETAIL (full-screen reader):
- Hero cover image (parallax, full-bleed 280 tall) with gradient bottom.
- Title 28/700 Fraunces ink.
- Meta row: author avatar + name + date + read-time.
- Action row sticky on scroll: bookmark · share · listen (audio TTS optional).
- Body: 16/500 ink, line-height 1.7, paragraphs spaced 24, pull-quotes
  centered orange Fraunces 22/600 italic, image captions 12/400 italic.
- Comments section at bottom: sorted "Most liked", composer pinned bottom.
- "Related articles" carousel 3-up.

LIKE animation: heart pop with confetti orange dust.

EMPTY STATE: "No articles match" with magnifying-glass-on-paw illustration.
```

**MJ shorthand:** `mobile pet blog screen, sticky search and category chips, featured article hero card with cover image and serif title, list of article cards with thumbnail and bookmark icon, editorial polish, modern warm reading app, 9:16 --style raw`

---

## 15. Profile / Settings

**Goal:** Owner-respecting account management. Easy to find logout, hard to fat-finger Delete.

**Hero focal point:** the user's profile card with avatar.

**Layout prompt**

```
Design a Profile / Settings screen for RESPAW, mobile 390×844.

Background: cloud #F8F8F6.

PROFILE card (mx-16, top, white, radius 24, padding 20):
- Large circular avatar 88×88 with "Edit" small camera badge bottom-right.
- Name 18/700 ink + email 13/400 #6B7280.
- Verified-email badge if applicable.
- Right side: "Edit profile" ghost button.

QUICK STATS row of 3 mini cards (white, radius 16, 88×72):
- Pets · Bookings · Reviews — each a count + tiny icon.

GROUP cards (each white, radius 20, mx-16, padding-top 8):
- "ACCOUNT" — group label 11/700 letter-spaced #6B7280 above the card:
    Rows: Edit profile · Email & verification · Phone & OTP · Change password
    Each row: 56px, icon left in soft circle bg #FFF1E8 with orange icon,
    label center 14/500 ink, right chevron.
- "PETS & CARE": My pets · Subscriptions · Saved vets · Reviews I wrote.
- "NOTIFICATIONS": Push notifications (toggle) · Email notifications (toggle)
  · Reminder times (sub-page).
- "SUPPORT": Help center · Contact us · Refund policy · Terms · Privacy.
- "ACCOUNT ACTIONS": Logout (red text-link feel within a row,
  not a button) · Delete account (separate red row, deeper red, with
  small trash icon).

LOGOUT confirm: bottom sheet drawer "Sign out?" subtitle "You'll need to
log in again to view your records." with "Cancel" ghost + "Sign out" red
filled.

DELETE flow:
- Step 1 sheet: "Delete account?" body "This permanently removes all your
  pets' records, history, prescriptions, and subscriptions. This cannot be
  undone." [Cancel] [Continue].
- Step 2 sheet: "Type DELETE to confirm" with text input + final red filled
  "Delete account permanently" button (disabled until exact match).

App version footer: "RESPAW v1.0.0 · Made with 🧡 for pet parents" 11/400
#9CA3AF centered above bottom nav.
```

**MJ shorthand:** `mobile profile and settings screen, top white profile card with circular avatar and stats row, grouped settings cards Account/Pets/Notifications/Support, soft icons in orange-tinted circles, red logout and delete account at bottom, modern friendly account screen, 9:16 --style raw`

---

# Cross-cutting screens

## 16. Notifications

```
Mobile Notifications screen, RESPAW, 390×844.

Top bar: title "Notifications" 18/700, right "Mark all read" link 13/600 #E55A0E.
Tabs: "All" / "Bookings" / "Pets" / "Promos".

Notification cards (each 80px tall):
- Left: tinted icon circle (color by type: orange=appointment, blue=consult,
  red=SOS, green=payment, gold=promo).
- Center: title 14/600 ink (1 line) + body 13/400 #374151 (2 lines max) +
  time-ago 11/500 #6B7280.
- Right: unread orange dot 8×8.
- Tap → deep link.
- Swipe-left reveals "Mark read" + "Delete".

Empty state: "All caught up 🐾" with peaceful sleeping cat illustration.
```

## 17. SOS Emergency Modal + Live Tracker

```
SOS bottom modal:
- Slide-up sheet, 90% height, soft red top accent (4px).
- Big "Need urgent care?" 24/700 ink.
- Subtitle "We'll alert nearby emergency vets within 30 seconds."
- Pet selector row (avatar bubbles).
- Notes textarea: "What's happening?"
- Auto-detected location card: address text + "Update location" link.
- Big bottom button "Send SOS" — RED #EF4444 instead of orange. Hold-to-confirm
  optional (long-press 1s).

Post-send Live Tracker screen:
- Status banner top: pulsing red "SOS active" or green "Vet en route".
- Big map showing user pin + assigned vet's pin moving (driving icon on path).
- Below map: assigned vet card with photo, name, phone (call button), ETA.
- Status timeline horizontal: Sent → Vet matched → On the way → Arrived.
- Pulsing "Cancel SOS" button at bottom (only when status=pending).
- Audio cue option for the elderly: tap-to-listen status updates.
```

## 18. AI Chatbot (Pet Assistant)

```
Mobile AI chatbot screen RESPAW, 390×844.

Top bar: title "Pet Assistant" + tiny avatar of an AI paw mascot + "New chat"
icon right.

Sessions list (left drawer or initial state): list of past conversations.

Active chat:
- Welcome card from assistant: "Hey, I'm Spot 🐾. Ask me anything about your
  pet — symptoms, food, training, you name it. (I'm not a vet — for
  emergencies, please book a real vet.)"
- Suggestion chips: "My dog won't eat", "How much should my puppy weigh?",
  "First-aid for cats".
- User bubbles right (orange), assistant bubbles left (white). Markdown
  rendering: bullet points, tables for dosage charts, links.
- Composer: text input + mic icon + image-attach icon + send.

Inline disclaimers: small footer beneath assistant replies "Always consult a
vet for medical decisions. Want to book? [Find a vet]".
```

---

# Generic patterns to reuse

## Empty states

```
Centered illustration 200×200 (orange + cream + ink line work, never
photorealistic), title 18/700 Fraunces, subtitle 14/400 #6B7280, primary
CTA button. Above the illustration, optional eyebrow chip.
Mood: never apologetic, always inviting.
```

## Loading states

```
Skeletons that mimic the structure exactly:
- Avatar circles: gray 200 with shimmer.
- Text lines: rounded rects, varying widths (60%, 90%, 40%).
- Cards: same radius/padding as real cards.
- Shimmer animation: linear gradient sweeping at 1.4s loop.
NEVER use centered spinners on lists. Spinners ONLY inside buttons during
form submits.
```

## Error states

```
Inline (non-blocking): top-of-screen amber pill banner "Something went wrong.
Tap to retry." with retry icon. Auto-dismiss on success.
Full-screen: friendly broken-bone illustration, "We hit a snag" 18/700,
description 14/400 #6B7280, CTAs Retry + Contact support.
NEVER show raw stack traces.
```

## Offline state

```
Subtle persistent banner at the top "You're offline" gray bg slate-700 text,
with a wifi-slash icon. Auto-dismisses 1.5s after reconnect.
Queued actions show with a clock icon "Will sync when online".
```

## Push notification visual

```
FCM-rendered notifications use a small orange respaw paw icon. Title is 14/600,
body 13/400. Big-picture style for prescription images, big-text style for SOS.
On tap → deep link to relevant screen (see USER_APP_SCREENS.md mapping).
```

---

# Tool-specific tips

### v0.dev / Lovable / Bolt
Paste **Master Brand Brief + screen prompt** in the same message. Ask for
React + TailwindCSS + shadcn/ui. Specify `mobile-first 390px viewport`.
After the first generation, iterate by asking to "swap the X with Y".

### Galileo AI / Uizard / Figma AI
Paste only the **Layout prompt** for the screen you want. Skip the master
brief — those tools handle global tokens via their own design-system menu.

### Midjourney
Use only the **MJ shorthand** line. Add `--ar 9:16 --style raw --v 6.1`
for mobile aspect ratio + minimal stylization. For consistent visuals,
add `--seed 12345 --cref <previous-screenshot-url>` in follow-ups.

### Hand-off to a human designer
Pair this doc with [USER_APP_SCREENS.md](USER_APP_SCREENS.md) (the field-level
spec). Designer interprets brand brief; uses the layout prompt as a brief and
the field spec as the contract.

---

# Asset wishlist

To bring this to life, you'll need:

| Asset | Where used | Style note |
|---|---|---|
| Brand logo (orange, cream, full color) | Splash, header, push icon | SVG + PNG @1x/2x/3x |
| Mascot illustration set | Empty states, onboarding | Flat, 8 expressions |
| Pet species icons | Pet form, vet specialization filters | Line, 24px, paw-themed |
| Status illustrations | success / error / offline / no-results | One per state, 200×200 |
| App-store screenshots | iOS + Android store listings | 6 hero shots from key screens |
| Marketing OG image | Web fallback, share previews | 1200×630 with brand orange |

Drop these in `pet-help-backend/storage/app/public/brand/` (or your CDN) and
reference via `APP_URL` env in deeplink metadata.
