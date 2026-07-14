# Quivitech Management System

Laravel 7 + Vue 2 SPA for a PC-building/repair shop: products & stock, customers, POS/orders, warranties, and post-sale service tracking.

Beyond basic inventory/POS, it covers three linked service programs tied to each order:
- **QuiviCraft** — PC-build order inspection & QC (`craft_inspections`)
- **QuiviServe** — post-build service tiers: BEK / MPS / PCE, each with its own claim tracking
- **QuiviCare** — RMA/warranty & spare-parts inventory (`care_data`, `care_warranty`, `inv_care`)

Plus Inventory Movement tracking (stock transfers linked to `master_sku`/`destination`/orders) and Customer Progress tracking (per-customer milestone/status tracking).

---

## Project docs

Deeper documentation — architecture, API routes, DB schema quirks, frontend component map, and known dev-environment gotchas — lives in `docs/QuiviTech/`, an Obsidian vault. Start at [`docs/QuiviTech/General.md`](docs/QuiviTech/General.md). It's kept up to date as the source of truth for how this app actually works, including places where the live database has drifted from what the code alone would suggest.

---

#### Project Key Metrics

- Laravel v7.0
- Vue.js v2.0
- PHP 7.4
- Node 12 (via nvm)
- MySQL/MariaDB

---

## Local setup

Host PHP installs commonly lack extensions Laravel 7 needs (`pdo_mysql`, `ctype`, `tokenizer`, `xml`, `fileinfo`, `bcmath`). This repo's own `Dockerfile` (`php:7.4-apache`) has everything required — use it for all Composer/Artisan work instead of relying on host PHP.

```bash
# 1. Build the app image
docker build -t quivitech-im:local --target web .

# 2. Configure environment
cp .env.example .env
# then set DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD
# for your MySQL/MariaDB instance

# 3. Install PHP deps, generate app key, run migrations + seed reference data
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local composer install
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan key:generate
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate --seed

# 4. Serve the app (Apache binds directly to host port 80)
docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local
chmod -R 777 storage bootstrap/cache   # first run only, after a fresh volume mount
```

App at `http://127.0.0.1/`.

`--network host` assumes your DB is reachable at `127.0.0.1:<port>` from the container; adjust `DB_HOST`/networking if your database runs elsewhere (e.g. a named container without `--network host`).

### Frontend

```bash
nvm install 12 && nvm use 12
npm install
npm run dev      # one-off build
npm run watch    # rebuilds public/js/app.js + public/css/app.css on change
```

### Migrations

Every migration in `database/migrations/` runs cleanly on an empty database (`migrate:fresh --seed`) and reproduces the live schema — verified 2026-07-13. **Do not write new migrations using `->change()`, `renameColumn()`, or `getDoctrineSchemaManager()`** — all three require `doctrine/dbal`, which isn't installed and can't be with this project's Laravel 7 + locked `nesbot/carbon` combo (installing it either conflicts with Carbon or hits a removed-class error at runtime). Use raw SQL instead, e.g. `DB::statement("ALTER TABLE x MODIFY \`col\` ...")`. See `docs/QuiviTech/Dev-Setup.md` for the full writeup.

---

## Production deployment

Ships as a Docker image to Kubernetes; `docker-compose.yaml` in this repo is a reference for running the published image standalone. See `docs/QuiviTech/Deployment.md`.

---

## License

Basically, feel free to use and re-use any way you want.
