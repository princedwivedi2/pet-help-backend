<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## First Admin Bootstrap

Use the `admin:create` Artisan command to create the initial admin user. The command is **idempotent** — running it multiple times is safe and will not create duplicates.

```bash
# Create admin with defaults (admin@petsathi.com / prompted for password)
php artisan admin:create

# Specify all options
php artisan admin:create \
  --email=admin@yourdomain.com \
  --name="Super Admin" \
  --password=yourSecurePassword123
```

| Option | Default | Description |
|---|---|---|
| `--email` | `admin@petsathi.com` | Admin email address |
| `--name` | `Admin` | Admin display name |
| `--password` | *(prompted)* | Password (min 8 chars) |

**Behaviour:**
- Creates the user and sets `role = admin` if the email does not yet exist.
- If a user with that email already exists as an admin, exits with a success message and makes no changes.
- If a user with that email exists but has a different role, exits with an error without modifying the record.

## Subscription Purchase API

### List available plans
```
GET /api/v1/subscription-plans
```

### Purchase a plan (Step 1 — create Razorpay order)
```
POST /api/v1/subscriptions
Authorization: Bearer <token>

{
  "action": "create_order",
  "plan_uuid": "<plan uuid>"
}
```

### Purchase a plan (Step 2 — verify payment & activate)
```
POST /api/v1/subscriptions
Authorization: Bearer <token>

{
  "action": "verify",
  "razorpay_order_id": "...",
  "razorpay_payment_id": "...",
  "razorpay_signature": "..."
}
```

### Get active subscription
```
GET /api/v1/subscriptions/active
Authorization: Bearer <token>
```

## Admin Payout Approval

### List pending payout requests
```
GET /api/v1/admin/payouts/pending
Authorization: Bearer <admin token>
```

### Process (approve) a payout
```
POST /api/v1/admin/payouts/{vet_uuid}/process
Authorization: Bearer <admin token>

{
  "notes": "Optional admin note"    (optional)
}
```

The endpoint is **idempotent** — a vet's payout request can only be processed once. A second call will return `422` if no pending request remains.

**What happens on approval:**
1. `wallet.balance` is debited by the pending payout amount.
2. `wallet.pending_payout` is decremented.
3. `wallet.total_paid_out` is incremented.
4. A `payout_completed` `WalletTransaction` record is created.
5. The original `payout_request` transaction is marked `completed`.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
