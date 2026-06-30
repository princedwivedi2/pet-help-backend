# Backend Endpoints by Role

Every API endpoint mapped to the role(s) that can call it. Use this as the contract between frontend role-routing and backend authorization.

For full request/response shapes see [API.md](API.md).

---

## Roles in the system

| Role | Description | DB column |
|---|---|---|
| **Public** | No auth required | — |
| **User** | Pet parent (default role on register) | `users.role = 'user'` |
| **Vet** | Veterinarian / clinic | `users.role = 'vet'` + `vet_profiles.vet_status` |
| **Admin** | Platform operator | `users.role = 'admin'` |

### Vet sub-states

A user with `role = 'vet'` has one of these statuses on their `VetProfile`:

| Status | Can do |
|---|---|
| `pending` | Login (with notice), KYC upload, view own profile, application status. Cannot accept work. |
| `needs_information` | Same as pending; UI shows the admin's reason. |
| `approved` | Full vet capabilities. |
| `rejected` | All tokens revoked on rejection. Cannot login. |
| `suspended` | All tokens revoked on suspension. Cannot login. |

### Middleware stack

| Middleware | Effect |
|---|---|
| `auth:sanctum` | Bearer token required |
| `verified` | User must have verified email |
| `role:user` | `users.role === 'user'` (rarely enforced — most user actions check ownership via policies) |
| `role:vet` | `users.role === 'vet'` (does NOT check approved status — service-layer guards do that) |
| `role:admin` | `users.role === 'admin'` |
| `throttle:N,M` | Rate limit |

---

## Legend

- ✅ allowed
- 🔒 owns-only (policy-checked, e.g. user can only access their own pet)
- 👤 self-only (e.g. only modify own profile)
- 🆕 created-by-self (vet can only act on appointments assigned to them)
- — disallowed

---

## 1. Public (no auth)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/health` | ✅ |
| GET | `/api/v1/health/detailed` | ⚠️ recommend gating to admin/IP allow-list in production |
| POST | `/api/v1/auth/register` | ✅ creates `role=user` |
| POST | `/api/v1/auth/login` | ✅ |
| POST | `/api/v1/auth/forgot-password` | ✅ throttled |
| POST | `/api/v1/auth/reset-password` | ✅ throttled |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | ✅ signed URL |
| POST | `/api/v1/auth/otp/send` | ✅ throttle 3/min |
| POST | `/api/v1/auth/otp/verify` | ✅ throttle 10/min |
| POST | `/api/v1/vet/apply` | ✅ throttle 3 / 10 min — creates `role=vet`, status `pending` |
| POST | `/api/v1/vet/register` | ✅ legacy alias to apply |
| GET | `/api/v1/emergency-categories` | ✅ |
| GET | `/api/v1/guides` | ✅ |
| GET | `/api/v1/guides/{id}` | ✅ |
| GET | `/api/v1/vets` | ✅ search + filters |
| GET | `/api/v1/vets/{uuid}` | ✅ |
| GET | `/api/v1/reviews/vet/{uuid}` | ✅ |
| GET | `/api/v1/subscription-plans` | ✅ |
| GET | `/api/v1/ad-banners` | ✅ |
| GET | `/api/v1/blog/categories` | ✅ |
| GET | `/api/v1/blog/posts` | ✅ |
| GET | `/api/v1/blog/posts/{uuid}` | ✅ |
| GET | `/api/v1/blog/tags` | ✅ |
| GET | `/api/v1/community/topics` | ✅ |
| GET | `/api/v1/community/posts` | ✅ |
| GET | `/api/v1/community/posts/{uuid}` | ✅ |
| GET | `/api/v1/community/posts/{uuid}/replies` | ✅ |
| POST | `/api/payments/webhook` | ✅ HMAC-verified by `RAZORPAY_WEBHOOK_SECRET` |

---

## 2. Authenticated common (User · Vet · Admin)

These are accessible to any authenticated + verified caller.

### Auth

