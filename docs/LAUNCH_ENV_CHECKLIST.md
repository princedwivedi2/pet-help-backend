# Launch Environment Checklist

Every environment variable the platform needs, which config key reads it, and whether it is present in `.env.example`.

Legend: ✅ = present in `.env.example` | ❌ = missing from `.env.example` | ⚠️ = present but blank (you must fill in the value)

---

## Core Laravel

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `APP_NAME` | `app.name` | ✅ | Set to your app name |
| `APP_ENV` | `app.env` | ✅ | `production` for live |
| `APP_KEY` | `app.key` | ⚠️ | Run `php artisan key:generate` |
| `APP_DEBUG` | `app.debug` | ✅ | Must be `false` in production |
| `APP_URL` | `app.url` | ✅ | Set to your domain, e.g. `https://api.pet-help.com` |

---

## Database

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `DB_CONNECTION` | `database.default` | ✅ | `mysql` for production (currently `sqlite`) |
| `DB_HOST` | `database.connections.mysql.host` | ✅ (commented) | Uncomment for MySQL |
| `DB_PORT` | `database.connections.mysql.port` | ✅ (commented) | Default `3306` |
| `DB_DATABASE` | `database.connections.mysql.database` | ✅ (commented) | Your DB name |
| `DB_USERNAME` | `database.connections.mysql.username` | ✅ (commented) | DB user |
| `DB_PASSWORD` | `database.connections.mysql.password` | ✅ (commented) | DB password |

---

## Queue & Cache

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `QUEUE_CONNECTION` | `queue.default` | ✅ | Use `database` (dev) or `redis` (prod) |
| `CACHE_STORE` | `cache.default` | ✅ | Use `database` (dev) or `redis` (prod) |
| `REDIS_HOST` | `database.redis.default.host` | ✅ | Required if QUEUE_CONNECTION=redis |
| `REDIS_PORT` | `database.redis.default.port` | ✅ | Default `6379` |
| `REDIS_PASSWORD` | `database.redis.default.password` | ✅ | Set if Redis requires auth |

---

## Broadcasting — Laravel Reverb (SOS real-time)

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `BROADCAST_CONNECTION` | `broadcasting.default` | ✅ | Set to `reverb` |
| `REVERB_APP_ID` | `reverb.apps.apps[0].app_id` | ⚠️ | Generate with `php artisan reverb:install` |
| `REVERB_APP_KEY` | `reverb.apps.apps[0].key` | ⚠️ | Shared with mobile client |
| `REVERB_APP_SECRET` | `reverb.apps.apps[0].secret` | ⚠️ | Keep secret — server only |
| `REVERB_HOST` | `reverb.apps.apps[0].options.host` | ✅ | `127.0.0.1` dev, public hostname prod |
| `REVERB_PORT` | `reverb.apps.apps[0].options.port` | ✅ | `8080` dev, `443` prod (behind TLS proxy) |
| `REVERB_SCHEME` | `reverb.apps.apps[0].options.scheme` | ✅ | `https` in production |

---

## Firebase / FCM (Push Notifications + Chat)

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `FIREBASE_CREDENTIALS` | `firebase.projects.app.credentials` | ✅ | Path to service account JSON — e.g. `storage/firebase/firebase_credential.json` |
| `FIREBASE_DATABASE_URL` | `firebase.projects.app.database.url` | ✅ (commented) | Required for Realtime DB chat backend; format: `https://<project-id>-default-rtdb.firebaseio.com` |
| `FIREBASE_CHAT_BACKEND` | `services.firebase.chat_backend` | ✅ | `realtime` (default) or `firestore` (requires `ext-grpc`) |
| `FCM_SERVER_KEY` | `services.fcm.server_key` | ⚠️ | Legacy FCM key — leave blank unless using legacy API |

**Action required:** Download a Firebase Admin SDK service account JSON from the Firebase console → Project Settings → Service Accounts → Generate new private key. Save as `storage/firebase/firebase_credential.json` (excluded from git — add to `.gitignore`).

---

## Agora RTC (Video / Audio Consultations)

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `AGORA_APP_ID` | `services.agora.app_id` | ⚠️ | From Agora console → Project Management |
| `AGORA_APP_CERTIFICATE` | `services.agora.app_certificate` | ⚠️ | Enable "Primary Certificate" in Agora console |

