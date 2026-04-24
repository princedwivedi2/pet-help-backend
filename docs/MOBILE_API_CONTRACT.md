# Mobile API Contract (v1)

## Base URL
- Local: `http://localhost/api/v1`
- Production: `https://<your-domain>/api/v1`

## Authentication
- Auth scheme: Sanctum bearer token
- Header: `Authorization: Bearer <token>`
- Required for protected endpoints: `auth:sanctum`
- Verified-email requirement for most business APIs: `verified`
- Access token lifetime: use the current Sanctum token policy configured by the backend; if no explicit TTL is configured, treat tokens as persistent until logout/revocation
- Refresh token: not issued by the current backend contract
- Refresh flow: not available yet; clients must re-authenticate when a token is revoked, invalid, or rejected
- Refresh failure responses: expect `401 Unauthorized` with an auth-required message
- Client strategy:
  - store tokens in secure OS storage only
  - refresh UI state silently when a `401` indicates a stale session
  - back off exponentially on repeated failures
  - force re-login when the server rejects the token or the user signs out

## Role Behavior
- `user`
  - Can manage own pets, appointments, SOS, documents, profile, notifications
- `vet`
  - Can access vet profile/onboarding endpoints
  - Appointment and SOS action permissions are scoped to assigned resources
  - Pending vet can login with notice; rejected/suspended vet is blocked
- `admin`
  - Broad read and moderation/admin actions
  - Appointment mutation actions remain restricted (admin is read-only for appointment lifecycle)

## Standard Headers
- Request:
  - `Accept: application/json`
  - `Content-Type: application/json` (for JSON)
  - `Content-Type: multipart/form-data` (for uploads)
- Response:
  - `Content-Type: application/json`

## Response Envelope
All API responses follow the same envelope:

```json
{
  "success": true,
  "message": "Human readable message",
  "data": {},
  "errors": null
}
```

Error example:

```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "field": ["Error detail"]
  }
}
```

## Common Status Codes
- `200` OK
- `201` Created
- `401` Unauthenticated
- `403` Forbidden
- `404` Not Found
- `409` Conflict
- `422` Validation/Domain error
- `429` Rate limited

## Pagination Contract
Paginated endpoints return:

```json
{
  "data": {
    "items_or_resource_name": [],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 0
    }
  }
}
```

## Upload Contract
- Use `multipart/form-data`
- Include file fields exactly as required by endpoint validators
- Important secure storage behavior:
  - Vet verification docs are stored on private storage
  - Pet documents are stored on private storage and downloaded via temporary URL

## Auth Flow Endpoints
- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`
- `POST /auth/email/resend`
- `GET /auth/email/verify/{id}/{hash}` (signed)
- `PUT /auth/change-password`
- `PUT /auth/profile`
- `DELETE /auth/account`
- `POST /auth/device-token`

## Device Token Flow (Push)
1. App obtains push token from FCM/APNS bridge.
2. App calls `POST /auth/device-token` with token payload.
3. Server stores token for notification fanout.
4. On logout, token is cleared server-side.

## Appointment Flow (Mobile)
1. User books: `POST /appointments`
2. Vet accepts/rejects:
   - `PATCH /appointments/{uuid}/accept`
   - `PATCH /appointments/{uuid}/reject`
3. Vet starts/completes:
   - `PATCH /appointments/{uuid}/start`
   - `PATCH /appointments/{uuid}/complete`
4. Either side cancels (policy-scoped):
   - `PATCH /appointments/{uuid}/cancel`
5. Lists/details:
   - `GET /appointments`
   - `GET /appointments/{uuid}`
   - `GET /appointments/vet` (vet only)

Reliability guarantees:
- Past-time booking blocked
- Overlapping slots blocked
- Stale appointments auto-expire via scheduler
- Waitlist notifications processed via scheduler

## SOS Flow (Mobile)
1. User creates SOS: `POST /sos`
2. Nearby vet notification dispatch is queued asynchronously
3. Active SOS lookup: `GET /sos/active`
4. Status updates: `PUT /sos/{uuid}/status`
5. Live location updates (assigned vet/admin scope): `PUT /sos/{uuid}/location`

Safety guarantees:
- Post-acceptance status mutations are assigned-vet scoped
- Vet location updates are assigned-vet scoped
- SOS escalation/expiry jobs run on scheduler

## Payment Flow (Mobile)
- `POST /payments/create-order`
- `POST /payments/verify`
- `POST /payments/offline`
- `GET /payments`
- `GET /payments/{uuid}`
- `POST /payments/{uuid}/refund`
- `GET /payments/wallet`
- Webhook callback: `POST /payments/webhook` (public, signature validated server-side)
- Idempotency guidance:
  - send an `Idempotency-Key` header on `POST /payments/create-order`, `POST /payments/verify`, and `POST /payments/{uuid}/refund`
  - server should treat repeated keys as the same intent and return the original response
  - recommended retention window: 24-72 hours
  - generate a new UUID per distinct payment intent
  - do not reuse keys across different operations
  - client retry strategy: exponential backoff with the same key for the same intent only
- Webhooks must remain signature-validated and idempotent on the server side

## Pet Document Access Rules
- List/download pet documents: owner or admin only
- Related vets are not allowed to list/download pet documents by relationship alone

## Vet Document Access Rules
- Vet can view own verification documents
- Admin can review any vet verification document via admin route
- Documents are served from private storage paths

## Scheduler / Ops Requirements
- Server cron must run Laravel scheduler every minute:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Client Implementation Notes
- Always branch by `success` first, then inspect `errors`
- Treat `409` as state conflict and refresh data before retry
- Treat `422` as actionable validation/domain feedback
- Refresh auth state on `401`
- Avoid optimistic updates for SOS and appointment status unless reconciled with server response
