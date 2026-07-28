# Changelog

All notable changes to Navdhya are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **PR 0.8** New `lib/security/rate-limit.ts` with
  `InProcessRateLimiter` (sliding fixed-window, per-call cost,
  max-keys GC, `toHeaders()` helper) and `clientIp()` helper.
  Consumed by `/api/revalidate` and ready for use by SMS, OTP,
  and auth routes in PR 1.
- **PR 0.8** New `lib/auth/cookie-names.ts` as the single source
  of truth for auth cookie names (`DEV_ROLE_COOKIE`, `SESSION_COOKIE`,
  `CONSENT_COOKIE`, `ANALYTICS_OPT_IN_COOKIE`,
  `EXPERIMENT_COHORT_COOKIE`, `LAYOUT_SEED_COOKIE`) plus
  `VALID_DEV_ROLES` and `isValidDevRole` type guard. The dev-role
  cookie string was previously duplicated in 3 files; changing it
  in one place silently broke the others. All 3 callers updated.
- **PR 0.8** `OtpInput` no longer double-fires `onComplete` on
  every keystroke. The original `useEffect` had `onComplete` in its
  dep array AND ran on every value change, so a single OTP entry
  produced multiple submissions (and every parent re-render with a
  new callback identity re-triggered it). New version uses a ref to
  track the last value for which we already called `onComplete`;
  fires exactly once per distinct value. Closes F-FE-09 sibling
  P1 (auth UI double-submit risk).
- **PR 0.8** `AuthSplitLayout` body scroll lock is now
  reference-counted. The old in-place mutation leaked when two
  layouts mounted concurrently: the inner one's unmount restored
  the wrong value, releasing the outer one's lock and leaving the
  page scrollable while a layout was still open. New code lives in
  `lib/security/scroll-lock.ts` and is safe to call from any number
  of overlapping layouts/modals.
- **PR 0.8** `getMockSession` verification status is now
  role-realistic. Old code marked `Pandit` as `"verified"` and
  everything else as `"pending"` (inverse of reality). New code:
  `User` / `Manager` / `Admin` / `SuperAdmin` = `"verified"`,
  `Vendor` = `"pending"` (business-doc review), `Pandit` =
  `"pending"` for new mocks and `"verified"` for the three
  pre-approved pandits (`mock-pandit`, `mock-pandit-1`,
  `mock-pandit-2`).
- **PR 0.8** `app/find-pandit/client.tsx` and `app/pandits/client.tsx`
  no longer generate their JSON-LD schemas in client code. The
  duplicate `seoManager.generateSchema` + `dangerouslySetInnerHTML`
  injection is gone from both client files; the schema is now
  rendered once by the server-side `page.tsx` and ships in the
  initial HTML. Removes wasted client JS, eliminates a
  `dangerouslySetInnerHTML` surface, and lets Next.js static-
  optimise the schema. Closes P1 audit finding.
- **PR 0.8** `lib/dlo-x/cache.ts` is now a bounded LRU
  (max 10,000 entries) with proper TTL. Old code was an unbounded
  `Map<string, { value, expiry }>` — at 5K RPS with 1KB entries
  per value, memory grew at ~5MB/sec (~18GB/hour). Also reachable
  by an attacker sending random `?bust=…` keys. LRU evicts the
  least-recently-used entry on every insert. Closes P0 memory leak.
- **PR 0.8** `lib/dlo-x/layout-resolver.ts` `matchesRoutePattern`
  now requires a `/` boundary after a wildcard prefix. Old
  `startsWith(prefix)` matched `/pandits` against `/pandit/*` (the
  public directory was being resolved to the dashboard policy).
  Now requires either exact prefix match or `prefix/...`.
- **PR 0.8** `lib/dlo-x/layout-policies.ts` now registers an
  explicit `find-pandit-page` policy (was falling through to the
  `*` wildcard). Closes consistency gap with `/pandits` and
  `/pujas/*` which were already explicit.
- **PR 0.8** New `lib/security/csrf.ts` with double-submit-cookie
  CSRF protection. `issueCsrfToken()` returns a HMAC-signed,
  time-bounded token; `validateCsrfToken(cookie, header)` checks
  match + signature + expiry; `extractCsrfToken(request)` pulls
  the values from a Next.js request. Fails closed in production
  if `CSRF_SECRET` is unset (throws on first call). Closes
  C-RISK-01 (no CSRF on state-changing endpoints).
- **PR 0.8** `app/api/revalidate/route.ts` and
  `app/api/ads/decision/route.ts` now require a valid CSRF token
  on every state-changing request. `ads/decision` is read-only
  but writes audit logs, so we treat it as state-changing. The
  check is gated on `process.env.NODE_ENV === 'production' || CSRF_SECRET`
  so dev tooling (Vercel preview, `curl`, etc.) still works
  without a secret. `revalidate` uses `extractCsrfToken` against
  `NextRequest.cookies`; `ads/decision` parses the `Cookie` header
  directly to avoid loading `next/server` (which transitively
  imports a fetch global that breaks in some Jest environments).
