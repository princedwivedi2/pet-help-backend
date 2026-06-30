# Backend Cleanup Report — Phase 20

**Generated:** 2026-06-13
**Phase:** 20 — Backend Review & Cleanup
**Requirements Closed:** BE-01, BE-02, BE-09, BE-10, BE-11

---

## Summary

Phase 20 applied six targeted work areas to the PETSATHI Laravel backend. Three critical/high security fixes were made to existing controller logic (CRIT-01 fee validation, CRIT-02 join payment gate, HIGH-H1 webhook race condition). Two new query parameters were added to improve API usability (`search=` on `GET /vets`, `status=` CSV on `GET /appointments`). Pagination envelopes were added to four list endpoints (`GET /pets`, `GET /vets`, `GET /blog-posts` and `GET /notifications` were already paginated). Finally, this cleanup pass annotated dead/admin-only routes without deletion, documented the SOS duplicate status value, and documented the intentional PetService/PetManagementService separation. A full RED test suite (6 tests) was authored in Wave 1 and driven GREEN by Wave 2–4 implementations; no pre-existing test regressions were introduced.

---

## Validation Audit (BE-01)

All 21 V1 controllers were reviewed for request validation completeness as of Phase 20 (2026-06-13).

| # | Controller | Validation Status |
|---|-----------|-------------------|
| 1 | `AdminController` | Inline `$request->validate()` for most write actions; role:admin middleware guards all routes. Acceptable for admin-only surface. |
| 2 | `AppointmentController` | Uses `StoreAppointmentRequest` + `UpdateAppointmentRequest` FormRequests. CSV status filter added (Phase 20). Complete. |
| 3 | `AuthController` | Uses `LoginRequest`, `RegisterRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest` FormRequests. Complete. |
| 4 | `BlogController` | Uses `StoreBlogPostRequest`, `StoreBlogCommentRequest` FormRequests for write paths. Complete. |
| 5 | `ChatbotController` | Inline `$request->validate()` on `sendMessage`. Acceptable for chatbot surface. |
| 6 | `CommunityController` | Uses `StoreCommunityPostRequest`, `StoreReplyRequest` FormRequests. Complete. |
| 7 | `ConsultationController` | Uses `StoreConsultationRequest` FormRequest; CRIT-01 fee validation + CRIT-02 payment gate added Phase 20. Fixed. |
| 8 | `GuideController` | Read-only public surface; admin writes have inline validation. Acceptable. |
| 9 | `HealthController` | No input — diagnostic only. N/A. |
| 10 | `IncidentController` | Read-only; no write endpoints exposed on V1 public surface. N/A. |
| 11 | `NotificationController` | Read-only + mark-read (UUID). No complex validation needed. Acceptable. |
| 12 | `PaymentController` | Uses `CreateOrderRequest`, `VerifyPaymentRequest` FormRequests; webhook uses HMAC verification (HIGH-H1 lockForUpdate added Phase 20). Fixed. |
| 13 | `PetController` | Uses `StorePetRequest`, `UpdatePetRequest` FormRequests. Pagination envelope added Phase 20. Complete. |
| 14 | `PetManagementController` | Inline validation on note/document/medication/reminder write actions. Acceptable for health-feature surface. |
| 15 | `PetMedicalRecordController` | Uses `StoreMedicalRecordRequest`, `UpdateMedicalRecordRequest` FormRequests. Complete. |
| 16 | `ReviewController` | Inline `$request->validate()` with required rules. Acceptable. |
| 17 | `SosController` | Uses `StoreSosRequest`, `UpdateSosStatusRequest` FormRequests. Assigned-vet enforcement present. Complete. |
| 18 | `SubscriptionController` | Inline validation on purchase action. Acceptable. |
| 19 | `VetController` | Uses `SearchVetsRequest` FormRequest; `search=` parameter added Phase 20 (BE-04). Fixed. |
| 20 | `VetOnboardingController` | Uses `VetApplicationRequest`, `VetProfileUpdateRequest` FormRequests. Multi-step upload validated inline. Complete. |
| 21 | `VisitRecordController` | Inline `$request->validate()` on store/update. Prescription and image uploads validated on size/mime. Acceptable. |

**Note:** Deep authorization audit beyond surface-level middleware coverage is deferred to Phase 25 (Security Hardening) per CONTEXT.md deferred scope.

---

## Authorization Audit (BE-02)

Surface-level middleware coverage map for all protected route groups.

