# RESPAW / Pet-Help — 10-Day Delivery Plan (User App + Vet App)

**Date:** 2026-06-14
**Goal:** Ship full, production-usable functionality for BOTH mobile apps on the existing Laravel backend.
**Scope:** Planning + analysis only. No feature implementation in this document.
**Notion-ready:** Section 2 tasks are atomic with title / app / priority / effort / dependencies / acceptance criteria — paste rows directly into a Notion board.

---

> **Revision note (2026-06-14):** Section 0–2 updated after a code-level read of the existing user app (`02/pet-help-user-app`). The user app already exists and is ~65% built — it is NOT being scaffolded. Payments, pets CRUD, and booking are further along than a structural scan suggested; Agora A/V and SOS remain the largest net-new work.

## 0. Current State Snapshot (what was found)

| Component | Path | State |
|---|---|---|
| **Backend (Laravel)** | `C:\wamp64\www\02\pet-help-backend` | **~90% complete.** Full REST API at `/api/v1` covering auth+OTP, pets & pet-management (notes/reminders/documents/medications), medical records, appointments (book/accept/reject/start/complete/cancel/reschedule/slots/waitlist), payments (Razorpay order/verify/wallet/refund/offline + webhook), reviews, visit records, notifications, blog, subscriptions, consultations (instant+scheduled, Agora join-token, chat, connection-failure auto-refund), SOS, incidents, vet onboarding (apply/register/profile/documents/availabilities/status/payout), and a full admin surface. |
| **Vet app** | `C:\wamp64\www\respaw-vet-app` | **~80% built.** Expo SDK 54, RN 0.81.5, TypeScript. Stack: `axios` + `zustand` + `@tanstack/react-query` + `react-hook-form` + `zod` + **`react-native-agora` 4.5.4**. Has auth, KYC onboarding (3 steps + review/rejected), dashboard, appointments, consultations (Agora video + chat room), pets + medical records, prescription creator, profile/settings, slot management, wallet/payout/transactions, notifications. Has Jest unit tests + Maestro e2e. Bare/prebuild flow (`expo run:android/ios`). |
| **User app** | `C:\wamp64\www\02\pet-help-user-app` | **~65% built — exists, do NOT scaffold.** Expo SDK 54, RN 0.81.5, TypeScript. Real `fetch`-based API client (`services/client.ts`) with SecureStore `authToken`, envelope unwrap, multipart support. **Genuinely solid engineering:** `utils/backendAdapters` normalizers, `Promise.allSettled` graceful loading, `React.memo`'d form inputs. Screens for Splash, Auth, Home, Search, Pets (full CRUD + image picker), records/prescriptions, Appointments (+detail), Booking (robust slot adapter), Payment (**working WebView Razorpay checkout**), Confirmation, Vet detail, Consultation (chat-only), Notifications, Blog, Subscription, Chat, Profile — most wired to real services. **Architecture diverges from vet app:** Context + plain services (no zustand/react-query), `fetch` not `axios`. |

### Critical user-app gaps vs. vet app (the real 10-day work)
*(Revised after reading the actual code — several items are smaller than first assumed.)*
1. **No real-time A/V** — `react-native-agora` is NOT installed (zero references); `ModalityPickerScreen` lets users pick video/audio but routes them into a **chat-only** `ConsultationRoom`, so those modes are dead-ends. Backend already issues Agora join tokens via `POST /consultations/{uuid}/join`. **Biggest single piece of work.**
2. **No SOS screen** — confirmed absent (only a `payable_type: 'sos'` enum exists). Backend `POST /sos`, `/sos/active`, status/location updates ready; no UI route. **Build from scratch.**
3. **Payments ~80% done, NOT from scratch** — `PaymentScreen` (229 lines) renders a Razorpay checkout via WebView and calls `createPaymentOrder` + `verifyPayment`. **Gap:** the WebView→RN `postMessage` bridge that auto-captures `razorpay_payment_id`/`signature` looks incomplete — `handleVerifyPayment` currently asks the user to *paste* them manually. Finish the bridge + E2E test. No native SDK required.
4. **Push scaffolded but inert** — `AuthProvider.registerDeviceTokenSilently()` + `services/auth.registerDeviceToken` exist and fire on login, but `expo-notifications` is not installed so no real push token is ever obtained. Add `expo-notifications` + real token + handlers.
5. **No user wallet screen** — backend `GET /payments/wallet` exists.
6. **Mock = intentional initial-state seeding** (Home seeds `vetHighlights`/`pets`/etc. then overwrites via `allSettled`) — not a "broken mock" bug, but Home should clear seeds on empty/loaded; Blog/Subscription/Chat still need their real-data paths confirmed.
7. **Architecture decision** — keep the existing fetch+Context stack (recommended for a 10-day window) rather than rewrite to mirror the vet app's zustand+react-query.
8. **Hardcoded LAN API base** (`http://10.253.208.41:8002/api/v1` in `services/client.ts`) — must be env-driven before any build.

