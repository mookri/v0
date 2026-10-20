# Vercel

**Status:** Current (configuration and known failure state)
**Scope:** The Vercel projects used for the Next.js apps.

Two Vercel project names are visible in this repository's checks: **`navdhya`** and **`navdhya-0`**. Their preview checks are currently failing. This page explains how the monorepo should be configured, how to capture the real error, and what to check first. It does **not** claim the projects are fixed.

> Build logs require Vercel authentication. Without an account/token this repository can only see that a deployment failed, not why. Do not guess at the cause; capture the log first.

---

## 1. Project model

Use **one Vercel project per app**, not one project for the whole monorepo.

| App | Package | Root Directory | Typical domain |
| --- | --- | --- | --- |
| Web | `@navdhya/web` | `apps/web` | `www.navdhya.com` |
| Customer | `@navdhya/customer` | `apps/customer` | `app.navdhya.com` |
| Partner | `@navdhya/partner-app` | `apps/partner` | `partner.navdhya.com` |
| Dashboard | `@navdhya/dashboard` | `apps/dashboard` | Private/TBD |

The current names `navdhya` and `navdhya-0` do not reveal which app each points at. Verify the mapping before changing settings:

1. Open the project in Vercel.
2. Check **Settings → General → Root Directory**.
3. Check **Settings → Domains**.
4. Check the latest deployment's build log.

Do not create a root `vercel.json` that builds one app for every project. The monorepo has four different Next.js apps and four different root directories.

---

## 2. Required project settings

For each app project:

| Setting | Value |
| --- | --- |
| Framework Preset | **Next.js** |
| Root Directory | `apps/<app>` |
| Install Command | Auto-detected pnpm, or `pnpm install --frozen-lockfile` |
| Build Command | Auto (`next build`), or `pnpm --filter @navdhya/<package> run build` from the repo root |
| Output Directory | `.next` (default) |
| Node.js Version | **20.x** (matches `.nvmrc` and `package.json` engines) |
| Package Manager | pnpm **9.15.4** (from `packageManager`) |
| Include source files outside Root Directory | Enabled for the monorepo |

Notes:

- The repository uses a pnpm workspace. Vercel must install from the workspace root so `@navdhya/*` packages resolve. The Root Directory should be the app, not the repository root.
- Do not set an app-local lockfile. `pnpm-lock.yaml` lives at the repository root.
- `NEXT_STANDALONE` is a deploy-only switch. Vercel's Next.js builder does not need it; leave it unset unless a specific build requires it.
- The apps use Next.js 16. If Vercel's framework detection is stale, select **Next.js** explicitly and check that it runs `next build`.

Environment variables are project-scoped in Vercel. Use different values for Production, Preview and Development. See [`ENVIRONMENTS.md`](ENVIRONMENTS.md) and [`../development/ENVIRONMENT-VARIABLES.md`](../development/ENVIRONMENT-VARIABLES.md).

### Minimum customer-project variables

| Variable | Preview | Production |
| --- | --- | --- |
| `CUSTOMER_BACKEND` | unset (mock) | `laravel` |
| `NAVDHYA_API_BASE` | unset | `https://api.navdhya.com` (origin only) |
| `CUSTOMER_TENANT_ID` | unset | production tenant ULID |
| `SESSION_SIGNING_KEY` | set a unique preview-only key | same value as backend `IDENTITY_HMAC_SECRET` | The mock customer login path still requires this key in production mode, so do not leave it unset in previews.
| `CUSTOMER_AUTH_MODE` | unset/`direct` | `direct` unless federation is enabled |
| `SESSION_COOKIE_SECURE` | unset | `true` (TLS is terminated at the edge) |

Never put a production secret in the Preview environment. Preview deployments should stay in mock mode unless they are wired to a dedicated, disposable test backend.

### Minimum web-project variables

| Variable | Notes |
| --- | --- |
| `NEXT_PUBLIC_GA4_MEASUREMENT_ID_WEB` | Public analytics ID; can be empty |
| `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB` | Public GTM container; optional |
| `NEXT_PUBLIC_SIGNALS_ENABLED` | Keep `false` until shadow validation passes |
| `SIGNALS_HMAC_SECRET` | Server-only; never `NEXT_PUBLIC_*` |
| `SIGNALS_CONSENT_TOKEN_SECRET` | Server-only |
| `SIGNALS_INGEST_URL` | Server-only ingestion endpoint |

`NEXT_PUBLIC_*` values are embedded at build time. Changing one requires a redeploy, not just a restart.

---

## 3. Diagnose the current preview failures

