# API Documentation Status

## Current Position

This file records current API documentation status. Final mobile contract documentation is still required before Android/iOS implementation starts.

## Existing API Reality

- Backend API version: `/api/v1`
- Response envelope: `{ success, message, data, errors }`
- Broad coverage across auth, pets, vets, SOS, appointments, payments, content, and admin modules
- Route count: 211 routes as of 2026-04-20

## Documentation Gap to Close

Create and finalize:

- `docs/MOBILE_API_CONTRACT.md`

## Required Contract Sections

1. Base URL and headers
2. Auth/token flow
3. Role behavior (user, vet, admin)
4. Standard success/error envelope
5. Validation error format
6. Pagination format
7. File upload examples
8. Payment flow
9. SOS flow
10. Appointment flow
11. Device token/notification flow

## Release Rule

Do not start full mobile implementation until the mobile API contract is reviewed and frozen.

## Revision Note

This file is maintained as a living status note and supersedes older route-count assumptions.