### Architecture decision (locked for this plan)
- **Do NOT rewrite the user app to zustand+react-query.** Keep its `fetch`+Context+services stack. Only **mirror the vet app's `services/rtc/*` RTC provider abstraction** (`RtcProvider` interface → `AgoraRtcProvider`) so both apps share the same provider contract and can swap providers later. This saves ~2 days.

---

## 1. Functionality Matrix (both apps → backend endpoints)

Legend: ✅ done · 🟡 partial / needs wiring or hardening · 🔴 to build · ⛔ cut/defer for this 10-day window · **(gap)** = backend work needed

### 1A. USER app

| Feature | Status | Backend endpoint(s) | Notes |
|---|---|---|---|
| Splash / session restore | ✅ | `GET /auth/me` | Wired |
| Login / Register / OTP | 🟡 | `POST /auth/login`,`/register`,`/otp/send`,`/otp/verify`,`/forgot-password` | Login wired; verify OTP + forgot-password flows need finishing |
| Email-verified gating | 🟡 | `verified` middleware | Surface "verify email" banner + resend |
| Profile view/edit | 🟡 | `PUT /auth/profile`, `PUT /auth/change-password`, `DELETE /auth/account` | Edit + delete-account need wiring |
| Push device token | 🟡 | `POST /auth/device-token` | **Scaffolded but inert** — `AuthProvider.registerDeviceTokenSilently` fires on login but `expo-notifications` not installed; add lib + real token + handlers |
| Pets CRUD | ✅ | `apiResource pets` | Full CRUD with image picker + validation (`PetsScreen` 567 lines) |
| Pet dashboard | 🔴 | `GET /pets/{pet}/dashboard` | Not surfaced |
| Pet notes/reminders/documents/medications | 🔴/⛔ | `/pets/{pet}/notes|reminders|documents|medications` | Reminders+meds P1; notes/docs P2 |
| Medical records (view + add) | 🟡 | `GET/POST /pets/{petId}/medical-records` | View wired; add P1 |
| Prescriptions view | 🟡 | via visit-records / medical-records | Read-only |
| Find / search vets | 🟡 | `GET /vets`, `GET /vets/{uuid}`, `GET /reviews/vet/{uuid}` | Wire reviews into detail |
| Available slots | ✅ | `GET /appointments/slots/{vet_uuid}` | Robust slot-adapter in `BookingScreen` (handles bare-time + datetime) |
| Book appointment | ✅ | `POST /appointments` | Wired (`BookingScreen` 305 lines); verify E2E with payment |
| Appointment list/detail | ✅ | `GET /appointments`, `/{uuid}` | Wired |
| Cancel / reschedule | 🟡 | `PATCH /{uuid}/cancel`, `POST /{uuid}/reschedule` | Wire actions |
| Waitlist | ⛔ | `/waitlist` | Defer to V1.1 |
| **Payments (Razorpay)** | 🟡 | `POST /payments/create-order`,`/verify`, `GET /payments`, `/{uuid}` | **~80% done** — WebView checkout + verify wired in `PaymentScreen`; finish WebView→RN `postMessage` auto-capture of payment_id/signature (currently manual paste). No native SDK |
| Offline payment record | ⛔ | `POST /payments/offline` | Defer |
| Wallet (user) | 🔴 | `GET /payments/wallet` | Build screen |
| Refund status | 🟡 | `GET /payments/{uuid}` | Show in history |
| **Consultation — video/audio (Agora)** | 🔴 | `POST /consultations`, `/{uuid}/join`, `/accept`, `/complete`, `/connection-failure` | **Critical** — mirror vet RTC |
| Consultation — chat | 🟡 | `/{uuid}/messages` | Exists; integrate into A/V room |
| Modality picker + pre-call payment | 🟡 | `POST /consultations` + payment | Wire payment gate |
| **SOS / emergency flow** | 🔴 | `POST /sos`, `GET /sos/active`, `PUT /{uuid}/status|location` | **Critical** — build screen + location + live status |
| Reviews (write/reply/flag) | 🔴 | `POST /reviews`, `PUT /{uuid}/reply|flag` | Post-appointment review |
| Notifications list | ✅ | `GET /notifications`, `/unread-count`, `/read-all`, `/{id}/read` | Wired |
| Blog read + like/comment | 🟡 | `GET /blog/*`, `POST .../comments`,`/like` | Remove mock fallback |
| Subscriptions | 🟡 | `GET /subscription-plans`, `POST /subscriptions`, `/active` | Remove mock; wire purchase |
| Chatbot / AI assistant | 🟡/⛔ | `/chatbot/sessions/*` | Basic wire P2 |
| Ad banners | 🟡 | `GET /ad-banners` | Home carousel |
| Community | ⛔ | `/community/*` | Backend marks "not consumed by mobile V1" — defer |
| Guides / emergency categories | ⛔ | `GET /guides`, `/emergency-categories` | "Not consumed by V1" — defer |

