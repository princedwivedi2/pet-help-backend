# Pet Help API Reference

**Base URL:** `https://api.example.com` (replace with your deploy URL).
**API version prefix:** `/api/v1`
**Auth scheme:** `Authorization: Bearer {sanctum_token}` (obtained from login/register)
**Content-Type:** `application/json` for JSON bodies, `multipart/form-data` for uploads
**Accept:** `application/json`

For high-level architecture see [OVERVIEW.md](OVERVIEW.md); for module-level technical detail see [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Conventions

### Response envelope

```json
{ "success": true,  "message": "OK", "data": { ... }, "errors": null }
{ "success": false, "message": "Validation failed", "data": null, "errors": { "field": ["..."] } }
```

### Status codes

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 401 | Missing/expired bearer token |
| 403 | Authenticated but not authorized (role / policy / state) |
| 404 | Not found |
| 409 | Conflict (duplicate license, etc.) |
| 422 | Validation failed (`errors` populated) |
| 429 | Rate limited |
| 503 | Service unavailable (e.g. Razorpay webhook secret missing) |

### Pagination

`?per_page=15` (max 50). Response payload includes:

```json
"pagination": {
  "current_page": 1,
  "last_page":    7,
  "per_page":     15,
  "total":        96
}
```

### Throttling

| Bucket | Limit |
|---|---|
| Auth (`register`, `login`, `forgot-password`, `reset-password`) | 5 / minute |
| Email resend | 3 / minute |
| Vet apply / register | 3 / 10 minutes |
| OTP send | 3 / minute |
| OTP verify | 10 / minute |
| Pet CRUD | 60 / minute |
| Pet management sub-resources | 30 / minute |
| Appointments | 30 / minute |
| Payments | 20 / minute |
| Reviews / chatbot send | 10 / minute |
| Community votes | 20 / minute |
| Community reports | 10 / minute |
| SOS | 10 / minute |

### File uploads

Multipart form-data. Vet documents max 5 MB (`pdf,jpg,jpeg,png`). Pet documents max 20 MB. Profile photos validated by URL whitelist (vet apply) or direct upload.

---

## 1. Auth

### Public

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/auth/register` | Register new user. Body: `{ name, email, password, password_confirmation, phone? }`. Returns user + token. |
| POST | `/api/v1/auth/login` | Body: `{ email, password }`. Returns user + token. Vets in `pending` state get a notice; `rejected`/`suspended` are blocked. |
| POST | `/api/v1/auth/forgot-password` | Body: `{ email }`. Sends password reset email. |
| POST | `/api/v1/auth/reset-password` | Body: `{ email, token, password, password_confirmation }`. |
| GET  | `/api/v1/auth/email/verify/{id}/{hash}` | Signed URL handler — clicked from verification email. |
| POST | `/api/v1/auth/otp/send` | Body: `{ identifier, channel?, purpose? }`. `channel` = `email` or `sms` (auto-detected from identifier). 60s cooldown. |
| POST | `/api/v1/auth/otp/verify` | Body: `{ identifier, code, channel?, purpose? }`. Code is 6 digits, max 5 attempts, 10-min TTL. |

### Authenticated

| Method | Path | Description |
|---|---|---|
| GET    | `/api/v1/auth/me` | Current user. |
| POST   | `/api/v1/auth/logout` | Revokes the current token. |
| POST   | `/api/v1/auth/email/resend` | Re-send verification email. |
| PUT    | `/api/v1/auth/change-password` | Body: `{ current_password, new_password, new_password_confirmation }`. Revokes other tokens. |
| PUT    | `/api/v1/auth/profile` | Body: subset of `{ name, phone, avatar, address, city, latitude, longitude }`. `role`/`email`/`email_verified_at`/`password` cannot be set. |
| DELETE | `/api/v1/auth/account` | Soft-delete the account. |
| POST   | `/api/v1/auth/device-token` | Body: `{ token, platform? }` where platform ∈ `ios|android|web|unknown`. Upserts a row in `device_tokens`; reactivates inactive rows; reassigns to current user if token previously belonged to someone else. |

---

## 2. Pets (authenticated)

### CRUD

| Method | Path | Notes |
|---|---|---|
| GET    | `/api/v1/pets` | List the user's pets. |
| POST   | `/api/v1/pets` | Body: `{ name, species, breed?, age?, weight?, allergies?, photo? }`. |
| GET    | `/api/v1/pets/{pet}` | Show one (UUID-routed). |
| PUT/PATCH | `/api/v1/pets/{pet}` | Update. |
| DELETE | `/api/v1/pets/{pet}` | Soft delete. |

### Pet sub-resources (`{pet}` is the pet UUID)

Dashboard:
- GET `/api/v1/pets/{pet}/dashboard` — aggregated overview

Notes:
- GET / POST `/api/v1/pets/{pet}/notes`
- GET / PUT / DELETE `/api/v1/pets/{pet}/notes/{note}`

Reminders:
- GET / POST `/api/v1/pets/{pet}/reminders`
- PUT / DELETE `/api/v1/pets/{pet}/reminders/{reminder}`
- POST `/api/v1/pets/{pet}/reminders/{reminder}/complete`

Documents (private storage):
- GET / POST `/api/v1/pets/{pet}/documents`
- PUT / DELETE `/api/v1/pets/{pet}/documents/{document}`
- GET `/api/v1/pets/{pet}/documents/{document}/download` — returns 60-min signed URL

Medications + adherence log:
- GET / POST `/api/v1/pets/{pet}/medications`
- PUT / DELETE `/api/v1/pets/{pet}/medications/{medication}`
- POST `/api/v1/pets/{pet}/medications/{medication}/discontinue`
- POST `/api/v1/pets/{pet}/medications/{medication}/log` — record a dose taken

Medical records (alternate path style — `{petId}` is the numeric ID):
- GET / POST `/api/v1/pets/{petId}/medical-records`
- GET / PUT / DELETE `/api/v1/pets/{petId}/medical-records/{uuid}`

Pet history:
- GET `/api/v1/pets/{petId}/appointments`
- GET `/api/v1/pets/{petId}/visit-records`
- GET `/api/v1/pets/{petId}/incidents`

---

## 3. Vets

### Public discovery

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/vets` | Search/list. Query: `lat`, `lng`, `radius_km`, `available_only`, `emergency_only`, `city`, `specialization`, `languages[]` (e.g. `?languages[]=en&languages[]=hi`), `min_rating`, `sort_by` (`distance` \| `rating`), `limit`. |
| GET | `/api/v1/vets/{uuid}` | Show one. |
| GET | `/api/v1/reviews/vet/{uuid}` | Public reviews for a vet. |

### Vet onboarding (no auth, throttled)

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/vet/apply` | Multi-part form. Required: `full_name, email, password, password_confirmation, clinic_name, clinic_address, latitude, longitude, license_number, years_of_experience, accepted_species[], services_offered[]`. Optional: `phone_number, profile_photo, qualifications, specialization, languages[], consultation_fee, home_visit_fee, working_hours, documents[], is_emergency_available, is_24_hours`. |
| POST | `/api/v1/vet/register` | Legacy (no document upload) — delegates to apply. |

### Vet-only (role:vet, verified)

| Method | Path | Notes |
|---|---|---|
| GET / PUT / POST | `/api/v1/vet/profile` | Read / update own profile. Status fields are not mass-assignable. |
| POST | `/api/v1/vet/documents` | Upload one document. Body: `{ document, document_type }` where type ∈ `license\|degree\|id_proof\|clinic_registration`. |
| GET  | `/api/v1/vet/documents/{type}` | Returns 60-min signed URL for own document. |
| PUT  | `/api/v1/vet/status` | Set availability (e.g. `available\|busy\|away`). |
| GET / POST | `/api/v1/vet/availabilities` | Manage weekly slots. |
| PUT / DELETE | `/api/v1/vet/availabilities/{id}` | Update / remove a slot. |
| POST | `/api/v1/vet/wallet/payout-request` | Body: `{ amount, payment_detail }`. |

---

## 4. Appointments (authenticated)

| Method | Path | Notes |
|---|---|---|
| GET   | `/api/v1/appointments` | User: own appointments. Vet: switch to `/appointments/vet`. |
| POST  | `/api/v1/appointments` | Body: `{ vet_uuid, pet_id, scheduled_at, appointment_type (clinic_visit\|home_visit\|online), reason }`. Past dates rejected; overlapping slots rejected. |
| GET   | `/api/v1/appointments/slots/{vet_uuid}` | Available slots for a vet on a given date. |
| GET   | `/api/v1/appointments/vet` | Vet inbox (role:vet). |
| GET   | `/api/v1/appointments/{uuid}` | Show one. |
| PATCH | `/api/v1/appointments/{uuid}/accept` | role:vet |
| PATCH | `/api/v1/appointments/{uuid}/reject` | role:vet |
| PATCH | `/api/v1/appointments/{uuid}/start` | role:vet |
| PATCH | `/api/v1/appointments/{uuid}/complete` | role:vet |
| PATCH | `/api/v1/appointments/{uuid}/cancel` | Either party. |
| PUT   | `/api/v1/appointments/{uuid}/status` | Generic status setter. |
| PUT   | `/api/v1/appointments/{uuid}/end-visit` | Marks the visit complete with notes. |
| POST  | `/api/v1/appointments/{uuid}/reschedule` | Body: `{ new_scheduled_at }`. |

### Waitlist

| Method | Path |
|---|---|
| GET | `/api/v1/waitlist` |
| POST | `/api/v1/waitlist` — Body: `{ vet_uuid, pet_id, preferred_at }` |
| DELETE | `/api/v1/waitlist/{uuid}` |

---

## 5. SOS (authenticated)

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/sos` | Body: `{ latitude, longitude, pet_id?, notes?, urgency? }`. Returns immediately; `DispatchSosNearbyVetsJob` fans out notifications. |
| GET  | `/api/v1/sos/active` | Caller's active SOS, or vets in their area (vet view). |
| PUT  | `/api/v1/sos/{uuid}/status` | Status transitions; only the owner or assigned vet may call. |
| PUT  | `/api/v1/sos/{uuid}/location` | Live location update — assigned vet only. |

### Incidents (read-only audit trail)

| Method | Path |
|---|---|
| GET | `/api/v1/incidents` |
| GET | `/api/v1/incidents/{uuid}` |

---

## 6. Online consultations (authenticated)

The full lifecycle for video / audio / chat consults — both **instant** (vet matched after issue selection) and **scheduled** (linked to an appointment).

| Method | Path | Notes |
|---|---|---|
| GET  | `/api/v1/consultations` | List own (or assigned-to-me, for vets). |
| POST | `/api/v1/consultations` | User starts an instant consult. Body: `{ modality (video\|audio\|chat), pet_id?, issue_category?, issue_description?, fee_amount?, payment_uuid? }`. `pet_id` is the numeric ID of a pet owned by the authenticated user. Returns the session + `available_vets` array. |
| GET  | `/api/v1/consultations/{uuid}` | Show. |
| POST | `/api/v1/consultations/{uuid}/accept` | Vet accepts. Creates the WebRTC room, arms 10-min no-show watchdog. |
| POST | `/api/v1/consultations/{uuid}/join` | Caller (user or assigned vet) gets `{ room_provider, room_id, token, role }`. Token is HMAC-signed and 1-hour TTL. When both parties have joined, status → `active`. |
| POST | `/api/v1/consultations/{uuid}/connection-failure` | Client reports a drop. ≥3 failures → session auto-fails with `auto_refund_triggered: true`. |
| POST | `/api/v1/consultations/{uuid}/complete` | Vet ends. Body: `{ vet_notes?, diagnosis?, prescription? }`. |
| POST | `/api/v1/consultations/{uuid}/cancel` | Vet cancels (counts as failure → refund). |
| GET  | `/api/v1/consultations/{uuid}/messages` | Chat history (DB source of truth). |
| POST | `/api/v1/consultations/{uuid}/messages` | Body: `{ body }`. Persists in MySQL, mirrors to Firebase for realtime fan-out. |

### WebRTC client integration

After `/join`, client receives `{ room_provider: "webrtc", room_id, token, ... }`. The signaling path is `/signaling/{room_id}` in Firebase Realtime DB. Use:
- Browser WebRTC `RTCPeerConnection` for media
- Firebase JS SDK `database().ref("/signaling/{room_id}")` for SDP + ICE exchange

If `room_metadata.fallback === true`, Firebase is unavailable; client must fall back to direct P2P (no ICE relay).

---

## 7. Payments

### Authenticated user actions

| Method | Path | Notes |
|---|---|---|
| GET  | `/api/v1/payments` | History (own; vets see payments received). |
| POST | `/api/v1/payments/create-order` | Body: `{ payable_type (appointment\|sos), payable_uuid, payment_model? (platform_fee\|full_payment) }`. Returns `{ payment, payment_uuid, razorpay_key }`. |
| POST | `/api/v1/payments/verify` | Body: `{ payment_uuid, razorpay_payment_id, razorpay_order_id, razorpay_signature }`. Verifies signature AND fetches `GET /v1/payments/{id}` from Razorpay to assert `status === captured`, amount, currency, order_id. |
| POST | `/api/v1/payments/offline` | Vet records cash. Body: `{ payable_type, payable_uuid, amount }`. Amount must be ≥50% of expected fee. |
| GET  | `/api/v1/payments/wallet` | Vet wallet + last 50 transactions. |
| GET  | `/api/v1/payments/{uuid}` | Show single. |
| POST | `/api/v1/payments/{uuid}/refund` | Body: `{ reason? }`. Calls Razorpay refund first; only marks DB on success. |

### Public webhook

| Method | Path | Notes |
|---|---|---|
| POST | `/api/payments/webhook` | Razorpay webhook. HMAC-verified via `X-Razorpay-Signature` header against `RAZORPAY_WEBHOOK_SECRET`. Idempotent — replays on the same `event_id` are silently ignored (UNIQUE constraint on `webhook_events`). |

---

## 8. Subscriptions

### Public

| Method | Path |
|---|---|
| GET | `/api/v1/subscription-plans` | Active plans only. |

### Authenticated

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/subscriptions` | Body: `{ plan_id }`. Creates a Razorpay order; client then calls `/payments/verify` with `payable_type: subscription`. |
| GET  | `/api/v1/subscriptions/active` | Caller's currently-active subscription. |

---

## 9. Reviews (authenticated)

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/reviews` | Body: `{ vet_uuid, appointment_uuid, rating (1-5), title?, comment? }`. Only valid after a completed appointment. |
| PUT  | `/api/v1/reviews/{uuid}/reply` | Vet replies. |
| PUT  | `/api/v1/reviews/{uuid}/flag` | Body: `{ reason }`. |

(Public read: `GET /api/v1/reviews/vet/{uuid}` — see §3.)

---

## 10. Visit records (authenticated)

For appointments + SOS — the vet's clinical note record.

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/visit-records` | Body: `{ payable_type, payable_uuid, notes?, diagnosis?, prescription? }`. |
| PUT  | `/api/v1/visit-records/{uuid}` | Update. |
| POST | `/api/v1/visit-records/{uuid}/prescription` | Upload prescription file. |
| POST | `/api/v1/visit-records/{uuid}/images` | Upload images (e.g. wound photos). |
| GET  | `/api/v1/visit-records/appointment/{uuid}` | Records linked to an appointment. |
| GET  | `/api/v1/visit-records/sos/{uuid}` | Records linked to an SOS. |

---

## 11. Content

### Guides (public)

| Method | Path |
|---|---|
| GET | `/api/v1/emergency-categories` |
| GET | `/api/v1/guides` |
| GET | `/api/v1/guides/{id}` |

### Blog

Public:

| Method | Path |
|---|---|
| GET | `/api/v1/blog/categories` |
| GET | `/api/v1/blog/posts` |
| GET | `/api/v1/blog/posts/{uuid}` |
| GET | `/api/v1/blog/tags` |

Authenticated:

| Method | Path |
|---|---|
| POST | `/api/v1/blog/posts/{uuid}/comments` |
| POST | `/api/v1/blog/posts/{uuid}/like` |

### Community

Public:

| Method | Path |
|---|---|
| GET | `/api/v1/community/topics` |
| GET | `/api/v1/community/posts` |
| GET | `/api/v1/community/posts/{uuid}` |
| GET | `/api/v1/community/posts/{uuid}/replies` |

Authenticated:

| Method | Path |
|---|---|
| POST   | `/api/v1/community/posts` |
| DELETE | `/api/v1/community/posts/{uuid}` (own only) |
| POST   | `/api/v1/community/posts/{uuid}/replies` |
| DELETE | `/api/v1/community/replies/{uuid}` (own only) |
| POST   | `/api/v1/community/votes` — Body: `{ type (post\|reply), target_uuid, value (1\|-1) }` |
| POST   | `/api/v1/community/reports` |

### Ad banners (public)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/ad-banners` | Query: `?position=...` for filtering. |

### Chatbot (authenticated)

| Method | Path |
|---|---|
| GET    | `/api/v1/chatbot/sessions` |
| POST   | `/api/v1/chatbot/sessions` |
| GET    | `/api/v1/chatbot/sessions/{uuid}` |
| DELETE | `/api/v1/chatbot/sessions/{uuid}` |
| GET    | `/api/v1/chatbot/sessions/{uuid}/messages` |
| POST   | `/api/v1/chatbot/sessions/{uuid}/messages` |

---

## 12. Notifications (authenticated)

| Method | Path |
|---|---|
| GET | `/api/v1/notifications` |
| GET | `/api/v1/notifications/unread-count` |
| PUT | `/api/v1/notifications/read-all` |
| PUT | `/api/v1/notifications/{id}/read` |

---

## 13. Admin (`role:admin`, all under `/api/v1/admin`)

### Dashboard / metrics

| Method | Path |
|---|---|
| GET | `/api/v1/admin/stats` |
| GET | `/api/v1/admin/metrics` |
| GET | `/api/v1/admin/metrics/time-series` |
| GET | `/api/v1/admin/metrics/geo` |
| GET | `/api/v1/admin/recent-activity` |

### Users + roles

| Method | Path |
|---|---|
| GET | `/api/v1/admin/users` |
| PUT | `/api/v1/admin/users/{id}/role` — Body: `{ role }` |

### Vet trust workflow

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/admin/vets` | All vets (filter by `?status=...`). |
| GET | `/api/v1/admin/vets/unverified` | |
| GET | `/api/v1/admin/vets/{uuid}` | |
| GET | `/api/v1/admin/vets/{uuid}/review` | Profile completeness + missing-doc check. |
| GET | `/api/v1/admin/vets/{uuid}/documents/{type}` | Signed URL for vet doc. |
| GET | `/api/v1/admin/vets/{uuid}/history` | Verification audit trail. |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/approve` | |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/reject` | Body: `{ reason }`. Revokes all vet tokens. |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/suspend` | Body: `{ reason }`. Revokes all vet tokens. |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/reactivate` | |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/request-info` | Body: `{ reason }`. |
| PUT       | `/api/v1/admin/vets/{uuid}/verify` | Confirms KYC docs received. |

### Operations

| Method | Path |
|---|---|
| GET | `/api/v1/admin/appointments` |
| GET | `/api/v1/admin/pets` |
| GET | `/api/v1/admin/sos` |
| GET | `/api/v1/admin/incidents` |
| GET | `/api/v1/admin/incidents/{uuid}` |

### Finance

| Method | Path |
|---|---|
| GET  | `/api/v1/admin/revenue` |
| GET  | `/api/v1/admin/payments` |
| GET  | `/api/v1/admin/payouts/pending` |
| POST | `/api/v1/admin/payouts/{vet_uuid}/process` — Body: `{ amount, notes? }` |

### Subscription plans

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/subscription-plans` |
| PUT / DELETE | `/api/v1/admin/subscription-plans/{id}` |

### Ad banners

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/ad-banners` |
| PUT / DELETE | `/api/v1/admin/ad-banners/{uuid}` |

### Reviews moderation

| Method | Path |
|---|---|
| GET | `/api/v1/admin/reviews/flagged` |
| PUT | `/api/v1/admin/reviews/{uuid}/resolve` |
| PUT | `/api/v1/admin/reviews/{uuid}/dismiss` |
| DELETE | `/api/v1/admin/reviews/{uuid}` |

### Blog admin

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/blog/categories` |
| PUT / DELETE | `/api/v1/admin/blog/categories/{uuid}` |
| GET / POST | `/api/v1/admin/blog/posts` |
| GET / PUT / DELETE | `/api/v1/admin/blog/posts/{uuid}` |
| PUT | `/api/v1/admin/blog/posts/{uuid}/toggle-publish` |
| GET | `/api/v1/admin/blog/posts/{uuid}/comments` |
| PUT | `/api/v1/admin/blog/comments/{uuid}/approve` |
| DELETE | `/api/v1/admin/blog/comments/{uuid}` |
| GET / POST | `/api/v1/admin/blog/tags` |

### Community admin

| Method | Path |
|---|---|
| POST | `/api/v1/admin/community/topics` |
| PUT  | `/api/v1/admin/community/topics/{uuid}` |
| GET  | `/api/v1/admin/community/posts` |
| PUT  | `/api/v1/admin/community/posts/{uuid}/lock` |
| PUT  | `/api/v1/admin/community/posts/{uuid}/toggle-visibility` |
| DELETE | `/api/v1/admin/community/posts/{uuid}` |
| DELETE | `/api/v1/admin/community/replies/{uuid}` |
| GET  | `/api/v1/admin/community/reports` |
| PUT  | `/api/v1/admin/community/reports/{uuid}` |

### Guides admin

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/guides/categories` |
| PUT / DELETE | `/api/v1/admin/guides/categories/{id}` |
| GET / POST | `/api/v1/admin/guides` |
| PUT / DELETE | `/api/v1/admin/guides/{id}` |

### Audit log

| Method | Path |
|---|---|
| GET | `/api/v1/admin/audit-logs` |

---

## 14. Health

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/health` | Lightweight readiness probe. |
| GET | `/api/v1/health/detailed` | Includes DB + cache status. Use behind admin/IP allow-list. |

---

## Idempotency notes

- **Payment verify** — `verifyPayment` is idempotent: if the row is already `paid`, returns the existing record without re-charging.
- **Razorpay webhook** — `(provider, event_id)` UNIQUE constraint on `webhook_events`. Replays return 200 without re-running side effects.
- **Subscription verify** — same pattern as payment verify.
- **Device token registration** — upsert by token; reactivates inactive rows; reassigns to current user.
- **OTP send** — 60-second cooldown per (identifier, channel, purpose); attempts to send sooner return 422.

---

## Appendix: route count

This document covers ~210 v1 routes plus the `/api/payments/webhook` and `/api/payments/*` legacy aliases. For a live route dump:

```bash
php artisan route:list --json | jq '.[] | select(.uri | startswith("api/v1/")) | "\(.method) \(.uri)"'
```
