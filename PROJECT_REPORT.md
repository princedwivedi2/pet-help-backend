# ReSpaw Project Report

## Security & Compliance

- Sanctum auth and role boundaries are already in place.
- Sensitive vet and pet medical data should stay private by default.
- Vet verification documents require owner/admin-only access.
- Audit trail and reviewable operational logs remain necessary for production signoff.
- Payment verification must reconcile gateway truth before launch.

## Deployment Strategy

- Dev: local Laravel API with current route surface and feature tests.
- Staging: freeze `docs/MOBILE_API_CONTRACT.md`, validate mobile integration, and confirm queue/scheduler behavior.
- Prod: release only after payment, private document, and SOS hardening are complete.
- Rollback: use tagged releases, database migration review, and restore-safe config changes.
- CI/CD: run feature tests, doc checks, and release artifacts before promoting builds.

## Operational Readiness

- Monitoring/alerting: scheduler, queue, payment, and auth failures need visible logs and alerts.
- Incident response: document who owns payment, SOS, and document-access incidents.
- On-call rotation: backend and QA should share launch-period coverage.
- Runbook: include scheduler checks, queue health, auth smoke tests, and recovery steps.
- Test checklist: payment verification, document authorization, SOS queue dispatch, and appointment reliability.

## Executive Overview

ReSpaw is a multi-role pet care platform connecting pet owners, veterinarians, and admins.

The backend is a Laravel 12 API with broad completed functionality. Current evidence shows 211 API routes, core multi-role modules in place, and five launch-hardening gaps still open.

## Product Architecture Direction

- Backend: existing Laravel 12 API (`pet-help-backend`)
- Mobile: one shared React Native/Expo app for user and vet roles
- Admin: web-first dashboard (`pet-help-admin`)
- Payments: Razorpay
- Notifications: FCM/device tokens
- Data store: MySQL

## Platform Scope Implemented

- User
- Pet
- Vet
- SOS
- Appointment
- Payment
- Review
- Blog
- Community
- Chatbot
- Admin
- Scheduler-backed lifecycle handling for time-sensitive flows
- Test and postman assets present

## Readiness Assessment

Strong MVP backend foundation exists. Production readiness now depends on focused hardening, not broad re-architecture.

## Production Gap List

1. [P0] Razorpay payment/order amount-status reconciliation
	- Current state: payment flows exist, but gateway reconciliation is still the launch blocker.
2. [P0] Private storage and secure serving for sensitive vet documents
	- Current state: document privacy must be confirmed end-to-end before launch.
3. [P1] Queue-backed SOS nearby-vet notifications
	- Current state: SOS dispatch needs queue hardening for response reliability.
4. [P1] Frozen mobile API contract documentation
	- Current state: mobile consumers still need a contract freeze point.
5. [P1] Stable and repeatable full test suite run
	- Current state: test repeatability and CI evidence still need to be formalized.

## Immediate Priority Sequence

1. Backend hardening (gaps 1, 2, 3, 5) - 2 weeks - 2 backend engineers + QA - sequential predecessor to mobile build
2. Mobile API contract freeze (gap 4) - 1 week - 1 backend engineer + 1 mobile engineer - can start after gap 1 reaches reviewable state
3. RN/Expo app build (role-based) - 3 weeks - 2 mobile engineers + backend support - parallel with final backend QA once contract is frozen
4. End-to-end QA and launch readiness - 1 week - QA + backend + mobile - follows the build and contract freeze milestones
