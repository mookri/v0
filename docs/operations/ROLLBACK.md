# Rollback

**Status:** Current for code/flag rollback; database restore is **Target** until a drill is recorded
**Scope:** Frontend, backend, feature flags and first incident response.

**Default rule:** prefer a known-good rollback over an improvised forward fix. A rollback is a release, not an exception — record it like one.

---

## 1. First 10 minutes

1. **Stop changing state.** No new deploys, migrations or manual data edits while the failure mode is unknown.
2. **Identify the blast radius.** Which surface(s), environment, tenant(s) and time window?
3. **Pick the smallest reversible action:**
   - Bad frontend release → promote the previous deployment.
   - Bad backend release → restore the previous code tag.
   - Bad traffic flip → turn the flag off.
   - Bad data migration → stop writes if necessary and follow the database recovery path.
4. **Open an incident record** with start time, severity, owner and current action.
5. **Communicate** the impact and next update time.
6. **Verify** the rollback with the health checks and a critical journey.
7. **Preserve evidence** before cleanup.

Severity and notification rules: [`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md).

---

## 2. Frontend rollback

### Vercel

1. Open the project in Vercel.
2. Go to **Deployments** and find the last known-good production deployment.
3. Use **Promote to Production** (or reassign the production alias to that deployment).
4. Confirm the production domain points at the restored deployment.
5. Run the smoke checks from [`MONITORING.md`](MONITORING.md).

Do not rebuild the old commit if a known-good deployment already exists; promotion is faster and preserves the exact artefact.

### Host / release artefact

If the host deployment uses versioned release directories and a current symlink:

```sh
# illustrative layout — confirm the host's actual paths first
readlink -f /srv/navdhya/current
ln -sfn /srv/navdhya/releases/<previous-release> /srv/navdhya/current
# restart the process manager for the app
```

Then verify the app process, static assets (`.next/static`, `public`) and `/api/healthz`/`/api/readyz`.

The host deploy path is **Target**: `scripts/deploy/*.sh` do not exist yet, and the release tarball currently omits static assets. Do not treat the illustrative commands above as a live procedure until the host layout and scripts are implemented.

### Vercel vs host

Use the platform that actually served the failing traffic. A Vercel rollback does nothing for a host-served app, and vice versa.

---

## 3. Backend rollback

Backend releases are additive and backward-compatible by policy, so rollback is **code-only**:

```sh
cd /path/to/platform-navdhya
# The backend repository may have no configured remote (GIT-001). Ensure the
# previous release tag is available locally (via the release artifact or a
# configured remote) before attempting checkout.
git fetch --tags   # only if a remote is configured
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
# restart PHP-FPM and the queue worker
```

Then:

- `GET /up` → 200.
- `GET /ready` → 200.
- `GET /.well-known/jwks.json` → 200.
- A read-only API call and one authenticated flow.

### Do not run down-migrations

`php artisan migrate:rollback` is forbidden in staging and production. Down-migrations exist for development and tests. If a schema change is not backward-compatible, the release process failed; restore from the pre-deploy backup and fix forward with a new additive migration.

### Queue worker

Restart the worker after rollback. In-flight jobs may have been written by the newer code; make job payloads backward-compatible or drain/park the queue before rolling back if the payload shape changed.

### Passport keys

Do not delete keys during a code rollback. If keys were rotated, keep the old key in JWKS until issued tokens expire. Restore key files only from the controlled backup.

---

## 4. Feature-flag rollback

The customer/partner strangler is flag-gated. Turning the flag off returns traffic to the web origin without removing the deployed app.

| Flag | Effect |
| --- | --- |
| `STRANGLER_CUSTOMER_BOOK=1` | `/book/**` redirects to `app.navdhya.com` |
| `STRANGLER_CUSTOMER_USER=1` | User/account routes redirect |
| `STRANGLER_CUSTOMER_SETTINGS=1` | Settings routes redirect |
| `STRANGLER_CUSTOMER_LIVE=1` | Sets all three at once; stepwise flags take precedence per class |

Rollback:

1. Set the relevant flag to `0`. If `STRANGLER_CUSTOMER_LIVE` was used, remove it **and** set every per-class override (`STRANGLER_CUSTOMER_BOOK`, `STRANGLER_CUSTOMER_USER`, `STRANGLER_CUSTOMER_SETTINGS`) to `0` explicitly; removing the umbrella flag alone leaves overrides active.
2. Redeploy web, or flip the environment if the platform supports runtime config.
3. Verify the web route returns 200 without a redirect.
4. Record the flip in [`../architecture/cutover-log.md`](../architecture/cutover-log.md) — timestamp, direction, actor, probes.

Web continues to serve every route during the strangler, so flag-off is the lowest-risk rollback.

---

## 5. Data and database recovery

Use this only when data is corrupt, a destructive migration ran, or the primary is lost. It is not the first response to a bad code release.

1. **Stop writes** if corruption is ongoing (maintenance mode or remove the app from the load balancer).
2. **Identify the recovery point.** Use the last known-good backup/PITR timestamp before the damaging change.
3. **Restore to a separate instance first** when possible; validate row counts, audit-chain continuity and tenant isolation.
4. **Promote/switch** the restored instance; pin connections.
5. **Replay** any safe writes that occurred after the recovery point only if the business owner approves; otherwise communicate the data-loss window.
6. **Run the tenant smoke suite** before reopening traffic.
7. **Record RPO/RTO actuals** and schedule a drill if the target was missed.

Backend targets (v1): RPO ≤ 5 minutes, RTO ≤ 1 hour. The HA/DR design calls for MySQL binlog + nightly snapshots and quarterly restore drills. These are **targets**; a dated drill is still required to prove them.

See [`platform-navdhya/docs/20-sre/ha-dr.md`](../../platform-navdhya/docs/20-sre/ha-dr.md).

---

## 6. Rollback verification

After any rollback:

- [ ] The affected surface returns 200 and the expected build/version.
- [ ] `/api/healthz` and `/api/readyz` are healthy (web).
- [ ] Customer/partner `/api/health` is healthy.
- [ ] Backend `/up` and `/ready` are healthy.
- [ ] Login/session works for a test account.
- [ ] The critical booking/payment read path works (test data).
- [ ] Queue is draining; no new dead letters.
- [ ] Scheduler last run is recent.
- [ ] Error rate and latency are back to baseline.
- [ ] The incident record contains the action, timestamp and verification evidence.

---

## 7. After the rollback

- Keep the failed release tagged/known; do not delete the evidence.
- Add a regression test or a guard for the failure mode.
- Fix forward in a new release; do not silently re-cut the failed tag.
- Update the runbook if a step was missing or ambiguous.
- Complete a postmortem for P1 within five business days.

---

## 8. Related

- [`DEPLOYMENT.md`](DEPLOYMENT.md) — deploy path and checklist
- [`MONITORING.md`](MONITORING.md) — health checks and alert classes
- [`cutover-runbook.md`](cutover-runbook.md) — flag-gated traffic flips
- [`../architecture/cutover-log.md`](../architecture/cutover-log.md) — append-only flip record
- [`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md) — severity and incident ladder
- [`platform-navdhya/docs/20-sre/ha-dr.md`](../../platform-navdhya/docs/20-sre/ha-dr.md) — RPO/RTO and restore drills