### 1B. VET app

| Feature | Status | Backend endpoint(s) | Notes |
|---|---|---|---|
| Splash / session restore | ✅ | `GET /auth/me` | Done |
| Login / forgot password | ✅ | `POST /auth/login`, `/forgot-password` | Done |
| Vet apply / register | ✅ | `POST /vet/apply`, `/vet/register` | Done |
| KYC onboarding (3 steps + review/rejected) | ✅ | `POST /vet/documents`, `PUT /vet/profile`, `PUT /vet/status` | Done; verify reject→resubmit loop |
| Dashboard | ✅ | `GET /appointments/vet`, metrics | Done; verify live data |
| Appointments list/detail | ✅ | `GET /appointments/vet`, `/{uuid}` | Done |
| Accept/reject/start/complete | ✅ | `PATCH /{uuid}/accept|reject|start|complete` | Done; smoke test |
| End visit + visit record | 🟡 | `PUT /{uuid}/end-visit`, `POST /visit-records` + images/prescription | Verify upload paths |
| Prescription creator | ✅ | `POST /visit-records/{uuid}/prescription` | Done |
| Consultation room (Agora video+chat) | ✅ | `/consultations/{uuid}/join|accept|complete|messages` | Done; needs creds + device QA |
| Consultations list | ✅ | `GET /consultations` | Done |
| Pets + medical records (view) | ✅ | `GET /pets/{petId}/medical-records` | Read context for visit |
| Profile / edit / settings / change pw | ✅ | `PUT /vet/profile`, `PUT /auth/change-password` | Done |
| Upload documents | ✅ | `POST /vet/documents` | Done |
| Slot management / editor | ✅ | `GET/POST/PUT/DELETE /vet/availabilities` | Done; timezone QA |
| Wallet / transactions / payout request | ✅ | `GET /payments/wallet`, `POST /vet/wallet/payout-request` | Done; verify payout states |
| Notifications | ✅ | `GET /notifications/*` | Done |
| Push device token | 🔴 | `POST /auth/device-token` | Verify registered + handlers |
| SOS responder view | 🔴 | `GET /sos/active`, `PUT /{uuid}/status` | Vet-side accept/respond — confirm if in scope |
| Reviews (reply to user) | 🟡 | `PUT /reviews/{uuid}/reply` | Wire reply |

### 1C. Backend gaps to verify/close (cross-cutting)