| Method | Path | User | Vet | Admin | Notes |
|---|---|---|---|---|---|
| GET | `/api/v1/auth/me` | ✅ | ✅ | ✅ | Returns own user record |
| POST | `/api/v1/auth/logout` | ✅ | ✅ | ✅ | Revokes current token |
| POST | `/api/v1/auth/email/resend` | ✅ | ✅ | ✅ | throttle 3/min |
| PUT | `/api/v1/auth/change-password` | 👤 | 👤 | 👤 | Revokes other tokens |
| PUT | `/api/v1/auth/profile` | 👤 | 👤 | 👤 | role/email/email_verified_at not mass-assignable |
| DELETE | `/api/v1/auth/account` | 👤 | 👤 | 👤 | Soft-delete |
| POST | `/api/v1/auth/device-token` | ✅ | ✅ | ✅ | Upserts FCM token; reassigns if previously another user's |

### Notifications

| Method | Path | User | Vet | Admin |
|---|---|---|---|---|
| GET | `/api/v1/notifications` | 👤 | 👤 | 👤 |
| GET | `/api/v1/notifications/unread-count` | 👤 | 👤 | 👤 |
| PUT | `/api/v1/notifications/read-all` | 👤 | 👤 | 👤 |
| PUT | `/api/v1/notifications/{id}/read` | 🔒 | 🔒 | 🔒 |

### Chatbot (AI assistant)

| Method | Path | User | Vet | Admin |
|---|---|---|---|---|
| GET | `/api/v1/chatbot/sessions` | ✅ | ✅ | ✅ |
| POST | `/api/v1/chatbot/sessions` | ✅ | ✅ | ✅ |
| GET | `/api/v1/chatbot/sessions/{uuid}` | 🔒 | 🔒 | 🔒 |
| DELETE | `/api/v1/chatbot/sessions/{uuid}` | 🔒 | 🔒 | 🔒 |
| GET | `/api/v1/chatbot/sessions/{uuid}/messages` | 🔒 | 🔒 | 🔒 |
| POST | `/api/v1/chatbot/sessions/{uuid}/messages` | 🔒 | 🔒 | 🔒 throttle 20/min |

### Community participation

| Method | Path | User | Vet | Admin |
|---|---|---|---|---|
| POST | `/api/v1/community/posts` | ✅ | ✅ | ✅ |
| DELETE | `/api/v1/community/posts/{uuid}` | 🔒 own | 🔒 own | ✅ any (admin can delete) |
| POST | `/api/v1/community/posts/{uuid}/replies` | ✅ | ✅ | ✅ |
| DELETE | `/api/v1/community/replies/{uuid}` | 🔒 own | 🔒 own | ✅ any |
| POST | `/api/v1/community/votes` | ✅ | ✅ | ✅ |
| POST | `/api/v1/community/reports` | ✅ | ✅ | ✅ |

### Blog interaction

| Method | Path | User | Vet | Admin |
|---|---|---|---|---|
| POST | `/api/v1/blog/posts/{uuid}/comments` | ✅ | ✅ | ✅ (admin can also moderate via /admin/) |
| POST | `/api/v1/blog/posts/{uuid}/like` | ✅ | ✅ | ✅ |

---

## 3. User (`role=user`)

The pet-parent surface. Users access these in addition to the common endpoints above.

### Pet management (owns-only via PetPolicy)

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/pets` | 👤 own list |
| POST | `/api/v1/pets` | 👤 |
| GET / PUT / PATCH / DELETE | `/api/v1/pets/{pet}` | 🔒 |
| GET | `/api/v1/pets/{pet}/dashboard` | 🔒 |
| GET / POST | `/api/v1/pets/{pet}/notes` | 🔒 |
| GET / PUT / DELETE | `/api/v1/pets/{pet}/notes/{note}` | 🔒 |
| GET / POST | `/api/v1/pets/{pet}/reminders` | 🔒 |
| PUT / DELETE | `/api/v1/pets/{pet}/reminders/{reminder}` | 🔒 |
| POST | `/api/v1/pets/{pet}/reminders/{reminder}/complete` | 🔒 |
| GET / POST | `/api/v1/pets/{pet}/documents` | 🔒 (private storage) |
| PUT / DELETE | `/api/v1/pets/{pet}/documents/{document}` | 🔒 |
| GET | `/api/v1/pets/{pet}/documents/{document}/download` | 🔒 60-min signed URL |
| GET / POST | `/api/v1/pets/{pet}/medications` | 🔒 |
| PUT / DELETE | `/api/v1/pets/{pet}/medications/{medication}` | 🔒 |
| POST | `/api/v1/pets/{pet}/medications/{medication}/discontinue` | 🔒 |
| POST | `/api/v1/pets/{pet}/medications/{medication}/log` | 🔒 |
| GET / POST | `/api/v1/pets/{petId}/medical-records` | 🔒 |
| GET / PUT / DELETE | `/api/v1/pets/{petId}/medical-records/{uuid}` | 🔒 |
| GET | `/api/v1/pets/{petId}/appointments` | 🔒 |
| GET | `/api/v1/pets/{petId}/visit-records` | 🔒 |
| GET | `/api/v1/pets/{petId}/incidents` | 🔒 |

### SOS

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/sos` | 👤 creates own SOS |
| GET | `/api/v1/sos/active` | 👤 own active |
| PUT | `/api/v1/sos/{uuid}/status` | 🔒 owner can cancel; assigned vet can transition |
| PUT | `/api/v1/sos/{uuid}/location` | 🆕 assigned vet only |
| GET | `/api/v1/incidents` | 👤 own |
| GET | `/api/v1/incidents/{uuid}` | 🔒 |

