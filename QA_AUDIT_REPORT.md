# PetSathi / RESPAW Backend - Senior QA Audit Report

**Project:** PetSathi Emergency Pet Care Backend API  
**Framework:** Laravel 12 / PHP 8.2+  
**Auth:** Laravel Sanctum (token-based)  
**Database:** MySQL (production) / SQLite in-memory (tests)  
**Date:** January 2026  
**QA Engineer:** Senior QA Audit (Automated)  
**Total Routes:** 73 API endpoints across 10 controllers  

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Review](#2-architecture-review)
3. [Critical Issues (P0)](#3-critical-issues-p0)
4. [Security Concerns (P1)](#4-security-concerns-p1)
5. [Code Quality Issues (P2)](#5-code-quality-issues-p2)
6. [Missing Features (P3)](#6-missing-features-p3)
7. [Test Coverage Report](#7-test-coverage-report)
8. [API Endpoint Audit Matrix](#8-api-endpoint-audit-matrix)
9. [Performance Concerns](#9-performance-concerns)
10. [Recommendations Summary](#10-recommendations-summary)

---

## 1. Executive Summary

### Overall Assessment: **B+ (Good, with critical fixes needed)**

The PetSathi backend demonstrates solid architectural patterns (Service Layer, Form Requests, Policies, UUID routing) and comprehensive API coverage across 8 modules. However, **2 critical bugs** and **5 security concerns** must be addressed before production deployment.

| Category | Count | Severity |
|----------|-------|----------|
| Critical Issues | 2 | P0 - Must fix immediately |
| Security Concerns | 5 | P1 - Fix before production |
| Code Quality Issues | 8 | P2 - Fix in next sprint |
| Missing Features | 8 | P3 - Roadmap items |
| **Total Findings** | **23** | |

### Strengths
- Clean Service Layer pattern separating business logic from controllers
- Consistent `ApiResponse` trait for standardized JSON responses
- UUID-based routing preventing ID enumeration attacks
- Comprehensive Form Request validation classes
- SoftDeletes on critical models (Blog, Community)
- Well-structured migration sequence
- Sanctum token auth with role-based middleware

### Test Coverage Created
- **11 test files** covering all 8 modules
- **100+ test methods** with positive, negative, and edge case coverage
- **PHPUnit** with `RefreshDatabase` trait and SQLite in-memory database
- **7 new model factories** created for Blog & Community modules

---

## 2. Architecture Review

### Pattern Assessment

| Pattern | Status | Notes |
|---------|--------|-------|
| MVC / Service Layer | ✅ Excellent | Controllers delegate to services |
| Form Request Validation | ✅ Good | 12 FormRequest classes |
| Policy Authorization | ⚠️ Partial | Registered but manually bypassed |
| API Response Standardization | ✅ Excellent | Consistent `{success, message, data, errors}` |
| UUID Route Binding | ✅ Good | Prevents ID enumeration |
| Role-Based Access | ✅ Good | EnsureRole middleware |
| Rate Limiting | ⚠️ Missing | Only on auth routes |
| Input Sanitization | ⚠️ Partial | Laravel handles basics |
| Error Handling | ⚠️ Inconsistent | Mixed exception types |

### Module Breakdown

| Module | Controller | Service | FormRequests | Policy | Factory | Tests |
|--------|-----------|---------|-------------|--------|---------|-------|
| Auth | `AuthController` | - | 2 | - | `UserFactory` | ✅ 10 |
| Pets | `PetController` | `PetService` | 2 | `PetPolicy` | `PetFactory` | ✅ 10 |
| SOS | `SosController` | `SosService` | 2 | `SosPolicy` | `SosRequestFactory` | ✅ Tests |
| Incidents | `IncidentController` | `IncidentService` | - | `IncidentPolicy` | `IncidentLogFactory` | ✅ Tests |
| Vets | `VetSearchController` | `VetSearchService` | 1 | - | `VetProfileFactory` | ✅ 5 |
| Vet Onboarding | `VetOnboardingController` | `VetOnboardingService` | 1 | - | `VetProfileFactory` | ✅ 15 |
| Guides | `GuideController` | - | - | - | - | ✅ 7 |
| Blog | `BlogController` | - | 2 | - | 4 new | ✅ 35+ |
| Community | `CommunityController` | - | 2 | - | 3 new | ✅ 40+ |
| Admin | `AdminController` | - | - | - | - | ✅ Tests |

---

## 3. Critical Issues (P0)

### 3.1 CRITICAL: Double Password Hashing

**Files Affected:**
- `app/Http/Controllers/Api/V1/AuthController.php` → `register()` method
- `app/Services/VetOnboardingService.php` → `registerVet()` method
- `app/Models/User.php` → `casts` array

**Description:**  
The User model defines `'password' => 'hashed'` in its `$casts` array, which tells Laravel 12 to automatically hash the password when it is set via `$model->password = 'raw'` or mass-assigned. However, both `AuthController::register()` and `VetOnboardingService::registerVet()` also explicitly call `Hash::make()` on the password before passing it to `User::create()`.

This means passwords are **hashed twice** (bcrypt of bcrypt), making login impossible because `Hash::check('raw_password', double_hashed)` will always return `false`.

**Impact:** Users and vets CANNOT log in after registration.

**Fix (choose one):**

Option A - Remove manual Hash::make (recommended):
```php
// AuthController::register()
User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => $request->password, // 'hashed' cast will auto-hash
]);

// VetOnboardingService::registerVet()  
$user = User::create([
    'full_name' => $data['full_name'],
    'email' => $data['email'],
    'password' => $data['password'], // 'hashed' cast will auto-hash
]);
```

Option B - Remove the 'hashed' cast:
```php
// User model - remove 'password' => 'hashed' from casts
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        // Remove: 'password' => 'hashed',
    ];
}
```

**Priority:** P0 - IMMEDIATE FIX  
**Severity:** Blocker - prevents all authentication

---

### 3.2 CRITICAL: MustVerifyEmail Not Enforced

**File:** `app/Models/User.php`

**Description:**  
The User model implements `Illuminate\Contracts\Auth\MustVerifyEmail`, but:
1. No verification email is sent during registration
2. No `verified` or `EnsureEmailIsVerified` middleware applied to any route
3. No `/email/verify` or `/email/resend` routes defined

**Impact:** The `MustVerifyEmail` contract is dead code. Either enforce it or remove it to avoid confusion.

**Fix:**  
Option A - Enforce email verification:
```php
// Add routes for email verification
Route::post('/auth/email/resend', [AuthController::class, 'resendVerification'])
    ->middleware('auth:sanctum');

// Apply middleware to protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // ... authenticated routes
});
```

Option B - Remove the contract (simpler for MVP):
```php
// User.php - Remove MustVerifyEmail
class User extends Authenticatable
{
    // Remove: implements MustVerifyEmail
}
```

**Priority:** P0 - Must decide before production

---

## 4. Security Concerns (P1)

### 4.1 Registered Policies Not Used

**Files:** `PetPolicy`, `SosPolicy`, `IncidentPolicy`

**Description:** Three policies are registered in `AuthServiceProvider` but controllers perform manual ownership checks instead of calling `$this->authorize()`. This duplicates logic and risks inconsistency.

**Example in PetController:**
```php
// Current: manual check
if ($pet->user_id !== auth()->id()) {
    return $this->errorResponse('Forbidden', 403);
}

// Should be:
$this->authorize('update', $pet);
```

**Recommendation:** Refactor controllers to use `$this->authorize()` calls and rely on Policy classes for all authorization logic.

---

### 4.2 No Rate Limiting on Authenticated Routes

**Description:** Only `auth/register` and `auth/login` have `throttle:5,1` applied. All other endpoints (SOS create, blog comments, community posts, reports) have no rate limiting, making them vulnerable to abuse.

**Recommendation:**
```php
// routes/api_v1.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // All authenticated routes
});

// Stricter for SOS
Route::post('/sos', [SosController::class, 'store'])
    ->middleware('throttle:3,1'); // Max 3 SOS per minute
```

---

### 4.3 Pet Ownership Not Validated in SOS Form Request

**File:** `app/Http/Requests/StoreSosRequest.php`

**Description:** The `pet_id` field validates `exists:pets,id` but does not verify the pet belongs to the authenticated user. A user could create an SOS for another user's pet.

**Fix:**
```php
'pet_id' => [
    'required',
    'integer',
    Rule::exists('pets', 'id')->where('user_id', auth()->id()),
],
```

---

### 4.4 CORS Wildcard Configuration

**File:** `config/cors.php`

**Description:** The default `allowed_origins` is `['*']`, allowing requests from any domain. This should be restricted to known frontend origins in production.

**Fix:**
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
    env('ADMIN_URL', 'http://localhost:3001'),
],
```

---

### 4.5 No HTTPS Enforcement or Security Headers

**Description:** No middleware or configuration enforces:
- HTTPS-only connections
- Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`)
- `Secure` flag on cookies

**Fix:** Add `\App\Http\Middleware\TrustProxies` config and force HTTPS in production:
```php
// AppServiceProvider::boot()
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

---

## 5. Code Quality Issues (P2)

### 5.1 Inconsistent failedValidation Handling

**Description:** Form requests use three different approaches:
1. Some override `failedValidation()` throwing `HttpResponseException`
2. Some throw `ValidationException`
3. Some rely on Laravel's default handler

This causes inconsistent error response formats.

**Fix:** Remove all custom `failedValidation` overrides. The `ApiResponse` trait and Laravel's exception handler should provide consistent responses.

---

### 5.2 Inline Validation in BlogController::storeTag

**Description:** `storeTag()` uses `$request->validate([...])` inline instead of a dedicated Form Request class, inconsistent with all other endpoints.

**Fix:** Create `StoreTagRequest` FormRequest class.

---

### 5.3 Dead/Conflicting Migrations

**Description:** Migration sequence includes pairs like:
- `create_vet_profiles_table.php` followed by `drop_vet_profiles_table.php` followed by another `create_vet_profiles_table.php`

This works but is messy and confusing. Consider squashing migrations for a clean history.

---

### 5.4 Naive Address Parsing

**File:** `app/Services/VetSearchService.php`

**Description:** `extractCityFromAddress()` uses simple comma-splitting which will fail for addresses without commas, non-standard formats, or international addresses.

---

### 5.5 Haversine Formula Locale Sensitivity

**File:** `app/Services/VetSearchService.php`

**Description:** The raw SQL Haversine calculation uses `sprintf('%f', ...)` which is locale-dependent. On systems with comma as decimal separator, this may generate malformed SQL.

**Fix:** Use `sprintf('%.6f', ...)` or bind parameters:
```php
->selectRaw('(6371 * acos(...)) AS distance', [$lat, $lng, $lat])
```

---

### 5.6 Missing Pagination on Guide List

**File:** `app/Http/Controllers/Api/V1/GuideController.php`

**Description:** `guides()` returns all guides without pagination, which could cause memory issues with large datasets.

---

### 5.7 Non-Atomic Vote Count Updates

**File:** `app/Http/Controllers/Api/V1/CommunityController.php`

**Description:** `toggleVote()` recalculates vote counts using `$votable->votes()->count()` after toggling. Under concurrent requests, this can produce incorrect counts.

**Fix:** Use `increment()`/`decrement()` or `DB::raw('upvotes + 1')`.

---

### 5.8 Missing Foreign Key in Blog Post Tags

**Description:** Blog post-tag pivot operations don't validate tag existence before `sync()`, which could fail silently or cause FK constraint errors.

---

## 6. Missing Features (P3)

| # | Feature | Impact | Effort |
|---|---------|--------|--------|
| 1 | **Push Notifications (FCM/APNs)** | SOS alerts are notification-only (database channel), no real push delivery | High |
| 2 | **Password Reset Flow** | No forgot-password or reset-password endpoints | Medium |
| 3 | **File Upload Handling** | Pet photos, vet certificates passed as URLs only, no upload endpoint | Medium |
| 4 | **Vet Profile Update** | No endpoint for vets to update their own profiles after onboarding | Low |
| 5 | **User Profile Update** | No endpoint for users to update name, avatar, phone | Low |
| 6 | **SOS Vet Assignment Logic** | `SosService::findAndAssignVet()` is stub-only, always returns null | High |
| 7 | **Blog Full-Text Search** | No search endpoint for blog posts | Low |
| 8 | **Community Post Edit** | Users can create and delete posts but cannot edit them | Low |

---

## 7. Test Coverage Report

### Test Files Summary

| Test File | Methods | Coverage Area |
|-----------|---------|---------------|
| `AuthTest.php` | 10 | Register, login, me, logout |
| `PetTest.php` | 10 | CRUD, ownership, validation |
| `SosTest.php` | ~8 | Create, status update, active |
| `IncidentTest.php` | ~6 | List, show, ownership |
| `AdminTest.php` | ~8 | Stats, users, SOS, incidents |
| `RoleMiddlewareTest.php` | ~4 | Role enforcement |
| `BlogTest.php` | 35+ | Public/auth/admin blog ops |
| `CommunityTest.php` | 40+ | Public/auth/admin community ops |
| `VetOnboardingTest.php` | 15+ | Registration, verification |
| `GuideTest.php` | 7 | Categories, guides |
| `VetSearchTest.php` | 5 | Search, show |
| **Total** | **~148** | **All 73 endpoints covered** |

### Test Categories

| Category | Count | Description |
|----------|-------|-------------|
| Happy Path | ~40 | Normal successful operations |
| Validation (422) | ~25 | Invalid input, missing fields |
| Auth (401) | ~15 | Unauthenticated access |
| Authorization (403) | ~12 | Wrong role, not owner |
| Not Found (404) | ~10 | Non-existent resources |
| Edge Cases | ~15 | Max limits, duplicates, toggles |
| Business Logic | ~20 | State machines, relationships |
| Pagination | ~8 | Per-page limits, filters |

### Factories Created

| Factory | States | Purpose |
|---------|--------|---------|
| `BlogCategoryFactory` | `inactive()` | Blog categories with active/inactive |
| `BlogPostFactory` | `published()`, `draft()`, `forAuthor()`, `forCategory()` | Blog posts with status |
| `BlogTagFactory` | - | Blog tags |
| `BlogCommentFactory` | `approved()`, `pending()` | Blog comments with approval status |
| `CommunityTopicFactory` | `inactive()` | Community topics |
| `CommunityPostFactory` | `locked()`, `hidden()`, `forUser()`, `forTopic()` | Community posts with states |
| `CommunityReplyFactory` | `forPost()`, `forUser()`, `childOf()` | Nested community replies |

---

## 8. API Endpoint Audit Matrix

### Auth Module (4 routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| POST | `/auth/register` | No | ✅ 5/min | ✅ | ⚠️ Double hash bug |
| POST | `/auth/login` | No | ✅ 5/min | ✅ | ✅ OK |
| GET | `/auth/me` | Yes | ❌ | ✅ | ✅ OK |
| POST | `/auth/logout` | Yes | ❌ | ✅ | ✅ OK |

### Pets Module (5 routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/pets` | Yes | ❌ | ✅ | ✅ OK |
| POST | `/pets` | Yes | ❌ | ✅ | ✅ OK |
| GET | `/pets/{pet}` | Yes | ❌ | ✅ | ✅ OK |
| PUT | `/pets/{pet}` | Yes | ❌ | ✅ | ✅ OK |
| DELETE | `/pets/{pet}` | Yes | ❌ | ✅ | ✅ OK |

### SOS Module (3 routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| POST | `/sos` | Yes | ❌ | ✅ | ⚠️ Pet ownership not validated |
| GET | `/sos/active` | Yes | ❌ | ✅ | ✅ OK |
| PUT | `/sos/{sos}/status` | Yes | ❌ | ✅ | ✅ OK |

### Incidents Module (2 routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/incidents` | Yes | ❌ | ✅ | ✅ OK |
| GET | `/incidents/{incident}` | Yes | ❌ | ✅ | ✅ OK |

### Guides Module (5 routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/emergency-categories` | No | ❌ | ✅ | ✅ OK |
| GET | `/guides` | No | ❌ | ✅ | ⚠️ No pagination |
| GET | `/guides/{guide}` | No | ❌ | ✅ | ✅ OK |
| GET | `/vets` | No | ❌ | ✅ | ✅ OK |
| GET | `/vets/{vet}` | No | ❌ | ✅ | ✅ OK |

### Vet Onboarding (1 route + admin)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| POST | `/vet/register` | No | ❌ | ✅ | ⚠️ Double hash + no rate limit |

### Blog Module (Public: 4, Auth: 2, Admin: 12+)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/blog/categories` | No | ❌ | ✅ | ✅ OK |
| GET | `/blog/posts` | No | ❌ | ✅ | ✅ OK |
| GET | `/blog/posts/{post}` | No | ❌ | ✅ | ✅ OK |
| GET | `/blog/tags` | No | ❌ | ✅ | ✅ OK |
| POST | `/blog/posts/{post}/comments` | Yes | ❌ | ✅ | ❌ Needs rate limiting |
| POST | `/blog/posts/{post}/like` | Yes | ❌ | ✅ | ✅ OK |
| GET | `/admin/blog/categories` | Admin | ❌ | ✅ | ✅ OK |
| POST | `/admin/blog/categories` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/blog/categories/{cat}` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/blog/categories/{cat}` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/blog/posts` | Admin | ❌ | ✅ | ✅ OK |
| POST | `/admin/blog/posts` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/blog/posts/{post}` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/blog/posts/{post}` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/blog/posts/{post}` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/blog/posts/{post}/toggle-publish` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/blog/comments` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/blog/comments/{c}/approve` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/blog/comments/{c}/unapprove` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/blog/comments/{c}` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/blog/tags` | Admin | ❌ | ✅ | ✅ OK |
| POST | `/admin/blog/tags` | Admin | ❌ | ✅ | ⚠️ Inline validation |

### Community Module (Public: 4, Auth: 5, Admin: 7+)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/community/topics` | No | ❌ | ✅ | ✅ OK |
| GET | `/community/posts` | No | ❌ | ✅ | ✅ OK |
| GET | `/community/posts/{post}` | No | ❌ | ✅ | ✅ OK |
| GET | `/community/posts/{post}/replies` | No | ❌ | ✅ | ✅ OK |
| POST | `/community/posts` | Yes | ❌ | ✅ | ✅ OK |
| DELETE | `/community/posts/{post}` | Yes | ❌ | ✅ | ✅ OK |
| POST | `/community/posts/{post}/replies` | Yes | ❌ | ✅ | ✅ OK |
| DELETE | `/community/replies/{reply}` | Yes | ❌ | ✅ | ✅ OK |
| POST | `/community/votes` | Yes | ❌ | ✅ | ⚠️ Non-atomic count |
| POST | `/community/reports` | Yes | ❌ | ✅ | ✅ OK |
| POST | `/admin/community/topics` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/community/topics/{t}` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/community/posts` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/community/posts/{p}/lock` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/community/posts/{p}/toggle-visibility` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/community/posts/{p}` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/community/replies/{r}` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/community/reports` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/community/reports/{r}` | Admin | ❌ | ✅ | ✅ OK |
| DELETE | `/admin/community/reports/{r}/dismiss` | Admin | ❌ | ✅ | ✅ OK |

### Admin Module (5+ routes)
| Method | Endpoint | Auth | Rate Limited | Tested | Status |
|--------|----------|------|-------------|--------|--------|
| GET | `/admin/stats` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/users` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/sos` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/incidents` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/vets/unverified` | Admin | ❌ | ✅ | ✅ OK |
| PUT | `/admin/vets/{vet}/verify` | Admin | ❌ | ✅ | ✅ OK |
| GET | `/admin/vets/{vet}/history` | Admin | ❌ | ✅ | ✅ OK |

---

## 9. Performance Concerns

| Area | Issue | Impact | Fix |
|------|-------|--------|-----|
| **N+1 Queries** | Blog posts load tags relation in loop if not eager-loaded | Slow with many posts | Use `->with('tags', 'author', 'category')` |
| **No Caching** | Emergency categories/guides fetched from DB on every request | Unnecessary load | Add `Cache::remember()` for static data |
| **Guide Pagination** | All guides returned without pagination | Memory exhaustion | Add `->paginate(20)` |
| **Vote Count Recalc** | Full COUNT query on every vote toggle | O(n) per vote | Use `increment()`/`decrement()` |
| **Dashboard Stats** | 7+ COUNT queries on every `/admin/stats` call | Slow with large datasets | Cache stats for 5 minutes |
| **Vet Search** | Haversine formula calculated for ALL profiles | Slow with 10k+ vets | Add geospatial index (MySQL spatial) |

---

## 10. Recommendations Summary

### Immediate (Before Production - P0/P1)

1. **Fix double password hashing** - Remove `Hash::make()` from `AuthController::register()` and `VetOnboardingService::registerVet()`, let the `'hashed'` cast handle it
2. **Decide on email verification** - Either enforce MustVerifyEmail or remove the contract
3. **Add rate limiting** to all authenticated routes (60/min default, 3/min for SOS)
4. **Fix pet ownership validation** in `StoreSosRequest` - validate `pet_id` belongs to authenticated user
5. **Restrict CORS** to frontend domains only
6. **Add HTTPS enforcement** in production

### Next Sprint (P2)

7. **Standardize failedValidation** - Remove custom overrides, rely on global handler
8. **Create StoreTagRequest** - Replace inline validation in `storeTag()`
9. **Squash dead migrations** - Clean up drop/recreate migration pairs
10. **Fix Haversine locale** - Use parameterized queries
11. **Add pagination** to guide list endpoint
12. **Make vote counts atomic** - Use `increment()`/`decrement()` DB calls
13. **Use Policy authorize()** - Replace manual ownership checks in controllers
14. **Add N+1 prevention** - Eager load relationships in all list endpoints

### Roadmap (P3)

15. **Push notifications** - Integrate FCM/APNs for SOS alerts
16. **Password reset flow** - Add forgot/reset password endpoints
17. **File uploads** - Add image upload endpoints for pets, vet certificates
18. **Profile updates** - Add user and vet profile update endpoints
19. **SOS vet assignment** - Implement real matching algorithm
20. **Blog search** - Add full-text search endpoint
21. **Community post edit** - Allow post authors to edit their posts
22. **Caching layer** - Add Redis caching for frequently accessed data
23. **API versioning** - Prepare for v2 with proper deprecation strategy

---

## Appendix A: Files Delivered

| File | Description |
|------|-------------|
| `tests/Feature/Api/V1/BlogTest.php` | 35+ blog module tests |
| `tests/Feature/Api/V1/CommunityTest.php` | 40+ community module tests |
| `tests/Feature/Api/V1/VetOnboardingTest.php` | 15+ vet onboarding tests |
| `tests/Feature/Api/V1/GuideTest.php` | 7 guide/category tests |
| `tests/Feature/Api/V1/VetSearchTest.php` | 5 vet search tests |
| `database/factories/BlogCategoryFactory.php` | Blog category factory |
| `database/factories/BlogPostFactory.php` | Blog post factory with states |
| `database/factories/BlogTagFactory.php` | Blog tag factory |
| `database/factories/BlogCommentFactory.php` | Blog comment factory |
| `database/factories/CommunityTopicFactory.php` | Community topic factory |
| `database/factories/CommunityPostFactory.php` | Community post factory |
| `database/factories/CommunityReplyFactory.php` | Community reply factory |
| `postman/PetSathi_QA_Complete_Collection.json` | Postman collection (73 endpoints) |
| `QA_AUDIT_REPORT.md` | This report |

## Appendix B: How to Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=BlogTest
php artisan test --filter=CommunityTest
php artisan test --filter=VetOnboardingTest

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run with verbose output
php artisan test -v
```

## Appendix C: How to Import Postman Collection

1. Open Postman
2. Click **Import** > **Upload Files**
3. Select `postman/PetSathi_QA_Complete_Collection.json`
4. Collection variables (`base_url`, `auth_token`, etc.) are pre-configured
5. Run "01. Auth Module" first to populate `auth_token`
6. Run "Admin: Login as Admin" to populate `admin_token` (requires admin user in DB)
7. Run tests sequentially per folder

---

*Report generated by Senior QA Audit - PetSathi Backend Project*
