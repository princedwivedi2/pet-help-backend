# Day 8 — End-to-End Happy-Path Run (User + Vet)

**Task:** Full booking → pay → consult → prescription happy-path run (P0, cross-app).
**Acceptance:** End-to-end journey passes on real devices.
**Prepared:** 2026-06-21 · Code verified wired across user app, vet app, and backend.

---

## 0. Code verification (done — static)

Every leg of the journey is wired in code. This was confirmed before writing the runbook:

| Leg | User app | Vet app | Backend route | Status |
|---|---|---|---|---|
| Book appointment | `BookingScreen` → `POST /appointments` | — | `POST /appointments` | ✅ wired |
| Pay (Razorpay) | `PaymentScreen` WebView bridge auto-captures `payment_id`+`signature` → `verify` | — | `POST /payments/create-order`, `/verify` | ✅ wired (Day 2 bridge closed) |
| Consult — token | `useRtcSession` → `GET /consultations/{uuid}/rtc-token` → Agora `joinChannel` | same endpoint | `GET /consultations/{uuid}/rtc-token` | ✅ wired |
| Consult — lifecycle | `start`, `join`, `messages`, `connection-failure`, `complete` | `accept`, `complete` | all present in `api_v1.php:304–316` | ✅ wired |
| Prescription issue | — | `POST /visit-records/{uuid}/prescription` | `VisitRecordController::uploadPrescription` | ✅ wired |
| Prescription view | `PrescriptionsScreen` → `getPetVisitRecords` filter on `prescription_text/file` + download | — | `GET /pets/{pet}/visit-records` | ✅ wired |

**Conclusion:** No code gaps block the run. What remains is execution on real hardware, which requires credentials and devices (below).

---

## 1. Prerequisites (you must supply)

| Item | Where | Needed for |
|---|---|---|
| **Razorpay test key + secret** | backend `.env` (`RAZORPAY_KEY`, `RAZORPAY_SECRET`); backend must return `razorpay_key`/`razorpay_order_id` | Real checkout. *Shortcut:* set `EXPO_PUBLIC_PAYMENTS_MOCK=true` in the user app to skip Razorpay and call backend mock-confirm. |
| **Agora App ID (+ cert)** | user app `.env` `EXPO_PUBLIC_AGORA_APP_ID`; backend Agora cert | Video/audio call. Without it the room degrades to chat (no hang). |
| **FCM creds + `google-services.json`** | backend + user/vet app | Push deep-link step. Optional for the core journey. |
| **2 physical devices** | — | One pet-owner (user app), one vet (vet app). Camera + mic required for A/V. |
| **Seeded accounts** | backend | 1 verified user with ≥1 pet; 1 approved/verified vet with availability slots. |

> Fastest reliable run: **`EXPO_PUBLIC_PAYMENTS_MOCK=true` + real Agora App ID.** This removes the Razorpay live-key dependency while still exercising the full booking→consult→prescription loop.

---

## 2. Build & install

```
# Backend (LAN-reachable from both devices)
php artisan serve --host 0.0.0.0 --port 8002
# Confirm both apps' API base points at this host (env-driven, not the old 10.253.208.41 literal)

# User app
cd pet-help-user-app && npx expo run:android   # device A

# Vet app
cd respaw-vet-app && npx expo run:android        # device B
```

---

## 3. The run — step by step

Run top to bottom. Tick each ✅ / log a bug if it fails.

### A. Book + pay (User — device A)
1. Log in as the seeded user. → Home loads, greeting shows real name (not a placeholder).
2. Search/select a vet → Vet Detail renders (reviews visible).
3. Tap **Book** → BookingScreen → pick a slot (today/+1/+2) → confirm.
   - **Expect:** `POST /appointments` succeeds; routed to Payment.
4. Payment:
   - *Mock mode:* "Processing payment (test mode)…" → auto-confirms.
   - *Real mode:* WebView Razorpay opens → pay with test card → bridge auto-captures id+signature → verify (no manual paste).
   - **Expect:** `replace` → Confirmation screen with vet name + scheduled time.
5. Check **Appointments** list → new appointment present, status reflects paid/booked.

### B. Accept + start consult (Vet — device B)
6. Log in as the vet → Dashboard/Appointments shows the new appointment.
7. Accept → Start. → `PATCH .../accept` then consult `accept`/start succeed.
8. Vet enters consultation room.
   - **Expect:** `rtc-token` issued; Agora channel joins.

### C. Live A/V call (both devices)
9. User opens the consultation (from appointment/consult entry) → joins same channel.
10. **Expect (P0 core):** two-party **audio + video** connects; local + remote tiles render.
11. Exercise controls: mute, camera on/off, flip camera, end. → all respond.
12. Send a chat message each way → appears within ~5s (poll) on both sides, no dupes.
13. *(Optional)* Kill network on one device briefly → "Connection Lost" overlay offers **Continue via Chat / Leave**; refund-pending UX shows if call drops.

### D. Complete + prescription (Vet → User)
14. Vet completes the consult → `complete` succeeds; visit record created.
15. Vet creates a visit record + uploads a **prescription** (text and/or file) → `POST /visit-records/{uuid}/prescription` succeeds.
16. User → Pet → **Prescriptions / Records**.
    - **Expect:** the new prescription appears (text shown; "Download prescription" works for file).

### E. Push deep-link (optional — needs FCM)
17. Trigger a notification (e.g. appointment update / SOS) → tap it.
    - **Expect:** opens the correct screen (AppointmentDetail / ConsultationRoom / PaymentHistory). In-app notification tap deep-links identically.

---

## 4. Pass / fail checklist

- [ ] Booking creates appointment
- [ ] Payment verifies automatically (no manual id/signature paste)
- [ ] Confirmation screen reached
- [ ] Vet sees + accepts + starts appointment
- [ ] **Two-party audio+video call connects** (P0 gate)
- [ ] Call controls (mute/camera/flip/end) work
- [ ] In-call chat delivers both ways
- [ ] Vet completes consult
- [ ] Vet-issued prescription visible + downloadable in user app
- [ ] (Optional) Push tap deep-links to correct screen

**Day 8 P0 passes when every non-optional box is ticked on both Android devices.** Repeat on iOS if a Mac/EAS pipeline is available (else Android-first per plan R3).

---

## 5. Known caveats carried from prior QA (`pet-help-user-app/docs/QA_REPORT.md`)
- Vet `latitude`/`longitude` not in API payload → Nearby-Vets pins/distance degrade (not on this happy path).
- No reschedule endpoint (cancel + rebook is the supported path).
- Displayed booking/consult fee is a client estimate; backend recomputes the authoritative amount.
- Public vet endpoints leak PII (phone/email/license) — backend security follow-up.