### Appointments

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/appointments` | 👤 own |
| POST | `/api/v1/appointments` | 👤 books for own pets |
| GET | `/api/v1/appointments/slots/{vet_uuid}` | ✅ |
| GET | `/api/v1/appointments/{uuid}` | 🔒 owner OR assigned vet |
| PATCH | `/api/v1/appointments/{uuid}/cancel` | 🔒 either party |
| POST | `/api/v1/appointments/{uuid}/reschedule` | 🔒 owner |
| GET / POST / DELETE | `/api/v1/waitlist` & `/api/v1/waitlist/{uuid}` | 👤 own |

### Consultations

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/consultations` | 👤 own |
| POST | `/api/v1/consultations` | 👤 starts instant consult |
| GET | `/api/v1/consultations/{uuid}` | 🔒 participant |
| POST | `/api/v1/consultations/{uuid}/join` | 🔒 |
| POST | `/api/v1/consultations/{uuid}/connection-failure` | 🔒 |
| GET | `/api/v1/consultations/{uuid}/messages` | 🔒 |
| POST | `/api/v1/consultations/{uuid}/messages` | 🔒 |

### Payments

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/payments` | 👤 own (vet sees received) |
| POST | `/api/v1/payments/create-order` | 👤 own bookings |
| POST | `/api/v1/payments/verify` | 🔒 own payment |
| GET | `/api/v1/payments/{uuid}` | 🔒 |
| POST | `/api/v1/payments/{uuid}/refund` | 🔒 own (or admin) |

### Subscriptions

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/subscriptions` | 👤 |
| GET | `/api/v1/subscriptions/active` | 👤 own |

### Reviews

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/reviews` | 👤 only after a completed appointment |
| PUT | `/api/v1/reviews/{uuid}/flag` | ✅ any authenticated user |

---

## 4. Vet (`role=vet`)

Vets get most user surface (e.g. they can also have pets, leave reviews on other vets — though uncommon) PLUS vet-specific endpoints.

### Approval-gated

These require `vet_status === 'approved'`. Pending / needs_info vets see a friendly error.

### Profile + KYC

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/vet/profile` | 👤 own (works in any status) |
| PUT | `/api/v1/vet/profile` | 👤 (status fields not mass-assignable) |
| POST | `/api/v1/vet/profile` | 👤 alias |
| POST | `/api/v1/vet/documents` | 👤 (works in any status) |
| GET | `/api/v1/vet/documents/{type}` | 👤 |
| PUT | `/api/v1/vet/status` | 👤 sets availability flag |

### Schedule

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/vet/availabilities` | 👤 |
| POST | `/api/v1/vet/availabilities` | 👤 |
| PUT | `/api/v1/vet/availabilities/{id}` | 🔒 own |
| DELETE | `/api/v1/vet/availabilities/{id}` | 🔒 own |

### Appointments (vet view)

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/appointments/vet` | 👤 own inbox |
| PATCH | `/api/v1/appointments/{uuid}/accept` | 🆕 vet assigned to appointment |
| PATCH | `/api/v1/appointments/{uuid}/reject` | 🆕 |
| PATCH | `/api/v1/appointments/{uuid}/start` | 🆕 |
| PATCH | `/api/v1/appointments/{uuid}/complete` | 🆕 |
| PUT | `/api/v1/appointments/{uuid}/end-visit` | 🆕 |
| PUT | `/api/v1/appointments/{uuid}/status` | 🆕 |