### With dashboard access

1. Open the failing deployment for the project.
2. Open **Build Logs** and copy the first error, not just the last line.
3. Note the build duration:
   - **A few seconds** usually means configuration, install or root-directory failure.
   - **Several minutes** usually means the app compiled and then failed in a later phase.
4. Check **Settings → General → Root Directory** and **Node.js Version** against the table above.
5. Check the deployment's environment: an unset `SESSION_SIGNING_KEY` does not fail `next build`; it surfaces as a runtime auth failure when a request uses token authentication. Check runtime logs, not build logs.

### With a Vercel token or CLI login

```sh
npx vercel login
npx vercel link
npx vercel ls
npx vercel inspect <deployment-url> --logs
```

If the deployment is protected by Vercel Authentication, the CLI token must belong to a team/project with access. A login redirect is not a build error.

Useful local reproduction:

```sh
pnpm install --frozen-lockfile
pnpm --filter @navdhya/customer run build
```

Run with Node 20 to match Vercel.

---

## 4. Common failures

| Error or symptom | Likely cause | Fix |
| --- | --- | --- |
| `No Next.js version detected` | Root Directory is the repository root, or the app package is not detected | Set Root Directory to `apps/<app>` |
| Build fails in under 15 seconds | Install command, lockfile, root directory or Node version | Read the first log lines; verify the table above |
| `pnpm-lock.yaml` not found | Install runs in the wrong directory or the lockfile is ignored | Use the repo-root lockfile; do not add an app-local one |
| `Module not found: @navdhya/...` | Workspace install did not run at the root | Install from the workspace root; enable outside-root source files |
| `EPERM: operation not permitted, symlink` | Local Windows standalone trace, not Vercel | Use `NEXT_STANDALONE=0` locally; do not change Vercel for this |
| 404 on `/_next/static/...` after host deploy | Standalone package omitted `.next/static` and `public` | Fix the artefact packaging; see [`DEPLOYMENT.md`](DEPLOYMENT.md) |
| 401/redirect loop on customer | Signing key mismatch or cookie domain/secure settings | Align `SESSION_SIGNING_KEY`; verify `SESSION_COOKIE_SECURE` |
| Preview is protected by login | Vercel Authentication/deployment protection | Expected; use a token with access or open in the dashboard |
| Build passes, runtime 503 | Backend unreachable or missing env | Check `NAVDHYA_API_BASE`, DNS, TLS and `/up` |
| `NEXT_PUBLIC_*` change has no effect | Values are build-time | Redeploy after changing them |

---

## 5. Monorepo build commands

If the project root is the repository root (not recommended), the build command must target one app:

```sh
pnpm install --frozen-lockfile
pnpm --filter @navdhya/web run build
pnpm --filter @navdhya/customer run build
pnpm --filter @navdhya/partner-app run build
pnpm --filter @navdhya/dashboard run build
```

If the Root Directory is `apps/<app>`, the default `next build` is usually correct. Verify the install log shows the workspace root and the expected pnpm version.

Do not rely on Turbo remote cache in Vercel unless `TURBO_TOKEN`/`TURBO_TEAM` are configured; a cache miss must still build correctly.

---

## 6. Security and data rules

- Preview deployments must not connect to production MySQL, live MSG91, or live Razorpay.
- Use mock mode or a dedicated preview/test tenant.
- Keep deployment protection enabled for previews if they can reach non-public data.
- Never expose server secrets through `NEXT_PUBLIC_*`.
- Do not paste build logs containing tokens, cookies or customer data into issues; redact them.
- Rotate any secret that appears in a log or screenshot. See [`SECRETS.md`](SECRETS.md).

---

## 7. Current status and next action

| Item | Status |
| --- | --- |
| `navdhya` preview check | Failing; log not captured |
| `navdhya-0` preview check | Failing; log not captured |
| Project-to-app mapping | Unverified |
| Production custom domains | Unverified in this repository |
| Vercel CLI/token access | Not available in the current environment |

Next action: capture one failing build log and the project's Root Directory/Node settings, then fix that project before changing the second. Record the result in this page and in [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md).

---

## 8. Related

- [`DEPLOYMENT.md`](DEPLOYMENT.md) — full release path and artefact contract
- [`ENVIRONMENTS.md`](ENVIRONMENTS.md) — environment matrix
- [`../development/ENVIRONMENT-VARIABLES.md`](../development/ENVIRONMENT-VARIABLES.md) — variable reference
- [`../DOCUMENTATION-PLAN.md`](../DOCUMENTATION-PLAN.md) — known gaps and owners
