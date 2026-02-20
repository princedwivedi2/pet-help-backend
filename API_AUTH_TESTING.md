# PET-HELP Backend Authentication Testing

Base URL: http://localhost:8000/api/v1

## Test Commands

### 1. Register New User

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"John Doe\",\"email\":\"john@example.com\",\"password\":\"Password123\",\"password_confirmation\":\"Password123\"}"
```

Expected Response (201):
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "name": "John Doe",
      "email": "john@example.com",
      "updated_at": "2026-01-29T17:30:00.000000Z",
      "created_at": "2026-01-29T17:30:00.000000Z",
      "id": 1
    },
    "token": "1|abcdef123456..."
  },
  "errors": null
}
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"john@example.com\",\"password\":\"Password123\"}"
```

Expected Response (200):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2026-01-29T17:30:00.000000Z",
      "updated_at": "2026-01-29T17:30:00.000000Z"
    },
    "token": "2|ghijkl789012..."
  },
  "errors": null
}
```

### 3. Get Authenticated User

Replace YOUR_TOKEN with the token from login/register response.

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected Response (200):
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2026-01-29T17:30:00.000000Z",
      "updated_at": "2026-01-29T17:30:00.000000Z"
    }
  },
  "errors": null
}
```

### 4. Logout

Replace YOUR_TOKEN with the token from login/register response.

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected Response (200):
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null,
  "errors": null
}
```

## Validation Error Examples

### Register with Invalid Password (no uppercase)

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Test User\",\"email\":\"test@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}"
```

Expected Response (422):
```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "password": [
      "Password must contain at least one uppercase letter and one number."
    ]
  }
}
```

### Login with Invalid Credentials

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"john@example.com\",\"password\":\"WrongPassword123\"}"
```

Expected Response (401):
```json
{
  "success": false,
  "message": "Invalid credentials",
  "data": null,
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

### Access Protected Route without Token

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json"
```

Expected Response (401):
```json
{
  "message": "Unauthenticated."
}
```

## Password Validation Rules

- Minimum length: 8 characters
- Must contain at least 1 uppercase letter
- Must contain at least 1 number
- Must match password_confirmation field during registration