| Gap | Priority | Endpoint / area | Action |
|---|---|---|---|
| **Push delivery (FCM/APNs send)** | P0 | device-token stored — confirm a sender job actually pushes on appointment/consult/SOS events | Verify `NotificationController` + queued notifications fire to FCM |
| **Real-time signaling for consult/SOS** | P0 | `routes/channels.php` (broadcast) vs polling | Confirm whether incoming-call / SOS uses websockets/Pusher or client polling; both apps must match |
| **Agora token TTL / renewal** | P1 | `/consultations/{uuid}/join` | Confirm token expiry + renew-on-expire path |
| Razorpay keys + webhook secret | P0 | `/payments/webhook` (HMAC) | Confirm test+live keys present in `.env` |
| Consultation→payment gating | ✅ | join() checks `payment_status==paid` | Already enforced |

---

## 2. The 10-Day Plan (daily milestones → atomic tasks)

**Working assumptions:** 1 dev-day ≈ 6 focused hours. Effort: S ≤2h, M ≈ half day, L ≈ full day. Backend is treated as stable; backend tasks are verify/patch only. Two parallel tracks where possible (user-app heavy track + vet-app hardening track).

**P0 = must ship · P1 = should ship · P2 = nice-to-have**

---

### DAY 1 — Foundations, env, RTC abstraction, parity audit

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Confirm backend `.env`: Razorpay test+live keys, Agora App ID/cert, FCM creds; run `php artisan route:list` smoke | backend | P0 | M | — | All three credential sets present; `/health/detailed` green |
| Verify push-delivery path (device-token → queued FCM send on a test event) | backend | P0 | M | — | Sending a test notification reaches a real device token |
| Decide & document signaling model (websocket vs polling) for consult/SOS | backend | P0 | S | — | Decision recorded; both apps' tasks reference it |
| Lock user-app architecture decision (keep fetch+Context; mirror only RTC) | user | P0 | S | — | ADR note committed in `pet-help-user-app/docs` |
| Port vet app's `services/rtc/*` (RtcProvider interface + AgoraRtcProvider + useRtcSession) into user app | user | P0 | M | arch decision | Files compile; `tsc --noEmit` clean |
| Install `react-native-agora@^4.5.4` + run `expo prebuild` for user app | user | P0 | M | RTC port | Android+iOS native projects generated, app boots |
| Vet app: real-device build smoke (`expo run:android`) | vet | P0 | M | — | App launches on device, login works |

### DAY 2 — Finish user payments (WebView checkout already ~80% done)

> Note: payments are NOT a from-scratch build — `PaymentScreen` already has a working WebView Razorpay checkout + `verify` call. This day finishes the auto-capture bridge and hardens the flow, freeing ~half a day reallocated to Agora (Day 3) and SOS (Day 4).

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Finish WebView→RN `postMessage` bridge: auto-capture `razorpay_payment_id` + `signature`, auto-call verify | user | P0 | M | — | Successful checkout auto-verifies; no manual paste of id/signature |
| Replace hardcoded LAN API base with env/config (dev + prod URLs) | user | P0 | S | — | API base read from config; no `10.253.208.41` literal |
| Harden booking → payment → confirmation flow (failure/cancel paths) | user | P0 | M | bridge | Paid → Confirmation; cancelled/failed returns to a clean state |
| Payment history + refund status screen | user | P1 | M | payments svc | List from `GET /payments`; detail shows refund state |
| Verify webhook reconciliation (paid status flips server-side) | backend | P0 | S | keys | Webhook hit updates payment row |
| Vet: verify wallet balance reflects completed paid appointment | vet | P1 | S | payment | Completed paid appt increments vet wallet |

### DAY 3 — User consultation: Agora video/audio room

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Build A/V ConsultationRoom (mirror vet room) using ported RTC provider | user | P0 | L | D1 RTC | Local+remote video render; mute/camera/end controls |
| Wire `POST /consultations` (modality) + pre-call payment gate | user | P0 | M | D2 payment | Paid consult creates session; unpaid blocked |
| Wire `/{uuid}/join` token → joinChannel; integrate existing chat into room | user | P0 | M | room | Join issues token; chat + video coexist |
| Handle `connection-failure` reporting + auto-refund UX | user | P1 | S | room | Dropped call reports; user sees refund-pending state |
| **Cross-app live call test: user ↔ vet on two devices** | both | P0 | M | user+vet rooms | Two-party audio+video call connects end-to-end |
| Audio-only modality path | user | P1 | S | room | Audio call works without video track |

