---
tags: [deployment, docker, kubernetes]
---

# Deployment

## Docker
`Dockerfile` — `php:7.4-apache` base:
- installs `libzip-dev`, `zip`, `pdo_mysql`, `zip` PHP ext
- enables `mod_rewrite`
- sets `APACHE_DOCUMENT_ROOT=/var/www/html/public` (Laravel's public dir)
- copies full repo in, runs `composer install`
- `chmod 777 -R` on the whole app dir (broad — fine for a quick container but worth tightening if this ever needs to be hardened)
- runs `php artisan storage:link` at build time

**Actual deploy mechanism, confirmed with the user 2026-07-27**: `docker-build-push.sh`, run directly on the deploy server. It sources `SOFTWAREVERSION` from that server's local (gitignored) `.env`, builds the image, tags it `:latest`/`:release`/`:${SOFTWAREVERSION}`, pushes all three to the private registry `telur.enigmacode.com.my/enigma/quivitech/inventory-management`, then runs `docker compose down` → `docker compose pull` → `docker compose up -d` on that same host to actually roll the new image out (brief downtime during the swap — not a zero-downtime deploy). A GitLab CI/CD pipeline automating this flow is designed (not yet built as of this writing) — see `docs/superpowers/specs/2026-07-27-cicd-pipeline-design.md`.

## Kubernetes (`deployment/`) — confirmed unused for the real deploy flow (2026-07-27)
- `00-namespace.yaml`, `01-deployment.yaml`, `02-service.yaml`, `kustomization.yaml` — target namespace `quivitech-im-stag`, image `telur.enigmacode.com.my/enigma/quivitech/inventory-management:release`, `imagePullSecrets: regcred` already configured cluster-side.
- `deployment/README.md` — check this first for the actual deploy procedure/cluster context before touching manifests.
- **These manifests exist in the repo but are not how deploys actually happen** (see the docker-compose flow above) — don't assume they're live/current without checking with the user first; they may be an earlier or aspirational deploy path.

## Versioning
`SOFTWAREVERSION` env var tracks app version; recent git tags/commits show `v 0.0.4` as current.

## Related
- [[Command]] — plain command list, includes the deploy script one-liner
- [[Project-Overview]]