---

## Razorpay (Payments)

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `RAZORPAY_KEY_ID` | `services.razorpay.key_id` | ⚠️ | Test: `rzp_test_…` · Live: `rzp_live_…` |
| `RAZORPAY_KEY_SECRET` | `services.razorpay.key_secret` | ⚠️ | Keep server-side only |
| `RAZORPAY_WEBHOOK_SECRET` | `services.razorpay.webhook_secret` | ⚠️ | Set in Razorpay Dashboard → Webhooks |

---

## CORS

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `CORS_ALLOWED_ORIGINS` | `cors.allowed_origins` | ✅ | Comma-separated list; set to your RN app's web origin or `*` for native-only |

---

## OpenAI (Chatbot)

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `OPENAI_API_KEY` | `services.openai.key` | ⚠️ | Required only if AI chatbot feature is enabled |
| `OPENAI_MODEL` | `services.openai.model` | ✅ | Default `gpt-4o-mini` |

---

## Mail

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `MAIL_MAILER` | `mail.default` | ✅ | `log` (dev), `smtp`/`mailgun`/`resend` (prod) |
| `MAIL_HOST` | `mail.mailers.smtp.host` | ✅ | Only needed for SMTP |
| `MAIL_PORT` | `mail.mailers.smtp.port` | ✅ | Only needed for SMTP |
| `MAIL_USERNAME` | `mail.mailers.smtp.username` | ✅ | Only needed for SMTP |
| `MAIL_PASSWORD` | `mail.mailers.smtp.password` | ✅ | Only needed for SMTP |
| `MAIL_FROM_ADDRESS` | `mail.from.address` | ✅ | Set to your sender address |
| `RESEND_API_KEY` | `services.resend.key` | ❌ | Only needed if `MAIL_MAILER=resend` |

---

## Session

| Variable | Config key | Status | Notes |
|---|---|---|---|
| `SESSION_DRIVER` | `session.driver` | ✅ | `database` (default); use `redis` in production for multi-server |
| `SESSION_DOMAIN` | `session.domain` | ✅ | Set to your domain in production |

---

## Route:list smoke test

```
php artisan route:list
```

**Result as of 2026-06-15:** ✅ Clean — 463 routes registered, no errors.

---

## Pre-launch checklist summary

### Must set before any traffic

- [ ] `APP_KEY` — `php artisan key:generate`
- [ ] `APP_URL` — production domain
- [ ] `APP_DEBUG=false`
- [ ] `DB_*` — production MySQL credentials
- [ ] `FIREBASE_CREDENTIALS` — service account JSON file present on server
- [ ] `FIREBASE_DATABASE_URL` — Realtime DB URL (for consultation chat)
- [ ] `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` — run `php artisan reverb:install`
- [ ] `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` — use test keys for staging, live keys for prod
- [ ] `RAZORPAY_WEBHOOK_SECRET` — must match what Razorpay Dashboard sends
- [ ] `AGORA_APP_ID` / `AGORA_APP_CERTIFICATE` — from Agora console

### Required server processes (beyond PHP-FPM/nginx)

- [ ] `php artisan reverb:start` (or supervisor process) — SOS WebSocket
- [ ] `php artisan queue:work` (or supervisor process) — notification delivery
- [ ] Cron: `* * * * * php artisan schedule:run` — watchdog + reminders

### Credentials to fill in — no automation possible

These require real accounts and cannot be generated automatically:

| Credential | Where to get it |
|---|---|
| Firebase service account JSON | Firebase Console → Project Settings → Service Accounts |
| `FIREBASE_DATABASE_URL` | Firebase Console → Realtime Database |
| `REVERB_APP_KEY/SECRET/ID` | `php artisan reverb:install` (generates locally) |
| `RAZORPAY_KEY_ID/SECRET` | Razorpay Dashboard → API Keys |
| `RAZORPAY_WEBHOOK_SECRET` | Razorpay Dashboard → Webhooks → your endpoint |
| `AGORA_APP_ID` | Agora Console → Project Management |
| `AGORA_APP_CERTIFICATE` | Agora Console → Project Management → Enable Primary Certificate |
| `APP_KEY` | `php artisan key:generate` |
