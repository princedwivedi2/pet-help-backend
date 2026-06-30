# Signaling Model — Pet-Help Backend

**Decision date:** 2026-06-15  
**Status:** Adopted

---

## Summary

The backend uses **two complementary real-time channels** depending on the feature area, plus **FCM push notifications** as the delivery mechanism for out-of-band alerts. No HTTP polling is needed by clients.

| Feature area | Transport | Direction | Auth |
|---|---|---|---|
| SOS status & location updates | Laravel Reverb (WebSocket) | Server → client | Sanctum-authenticated private channel |
| Consultation chat messages | Firebase Realtime Database | Server → client (fan-out) | Firebase credential JSON |
| Appointment status changes | FCM push notification | Server → device | `FIREBASE_CREDENTIALS` service account |
| SOS alert to vets | FCM push notification | Server → device | `FIREBASE_CREDENTIALS` service account |
| All other domain events | FCM push notification | Server → device | `FIREBASE_CREDENTIALS` service account |

---

## 1. SOS Real-Time — Laravel Reverb (WebSocket)

### What's implemented

Two server-side broadcast events live in `app/Events/`:

| Event class | Broadcast name | Payload fields |
|---|---|---|
| `SosStatusChanged` | `sos.status.changed` | `uuid`, `status`, `assigned_vet_id`, `updated_at` |
| `SosLocationUpdated` | `sos.location.updated` | `uuid`, `latitude`, `longitude`, `updated_at` |

Both broadcast on `PrivateChannel('sos.{uuid}')`.

Channel authorization is in `routes/channels.php`:
- The SOS owner (`user_id`) may subscribe.
- The assigned vet (`vetProfile->id === sos->assigned_vet_id`) may subscribe.
- All others are rejected (returns `false`).

Events are dispatched from `SosController`:
```php
broadcast(new SosStatusChanged($sosRequest))->toOthers();
broadcast(new SosLocationUpdated($sosRequest, $lat, $lng))->toOthers();
```

### Mobile client integration (React Native)