- **PR 0.8** `lib/dlo-x/cache.ts` is now a bounded LRU
  (max 10,000 entries) with proper TTL. Old code was an unbounded
  `Map<string, { value, expiry }>` — at 5K RPS with 1KB entries
  per value, memory grew at ~5MB/sec (~18GB/hour). Also reachable
  by an attacker sending random `?bust=…` keys. LRU evicts the
  least-recently-used entry on every insert. Closes P0 memory leak.
- **PR 0.8** `lib/dlo-x/layout-resolver.ts` `matchesRoutePattern`
  now requires a `/` boundary after a wildcard prefix. Old
  `startsWith(prefix)` matched `/pandits` against `/pandit/*` (the
  public directory was being resolved to the dashboard policy).
  Now requires either exact prefix match or `prefix/...`.
- **PR 0.8** `lib/dlo-x/layout-policies.ts` now registers an
  explicit `find-pandit-page` policy (was falling through to the
  `*` wildcard). Closes consistency gap with `/pandits` and
  `/pujas/*` which were already explicit.
- **PR 0.8** New `lib/security/sanitize.ts` allow-list sanitizer
  (`sanitizePlainText`, `sanitizeMarkdown`, `sanitizeUrl`) with
  character-allow-list, control-char / zero-width stripping, scheme
  allow-list for URLs, and rejection of `javascript:` / `vbscript:` /
  `data:` (except `data:image/*` when explicitly allowed). 24/24
  sanitizer tests pass. Closes P-ARCH-05 (P0).
- **PR 0.8** `removePII` is now wired into `lib/dlo-x/telemetry.ts`
  `TelemetryService.track()`, redacting both field-names and value
  patterns (email, phone, Aadhaar, PAN, card, secrets) recursively.
  Returns a `RedactionLog` so callers can audit what was stripped.
  Closes P-ARCH-12 (P1).
- **PR 0.8** 177 new tests across `lib/security/__tests__/security.test.ts`
  (100: 10 RBAC + 7 tenant + 8 PII + 8 plain + 6 markdown + 12 URL + 9 rate-limit
  + 4 clientIp + 11 ab-testing + 5 cookie-names + 7 scroll-lock + 13 CSRF) +
  `lib/dlo-x/__tests__/cache.test.ts` (15) +
  `lib/dlo-x/__tests__/layout-policies.test.ts` (9) +
  `lib/pandits/__tests__/data.test.ts` (12) +
  `components/dashboard/__tests__/StatusBadge.test.tsx` (19) +
  `components/auth/__tests__/OtpInput.test.tsx` (7) +
  `app/api/ads/__tests__/csrf.test.ts` (4 new CSRF integration tests
  on the wired route). The previously broken
  `app/api/ads/__tests__/routes.test.ts` was fixed by switching
  it to `@jest-environment node` (jsdom does not expose the Web
  Fetch API globals that `next/server` references at module load).
  Full suite: lib/{dlo-x,security,pandits} + components/{dashboard,auth}
  + app/api/{ads,revalidate} green.
- **PR 0.55** i18n scaffold: `i18n.ts` config, `lib/i18n/locale.ts` (path
  resolution, prefix helpers), `lib/i18n/messages.ts` (server-side JSON
  loader with cache), `lib/i18n/formatters.ts` (Intl-based, INR default),
  `lib/i18n/translate.ts` (DeepL + Claude clients, no-op passthrough when
  keys are absent), `messages/en.json` + `messages/hi.json` (62 keys in
  sync), `scripts/translate.ts` (CLI), `scripts/check-bundle-parity.ts`
  (CI gate), `docs/i18n.md`. 42/42 i18n tests pass.

### Security
- **PR 0.8** Replaced DJB2-like `deterministicHash` in
  `lib/dlo-x/ab-testing.ts` with SHA-256 + rejection-sampled
  modulo. Old code had visible modulo bias at our traffic levels
  and let neighbouring user IDs collide into the same A/B bucket.
  New code distributes 5,000 inputs uniformly (max bucket
  < 3× expected, no empty buckets). Added `pickVariant` helper
  with optional integer weights. Closes P-ARCH follow-up.
- **PR 0.8** `/api/revalidate` now charges rate-limit tokens by
  slug count (was 1 token per request). A single request with
  100K slugs previously bypassed the 30 req/min limit and could
  DoS the registry. Hard caps: 100 slugs per request, 200 chars
  per slug, regex `/^[a-z0-9](?:[a-z0-9-]{0,198}[a-z0-9])?$/i`,
  no leading slashes / backslashes / `..` / `//` / `\\` / control
  chars. Invalid slugs get a 200 with status: 'invalid'.