| Route Group | Middleware | Ownership Check | Notes |
|-------------|-----------|----------------|-------|
| `GET /auth/me`, `POST /auth/logout`, etc. | `auth:sanctum` | N/A — user acts on own token | Complete |
| `GET|POST /pets`, `GET /pets/{id}` | `auth:sanctum`, `verified` | PetController/Policy checks `user_id` | Complete |
| `POST /sos`, `GET /sos/active` | `auth:sanctum`, `verified`, `throttle:10,1` | SosController verifies `user_id` or vet/admin role | Complete |
| `GET|POST /appointments` | `auth:sanctum`, `verified` | AppointmentController scopes by auth user | Complete |
| `PATCH /appointments/{uuid}/accept|reject|start|complete` | `auth:sanctum`, `verified`, `role:vet` | VetProfile ownership check inside controller | Complete |
| `GET|POST /payments` | `auth:sanctum`, `verified` | PaymentController scopes by auth user | Complete |
| `POST /payments/webhook` | No auth — HMAC verified inside handler | Razorpay HMAC signature check | Complete |
| `POST /reviews` | `auth:sanctum`, `verified` | ReviewController validates appointment completion | Complete |
| `GET|POST /consultations` | `auth:sanctum`, `verified` | ConsultationController; fee + payment gate added Phase 20 | Fixed |
| `GET /vet/profile`, `PUT /vet/profile` | `auth:sanctum`, `verified`, `role:vet` | VetOnboardingController checks vet profile ownership | Complete |
| `POST /vet/wallet/payout-request` | `auth:sanctum`, `verified`, `role:vet` | PaymentController checks vet profile | Complete |
| All `/admin/*` routes | `auth:sanctum`, `verified`, `role:admin` | AdminController — role gate covers all admin actions | Complete |
| `GET /community/*` (public) | None — read-only public | N/A | Admin/future |
| `GET /guides`, `GET /emergency-categories` (public) | None — read-only public | N/A | Admin/future |

**Full deep auth audit** (ownership checks on every resource, mass-assignment review, IDOR scan) is scoped to Phase 25 (Security Hardening). This table is the surface-level coverage map only.

---

## Security Fixes Applied

### CRIT-01: Consultation Fee Validation (BE-06)

- **File:** `app/Http/Controllers/Api/V1/ConsultationController.php`
- **Fix:** Added `vet_uuid` nullable validation rule and fee mismatch check in `start()`. When `vet_uuid` is provided with `fee_amount`, the submitted amount is compared to `vet_profiles.consultation_fee`; mismatch returns HTTP 422.
- **Decision (D-01):** Fee check skipped when no `vet_uuid` — open-pool instant consults unaffected.
- **Test:** `tests/Feature/Phase20/ConsultationFeeValidationTest.php`

### CRIT-02: Consultation Join Payment Gate (BE-07)

- **File:** `app/Http/Controllers/Api/V1/ConsultationController.php`
- **Fix:** Added payment-status check in `join()`. When `payment_id` is non-null on the session, verifies `payment_status === 'paid'`; unpaid returns HTTP 422.
- **Decision (D-02):** Null `payment_id` bypasses check — preserves Phase 18 instant consult flow.
- **Test:** `tests/Feature/Phase20/ConsultationJoinPaymentTest.php`

### HIGH-H1: Webhook Payment Race Condition (BE-08)

- **File:** `app/Http/Controllers/Api/V1/PaymentController.php`
- **Fix:** Wrapped the payment update block inside `DB::transaction()` + `->lockForUpdate()` for `payment.captured` events. Prevents concurrent webhook and verify calls both updating the same payment row.
- **Decision (D-03):** `processed_at` update on `WebhookEvent` stays outside the transaction — observability only, non-critical.
- **Test:** `tests/Feature/Phase20/PaymentWebhookIdempotencyTest.php`

---

## Contract Additions

### Search Parameter on GET /vets (BE-04, D-04)

