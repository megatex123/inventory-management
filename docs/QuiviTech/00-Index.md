---
tags: [moc]
---

# Quivitech — Second Brain Index

Map of content for the Quivitech project notes. Open this folder (`docs/second-brain/`) as an Obsidian vault.

## Notes
- [[Project-Overview]] — what this app is, tech stack, how to run it
- [[Architecture]] — Laravel + Vue structure, how frontend/backend connect
- [[Domain-Models]] — Eloquent models grouped by business area, with relations
- [[API-Routes]] — full `routes/api.php` map by feature
- [[Frontend-Components]] — Vue component modules under `resources/js/components`
- [[Dev-Setup]] — local dev environment: Docker workaround for host PHP, DB creds, nvm, known Doctrine/DBAL gotcha
- [[Deployment]] — Docker + Kubernetes production deployment setup
- [[Work-In-Progress]] — in-flight / not-yet-wired-up work

## Quick facts
- Laravel 7 + Vue 2 SPA ("Quivitech Management System")
- DB: MySQL/MariaDB. **Locally**: database `quivi` via the `lokaldb` Docker container, `root`/`nopassword2026!` — see [[Dev-Setup]]. `.env.example`'s `tarekuld_lvpos` credentials are a production/cPanel-style placeholder, not usable as-is.
- Auth: session login (`web.php`) + JWT (`tymon/jwt-auth`) for `/api/auth/*`
- Current app version: `0.0.4`+ (see `SOFTWAREVERSION` in `.env`)
- Deploys as a Docker image to Kubernetes (see [[Deployment]]); locally, run via the same `Dockerfile` — see [[Dev-Setup]]

## Maintenance
Refreshed 2026-07-10 (previous snapshot: 2026-07-02). Re-run a study pass when the schema or module list drifts — check git log and `app/Models` / `resources/js/components` diffs first. Also see the [[../../CLAUDE.md|project CLAUDE.md]] "Standing Orders" section for the update-as-you-go policy.
