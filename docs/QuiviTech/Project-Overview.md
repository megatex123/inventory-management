---
tags: [overview]
---

# Project Overview

Quivitech is a **Laravel 7 + Vue 2** management system (per `README.md`: "Manage the Product, Stock, Customers, Expenses"). It's a single-page app: Laravel serves one Blade view (`welcome`) and Vue Router handles all client-side routing (see `routes/web.php` catch-all `{any}` route).

In practice the app has grown well beyond product/stock/customers/expenses — it now covers a repair/service business: **product warranties, care (repair) jobs, "serve" (service) jobs with sub-types (PCE, MPS, BEK), meetings, and POS/orders.** See [[Domain-Models]].

## Tech stack
- **Backend**: PHP 7.2+/8.0, Laravel 7.29, `tymon/jwt-auth` (dev-develop) for API auth, `intervention/image` for image handling, `laravel/ui` for scaffolding.
- **Frontend**: Vue 2.5, Vue Router 3, Bootstrap 4, jQuery, Laravel Mix/webpack for build, SweetAlert2 + Noty for UI notifications.
- **DB**: MySQL (`tarekuld_lvpos`), migrations under `database/migrations`.
- **Container**: `php:7.4-apache` Docker image (see [[Deployment]]).

## Local setup (from README)
```
composer install
npm install
npm run dev
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

## Key env vars (`.env`)
- `DB_DATABASE=tarekuld_lvpos`, `DB_HOST=127.0.0.1`
- `JWT_SECRET` set for `tymon/jwt-auth`
- `SOFTWAREVERSION=0.0.0` in `.env.example` — actual working copy is versioned higher (git tags "v 0.0.4")
- Mail configured for Mailtrap (dev), Pusher/AWS placeholders unset

## Related
- [[Architecture]]
- [[API-Routes]]