### Consultations (vet side)

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/consultations/{uuid}/accept` | ✅ approved vet |
| POST | `/api/v1/consultations/{uuid}/complete` | 🆕 assigned vet |
| POST | `/api/v1/consultations/{uuid}/cancel` | 🆕 |

### Visit records

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/visit-records` | 🆕 vet assigned to the appointment/SOS |
| PUT | `/api/v1/visit-records/{uuid}` | 🆕 owner-vet |
| POST | `/api/v1/visit-records/{uuid}/prescription` | 🆕 |
| POST | `/api/v1/visit-records/{uuid}/images` | 🆕 |
| GET | `/api/v1/visit-records/appointment/{uuid}` | 🔒 participant |
| GET | `/api/v1/visit-records/sos/{uuid}` | 🔒 participant |

### Wallet + payouts

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/payments/wallet` | 👤 own |
| POST | `/api/v1/vet/wallet/payout-request` | 👤 |

### Offline payment recording (vet acts)

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/payments/offline` | 🆕 assigned vet OR admin (and amount validated against expected fee) |

### Reviews (vet replying)

| Method | Path | Access |
|---|---|---|
| PUT | `/api/v1/reviews/{uuid}/reply` | 🆕 vet who received the review |

---

## 5. Admin (`role=admin`)

All endpoints below are gated by `auth:sanctum + verified + role:admin`. URL prefix `/api/v1/admin`.

### Dashboard / metrics / audit

| Method | Path |
|---|---|
| GET | `/api/v1/admin/stats` |
| GET | `/api/v1/admin/metrics` |
| GET | `/api/v1/admin/metrics/time-series` |
| GET | `/api/v1/admin/metrics/geo` |
| GET | `/api/v1/admin/recent-activity` |
| GET | `/api/v1/admin/audit-logs` |

### Users + roles

| Method | Path |
|---|---|
| GET | `/api/v1/admin/users` |
| PUT | `/api/v1/admin/users/{id}/role` |

### Vet trust workflow

| Method | Path |
|---|---|
| GET | `/api/v1/admin/vets` |
| GET | `/api/v1/admin/vets/unverified` |
| GET | `/api/v1/admin/vets/{uuid}` |
| GET | `/api/v1/admin/vets/{uuid}/review` |
| GET | `/api/v1/admin/vets/{uuid}/documents/{type}` |
| GET | `/api/v1/admin/vets/{uuid}/history` |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/approve` |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/reject` |
| PUT       | `/api/v1/admin/vets/{uuid}/verify` |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/suspend` |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/reactivate` |
| PUT/PATCH | `/api/v1/admin/vets/{uuid}/request-info` |

### Operations oversight

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
| GET | `/api/v1/admin/revenue` |
| GET | `/api/v1/admin/payments` |
| GET | `/api/v1/admin/payouts/pending` |
| POST | `/api/v1/admin/payouts/{vet_uuid}/process` |

Admins can also call user-side payment refund: `POST /api/v1/payments/{uuid}/refund` (policy allows admin).

