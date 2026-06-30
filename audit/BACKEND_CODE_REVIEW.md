# Backend Code Review — Pet Help API

Location: `pet-help-backend/` (Laravel 12)
Date: 2026-05-31

Scope
- Review of route surface (`routes/api_v1.php`) and key controllers: `AuthController`, `AppointmentController`, `PaymentController`, `ConsultationController`, `SosController`, plus observations about service-layer usage and middleware.

Summary
- The backend exposes a comprehensive `/api/v1` surface that aligns with the product requirements for auth, pets, vets, appointments, SOS, consultations, payments, content, community, and admin features.
- Controllers implement validation, role-based middleware, and thoughtful error handling; many flows delegate business rules to Service classes (`AppointmentService`, `PaymentService`, `ConsultationService`, `SosService`) which centralizes domain logic.
- Security controls observed: `auth:sanctum`, `verified` middleware, `role:` middleware for role-specific areas, throttling on sensitive endpoints, HMAC verification for payment webhooks, and idempotency guards on webhook events.

What is implemented (high level)
- Auth: register, login, OTP send/verify, password reset, profile update, device token registration, token issuance via Sanctum.
- Pets: full CRUD and a large set of sub-resources (notes, reminders, documents, medications, medical records).
- Vets: public discovery (`GET /vets`) and vet profile retrieval; vet onboarding endpoints implemented via `VetOnboardingController` and admin review endpoints exist.
- Appointments: booking, slots, lifecycle actions (accept/reject/start/complete/cancel), waitlist, reschedule, user & vet inboxes.
- Consultations: instant + scheduled lifecycle, join token issuance, post-join signaling via service/fanout, message persistence.
- Payments: create-order, verify, offline payments, wallet and payout requests, webhook handler with HMAC verification and idempotency.
- SOS: create, active queries, status transitions, vet location updates, queued dispatch job (`DispatchSosNearbyVetsJob`).
- Content, blog, community, chatbot, notifications — endpoints present for public and authenticated actions.

Strengths & Good Practices
- Envelope responses via `ApiResponse` trait provide consistent `success/message/data/errors` shape.
- Validation handled with Form Requests (e.g., `StoreAppointmentRequest`) keeping controllers lean.
- Throttling on sensitive actions (OTP, auth, sos, appointment) reduces abuse risk.
- Payment webhook: HMAC verification, duplicate-event dedupe, safe short-circuiting on duplicate replays.
- Role gating: vet/admin role middleware plus controller-level extra checks for actions that must be limited to assigned vet or owner.
- Idempotency in key places (webhook events) and transactional guards on creation flows.

Gaps, Risks & Recommended Fixes
1. Official Mobile API Contract missing (docs mention `docs/MOBILE_API_CONTRACT.md` but it's not present). Action: publish a stable, versioned mobile contract (OpenAPI/Swagger or Postman collection) and keep it in `docs/`.

2. Environment-sensitive behavior: Payment code checks for `RAZORPAY_WEBHOOK_SECRET` and test vs prod keys — ensure CI/CD and staging are configured for `APP_ENV` behavior. Action: add runtime self-check on startup to warn if critical keys missing.

3. Tests: repository contains `tests/` scaffolding, but I did not run test suites — ensure unit & integration coverage for payments, appointment slot concurrency, SOS dispatch, and consultation joins. Action: add focused integration tests for `PaymentService::verifyPayment` and `AppointmentService::create` to validate race conditions.

4. API contract clarity for consultation WebRTC signaling: the server returns join tokens; clients also need Firebase config + fallback instructions. Action: add `docs/CONSULTATION_CLIENT_INTEGRATION.md` showing Firebase RTDB paths, join-token structure, TTL, and sample client pseudocode.

5. Pagination and response shapes: Most endpoints return pagination wrapper; ensure the mobile client code expects `items` vs `data.*` consistently. Action: produce `docs/MOBILE_API_CONTRACT.md` with canonical response examples.

6. Rate-limit & quota observability: Throttle middleware is in routes but no rate-limit headers are surfaced to clients (X-RateLimit-*). Action: add informative headers or an error payload with `retry_after` to help mobile UX.

7. Monitoring & alerts for webhook failures: webhook handler logs but there should be an alerting route for critical failures (e.g. webhook secret missing). Action: integrate with logging/monitoring (Sentry/CloudWatch) and add admin endpoint for webhook replay if needed.

8. File uploads & signed URLs: pet documents and vet docs use signed URLs; verify storage kernel configuration for private disk across environments and include a `docs/FILE_UPLOAD.md` with example multipart and signed-url flows.

9. Transactional cleanup for file uploads: Vet apply flow mentions transactional rollback that cleans orphan files — ensure `Storage::delete()` happens in `finally` blocks when exceptions occur; test with interrupted uploads.

10. Timezones & slot availability: Appointment slot creation/validation must normalize client timezone to server; ensure `StoreAppointmentRequest` and services accept `timezone` param. Action: document timezone handling in `docs/MOBILE_API_CONTRACT.md`.

Operational & Security Recommendations
- Enforce `APP_DEBUG=false` in production and ensure `APP_URL` set correctly for email verification signed URLs.
- Harden rate limits on auth endpoints in production; consider IP + user heuristics to block brute force.
- Periodically rotate `RAZORPAY_KEY_SECRET` and `RAZORPAY_WEBHOOK_SECRET` with an admin mechanism.
- Limit public exposure of admin endpoints; ensure VPN or allowlist for admin panel when possible.

Next Action Plan (recommended)
1. Produce `docs/MOBILE_API_CONTRACT.md` (versioned) — include all mobile-used endpoints with request/response examples and error codes. (High)
2. Add integration tests for: payment verification webhook, appointment slot concurrency, SOS dispatch job. (High)
3. Create `docs/CONSULTATION_CLIENT_INTEGRATION.md` with sample WebRTC + Firebase pseudocode. (Medium)
4. Add startup health-check that validates presence of `RAZORPAY_WEBHOOK_SECRET`, `RAZORPAY_KEY_ID`, and Firebase credentials; fail loudly in CI if missing. (High)
5. Add `X-RateLimit` headers or include `retry_after` in 429 errors for better client UX. (Low)

Files Reviewed (examples)
- `routes/api_v1.php`
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/V1/AppointmentController.php`
- `app/Http/Controllers/Api/V1/PaymentController.php`
- `app/Http/Controllers/Api/V1/ConsultationController.php`
- `app/Http/Controllers/Api/V1/SosController.php`

Conclusion
- The backend appears production-capable with strong domain separation, validation, and security practices already present.
- Immediate priorities for mobile readiness are publishing a frozen mobile API contract, adding integration tests for critical flows (payments, appointments, SOS), and documenting the consultation client integration.

If you'd like, I can now:
- Generate the `docs/MOBILE_API_CONTRACT.md` draft from `routes/api_v1.php` and sample controllers.
- Or produce the visual screen flow map (`pet-help-user-app/audit/SCREEN_FLOW.md`).
Which should I do next?
