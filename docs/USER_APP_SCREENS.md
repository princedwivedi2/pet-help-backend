# User App — Screen Specifications (Pet Parent)

Frontend build spec for the pet-parent app. Each screen lists purpose, layout sections, fields shown / inputs collected, actions, API calls, and edge states.

For request/response shapes see [API.md](API.md). For the underlying domain model see [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Conventions

- **Field labels** in **bold** are user-visible.
- API references use `METHOD path` notation (full path under `/api/v1/...`).
- Loading / empty / error states are listed once per screen.
- `(req)` = required input, `(opt)` = optional.

Auth header on every protected call: `Authorization: Bearer {token}`.

---

## 1. Splash

**Purpose:** Boot screen — load app config, check auth, route.

| Section | Content |
|---|---|
| Center | Logo, app name "RESPAW / Pet Help", tagline "Find trusted care for your pet" |
| Footer | Version, build number |

**Logic on mount:**
- Read stored Sanctum token (SecureStore).
- If token absent → route to **Login / Signup**.
- If token present → call `GET /auth/me`. On 200 → **Home**. On 401 → clear token, route to **Login / Signup**.
- Show splash for at least 1.2s for branding.

**API:** `GET /api/v1/auth/me`

---

## 2. Login / Signup

**Purpose:** Onboard or authenticate.

### Tabs

| Tab | Title |
|---|---|
| Login | Sign in |
| Signup | Create account |

### Login form

| Field | Type | Required | Validation |
|---|---|---|---|
| **Email or phone** | text | yes | email format OR digits |
| **Password** | password | yes | min 6 chars |
| **Use OTP instead** link | — | — | switches to OTP flow |

Actions:
- **Sign In** → `POST /auth/login` body `{ email, password }`. Store `data.token`. Route based on role:
  - `user` → **Home**
  - `vet` (approved) → vet app's Dashboard
  - `vet` (pending) → show `loginNotice` from response, route to limited home
  - `admin` → web admin panel (or block in mobile)
- **Forgot password** → modal asking for email, calls `POST /auth/forgot-password`.

### Signup form

| Field | Type | Required | Validation |
|---|---|---|---|
| **Full name** | text | yes | 2–60 chars |
| **Email** | email | yes | valid email |
| **Phone** | phone | opt | E.164 |
| **Password** | password | yes | min 8 chars |
| **Confirm password** | password | yes | matches password |
| **I agree to T&C** | checkbox | yes | must be checked |

Actions:
- **Create account** → `POST /auth/register`. Store token. Show "Please verify your email" banner. Route to **Home**.

### OTP flow (alternate)

| Field | Type |
|---|---|
| **Email or phone** | text |
| **Get OTP** button | — |
| (after send) **Enter 6-digit code** | numeric, 6 chars |
| **Resend** (disabled 60s) | — |

Actions:
- Send → `POST /auth/otp/send` body `{ identifier }` (channel auto-detected from `@` presence)
- Verify → `POST /auth/otp/verify` body `{ identifier, code }`. On 200, the user is identity-verified; in the future this will also issue a Sanctum token for true passwordless login.

**Edge states:** show inline errors from `errors.email`, `errors.password`, etc. Map 422 messages 1:1.

---

## 3. Home

**Purpose:** Daily landing — quick actions, nearby vets, my pets, banners.

### Sections (top → bottom)

1. **Header**
   - Greeting "Hi, {name}"
   - Notification bell with unread count → tap → **Notifications** screen
   - Profile avatar → tap → **Profile / Settings**

2. **Search bar** (taps into **Search Vets**)
   - Placeholder: "Search vets, clinics, services"
   - Right icon: emergency 🚨 (taps into SOS flow modal)

3. **Quick actions row** (horizontal cards)
   - **Consult Now** (primary) → instant consult flow
   - **Book Visit** → search vets
   - **My Pets** → pet profiles
   - **SOS** → SOS modal

4. **Promotional banners** (carousel)
   - From `GET /ad-banners?position=home_top`
   - Each: image, title, link target

5. **Nearby Vets** (horizontal scroll, 5–10 items)
   - From `GET /vets?lat={lat}&lng={lng}&limit=10` (after location permission granted)
   - Each card: photo, vet name, clinic, rating ★, distance "1.2 km away"
   - Tap → **Vet Detail**
   - "See all" → **Search Vets**

6. **My Pets** (horizontal scroll)
   - From `GET /pets`
   - Each: pet photo, name, species. "+" button → add pet.
   - Tap → **Pet Records**

7. **Tips & Articles** (vertical list of 3)
   - From `GET /blog/posts?per_page=3`
   - Each: thumbnail, title, category. Tap → **Blog Detail**.

8. **Bottom tab bar** (across all screens):
   - Home / Search / Pets / Appointments / Profile

**API on screen mount:**
- `GET /vets?lat&lng&limit=10`
- `GET /pets`
- `GET /ad-banners?position=home_top`
- `GET /blog/posts?per_page=3`
- `GET /notifications/unread-count`

**Edge states:**
- Location denied → hide distance, show "Enable location to see nearby vets" CTA.
- Empty pets → show "Add your first pet" empty state with illustration.
- Network error → show retry banner.

---

## 4. Search Vets

**Purpose:** Discovery + filtering.

### Top bar

- **Back** button
- **Search input** (auto-submits on debounce 400 ms)
- **Filter** icon → opens filter sheet

### Filter sheet

| Field | Type | Default |
|---|---|---|
| **Distance** | slider 1–100 km | 10 |
| **Emergency only** | toggle | off |
| **Available now** | toggle | off |
| **Specialization** | chips (general, surgery, dental, dermatology, …) | none |
| **Languages** | chips multi-select (en, hi, ta, te, kn, mr, …) | none |
| **Min rating** | star picker 0–5 | 0 |
| **Sort by** | radio (Distance, Rating) | Distance |

Apply → `GET /vets?lat&lng&radius_km&emergency_only&available_only&specialization&languages[]&min_rating&sort_by`.

### Results

Three buckets, each section has a header:

1. **Nearby** — from `nearby_vets`
2. **In your city** — from `city_vets` (when location is fuzzy or city searched)
3. **All vets** — from `all_vets`

Each list item:

| Field | Source |
|---|---|
| Photo | `vet.profile_photo` |
| Vet name | `vet.vet_name` |
| Clinic name | `vet.clinic_name` |
| Star rating + review count | `vet.avg_rating`, `vet.reviews_count` |
| Distance | `vet.distance_km` (only when lat/lng provided) |
| Specialization tags | `vet.specialization` |
| **Open now** chip | derived from `vet.working_hours` |
| **Emergency** chip | `vet.is_emergency_available === true` |
| **24/7** chip | `vet.is_24_hours === true` |

Tap row → **Vet Detail**.

**View toggle (top right):** List ↔ Map.
- Map: pins for each vet; tap pin → mini-card with "View profile" + "Book".

**API:** `GET /api/v1/vets`

**Edge states:**
- No results → "No vets match your filters. Try widening distance."
- No location → show city-search input + manual entry.

---

## 5. Vet Detail

**Purpose:** Show full vet profile + book CTA.

### Header

- Cover image / clinic photo
- Profile photo overlay
- Vet name, clinic name
- Star rating, review count

### Action bar (sticky)

- **Book Visit** (primary) → **Booking Slot**
- **Consult Online** → **Booking Slot** (with `appointment_type=online` preselected) or **Instant Consult** modal
- **Call** → `tel:{vet.phone}`
- **Directions** → opens map app with `vet.latitude,vet.longitude`

### Tabs

#### Tab 1 — About

| Field | Source |
|---|---|
| Bio / qualifications | `vet.qualifications` |
| Specialization | `vet.specialization` |
| Years of experience | `vet.years_of_experience` |
| Languages | `vet.languages` (chips) |
| Accepted species | `vet.accepted_species` |
| Services | `vet.services` |
| Consultation fee | `vet.consultation_fee` |
| Home visit fee | `vet.home_visit_fee` |
| Online fee | `vet.online_fee` |
| Address | `vet.address`, `vet.city`, `vet.state`, `vet.postal_code` |

#### Tab 2 — Hours

- Weekly schedule from `vet.working_hours` (Mon–Sun, open–close).
- "Open now" / "Closed" badge derived from current time.
- Holiday banner if any flagged.

#### Tab 3 — Reviews

- Aggregate: average rating, total count, distribution bars (5/4/3/2/1).
- List from `GET /reviews/vet/{uuid}` — each review: reviewer name, rating, date, title, comment, vet's reply (if any).
- "Write a review" CTA only enabled for users who completed an appointment with this vet.

**API:**
- `GET /vets/{uuid}` (on screen mount)
- `GET /reviews/vet/{uuid}` (on Reviews tab)

**Edge states:** vet not found → 404 page with "Browse other vets" CTA.

---

## 6. Booking Slot

**Purpose:** Pick date, time, pet, modality.

### Sections

1. **Vet summary** (compact card with photo + name)
2. **Pet selector** — chips/dropdown from `GET /pets`. Required.
3. **Appointment type** — radio:
   - `clinic_visit` (default if vet has clinic)
   - `home_visit` (only if `vet.home_visit_fee` set)
   - `online` (video / audio / chat selector below)
4. **Modality** (only if `online`) — radio: video / audio / chat.
5. **Date picker** — week strip; tap day → load slots for that day.
6. **Slot grid** — populated from `GET /appointments/slots/{vet_uuid}?date=YYYY-MM-DD`. Disabled slots greyed.
7. **Reason** (optional textarea, max 500 chars) — "What's bringing you in?"
8. **Fee summary**
   - Type of fee (consultation / home / online)
   - Amount
   - Payment model selector: **Booking Token (₹49)** / **Pay Full** (only if vet supports both)
9. **Confirm Booking** primary button.

### Validation

| Rule | Behavior |
|---|---|
| Pet not selected | Disable Confirm |
| Slot in the past | Disable Confirm |
| No internet | Show retry |

### On confirm

1. `POST /appointments` body:
   ```json
   {
     "vet_uuid": "...", "pet_id": 1,
     "appointment_type": "clinic_visit",
     "scheduled_at": "2026-05-10T14:00:00+05:30",
     "reason": "Annual checkup"
   }
   ```
   Returns appointment with `uuid`.
2. `POST /payments/create-order` body `{ payable_type: "appointment", payable_uuid, payment_model }` → get `payment_uuid` + `razorpay_key`.
3. Route to **Payment** with these IDs.

**API:**
- `GET /pets`
- `GET /appointments/slots/{vet_uuid}?date={iso}`
- `POST /appointments`
- `POST /payments/create-order`

---

## 7. Payment

**Purpose:** Present amount, run Razorpay checkout, verify.

### Sections

1. **Booking summary** (vet, date, type, fee).
2. **Fee breakdown**
   - Subtotal
   - Tax (if any)
   - Discount (if any)
   - **Total** (large)
3. **Payment method** (single choice for V1)
   - Razorpay (UPI / cards / netbanking — handled by Razorpay SDK)
4. **Pay Now** primary button.
5. **Refund policy link** (small, below button).

### Flow

1. Open Razorpay native checkout with `key_id` + `order_id` from create-order response.
2. On Razorpay success callback → `POST /payments/verify` body `{ payment_uuid, razorpay_payment_id, razorpay_order_id, razorpay_signature }`.
   - 200 → Show success animation → route to **My Appointments** (or **Consultation Room** for online instant).
   - 422 → Show "Payment verification failed" + support link.
3. On Razorpay cancel → return to Booking Slot with order in `created` state (will auto-expire).

**API:**
- `POST /payments/verify`

---

## 8. My Appointments

**Purpose:** Upcoming + past appointments.

### Top tabs

- **Upcoming**
- **Past**

### List item

| Field | Source |
|---|---|
| Vet photo | `appointment.vet_profile.profile_photo` |
| Vet name + clinic | `vet_profile.vet_name`, `clinic_name` |
| Pet name + photo | `appointment.pet.name` |
| Date / time | `scheduled_at` (formatted) |
| Type | `appointment_type` (clinic / home / online) |
| Status pill | requested / accepted / in_progress / completed / cancelled |
| Right chevron | tap → **Appointment Detail** |

### Appointment Detail (sub-screen)

Sections:
- Header (vet, pet, date, type, status)
- Reason
- Visit Records (for past)
- Payment status (Paid / Pending / Failed)
- Actions:
  - **Reschedule** (only requested/accepted, > 12 h before slot) → modal with date/time picker → `POST /appointments/{uuid}/reschedule`
  - **Cancel** → confirm dialog → `PATCH /appointments/{uuid}/cancel`
  - **Join Consultation** (only when online + slot is now ± 5 min) → **Consultation Room**
  - **Write Review** (only completed) → review modal → `POST /reviews`

**API:**
- `GET /appointments?per_page=20` (paginated, infinite-scroll)
- `GET /appointments/{uuid}` (on detail open)

---

## 9. Consultation Room

**Purpose:** Video / audio / chat consult interface.

### Three sub-modes (driven by `consultation.modality`)

#### Video / Audio mode

Top bar:
- Vet name, modality icon, **timer** (HH:MM:SS, started when both joined)
- Connection status pill (Connected / Reconnecting / Failed)

Main area:
- Vet's video tile (large)
- Self-view tile (small, draggable)
- For audio: avatar + waveform indicator

Bottom controls:
- 🎤 mute toggle
- 📷 camera toggle (video only)
- 🔊 speaker toggle
- ➕ chat toggle (slides in chat panel)
- 🔚 end call (red)

Side panel — Chat:
- Same UI as chat-only mode (below).

#### Chat-only mode

Full-screen chat:
- Header: vet name, status, **End** button
- Message list (DB source of truth via `GET /consultations/{uuid}/messages`, plus realtime via Firebase Realtime DB / Firestore)
- Composer: text input + send button + 📎 attachment

### Lifecycle on mount

1. `POST /consultations/{uuid}/join` → returns `{ room_provider, room_id, token, role }`.
2. Subscribe to Firebase signaling path `/signaling/{room_id}` (RTDB) for SDP/ICE.
3. Initiate `RTCPeerConnection`. Exchange offers/answers via Firebase.
4. While connected, render media streams.

### On connection failure

- After 1 retry, call `POST /consultations/{uuid}/connection-failure`.
- After backend's 3rd failure → session auto-fails; show "Connection lost. Refund issued." → route to **My Appointments**.

### On vet completing

- Server pushes status update via realtime; client navigates to **Visit Summary** (read-only sub-screen).

### Visit Summary

Shows:
- Diagnosis
- Vet notes
- Prescription (downloadable PDF if uploaded)
- "Rate this consultation" CTA

**API:**
- `POST /consultations/{uuid}/join`
- `GET /consultations/{uuid}` (poll on reconnect)
- `GET /consultations/{uuid}/messages` (initial chat history)
- `POST /consultations/{uuid}/messages` (send chat)
- `POST /consultations/{uuid}/connection-failure`

---

## 10. Pet Profiles

**Purpose:** List + add/edit pets.

### List

From `GET /pets`. Each item:

| Field | Source |
|---|---|
| Photo | `pet.photo_url` |
| Name | `pet.name` |
| Species + breed | `pet.species`, `pet.breed` |
| Age | derived from `pet.dob` |
| Right chevron | tap → **Pet Records** |

Footer: **+ Add Pet** floating action button → **Add/Edit Pet** form.

### Add/Edit Pet form

| Field | Type | Required | Notes |
|---|---|---|---|
| **Photo** | image picker | opt | uploads via multipart |
| **Name** | text | yes | 1–50 chars |
| **Species** | dropdown (dog, cat, bird, rabbit, reptile, fish, other) | yes | |
| **Breed** | text | opt | 1–60 chars |
| **Date of birth** | date | opt | not in future |
| **Sex** | radio (male / female) | opt | |
| **Weight (kg)** | number | opt | 0.1–200 |
| **Color / markings** | text | opt | |
| **Allergies** | textarea | opt | |
| **Notes** | textarea | opt | |

Actions:
- **Save** → `POST /pets` (new) or `PUT /pets/{pet}` (edit).
- **Delete** (edit only, with confirm) → `DELETE /pets/{pet}`.

**API:** `GET /pets`, `POST /pets`, `PUT /pets/{pet}`, `DELETE /pets/{pet}`.

---

## 11. Pet Records

**Purpose:** Per-pet timeline of all health data.

### Header

- Pet photo, name, species, age (computed), weight
- "Edit pet" pencil icon → **Add/Edit Pet** form (pre-filled)

### Tabs

| Tab | Content | API |
|---|---|---|
| **Timeline** | Chronological feed of medical records, visit notes, prescriptions, reminders. | `GET /pets/{petId}/medical-records` + `GET /pets/{petId}/visit-records` |
| **Vaccinations** | Shots given + due. | filter `medical-records?type=vaccination` |
| **Records** | Documents (vet reports, lab reports, X-rays). | `GET /pets/{pet}/documents` |
| **Medications** | Active + discontinued. | `GET /pets/{pet}/medications` |
| **Reminders** | Upcoming pet reminders. | `GET /pets/{pet}/reminders` |
| **Notes** | Owner-written notes. | `GET /pets/{pet}/notes` |

### Timeline item types

- 💉 Vaccination — `medical_record.type === 'vaccination'`
- 💊 Prescription — from a visit record
- 🧪 Lab report — `pet_document.category === 'lab_report'`
- 📝 Vet note — visit record
- 🔔 Reminder

Each item shows: icon, title, date, vet (if applicable), short description, **View** chevron.

### Documents tab — list item

| Field | Source |
|---|---|
| File icon by mime | `document.mime_type` |
| Title | `document.title` |
| Date uploaded | `document.created_at` |
| Size | `document.size_bytes` |
| **Download** button | `GET /pets/{pet}/documents/{document}/download` → opens 60-min signed URL |
| **Delete** | `DELETE /pets/{pet}/documents/{document}` |

Footer: **+ Upload Document** → file picker + form (title, category, expires_at).

### Medications tab — list item

| Field | Source |
|---|---|
| Name + dosage | `medication.name`, `dosage` |
| Frequency | `frequency` |
| Status | `active` / `discontinued` |
| **Mark Taken** quick action | `POST /pets/{pet}/medications/{medication}/log` |
| **Discontinue** | `POST /pets/{pet}/medications/{medication}/discontinue` |

### Reminders tab — list item

| Field | Source |
|---|---|
| Title | `reminder.title` |
| Due date | `reminder.due_at` |
| Recurring badge | if `recurrence` set |
| **Complete** | `POST /pets/{pet}/reminders/{reminder}/complete` |

---

## 12. Prescriptions

**Purpose:** Shortcut view of all prescriptions across all pets (cross-pet timeline).

### List item

| Field | Source |
|---|---|
| Pet photo + name | `visit_record.pet` |
| Vet name | `visit_record.vet_profile.vet_name` |
| Date | `visit_record.created_at` |
| Diagnosis (snippet) | `visit_record.diagnosis` |
| **View / Download** | opens prescription PDF via signed URL |

Filters at top: pet selector, date range.

**API:** Aggregated via `GET /pets/{petId}/visit-records` per pet, OR a future cross-pet endpoint.

---

## 13. Subscription Plans

**Purpose:** Browse + purchase subscriptions; show current plan.

### Sections

1. **Current plan** card (only if active)
   - Plan name, badge "Active", expires_at, **Manage** button.
   - From `GET /subscriptions/active`.
2. **Available plans** grid
   - From `GET /subscription-plans` (public endpoint).
   - Each card: name, monthly price, duration, feature list (bullets), **Subscribe** CTA.
3. **FAQ** accordion at bottom (static content).

### Subscribe flow

1. Tap **Subscribe** → confirm modal with plan summary.
2. `POST /subscriptions` body `{ plan_id }` → returns Razorpay order.
3. Open Razorpay checkout.
4. On success → `POST /payments/verify` with `payable_type: "subscription"` (set internally by backend).
5. On 200 → success screen → route back here, current-plan card now shows.

**API:**
- `GET /subscription-plans`
- `GET /subscriptions/active`
- `POST /subscriptions`
- `POST /payments/verify`

---

## 14. Blogs

**Purpose:** Educational content browsing.

### Blog list

Top: search input + category chips (from `GET /blog/categories`).

Each list card:

| Field | Source |
|---|---|
| Hero image | `post.cover_image` |
| Category chip | `post.category.name` |
| Title | `post.title` |
| Author + date | `post.author.name`, `post.published_at` |
| Reading time | computed from word count |
| ❤ likes count | `post.likes_count` |

Tap → **Blog Detail**.

### Blog Detail

- Hero image
- Title, author, date, read time, likes
- Body (markdown / sanitized HTML)
- ❤ Like button → `POST /blog/posts/{uuid}/like` (toggle)
- **Comments** section
  - Comments list: from `GET /blog/posts/{uuid}` (returned with post)
  - Composer at bottom: text → `POST /blog/posts/{uuid}/comments`
- "Related posts" carousel

**API:**
- `GET /blog/categories`
- `GET /blog/posts?category={id}&search={q}&per_page=15`
- `GET /blog/posts/{uuid}`
- `POST /blog/posts/{uuid}/like`
- `POST /blog/posts/{uuid}/comments`

---

## 15. Profile / Settings

**Purpose:** Account management.

### Sections

1. **Profile card**
   - Avatar (tap to change → uploads via multipart on `PUT /auth/profile`)
   - Name, email
   - **Edit Profile** button → form below

2. **Edit Profile form**
   - **Name** (text)
   - **Phone** (phone)
   - **Address** (text)
   - **City** (text)
   - **Latitude / Longitude** auto-set from "Use my location" button
   - **Save** → `PUT /auth/profile`

3. **Email & verification**
   - Email (read-only — change requires re-auth flow, future)
   - "Email not verified" banner with **Resend** → `POST /auth/email/resend`

4. **Security**
   - **Change Password** → form { current_password, new_password, new_password_confirmation } → `PUT /auth/change-password`

5. **Notifications**
   - Push notifications toggle (controls registration; OFF → call `POST /auth/device-token` with empty token, or have a `delete-device-token` future endpoint)
   - Email notifications toggle (UI only — backend pref to be added)

6. **My Pets** shortcut → **Pet Profiles**

7. **Subscription** shortcut → **Subscription Plans**

8. **Saved vets** (future) — bookmarked vets

9. **Reviews I've written** (future)

10. **Help & Support**
    - FAQ (static)
    - Contact us (mailto / chat)
    - About / Terms / Privacy

11. **Logout** → confirm → `POST /auth/logout` → clear stored token → **Login / Signup**.

12. **Delete account** (red, confirm dialog with text-match) → `DELETE /auth/account` → logout flow.

### App lifecycle hook

On every cold start with valid token AND a fresh FCM token from the platform SDK:
- `POST /auth/device-token` body `{ token: <fcm_token>, platform: 'ios'|'android'|'web' }`.

---

## Cross-cutting screens

### Notifications

- List from `GET /notifications`. Each: icon, title, body, time-ago, unread dot.
- Tap → action (e.g. open appointment / SOS / consult).
- **Mark all read** → `PUT /notifications/read-all`.

### SOS modal

Triggered from Home or Search Vets emergency icon.

| Field | Type |
|---|---|
| Pet selector | dropdown from `GET /pets` |
| Notes | textarea |
| Auto-detected location | shown read-only with "Update location" button |

**Send SOS** → `POST /sos` body `{ latitude, longitude, pet_id?, notes?, urgency? }`.

After send → **SOS Live Tracker** screen:
- Status pill (pending / assigned / in_progress / completed)
- Live map showing assigned vet's location (via `GET /sos/{uuid}` poll or Reverb `sos.{uuid}` channel)
- Cancel button (until assigned only)

### AI Chatbot (Pet Assistant)

Accessible from Home or Profile.
- Sessions list `GET /chatbot/sessions` + new session.
- Inside session: chat UI, send → `POST /chatbot/sessions/{uuid}/messages`, receive → AI reply rendered as bubble.

---

## Global state needs

| State | Storage |
|---|---|
| Auth token | SecureStore / Keychain |
| Current user | in-memory + persisted; refreshed via `GET /auth/me` |
| FCM token | platform SDK + sent on every cold start |
| Current location | platform location API + cached for 60 s |
| Pets list | cached + revalidated on focus |
| Pending appointments | cached + revalidated |

## Push notification deep links

| FCM `data.type` | Open screen |
|---|---|
| `appointment.confirmed` | My Appointments → detail |
| `appointment.reminder` | Consultation Room (if online) or Appointment Detail |
| `sos.assigned` | SOS Live Tracker |
| `consultation.matched` | Consultation Room |
| `payment.succeeded` | Appointment Detail |
| `pet.reminder.due` | Pet Records → Reminders tab |

## Empty / loading / error states

For every screen:
- **Loading**: skeleton placeholders (avoid spinners on lists).
- **Empty**: illustration + 1-line copy + primary CTA.
- **Error**: inline retry banner (non-blocking) + log to crash reporter.
- **Offline**: detect via NetInfo; banner "You're offline" + queued mutations show optimistic state.
