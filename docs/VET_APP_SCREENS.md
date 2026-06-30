# Vet App — Screen Specifications

Frontend build spec for the vet/clinic side. The vet app may ship as a separate mobile build OR as a role-routed flow inside the same RN app as the user side — the screens below are independent of that decision.

For request/response shapes see [API.md](API.md). For the underlying domain model see [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Conventions

- All screens require the **vet** role. Approved status (`vet_status === 'approved'`) is required for accepting work; pending vets see a limited shell.
- Auth header on every protected call: `Authorization: Bearer {token}`.

---

## 1. Login

**Purpose:** Authenticate, route based on vet status.

### Form

| Field | Type | Required |
|---|---|---|
| **Email** | email | yes |
| **Password** | password | yes |

Actions:
- **Sign In** → `POST /auth/login`. Inspect `data.user.role` — if not `vet`, log out and show "This account is not a vet account."
- The login response also returns `loginNotice` for non-approved vets — surface it in a banner.
- **Forgot password** → modal → `POST /auth/forgot-password`.
- **Apply as vet** link → public **Vet Application** screen (entry point for new vets, see below).

### Routing after login (by status)

- `pending` → **KYC Upload** (or **Application Status** if already submitted)
- `needs_information` → **KYC Upload** with admin-flagged fields highlighted
- `approved` → **Dashboard**
- `rejected` → blocked screen with rejection reason + "Apply again" link
- `suspended` → blocked screen with suspension reason + support contact

**API:** `POST /auth/login`

---

## 2. Vet Application (public, throttled 3/10min)

**Purpose:** First-time vet onboarding form.

### Sections

1. **Personal info**

| Field | Type | Required |
|---|---|---|
| **Full name** | text | yes |
| **Email** | email | yes |
| **Password** | password | yes |
| **Confirm password** | password | yes |
| **Phone number** | phone | yes |
| **Profile photo** | image picker | yes — uploaded via multipart |

2. **Clinic details**

| Field | Type | Required |
|---|---|---|
| **Clinic name** | text | yes |
| **Clinic address** | text | yes |
| **City** | text | opt |
| **State** | text | opt |
| **Postal code** | text | opt |
| **Latitude / Longitude** | hidden, set via "Pick location" map | yes |

3. **Professional info**

| Field | Type | Required |
|---|---|---|
| **License number** | text | yes |
| **Years of experience** | number | yes |
| **Qualifications** | text | opt |
| **Specialization** | text / chips | opt |
| **Languages** | chip multi-select | opt |
| **Accepted species** | chip multi-select (dog, cat, …) | yes (≥1) |
| **Services offered** | chip multi-select (general, emergency, vaccination, surgery, …) | yes (≥1) |

4. **Fees**

| Field | Type | Required |
|---|---|---|
| **Consultation fee** | number (₹) | opt |
| **Home visit fee** | number (₹) | opt |
| **Online fee** | number (₹) | opt |
| **Max home-visit km** | number | opt |

5. **Availability flags**

| Field | Type |
|---|---|
| **Emergency available** | toggle |
| **24 hour clinic** | toggle |

6. **Working hours** (per-day grid, opt) — open / close per weekday.

7. **Verification documents** (multi-file picker) — required:
   - License (PDF/JPG/PNG, max 5 MB)
   - Degree certificate
   - Government ID
   - Clinic registration (opt)

### Submit

- `POST /vet/apply` (multipart) — returns 201 with vet profile in `pending` state.
- Show "Application submitted — awaiting review" success screen with timeline:
  - ✅ Submitted
  - ⏳ Under review
  - 🟡 Approval / additional info request

**API:** `POST /api/v1/vet/apply`

---

## 3. KYC Upload

**Purpose:** Upload or re-upload verification documents (after admin requests more info).

### Sections

For each of the 4 document types, a card:

| Element | Behavior |
|---|---|
| Doc type label | "License", "Degree", "ID Proof", "Clinic Registration" |
| Status pill | Not uploaded / Pending / Approved / Rejected (per `vet_verification` history) |
| Preview / filename | shown if already uploaded |
| **Upload** / **Replace** button | opens file picker → `POST /vet/documents` body `{ document_type, document }` |
| **View** button | opens signed URL via `GET /vet/documents/{type}` |
| Admin notes | shown if `verification_status === 'needs_information'` and reason provided |

### Footer

- **Submit for review** button (only visible when at least license + degree + id_proof are uploaded) → `PUT /vet/status` (or implicit after first upload — admin sees pending queue).

**API:**
- `POST /vet/documents`
- `GET /vet/documents/{type}`

---

## 4. Dashboard

**Purpose:** At-a-glance view of today's workload.

### Top stats row (4 cards)

| Card | Value | Source |
|---|---|---|
| **Today's appointments** | count | `GET /appointments/vet?date=today&per_page=1` (use `pagination.total`) |
| **Pending requests** | count | filter `?status=requested` |
| **This week's earnings** | sum (₹) | derived from `GET /payments` (vet sees received) over week range |
| **Wallet balance** | ₹ | `GET /payments/wallet` → `wallet.balance` |

Tap any card → drills into the relevant screen.

### Today's schedule (timeline)

- List of appointments scheduled for today, sorted by `scheduled_at`.
- Each row:
  - Time block (e.g. 14:00–14:30)
  - Pet photo + name
  - Owner name
  - Type (clinic / home / online)
  - Status pill
  - Tap → **Appointment Detail**.
- "View all" → **Appointments** screen.

### Quick actions

- **Set Availability** → **Schedule Manager**
- **Add Visit Record** (for past appointment without record) → quick picker
- **View Earnings** → **Earnings**

### Notifications bell

- Top right; unread count from `GET /notifications/unread-count`.
- Tap → notifications list (same as user app).

### SOS feed (only if `is_emergency_available`)

- Sticky banner at top: "Active SOS in your area: {n}"
- Tap → SOS list (filterable from `GET /admin/sos` would be admin-only; for vets, surfaced via FCM push + a derived endpoint — the vet's notify-when-nearby flow).

**APIs on screen mount:**
- `GET /appointments/vet?per_page=10`
- `GET /payments/wallet`
- `GET /notifications/unread-count`

---

## 5. Appointments

**Purpose:** Inbox + lifecycle management.

### Tabs

- **Requests** (status=requested)
- **Today** (scheduled_at = today, status in accepted/in_progress)
- **Upcoming** (scheduled_at > today, status=accepted)
- **Past** (status in completed/cancelled/rejected)

### Each row

| Element | Source |
|---|---|
| Time | `scheduled_at` |
| Pet photo + name | `appointment.pet` |
| Owner name | `appointment.user.name` |
| Type | `appointment_type` |
| Reason snippet | `reason` |
| Status pill | `status` |

Right swipe (or tap → detail):
- For **requested**: **Accept** / **Reject** buttons
- For **accepted**: **Start visit** button
- For **in_progress**: **Complete** button + "Add notes"

### Appointment Detail

Sections:
- Patient (pet) summary card → tap → opens read-only **Pet Records (vet view)**
- Owner contact (name, phone, masked email) — call button
- Time, type, reason
- Status timeline
- **Visit Record** section:
  - If empty + status in_progress/completed: **+ Add Visit Record** button
  - If exists: shows notes, diagnosis, prescription, images
- **Payment** section:
  - Status (pending / paid / offline)
  - **Record offline payment** button (if not yet paid) → opens form

### Lifecycle actions

| Action | API |
|---|---|
| Accept | `PATCH /appointments/{uuid}/accept` |
| Reject | `PATCH /appointments/{uuid}/reject` body `{ reason? }` |
| Start | `PATCH /appointments/{uuid}/start` |
| Complete | `PATCH /appointments/{uuid}/complete` body `{ notes?, diagnosis?, prescription? }` |
| Reschedule | `POST /appointments/{uuid}/reschedule` body `{ new_scheduled_at }` |
| Cancel | `PATCH /appointments/{uuid}/cancel` |

### Offline payment form

| Field | Type | Required |
|---|---|---|
| **Amount received** | number (₹) | yes — must be ≥ 50% of expected fee |
| **Payment mode** | radio (cash / card / UPI) | opt (UI hint only) |
| **Notes** | text | opt |

Submit → `POST /payments/offline` body `{ payable_type: 'appointment', payable_uuid, amount }`.

**APIs:** `GET /appointments/vet`, `GET /appointments/{uuid}`, lifecycle PATCHes, `POST /payments/offline`.

---

## 6. Schedule Manager

**Purpose:** Manage weekly slots, holidays, current availability status.

### Top bar

- **Status toggle:** Available / Busy / Away — `PUT /vet/status`
- "Emergency mode" pill (read-only, set in profile)

### Weekly grid

7 columns (Mon–Sun) × time blocks. Each cell shows existing slots from `GET /vet/availabilities`.

- Tap empty cell → **Add Slot** modal
- Tap existing slot → **Edit Slot** modal

### Add/Edit Slot modal

| Field | Type | Required |
|---|---|---|
| **Day of week** | radio | yes |
| **Open time** | time picker | yes |
| **Close time** | time picker | yes — must be > open |
| **Emergency hours** | toggle | opt — flags this as after-hours emergency window |

Submit:
- New: `POST /vet/availabilities`
- Edit: `PUT /vet/availabilities/{id}`
- Delete: `DELETE /vet/availabilities/{id}` (with confirm)

### Holidays

Below grid — list of upcoming dates marked unavailable. Add via date picker (stored in `working_hours.holidays` JSON on vet profile via `PUT /vet/profile`).

### Bulk actions

- **Copy week** — clones current week's pattern to next week (UI helper, multiple POSTs).
- **Set 24/7** — replaces all slots with continuous availability (uses `is_24_hours` flag on profile).

**APIs:** `GET/POST/PUT/DELETE /vet/availabilities`, `PUT /vet/status`, `PUT /vet/profile`.

---

## 7. Consultation Room (Vet Side)

**Purpose:** Run video / audio / chat consult, write notes inline.

### Pre-join (instant consult only)

When the vet receives an instant-consult request via push or polling:

- Banner appears: "New consult request — {issue_category}"
- Modal:
  - Pet summary (species, age, allergies)
  - User name (masked phone)
  - Issue description
  - Modality icon
  - **Accept** (60s timer) → `POST /consultations/{uuid}/accept` → opens room
  - **Decline** → dismiss

### In-room layout (video/audio)

Top bar:
- Pet name (with mini info icon → quick info bottom sheet showing allergies, medications, last visit)
- User name
- **Timer**
- Connection pill

Main grid:
- User's video (large)
- Self-view (small, draggable)

Right-side dock (collapsible):
- **Pet info** card (read-only quick summary)
- **Pet records** quick link → `GET /pets/{petId}/medical-records` opens drawer
- **Visit notepad** — vet types as they consult; auto-saves draft locally

Bottom controls:
- 🎤 mute · 📷 camera · 🔊 speaker · ➕ chat · 🔚 end

### End consult flow

Tap **End** → modal:

| Field | Type | Required |
|---|---|---|
| **Vet notes** | textarea | yes |
| **Diagnosis** | textarea | opt |
| **Prescription** | textarea / template builder | opt |
| **Attach prescription PDF** | file picker | opt |

Submit → `POST /consultations/{uuid}/complete` body with the above. Routes back to **Appointments** with a "Consultation completed" toast.

### Connection failure

- After client-side retry fails → `POST /consultations/{uuid}/connection-failure`
- After 3 backend-counted failures → session auto-fails; vet sees "Consult auto-cancelled — refund issued."

**APIs:** `POST /consultations/{uuid}/accept`, `POST /consultations/{uuid}/join`, `POST /consultations/{uuid}/messages`, `POST /consultations/{uuid}/connection-failure`, `POST /consultations/{uuid}/complete`, `POST /consultations/{uuid}/cancel`.

---

## 8. Add Prescription / Visit Record

Reachable from:
- Appointment Detail → "Add Visit Record"
- Consultation Room → "End consult" modal
- Past appointments lacking a record

### Form

| Field | Type | Required |
|---|---|---|
| **Linked to** | radio (appointment / SOS) — pre-filled from caller | yes |
| **Diagnosis** | textarea | opt |
| **Notes** | textarea | yes |
| **Prescription** | structured rows: drug name, dosage, frequency, duration, notes — **Add row** button | opt |
| **Prescription PDF** | file picker | opt |
| **Attached images** | multi-file picker (wound photos, scans) | opt |
| **Follow-up reminder** | toggle + date — creates a `pet_reminders` row | opt |

Submit:
1. `POST /visit-records` body `{ payable_type, payable_uuid, notes, diagnosis }`
2. If prescription PDF attached → `POST /visit-records/{uuid}/prescription`
3. If images attached → `POST /visit-records/{uuid}/images`
4. If follow-up → `POST /pets/{petId}/reminders` (separately).

### Existing record edit

- `GET /visit-records/appointment/{uuid}` to load.
- `PUT /visit-records/{uuid}` to save edits.

### Prescription template builder (UX detail)

- Saved templates stored client-side (or server-side in a future endpoint).
- "Insert template" dropdown above the rows; pre-fills common combos (deworming, vaccinations).

---

## 9. Earnings

**Purpose:** Wallet, transactions, payout requests.

### Top summary

| Card | Value | Source |
|---|---|---|
| **Available balance** | ₹ | `wallet.balance` |
| **Pending payout** | ₹ | `wallet.pending_payout` |
| **Total earned** | ₹ | `wallet.total_earned` |
| **Total paid out** | ₹ | `wallet.total_paid_out` |

### Tabs

#### Tab 1 — Transactions

From `wallet.transactions` (last 50 returned with wallet) + paginated history (future endpoint).

| Column | Source |
|---|---|
| Date | `tx.created_at` |
| Type pill | `tx.type` (credit / refund_debit / payout_request / payout_completed) |
| Amount | `tx.amount` |
| Description | `tx.description` |
| Balance after | `tx.balance_after` |

Filter: type, date range.

#### Tab 2 — Payouts

- List of payout request history with statuses.
- **Request payout** primary button → form:

| Field | Type | Required |
|---|---|---|
| **Amount** | number (₹) | yes — ≤ available balance |
| **Payment method** | radio (UPI / bank transfer) | yes |
| **UPI ID** *or* **Account / IFSC** | text fields | yes |
| **Notes** | text | opt |

Submit → `POST /vet/wallet/payout-request` body `{ amount, payment_detail }`.

#### Tab 3 — Statements

- Monthly statement download (future PDF generation endpoint).
- Tax summary (future).

**APIs:**
- `GET /payments/wallet`
- `GET /payments` (own, role-aware = received as vet)
- `POST /vet/wallet/payout-request`

---

## 10. Profile

**Purpose:** Manage public vet profile + settings.

### Sections

1. **Public profile preview** card — same way users see them in **Vet Detail**.

2. **Edit Profile form** — same fields as **Vet Application** (name, clinic, fees, languages, services, accepted species, working hours, emergency flags) BUT excluding password and protected fields (`vet_status`, `verification_status`, `is_active`).

   Submit → `PUT /vet/profile` (multipart if photo).

3. **Documents** — `GET /vet/profile` returns `document_status`; show per-doc:
   - License, Degree, ID Proof, Clinic Registration — each with status pill + **Re-upload** button → `POST /vet/documents`.

4. **Reviews** — list of reviews received (`GET /reviews/vet/{ownUuid}`); vet can reply once: `PUT /reviews/{uuid}/reply` body `{ reply }`. Flag inappropriate reviews: `PUT /reviews/{uuid}/flag` body `{ reason }`.

5. **Account settings**
   - Change password → `PUT /auth/change-password`
   - Notifications toggles
   - Logout → `POST /auth/logout`
   - Delete account (with confirm) → `DELETE /auth/account`

**APIs:** `GET /vet/profile`, `PUT /vet/profile`, `POST /vet/documents`, `GET /reviews/vet/{uuid}`, `PUT /reviews/{uuid}/reply`, `PUT /auth/change-password`, `POST /auth/logout`.

---

## Cross-cutting

### Notifications (vet-side relevant types)

| FCM `data.type` | Open screen |
|---|---|
| `appointment.requested` | Appointments → Requests tab |
| `appointment.cancelled_by_user` | Appointment Detail |
| `consultation.requested` | Consult accept modal |
| `sos.nearby` | SOS detail |
| `payment.captured` | Earnings → transactions |
| `payout.processed` | Earnings → payouts |
| `vet.approved` | Dashboard with celebration banner |
| `vet.needs_information` | KYC Upload with admin reason |

### Pet Records (vet read-only access)

When viewing a patient mid-consult or from an appointment:
- Same data structure as user's **Pet Records** but read-only.
- Vet can also add to it via **Add Visit Record** flow.

### Empty / loading / error states

Same conventions as user app.

### Approval-gated UI

For `vet_status !== 'approved'`:
- Hide / grey out: **Appointments**, **Schedule Manager**, **Consultation Room**, **Earnings**, payout actions.
- Always-visible: Dashboard (in degraded mode showing only application status), KYC Upload, Profile (read-only of public fields), Logout.

### Vet status banner

Globally shown when `vet_status !== 'approved'`:
- `pending` (yellow): "Application under review."
- `needs_information` (orange): "Admin needs more info — see KYC Upload."
- `rejected` (red): "Application rejected. Tap for details."
- `suspended` (red): "Account suspended. Contact support."
