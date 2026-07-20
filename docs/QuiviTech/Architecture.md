---
tags: [architecture]
---

# Architecture

## Request flow
- `routes/web.php`: only real routes are `/login` (GET/POST) and `/logout`; everything else (`/{any}`) falls through to the `welcome` Blade view, which boots the Vue SPA.
- `routes/api.php`: all real business logic is exposed here as JSON endpoints, consumed by the Vue app via Axios. See [[API-Routes]] for the full map.
- Auth: `AuthController` handles `/api/auth/*` (login/signup/logout/refresh/me) via JWT (`app/Http/Middleware/JWT.php`). Session-based `/login` also exists for the Blade-served welcome page (Laravel UI scaffolding under `app/Http/Controllers/Auth/*`).
- **Sidebar menu is DB-driven (2026-07-20).** `welcome.blade.php`'s `<ul id="accordionSidebar">` used to be ~290 lines of hand-written HTML; it's now a `@foreach` over `MenuItem` rows (self-referential `parent_id`, 3 levels: root `group`/`link` → `header` → `link`), injected via a `View::composer('welcome', ...)` in `AppServiceProvider::boot()` and rendered through the recursive `resources/views/partials/sidebar-menu-item.blade.php`. To change the menu, edit `menu_items` rows (via Tinker/SQL) — do not add hardcoded `<li>` blocks back into `welcome.blade.php`. Seeded from `database/seeds/MenuItemsTableSeeder.php`, which is the source of truth for what "default" menu content looks like. No admin UI for this exists — editing is DB-only by design (confirmed scope).

## Backend layout
- `app/Http/Controllers/` — one controller per resource, mostly thin REST controllers (`apiResource` bound in `routes/api.php`) plus a few controllers with custom action methods (`PosController`, `OrderController`, `CustomersController` for public hash-link updates).
- `app/Models/` — Eloquent models, mostly flat with `belongsTo`/`hasMany` relations. Soft deletes used heavily (`SoftDeletes` trait) across newer models. See [[Domain-Models]].
- `app/Console/Commands/AutoDeleteUnapproved.php` — scheduled cleanup command (check `app/Console/Kernel.php` for schedule).

## Frontend layout
- `resources/js/components/<feature>/` — one folder per business feature (product, care, serve, serve_bek, serve_mps, serve_pce, pos, order, meeting, meeting_details, customer, employee, salary, expens, category, sub_category, brand, craft, suppliers, care_data, care_warranty, product_warranty, auth, stock). See [[Frontend-Components]].
- `resources/js/Helpers` — shared JS helpers (e.g. Axios instance, formatting).
- Build: Laravel Mix / webpack (`webpack.mix.js`), output compiled to `public/js/app.js` — **this file is currently modified/tracked in git status, likely a build artifact drift; don't hand-edit it.**

## Notable pattern: "serve" sub-modules
The service/repair domain splits into a parent `ServeData` plus specialized sub-types with their own controllers/routes/components: `ServePce`, `ServeMps`, `ServeBek`. Each has near-identical CRUD + statistics/search endpoints — a copy-paste pattern rather than shared abstraction (route comments in `routes/api.php` literally say "Corrected - no duplicate routes" and "Updated to match Vue component", suggesting this grew organically / was patched multiple times).

## Related
- [[Domain-Models]]
- [[API-Routes]]
- [[Work-In-Progress]]
