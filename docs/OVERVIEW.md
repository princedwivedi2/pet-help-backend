# RESPAW / Pet Help — Project Overview

> **Find trusted care for your pet anytime, online or nearby.**

ReSpaw / Pet Help is a hybrid pet-care platform that combines local vet discovery, appointment booking, online video/audio/chat consultations, pet health records, payments, and educational content. This document is the high-level overview of the backend; see [DOCUMENTATION.md](DOCUMENTATION.md) for technical detail and [API.md](API.md) for the endpoint reference.

---

## What it is

A pet-care **operating system** with three roles:

| Role | What they do |
|---|---|
| **Pet Parent** | Manage pets, find vets, book appointments, run instant online consults, pay, get prescriptions |
| **Vet / Clinic** | KYC-onboard, set availability, accept bookings + instant consults, write notes, manage earnings |
| **Admin** | Approve vets, run operations + finance (payouts, refunds), publish content, view analytics |

Mobile/web frontends consume a single Laravel REST API. Real-time features (consultation video signaling, chat fan-out, push notifications) are powered by Firebase.

---

## Core flows

### A. Instant consultation (the headline feature)

```
Pet parent opens app
  → picks issue
  → list of available vets returned
  → pays (held / authorised)
  → vet accepts
  → both parties join the WebRTC room
  → consult ends → payment captured
  → if vet no-shows in 10 min OR ≥3 connection failures → AUTO REFUND
```

### B. Scheduled online booking

```
Search vet → view profile → pick online slot → pay → reminder → join at the booked time
```

### C. Offline clinic visit

```
Vet near me → view clinic → pick slot → pay (booking token OR full) → visit → vet marks completed
```

### D. SOS emergency

```
User triggers SOS with location
  → queued job notifies nearby emergency-available vets
  → assigned vet handles, pushes live location updates
  → escalator job widens radius if no vet accepts within timeout
```

### E. Vet onboarding

```
Vet signs up → uploads KYC documents → admin reviews → approve / reject / request more info
   → on approval, vet can accept appointments and online consults
```

---

## Feature map

### Pet Parent
- Auth: register / login / OTP send-and-verify / email verification / forgot+reset password
- Pet profiles (multiple pets) with breed, species, age, weight, allergies, notes
- Pet records: vaccinations, prescriptions, lab reports, consultation history, documents (private storage)
- Vet discovery: nearby search with distance, language filter, specialization, ratings
- Appointment booking: confirm, cancel, reschedule, waitlist
- Online consultations: instant + scheduled, video / audio / chat
- Payments: Razorpay online + offline cash + booking-token split
- Subscriptions
- Pet community + blog content
- AI chatbot for pet-care Q&A
- Push notifications (multi-device FCM)

### Vet
- KYC onboarding with private document upload
- Profile (specialization, languages, fees, availability)
- Schedule manager (slots, holidays, 24-hour mode, emergency mode)
- Inbound bookings + instant consult requests
- Visit records: notes, diagnosis, prescriptions, image uploads
- Wallet + payout requests + transaction history
- Reviews + reply

### Admin
- Trust: approve / reject / suspend / reactivate vets, request more info
- Operations: users, vets, appointments, SOS, incidents, refunds
- Finance: revenue dashboard, commission tracking, payouts, subscriptions
- Growth: ad banners, blog/community CMS, featured vets
- Analytics: time-series, geo distribution, recent activity, audit logs

---

## Architecture at a glance

```
┌──────────────────────────────────────────────────────────────────────┐
│                      Mobile + Web Clients                            │
│  (RN/Expo user+vet app, React admin dashboard, future web SPA)        │
└──────────┬─────────────────────────────────────────┬─────────────────┘
           │ Sanctum bearer token                    │ Firebase JS SDK
           │ (REST: /api/v1/...)                     │ (RTDB / FCM / Firestore)
           ▼                                         ▼
┌──────────────────────────────┐         ┌─────────────────────────────┐
│  Laravel 12 API              │         │  Firebase                   │
│   - Sanctum auth             │  push   │   - Realtime DB (signaling, │
│   - Service-layer            │ ──────▶ │     chat fan-out default)   │
│   - Form Requests            │         │   - Firestore (chat opt-in) │
│   - Policies                 │  msg    │   - FCM HTTP v1 (push)      │
│   - Scheduled jobs           │ ──────▶ │                             │
│   - Queue worker             │         └─────────────────────────────┘
│   - Reverb (own WebSocket)   │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐         ┌─────────────────────────────┐
│  MySQL (source of truth)     │         │  Razorpay                   │
│   - 80+ tables, all flows    │ ◀──────▶│   - Orders, captures,       │
│     (auth, pets, vets,       │ webhook │     refunds, subscriptions  │
│      payments, consults,     │         │     idempotent via          │
│      sos, reviews, content)  │         │     webhook_events table    │
└──────────────────────────────┘         └─────────────────────────────┘
```

**Why this shape:** MySQL is the source of truth for everything. Firebase is a *side-effect* for realtime fan-out and push — failures there never break business logic. WebRTC handles the actual video/audio media P2P; Firebase only carries SDP + ICE signaling. This makes the system testable, deployable without exotic dependencies, and resilient when Firebase has a bad day.

