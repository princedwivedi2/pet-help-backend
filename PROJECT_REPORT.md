# PETSATHI — Pet Care and Veterinary Assistance Platform

## Project Report

---

## Table of Contents

1. [Project Title](#1-project-title)
2. [Abstract / Overview](#2-abstract--overview)
3. [Problem Statement](#3-problem-statement)
4. [Proposed Solution](#4-proposed-solution)
5. [Objectives of the System](#5-objectives-of-the-system)
6. [Target Users](#6-target-users)
7. [System Architecture](#7-system-architecture)
8. [Technology Stack](#8-technology-stack)
9. [Core Features](#9-core-features)
10. [API-Driven Design Approach](#10-api-driven-design-approach)
11. [Security Considerations](#11-security-considerations)
12. [User Flow & Modules](#12-user-flow--modules)
13. [Future Scope & Enhancements](#13-future-scope--enhancements)
14. [Challenges & Considerations](#14-challenges--considerations)
15. [Conclusion](#15-conclusion)

---

## 1. Project Title

**PETSATHI — Pet Care and Veterinary Assistance Platform**

A comprehensive digital platform that connects pet owners with registered veterinarians, provides emergency medical guidance, and facilitates real-time SOS assistance for domestic animals.

---

## 2. Abstract / Overview

PETSATHI is a multi-platform pet healthcare system designed to bridge the gap between pet owners and veterinary professionals. The platform enables pet owners to register and manage their pets' health profiles, access curated emergency medical guides, locate nearby veterinary clinics using geolocation, and raise real-time SOS requests during pet health emergencies.

The system is composed of three main components: a mobile application serving as the primary user interface for pet owners and veterinarians, a web-based administration panel for platform management, and a Laravel-based RESTful backend that powers all data operations, authentication, and business logic. Communication between the client applications and the server follows a standardized API-driven architecture with token-based authentication and role-based access control.

The backend exposes a versioned REST API (v1) that handles user registration and authentication, pet profile management, emergency guide retrieval, veterinary search with distance calculation, SOS request lifecycle management, incident logging, and administrative operations. All API responses follow a consistent envelope format (`{ success, message, data, errors }`) to ensure predictable client-side handling.

---

## 3. Problem Statement

Pet ownership in urban and semi-urban regions has increased substantially, yet accessible and timely veterinary care remains a persistent challenge for many pet owners. The following issues define the problem space this project addresses:

1. **Lack of Centralized Information:** Pet owners often lack a single reliable source for emergency first-aid guidance specific to animal species. Information is scattered across forums, social media, and general-purpose websites, leading to delayed or incorrect responses during health emergencies.

2. **Difficulty Locating Nearby Veterinary Clinics:** In urgent situations, pet owners frequently struggle to identify nearby veterinary facilities, particularly those offering emergency or after-hours services. Existing mapping tools do not filter for veterinary-specific attributes such as accepted species, emergency availability, or operating hours.

3. **Absence of a Structured Emergency Request System:** When a pet faces a medical emergency, there is no standardized digital mechanism for pet owners to raise an alert, track its status, or receive acknowledgement from nearby veterinary professionals. Communication typically relies on phone calls, which may fail during high-stress scenarios.

4. **Fragmented Health Records:** Pet health records, including vaccination history, past surgeries, medication logs, and incident reports, are typically maintained on paper or scattered across multiple veterinary clinics. This fragmentation hampers continuity of care and complicates diagnosis during subsequent visits.

5. **Limited Administrative Oversight:** Platforms that do exist in this space often lack administrative tools to verify veterinary credentials, monitor platform activity, manage user reports, or enforce quality standards across the system.

These challenges collectively result in delayed treatment, preventable complications, and an overall lack of confidence among pet owners in their ability to respond effectively to their pets' healthcare needs.

---

## 4. Proposed Solution

PETSATHI addresses the identified problems through an integrated digital platform with the following solution components:

### 4.1 Unified Pet Health Management

The platform provides a digital pet profile system where owners can register up to 10 pets per account. Each profile stores the pet's species, breed, birth date, weight, photograph, and medical notes. This consolidated view ensures that relevant health information is immediately accessible during consultations or emergencies.

### 4.2 Curated Emergency Guidance Library

A structured library of emergency guides is organized into categories (e.g., poisoning, injuries, breathing difficulties, seizures). Each guide includes a severity level classification (low, medium, high, critical), applicable species tags, estimated reading time, and detailed step-by-step instructions. Guides are maintained and published by administrators, ensuring content accuracy and medical validity.

### 4.3 Geolocation-Based Veterinary Search

The system implements a Haversine-formula-based distance calculation to identify and rank veterinary clinics by proximity to the user's current location. Search results can be filtered by emergency availability, 24-hour operation status, and currently-open schedule. The default search radius is 10 kilometres, and results are sortable by distance or clinic rating.

### 4.4 Real-Time SOS Request System

Pet owners can initiate SOS emergency requests that capture their GPS coordinates, a description of the emergency, the emergency type (injury, illness, poisoning, accident, breathing difficulty, seizure, or other), and optionally link the request to a specific pet profile. The system manages the full request lifecycle through defined states: pending, acknowledged, in-progress, completed, and cancelled. Each SOS request automatically generates a corresponding incident log for record-keeping. Notifications are dispatched to the user at each status transition.

### 4.5 Incident Logging and Medical History

All emergency events and veterinary interactions are recorded as incident logs tied to the user, pet, associated SOS request, and attending veterinary profile. Incident types include emergency, routine visit, vaccination, surgery, medication, and other. This creates a chronological medical history that supports continuity of care across different veterinary providers.

### 4.6 Administrative Control Panel

A dedicated admin module provides dashboard statistics, user management (with role assignment), oversight of all SOS requests (including soft-deleted records), and access to the complete incident log across the platform. Administrators can assign roles (user, vet, admin) and manage the overall health of the platform.

---

## 5. Objectives of the System

The primary objectives of the PETSATHI platform are:

1. **To provide a centralized pet health management system** that allows pet owners to maintain digital health profiles, medical notes, and historical records for their pets in a single accessible location.

2. **To deliver accurate and categorized emergency guidance** through a curated library of veterinary first-aid guides, organized by category and severity, enabling pet owners to take informed preliminary action before professional help arrives.

3. **To enable proximity-based veterinary discovery** using geolocation services and the Haversine formula, allowing users to identify the nearest veterinary clinics with filters for emergency availability and operating hours.

4. **To implement a structured SOS emergency request workflow** with defined lifecycle states, automated incident logging, and real-time notifications, ensuring that emergency situations are tracked from initiation to resolution.

5. **To establish a role-based access control system** that differentiates between pet owners, veterinary professionals, and administrators, ensuring that each user type has appropriate permissions and access to relevant functionality.

6. **To build a scalable and secure REST API backend** using industry-standard practices including token-based authentication, input validation, rate limiting, and consistent error handling, suitable for consumption by both mobile and web client applications.

7. **To maintain an auditable record of all veterinary incidents** through structured incident logs that link users, pets, SOS requests, and veterinary profiles, supporting medical history continuity and platform accountability.

---

## 6. Target Users

The platform serves three distinct user roles, each with defined responsibilities and access levels:

### 6.1 Pet Owner (User Role)

The primary end-user of the system. Pet owners use the mobile application to:
- Register and manage pet profiles (species, breed, weight, medical notes)
- Browse emergency categories and read published veterinary guides
- Search for nearby veterinary clinics based on current location
- Create SOS emergency requests with geolocation and emergency classification
- Track the status of active SOS requests
- View incident logs and medical history for their pets

### 6.2 Veterinarian (Vet Role)

Registered veterinary professionals who are listed on the platform. Their profiles include clinic information, contact details, geographic coordinates, accepted species, available services, operating hours, emergency availability, and verification status. Veterinarians participate in the SOS response workflow and contribute to incident resolution.

### 6.3 Administrator (Admin Role)

Platform administrators who manage system-wide operations through a web-based control panel. Administrators have access to:
- Dashboard statistics (total users, pets, active SOS requests, incident counts)
- User management with role assignment capabilities
- Oversight of all SOS requests across the platform
- Access to comprehensive incident logs
- Content management for emergency categories and guides

---

## 7. System Architecture

### 7.1 Architectural Pattern

PETSATHI follows a **client-server architecture** with a clear separation between the frontend client applications and the backend API server. The backend operates as a stateless RESTful API service, with all client-server communication occurring over HTTP using JSON payloads.

### 7.2 High-Level Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                              │
│                                                                  │
│   ┌─────────────────────┐       ┌──────────────────────────┐     │
│   │   Mobile App         │       │   Web Admin Panel        │     │
│   │  (React Native/Expo) │       │  (Browser-based)         │     │
│   │                      │       │                          │     │
│   │  • Pet Owner Module  │       │  • Admin Dashboard       │     │
│   │  • Vet Module        │       │  • User Management       │     │
│   │  • SOS Interface     │       │  • SOS Oversight         │     │
│   │  • Guide Browser     │       │  • Incident Reports      │     │
│   └────────┬─────────────┘       └────────────┬─────────────┘     │
│            │                                  │                   │
└────────────┼──────────────────────────────────┼───────────────────┘
             │          HTTPS / JSON            │
             │        Bearer Token Auth         │
             ▼                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                      API GATEWAY LAYER                           │
│                                                                  │
│   Route Prefix: /api/v1                                          │
│   Middleware: CORS, Throttle, Auth:Sanctum, Role-Based           │
│   Response Envelope: { success, message, data, errors }          │
│                                                                  │
└──────────────────────────────────────────┬───────────────────────┘
                                           │
┌──────────────────────────────────────────┼───────────────────────┐
│                   APPLICATION LAYER      │                       │
│                                          ▼                       │
│   ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐      │
│   │  Controllers  │  │  Form        │  │  Middleware       │      │
│   │  (7 total)    │  │  Requests    │  │  (EnsureRole)     │      │
│   │              │  │  (9 total)   │  │                  │      │
│   └──────┬───────┘  └──────────────┘  └──────────────────┘      │
│          │                                                       │
│          ▼                                                       │
│   ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐      │
│   │  Services     │  │  Policies    │  │  Notifications   │      │
│   │  (4 total)    │  │  (3 total)   │  │  (2 total)       │      │
│   └──────┬───────┘  └──────────────┘  └──────────────────┘      │
│          │                                                       │
└──────────┼───────────────────────────────────────────────────────┘
           │
┌──────────┼───────────────────────────────────────────────────────┐
│          ▼          DATA LAYER                                    │
│   ┌──────────────┐  ┌──────────────────────────────────────┐     │
│   │  Eloquent     │  │  Database (MySQL)                    │     │
│   │  Models       │  │                                      │     │
│   │  (8 total)    │  │  Tables: users, pets, vet_profiles,  │     │
│   │              │  │  sos_requests, incident_logs,        │     │
│   │  SoftDeletes  │  │  emergency_categories,              │     │
│   │  UUID Routing │  │  emergency_guides, vet_availabilities│     │
│   │  Scopes       │  │  notifications, personal_access_    │     │
│   │              │  │  tokens, sessions, cache, jobs       │     │
│   └──────────────┘  └──────────────────────────────────────┘     │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
```

### 7.3 Request Lifecycle

1. The client application sends an HTTP request to the API endpoint with the appropriate HTTP method, headers (including the Bearer token for authenticated routes), and request body.
2. The request passes through Laravel's middleware pipeline: CORS handling, rate limiting (throttle), authentication verification (Sanctum), and role-based access checks (EnsureRole middleware).
3. The corresponding Form Request class validates the input data against defined rules. Validation failures return a 422 response with field-level error details.
4. The controller receives the validated request and delegates business logic to the appropriate service class.
5. The service class interacts with Eloquent models to perform database operations within transactions where necessary (e.g., SOS creation with incident log).
6. Authorization is enforced through policy classes that verify resource ownership before permitting operations.
7. The controller returns a standardized JSON response using the ApiResponse trait.
8. Laravel's exception handler converts any uncaught exceptions into the standard response envelope format.

---

## 8. Technology Stack

### 8.1 Backend (Laravel)

| Component | Technology | Version / Details |
|---|---|---|
| Framework | Laravel | 12.0 |
| Language | PHP | 8.2+ |
| Authentication | Laravel Sanctum | 4.3 (Bearer token, 7-day expiry) |
| ORM | Eloquent | Built-in with Laravel |
| API Versioning | Route prefix | `/api/v1` |
| Response Format | Custom ApiResponse Trait | `{ success, message, data, errors }` |
| Testing | PHPUnit | 11.5.3 |
| Fake Data | FakerPHP | 1.23 |
| Interactive Shell | Laravel Tinker | 2.10.1 |

### 8.2 Mobile Application (React Native / Expo)

| Component | Technology | Details |
|---|---|---|
| Framework | React Native with Expo | Cross-platform (iOS & Android) |
| Language | JavaScript / TypeScript | — |
| Navigation | React Navigation | Stack and tab-based navigation |
| State Management | Context API / AsyncStorage | Local state and persistent storage |
| HTTP Client | Axios / Fetch API | For REST API communication |
| Location Services | Expo Location | GPS coordinates for SOS and vet search |
| Notifications | Expo Notifications | Push notification handling |

### 8.3 Database

| Component | Technology | Details |
|---|---|---|
| RDBMS | MySQL | Relational database |
| Schema Management | Laravel Migrations | 13 migration files |
| Indexing Strategy | Composite indexes | Optimized for frequent query patterns |
| Data Integrity | Foreign key constraints | CASCADE and SET NULL referential actions |
| Soft Deletion | SoftDeletes trait | Applied to pets, SOS requests, incidents, vet profiles |

**Database Schema Summary:**

The database consists of 13 primary tables:

| Table | Purpose | Key Attributes |
|---|---|---|
| `users` | User accounts and authentication | name, email, password, role (user/vet/admin) |
| `pets` | Pet profiles linked to owners | species (enum), breed, weight, medical notes; FK to users |
| `emergency_categories` | Grouping for emergency guides | name, slug, icon, sort order, active status |
| `emergency_guides` | First-aid and emergency instructions | severity level, applicable species (JSON), content |
| `vet_profiles` | Veterinary clinic information | coordinates (lat/lng), services (JSON), accepted species (JSON), ratings |
| `vet_availabilities` | Weekly operating schedules | day of week, open/close times, emergency hours flag |
| `sos_requests` | Emergency assistance requests | coordinates, emergency type (enum), status lifecycle, assigned vet |
| `incident_logs` | Medical event records | incident type, status, follow-up date, attachments (JSON), vet notes |
| `notifications` | Database notification storage | Polymorphic notifiable, JSON data payload |
| `personal_access_tokens` | Sanctum API tokens | tokenable (morph), abilities, expiration |
| `sessions` | User session management | user, IP address, user agent, last activity |
| `cache` / `cache_locks` | Application caching | key-value store with expiration |
| `jobs` / `job_batches` / `failed_jobs` | Queue management | Payload, attempts, failure handling |

### 8.4 Authentication Mechanism

The platform employs **Laravel Sanctum** for API token authentication. The mechanism operates as follows:

1. **Registration:** A new user submits their name, email, and password (minimum 8 characters, at least one uppercase letter and one number, with confirmation). Upon successful registration, the system creates the user record with a hashed password and issues a personal access token named `mobile-app` with a 7-day expiration period. The token is returned to the client in the response body.

2. **Login:** Existing users authenticate with their email and password. Upon successful credential verification, a new token is generated and returned. The response also includes the user object with role information.

3. **Authenticated Requests:** For all subsequent API calls to protected endpoints, the client includes the token in the `Authorization` header as a Bearer token (`Authorization: Bearer <token>`). The `auth:sanctum` middleware validates the token on each request.

4. **Role-Based Access:** Beyond authentication, specific routes are further protected by the custom `EnsureRole` middleware, which checks the authenticated user's `role` attribute (user, vet, or admin) against the required role for the endpoint. Unauthorized access returns a 403 Forbidden response.

5. **Logout:** The user can invalidate their current token by calling the logout endpoint, which deletes the active token from the database.

6. **Token Expiry:** Tokens are configured to expire after 7 days (10,080 minutes) as defined in the Sanctum configuration. Expired tokens are automatically rejected.

---

## 9. Core Features

### 9.1 Pet Owner Features

| Feature | Description | Technical Details |
|---|---|---|
| **Account Registration & Login** | Users register with name, email, and password. Login returns an API token for session management. | Sanctum token auth, password hashing via `Hash::make`, validation via `RegisterRequest` and `LoginRequest` |
| **Pet Profile Management** | Full CRUD operations for pet profiles including species, breed, weight, birth date, photo URL, and medical notes. | Maximum 10 pets per user enforced at application level. `PetService` handles business logic. `PetPolicy` enforces ownership. SoftDeletes enabled. |
| **Emergency Guide Access** | Browse emergency categories and read detailed first-aid guides filtered by category, species, and severity. | Public endpoints. Guides scoped to `published()` status. Category listing ordered by `sort_order`. |
| **Nearby Veterinary Search** | Locate veterinary clinics near the user's current GPS coordinates with filters for emergency availability and operating hours. | Haversine formula implemented in SQL via `selectRaw`. Default 10 km radius, max 20 results. Filterable by `emergency_only` and sortable by `distance` or `rating`. |
| **SOS Emergency Requests** | Initiate an emergency request with location, description, and emergency type classification. Track request status through lifecycle states. | Rate-limited to 5 per hour. One active SOS per user. Auto-creates linked `IncidentLog`. Notifications dispatched on creation and status changes. |
| **Incident History** | View chronological logs of all veterinary incidents, emergencies, and medical events associated with the user's pets. | Filterable by pet, status, and date range. Paginated at 15 records per page. Ownership enforcement via `forUser()` scope. |

### 9.2 Veterinarian Features

| Feature | Description | Technical Details |
|---|---|---|
| **Profile Listing** | Veterinary profiles are displayed with clinic name, vet name, contact information, address, accepted species, available services, and ratings. | Profiles use UUID-based routing. JSON fields for `services` and `accepted_species`. `is_verified` flag for platform trust. |
| **Availability Schedule** | Weekly operating hours with separate emergency hour designations for each day. | `vet_availabilities` table with day-of-week (0–6), open/close times, and `is_emergency_hours` flag. Unique constraint prevents duplicate schedule entries. |
| **Emergency Availability** | Vets can be marked as emergency-available and/or 24-hour operation, affecting their visibility in emergency searches. | `is_emergency_available` and `is_24_hours` boolean flags on `vet_profiles`. Used as filter criteria in `VetSearchService`. |
| **SOS Assignment** | Veterinary profiles can be assigned to SOS requests for response tracking. | `assigned_vet_id` foreign key on `sos_requests` references `vet_profiles`. Nearest vet search triggered on SOS creation (within 25 km radius). |
| **Incident Participation** | Incident logs reference the attending veterinary profile, linking medical events to specific care providers. | `vet_profile_id` foreign key on `incident_logs`. Allows tracking of which veterinarian handled each incident. |

### 9.3 Admin Features

| Feature | Description | Technical Details |
|---|---|---|
| **Dashboard Statistics** | Overview of platform metrics including total counts for users, pets, active SOS requests, and incidents. | `AdminController@stats` aggregates counts across multiple models. Accessible only to `role:admin` users. |
| **User Management** | List all registered users with search and role-based filtering. Assign or change user roles. | Search by name or email. Filter by role. Role update endpoint prevents administrators from changing their own role (self-protection). |
| **SOS Oversight** | View all SOS requests across the platform, including soft-deleted records, with related user and pet information. | Uses `withTrashed()` scope to include deleted SOS requests. Eager loads `user` and `pet` relationships. Ordered by most recent first. |
| **Incident Management** | Access all incident logs across the platform with related entity data. | Eager loads `user`, `pet`, `sosRequest`, and `vetProfile` relationships. Ordered by most recent incident date. |

---

## 10. API-Driven Design Approach

### 10.1 Design Principles

The backend is designed exclusively as an API service with no server-side view rendering. This approach was chosen for the following reasons:

- **Client Independence:** The API serves both the mobile application and the web admin panel from a single codebase, avoiding code duplication.
- **Scalability:** The stateless nature of REST allows horizontal scaling of the API server independently of the client applications.
- **Versioning:** The `/api/v1` prefix allows introduction of new API versions without disrupting existing clients.
- **Testability:** API endpoints can be tested independently using automated test suites and tools such as Postman (a collection is included in the project).

### 10.2 API Structure

The API is organized into the following route groups:

| Group | Prefix | Middleware | Purpose |
|---|---|---|---|
| Authentication | `/api/v1/auth` | `throttle:5,1` (register/login) | User registration, login, profile, logout |
| Public Resources | `/api/v1` | None | Emergency categories, guides, vet search |
| Pet Management | `/api/v1/pets` | `auth:sanctum` | CRUD operations on pet profiles |
| SOS Management | `/api/v1/sos` | `auth:sanctum` | Create, track, and update SOS requests |
| Incident Logs | `/api/v1/incidents` | `auth:sanctum` | View incident history |
| Administration | `/api/v1/admin` | `auth:sanctum`, `role:admin` | Platform management operations |

### 10.3 Response Standardization

All API responses conform to a consistent envelope structure:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { },
    "errors": null
}
```

For error responses:

```json
{
    "success": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

This standardization is implemented through the `ApiResponse` trait, which provides helper methods for common HTTP status codes: 200 (success), 201 (created), 401 (unauthorized), 403 (forbidden), 404 (not found), 422 (validation error), and 429 (too many requests).

### 10.4 Exception Handling

The application configures a global exception handler that intercepts all uncaught exceptions and converts them into the standard response envelope format. This ensures that clients never receive raw framework error pages and can always parse the response using the same logic. In development mode, additional debug information (exception message and stack trace) is included in 500 responses.

---

## 11. Security Considerations

### 11.1 Authentication Security

- **Password Hashing:** All passwords are stored using PHP's `Hash::make()` function (bcrypt by default), ensuring that plaintext passwords are never persisted in the database.
- **Token-Based Sessions:** Laravel Sanctum tokens replace traditional session-based authentication, making the API stateless and suitable for mobile clients. Tokens expire after 7 days, limiting the window of exposure for compromised tokens.
- **Token Invalidation:** The logout endpoint deletes the user's current token from the database, immediately revoking access.

### 11.2 Authorization and Access Control

- **Role-Based Middleware:** The custom `EnsureRole` middleware enforces role-based access at the route level. Admin endpoints require `role:admin`, preventing unauthorized users from accessing platform management functions.
- **Policy-Based Authorization:** Three policy classes (`PetPolicy`, `SosPolicy`, `IncidentPolicy`) enforce resource ownership. Users can only view, update, or delete resources that belong to them. Policies are registered centrally via `Gate::policy()` in the `AppServiceProvider`.
- **Self-Protection:** The admin role-update endpoint includes a check preventing administrators from modifying their own role, avoiding accidental privilege escalation or de-escalation.

### 11.3 Input Validation

- **Form Request Classes:** Nine dedicated Form Request classes validate all incoming data before it reaches controller logic. This includes type checking, format validation, enum constraints, and relational existence checks.
- **Password Policy:** Registration requires a minimum of 8 characters, at least one uppercase letter, at least one number, and password confirmation.
- **Coordinate Validation:** Latitude and longitude values are validated against their mathematical bounds (−90 to 90 and −180 to 180, respectively).
- **Pet Limit Enforcement:** The system enforces a maximum of 10 pets per user account, verified at both the model and service levels.

### 11.4 Rate Limiting

- **Authentication Endpoints:** Registration and login endpoints are throttled to 5 requests per minute to mitigate brute-force attacks.
- **SOS Creation:** An application-level rate limit restricts users to 5 SOS requests per hour, preventing abuse of the emergency system.

### 11.5 Data Protection

- **Soft Deletion:** Records for pets, SOS requests, incident logs, and vet profiles are soft-deleted rather than permanently removed, preserving data integrity and auditability.
- **UUID Exposure:** SOS requests, incident logs, and veterinary profiles use UUIDs as their public-facing identifiers (route keys), preventing enumeration attacks that exploit sequential auto-increment IDs.
- **CORS Configuration:** Cross-Origin Resource Sharing middleware is configured and applied to all API routes, controlling which origins can access the API.

### 11.6 Error Information Control

- **Production Safety:** The global exception handler suppresses detailed error messages and stack traces in production environments, preventing information leakage. Debug information is only included when the application is in debug mode.

---

## 12. User Flow & Modules

### 12.1 Pet Owner User Flow

```
Registration / Login
        │
        ▼
   Dashboard (Home)
        │
        ├──► Pet Management
        │       ├── Add New Pet (up to 10)
        │       ├── View Pet Profile
        │       ├── Edit Pet Details
        │       └── Delete Pet (soft delete)
        │
        ├──► Emergency Guides
        │       ├── Browse Categories
        │       ├── View Guides by Category
        │       └── Read Guide Detail (severity, species, content)
        │
        ├──► Find Nearby Vets
        │       ├── Search by Current Location
        │       ├── Filter: Emergency Available / Open Now
        │       ├── Sort: Distance / Rating
        │       └── View Vet Profile (services, hours, contact)
        │
        ├──► SOS Emergency
        │       ├── Create SOS Request
        │       │     ├── Auto-capture GPS coordinates
        │       │     ├── Select Emergency Type
        │       │     ├── Link to Pet (optional)
        │       │     └── Provide Description
        │       ├── Track Active SOS Status
        │       │     ├── Pending → Acknowledged → In Progress → Completed
        │       │     └── Cancel SOS (if still active)
        │       └── Receive Status Notifications
        │
        └──► Incident History
                ├── View All Incidents
                ├── Filter by Pet / Status / Date Range
                └── View Incident Detail
```

### 12.2 Administrator User Flow

```
Admin Login
    │
    ▼
Admin Dashboard
    │
    ├──► Statistics Overview
    │       ├── Total Users Count
    │       ├── Total Pets Count
    │       ├── Active SOS Requests Count
    │       └── Total Incidents Count
    │
    ├──► User Management
    │       ├── Search Users (by name/email)
    │       ├── Filter by Role (user/vet/admin)
    │       └── Update User Role
    │
    ├──► SOS Oversight
    │       ├── View All SOS Requests (including deleted)
    │       └── Monitor Request Statuses
    │
    └──► Incident Management
            ├── View All Incident Logs
            └── Review Incident Details (user, pet, vet, SOS linkage)
```

### 12.3 Module Breakdown

| Module | Backend Components | Database Tables |
|---|---|---|
| **Authentication** | `AuthController`, `RegisterRequest`, `LoginRequest`, Sanctum configuration | `users`, `personal_access_tokens` |
| **Pet Management** | `PetController`, `PetService`, `PetPolicy`, `StorePetRequest`, `UpdatePetRequest` | `pets` |
| **Emergency Guides** | `GuideController` | `emergency_categories`, `emergency_guides` |
| **Veterinary Search** | `VetController`, `VetSearchService` | `vet_profiles`, `vet_availabilities` |
| **SOS System** | `SosController`, `SosService`, `SosPolicy`, `SosAlertNotification`, `SosStatusNotification` | `sos_requests`, `notifications` |
| **Incident Logging** | `IncidentController`, `IncidentService`, `IncidentPolicy` | `incident_logs` |
| **Administration** | `AdminController`, `EnsureRole` middleware | All tables (read access) |

---

## 13. Future Scope & Enhancements

The current implementation establishes the foundational architecture and core workflows. The following enhancements are identified for future development iterations:

### 13.1 Communication and Consultation

- **In-App Chat:** Real-time messaging between pet owners and veterinarians using WebSocket (Laravel Echo with Pusher or Socket.IO) to enable text-based consultations before or after veterinary visits.
- **Video Consultation:** Integration of video calling capabilities for remote veterinary consultations, particularly valuable for rural users or non-emergency advice.
- **Push Notifications:** Implementation of Firebase Cloud Messaging (FCM) for real-time push notifications on mobile devices, complementing the existing database notification system.

### 13.2 Enhanced Veterinary Features

- **Vet Self-Registration Portal:** Allow veterinarians to register themselves on the platform, upload credentials, and manage their own profiles and availability schedules.
- **Appointment Booking System:** A scheduling module that allows pet owners to book appointments with specific veterinarians, including time slot selection, confirmation workflow, and calendar integration.
- **Prescription Management:** Digital prescription issuance by veterinarians with medication details, dosage instructions, and refill tracking.
- **Vet Ratings and Reviews:** A rating and review system where pet owners can rate veterinary services after completed consultations or SOS resolutions.

### 13.3 Pet Health Tracking

- **Vaccination Reminders:** Automated reminders for upcoming vaccinations based on species-specific schedules and age calculations.
- **Weight and Growth Tracking:** Graphical tracking of pet weight over time with alerts for significant deviations.
- **Medication Schedules:** A medication management module with dosage reminders and adherence tracking.
- **Document Uploads:** Integration with cloud storage (e.g., AWS S3) for uploading and storing medical documents, lab reports, and imaging results.

### 13.4 Platform Intelligence

- **Analytics Dashboard:** Advanced analytics for administrators including trends in SOS requests by region, common emergency types, peak usage times, and veterinary response metrics.
- **AI-Powered Symptom Checker:** A preliminary symptom assessment tool that guides pet owners through a decision tree to determine the urgency of their pet's condition.
- **Recommendation Engine:** Personalized recommendations for nearby veterinary services based on pet species, required specializations, and past interaction history.

### 13.5 Technical Enhancements

- **Queue-Based Processing:** Moving notification dispatch and heavy computations to background queues using Laravel Queues with Redis or database drivers.
- **Caching Layer:** Implementation of response caching for frequently accessed resources (emergency categories, published guides, vet listings) using Redis or Memcached.
- **API Rate Limiting Enhancements:** Per-endpoint throttle configuration with dynamic limits based on user role and subscription tier.
- **Automated Testing Coverage:** Expansion of the test suite to include integration tests, API endpoint tests, and service-level unit tests with target coverage above 80%.
- **CI/CD Pipeline:** Automated deployment pipeline using GitHub Actions for running tests, static analysis, and deployment to staging and production environments.
- **Multi-Language Support:** Localization of API responses and emergency guide content for regional language accessibility.

---

## 14. Challenges & Considerations

### 14.1 Technical Challenges

1. **Accurate Geolocation Calculations:** Implementing the Haversine formula for distance-based veterinary search required handling edge cases involving GPS accuracy, coordinate precision (10- and 11-decimal-place columns), and SQL-level computation performance. The calculation is performed within the database query to avoid loading unnecessary records into application memory.

2. **SOS Request State Management:** Managing the lifecycle of SOS requests across five states (pending, acknowledged, in-progress, completed, cancelled) required careful validation of allowable state transitions. Invalid transitions (e.g., completing a cancelled request) must be prevented while maintaining a straightforward API interface.

3. **Concurrent SOS Handling:** Enforcing the one-active-SOS-per-user constraint in a concurrent environment necessitated application-level checks combined with database indexing to prevent race conditions without introducing pessimistic locking overhead.

4. **Data Consistency in Transactions:** SOS creation involves multiple database operations (creating the SOS record, creating the linked incident log, and dispatching notifications). These operations are wrapped in database transactions to ensure atomicity, preventing orphaned records in case of partial failures.

5. **Token Security and Expiration:** Balancing token longevity (7 days for mobile convenience) against security exposure required consideration of token rotation strategies and the ability to invalidate all tokens if a compromise is suspected.

### 14.2 Design Considerations

1. **API Versioning Strategy:** The current `/api/v1` prefix was chosen to allow graceful migration to future API versions. Breaking changes can be introduced under `/api/v2` without disrupting existing mobile application installations that have not yet been updated.

2. **UUID vs. Auto-Increment:** Public-facing resources use UUIDs to prevent enumeration, but auto-increment primary keys are retained internally for join performance. This dual-identifier approach adds complexity but provides both security and query efficiency.

3. **Soft Deletion Trade-offs:** While soft deletion preserves data for auditing and recovery, it requires consistent use of the `SoftDeletes` trait and careful attention to queries (using `withTrashed()` or `withoutTrashed()` as appropriate) to avoid displaying deleted records to regular users.

4. **Rate Limiting Granularity:** The current rate limits (5 auth attempts per minute, 5 SOS requests per hour) represent initial conservative values. Production deployment may require adjustment based on observed usage patterns, potentially with different tiers for verified and unverified users.

5. **Notification Channel Limitations:** The current implementation uses only the database notification channel. Production deployment will require integration with push notification services (FCM/APNs) for real-time mobile alerts, particularly critical for SOS status updates.

### 14.3 Scaling Considerations

1. **Database Indexing:** Composite indexes have been strategically placed on frequently queried column combinations (e.g., `[user_id, status]` on SOS requests, `[latitude, longitude]` on vet profiles) to maintain query performance as data volume grows.

2. **Pagination:** All list endpoints implement cursor-based or offset-based pagination to control response sizes and prevent memory exhaustion on large datasets.

3. **Stateless API Design:** The absence of server-side session state (beyond token validation) enables horizontal scaling of the API server behind a load balancer without session affinity requirements.

---

## 15. Conclusion

PETSATHI is a structured and technically grounded pet care and veterinary assistance platform that addresses real challenges in pet healthcare accessibility. The system provides pet owners with the tools to manage their pets' health profiles, access curated emergency guidance, locate nearby veterinary services through geolocation, and raise emergency SOS requests with lifecycle tracking and incident documentation.

The backend is built on Laravel 12 with a clean separation of concerns across controllers, services, policies, and models. The REST API follows established conventions including versioned endpoints, token-based authentication via Sanctum, role-based access control, comprehensive input validation, and standardized response formatting. Security measures including password hashing, rate limiting, UUID-based resource identification, and policy-driven authorization are implemented to protect both user data and platform integrity.

The database schema is designed with referential integrity through foreign key constraints, supports soft deletion for audit compliance, and employs strategic indexing for query performance. The application architecture accommodates future extension through clearly defined service boundaries, making it feasible to introduce additional modules such as appointment booking, in-app messaging, and advanced analytics without restructuring existing components.

The current implementation serves as a functional and demonstrable foundation that validates the core concept of digitally-mediated pet healthcare. With the enhancements outlined in the future scope section, particularly real-time communication, push notifications, and veterinary self-service capabilities, the platform is positioned to evolve into a comprehensive pet healthcare ecosystem.

---

**Prepared by:** Project Development Team
**Date:** February 2026
**Platform Version:** 1.0
**Backend Framework:** Laravel 12.0 | PHP 8.2+
**Authentication:** Laravel Sanctum 4.3
**API Version:** v1

---