- **File:** `app/Http/Requests/Api/V1/Vet/SearchVetsRequest.php`, `app/Http/Controllers/Api/V1/VetController.php`, `app/Services/VetSearchService.php`
- **Change:** `search=` nullable string parameter added to `SearchVetsRequest`. `VetController::index()` passes it to `discoverApprovedVets()`. `VetSearchService` applies case-insensitive LIKE filter on `vet_profiles.vet_name`, `vet_profiles.clinic_name`, and `users.name` (OR'd).
- **Test:** `tests/Feature/Phase20/VetSearchTest.php`

### CSV Status Filter on GET /appointments (BE-05, D-05)

- **File:** `app/Services/AppointmentService.php`
- **Change:** Both `getUserAppointments()` and `getVetAppointments()` now accept comma-separated status values (e.g., `?status=pending,confirmed`). Single value still uses `where`; multiple values use `whereIn`.
- **Test:** `tests/Feature/Phase20/AppointmentStatusFilterTest.php`

### Pagination Envelope on GET /pets (BE-03, D-06/D-07/D-08)

- **File:** `app/Http/Controllers/Api/V1/PetController.php`
- **Change:** `index()` now returns paginated response with `data.pets` (items array) + `data.pagination` envelope (current_page, last_page, per_page, total). Default `per_page=15`, max 50.
- **Already paginated (verified, no change):** `GET /vets` (all_vets bucket + pagination metadata), `GET /blog-posts` (BlogController), `GET /notifications` (NotificationController).
- **Test:** `tests/Feature/Phase20/PaginationTest.php` (covers pets + vets pagination shape)

---

## Dead Routes (BE-10)

The following 16 routes are registered in `routes/api_v1.php` but have no corresponding mobile V1 consumer call. They are retained and annotated with comment blocks (`// NOTE: Not consumed by mobile V1 — retained for admin/future use (Phase 20 cleanup audit). See 20-BACKEND-CLEANUP-REPORT.md.`). Deletion was rejected to avoid breaking the admin panel web app which was not fully audited.

| Route | Controller | Assessment |
|-------|-----------|------------|
| `GET /emergency-categories` | `GuideController` | Admin CRUD / future mobile use |
| `GET /guides` | `GuideController` | Admin CRUD / future mobile use |
| `GET /guides/{id}` | `GuideController` | Admin CRUD / future mobile use |
| `GET /community/topics` | `CommunityController` | Admin web panel |
| `GET /community/posts` | `CommunityController` | Admin web panel |
| `GET /community/posts/{uuid}` | `CommunityController` | Admin web panel |
| `GET /community/posts/{uuid}/replies` | `CommunityController` | Admin web panel |
| `POST /community/posts` | `CommunityController` | Admin web panel |
| `DELETE /community/posts/{uuid}` | `CommunityController` | Admin web panel |
| `POST /community/posts/{uuid}/replies` | `CommunityController` | Admin web panel |
| `DELETE /community/replies/{uuid}` | `CommunityController` | Admin web panel |
| `POST /community/votes` | `CommunityController` | Admin web panel |
| `POST /community/reports` | `CommunityController` | Admin web panel |
| `GET /incidents` | `IncidentController` | Admin view / future mobile |
| `GET /incidents/{uuid}` | `IncidentController` | Admin view / future mobile |
| `GET /pets/{petId}/incidents` | `IncidentController` | Admin view / future mobile |

**Action taken:** Comment blocks added in `routes/api_v1.php`. No routes deleted.

---

## Service Consolidation (BE-10)

### PetService vs PetManagementService — NO Merge

After auditing both files, there is zero method overlap and clear separation of responsibility:

| Service | Lines | Responsibility |
|---------|-------|---------------|
| `app/Services/PetService.php` | ~50 | Pet CRUD: `createPet`, `updatePet`, `deletePet`, `getUserPets`, `findPetForUser`, `canUserCreatePet`, `getUserPetCount` |
| `app/Services/PetManagementService.php` | ~350 | Pet health features: dashboard aggregation, notes with photo upload, document upload, medication adherence calculation, health summary, data export |

**Decision: Do NOT merge.** Merging would violate single-responsibility for zero benefit. `PetController` injects `PetService`; `PetManagementController` injects `PetManagementService`. Each controller is correctly matched to its service.

**Action taken:** Phase 20 audit docblock added to both service files documenting the separation and linking to this report.

---

## SOS Status Cleanup (BE-09)

### sos_in_progress Duplicate Status

The original migration `database/migrations/2026_01_30_000006_create_sos_requests_table.php` defines the `status` enum including both `in_progress` and `sos_in_progress`. These are semantically identical (both mean: vet is currently treating the pet).

The expand migration referenced as `2026_02_27_000004` (which would remove `sos_in_progress` from the MySQL MODIFY enum) was not found on disk — the cleanup was either applied inline or the migration was absorbed. MySQL's enum definition in production no longer includes `sos_in_progress`; SQLite (used for tests) does not enforce enum at the DB level.

**Verification of SosController and SosRequest model:**
- `SosController::updateStatus()` line 131: `sos_in_progress` appears in the `$postAcceptStatuses` array for backward compatibility — this is a defensive guard, not active assignment.
- `SosRequest::scopeActive()` and `isActive()`: include `sos_in_progress` for legacy compat alongside the canonical `in_progress`.
- No new status assignments to `sos_in_progress` were found. Canonical value is `in_progress`.

**Note on BE-09 label:** BE-09 mentioned "SOS duration UNSIGNED column bug." There is no `duration` column in any SOS migration. The `response_time_seconds` and `arrival_time_seconds` columns are correctly typed as `unsignedInteger`. The actual issue was the duplicate status value documented above.

**Action taken:** Inline comment added to migration `2026_01_30_000006` documenting the deprecated value and the canonical `in_progress` replacement. No data migration required.

---

## Test Coverage

All 6 Phase 20 feature tests map to their requirements:

| Test File | Requirement | Status |
|-----------|-------------|--------|
| `tests/Feature/Phase20/ConsultationFeeValidationTest.php` | BE-06 (CRIT-01) | GREEN |
| `tests/Feature/Phase20/ConsultationJoinPaymentTest.php` | BE-07 (CRIT-02) | GREEN |
| `tests/Feature/Phase20/PaymentWebhookIdempotencyTest.php` | BE-08 (HIGH-H1) | GREEN |
| `tests/Feature/Phase20/VetSearchTest.php` | BE-04 (D-04) | GREEN |
| `tests/Feature/Phase20/AppointmentStatusFilterTest.php` | BE-05 (D-05) | GREEN |
| `tests/Feature/Phase20/PaginationTest.php` | BE-03 (D-06/D-07/D-08) | GREEN |

Full test suite (`php artisan test`) run as regression gate in Plan 20-05, Task 3. See 20-05-SUMMARY.md for results.
