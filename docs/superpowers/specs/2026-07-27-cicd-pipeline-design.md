# GitLab CI/CD Pipeline — Design

## Context

Deploys are currently fully manual: someone runs `./docker-build-push.sh` on the deploy server itself, which sources `SOFTWAREVERSION` from the (gitignored, not committed) `.env` file, builds the Docker image, tags it `:latest`/`:release`/`:${SOFTWAREVERSION}`, pushes all tags to the private registry `telur.enigmacode.com.my/enigma/quivitech/inventory-management`, then does `docker compose down` → `docker compose pull` → `docker compose up -d` on that same host to actually roll out the new image.

This design automates that flow via GitLab CI (the project's remote is a self-hosted GitLab instance), without changing the deploy *mechanism* — the target is still `docker compose` on the same server, not the `deployment/` Kubernetes manifests, which were confirmed to be a separate, currently-unused deploy path and are out of scope here.

## Confirmed with user

- **Trigger branch: `quivitech`** — this project's actual deployable branch (not `main`, which was an incorrect initial assumption based on generic git convention).
- **Deploy mechanism: docker-compose**, matching `docker-build-push.sh` exactly — not the K8s `deployment/` manifests (confirmed unused for the real deploy flow).
- **GitLab Runner is registered directly on the deploy server** — jobs run there natively (shell access to that host's Docker daemon and `docker-compose.yaml`), no Docker-in-Docker, no SSH step needed.
- **Deploy stage is a manual GitLab CI job** (build+push run automatically on every push to `quivitech`; the deploy job appears in the pipeline but sits paused until a human clicks "play") — there's no automated test suite in this codebase to gate an auto-deploy on, so a human stays in the loop for the step that actually touches the running service.
- **Explicit `docker login` via CI/CD variables**, not relying on pre-existing host-level Docker credential state.
- **Versioning**: image tags are `:latest`, `:release`, and `:$CI_COMMIT_SHORT_SHA` — replacing the script's manually-bumped `.env` `SOFTWAREVERSION` (fragile: gitignored, easy to forget to bump, not visible in CI) with something automatic, unique per commit, and always in sync with what's actually being built.

## Pipeline

Two stages, both restricted to `quivitech` via `rules`:

### Stage 1: `build-push` (automatic)

```yaml
build-push:
  stage: build-push
  tags: [deploy-server]
  rules:
    - if: '$CI_COMMIT_BRANCH == "quivitech"'
  script:
    - echo "$REGISTRY_PASSWORD" | docker login telur.enigmacode.com.my -u "$REGISTRY_USER" --password-stdin
    - docker build -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:latest
                    -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:release
                    -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:$CI_COMMIT_SHORT_SHA
                    .
    - docker push telur.enigmacode.com.my/enigma/quivitech/inventory-management:latest
    - docker push telur.enigmacode.com.my/enigma/quivitech/inventory-management:release
    - docker push telur.enigmacode.com.my/enigma/quivitech/inventory-management:$CI_COMMIT_SHORT_SHA
```

### Stage 2: `deploy` (manual)

```yaml
deploy:
  stage: deploy
  tags: [deploy-server]
  rules:
    - if: '$CI_COMMIT_BRANCH == "quivitech"'
      when: manual
  script:
    - docker compose down
    - docker compose pull
    - docker compose up -d
```

`tags: [deploy-server]` pins both jobs to the runner registered on the deploy server — **the exact tag name needs to match whatever tag that runner was actually registered with** (confirm in GitLab → Settings → CI/CD → Runners before the first real run; `deploy-server` here is a placeholder the user picks or renames).

`docker compose down/pull/up -d` matches the script's existing restart behavior exactly, including its brief-downtime characteristic (containers are stopped before the new image is pulled and started) — this design doesn't attempt a zero-downtime rollout, since that's not what the current manual process does either.

## Required GitLab CI/CD variables (user sets these up, not part of this implementation)

| Variable | Purpose |
|---|---|
| `REGISTRY_USER` | Username for `docker login telur.enigmacode.com.my` |
| `REGISTRY_PASSWORD` | Password/token for the same — mark **masked** and **protected** in GitLab's variable settings |

Both should be scoped to the `quivitech` branch (GitLab CI/CD variables support "protected branches only" — recommend enabling this alongside marking `quivitech` as a protected branch, if it isn't already, so these credentials are never exposed to a pipeline running on an arbitrary feature branch).

## Out of scope

- The Kubernetes `deployment/` manifests and any kustomize-based deploy path — confirmed unused for the real deploy flow, not touched by this design.
- Any automated test suite / test stage — none exists in this codebase (established throughout this project's history); this pipeline builds and deploys what's committed, it doesn't verify correctness beyond a successful `docker build`.
- SSH-based deployment — not needed since the runner already lives on the deploy server.
- Zero-downtime / rolling deploy strategy — matches the existing script's stop-then-start behavior, not a design goal here.
- A CI pipeline for other branches (e.g. a lint-only check on feature branches before merge) — not requested; `quivitech` is the only branch this pipeline reacts to.

## Testing

No automated test suite exists in this codebase. Verification for this pipeline itself: after `.gitlab-ci.yml` is committed and pushed to `quivitech` (with the required CI/CD variables already set up by the user), watch the pipeline run in GitLab's UI — confirm `build-push` completes and the 3 tags appear in the registry, then manually trigger `deploy` and confirm the running containers actually swap to the new image (e.g. via `docker compose ps` / checking the app responds after the restart). This can't be verified from within this development environment since it depends on GitLab's own CI infrastructure and the live deploy server — the user will need to confirm the first real run themselves.