---

## Tech stack

| Layer | Choice |
|---|---|
| Runtime | PHP 8.2+ |
| Framework | Laravel 12 |
| Database | MySQL (production) · SQLite in-memory (tests) |
| Auth | Laravel Sanctum (API token + signed-URL email verification) |
| Queue | Database driver default; can swap to Redis |
| Cache / Sessions | Database (configurable) |
| Realtime broadcast | Laravel Reverb (own WebSocket — Pusher protocol) |
| Push | FCM HTTP v1 via `kreait/firebase-php` |
| Realtime chat fan-out | Firebase Realtime DB (default) or Cloud Firestore (opt-in) |
| Video | Browser-native WebRTC; Firebase Realtime DB for SDP/ICE signaling |
| Payments | Razorpay (orders, payments, refunds, webhooks, subscriptions) |
| File storage | Private disk for KYC + pet documents (signed temporary URLs) |
| Tests | PHPUnit 11 — 453 passing, 1018 assertions |

---

## Key engineering principles

1. **Source of truth lives in MySQL.** Firebase, Razorpay, and FCM are side-effects — failures there never block writes.
2. **Fail closed for money, fail open for fan-out.** Razorpay verification asserts gateway-side `status=captured` + amount + currency + order_id; webhook events deduplicated. Chat broadcasts and pushes log + return false on failure.
3. **Protected fields are not mass-assignable.** `vet_status`, `verification_status`, `is_active`, `email_verified_at`, `role` are out of `$fillable`; admin-gated paths use `forceFill()`.
4. **Closure-based DI for SDK testability.** `WebRtcProvider`, `FcmNotificationDispatcher`, and the chat broadcasters take `Closure(): ?T` resolvers so the kreait SDK's `final` classes don't bleed into tests.
5. **Multi-device aware.** FCM tokens live in their own `device_tokens` table; per-token failure deactivates only that row.
6. **Idempotency where it matters.** Razorpay webhooks (UNIQUE event_id), payment retries (return existing on isPaid), subscription verify, OTP send/verify.
7. **Queued + scheduled work.** SOS dispatch, no-show watchdog, appointment expiry, reminders, document-expiry alerts all run in the scheduler with `onOneServer()` + `withoutOverlapping()`.

---

## What's complete vs in-flight

| Area | Status |
|---|---|
| Auth (incl. OTP) | ✅ Complete |
| Pet records | ✅ Complete |
| Vet onboarding + verification | ✅ Complete |
| Vet search (incl. language filter) | ✅ Complete |
| Appointments lifecycle | ✅ Complete |
| SOS emergency | ✅ Complete |
| Online consultations (foundation) | ✅ Complete (WebRTC + Firebase signaling) |
| Booking-token split payment | 🚧 Schema present, service methods pending |
| Razorpay verify hardening | ✅ Complete (gateway-side reconciliation + webhook idempotency) |
| Multi-device FCM | ✅ Complete |
| Firebase chat fan-out (RTDB + Firestore) | ✅ Complete (config-driven) |
| Admin trust + ops | ✅ Complete |
| Subscriptions | ✅ Complete |
| Auto-refund (no-show watchdog) | ✅ Complete |
| Production deploy runbook | 📝 Pending |

---

## Repository layout (top-level)

```
pet-help-backend/
├── app/
│   ├── Console/Commands/        # admin:create + scheduled tasks
│   ├── Contracts/               # Interfaces (NotificationDispatcher,
│   │                            #   VideoProviderInterface, SignalingChannel,
│   │                            #   ChatMessageBroadcaster, FirestoreWriter, ...)
│   ├── Http/Controllers/Api/V1/ # All API endpoints
│   ├── Http/Requests/           # Form Request validation
│   ├── Jobs/                    # SOS dispatch, no-show watchdog
│   ├── Models/                  # Eloquent models (40+)
│   ├── Notifications/           # SOS, appointment status, vet approval, etc.
│   ├── Policies/                # Pet, Vet, Appointment, SOS, Blog, Community
│   ├── Providers/AppServiceProvider.php  # Container bindings
│   └── Services/                # Service layer (Auth, Sos, Appointment,
│                                #   Payment, Consultation, Vet*, Otp, etc.)
├── config/                      # services.php, sanctum.php, firebase.php, ...
├── database/
│   ├── factories/               # Test fixtures
│   ├── migrations/              # 80+ migrations
│   └── seeders/                 # Reference data + dev demo accounts
├── docs/                        # OVERVIEW.md (this), DOCUMENTATION.md, API.md
├── routes/
│   ├── api_v1.php               # All v1 API routes
│   ├── channels.php             # Reverb broadcast channel auth
│   └── console.php              # Scheduler tasks
├── storage/firebase/            # Service account JSON (private)
├── tests/                       # 453 tests, 1018 assertions
└── .env.example                 # Reference environment
```

---

## Where to go next

- For developers: read [DOCUMENTATION.md](DOCUMENTATION.md) — module-by-module technical reference, configuration, deployment.
- For mobile / frontend integration: read [API.md](API.md) — every endpoint with method, path, auth requirement, and notes.
- For onboarding: clone, run `composer install`, copy `.env.example` → `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan admin:create`, then `php artisan serve`.
