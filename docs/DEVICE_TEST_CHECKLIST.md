# RESPAW — On-Device Functionality Test Checklist (Vet + User)

Prereqs: backend `artisan serve --host 0.0.0.0 --port 8002` running; both apps' `.env` → `http://192.168.1.7:8002/api/v1`; phone on same Wi-Fi.
Vet login: `vet@petsathi.com` / `vet12345`. User login: register a new user, or `test@example.com` / `password`.
Format: action → **expected**. The video call needs TWO clients (vet on this device + user on a 2nd device/emulator).

## VET APP
### Auth
- Launch app → **Login screen appears (no infinite splash);** logcat shows `[boot] render {"isBootstrapping":false}`.
- Login with vet creds → **lands on Dashboard.**
- Wrong password → **inline "Invalid credentials" error, no crash.**

### Dashboard
- Open → **greeting, today's appointments (or empty state), pending count, wallet balance all render.**
- Pull to refresh → **data reloads.**

### Appointments
- Appointments tab → **list loads, statuses colour-coded.**
- Tap one → **detail with pet/owner info.**
- Accept pending → **status → confirmed.** Start → **in_progress.** Complete → **completed.**

### Slots / Availability  (was crashing — now fixed)
- Open Slot Management → **NO crash; weekday chips + slots for the selected day (or empty state).**
- Add slot in editor → **saves and shows under that day.**
- Delete slot → **removed from list.**

### Wallet
- Wallet → **balance + transaction history render.**
- Payout request → submit → **shows pending state.**

### Profile / KYC
- Profile → **details render;** Edit → save → **persists.** Change password → **success.**
- (If onboarding) KYC steps 1–3 → **progress header sits below the status bar (inset fix);** submit → **Review screen.**
- Upload documents → **file picker opens, upload succeeds.**

### Consultation / Video  (CORE)
- Consultations list → **loads.**
- Open active consult → **room loads; rtc-token fetched.** Allow camera+mic → **local video tile shows your camera.**
- User joins same consult (2nd device) → **remote video appears + two-way audio.**
- Mute / camera toggle / flip / End → **all respond;** End → **returns to list.**
- Force a token failure → **"Session Unavailable" + "Switch to Chat" (friendly copy, not the old dev message).**

## USER APP
### Auth / Onboarding
- Launch → **Login/Home;** register a user OR login `test@example.com` / `password` → **Home.**

### Pets
- Pets → Add (name, species, photo) → **saved, appears in list.** Edit / delete → **works.**

### Find vet + Book + Pay
- Home/Search → vet list → open **Vet detail (with reviews).**
- Book → pick slot → confirm → **Payment screen.**
- Payment (mock on) → **"Processing payment (test mode)" → auto-confirms → Confirmation.**
- Appointments list → **new appointment present.**

### Consultation / Video  (CORE — pairs with vet device)
- Start video consult (modality → video) → pay (mock) → **room.**
- Vet accepts on other device → **two-way video + audio.** In-call chat → **messages deliver both ways.**

### Records / Prescriptions
- After vet completes + issues a prescription → user → Pet → Prescriptions → **prescription shows + "Download" works.**

### Other
- Notifications → **list loads.** Blog → **list + post open.** Subscriptions → **plans render.** Wallet → **balance shows.** Profile edit / change password → **persist.**
