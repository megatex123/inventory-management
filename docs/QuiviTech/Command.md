---
tags: [reference, commands, docker, dev-setup]
---

# Commands

Quick-reference cheatsheet of every operational command for this project — build, run, database, frontend, deployment. Full rationale/gotchas for these live in [[Dev-Setup]] and [[Deployment]]; this note is just the command list, kept in sync with those.

## Local dev: build & run the app

```bash
cd /home/penyahpepijat/claude/inventory-management

# Build the app image (multi-stage Dockerfile; no --target flag needed/valid)
docker build -t quivitech-im:local .

# First run (creates the container, binds to host port 80 via --network host)
docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local

# Subsequent starts/stops
docker start quivitech-im-dev
docker stop quivitech-im-dev

# After a Dockerfile/composer.json change, recreate the container with the new image
docker rm -f quivitech-im-dev
docker build -t quivitech-im:local .
docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local
```

App: `http://127.0.0.1/`

**One-time permission fix after a fresh volume mount** (bind mount shadows the image's build-time `chown`):
```bash
chmod -R 777 storage bootstrap/cache
chmod -R 775 public/backend/suppliers public/backend/products && chgrp -R www-data public/backend/suppliers public/backend/products
```

## Composer / artisan (via the same image, mounted against the repo)

Run with `--network host` so the container can reach the host's published `lokaldb` port:
```bash
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local composer install
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan key:generate
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate
```
Or, once `quivitech-im-dev` is already running, exec straight into it instead of spinning up a throwaway container:
```bash
docker exec quivitech-im-dev php artisan migrate
docker exec quivitech-im-dev php artisan tinker
```

## Database

Container `lokaldb` (MariaDB, shared across local projects) — `root` / `nopassword2026!`, database `quivi`.

```bash
# Raw SQL against the live DB
docker exec lokaldb mariadb -uroot -p"nopassword2026!" quivi -e "SHOW TABLES;"

# Interactive shell
docker exec -it lokaldb mariadb -uroot -p"nopassword2026!" quivi

# Full dump to quivi.sql (routines + triggers + single-transaction)
./dump-db.sh                    # writes quivi.sql at repo root
./dump-db.sh path/to/output.sql # or a custom path
```
`dump-db.sh` reads `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` from `.env` (all overridable via env vars of the same name), container name via `DB_CONTAINER` (default `lokaldb`).

## Git

```bash
./git-push.sh                    # stages everything, commits with an auto timestamp message, pushes current branch
./git-push.sh "your message"     # same, with your own commit message
```
Excludes the `docs/QuiviTech/.obsidian/` vault UI-state files (window layout, graph settings — not project content) from what it stages (`.env` is separately gitignored already). No-ops (doesn't commit/push) if there's nothing to stage. Pushes to `origin/<current branch>` — doesn't create or switch branches, doesn't force-push.

## Frontend (Node 12 via nvm)

nvm lives at `~/.var/app/com.visualstudio.code/config/nvm`, not `~/.nvm` — see [[Dev-Setup]] for why.
```bash
export NVM_DIR="$HOME/.var/app/com.visualstudio.code/config/nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm use 12

npm install
npm run dev      # one-off build
npm run watch    # rebuilds public/js/app.js + public/css/app.css on file change (long-running)
```

## Deployment

```bash
./docker-build-push.sh   # builds + tags + pushes the production image (reads SOFTWAREVERSION from .env), then rolls the compose stack
```
Kubernetes manifests live under `deployment/` — check `deployment/README.md` for the actual cluster deploy procedure before touching them; see [[Deployment]].

## Related
- [[Dev-Setup]] — full explanation and gotchas behind the local-dev commands above
- [[Deployment]] — production Docker/Kubernetes detail
- [[Domain-Models]] — what's actually in the DB the dump/query commands above touch
