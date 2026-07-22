---
tags: [dev-setup, docker, environment]
---

# Dev Setup (local, this sandbox)

Set up 2026-07-10. This machine's Claude Code session runs inside a **Flatpak-sandboxed VSCode** — the Bash tool's own filesystem/PATH is not the real host. Reach the real host with `flatpak-spawn --host <cmd>`. All commands below assume that prefix where noted.

## Why Docker instead of host PHP

Host has `php74` (`/usr/bin/php74`, from a manually-built `php74-cli` package — not in any synced pacman repo, so no sibling extension packages to install) but it's missing **pdo_mysql, ctype, tokenizer, xml, fileinfo, bcmath, gd, zip** — only curl/iconv/json/mbstring/openssl/phar are compiled in. Fixing it needs root (sudo requires a password not available here) and possibly a source rebuild.

The repo's own `Dockerfile` (`php:7.4-apache`, installs `pdo_mysql`, `zip`, `gd`) already has what's needed and requires no host changes. **Use it for all composer/artisan work.**

`gd` was added 2026-07-22 (previously missing — any supplier/product photo upload 500'd with "GD Library extension not available", since `SuppliersController`/`ProductsController` resize uploads via `Intervention\Image`). If your running container predates that change, `docker-php-ext-install gd` inside it live, or just rebuild.

## Build the image

The Dockerfile is multi-stage (`frontend` → `composer` → `production`); there's no `web` target anymore (that was the old pre-2026-07-15 structure — don't use `--target web`, the build will fail with "target stage not found"). Build the default (last) stage:

```bash
cd /home/penyahpepijat/claude/inventory-management
docker build -t quivitech-im:local .
```

## Database

A MariaDB container `lokaldb` is already running on the host (shared across other local projects, not specific to this repo):
```
container: lokaldb
port: 3306 (published to host)
root password: nopassword2026!
database: quivi   (already has real data — see [[Domain-Models]] for schema history)
```

`.env` should have:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quivi
DB_USERNAME=root
DB_PASSWORD=nopassword2026!
```
(`.env.example` ships placeholder `tarekuld_lvpos` cPanel-style credentials that don't apply locally — copy `.env.example` to `.env` then overwrite `DB_*`.)

To run raw SQL against it directly (e.g. to inspect schema without going through artisan):
```bash
docker exec lokaldb mariadb -uroot -p"nopassword2026!" quivi -e "SHOW TABLES;"
```

## Composer / artisan (via the container, mounted against the repo)

Run these with `--network host` so the container can reach `127.0.0.1:3306` (the host's published lokaldb port) exactly like `DB_HOST=127.0.0.1` expects:

```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local composer install
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan key:generate
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate
```

`vendor/` gets written to the host filesystem this way (useful for editor tooling) rather than being sealed inside the image.

## Serving the app

```bash
docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local
```
`--network host` means Apache binds directly to the host's port 80 — no `-p` mapping. App at `http://127.0.0.1/`.