- **PR 0.8** `/api/ads/decision` now validates the body with
  zod (`DecisionRequestEnvelope`): caps `opportunities` to 20,
  enforces `requestId` + `opportunities[].slotId` shape, rejects
  bodies > 64 KB with 413, returns the first 5 zod issues on 400.
  No more `payload as DecisionRequest` unsafe cast.
- **PR 0.8** `hasPermission` no longer lets an unknown role claim
  SuperAdmin access. Old code defaulted both unknowns to `0`, so
  `0 >= 0` was `true` — any caller could ask for `'SuperAdmin'`
  permissions and get them. New code requires the typed `Role` and
  returns `false` for any unknown value. SuperAdmin is now in the
  hierarchy. Audit P-ARCH-26 follow-up.
- **PR 0.8** `validateTenant` no longer treats the `'default'` tenant
  as a global read-all key. Old code returned `true` whenever
  `userTenant === 'default'` regardless of `resourceTenant`, so a
  default-tenant user could read every other tenant's data. New code
  does a strict equality check, plus an explicit `GLOBAL_TENANTS`
  allow-list (`global` / `public` / `system`) for resources that are
  intentionally shared. Audit P-ARCH-04 (P0).
- **PR 0.25** Removed dead `sanitizeInput` and `sanitizeHeaders` from
  `lib/dlo-x/security.ts` — they were unused and over-escaped `/` which
  would have corrupted JSON-LD. Audit P-ARCH-17.
- **PR 0.25** Replaced non-deterministic per-request `crypto.randomUUID()`
  seed in `proxy.ts` with sticky `deriveStableSeed()` (cookie > daily IP+UA
  hash). Fixes A/B test bucketing and layout-cache hit-rate.
  Audit P-ARCH-03 / P-ARCH-11 (P1).
- **PR 0.25** Added `.gitleaks.toml` and `.githooks/pre-commit` for secret
  scanning (Razorpay keys, AWS keys, Indian PAN/Aadhaar, UIDP JWTs).
  Mocks + audit docs whitelisted.
- **PR 0.25** Removed `@vercel/analytics` (replaced with PostHog swap stub)
  and `next-themes` / `sonner` (unused). Audit F-FE-19, C-COST-01.

### Changed
- **PR 0.25** Removed 20 unused `@radix-ui/*` packages (kept 7 in active
  use). Audit P-ARCH-21 / C-COST-01.
- **PR 0.25** Removed auto-running `postinstall` hook (was re-running
  `scripts/postbuild.mjs` on every `npm install`). Build pipeline still
  calls it after `next build`. Audit C-RISK-05.
- **PR 0.25** `next.config.ts` CSP updated: removed Vercel telemetry URLs,
  added `https://*.posthog.com` (analytics) and `https://graph.facebook.com`
  (WhatsApp Business API, PR 11.5). Audit Q3 / Q6.
- **PR 0.25** `next.config.ts` `images.remotePatterns`: activated AWS S3
  mirror (`navdhya-assets.s3.ap-south-1.amazonaws.com`) and CloudFront
  alias. Audit Q7.

### Added
- **PR 0.5** AEO Speakable + KeyFacts + TLDR + ClaimReview + AuthorEntity
  JSON-LD on all 1,265 SEO pages.
- **PR 0.5** `/llms.txt` and `/llms-full.txt` markdown exports for AI
  search engines (GPTBot, ClaudeBot, Perplexity, etc.).
- **PR 0.5** 17 AI bot user-agents in `lib/seo/robots.ts` with explicit
  allow/deny for sensitive paths.
- **PR 0.25** `lib/dlo-x/stable-seed.ts` — deterministic seed derivation
  with 8 unit tests.
- **PR 0.25** `lib/infra/dns.ts` — Route 53 record source-of-truth
  document for `navdhya.com`.
- **PR 0.25** `components/analytics/PostHogProvider.tsx` — PostHog
  swap stub. Real wiring in PR 11.5.
- **PR 0.25** `.github/CODEOWNERS` — review-routing rules.
- **PR 0.25** npm scripts: `secrets:scan`, `secrets:scan:all`,
  `secrets:baseline`, `secrets:check`.

## [0.1.0] - 2026-05-15

### Added
- Initial scaffold (Next.js 16, React 19, TypeScript 5, Tailwind 4).
- DLO-X layout orchestration (10 shells, route policies).
- SEO platform: 1,265 pages across 14 content clusters, JSON-LD
  generator, sitemap, robots.
- 6 role-based dashboards (User / Pandit / Vendor / Manager / Admin /
  SuperAdmin).
- Mock auth + DevRoleSwitcher for local development.