### Subscription plans CMS

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/subscription-plans` |
| PUT / DELETE | `/api/v1/admin/subscription-plans/{id}` |

### Ad banners CMS

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

### Community moderation

| Method | Path |
|---|---|
| POST | `/api/v1/admin/community/topics` |
| PUT | `/api/v1/admin/community/topics/{uuid}` |
| GET | `/api/v1/admin/community/posts` |
| PUT | `/api/v1/admin/community/posts/{uuid}/lock` |
| PUT | `/api/v1/admin/community/posts/{uuid}/toggle-visibility` |
| DELETE | `/api/v1/admin/community/posts/{uuid}` |
| DELETE | `/api/v1/admin/community/replies/{uuid}` |
| GET | `/api/v1/admin/community/reports` |
| PUT | `/api/v1/admin/community/reports/{uuid}` |

### Guides admin

| Method | Path |
|---|---|
| GET / POST | `/api/v1/admin/guides/categories` |
| PUT / DELETE | `/api/v1/admin/guides/categories/{id}` |
| GET / POST | `/api/v1/admin/guides` |
| PUT / DELETE | `/api/v1/admin/guides/{id}` |

---

## 6. Status × Endpoint matrix (vet sub-states)

| Endpoint | pending | needs_information | approved | rejected | suspended |
|---|---|---|---|---|---|
| Login | ✅ (with notice) | ✅ (with notice) | ✅ | — token revoked | — token revoked |
| `GET /vet/profile` | ✅ | ✅ | ✅ | — | — |
| `POST /vet/documents` | ✅ | ✅ | ✅ | — | — |
| `PUT /vet/profile` | ✅ | ✅ | ✅ | — | — |
| `POST /vet/availabilities` | — recommended UX | — | ✅ | — | — |
| `PATCH /appointments/{uuid}/accept` | — | — | ✅ | — | — |
| `POST /consultations/{uuid}/accept` | — | — | ✅ | — | — |
| `POST /vet/wallet/payout-request` | — | — | ✅ | — | — |

**Implementation notes:**
- Login response sets `loginNotice` for non-approved vets so the frontend can show a banner.
- Service-layer guards in `VetOnboardingService` block status transitions to invalid states (e.g. cannot approve a rejected vet).
- On reject / suspend, all the vet's Sanctum tokens are revoked (`$user->tokens()->delete()`), forcing re-login to be blocked.

---

## 7. Token revocation events

| Event | Effect |
|---|---|
| `POST /auth/logout` | Revokes the calling token only |
| `PUT /auth/change-password` | Revokes ALL OTHER tokens (current session keeps its token) |
| `POST /auth/reset-password` | Revokes ALL tokens |
| `DELETE /auth/account` | Revokes ALL tokens |
| Admin rejects / suspends vet | Revokes ALL the vet's tokens |
| (future) Admin disables user | Revokes ALL tokens |

Frontend should expect 401s and route to login on receipt.

---

## 8. Idempotency contracts

| Endpoint | Idempotency mechanism |
|---|---|
| `POST /payments/verify` | Returns existing record when `payment.isPaid()` |
| `POST /api/payments/webhook` | UNIQUE on `(provider, event_id)` in `webhook_events` table |
| `POST /auth/device-token` | Upsert by `token` (UNIQUE), reactivates + reassigns |
| `POST /auth/otp/send` | 60-second cooldown per `(identifier, channel, purpose)` |
| `POST /admin/vets/{uuid}/approve` | Throws if already approved |

---

## 9. Throttle table (cumulative summary)

| Bucket | Limit | Routes |
|---|---|---|
| Public auth | 5/min | register, login, forgot-password, reset-password |
| Email resend | 3/min | auth/email/resend |
| Vet apply | 3/10min | vet/apply, vet/register |
| OTP send | 3/min | auth/otp/send |
| OTP verify | 10/min | auth/otp/verify |
| Pet CRUD | 60/min | pets/* |
| Pet sub-resources | 30/min | pets/{pet}/notes/reminders/documents/medications |
| Appointments | 30/min | appointments/* |
| SOS | 10/min | sos/* |
| Payments | 20/min | payments/* |
| Reviews | 10/min | reviews POST/PUT |
| Chatbot send | 20/min | chatbot/sessions/{uuid}/messages POST |
| Community posts/replies | 30/min | community/posts, community/posts/{uuid}/replies |
| Community votes | 20/min | community/votes |
| Community reports | 10/min | community/reports |
| Blog comments/likes | 30/min | blog/posts/{uuid}/comments, blog/posts/{uuid}/like |
| Subscriptions | 20/min | subscriptions/* |
| Waitlist | 20/min | waitlist/* |

---

## 10. Frontend integration cheat sheet

### When to redirect on 401

Always — token is missing, expired, or revoked. Clear stored token, route to login.

### When to redirect on 403

- Logged in but role is wrong → "You don't have permission" page with link back to home of correct role.
- Vet but pending → show "Awaiting approval" view with KYC entry point.
- For mobile app, handle the `loginNotice` returned at login to render the right shell.

### Role-based bottom-nav (mobile)

| Role | Tabs |
|---|---|
| User | Home · Search · Pets · Appointments · Profile |
| Vet (approved) | Dashboard · Appointments · Schedule · Earnings · Profile |
| Vet (pending/needs_info) | Status · KYC Upload · Profile |
| Admin | (web panel only — see [ADMIN_PANEL_SCREENS.md](ADMIN_PANEL_SCREENS.md)) |

### Endpoint discovery

Two sources of truth in the repo:
- `routes/api_v1.php` — definitive route list (this doc reflects it).
- `php artisan route:list --json` — live introspection.
