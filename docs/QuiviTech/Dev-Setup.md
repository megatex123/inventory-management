---
tags: [dev-setup, docker, environment]
---

# Dev Setup (local, this sandbox)

Set up 2026-07-10. This machine's Claude Code session runs inside a **Flatpak-sandboxed VSCode** — the Bash tool's own filesystem/PATH is not the real host. Reach the real host with `flatpak-spawn --host <cmd>`. All commands below assume that prefix where noted.

## Why Docker instead of host PHP

Host has `php74` (`/usr/bin/php74`, from a manually-built `php74-cli` package — not in any synced pacman repo, so no sibling extension packages to install) but it's missing **pdo_mysql, ctype, tokenizer, xml, fileinfo, bcmath, gd, zip** — only curl/iconv/json/mbstring/openssl/phar are compiled in. Fixing it needs root (sudo requires a password not available here) and possibly a source rebuild.

The repo's own `Dockerfile` (`php:7.4-apache`, installs `pdo_mysql` + `zip`) already has what's needed and requires no host changes. **Use it for all composer/artisan work.**

## Build the image

```bash
cd /home/penyahpepijat/claude/inventory-management
docker build -t quivitech-im:local --target web .
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

If you hit this on a migration with `->change()`: apply the equivalent `ALTER TABLE ... MODIFY COLUMN ...` as raw SQL directly against the DB, then insert a row into the `migrations` table to mark it as run (don't re-attempt via `artisan migrate`). This is exactly what happened historically with `2026_07_02_150000_fix_inventory_tables_schema` — it's very unlikely that migration was ever actually run via `artisan migrate` on production either, given this same class-not-found failure would occur there too.

## Verified working (2026-07-10)

`docker ps` → `quivitech-im-dev` up, `lokaldb` up. `curl http://127.0.0.1/` → 200, renders `<title>Quivitech - Dashboard</title>`. `php artisan migrate:status` → all migrations applied (after backfilling records for tables that existed live without migration history — see [[Domain-Models]]).

## Related
- [[Deployment]] — production Docker/Kubernetes (different image workflow than local dev)
- [[Domain-Models]] — live-DB-as-ground-truth schema audit history
- [[Project-Overview]]
