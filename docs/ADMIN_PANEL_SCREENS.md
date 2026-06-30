# Admin Panel — Screen Specifications

Web dashboard for platform operations. All screens require `role:admin` + `verified` middleware.

For request/response shapes see [API.md](API.md). For domain detail see [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Layout

| Region | Content |
|---|---|
| **Top bar** | Logo, search (global users/vets/appointments), notifications bell, admin profile menu (logout). |
| **Left sidebar** | Dashboard · Vet Approval · Users · Bookings · SOS & Incidents · Payouts & Finance · Subscriptions · Content CMS · Analytics · Audit Log · Settings |
| **Main** | Screen content. |

Auth header on every protected call: `Authorization: Bearer {token}`.

---

## 1. Dashboard

**Purpose:** Operational pulse at a glance.

### KPI cards (4–6 across the top)

| Card | Source |
|---|---|
| **Total users** | `GET /admin/stats` → `total_users` |
| **Active vets** | `total_active_vets` |
| **Bookings today** | `bookings_today` |
| **Revenue today** | `GET /admin/revenue` → `revenue_today` |
| **Open SOS** | `GET /admin/sos?status=pending` (count via `pagination.total`) |
| **Pending vet approvals** | `GET /admin/vets/unverified` count |

Each KPI card shows current value + delta vs yesterday (small green/red).

### Charts row

| Chart | Source | X-axis | Y-axis |
|---|---|---|---|
| **Bookings by day** (last 30d) | `GET /admin/metrics/time-series?metric=appointments&days=30` | date | count |
| **Revenue trend** | `GET /admin/metrics/time-series?metric=revenue&days=30` | date | ₹ |
| **City growth (top 10)** | `GET /admin/metrics/geo` | city | active users |
| **Active vets by city** | `GET /admin/metrics/geo?type=vets` | city | active vets |

### Recent activity feed

- From `GET /admin/recent-activity?per_page=20`.
- Each row: timestamp · actor · action · target · "View" link.

### Quick actions row

- **Approve next vet** → routes to **Vet Approval** with first pending preselected
- **Process payouts** → **Payouts**
- **Resolve flagged review** → **Reviews moderation**
- **Open audit log** → **Audit Log**

**APIs on mount:**
- `GET /admin/stats`
- `GET /admin/metrics/time-series` (×2)
- `GET /admin/metrics/geo`
- `GET /admin/recent-activity`
- `GET /admin/revenue?range=today`

---

## 2. Vet Approval Queue

**Purpose:** Trust + verification workflow.

### Tabs

- **Pending** (status: pending)
- **Needs info** (status: needs_information)
- **Approved**
- **Rejected**
- **Suspended**

Driven by `GET /admin/vets?status={tab}`.

### Table columns

| Column | Source |
|---|---|
| Vet name | `vet.vet_name` |
| Clinic name | `vet.clinic_name` |
| Email | `vet.email` |
| City | `vet.city` |
| Applied at | `vet.created_at` |
| Specialization | `vet.specialization` |
| Documents complete? | derived from `document_status` (✅ / ⚠️ missing) |
| Status pill | `vet.vet_status` |
| Actions | "Review" → opens detail panel |

Filters above table: city, specialization, services, search by name/email/license.

### Detail panel (drawer or detail page)

#### Header

- Vet photo, name, clinic, applied date, status pill

#### Sections

1. **Profile review** — `GET /admin/vets/{uuid}/review` returns:
   - Profile completeness checklist (each field ✅ or ❌)
   - Document checklist (license, degree, id_proof, clinic_registration: each with status + view button)
   - Issues array (machine-readable list of missing fields)

2. **Documents**
   - For each: status pill, **View** button (opens `GET /admin/vets/{uuid}/documents/{type}` signed URL in new tab), **Notes** field for admin.

3. **Verification history**
   - From `GET /admin/vets/{uuid}/history`. Timeline of every state change with actor + reason + notes.

4. **Action bar** (sticky bottom)
   - **Approve** (green, primary) — disabled if profile/docs incomplete → `PUT /admin/vets/{uuid}/approve` body `{ notes? }`
   - **Reject** (red) → modal with required `reason` → `PUT /admin/vets/{uuid}/reject` body `{ reason }`
   - **Suspend** (orange, only if currently approved) → modal with `reason` → `PUT /admin/vets/{uuid}/suspend`
   - **Reactivate** (only if suspended) → `PUT /admin/vets/{uuid}/reactivate`
   - **Request more info** → modal with `reason` text → `PUT /admin/vets/{uuid}/request-info`
   - **Verify** (mark KYC docs verified separately from approve) → `PUT /admin/vets/{uuid}/verify`

**Validation: cannot approve if review returns missing fields/documents.**

**APIs:**
- `GET /admin/vets`, `GET /admin/vets/unverified`
- `GET /admin/vets/{uuid}`
- `GET /admin/vets/{uuid}/review`
- `GET /admin/vets/{uuid}/history`
- `GET /admin/vets/{uuid}/documents/{type}`
- All approval/rejection/etc. PUT/PATCH endpoints

---

## 3. Users

**Purpose:** Manage user accounts.

### Filters / search

- Search by name / email / phone
- Role filter: all / user / vet / admin
- Status: active / suspended

### Table columns

| Column | Source |
|---|---|
| Avatar + name | `user.name`, `user.avatar` |
| Email | `user.email` |
| Phone | `user.phone` |
| Role pill | `user.role` |
| City | `user.city` |
| Joined | `user.created_at` |
| Last login | `user.last_login_at` |
| Actions | View / Change role |

From `GET /admin/users?per_page=25&search={q}&role={r}`.

### User detail (drawer)

- Profile data
- Pets count + tap → list (calls `GET /admin/pets?user_id={id}`)
- Appointments count
- Subscription status
- Audit trail (filtered `GET /admin/audit-logs?user_id={id}`)

### Actions

- **Change role** (modal with radio user/vet/admin + confirmation typing) → `PUT /admin/users/{id}/role` body `{ role }`
  - **Caution copy:** "Granting admin gives full access. Are you sure?"
- **Reset password** (sends magic email — calls `POST /auth/forgot-password` server-side as the user)
- **Soft-delete** (future)

**APIs:** `GET /admin/users`, `PUT /admin/users/{id}/role`, `GET /admin/pets`, `GET /admin/audit-logs`.

---

## 4. Bookings (Appointments + SOS + Incidents oversight)

**Purpose:** Cross-cutting operational view of every booking and emergency.

### Tabs

- **Appointments**
- **SOS Requests**
- **Incidents**

### Appointments tab

Filters: status, date range, vet, user, type (clinic/home/online), city.

Table:

| Column | Source |
|---|---|
| When | `scheduled_at` |
| Pet | `appointment.pet.name` |
| Owner | `user.name` |
| Vet | `vet_profile.vet_name` |
| Clinic | `vet_profile.clinic_name` |
| Type | `appointment_type` |
| Status | `status` |
| Fee | `fee_amount` |
| Payment status | `payment_status` |
| Action | View / Cancel / Override status (admin only) |

Bulk actions: export CSV (future).

From `GET /admin/appointments?per_page=25&...filters`.

#### Appointment detail (drawer)

- Full timeline (status changes)
- Owner contact
- Vet contact
- Visit record (if exists)
- Payment record + linked Razorpay IDs
- **Refund** action → confirms then `POST /payments/{uuid}/refund`
- **Cancel & refund** combined action

### SOS Requests tab

Filters: status, date range, city, urgency.

| Column | Source |
|---|---|
| Created | `sos.created_at` |
| Owner | `user.name` |
| Pet | `pet.name` (if linked) |
| Location | `latitude, longitude` (with link to map) |
| Urgency | `urgency` |
| Assigned vet | `vet_profile.vet_name` (or — if none) |
| Status | `status` |
| Notes | `notes` |

From `GET /admin/sos`.

#### SOS detail

- Live map with SOS marker + assigned vet trail (if any)
- Status timeline
- Incident logs (`GET /admin/incidents?sos_id={id}`)
- Manual reassignment (future)
- Mark resolved (`PUT /sos/{uuid}/status`)

### Incidents tab

Generic audit feed of all incident events. From `GET /admin/incidents`. Detail via `GET /admin/incidents/{uuid}`.

---

## 5. Payouts & Finance

**Purpose:** Money-out operations + revenue oversight.

### Tabs

- **Pending Payouts**
- **All Payments**
- **Revenue Dashboard**
- **Refunds**

### Pending Payouts tab

From `GET /admin/payouts/pending`. Table:

| Column | Source |
|---|---|
| Vet name | `vet_profile.vet_name` |
| Clinic | `clinic_name` |
| Pending payout | `wallet.pending_payout` |
| Available balance | `wallet.balance` |
| Last payout | latest `payout_completed` transaction date |
| Action | **Process payout** |

### Process payout modal

| Field | Type | Required |
|---|---|---|
| **Amount** | number | yes — ≤ pending |
| **Payment method** | dropdown | yes |
| **UPI ID** / **Bank account** | text | yes |
| **Settlement reference** | text | opt — Razorpay payout id, NEFT ref, etc. (stored in description for audit) |
| **Notes** | text | opt |

Submit → `POST /admin/payouts/{vet_uuid}/process` body `{ amount, notes? }`.

### All Payments tab

From `GET /admin/payments?per_page=25&...filters`.

Filters: payment status (paid/created/failed/refunded/partially_refunded), payment_mode (online/offline), date range, payable_type (appointment/sos_request/subscription).

| Column | Source |
|---|---|
| Date | `paid_at \|\| created_at` |
| User | `user.name` |
| Vet | `vet_profile.vet_name` |
| Type | `payable_type` |
| Mode | `payment_mode` |
| Amount | `amount` |
| Platform fee | `platform_fee` |
| Vet payout | `vet_payout_amount` |
| Status | `payment_status` |
| Razorpay ID | `razorpay_payment_id` |
| Action | View / Refund |

### Revenue Dashboard tab

From `GET /admin/revenue?range={today|week|month|year|custom}`.

Cards:
- Total revenue
- Platform fees collected
- Total commission
- Total vet payouts (pending + completed)
- Pending payments count
- Failed payments count
- Net revenue (paid − refunded)

Charts: revenue by day, by city, by appointment_type.

### Refunds tab

Filtered list (`GET /admin/payments?status=refunded,partially_refunded`). Each row shows refund reason + linked vet wallet impact.

---

## 6. Content CMS

**Purpose:** Manage blog, community moderation, guides, ad banners, subscriptions, reviews moderation.

### Sub-tabs / sub-pages in left nav

#### 6.1 Blog

- **Posts** — table with cover, title, category, status (published/draft), author, comments count.
  - Create / edit (rich-text editor) → `POST /admin/blog/posts`, `PUT /admin/blog/posts/{uuid}`
  - **Toggle publish** → `PUT /admin/blog/posts/{uuid}/toggle-publish`
  - **Delete** → `DELETE /admin/blog/posts/{uuid}`
- **Comments moderation** — pending comments → **Approve** (`PUT /admin/blog/comments/{uuid}/approve`) / **Delete**.
- **Categories** — CRUD via `/admin/blog/categories`.
- **Tags** — CRUD via `/admin/blog/tags`.

Form fields for a post:

| Field | Type | Required |
|---|---|---|
| **Title** | text | yes |
| **Slug** | text (auto-generated from title, editable) | yes |
| **Cover image** | file | opt |
| **Excerpt** | textarea | opt |
| **Body** | rich text / markdown | yes |
| **Category** | dropdown | yes |
| **Tags** | chip multi-select | opt |
| **Author** | dropdown of admin users | yes (default: current admin) |
| **Status** | radio (Draft / Published) | yes |
| **Featured** | toggle | opt |
| **Published at** | datetime | auto on publish |

#### 6.2 Community moderation

- **Topics** — CRUD via `/admin/community/topics`.
- **Posts** — list with **Lock** (`PUT /admin/community/posts/{uuid}/lock`), **Toggle visibility** (`/toggle-visibility`), **Delete** (`DELETE /admin/community/posts/{uuid}`).
- **Reports queue** — from `GET /admin/community/reports?status=pending`.
  - Each report: target type (post/reply), reporter, reason, target preview.
  - Action modal: dismiss / remove content → `PUT /admin/community/reports/{uuid}` body `{ action, notes }`.

#### 6.3 Emergency Guides

- Categories: CRUD `/admin/guides/categories`.
- Articles: CRUD `/admin/guides`. Each: title, body, category, severity, image.

#### 6.4 Ad Banners

- Table from `GET /admin/ad-banners`. Each row: image preview, title, position, priority, active toggle, dates.
- Create/edit form:

| Field | Type | Required |
|---|---|---|
| **Title** | text | yes |
| **Image** | file | yes |
| **Link URL** | url | opt |
| **Position** | dropdown (home_top, search_top, vet_detail, …) | yes |
| **Priority** | number | yes (higher = first) |
| **Is active** | toggle | yes |
| **Starts at** | datetime | opt |
| **Ends at** | datetime | opt |

`POST /admin/ad-banners`, `PUT /admin/ad-banners/{uuid}`, `DELETE /admin/ad-banners/{uuid}`.

#### 6.5 Subscription Plans

Table from `GET /admin/subscription-plans`. Form:

| Field | Type | Required |
|---|---|---|
| **Name** | text | yes |
| **Type** | dropdown (basic / premium / pro) | yes |
| **Price** | number (₹) | yes |
| **Duration (days)** | number | yes |
| **Features** | textarea (one per line) → array | yes |
| **Description** | textarea | opt |
| **Is active** | toggle | yes |

`POST /admin/subscription-plans`, `PUT /admin/subscription-plans/{id}`, `DELETE /admin/subscription-plans/{id}`.

#### 6.6 Reviews moderation

- **Flagged Reviews** queue — from `GET /admin/reviews/flagged`.
  - Each: reviewer, vet, rating, comment, flag reason.
  - Actions:
    - **Resolve** (review stays) → `PUT /admin/reviews/{uuid}/resolve`
    - **Dismiss flag** → `PUT /admin/reviews/{uuid}/dismiss`
    - **Delete review** → `DELETE /admin/reviews/{uuid}`

---

## 7. Analytics

**Purpose:** Deeper analytics than the Dashboard.

### Top filters

- Date range (today / 7d / 30d / 90d / custom)
- City filter (multi)
- Compare-to-previous-period toggle

### Sections

1. **Booking funnel**
   - Visits → searches → vet detail views → booking initiated → booked → paid → completed.
   - Source: aggregated metrics; bars + drop-off %.
   - (Future endpoint — currently approximate from `GET /admin/metrics/time-series`.)

2. **Vet performance**
   - Top vets by bookings, revenue, rating.
   - From `GET /admin/vets?sort=bookings`.

3. **City growth**
   - Map heatmap from `GET /admin/metrics/geo`.
   - Table: city, users, vets, bookings, revenue.

4. **Conversion**
   - Sign-ups → first booking
   - First booking → repeat
   - Subscription conversion rate

5. **Realtime widget**
   - Active SOS, active consults, online vets count.
   - WebSocket via Reverb → `App.Models.User.{adminId}` private channel (future) OR poll every 30 s.

6. **Export**
   - CSV download of any chart's underlying data (future endpoint).

**APIs:**
- `GET /admin/metrics/time-series`
- `GET /admin/metrics/geo`
- `GET /admin/stats`

---

## Cross-cutting

### Audit Log (separate sidebar item)

- Filterable list from `GET /admin/audit-logs?per_page=50&user_id&model_type&action&date_range`.
- Columns: when, actor, model + id, action (created/updated/deleted/approved/...), changes summary, IP.
- Tap → JSON diff view.

### Settings (sidebar item)

- Platform settings (commission rate, default fees, holidays) — currently env-driven; UI is future.
- Admin team management (invite admins, change roles via Users screen).

### Top-bar global search

- Searches across users, vets, appointments by ID/email/name.
- Hits `GET /admin/users`, `/admin/vets`, `/admin/appointments` in parallel.

### Permissions (current RBAC)

- All admin endpoints require `role:admin`.
- Future: super-admin role for irreversible actions (delete, role-grant). Not yet enforced — gate in UI for now.

### Empty / loading / error states

- Skeleton tables on load.
- "No results" with filter-clear CTA.
- Error banners with retry.
- 401 → kick to login.
- 403 → "You don't have permission" with link back to Dashboard.
