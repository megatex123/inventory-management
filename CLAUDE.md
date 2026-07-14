# Quivitech Inventory Management

---

## Standing Orders: Obsidian Vault

The folder `docs/QuiviTech/` at the project root is an **Obsidian vault**. It is the living documentation for this project — open it in Obsidian as a vault, or just read the `.md` files directly.

**Before doing any non-trivial work, read the relevant vault note(s) first.** It will give you faster orientation than re-reading source files from scratch — this codebase has several non-obvious traps (untracked tables, a broken Doctrine/DBAL migration, a host PHP that's missing extensions) that are already documented there.

**Whenever you make a meaningful change to the codebase or the dev environment, update the relevant vault note(s) too.** Treat the vault as the source of truth for project understanding, not just a one-time snapshot.

### Which note covers what

| Changed area | Note to update |
|---|---|
| New route, controller, or API endpoint | [[API-Routes]] |
| New Eloquent model, relation, or schema quirk found in the live DB | [[Domain-Models]] |
| New Vue component module under `resources/js/components` | [[Frontend-Components]] |
| Backend/frontend structure, request flow | [[Architecture]] |
| Local dev environment (Docker, PHP, nvm, DB) changes or gotchas | [[Dev-Setup]] |
| Production Docker image / Kubernetes deploy | [[Deployment]] |
| Anything in-flight / not yet fully wired up | [[Work-In-Progress]] |
| Stack, versions, "what is this app" | [[Project-Overview]] |

### Rules for editing vault notes

1. **Use Obsidian wiki-links** (`[[Note Name]]`) when referencing another note — never plain text or file paths.
2. **Do not duplicate content** across notes — link instead.
3. **The live DB is ground truth, not the migration files** — several tables (`brand`, `care_data`, `master_sku`, `serve_data`, etc.) were historically created outside of migrations. Always check `DESCRIBE <table>` against the running DB before trusting a model's `$fillable`/`$casts`, especially for anything not backed by a clean migration history. See [[Domain-Models]] for the ones already audited.
4. If you create a genuinely new area (a whole new subsystem), create a new note and link it from `General.md`.

### At the start of a new conversation

Read `docs/QuiviTech/General.md` first, then the note(s) relevant to the task.

---

## What this is

Laravel 7 + Vue 2 SPA for a PC-building/repair shop (Quivitech): products/stock, customers, POS/orders, warranties, and repair ("Care") / service ("Serve") job tracking. See [[Project-Overview]] for the full picture.

## Running it locally

The host's native `php7.4` is missing extensions Laravel needs (pdo_mysql, ctype, tokenizer, xml, fileinfo, bcmath). **Use the project's own Docker image instead of host PHP** — see [[Dev-Setup]] for full detail. Quick reference:

```bash
# App container (php:7.4-apache, built from this repo's Dockerfile)
docker build -t quivitech-im:local --target web .
docker run -d --name quivitech-im-dev --network host -v "$(pwd):/var/www/html" quivitech-im:local

# Composer / artisan via the same image, mounted against the repo
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local composer install
docker run --rm --network host -v "$(pwd):/var/www/html" quivitech-im:local php artisan migrate

# Frontend (Node 12 via nvm — nvm lives at $HOME/.var/app/com.visualstudio.code/config/nvm, not ~/.nvm)
nvm use 12 && npm install && npm run watch
```

DB: local MariaDB container `lokaldb` (already running, shared across projects), database `quivi`, `root` / `nopassword2026!`. **Not** the placeholder `tarekuld_lvpos` credentials in `.env.example`.

App: `http://127.0.0.1/`

## Domain

Part of the `percubaan.com` server-wide setup — see `/home/penyahpepijat/claude/CLAUDE.md` for cross-project conventions (this file takes precedence for anything specific to this project).
