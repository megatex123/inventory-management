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
- [[Deployment]] — Docker + Kubernetes deployment setup
- [[Work-In-Progress]] — uncommitted / in-flight work (raw inventory feature)

## Quick facts
- Laravel 7 + Vue 2 SPA ("Quivitech Management System")
- DB: MySQL, database `tarekuld_lvpos`
- Auth: session login (`web.php`) + JWT (`tymon/jwt-auth`) for `/api/auth/*`
- Current app version: `0.0.4` (see `SOFTWAREVERSION` in `.env`)
- Deploys as a Docker image to Kubernetes (see `deployment/`)

## Maintenance
This vault is generated from a snapshot of the repo (2026-07-02). Re-run a study pass when the schema or module list drifts — check git log and `app/Models` / `resources/js/components` diffs first.
