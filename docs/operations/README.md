# Operations

**Status:** Current
**Scope:** Running, deploying, monitoring and recovering the Navdhya platform.

Start with the task, not the tool:

| I need to… | Go to |
| --- | --- |
| Deploy a frontend or backend release | [`DEPLOYMENT.md`](DEPLOYMENT.md) |
| Know which environment/domain I am touching | [`ENVIRONMENTS.md`](ENVIRONMENTS.md) |
| Fix a Vercel build or configure a project | [`VERCEL.md`](VERCEL.md) |
| Deploy or operate Laravel | [`BACKEND.md`](BACKEND.md) → [`platform-navdhya/docs/22-runbooks/deploy.md`](../../platform-navdhya/docs/22-runbooks/deploy.md) |
| Add, rotate or revoke a secret | [`SECRETS.md`](SECRETS.md) |
| Respond to an alert or check health | [`MONITORING.md`](MONITORING.md) |
| Roll back a release | [`ROLLBACK.md`](ROLLBACK.md) |
| Run the optional local Postgres/Redis stack | [`local-platform.md`](local-platform.md) |
| Flip customer/partner traffic | [`cutover-runbook.md`](cutover-runbook.md) — **Target**, flag-gated |
| Rebuild search | [`search/index-rebuild.md`](search/index-rebuild.md) |
| Review the admin surface | [`admin-operations.md`](admin-operations.md) |
| Understand the CI/CD target | [`../CI-CD-PIPELINE-PLAN.md`](../CI-CD-PIPELINE-PLAN.md) |
| DevOps practices and tooling | [`DEVOPS.md`](DEVOPS.md) |
| Backend security findings and the auth-speed plan | [`BACKEND-RCA.md`](BACKEND-RCA.md) |

---

## On-call first five minutes

1. **Confirm scope.** Is the symptom on `www`, `app`, `partner`, the dashboard, or the API? Check the environment before changing anything.
2. **Check health.**
   - Web BFF: `GET /api/healthz` (liveness) and `GET /api/readyz` (BFF readiness; checks backend liveness via `/up`). For dependency readiness, use the backend `GET /ready`.
   - Customer: `GET /api/health` and `GET /api/version`.
   - Partner: `GET /api/health` and `GET /api/version`.
   - Backend: `GET /up`, `GET /ready`, `GET /.well-known/jwks.json`.
3. **Check the most recent change.** Compare the current deployment with the last known-good release/tag. Prefer rollback over an improvised forward fix.
4. **Stop the bleeding.** Roll back the frontend, disable the relevant flag, or scale the affected backend process. Do not run `migrate:rollback` in staging or production.
5. **Record and escalate.** Open an incident record, capture timestamps and request IDs, and follow [`ROLLBACK.md`](ROLLBACK.md) and the backend incident runbook ([`platform-navdhya/docs/22-runbooks/incident-response.md`](../../platform-navdhya/docs/22-runbooks/incident-response.md)).

---

## Operating rules

- **Additive migrations only** in staging/production. A migration that drops or renames is rejected at review; rollback is code-only.
- **No production data in preview or staging.** Mock mode is the default; wired mode uses a dedicated test tenant.
- **No secrets in `NEXT_PUBLIC_*`.** Public variables are baked into the browser bundle.
- **Deploy behind approval.** Release deploys use GitHub Environments; `DEPLOY_ENABLED` must stay false until the deploy scripts and hosts are ready.
- **One canonical procedure per task.** Link to it; do not fork it in another runbook.
- **Record the evidence.** Update `docs/architecture/cutover-log.md` for traffic flips and the incident record for rollbacks.

---

## Current operational gaps

These are tracked in [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md):

- `scripts/deploy/staging.sh` and `scripts/deploy/production.sh` do not exist; the release deploy job fails preflight when enabled.
- Vercel preview checks for `navdhya` and `navdhya-0` are currently red; build logs need a Vercel login or token.
- Backend CI is specified but dormant because its workflow is outside the root `.github/workflows/` directory.
- `e2e/run.sh` is missing, so the Linux nightly e2e job cannot run.
- Alert routing and on-call ownership are not yet recorded as a live system.
