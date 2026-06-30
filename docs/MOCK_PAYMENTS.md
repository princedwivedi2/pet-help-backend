# Mock Payment Mode

Allows the full booking and consultation payment flow to work end-to-end during
development and testing **without real Razorpay credentials**.

---

## Enabling mock mode

In your `.env` (never in production):

```env
PAYMENTS_MOCK=true
```

Config key: `config('services.payments.mock')` — cast to bool, default `false`.

When `PAYMENTS_MOCK=true`:
- `POST /payments/create-order` skips the Razorpay API and returns a fake order ID (`order_mock_<uuid>`).
- `POST /payments/mock-confirm` becomes active and marks the payment as paid, running every real post-capture side-effect.
- `POST /payments/verify` (the real Razorpay path) still works — it is not disabled.

When `PAYMENTS_MOCK=false` (default / production):
- `POST /payments/mock-confirm` returns **404** — it is invisible.
- Everything else is unchanged from the real Razorpay flow.

---

## Endpoint: POST /payments/mock-confirm

**Auth:** Bearer token (any authenticated user)  
**Condition:** Only active when `PAYMENTS_MOCK=true` AND `APP_ENV != production`

### Request

```json
{
  "payment_uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `payment_uuid` | string (UUID) | yes | UUID of the Payment row returned by `create-order` |

The payment must belong to the authenticated user (`user_id` match). Returns 404 if not found.

### Response (success)

```json
{
  "success": true,
  "message": "Payment confirmed",
  "data": {
    "payment": {
      "uuid": "...",
      "payment_status": "paid",
      "paid_at": "2026-06-19T10:00:00Z",
      "razorpay_payment_id": "mock_pay_<uuid>",
      "amount": 50000,
      "payable_type": "appointment",
      "payable_id": 7,
      ...
    }
  },
  "errors": null
}
```

### Response (flag off)

```json
HTTP 404 Not Found
```

### Response (already paid — idempotent)

Returns the same 200 success with the existing `paid` payment. Safe to retry.

---

## Side-effects on confirm (identical to real Razorpay capture)

| Payable type | Side-effect |
|---|---|
| `appointment` | `appointments.payment_status = 'paid'`, `payment_mode` set |
| `consultation` | `consultation_sessions.payment_id` linked (unlocks the `/join` gate) |
| `sos_request` | `sos_requests.emergency_charge` updated |
| `subscription` | `Subscription` row created and activated |
| Any with vet + payout > 0 | `VetWallet` credited, `WalletTransaction` row inserted |
| All | `AuditLog` entry with action `mock_payment_confirmed` |

---

## Full mock flow (appointment example)

```
# 1. Book an appointment (existing flow — unchanged)
POST /api/v1/appointments
→ { appointment_uuid: "appt-uuid" }

# 2. Create a payment order (mock mode — no Razorpay call)
POST /api/v1/payments/create-order
Body: { "payable_type": "appointment", "payable_uuid": "appt-uuid" }
→ {
    "data": {
      "payment": { "uuid": "pay-uuid", "razorpay_order_id": "order_mock_...", ... },
      "payment_uuid": "pay-uuid",
      "razorpay_key": ""       ← empty in mock mode
    }
  }

# 3. Skip Razorpay SDK — confirm directly
POST /api/v1/payments/mock-confirm
Body: { "payment_uuid": "pay-uuid" }
→ { "data": { "payment": { "payment_status": "paid", ... } } }

# 4. Appointment is now payment_status=paid; proceed with booking flow
```

## Full mock flow (consultation example)

```
# 1. Start a consultation
POST /api/v1/consultations
Body: { "modality": "video", "fee_amount": 50000, ... }
→ { consultation: { uuid: "consult-uuid", fee_amount: 50000, ... } }

# 2. Create payment for the consultation
POST /api/v1/payments/create-order
Body: { "payable_type": "consultation", "payable_uuid": "consult-uuid" }
→ { data: { payment_uuid: "pay-uuid", razorpay_order_id: "order_mock_..." } }

# 3. Mock-confirm
POST /api/v1/payments/mock-confirm
Body: { "payment_uuid": "pay-uuid" }
→ payment.status = paid; consultation_sessions.payment_id is linked

# 4. Join the consultation (payment gate now passes)
POST /api/v1/consultations/consult-uuid/join
→ { token, ... }
```

---

## Production safety

Two independent guards prevent mock-confirm from ever running in production:

1. **Config flag guard** (`isForcedMock()`) — `PAYMENTS_MOCK` defaults to `false`; the endpoint returns 404 when false.
2. **Environment guard** — `mockConfirm()` explicitly checks `APP_ENV !== 'production'` and returns 404 regardless of the flag.

Neither guard is bypassable without a code change + deploy.

---

## Switching to real Razorpay

1. Set `PAYMENTS_MOCK=false` (or remove the line).
2. Set `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` in `.env`.
3. Register `https://your-domain/api/v1/payments/webhook` in the Razorpay Dashboard for events `payment.captured` and `order.paid`.
4. No code changes required — the real path was never touched by this feature.
