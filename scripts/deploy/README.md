# Deploy Script Contract

**Status:** Target — the scripts described here do not exist yet
**Scope:** `scripts/deploy/staging.sh` and `scripts/deploy/production.sh`, called by `.github/workflows/release.yml`.

The release workflow deliberately fails when a deploy script is missing. This page defines the contract those scripts must satisfy so the implementation is reviewable and the release job can be enabled safely.

---

## 1. Why this file exists

`.github/workflows/release.yml` builds four standalone artefacts, attests provenance, publishes a GitHub Release, and then calls:

```text
scripts/deploy/<env>.sh
```

The deploy job is disabled until `vars.DEPLOY_ENABLED == 'true'`. When enabled, it preflights the script's executable bit and fails with:

```text
scripts/deploy/<env>.sh is required before DEPLOY_ENABLED can be true
```

There is no Dockerfile in this repository. The deploy target is a host running the Next.js standalone server under a process manager. The backend is deployed separately; these scripts do **not** run Laravel migrations.

---

## 2. Inputs available to the script

The workflow checks out the repository at the release tag and downloads the release artefacts into `dist/`.

| Input | Source | Notes |
| --- | --- | --- |
| `TARGET_ENV` | Workflow env | `staging` or `production` |
| `RELEASE_TAG` | `github.ref_name` | e.g. `v0.1.0` |
| `dist/navdhya-<app>-<tag>.tar.gz` | Download artifact | `app` ∈ `web`, `customer`, `dashboard`, `partner` |
| `dist/sha256sums-<app>.txt` | Download artifact | Generated in `dist/` |
| Repository checkout | `actions/checkout` | Contains the deploy script and host config templates |
| GitHub Environment | `staging` / `production` | Approval rules and secrets |

The deploy job currently exports **only** `TARGET_ENV` and `RELEASE_TAG`. It does not export SSH credentials. The implementation must either:

- add `STAGING_SSH_KEY` / `PRODUCTION_SSH_KEY` (and host/user/path variables) to the job env, or
- establish a short-lived SSH agent before calling the script.

Do not commit private keys or host passwords to make this work.

---

## 3. Required behavior

### Preflight

- [ ] Refuse to run if `TARGET_ENV` is not `staging` or `production`.
- [ ] Refuse to run if `RELEASE_TAG` is empty or does not start with `v`.
- [ ] Require all four artefacts and their checksum files.
- [ ] Verify checksums before unpacking:
  ```sh
  cd dist
  sha256sum -c sha256sums-web.txt
  # ... repeat for customer, dashboard, partner
  ```
- [ ] Verify the archive contains a runnable standalone server and the expected build ID.
- [ ] **Fail if static assets are missing.** Next.js standalone output needs `.next/static` and `public`. The current release tarball includes only `standalone/`; either amend `release.yml` to package the missing assets or make this a hard preflight failure. Do not deploy an app that will serve 404s for JS/CSS.
- [ ] Confirm the target host is the one for `TARGET_ENV`; never infer production from a branch.

### Deploy

