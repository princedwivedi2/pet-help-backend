# API Auth Testing Notes

## Scope

Authentication and role-safety testing baseline for pet-help-backend.

## Current Auth Expectations

- Sanctum token authentication is active
- Role segregation for user/vet/admin must be preserved
- Verified-email middleware behavior should be validated for protected flows
- Profile updates must not allow immutable or protected-field mutation: `role`, `password`, and `email_verified_at` must remain unchanged through profile update endpoints unless changed through dedicated admin/auth flows

## Mandatory Auth Test Scenarios

1. Register/login/logout happy paths

	 - Base URL: `{{API_BASE_URL}}/api/v1`
	 - Endpoints:
		 - `POST /auth/register`
		 - `POST /auth/login`
		 - `POST /auth/logout`
	 - Example register request:

		 ```bash
		 curl -X POST "{{API_BASE_URL}}/api/v1/auth/register" \
			 -H "Accept: application/json" \
			 -H "Content-Type: application/json" \
			 -d '{"name":"Test User","email":"auth-user@example.com","password":"Password1!","password_confirmation":"Password1!","role":"user"}'
		 ```

	 - Expected status: `201 Created`
	 - Assert:
		 - `success=true`
		 - `data.token` is present and non-empty
		 - `data.user.email` matches the submitted email
		 - password is not returned
	 - Example login request:

		 ```bash
		 curl -X POST "{{API_BASE_URL}}/api/v1/auth/login" \
			 -H "Accept: application/json" \
			 -H "Content-Type: application/json" \
			 -d '{"email":"auth-user@example.com","password":"Password1!"}'
		 ```

	 - Expected status: `200 OK`
	 - Example logout request:

		 ```bash
		 curl -X POST "{{API_BASE_URL}}/api/v1/auth/logout" \
			 -H "Accept: application/json" \
			 -H "Authorization: Bearer <token>"
		 ```

	 - Expected status: `200 OK`
	 - Assert token invalidation or server-side logout acknowledgement.

2. Invalid credential rejection

	 - Endpoint: `POST /auth/login`
	 - Example request:

		 ```bash
		 curl -X POST "{{API_BASE_URL}}/api/v1/auth/login" \
			 -H "Accept: application/json" \
			 -H "Content-Type: application/json" \
			 -d '{"email":"auth-user@example.com","password":"WrongPassword!"}'
		 ```

	 - Expected status: `422 Unprocessable Entity` or `401 Unauthorized` depending on controller policy
	 - Assert:
		 - `success=false`
		 - `errors` contains an authentication/password message
		 - no token is returned

3. Token-protected endpoint denial without token

	 - Endpoint example: `GET /profile` or any authenticated resource such as `GET /pets`
	 - Example request:

		 ```bash
		 curl -X GET "{{API_BASE_URL}}/api/v1/pets" -H "Accept: application/json"
		 ```

	 - Expected status: `401 Unauthorized`
	 - Assert:
		 - `success=false`
		 - response indicates authentication is required

4. Role-protected endpoint denial for wrong role

	 - Endpoint example: `GET /appointments/vet`
	 - Example request with a user token:

		 ```bash
		 curl -X GET "{{API_BASE_URL}}/api/v1/appointments/vet" \
			 -H "Accept: application/json" \
			 -H "Authorization: Bearer <user-token>"
		 ```

	 - Expected status: `403 Forbidden`
	 - Assert:
		 - `success=false`
		 - message indicates role restriction
		 - no data payload is returned

5. Email verification enforcement where required

	 - Protected endpoint example: `GET /pets` or `POST /appointments`
	 - Example request with unverified token:

		 ```bash
		 curl -X GET "{{API_BASE_URL}}/api/v1/pets" \
			 -H "Accept: application/json" \
			 -H "Authorization: Bearer <unverified-user-token>"
		 ```

	 - Expected status: `403 Forbidden` or `409 Conflict` if your controller emits a verification prompt
	 - Assert:
		 - response explains email verification is required
		 - token remains valid for the auth session

6. Profile update attempts with `role`, `password`, `email_verified_at` rejected or ignored

	 - Endpoint: `PUT /auth/profile`
	 - Example request:

		 ```bash
		 curl -X PUT "{{API_BASE_URL}}/api/v1/auth/profile" \
			 -H "Accept: application/json" \
			 -H "Authorization: Bearer <token>" \
			 -H "Content-Type: application/json" \
			 -d '{"name":"Updated Name","role":"admin","password":"NewPassword1!","email_verified_at":"2026-01-01 00:00:00"}'
		 ```

	 - Expected status: `200 OK` for allowed profile fields only
	 - Assert:
		 - `name` changes if allowed
		 - `role` is ignored or rejected and remains unchanged
		 - `password` is not updated through profile update
		 - `email_verified_at` is not updated through profile update
		 - response should not echo protected fields as updated values

## Vet-Specific Auth Behavior

Validate login/flow behavior for:

- Pending vets
	- Login allowed: `200 OK`
	- Token/session issued: yes
	- Expected response includes a login notice or pending-verification message
	- Allowed scope: read-only/profile/onboarding only; appointment/SOS mutation endpoints remain blocked until approval

- Approved vets
	- Login allowed: `200 OK`
	- Token/session issued: yes
	- Full vet scope available, including vet dashboard, appointment management, SOS status/location actions, wallet, and review pages
	- Assert returned vet status reflects approved state

- Rejected vets
	- Login blocked: `403 Forbidden` is preferred
	- Token/session issued: no
	- Expected error string should state the vet application was rejected and re-application/support guidance may be shown
	- No protected vet endpoints should be accessible

- Suspended vets
	- Login blocked while suspension is active: `403 Forbidden`
	- Token/session issued: no new token should be granted for blocked login attempts
	- Temporary suspension should be reversible by admin workflow; permanent suspension should remain blocked until explicitly restored
	- Protected vet endpoints remain unavailable until the account is reinstated

## Output Expectation

Accepted report formats:

1. Markdown report

	 Store in: `tests/reports/auth/`

	 Template:

	 ```md
	 # Auth Test Report

	 - Date:
	 - Environment:
	 - Base URL:
	 - Tester:
	 - Summary:
	 - Passed:
	 - Failed:

	 ## Scenarios
	 | Scenario | Endpoint | Request | Expected | Actual | Status |
	 | --- | --- | --- | --- | --- | --- |
	 | Register/login/logout | POST /auth/register | curl ... | 201/200 | ... | Pass |
	 ```

2. JSON report

	 Store in: `tests/reports/auth/`

	 Template:

	 ```json
	 {
		 "date": "2026-04-24",
		 "environment": "local",
		 "base_url": "http://localhost:8002/api/v1",
		 "tester": "name",
		 "scenarios": [
			 {
				 "name": "register-login-logout",
				 "endpoint": "POST /auth/register",
				 "expected_status": 201,
				 "status": "passed"
			 }
		 ]
	 }
	 ```

Submission locations:

- Preferred: commit the report under `tests/reports/auth/` with the test run artifact name
- Optional: create a GitHub issue labeled `auth-test-report` and link the markdown or JSON file in the issue body

Auth testing report should include:

- executed commands
- passed scenarios
- failed scenarios with root causes
- remediation actions and follow-up tests