**After a fresh volume mount, fix permissions once** (the image's build-time `chown www-data` gets shadowed by the host bind mount):
```bash
chmod -R 777 storage bootstrap/cache
```
A few files created by the container as `root` (e.g. `bootstrap/cache/services.php`, `storage/logs/laravel.log`) can't be chmod'd by a non-root host user afterwards — harmless, they're already world-readable (644/755), Laravel just can't write new log lines until the container recreates them with looser perms.

**Same issue hits `public/backend/{suppliers,products}/`** (host-owned from the bind mount, `www-data` can't write) — any photo upload there 500s with `NotWritableException` even after `gd` is installed. Fix once the same way:
```bash
chmod -R 775 public/backend/suppliers public/backend/products && chgrp -R www-data public/backend/suppliers public/backend/products
```

Restart later: `docker start quivitech-im-dev`. Rebuild after a `Dockerfile`/`composer.json` change: re-run the `docker build` above (container needs recreating to pick up a new image — `docker rm -f quivitech-im-dev` then re-run `docker run -d ...`).

## Frontend (Node 12 via nvm)

nvm is **not** at `~/.nvm` on this host — it's at `~/.var/app/com.visualstudio.code/config/nvm` (check `~/.bashrc`, it sets `NVM_DIR` twice; the second one wins and points to `~/.nvm`, which doesn't have `nvm.sh` — the first, real one is the VSCode-flatpak-provisioned path):

```bash
export NVM_DIR="$HOME/.var/app/com.visualstudio.code/config/nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm install 12   # only needed once — installs v12.22.12
nvm use 12
```

Then normal Laravel Mix workflow:
```bash
npm install
npm run dev      # one-off build
npm run watch    # rebuilds public/js/app.js + public/css/app.css on file change
```
`npm run watch` is a long-running foreground process — run it in the background and tail its output rather than blocking on it.

## Known gotcha: Doctrine DBAL is broken for this Laravel/Carbon combo

Any migration using Schema `->change()` (column alteration) needs `doctrine/dbal`. **Don't `composer require doctrine/dbal` to fix it** — it doesn't actually work here:
- `doctrine/dbal ^2.x` (the version Laravel 7's `MySqlConnection::getDoctrineDriver()` expects — references the old class path `Doctrine\DBAL\Driver\PDOMySql\Driver`) **conflicts** with this project's locked `nesbot/carbon 2.73.0` → `carbonphp/carbon-doctrine-types`.
- `doctrine/dbal ^3.x` installs fine but Laravel 7's code can't find `Doctrine\DBAL\Driver\PDOMySql\Driver` (renamed in DBAL 3) → fails at runtime with `Class 'Doctrine\DBAL\Driver\PDOMySql\Driver' not found`.

**Don't write new migrations using `->change()`, `renameColumn()`, or `getDoctrineSchemaManager()`** — all three require doctrine/dbal. Use raw SQL instead: `DB::statement("ALTER TABLE x MODIFY \`col\` ...")` for column-type changes, `DB::statement("ALTER TABLE x CHANGE \`old\` \`new\` ...")` for renames, and an `information_schema.statistics` query for index-existence checks. As of 2026-07-13 every migration in `database/migrations/` has been audited and rewritten this way — `2025_12_25_140337_update_customers_table_add_registration_fields` and `2026_07_02_150000_fix_inventory_tables_schema` were the two offenders (both `->change()`-based, plus the customers one also used `renameColumn()`/`getDoctrineSchemaManager()`). Both now run cleanly via plain `artisan migrate`, no backfilling required.

## Migrations are now fresh-install-clean (2026-07-13)

Historically ~19 of the ~40 migration files were marked "No" in `migrate:status` on the live `quivi` DB — not because they were broken, but because their tables were created directly against the DB outside the migration system (see [[Domain-Models]]). That masked several migration files that had genuinely drifted from live schema and would either error or produce the wrong schema on a true fresh install (empty DB, `php artisan migrate` from scratch). Audited by diffing `SHOW CREATE TABLE` on live `quivi` against a scratch DB (`quivi_migration_test`) migrated from empty, then fixed until they matched. Beyond the two Doctrine offenders above, real drift found and fixed:
- `2025_12_29_124813_create_meetings_table`: `->after('id')` inside `Schema::create()` is invalid (only valid in `Schema::table()`) and errored out fresh migrate entirely; `meeting_id` was also wrongly nullable.
- `2026_01_03_143720_create_meeting_details_table`: several columns had the wrong type entirely (`reason`/`play_mode`/`case_size`/`include_monitor` were string/enum/int instead of tinyint) and `include_notes`/`qvtd_notes` were missing altogether.
- `2021_06_06_073520_create_order_details_table`: missing `serial_no` and `start_warranty_at` (added live outside any migration).
- `2021_06_02_134411_create_suppliers_table`: missing `supplier_id` (NOT NULL, auto-generated `QV-SUPP-XXXX` by `SuppliersController@store`) and lacked soft deletes; `email`/`phone`/`address` were NOT NULL, live has them nullable.
- `2021_06_02_174502_create_products_table`: badly stale — still had early SSD-only placeholder columns (`capacity`/`form`/`interface`/`read`/`write`/`tier`) instead of the real generic component-spec schema (`is_care`, `category_name`, `sub_cat_id`, `brand_id`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `product_loan`, etc.) that's actually live.
- New migration `2026_07_11_120000_add_missing_columns_to_order_table`: the `order` table's `order_id`, `invoice_id`, `approve`, `approved_at`, `is_reason` columns had no migration at all (added directly to live DB); `order_date`/`craft_id`/`serve_id`/`care_id` had also drifted (type/nullability). This one runs right after `create_order_table`.

Remaining diffs between a fresh `migrate:fresh` and live `quivi` are cosmetic only (signed vs unsigned `id`/`bigint` display width, `int(11)` vs `int(20)` display width, auto-generated vs custom index names, and a few `created_at`/`updated_at` `ON UPDATE` placement swaps) — none affect app behavior, since Eloquent sets timestamps explicitly on every write path in this codebase. Verify with: create a scratch DB, run `docker exec -e DB_DATABASE=<scratch> quivitech-im-dev php artisan migrate:fresh --force`, diff `SHOW CREATE TABLE` against `quivi`.

## Verified working (2026-07-10)

`docker ps` → `quivitech-im-dev` up, `lokaldb` up. `curl http://127.0.0.1/` → 200, renders `<title>Quivitech - Dashboard</title>`. `php artisan migrate:status` → all migrations applied (after backfilling records for tables that existed live without migration history — see [[Domain-Models]]).

## Related
- [[Deployment]] — production Docker/Kubernetes (different image workflow than local dev)
- [[Domain-Models]] — live-DB-as-ground-truth schema audit history
- [[Project-Overview]]