- [ ] Use a versioned release directory (for example `/srv/navdhya/<app>/releases/<tag>`), not an in-place overwrite.
- [ ] Unpack atomically and preserve the previous release.
- [ ] Put runtime secrets in the host environment/secret manager, not in the release directory.
- [ ] Switch the `current` symlink (or the process manager's release path) only after unpacking succeeds.
- [ ] Restart the app process gracefully; wait for the old process to exit.
- [ ] Do not restart the Laravel queue worker from this script; backend deploys are separate.
- [ ] Do not run database migrations, `migrate:fresh`, or any data mutation.

### Verify

- [ ] `GET /api/healthz` on web → 200.
- [ ] `GET /api/readyz` on web → 200 (or the expected mock/back-end state).
- [ ] `GET /api/health` on customer and partner → 200.
- [ ] The home page returns 200 and a known static asset returns 200.
- [ ] The previous release remains available for rollback.
- [ ] On any failed check, restore the previous release and fail the job.

### Record

- [ ] Print the tag, commit SHA, app names, target host, checksums and timestamps to the job summary.
- [ ] Write a deployment record outside the release directory (or emit it as a workflow artifact).
- [ ] Never print secret values.

---

## 4. Recommended environment contract

The exact host layout is not yet chosen. The implementation should use explicit, documented names rather than hard-coded paths.

| Variable | Scope | Purpose |
| --- | --- | --- |
| `TARGET_ENV` | Workflow | `staging` / `production` |
| `RELEASE_TAG` | Workflow | Release tag |
| `DEPLOY_HOST` | GitHub Environment variable | Target host |
| `DEPLOY_USER` | GitHub Environment variable | SSH user |
| `DEPLOY_PATH` | GitHub Environment variable | Base release path |
| `STAGING_SSH_KEY` | GitHub Environment secret | Staging private key |
| `PRODUCTION_SSH_KEY` | GitHub Environment secret | Production private key |
| `APP_ORIGIN_WEB`, `APP_ORIGIN_CUSTOMER`, `APP_ORIGIN_PARTNER`, `APP_ORIGIN_DASHBOARD` | GitHub Environment variable | Smoke URLs |

These names are a proposal. If the host/process manager uses different concepts, document the actual names here before implementation.

---

## 5. Process manager examples

Use what the host actually runs. Two common shapes:

### systemd

```ini
[Service]
WorkingDirectory=/srv/navdhya/<app>/current
ExecStart=/usr/bin/node server.js
EnvironmentFile=/etc/navdhya/<app>.env
Restart=always
User=navdhya
```

### Supervisor

```ini
[program:navdhya-<app>]
command=node server.js
directory=/srv/navdhya/<app>/current
autostart=true
autorestart=true
user=navdhya
```

The standalone server reads `PORT`/`HOSTNAME`; set them explicitly in the unit rather than relying on defaults.

---

## 6. Rollback contract

Every deploy must leave enough state for [`docs/operations/ROLLBACK.md`](../../docs/operations/ROLLBACK.md):

- The previous release directory is intact.
- The previous symlink target is recorded.
- The previous process state is known.
- The script exits non-zero on failure, after attempting to restore the previous release.

A deploy script that deletes the previous release before verification is not acceptable.

---

## 7. Release workflow caveat

The `deploy` job uses a matrix:

```yaml
strategy:
  matrix:
    environment: [staging, production]
```

Both environments run as parallel jobs. A failed staging deploy does **not** automatically block production because `fail-fast: false`. If the promotion policy requires "staging green before production", split the deploy into sequential jobs or add an explicit dependency/approval. Record the chosen policy in [`docs/operations/DEPLOYMENT.md`](../../docs/operations/DEPLOYMENT.md).

---

## 8. Minimum skeleton

This is an outline, not an implementation:

```bash
#!/usr/bin/env bash
set -euo pipefail

: "${TARGET_ENV:?}"
: "${RELEASE_TAG:?}"

# 1. Validate target and tag.
# 2. Verify dist/*.tar.gz checksums.
# 3. Unpack to a new release directory.
# 4. Verify server.js, BUILD_ID, .next/static and public.
# 5. Switch the current symlink.
# 6. Restart the process manager.
# 7. Smoke the health endpoints.
# 8. On failure, restore the previous release and exit non-zero.
```

The script must be executable in Git:

```sh
chmod +x scripts/deploy/staging.sh scripts/deploy/production.sh
git update-index --chmod=+x scripts/deploy/staging.sh scripts/deploy/production.sh
```

Skipping the executable bit makes the release preflight fail even if the script exists.

---

## 9. Definition of done

- [ ] Both scripts exist and are executable.
- [ ] `release.yml` exports the credentials the scripts need.
- [ ] Static assets are packaged or the script fails before switching traffic.
- [ ] A staging deploy is rehearsed and recorded.
- [ ] A production deploy is rehearsed with a rollback.
- [ ] `DEPLOY_ENABLED` is only set after the above.
- [ ] The deployment record includes tag, commit, checksums and smoke evidence.
