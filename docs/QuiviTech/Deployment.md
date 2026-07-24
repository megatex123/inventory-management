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

`docker-build-push.sh` — helper script to build and push the image (check script contents before running; it likely tags with `SOFTWAREVERSION`).

## Kubernetes (`deployment/`)
- `00-namespace.yaml`, `01-deployment.yaml`, `02-service.yaml`, `kustomization.yaml`
- `deployment/README.md` — check this first for the actual deploy procedure/cluster context before touching manifests.

## Versioning
`SOFTWAREVERSION` env var tracks app version; recent git tags/commits show `v 0.0.4` as current.

## Related
- [[Command]] — plain command list, includes the deploy script one-liner
- [[Project-Overview]]