Use the [Pusher React Native SDK](https://github.com/pusher/pusher-websocket-react-native) — Reverb speaks the Pusher protocol.

```ts
const pusher = Pusher.getInstance();
await pusher.init({
  apiKey: REVERB_APP_KEY,
  cluster: '',
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: REVERB_SCHEME === 'https',
  authEndpoint: `${API_BASE}/api/v1/broadcasting/auth`,
  onAuthorizer: (channelName, socketId) => {
    // pass Bearer token in Authorization header
  },
});

const channel = await pusher.subscribe({
  channelName: `private-sos.${sosUuid}`,
  onEvent: (event) => {
    if (event.eventName === 'sos.status.changed') { /* update UI */ }
    if (event.eventName === 'sos.location.updated') { /* move pin */ }
  },
});
```

**Broadcasting auth endpoint:** `POST /api/v1/broadcasting/auth` (requires `Authorization: Bearer {token}`)

**Environment variables required (client-side):**
- `REVERB_APP_KEY` — from your `.env` `REVERB_APP_KEY`
- `REVERB_HOST` — server hostname/IP
- `REVERB_PORT` — default `8080` (dev), `443` (prod)
- `REVERB_SCHEME` — `http` (dev) or `https` (prod)

**Server startup:** `php artisan reverb:start` (dev) or run as a persistent process/supervisor in prod.

---

## 2. Consultation Chat — Firebase Realtime Database

### What's implemented

Consultation messages are stored in MySQL (`consultation_messages` table) and fanned out to Firebase Realtime Database (or Firestore if `FIREBASE_CHAT_BACKEND=firestore`) via `FirebaseChatBroadcaster`.

Firebase path written per message:
```
/consultations/{consultation_uuid}/messages/{message_uuid}
```

Payload fields: `uuid`, `sender_type` (`user`|`vet`), `sender_id`, `body`, `created_at`.

The broadcaster is a **side-effect** — Firebase write failures are logged but do not block the HTTP response or the MySQL write. This means MySQL is always the source of truth; Firebase is the realtime fan-out layer.

### Mobile client integration

Use the [Firebase React Native SDK](https://rnfirebase.io/):

```ts
import database from '@react-native-firebase/database';

const ref = database().ref(`/consultations/${consultationUuid}/messages`);
const onValue = ref.on('child_added', (snapshot) => {
  const msg = snapshot.val();
  // append msg to chat UI
});

// cleanup
return () => ref.off('child_added', onValue);
```

**Credentials required:** The same Firebase project used on the server — download `google-services.json` (Android) / `GoogleService-Info.plist` (iOS) from the Firebase console and bundle it with the app.

**Backend env:** `FIREBASE_CREDENTIALS=storage/firebase/firebase_credential.json` — must point to a valid Firebase Admin SDK service account JSON file.

---

## 3. Push Notifications — FCM (Firebase Cloud Messaging)

### What's implemented

`FcmNotificationDispatcher` (`app/Services/FcmNotificationDispatcher.php`) is a complete, production-ready FCM sender using the Firebase Admin SDK (kreait) HTTP v1 API. It:

- Fans out to **all active device tokens** for a user (multi-device)
- Falls back to the legacy `users.fcm_token` column for pre-migration users
- Deactivates invalid/expired tokens automatically on `NotFound` errors
- Is bound to `NotificationDispatcher` contract in `AppServiceProvider`

A custom Laravel notification channel (`app/Notifications/Channels/FcmChannel.php`) bridges the Laravel `->notify()` system to this dispatcher. Every notification that implements `toFcm()` and includes `FcmChannel::class` in `via()` will send a real push.

### Events that trigger FCM push (as of 2026-06-15)

| Event | Notification class | Recipient |
|---|---|---|
| SOS created | `SosAlertNotification` | User + nearby vets |
| SOS escalated | `SosAlertNotification(isEscalation: true)` | Expanded vet radius |
| SOS status changed | `SosStatusNotification` | SOS owner (user) |
| Appointment booked | `AppointmentBookedNotification` | Assigned vet |
| Appointment status changed | `AppointmentStatusNotification` | User or vet (counterparty) |
| Vet profile approved | `VetApprovedNotification` | Vet user |

### FCM `data` payload keys (for mobile deep-link routing)

All pushes include a `type` key in the `data` map. Mobile apps should switch on it:

| `type` value | Action |
|---|---|
| `sos_alert` | Open SOS detail screen (`sos_uuid`) |
| `sos_status_update` | Refresh SOS status (`sos_uuid`, `new_status`) |
| `appointment_booked` | Open appointment detail (`appointment_uuid`) |
| `appointment_status_changed` | Refresh appointment (`appointment_uuid`, `new_status`) |
| `vet_approved` | Navigate to vet dashboard |

### Device token registration

Clients must call `POST /api/v1/auth/device-token` after login with `{ token, platform }`. Tokens are stored in `device_tokens` table and deactivated on logout (`PATCH /api/v1/auth/logout`).

---

## 4. What is NOT real-time WebSocket

The following use FCM push as the signal, with a subsequent REST poll for state:

- **Appointment lifecycle** (accepted/rejected/confirmed/started/completed) — push fires, client polls `GET /api/v1/appointments/{uuid}` for current state.
- **Consultation matching** (vet accepted instant consult) — same pattern.

This is intentional. WebSocket subscriptions for every appointment would require per-appointment channel auth overhead. FCM is sufficient because appointment state changes are infrequent and the client fetches fresh data after wakeup.

---

## 5. Architecture diagram

```
Mobile App (React Native)
│
├── Pusher SDK ─────────────────────→ Laravel Reverb (ws://host:8080)
│   private-sos.{uuid}                 PrivateChannel auth via /api/broadcasting/auth
│   events: sos.status.changed
│            sos.location.updated
│
├── Firebase SDK ────────────────────→ Firebase Realtime DB
│   /consultations/{uuid}/messages     Fan-out written by FirebaseChatBroadcaster
│
└── APNs / FCM ──────────────────────→ Device OS
    (background / foreground push)      via FcmNotificationDispatcher → kreait Admin SDK
                                        Trigger: domain events in AppointmentService,
                                                 SosService, VetOnboardingService
```

---

## 6. Required server processes

| Process | Command | Purpose |
|---|---|---|
| HTTP server | `php artisan serve` / nginx+php-fpm | REST API |
| WebSocket server | `php artisan reverb:start` | SOS real-time |
| Queue worker | `php artisan queue:work` | Queued notifications + jobs |
| Scheduler | `php artisan schedule:run` (cron every minute) | Watchdog jobs, reminders |
