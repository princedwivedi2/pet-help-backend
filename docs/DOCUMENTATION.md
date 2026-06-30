# Pet Help Backend — Technical Documentation

Module-by-module reference for the Laravel 12 API. For the high-level product picture see [OVERVIEW.md](OVERVIEW.md); for endpoint-by-endpoint reference see [API.md](API.md).

---

## Table of contents

1. [Setup & local development](#1-setup--local-development)
2. [Configuration & environment](#2-configuration--environment)
3. [Response envelope & errors](#3-response-envelope--errors)
4. [Authentication & authorization](#4-authentication--authorization)
5. [Modules](#5-modules)
   - 5.1 [Pet management](#51-pet-management)
   - 5.2 [Vet onboarding & verification](#52-vet-onboarding--verification)
   - 5.3 [Appointments](#53-appointments)
   - 5.4 [SOS](#54-sos)
   - 5.5 [Online consultations](#55-online-consultations)
   - 5.6 [Payments, wallet, subscriptions](#56-payments-wallet-subscriptions)
   - 5.7 [Reviews, blog, community, guides, chatbot](#57-reviews-blog-community-guides-chatbot)
   - 5.8 [Admin](#58-admin)
6. [Notifications & realtime](#6-notifications--realtime)
   - 6.1 [Multi-device FCM push](#61-multi-device-fcm-push)
   - 6.2 [Realtime chat fan-out (RTDB or Firestore)](#62-realtime-chat-fan-out-rtdb-or-firestore)
   - 6.3 [WebRTC signaling](#63-webrtc-signaling)
   - 6.4 [Reverb broadcasting](#64-reverb-broadcasting)
7. [Scheduler & queue](#7-scheduler--queue)
8. [Storage & private documents](#8-storage--private-documents)
9. [Testing](#9-testing)
10. [Deployment](#10-deployment)
11. [Operational tips](#11-operational-tips)

---

## 1. Setup & local development

```bash
# 1. Dependencies
composer install --no-interaction
# google/cloud-firestore is optional and requires ext-grpc on production hosts.
# If grpc isn't installed locally, add: --ignore-platform-req=ext-grpc

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Firebase service account (optional but recommended)
# Drop your service account JSON at storage/firebase/firebase_credential.json
# (default path; override via FIREBASE_CREDENTIALS env var).

# 4. Database
php artisan migrate --seed   # Demo accounts only seeded in local/testing env

# 5. Bootstrap an admin (production-safe; prompts for password)
php artisan admin:create --email=you@example.com

# 6. Run
php artisan serve                  # API at http://localhost:8000
php artisan queue:work             # Background jobs
php artisan reverb:start           # WebSocket server (only if using broadcast features)
php artisan schedule:work          # Local scheduler (production uses cron)
```

**Demo accounts (local only — gated by `app()->environment(['local','testing'])`):**

| Email | Password | Role |
|---|---|---|
| admin@petsathi.com | admin123 | admin |
| vet@petsathi.com | vet123 | vet |
| test@example.com | password | user |

These are NOT seeded in production — `php artisan db:seed` warns and skips. Use `admin:create` instead.

---

## 2. Configuration & environment

### 2.1 Core Laravel

| Key | Purpose |
|---|---|
| `APP_KEY` | Generated via `php artisan key:generate`. Must be set in production. |
| `APP_ENV` | `local`, `testing`, or `production`. Production triggers stricter Razorpay key validation. |
| `APP_DEBUG` | **Must be `false` in production** — leaks stack traces in error responses. |
| `APP_URL` | Used to build signed URLs (email verification, etc.). |

### 2.2 Database

| Key | Default | Notes |
|---|---|---|
| `DB_CONNECTION` | `sqlite` | Use `mysql` in production. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | — | Standard MySQL connection. |

### 2.3 Sanctum

`config/sanctum.php` reads `SANCTUM_STATEFUL_DOMAINS` for SPA cookie auth. Mobile clients use bearer tokens — they do not need stateful domains.

### 2.4 Razorpay

| Key | Required for | Notes |
|---|---|---|
| `RAZORPAY_KEY_ID` | Live payments | In production, **`rzp_test_*` keys are refused** at runtime. |
| `RAZORPAY_KEY_SECRET` | Live payments | |
| `RAZORPAY_WEBHOOK_SECRET` | Webhooks | Required — webhook handler returns 503 if missing. |
| `RAZORPAY_COMMISSION_RATE` | Optional | Default 15. |

If keys are absent (or `rzp_test_*` outside production), `PaymentService::isMockMode()` returns true and `createOrder()` returns mock data with a `_mock: true` flag. Verification skips the gateway-fetch reconciliation in mock mode.

### 2.5 Firebase

| Key | Purpose |
|---|---|
| `FIREBASE_CREDENTIALS` | Path (relative to project root) to service account JSON. Default: `storage/firebase/firebase_credential.json`. |
| `FIREBASE_DATABASE_URL` | Required for Realtime Database. |
| `FIREBASE_CHAT_BACKEND` | `realtime` (default) or `firestore`. Firestore needs `ext-grpc`. |
| `FCM_SERVER_KEY` | Legacy fallback only — Admin SDK is preferred. |

The `FirebaseFactory` binding in `AppServiceProvider` is **resilient**: if the credentials file is missing or invalid, the factory still instantiates without service-account binding, and downstream services (FCM dispatcher, WebRTC provider, chat broadcaster) catch the resulting errors and degrade gracefully (returns false / logs warning).

### 2.6 Mail

Standard Laravel mail config. For production, set `MAIL_MAILER` to `smtp`/`ses`/`postmark` and configure accordingly. OTP emails use the `emails.otp` Blade view.

### 2.7 Queue & cache

| Key | Default | Notes |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Redis recommended for production. Required tables: `jobs`, `failed_jobs`. |
| `CACHE_STORE` | `database` | |

### 2.8 CORS

`config/cors.php` reads `CORS_ALLOWED_ORIGINS` (comma-separated). For mobile-only deployments (bearer tokens), CORS is irrelevant.

---

## 3. Response envelope & errors

Every API response follows this shape:

```json
{
  "success": true,
  "message": "Resource retrieved",
  "data":    { /* payload */ },
  "errors":  null
}
```

On error:

```json
{
  "success": false,
  "message": "Validation failed",
  "data":    null,
  "errors":  { "email": ["The email field is required."] }
}
```

| Status | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden (role / policy) |
| 404 | Not found |
| 409 | Conflict (e.g. duplicate license number) |
| 422 | Validation failed |
| 429 | Rate limited |
| 503 | Service unavailable (e.g. webhook secret missing) |

Pagination payloads include:

```json
{
  "items": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 7,
    "per_page": 15,
    "total": 96
  }
}
```

---

## 4. Authentication & authorization

### Token issuance

`POST /api/v1/auth/register` and `POST /api/v1/auth/login` return a Sanctum bearer token in `data.token`. Subsequent calls send `Authorization: Bearer {token}`.

### Email verification

Users must verify their email before accessing protected routes (`verified` middleware on the main route group). The verification email contains a signed URL to `GET /api/v1/auth/email/verify/{id}/{hash}`.

### OTP

Phone or email OTP via `POST /api/v1/auth/otp/send` (3/min throttle) and `POST /api/v1/auth/otp/verify` (10/min throttle). Codes are 6 digits, hashed at rest, expire in 10 minutes, max 5 attempts before lockout, 60-second cooldown between sends. Currently OTP verification confirms identity but does NOT issue a Sanctum token — that's a follow-up.

### Roles

Users have one of: `user`, `vet`, `admin`. The `role` column is **not in `$fillable`** — assignment goes through `User::role = ...` + `save()` in the registration service or `forceFill()` in `admin:create`.

### Vet status

Pending vets can apply but cannot accept appointments / consults / SOS until admin approves them. The status fields (`vet_status`, `verification_status`, `is_active`) are **not mass-assignable** on `VetProfile` — admin-gated transitions in `VetOnboardingService` use `forceFill()->save()`.

### Authorization

Policies are registered in `AppServiceProvider::boot()`:
- `PetPolicy`, `SosPolicy`, `IncidentPolicy`, `VetProfilePolicy`, `AppointmentPolicy`, `BlogPostPolicy`, `BlogCommentPolicy`, `CommunityPostPolicy`, `CommunityReplyPolicy`.

Middleware:
- `auth:sanctum` — bearer token required
- `verified` — email verification required
- `role:vet`, `role:admin` — role gate

---

## 5. Modules

### 5.1 Pet management

| Concern | File |
|---|---|
| Controller | `app/Http/Controllers/Api/V1/PetController.php`, `PetManagementController.php` |
| Models | `Pet`, `PetNote`, `PetReminder`, `PetDocument`, `PetMedication`, `PetMedicationLog`, `PetMedicalRecord` |
| Policy | `PetPolicy` (owner-only access; vets get scoped read access via appointments/SOS) |

Pet documents are stored on the **private** disk (`storage/app/private`). Downloads return a 60-min signed temporary URL only after the policy authorizes the requester. Photos are on the public disk (small + non-sensitive).

Sub-resources under each pet:
- `notes/`, `reminders/`, `documents/`, `medications/`, `medical-records/`
- `dashboard` — aggregated overview

### 5.2 Vet onboarding & verification

| Concern | File |
|---|---|
| Controller | `app/Http/Controllers/Api/V1/VetOnboardingController.php` |
| Services | `VetOnboardingService`, `VetVerificationService`, `VetSearchService`, `VetProfileCompletionService` |
| Models | `VetProfile`, `VetVerification`, `VetAvailability` |

**Application flow:**
1. `POST /api/v1/vet/apply` — public, throttled 3/10min. Creates User (role=vet) + VetProfile (status pending) in a single transaction. Documents stored on private disk; transaction rollback cleans up orphan files.
2. Admin reviews via `GET /api/v1/admin/vets/...` endpoints.
3. Admin approves / rejects / requests-more-info / suspends / reactivates. Status transitions go through `VetOnboardingService::approveVet`/`rejectVet`/`suspendVet`/`reactivateVet` — all use `forceFill()->save()` because the status fields are protected.
4. Suspending or rejecting **revokes all Sanctum tokens** so the vet immediately loses API access.

**Search** (`VetSearchService`):
- `getNearbyVets(lat, lng, radius_km, ...)` — Haversine distance, MySQL/PostgreSQL use `ACOS`; SQLite (tests) uses a Manhattan approximation.
- `discoverApprovedVets(...)` — returns three buckets: `nearby_vets` (within radius), `city_vets` (LIKE city/address), `all_vets` (catch-all sorted by distance/rating).
- Filters: `emergency_only`, `available_only` (open right now per `VetAvailability`), `specialization`, `min_rating`, `languages` (JSON contains).

### 5.3 Appointments

| Concern | File |
|---|---|
| Controller | `app/Http/Controllers/Api/V1/AppointmentController.php` |
| Service | `AppointmentService` |
| Model | `Appointment` |
| Policy | `AppointmentPolicy` |

**Lifecycle:** `requested → accepted → in_progress → completed | cancelled | rejected | expired`.

Vet-only PATCH actions: `accept`, `reject`, `start`, `complete`. Both parties can `cancel`. Reschedule requires shifting the slot.

**Conflict prevention:** `AppointmentService` checks for duration-based overlap on the same vet before creating. Past-date booking is rejected.

**Stale appointment expiry:** scheduled task in `routes/console.php` runs every 15 minutes, marks long-untouched `requested`/`accepted` rows as `expired`, idempotent via `Cache::add` keys.

**Waitlist:** `POST /api/v1/waitlist` joins a vet's waitlist; scheduler fires `waitlist:notify` periodically.

### 5.4 SOS

| Concern | File |
|---|---|
| Controller | `SosController` |
| Service | `SosService` |
| Job | `DispatchSosNearbyVetsJob` |
| Notification | `SosAlertNotification` |
| Models | `SosRequest`, `IncidentLog` |
| Policy | `SosPolicy` |

User creates SOS with `lat`, `lng`, optional `pet_id`, optional `notes`. The controller responds quickly; nearby-vet discovery + per-vet notification dispatch happens in `DispatchSosNearbyVetsJob`.

**Status lifecycle:** `pending → assigned → in_progress → completed | cancelled | expired`. Live-location updates (`PUT /sos/{uuid}/location`) are gated to the assigned vet only. Incident logs trail the lifecycle for audit.

**Schedulers** (`routes/console.php`):
- `sos:escalate-pending` (every 2 min) — widens search radius if no vet has accepted within escalation window.
- `sos:expire-stale` (every 5 min) — terminates old unhandled SOS rows.

### 5.5 Online consultations

| Concern | File |
|---|---|
| Controller | `app/Http/Controllers/Api/V1/ConsultationController.php` |
| Service | `ConsultationService` |
| Provider interface | `App\Contracts\VideoProviderInterface` |
| Provider impl | `App\Services\Video\WebRtcProvider` |
| Signaling channel | `App\Contracts\SignalingChannel` → `FirebaseSignalingChannel` |
| Watchdog | `App\Jobs\ConsultationNoShowWatchdogJob` |
| Models | `ConsultationSession`, `ConsultationMessage` |

**Lifecycle:**
```
pending → matching → matched → joining → active → completed
                              ↓
                            cancelled / expired / failed
```

**Instant consult flow:**
1. `POST /api/v1/consultations` — user picks issue category + modality (`video` / `audio` / `chat`). Service returns a session in `matching` state and a list of available vets via `ConsultationService::listAvailableVets`.
2. `POST /api/v1/consultations/{uuid}/accept` (vet) — flips to `matched`, creates the WebRTC room, arms a 10-minute no-show watchdog.
3. `POST /api/v1/consultations/{uuid}/join` — issues a join token (HMAC-signed JWT-like) and provider room id. Both parties join → status `active`, `started_at` set.
4. `POST /api/v1/consultations/{uuid}/connection-failure` — client reports a failure. After 3 failures the session is auto-failed with refund flag.
5. `POST /api/v1/consultations/{uuid}/complete` (vet) — vet writes notes/diagnosis/prescription; session ends.

**Auto-refund triggers** (per spec):
- Vet no-show after 10 min → `expired` (`ConsultationNoShowWatchdogJob`, runs every minute).
- ≥3 connection failures → `failed`.
- Vet cancels → `failed` via `vetCancel`.
The watchdog calls `PaymentService::refund()` when `auto_refund_triggered` is set on the session.

**Video provider abstraction:**
The `VideoProviderInterface` has `createRoom`, `generateJoinToken`, `destroyRoom`, `name`. Default impl `WebRtcProvider` uses Firebase Realtime DB for SDP/ICE signaling. Other providers (Twilio, Daily, Agora, LiveKit) can be wired by implementing the interface and rebinding.

**Chat messages:**
- Persisted in `consultation_messages` table (source of truth)
- Broadcast to Firebase via the configured `ChatMessageBroadcaster` for realtime fan-out (see §6.2)

### 5.6 Payments, wallet, subscriptions

| Concern | File |
|---|---|
| Controller | `PaymentController`, `SubscriptionController` |
| Service | `PaymentService` |
| Models | `Payment`, `WebhookEvent`, `VetWallet`, `WalletTransaction`, `Subscription`, `SubscriptionPlan` |

**Razorpay verification (`PaymentService::verifyPayment`):**
1. HMAC signature check (`hash_hmac('sha256', $orderId.'|'.$paymentId, $secret)`).
2. **Gateway-side reconciliation** — calls Razorpay `GET /v1/payments/{id}` and asserts `status === 'captured'`, `amount`, `currency`, and `order_id` ALL match the local `Payment` row. Any mismatch marks the row `failed` and throws.
3. If both pass: row → `paid`, vet wallet credited (full-payment model), payable status synced (Appointment / SOSRequest).

**Production safety:**
- `assertNotTestKeysInProduction()` is called at every gateway-touching method. In `APP_ENV=production` with `rzp_test_*` keys, throws RuntimeException before any order is created.
- `isMockMode()` skips gateway calls in non-prod when keys are absent or test-mode.

**Webhook idempotency:**
`POST /api/payments/webhook` — HMAC verifies signature, then deduplicates by `(provider, event_id)` UNIQUE constraint on `webhook_events`. Replays return 200 without re-running side effects (so the vet wallet isn't double-credited on retries).

**Refunds:**
- `POST /api/v1/payments/{uuid}/refund` — calls Razorpay refund API first; on success, marks payment `refunded` / `partially_refunded` and debits vet wallet.
- Wallet debit uses `lockForUpdate()` to prevent concurrent over-refund. Deficit is logged + audited.

**Booking-token columns** (schema present, service methods pending):
`payments` table has `token_amount`, `balance_due`, `balance_collected_at` for the ₹49/₹99 + balance-at-clinic flow.

**Vet wallet:**
- Credited via `PaymentService::creditVetWallet` after a paid full-payment payment.
- Vet requests payout via `POST /api/v1/vet/wallet/payout-request`.
- Admin processes via `POST /api/v1/admin/payouts/{vet_uuid}/process`.

### 5.7 Reviews, blog, community, guides, chatbot

- **Reviews** (`ReviewController`) — only verified reviews (post-completion appointments) are visible. Vet can reply once. Users can flag with reason. Admin moderates flagged reviews.
- **Blog** (`BlogController`) — categories, posts, tags, comments (moderated), likes. Admin endpoints under `/admin/blog/*` for full CRUD.
- **Community** (`CommunityController`) — topics, posts, replies, votes (upvote/downvote), reports. Admin moderation queue.
- **Guides** (`GuideController`) — emergency pet-care articles by category.
- **Chatbot** (`ChatbotController`) — AI-powered pet-care Q&A sessions. OpenAI-backed (configurable model).

### 5.8 Admin

`AdminController` exposes:
- Stats / metrics (time-series, geo, recent activity)
- User management + role updates
- Vet verification workflow (approve/reject/suspend/reactivate/request-info)
- Appointments + pets oversight
- Revenue + payments + payout processing
- Subscription plan CRUD
- Ad banner CRUD
- Reviews moderation
- Blog/community/guides admin CRUD
- Audit log read

`AdminMetricsService` powers the dashboard endpoints with time-bucketed aggregates.

---

## 6. Notifications & realtime

### 6.1 Multi-device FCM push

**Schema:** `device_tokens(id, user_id, token UNIQUE, platform[ios|android|web|unknown], last_seen_at, is_active, timestamps)`. Users can have multiple active rows.

**Registration:** `POST /api/v1/auth/device-token` accepts `{ token, platform? }`. Upserts by token:
- Same token re-presented → reactivate + bump `last_seen_at`.
- Token previously belonging to another user → **reassign user_id** (FCM tokens are device-scoped; a phone shared between two accounts must not retain overlapping tokens).

**Dispatch** (`FcmNotificationDispatcher::sendPush`):
- Reads ALL active rows for the user.
- Sends one CloudMessage per token via `kreait/firebase-php` HTTP v1.
- Per-token `NotFound` → deactivates ONLY that row; other devices keep working.
- Returns true if at least one device received the push.
- Falls back to legacy `users.fcm_token` column when no `device_tokens` rows exist (transition period).

**Constructor pattern:** `__construct(Closure $messagingResolver)` — closure returns `?Kreait\Firebase\Contract\Messaging`. Tests pass `fn() => $mockMessaging`; production binding wraps `FirebaseFactory::createMessaging()` in try/catch returning null.

### 6.2 Realtime chat fan-out (RTDB or Firestore)

**Source of truth = MySQL.** The broadcaster is a fire-and-forget side-effect.

| Backend | When | Path layout |
|---|---|---|
| `realtime` (default) | Works everywhere kreait works | `/consultations/{sessionUuid}/messages/{messageId}` |
| `firestore` | Better querying / security rules / pagination — needs `ext-grpc` | `consultations/{uuid}/messages/{id}` (Firestore subcollection) |

Backend chosen by `FIREBASE_CHAT_BACKEND` env (default `realtime`). Both implementations share the `ChatMessageBroadcaster` interface and follow the same closure-resolver + null-safe pattern. `ConsultationService::postMessage` invokes the broadcaster after every persist.

**Files:**
- `app/Contracts/ChatMessageBroadcaster.php`
- `app/Services/Chat/FirebaseChatBroadcaster.php` (RTDB)
- `app/Services/Chat/FirestoreChatBroadcaster.php` (Firestore)
- `app/Services/Chat/GoogleFirestoreWriter.php` (thin wrapper around `FirestoreClient`)
- `app/Services/Chat/NullChatBroadcaster.php` (no-op default)

### 6.3 WebRTC signaling

`WebRtcProvider` uses Firebase Realtime DB for SDP offer/answer and ICE candidate exchange:

```
/signaling/room-{sessionUuid}
  ├── offers/      ← caller writes SDP offer
  ├── answers/     ← callee writes SDP answer
  ├── ice_candidates/
  └── status: active | closed
```

Clients:
1. `POST /consultations/{uuid}/join` to get a room id + signed token.
2. Subscribe via Firebase JS SDK to `/signaling/room-{uuid}`.
3. Use browser WebRTC (`RTCPeerConnection`) — media is P2P, only signaling traverses Firebase.

The provider takes `Closure(): ?SignalingChannel` (our own thin interface, not the kreait `Database` directly — that interface returns the final `Reference` class which Mockery refuses). When Firebase is unavailable, `createRoom` returns metadata with `fallback: true` so the client can still attempt P2P.

### 6.4 Reverb broadcasting

`config/broadcasting.php` defaults to Reverb (Laravel's own WebSocket server). `routes/channels.php` defines:
- `App.Models.User.{id}` — private user channel.
- `sos.{uuid}` — only the SOS owner and assigned vet may subscribe.

Reverb is independent of the Firebase realtime fan-out — it's used for Laravel-internal broadcast events. Run with `php artisan reverb:start`.

---

## 7. Scheduler & queue

### Scheduled tasks (`routes/console.php`)

| Task | Frequency | Purpose |
|---|---|---|
| `consultations:no-show-watchdog` | every minute | Auto-refund instant consults where vet matched but never joined within 10 min |
| `sos:escalate-pending` | every 2 min | Widen SOS search radius if no vet accepted |
| `sos:expire-stale` | every 5 min | Terminate unhandled SOS rows |
| `appointments:expire-stale` | every 15 min | Mark untouched appointments expired |
| `appointments:waitlist-notify` | every 5 min | Notify waitlisted users of opening slots |
| `pet:reminders` | hourly | Push pending pet reminders |
| `pet:medication-reminders` | every 30 min | Medication dosing reminders |
| `pet:document-expiry-alerts` | daily 09:00 | Expiring vaccination certs etc. |

All use `onOneServer()` + `withoutOverlapping()` — safe for multi-server deployments.

**Production cron:**
```
* * * * * cd /path/to/app && php artisan schedule:run >> /var/log/scheduler.log 2>&1
```

### Queue

Default driver: `database` (`jobs` + `failed_jobs` tables). Run worker with:

```bash
php artisan queue:work --tries=3 --timeout=90
```

For production reliability, supervise with systemd / supervisor / pm2.

Critical queued jobs:
- `App\Jobs\DispatchSosNearbyVetsJob` — fans out SOS notifications.
- `App\Jobs\ConsultationNoShowWatchdogJob` — runs from the scheduler every minute.

---

## 8. Storage & private documents

| Disk | Used for | Access |
|---|---|---|
| `local` | Vet KYC documents, pet documents | Authenticated controller endpoints stream signed temp URLs (60 min). |
| `public` | Pet photos, vet profile photos | Symlink at `public/storage` — `php artisan storage:link`. |

**Vet documents** (`license`, `degree`, `id_proof`, `clinic_registration`):
- Uploaded to `vet-documents/{uuid}.{ext}` on the private disk.
- Vet downloads via `GET /api/v1/vet/documents/{type}` (own only).
- Admin downloads via `GET /api/v1/admin/vets/{uuid}/documents/{type}`.
- Validation: `mimes:pdf,jpg,jpeg,png`, `max:5120` KB.

**Pet documents:**
- Owner uploads via `POST /api/v1/pets/{pet}/documents`.
- Owner downloads via `GET /api/v1/pets/{pet}/documents/{document}/download` — returns a signed URL after `PetPolicy::view` authorizes.
- Max 20 MB per file.

---

## 9. Testing

```bash
php -d memory_limit=2048M vendor/phpunit/phpunit/phpunit
# or:
php -d memory_limit=2048M artisan test
```

**Counts:** 453 tests, 1018 assertions, ~57s wall-clock.

| Suite | Examples |
|---|---|
| Auth + OTP + device tokens | `AuthFlowTest`, `AuthTest`, `OtpTest`, `DeviceTokenRegistrationTest` |
| Pet management | `PetCrudTest`, `PetMedicalRecordTest`, `PetManagementTest` |
| Vet onboarding + search | `VetOnboardingTest`, `VetSearchTest`, `VetVerificationTest`, `VetProfileLanguagesTest` |
| Appointments | `AppointmentFlowTest`, `AppointmentReliabilityTest` |
| SOS | `SosFlowTest` |
| Consultations | `ConsultationFoundationTest` |
| Payments | `PaymentFlowTest`, `AdminPayoutApprovalTest`, `ProductionBlockerFixesTest` |
| Realtime | `WebRtcProviderTest`, `FcmNotificationDispatcherTest`, `ChatMessageBroadcasterTest`, `FirestoreChatBroadcasterTest`, `ChatBackendSelectionTest` |
| Admin | `AdminMetricsTest`, `AdminController*Test` |

**Test DB:** in-memory SQLite (`phpunit.xml`). `RefreshDatabase` trait re-migrates between tests.

**Mocking strategy:** all kreait `final` classes (`Factory`, `Database`, `Messaging`, `Reference`, `FirestoreClient`) are wrapped behind our own thin interfaces (`SignalingChannel`, `FirestoreWriter`) or accessed via closure resolvers — tests pass `fn() => $mock` instead of trying to mock final classes.

---

## 10. Deployment

Minimum viable production deploy:

```bash
# 1. Pull code
git pull --ff-only

# 2. Install (production-tuned)
composer install --no-dev --optimize-autoloader

# 3. Migrate
php artisan migrate --force

# 4. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Storage symlink
php artisan storage:link

# 6. Bootstrap admin (first-time only)
php artisan admin:create --email=admin@yourdomain.com

# 7. Restart workers
sudo systemctl restart pet-help-queue pet-help-reverb
```

**Required services:**
- HTTP (PHP-FPM via nginx or Apache)
- Cron entry: `* * * * * cd /app && php artisan schedule:run >> /var/log/scheduler.log 2>&1`
- Queue worker (supervised): `php artisan queue:work --tries=3 --timeout=90`
- Reverb worker (only if using broadcast features): `php artisan reverb:start`

**Required environment:**
- `APP_KEY` set (don't regenerate after launch — invalidates all encrypted data)
- `APP_DEBUG=false`
- `APP_ENV=production`
- Database credentials
- `FIREBASE_CREDENTIALS` pointing at the service account JSON
- Razorpay live keys (or absent for offline-only mode)
- Mail config

**Optional but recommended:**
- Sentry / Bugsnag for error tracking
- Redis for queue + cache + session (replace `database` driver)
- HTTPS at the edge (Sanctum requires `secure_cookie` in production)

---

## 11. Operational tips

**Logs:** default to `storage/logs/laravel.log`. Set `LOG_CHANNEL=daily` and `LOG_DAYS=14` for rotation in production.

**Failed jobs:** inspect with `php artisan queue:failed`; retry with `php artisan queue:retry all`.

**Razorpay key rotation:** swap `RAZORPAY_KEY_ID` / `_SECRET` / `_WEBHOOK_SECRET` in `.env`, run `php artisan config:cache`. Existing webhook events keep their idempotency (different event_ids).

**Firebase outage:** consultation chat fan-out and FCM push will silently fail (logged). MySQL chat history + appointment lifecycle are unaffected. WebRTC consults degrade to "P2P-only" room metadata — clients can still attempt connection without ICE relay.

**Database backup:** any nightly `mysqldump` works; key tables: `users`, `pets`, `vet_profiles`, `appointments`, `payments`, `consultation_sessions`, `sos_requests`. The `webhook_events` table is recoverable from Razorpay.

**Migrations on deploy:** `php artisan migrate --force` is idempotent — most newer migrations check `Schema::hasColumn` / `Schema::hasTable` before mutating.

**Audit logs:** `audit_logs` table captures user-attributed model changes via `AuditService::log(...)`. Read via `GET /api/v1/admin/audit-logs`.
