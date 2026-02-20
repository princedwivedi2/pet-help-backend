# Pet Help API v1 — Reference Documentation

> **Base URL:** `{APP_URL}/api/v1`  
> **Auth:** Bearer token via Laravel Sanctum  
> **Format:** All responses follow the envelope `{ success, message, data, errors }`

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Pets](#2-pets)
3. [SOS Requests](#3-sos-requests)
4. [Incidents](#4-incidents)
5. [Emergency Guides (public)](#5-emergency-guides)
6. [Vets (public)](#6-vets)
7. [Admin](#7-admin)
8. [Error Codes](#8-error-codes)

---

## 1. Authentication

### POST `/auth/register`

> **Rate limit:** 5 requests / minute

| Field                   | Type   | Required | Rules                                  |
|-------------------------|--------|----------|----------------------------------------|
| `name`                  | string | yes      | max:255                                |
| `email`                 | string | yes      | valid email, unique                    |
| `password`              | string | yes      | min:8, 1 uppercase, 1 number, confirmed|
| `password_confirmation` | string | yes      | must match `password`                  |

**201 Created:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": { "id": 1, "name": "...", "email": "..." },
    "token": "1|abc..."
  },
  "errors": null
}
```

---

### POST `/auth/login`

> **Rate limit:** 5 requests / minute

| Field      | Type   | Required |
|------------|--------|----------|
| `email`    | string | yes      |
| `password` | string | yes      |

**200 OK:** Returns `user` + `token` (same structure as register).  
**401:** Invalid credentials.

---

### GET `/auth/me`

> **Auth required**

Returns the authenticated user object.

**200 OK:**
```json
{ "success": true, "data": { "user": { "id": 1, "name": "...", "email": "...", "role": "user" } } }
```

---

### POST `/auth/logout`

> **Auth required**

Revokes the current access token.

**200 OK:** `{ "success": true, "message": "Logout successful" }`

---

## 2. Pets

> All endpoints require **Auth: Bearer token**.

### GET `/pets`

List authenticated user's pets (ordered by name).

**200 OK:**
```json
{ "data": { "pets": [ { "id": 1, "name": "Buddy", "species": "dog", ... } ] } }
```

---

### POST `/pets`

Create a new pet. Max **10 pets per user**.

| Field           | Type   | Required | Rules                                                        |
|-----------------|--------|----------|--------------------------------------------------------------|
| `name`          | string | yes      | max:255                                                      |
| `species`       | string | yes      | in: dog, cat, bird, rabbit, hamster, fish, reptile, other    |
| `breed`         | string | no       | max:255                                                      |
| `birth_date`    | date   | no       | before: today                                                |
| `weight_kg`     | float  | no       | min:0                                                        |
| `photo_url`     | string | no       | valid URL                                                    |
| `medical_notes` | string | no       | max:5000                                                     |

**201 Created:** Returns `{ "data": { "pet": { ... } } }`  
**422:** Validation error or max limit reached.

---

### GET `/pets/{id}`

Show a specific pet (must belong to authenticated user).

**200 OK** / **404 Not Found**

---

### PUT `/pets/{id}`

Update a pet. Same fields as create, all optional (`sometimes` rule).

**200 OK** / **404 Not Found**

---

### DELETE `/pets/{id}`

Soft-delete a pet.

**200 OK** / **404 Not Found**

---

## 3. SOS Requests

> All endpoints require **Auth: Bearer token**.

### POST `/sos`

Create an emergency SOS request.

**Business rules:**
- Max **5 SOS per hour** (rate-limited per user)
- Only **one active SOS** at a time (pending/acknowledged/in_progress)
- `pet_id` must belong to authenticated user (if provided)
- Vet notification is automatically dispatched (non-blocking)
- An incident log is auto-created with type `emergency`

| Field            | Type   | Required | Rules                                                         |
|------------------|--------|----------|---------------------------------------------------------------|
| `pet_id`         | int    | no       | exists in `pets`, must be own pet                             |
| `latitude`       | float  | yes      | numeric                                                       |
| `longitude`      | float  | yes      | numeric                                                       |
| `address`        | string | no       | max:500                                                       |
| `description`    | string | yes      | min:10                                                        |
| `emergency_type` | string | no       | in: injury, illness, poisoning, accident, breathing, seizure, other |

**201 Created:**
```json
{
  "data": {
    "sos": { "uuid": "...", "status": "pending", ... },
    "notification": { "vets_notified": 3, "vets": [...] }
  }
}
```

**422:** Active SOS exists / validation error  
**429:** Rate limit exceeded (5/hour)

---

### GET `/sos/active`

Get the user's currently active SOS request (if any).

**200 OK:**
```json
{ "data": { "sos": { ... } } }   // or { "sos": null }
```

---

### PUT `/sos/{uuid}/status`

Update an SOS request's status.

| Field              | Type   | Required | Rules                      |
|--------------------|--------|----------|----------------------------|
| `status`           | string | yes      | in: cancelled, completed   |
| `resolution_notes` | string | no       | nullable                   |

**State transitions:**
| Current Status | → `cancelled` | → `completed` |
|----------------|---------------|----------------|
| pending        | ✅            | ❌             |
| acknowledged   | ✅            | ✅             |
| in_progress    | ❌            | ✅             |
| completed      | ❌            | ❌             |
| cancelled      | ❌            | ❌             |

**200 OK** / **404** / **422** (invalid transition)

---

## 4. Incidents

> All endpoints require **Auth: Bearer token**.

### GET `/incidents`

List authenticated user's incident logs with filtering and pagination.

| Param      | Type   | Required | Rules                                       |
|------------|--------|----------|---------------------------------------------|
| `pet_id`   | int    | no       | must be own pet                             |
| `status`   | string | no       | in: open, in_treatment, resolved, follow_up_required |
| `from_date`| date   | no       |                                             |
| `to_date`  | date   | no       |                                             |
| `per_page` | int    | no       | 1-50, default: 15                           |

**200 OK:**
```json
{
  "data": {
    "incidents": [...],
    "pagination": { "current_page": 1, "last_page": 2, "per_page": 15, "total": 25 }
  }
}
```

---

### GET `/incidents/{uuid}`

Show a specific incident (must belong to authenticated user).

**200 OK** / **404 Not Found**

---

## 5. Emergency Guides

> **Public** — no authentication required.

### GET `/emergency-categories`

List all emergency categories.

### GET `/guides`

List all emergency guides.

### GET `/guides/{id}`

Show a specific guide.

---

## 6. Vets

> **Public** — no authentication required.

### GET `/vets`

List active vet profiles. Supports query params:
- `latitude`, `longitude` — for distance-based sorting
- `radius_km` — filter radius (default: 10)
- `emergency_only` — boolean
- `sort_by` — `distance` | `rating`

### GET `/vets/{uuid}`

Show a specific vet profile with availability schedule.

---

## 7. Admin

> All endpoints require **Auth: Bearer token** + **Role: admin**.  
> Regular users and vets receive **403 Forbidden**.

### GET `/admin/stats`

Dashboard summary statistics.

**200 OK:**
```json
{
  "data": {
    "stats": {
      "total_users": 150,
      "total_pets": 320,
      "active_sos": 2,
      "total_sos": 85,
      "total_incidents": 120,
      "sos_by_status": { "pending": 1, "completed": 70, "cancelled": 14 }
    }
  }
}
```

---

### GET `/admin/users`

List all users with counts.

| Param     | Type   | Required |
|-----------|--------|----------|
| `role`    | string | no       | filter by role: user, vet, admin |
| `search`  | string | no       | name or email substring          |
| `per_page`| int    | no       | 1-100, default: 20              |

---

### PUT `/admin/users/{id}/role`

Update a user's role. **Cannot change own role.**

| Field  | Type   | Required | Rules               |
|--------|--------|----------|----------------------|
| `role` | string | yes      | in: user, vet, admin |

---

### GET `/admin/sos`

List all SOS requests (including soft-deleted).

| Param      | Type   | Required |
|------------|--------|----------|
| `status`   | string | no       |
| `from_date`| date   | no       |
| `to_date`  | date   | no       |
| `per_page` | int    | no       | 1-100, default: 20 |

---

### GET `/admin/incidents`

List all incident logs.

| Param          | Type   | Required |
|----------------|--------|----------|
| `status`       | string | no       |
| `incident_type`| string | no       |
| `per_page`     | int    | no       | 1-100, default: 20 |

---

## 8. Error Codes

| Status | Meaning                          |
|--------|----------------------------------|
| 200    | OK                               |
| 201    | Created                          |
| 401    | Unauthenticated (missing/invalid token) |
| 403    | Forbidden (insufficient role)    |
| 404    | Resource not found               |
| 405    | Method not allowed               |
| 422    | Validation error                 |
| 429    | Too many requests                |
| 500    | Internal server error            |

**Error envelope:**
```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": { "field": ["Error message"] }
}
```

---

## Migration Notes

After pulling these changes, run:
```bash
php artisan migrate
```

New migrations:
- `add_role_to_users_table` — adds `role` column (default: `'user'`, values: `user|vet|admin`)
- `create_notifications_table` — required for database notification channel
