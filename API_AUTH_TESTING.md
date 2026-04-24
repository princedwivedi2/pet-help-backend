# API Auth Testing Notes (Updated 2026-04-20)

## Scope

Authentication and role-safety testing baseline for ReSpaw backend.

## Current Auth Expectations

- Sanctum token authentication is active
- Role segregation for user/vet/admin must be preserved
- Verified-email middleware behavior should be validated for protected flows
- Profile updates must not allow role or protected-identity field mutation

## Mandatory Auth Test Scenarios

1. Register/login/logout happy paths
2. Invalid credential rejection
3. Token-protected endpoint denial without token
4. Role-protected endpoint denial for wrong role
5. Email verification enforcement where required
6. Profile update attempts with `role`, `password`, `email_verified_at` rejected/ignored correctly

## Vet-Specific Auth Behavior

Validate login/flow behavior for:

- pending vets
- approved vets
- rejected vets
- suspended vets

## Output Expectation

Auth testing report should include:

- executed commands
- passed scenarios
- failed scenarios with root causes
- remediation actions and follow-up tests