### DAY 4 — User SOS / emergency flow

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Add `expo-location` + permissions to user app | user | P0 | S | — | Foreground location obtained |
| Build SOS screen + route (trigger, category, pet, live status) | user | P0 | L | location | `POST /sos` creates request; shows active status |
| Live status polling/subscription per Day-1 signaling decision | user | P0 | M | signaling | Status changes (accepted/en-route/resolved) update UI |
| `PUT /sos/{uuid}/location` periodic updates while active | user | P1 | S | SOS screen | Location updates posted during active SOS |
| Vet SOS responder view (active list + accept/update status) — confirm scope | vet | P1 | M | signaling | Vet sees active SOS, can update status; reflects to user |
| Push alert on SOS create/assign | backend | P0 | S | D1 push | SOS event triggers push to relevant party |

### DAY 5 — User push notifications + auth polish + pets/medical

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Install `expo-notifications`, obtain real push token, complete the existing `registerDeviceTokenSilently` path + foreground/background handlers | user | P0 | M | D1 push | Real token (not auth-token placeholder) posted to `/auth/device-token`; taps deep-link |
| Finish OTP verify + forgot/reset password flows | user | P1 | M | — | OTP + reset complete against backend |
| Email-verified banner + resend | user | P1 | S | — | Unverified users prompted; resend throttled |
| Profile edit / change password / delete account wired | user | P1 | M | — | All three persist server-side |
| Medical records: add record + view; pet reminders & medications | user | P1 | M | pets svc | Create medical record + reminder; meds list |
| Vet push token registration verified | vet | P0 | S | D1 push | Vet receives appointment/consult push |

### DAY 6 — Reviews, subscriptions, blog, wallet, remove mocks

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Post-appointment review (write) + show in vet detail | user | P1 | M | reviews svc | `POST /reviews` works; appears under vet |
| User wallet screen | user | P2 | M | wallet svc | `GET /payments/wallet` rendered |
| Remove mock fallbacks (Blog, Subscription, Chat) → real data | user | P1 | M | svcs | No `data/mock` imports in those screens |
| Subscriptions: plans + purchase + active state | user | P2 | M | sub svc | Purchase flow completes; active plan shown |
| Ad banner carousel on Home | user | P2 | S | banners svc | Active banners from `/ad-banners` shown |
| Vet: reply to review | vet | P2 | S | reviews | `PUT /reviews/{uuid}/reply` works |

### DAY 7 — Vet app hardening + visit-record/prescription E2E

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Verify full appointment lifecycle on device (accept→start→complete→end-visit) | vet | P0 | M | — | All transitions succeed against backend |
| Visit record + image + prescription upload E2E | vet | P0 | M | — | Record saved; images+prescription upload + visible to user |
| Slot management timezone + edge QA | vet | P1 | M | — | Slots created/edited render correctly user-side |
| KYC reject→resubmit loop verified | vet | P1 | S | — | Rejected vet can resubmit docs |
| Payout request full state machine | vet | P1 | S | — | Request → pending → admin-processed reflects in app |
| User sees vet-issued prescription/visit record | user | P0 | S | vet upload | Prescription appears in user app records |

### DAY 8 — Integration, edge cases, error/empty/offline states

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Global error handling: 401 sign-out, 422 field errors, 429/503 backoff (both apps) | both | P0 | M | — | Consistent handling verified across key screens |
| Empty + loading + offline states on all list screens | both | P1 | M | — | No blank/crash on empty/no-network |
| Deep-link from push → correct screen (appt, consult, SOS) | both | P1 | M | D5 push | Tapping push opens correct detail |
| Token/session expiry + refresh behavior | both | P0 | S | — | Expired token routes to login cleanly |
| Full booking→pay→consult→prescription happy-path run (user+vet) | both | P0 | L | D2–7 | End-to-end journey passes on real devices |

