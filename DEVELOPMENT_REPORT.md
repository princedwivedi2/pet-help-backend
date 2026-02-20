# Pet Help API - Development Report

> **Generated:** January 30, 2026  
> **Project:** Pet Help Backend API v1  
> **Framework:** Laravel with Sanctum Authentication

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Directory Structure](#directory-structure)
3. [Files Created](#files-created)
4. [API Endpoints](#api-endpoints)
5. [Database Schema](#database-schema)
6. [Services & Business Logic](#services--business-logic)
7. [Testing with Postman](#testing-with-postman)

---

## 🎯 Project Overview

Pet Help is a mobile application backend that provides:
- **User Authentication** - Register, login, logout with JWT tokens (Sanctum)
- **Pet Management** - CRUD operations for user pets (max 10 per user)
- **Emergency SOS** - Create/manage emergency requests with rate limiting
- **Vet Search** - Find nearby vets with geolocation (Haversine formula)
- **Emergency Guides** - First-aid guides for pet emergencies
- **Incident Tracking** - Log and track pet health incidents

---

## 📁 Directory Structure

```
pet-help-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AuthController.php
│   │   │   ├── GuideController.php
│   │   │   ├── IncidentController.php
│   │   │   ├── PetController.php
│   │   │   ├── SosController.php
│   │   │   └── VetController.php
│   │   └── Requests/Api/V1/
│   │       ├── Auth/
│   │       ├── Guide/
│   │       ├── Incident/
│   │       ├── Pet/
│   │       ├── Sos/
│   │       └── Vet/
│   ├── Models/
│   │   ├── EmergencyCategory.php
│   │   ├── EmergencyGuide.php
│   │   ├── IncidentLog.php
│   │   ├── Pet.php
│   │   ├── SosRequest.php
│   │   ├── User.php
│   │   ├── VetAvailability.php
│   │   └── VetProfile.php
│   ├── Services/
│   │   ├── IncidentService.php
│   │   ├── PetService.php
│   │   ├── SosService.php
│   │   └── VetSearchService.php
│   └── Traits/
│       └── ApiResponse.php
├── database/
│   ├── factories/
│   │   ├── IncidentLogFactory.php
│   │   ├── PetFactory.php
│   │   ├── SosRequestFactory.php
│   │   ├── UserFactory.php
│   │   └── VetProfileFactory.php
│   ├── migrations/
│   │   ├── 2026_01_29_172500_create_personal_access_tokens_table.php
│   │   ├── 2026_01_30_000001_create_pets_table.php
│   │   ├── 2026_01_30_000002_create_emergency_categories_table.php
│   │   ├── 2026_01_30_000003_create_emergency_guides_table.php
│   │   ├── 2026_01_30_000004_create_vet_profiles_table.php
│   │   ├── 2026_01_30_000005_create_vet_availabilities_table.php
│   │   ├── 2026_01_30_000006_create_sos_requests_table.php
│   │   └── 2026_01_30_000007_create_incident_logs_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── EmergencyCategorySeeder.php
│       ├── EmergencyGuideSeeder.php
│       └── VetProfileSeeder.php
├── routes/
│   └── api_v1.php
└── postman/
    └── Pet_Help_API_v1.postman_collection.json
```

---

## 📝 Files Created

### Controllers (6 files)

| File | Description |
|------|-------------|
| `AuthController.php` | User registration, login, logout, profile retrieval |
| `PetController.php` | CRUD operations for pets with ownership validation |
| `SosController.php` | Emergency SOS request creation, status updates |
| `VetController.php` | Vet search by location with distance calculation |
| `GuideController.php` | Emergency guide categories and articles |
| `IncidentController.php` | Incident history listing and details |

### Models (8 files)

| File | Description |
|------|-------------|
| `User.php` | User model with Sanctum tokens, relationships to pets/sos |
| `Pet.php` | Pet profile with species, breed, weight, medical notes |
| `SosRequest.php` | Emergency requests with UUID, status tracking |
| `VetProfile.php` | Vet clinic info with geo-coordinates, services |
| `VetAvailability.php` | Weekly availability schedule for vets |
| `EmergencyCategory.php` | Categories for emergency guides |
| `EmergencyGuide.php` | First-aid guides with species/severity |
| `IncidentLog.php` | Pet health incident records |

### Services (4 files)

| File | Description |
|------|-------------|
| `PetService.php` | Pet business logic, max 10 pets per user |
| `SosService.php` | SOS creation, vet notification stub, status updates |
| `VetSearchService.php` | Haversine distance calculation, nearby vet search |
| `IncidentService.php` | Incident querying with filters and pagination |

### Traits (1 file)

| File | Description |
|------|-------------|
| `ApiResponse.php` | Standardized JSON response methods |

### Migrations (7 files)

| File | Tables Created |
|------|----------------|
| `create_personal_access_tokens_table.php` | `personal_access_tokens` |
| `create_pets_table.php` | `pets` |
| `create_emergency_categories_table.php` | `emergency_categories` |
| `create_emergency_guides_table.php` | `emergency_guides` |
| `create_vet_profiles_table.php` | `vet_profiles` |
| `create_vet_availabilities_table.php` | `vet_availabilities` |
| `create_sos_requests_table.php` | `sos_requests` |
| `create_incident_logs_table.php` | `incident_logs` |

### Seeders (4 files)

| File | Purpose |
|------|---------|
| `DatabaseSeeder.php` | Main seeder orchestrator |
| `EmergencyCategorySeeder.php` | Seed emergency categories |
| `EmergencyGuideSeeder.php` | Seed first-aid guides |
| `VetProfileSeeder.php` | Seed sample vet profiles |

### Factories (5 files)

| File | Purpose |
|------|---------|
| `UserFactory.php` | Generate test users |
| `PetFactory.php` | Generate test pets |
| `SosRequestFactory.php` | Generate test SOS requests |
| `VetProfileFactory.php` | Generate test vet profiles |
| `IncidentLogFactory.php` | Generate test incidents |

### Request Validators (Organized by Feature)

```
app/Http/Requests/Api/V1/
├── Auth/
│   ├── RegisterRequest.php
│   └── LoginRequest.php
├── Pet/
│   ├── StorePetRequest.php
│   └── UpdatePetRequest.php
├── Sos/
│   ├── StoreSosRequest.php
│   └── UpdateSosStatusRequest.php
├── Vet/
│   └── SearchVetsRequest.php
├── Guide/
│   └── IndexGuideRequest.php
└── Incident/
    └── IndexIncidentRequest.php
```

---

## 🔌 API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/v1/auth/register` | ❌ | Register new user |
| `POST` | `/api/v1/auth/login` | ❌ | Login user |
| `GET` | `/api/v1/auth/me` | ✅ | Get current user |
| `POST` | `/api/v1/auth/logout` | ✅ | Logout user |

### Pets

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/pets` | ✅ | List user's pets |
| `POST` | `/api/v1/pets` | ✅ | Create new pet |
| `GET` | `/api/v1/pets/{id}` | ✅ | Get pet details |
| `PUT` | `/api/v1/pets/{id}` | ✅ | Update pet |
| `DELETE` | `/api/v1/pets/{id}` | ✅ | Delete pet |

### Emergency Guides (Public)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/emergency-categories` | ❌ | List categories |
| `GET` | `/api/v1/guides` | ❌ | List guides (filter by category) |
| `GET` | `/api/v1/guides/{id}` | ❌ | Get guide details |

### Vets (Public)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/vets` | ❌ | Search nearby vets |
| `GET` | `/api/v1/vets/{uuid}` | ❌ | Get vet details |

**Search Parameters:**
- `lat` - Latitude (required)
- `lng` - Longitude (required)
- `radius_km` - Search radius (default: 10)
- `emergency_only` - Filter 24/7 emergency vets
- `available_only` - Filter currently open
- `sort_by` - `distance` or `rating`

### SOS Requests

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/v1/sos` | ✅ | Create SOS request |
| `GET` | `/api/v1/sos/active` | ✅ | Get active SOS |
| `PUT` | `/api/v1/sos/{uuid}/status` | ✅ | Update SOS status |

**Rate Limit:** 5 SOS requests per hour

### Incidents

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/incidents` | ✅ | List user's incidents |
| `GET` | `/api/v1/incidents/{uuid}` | ✅ | Get incident details |

---

## 🗄️ Database Schema

### Users Table
```sql
- id (bigint, PK)
- name (string)
- email (string, unique)
- password (string, hashed)
- email_verified_at (timestamp, nullable)
- remember_token (string, nullable)
- created_at, updated_at
```

### Pets Table
```sql
- id (bigint, PK)
- user_id (FK → users)
- name (string)
- species (string: dog, cat, bird, etc.)
- breed (string, nullable)
- birth_date (date, nullable)
- weight_kg (decimal(5,2), nullable)
- photo_url (string, nullable)
- medical_notes (text, nullable)
- deleted_at (soft delete)
- created_at, updated_at
```

### SOS Requests Table
```sql
- id (bigint, PK)
- uuid (string, unique)
- user_id (FK → users)
- pet_id (FK → pets, nullable)
- latitude (decimal(10,8))
- longitude (decimal(11,8))
- address (string, nullable)
- description (text)
- emergency_type (enum: poisoning, injury, breathing, seizure, other)
- status (enum: pending, acknowledged, in_progress, completed, cancelled)
- assigned_vet_id (FK → vet_profiles, nullable)
- acknowledged_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- resolution_notes (text, nullable)
- deleted_at (soft delete)
- created_at, updated_at
```

### Vet Profiles Table
```sql
- id (bigint, PK)
- uuid (string, unique)
- clinic_name (string)
- vet_name (string)
- phone (string)
- email (string, nullable)
- address (string)
- city (string)
- state (string)
- postal_code (string)
- latitude (decimal(10,8))
- longitude (decimal(11,8))
- services (JSON array)
- accepted_species (JSON array)
- is_emergency_available (boolean)
- is_24_hours (boolean)
- is_verified (boolean)
- is_active (boolean)
- rating (decimal(2,1))
- review_count (integer)
- deleted_at (soft delete)
- created_at, updated_at
```

### Vet Availabilities Table
```sql
- id (bigint, PK)
- vet_profile_id (FK → vet_profiles)
- day_of_week (integer: 0-6)
- open_time (time)
- close_time (time)
- created_at, updated_at
```

### Emergency Categories Table
```sql
- id (bigint, PK)
- name (string)
- slug (string, unique)
- icon (string, nullable)
- description (text, nullable)
- sort_order (integer)
- is_active (boolean)
- created_at, updated_at
```

### Emergency Guides Table
```sql
- id (bigint, PK)
- category_id (FK → emergency_categories)
- title (string)
- slug (string, unique)
- summary (text)
- content (longtext, JSON)
- applicable_species (JSON array)
- severity_level (enum: low, medium, high, critical)
- estimated_read_minutes (integer)
- is_published (boolean)
- created_at, updated_at
```

### Incident Logs Table
```sql
- id (bigint, PK)
- uuid (string, unique)
- user_id (FK → users)
- pet_id (FK → pets, nullable)
- sos_request_id (FK → sos_requests, nullable)
- vet_profile_id (FK → vet_profiles, nullable)
- title (string)
- description (text)
- incident_type (enum: emergency, checkup, vaccination, illness, injury, other)
- status (enum: open, in_treatment, resolved)
- incident_date (date)
- notes (text, nullable)
- deleted_at (soft delete)
- created_at, updated_at
```

---

## ⚙️ Services & Business Logic

### PetService

- **Max Pets:** 10 per user
- **Operations:** Create, update, delete, list
- **Validation:** Ownership check before all operations

### SosService

- **Rate Limiting:** 5 requests per hour per user
- **Active Check:** Only one active SOS allowed at a time
- **Auto Incident:** Creates incident log automatically
- **Status Flow:** `pending` → `acknowledged` → `in_progress` → `completed/cancelled`
- **Vet Notification:** Stub for future push notification integration

### VetSearchService

- **Distance Calculation:** Haversine formula
- **Filters:** Emergency-only, currently available
- **Sort Options:** By distance or rating
- **Default Radius:** 10km

### IncidentService

- **Pagination:** Default 15 per page
- **Filters:** By pet, status, date range
- **Ownership:** Only user's incidents returned

---

## 🧪 Testing with Postman

### Collection File
`postman/Pet_Help_API_v1.postman_collection.json`

### Base URL
```
http://localhost:8000/api/v1
```

### Setup Steps

1. **Start Laravel Server:**
   ```bash
   cd pet-help-backend
   php artisan serve
   ```

2. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

3. **Seed Database:**
   ```bash
   php artisan db:seed
   ```

4. **Import Postman Collection:**
   - Open Postman
   - Import `Pet_Help_API_v1.postman_collection.json`
   - Collection variables auto-save token after login

### Testing Flow

1. **Register** → Creates user and returns token
2. **Login** → Auto-saves token to collection variable
3. **Create Pet** → Test pet creation
4. **List Pets** → Verify pet was created
5. **Search Vets** → Test geolocation search
6. **Create SOS** → Test emergency request
7. **Get Active SOS** → Verify SOS status
8. **Complete SOS** → Test status update

---

## 📊 API Response Format

All API responses follow a consistent format:

```json
{
    "success": true|false,
    "message": "Human-readable message",
    "data": { ... } | null,
    "errors": { ... } | null
}
```

### Success Response (200/201)
```json
{
    "success": true,
    "message": "Pet created successfully",
    "data": {
        "pet": { ... }
    },
    "errors": null
}
```

### Error Response (4xx/5xx)
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

---

## 🚀 Quick Start Commands

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
php artisan serve

# Run tests
php artisan test
```

---

## 📌 Notes

1. **Authentication:** Using Laravel Sanctum for API token authentication
2. **Soft Deletes:** Enabled on `pets`, `sos_requests`, `vet_profiles`, `incident_logs`
3. **UUIDs:** Used for public-facing IDs (`sos_requests`, `vet_profiles`, `incident_logs`)
4. **Geolocation:** Haversine formula for accurate distance calculation
5. **Rate Limiting:** Implemented at application level for SOS requests

---

*Report generated for Pet Help API MVP Development*