### DAY 9 — QA pass, accessibility, performance, store prep

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Regression QA matrix (P0 flows, both apps, Android+iOS) | both | P0 | L | D8 | All P0 flows pass; bugs logged/triaged |
| Fix P0/P1 bugs from QA | both | P0 | L | QA | P0 bugs closed |
| App icons, splash, store metadata, privacy policy links | both | P1 | M | — | Assets + listings drafted |
| Build signing config (Android keystore, iOS provisioning) | both | P0 | M | — | Signed release builds produced |
| Accessibility + dynamic font pass on key screens | both | P2 | M | — | Labels + hit targets on critical CTAs |

### DAY 10 — Release builds, store submission, buffer

| Task | App | Pri | Effort | Deps | Acceptance |
|---|---|---|---|---|---|
| Produce release builds (AAB + IPA) for both apps | both | P0 | M | D9 signing | Builds upload to Play Console + App Store Connect |
| Internal testing track / TestFlight distribution | both | P0 | M | builds | Testers can install both apps |
| Smoke test release builds on clean devices | both | P0 | M | builds | Login→core flow works on release build |
| Submit to stores (or stage for submission) | both | P0 | S | smoke | Submitted to review / ready to submit |
| Buffer + final bug triage | both | P0 | M | — | Outstanding criticals resolved or documented |

---

## 3. Risk List

| # | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| R1 | **Agora credentials/billing** not provisioned or token cert mismatch | Med | High (blocks all A/V) | Verify Day 1; vet app already integrates Agora — reuse same App ID/cert |
| R2 | **App Store / Play review latency** (1–7+ days) eats the Day-10 ship | High | High | Treat Day 10 as *submission*, not *live*; start store accounts + metadata by Day 8; use internal/TestFlight tracks for "delivery" |
| R3 | **iOS native build** requires macOS/Xcode; user app needs first prebuild | High | High | Confirm a Mac/CI (EAS) is available Day 1; if not, Android-first and defer iOS |
| R4 | **`expo prebuild` on user app** breaks existing config (currently managed-flow) | Med | Med | Branch before prebuild; vet app already bare — mirror its native config |
| R5 | **Push delivery** (FCM/APNs) not actually wired server-side | Med | High (SOS reliability) | Day-1 verification task; if missing, scope a backend sender job |
| R6 | **Razorpay live keys / KYC** for the merchant account incomplete | Med | High | Use test mode to build; flag live-key dependency early |
| R7 | **Real-time signaling** undefined (polling vs websocket) → inconsistent consult/SOS UX | Med | Med | Day-1 decision; if no websocket infra, ship polling for V1 |
| R8 | **Two-app scope in 10 days** is aggressive | High | Med | Vet app is ~80% done (hardening only); concentrate build effort on user app; honor the cut list |
| R9 | Hardcoded LAN API base in user app (`10.253.208.41:8002`) | High | Med | Fixed Day 2 (env/config task); confirm prod URL set before Day-10 build |
| R10 | Device fragmentation A/V bugs (camera/mic perms, echo) | Med | Med | Cross-device call tests Days 3 & 8 |

---

## 4. Cut / Defer list (explicitly out of this 10-day window)

Deferred to **V1.1** to keep the window realistic:
- **Community** module (backend already flags "not consumed by mobile V1").
- **Emergency guides / categories** (same flag).
- **Waitlist** join/leave UI.
- **Offline payment** recording (user side).
- **Chatbot/AI assistant** beyond a basic wired session (P2).
- **Pet notes & documents** management UI (keep reminders + medications + medical records).
- Deep **accessibility** and **localization** polish.
- Full **websocket** real-time infra if not already present (ship polling).
- **iOS submission** if no Mac/EAS pipeline is available Day 1 (Android-first).

> If timeline slips, drop in this order: Day-6 P2 items (wallet, subscriptions, banners) → reviews → SOS vet-responder view. **Never cut: user payments, user Agora A/V, user SOS create, push, store builds.**

---

## 5. Notion import notes
- Each Day-N table row = one Notion task. Suggested columns: **Task, App, Priority, Effort, Dependencies, Acceptance, Status, Owner, Day**.
- Group by **Day** for a timeline view; filter by **Priority** for a triage view; group by **App** for two parallel tracks (user-heavy / vet-hardening).
- Backend tasks are verify/patch only — tag separately so they aren't double-counted as mobile effort.
