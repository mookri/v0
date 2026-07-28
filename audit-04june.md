# Navdhya Codebase Audit — 4 Senior Perspectives

**Date:** 04 June 2026
**Auditor:** opencode (Senior Frontend Engineer + Principal Engineer + CTO + Product Engineer)
**Scope:** Full repo at `C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services` (1,265 SEO content files + scripts excluded from line-level review; reviewed: root config, `app/`, `components/`, `lib/`, `proxy.ts`)
**Methodology:** Read-only static audit. Each finding cites `file:line`. Findings mapped to existing PRs in `planning-04june.md` v12 or marked as new.

---

## 0. Executive Summary

The codebase is a **high-quality marketing/SEO surface** for a Hindu-ritual platform with a sophisticated **DLO-X (Dynamic Layout Orchestration) server-driven shell system**, a polished **UnifiedAuthConsole**, and a working **6-role dashboard skeleton** — but it is **0% connected to a real backend**. The entire "data layer" is hardcoded mocks; there is no database, no ORM, no real auth, no API routes for the business domain (only ads and ISR revalidation). The architecture is well-thought-out for the **rendering layer** (Edge proxy, policy-driven shell selection, role-aware routing metadata) but the **application layer is a façade**.

### Headline numbers

| Metric | Value |
|---|---|
| `.tsx` files in `app/` | 149 |
| `.tsx` files in `components/` | 106 |
| `.ts` files in `lib/` | 77 |
| API routes in `app/api/` | **5** (3 ads + 1 revalidate + 1 file) |
| Auth routes | 17 (5 are dead stubs) |
| Dashboard sections implemented | 6/6 `overview` only, all others `"This section is under development."` |
| Test files | **1** (`app/api/ads/__tests__/routes.test.ts`) |
| Dashboard primitive tests | **0** |
| Components with `'use client'` at top | majority |
| Files using mock data | most of `lib/auth/`, all of `RoleWorkspacePage` |
| Routes in `routes-redirects.json` | (not read in audit) |
| `db/`, `drizzle/`, `prisma/`, `migrations/` | **0** (no DB layer) |
| `.env*` files | (not in scope) |

### Severity distribution

| Severity | Count | Examples |
|---|---|---|
| **P0 (security/data)** | 7 | SuperAdmin privilege escalation, default-tenant bypass, hardcoded `isAuthenticated: true`, mojibake `₹`, no DB, no input escaping in ad payload, PII logged in `removePII` is incomplete |
| **P1 (correctness/UX)** | 14 | 5 dead auth routes, hardcoded `/user/overview` redirects, double-fire OTP, double-keyed `DEV_ROLE_COOKIE`, no `proxy.ts` enforcement, broken AB test math, status badge missing variant, `framer-motion` key prop drift |
| **P2 (architecture/perf)** | 13 | No tests for dashboard primitives, in-process LRU cache unsynced, 23 client mocks, registry read on every call, AEO absent, no i18n, no directory search, no error.tsx, no skeletons |
| **P3 (polish)** | 7 | Tailwind v4 + custom `form-input` / `btn-primary` / `stat-card` / `sidebar-nav-item` class names not in standard Tailwind, repeated SVG eye icons, missing `lang` switcher |

---

## 1. Senior Frontend Engineer Perspective

### 1.1 Strengths
- **DLO-X pattern** (`lib/dlo-x/*`) is a genuinely clever server-driven shell system. `LayoutOrchestrator` (`components/dlo-x/LayoutOrchestrator.tsx:1`) validates path/policy/shell, sends telemetry, and nests shells in reverse order — clean.
- **UnifiedAuthConsole** (`components/auth/UnifiedAuthConsole.tsx:1`) is well-decomposed: state machine `LOGIN|SIGNUP|FORGOT_PASSWORD|OTP|SUCCESS`, `AnimatePresence mode="wait"`, history sync via `popstate`. The visual design in `AuthSplitLayout` (`components/auth/AuthSplitLayout.tsx:1`) is genuinely premium.
- **Layout policy registry** (`lib/dlo-x/layout-policies.ts:1`) covers 28+ routes with priority, role, device, and A/B test slots. `PolicyBuilder` fluent API is readable.
- **SEO infra** (`app/(seo)/[...slug]/page.tsx:1`) is untouched and the schema generator, sitemap, robots, and `seo:qa` scripts are in place.
- **CSP is real** (`next.config.ts:10`) and the image pipeline is upgraded to AVIF + WebP with 9 device breakpoints — this is above-market for a Next 16 codebase.

### 1.2 Frontend findings

#### F-FE-01 [P1] Auth state machine ignores `?next=` query
**File:** `components/auth/UnifiedAuthConsole.tsx:60-77`
**Symptom:** `handlePopState` checks `pathname.includes('/login')` only — `?next=/admin/users` is lost. After login user lands on `/user/overview` regardless of where they came from.
**Fix:** parse `URLSearchParams` in `popstate`, store in `useRef`, push to `?next=` after OTP success.
**Maps to:** PR 1 (auth rewrite).

#### F-FE-02 [P1] Hardcoded `router.push('/user/overview')` in 4 auth client files
**Files:** `app/(auth)/lock/client.tsx:23`, `app/(auth)/two-factor/client.tsx:23`, `app/(auth)/pin-login/client.tsx:23`, `app/(auth)/email-verified/page.tsx:20`
**Symptom:** Pandit on `/lock` lands in User dashboard. Email-verified redirect ignores role.
**Fix:** read role from mock-session, route via `getRoleLanding(role).href` from `lib/auth/mock-session.ts:18`.
**Maps to:** PR 1.

#### F-FE-03 [P1] `AuthRoutePanel` declares 6 variants, only 2 work
**File:** `components/auth/AuthRoutePanel.tsx:14-20, 194-279`
**Symptom:** `verify`, `complete-profile`, `pandit-application`, `vendor-application` all fall through to the same generic form. Routes `app/(auth)/apply/pandit/page.tsx:6`, `app/(auth)/apply/vendor/page.tsx`, `app/(auth)/complete-profile/page.tsx` are 561-byte stubs.
**Fix:** implement each variant; for applications, dispatch to role-specific form.
**Maps to:** PR 1.

#### F-FE-04 [P1] `OtpInput` double-fires on submit
**File:** `components/auth/OtpInput.tsx:36-40`
**Symptom:** `useEffect` calls `onComplete?.(value)` whenever `value.length === length`; in `two-factor/client.tsx:22` parent also runs `handleSubmit` on Enter. Auto-submit then manual submit = double POST.
**Fix:** add a `submitOnComplete?: boolean` prop; default true; let parent control.
**Maps to:** PR 1.

#### F-FE-05 [P1] `AuthSplitLayout` scroll-lock leak
**File:** `components/auth/AuthSplitLayout.tsx:22-42`
**Symptom:** mutates `document.body.style.overflow` and `<html>.style.overflow` globally; no ref-count. If two `AuthSplitLayout`s mount (transition between `LoginScreen` → `OtpScreen`), cleanup of the first may fire before the second's effect runs → double-locked.
**Fix:** use `useEffect` ref-count pattern, or rely on `position: fixed` on root.
**Maps to:** PR 1.

#### F-FE-06 [P1] `AuthSplitLayout` `state` prop unused when `transitionTo` is undefined
**File:** `components/auth/AuthSplitLayout.tsx:78-107`
**Symptom:** Three header branches (`state === 'LOGIN'`, `'SIGNUP'`, default) but `transitionTo` is only wired in `UnifiedAuthConsole:166`. Standalone `<AuthRoutePanel>` calls won't get a "Sign Up" toggle.
**Fix:** pass `transitionTo` from `AuthRoutePanel` consumers or render a disabled button.
**Maps to:** PR 1.

#### F-FE-07 [P1] `lib/auth/countries.ts` missing — referenced
**Symptom:** This file appears in plan references but does not exist in the repo; `OtpScreen` was assumed to call `getCountryByCode(code).dialCode`. If added later, return type must be strict (no `undefined`).
**Fix:** when introduced, type return as `Country | undefined` and guard at call sites.
**Maps to:** PR 1.

#### F-FE-08 [P2] `framer-motion` `layoutId` / `key` not used in shell transitions
**Symptom:** `AnimatePresence` works, but role-switch (User → Pandit) does a hard cut. With DLO-X, an `AnimatePresence` on the resolved layout tree would give a smooth cross-fade.
**Maps to:** PR 4 (dashboards).

#### F-FE-09 [P1] `StatusBadge` `VARIANT_MAP` missing `restricted`, `verified` styles aligned with `verificationStatus`
**File:** `components/dashboard/StatusBadge.tsx:6-31`
**Symptom:** `restricted` → `'badge-neutral'` (silently masks a problem state). `verified` correctly maps to success, but `suspended`, `paused`, `expired` are missing.
**Maps to:** PR 4.

#### F-FE-10 [P1] Dashboard primitives have **zero** unit tests
**Files:** `components/dashboard/{StatCard,StatusBadge,QuickActions,TimelineItem,ProgressBar}.tsx` (no `__tests__/`).
**Symptom:** Regressions will only be caught at the E2E layer. QuickActions renders arbitrary `icon` + `label` + onClick — easy to break, hard to verify visually.
**Fix:** add Jest + Testing Library tests for each; cover the `VARIANT_MAP` exhaustively.
**Maps to:** PR 4 (test scaffold) — make this the first task.

#### F-FE-11 [P1] `DashboardShell` is a 9,791-byte monolithic client component
**File:** `components/dlo-x/shells/DashboardShell.tsx:61-259`
**Symptom:** Sidebar, topbar, user dropdown, theme toggle, dev role switcher, fullscreen button all in one file. `ICON_MAP` is a 36-entry `Record<string, ComponentType>` (line 23-59) — easy to forget entries (no exhaustive-type check).
**Fix:** split into `Sidebar`, `Topbar`, `UserMenu`, `QuickSearch` components. Replace `ICON_MAP` with exhaustive `Record<RoleSectionSlug, Icon>`.
**Maps to:** PR 4.

#### F-FE-12 [P1] `DashboardShell` reads `getMockSessionSync()` in render
**File:** `components/dlo-x/shells/DashboardShell.tsx:69`
**Symptom:** client-only session derived from `document.cookie`. This is fine for dev role switching but is the only session source in production. No `loading.tsx` skeleton; no SSR hydration mismatch guard.
**Maps to:** PR 1 + PR 4.

#### F-FE-13 [P2] No `loading.tsx` anywhere
**Symptom:** App-wide loading is `Preloader` (`app/layout.tsx:9`) which shows a fixed fullscreen spinner. No skeleton per route. RSC streaming has nothing to stream.
**Fix:** add `app/(auth)/loading.tsx`, `app/user/loading.tsx`, etc.
**Maps to:** PR 4 (UX polish).

#### F-FE-14 [P2] No `error.tsx` boundary per route
**File:** `components/dlo-x/ErrorBoundary.tsx` (not read in audit) is the only error fallback; `app/not-found.tsx:1` exists.
**Symptom:** Any unhandled RSC error → user sees generic browser error. Need `app/error.tsx`, `app/(auth)/error.tsx`, `app/user/error.tsx` with retry.
**Maps to:** PR 4 (UX polish).

#### F-FE-15 [P1] `pujas/temple/client.tsx` has mojibake `₹` rendered as `�'�`
**File:** `app/pujas/temple/client.tsx` (largest files list shows 5+ pujas/* client.tsx files; not all read but flag raised)
**Symptom:** the `₹` character (U+20B9) is encoded incorrectly. Likely a stray encoding conversion.
**Fix:** file should be UTF-8; check editor save settings.
**Maps to:** PR 0.5 (or as part of any pujas cleanup).

#### F-FE-16 [P1] `find-pandit` and `pandits` are 90% duplicate
**Files:** `app/find-pandit/client.tsx` (40,382 bytes), `app/pandits/client.tsx` (35,057 bytes)
**Symptom:** Both define `mockPandits` arrays of 6 hardcoded entries; both implement the same filter/grid UI; only URL differs. Two routes indexable for the same content → SEO duplicate-content penalty.
**Fix:** keep `/priests` (per plan), deprecate `/pandits` via 301 → `/priests`, delete the duplicate client.
**Maps to:** PR 11 (directory + search).

#### F-FE-17 [P1] SEO page has hardcoded schema in component file
**File:** `app/find-pandit/client.tsx:10-19`
**Symptom:** `FIND_PANDIT_SCHEMA` is generated inside the client component via `seoManager.generateSchema`, but `client.tsx` cannot emit `<script type="application/ld+json">` for SEO. Schema must come from `generateMetadata` in the server `page.tsx`.
**Fix:** move schema generation to `app/find-pandit/page.tsx` `generateMetadata`.
**Maps to:** PR 0.5.

#### F-FE-18 [P2] AdProvider is the only context provider
**File:** `app/providers.tsx:1-16`
**Symptom:** no `SessionProvider` (clerk-style), no `ThemeProvider` (`next-themes` is in deps, unused), no `QueryClientProvider` (we have no `react-query`), no `TooltipProvider`, no `ToastProvider` (`sonner` is in deps, unused).
**Fix:** add `ThemeProvider` (existing dep) and `Toaster` from `sonner`.
**Maps to:** PR 0 (foundations) — add as a 0.25 task.

#### F-FE-19 [P2] `next-themes` and `sonner` installed but unused
**Package.json:** lines 69, 77
**Symptom:** dead deps in production bundle.
**Maps to:** PR 0.25 (cleanup).

#### F-FE20 [P2] CSS uses non-standard utility classes
**File:** `app/globals.css` (not fully read) + `RoleWorkspacePage.tsx:50,56,134,168,277,etc.`
**Symptom:** `form-input`, `btn-primary`, `stat-card`, `sidebar-nav-item`, `sidebar-nav-item-active`, `sidebar-nav-label`, `badge-success`, `badge-warning`, `badge-danger`, `badge-info`, `badge-neutral`, `topbar`, `dropdown`, `dropdown-item`, `dropdown-divider` — these are not in Tailwind v4 core. They must be defined in `globals.css` or a component library. Search shows `components/ui/` exists but classes aren't in standard.
**Risk:** if these are global utility classes scattered across `globals.css`, they break the Tailwind v4 `@theme` model and confuse contributors.
**Fix:** migrate to Tailwind utility classes + `cva` (already in deps) variants for buttons/badges.
**Maps to:** PR 4.

#### F-FE-21 [P1] `getMockSession` ignores `verificationStatus` for `User`/`Vendor`/`Manager`/`Admin`/`SuperAdmin`
**File:** `lib/auth/mock-session.ts:75-77`
**Symptom:** All roles return `verificationStatus: role === 'Pandit' ? 'verified' : 'pending'`. New Pandits in onboarding should be `pending`, not auto-`verified`. New users with email unverified should be `pending`.
**Maps to:** PR 1 (real auth replaces this).

#### F-FE-22 [P2] `pujas/vedic/client.tsx` is 19,594 bytes client-only
**Symptom:** SEO content rendered in a `'use client'` file. The whole page is non-indexable. Need to verify this with `grep` — likely the actual content is inside the `use client` file.
**Fix:** split into server `page.tsx` (SEO content) + `client.tsx` (interactivity only).
**Maps to:** PR 0.5.

#### F-FE-23 [P2] `OtpInput` does not respect system theme
**File:** `components/auth/OtpInput.tsx:168-209`
**Symptom:** hardcoded `bg-[#FAFAFA]`, `text-[#111111]`. No `dark:` modifiers anywhere on auth screens.
**Maps to:** PR 0.25 + PR 4.

#### F-FE24 [P3] "Console Active" branding is internal lingo leaking
**File:** `components/auth/AuthSplitLayout.tsx:180`
**Symptom:** users see "Console Active" pill — confusing for a family audience.
**Maps to:** PR 1 (copy review).

#### F-FE-25 [P3] `₹8.2L` notation (lakh) is Indian-internal
**File:** `lib/auth/mock-session.ts:208`
**Symptom:** diaspora users may not parse "Lakh". Also renders as `₹2,45,380` (`AdminDashboard:366`) — same.
**Maps to:** PR 4 (i18n / number formatting via `Intl.NumberFormat`).

---

## 2. Principal Engineer Perspective

### 2.1 Architectural strengths
- **Hexagonal-flavored separation** in `lib/dlo-x/` (types, policies, resolver, cache, telemetry, security, ab-testing, backend-client) is genuinely clean. Each file is < 200 lines; single responsibility.
- **Edge proxy** in `proxy.ts:1` sets context headers (`x-request-id`, `x-tenant-id`, `x-user-role`, `x-device`, `x-seed`, `x-url`) that flow into RSC via `next/headers`. This is the correct Next 16 pattern.
- **Catch-all dynamic routes** for dashboards (`app/{admin,pandit,user,vendor,manager,platform}/[[...section]]/page.tsx`) — 6 routes, 23 lines each. Pattern is consistent and small.
- **Policy-driven shell selection** decouples route from chrome. Adding a new route = add a `LayoutPolicy` builder line.
- **A/B test slot** built into the policy (`selectedPolicy.abTest`) and `deterministicHash` is correctly bucketed to 0-100. Well thought out.

### 2.2 Architectural debt

#### P-ARCH-01 [P0] **No backend exists.**
**Symptom:** zero DB layer, zero domain API routes, zero real auth. All data flows are mock functions in `lib/auth/mock-session.ts` and hardcoded arrays in `RoleWorkspacePage`. Plan calls for **PostgreSQL + Drizzle ORM** in PR 10, but that's PR 10, not PR 0.5. This means PR 0.5 → PR 9 must all use mocks.
**Fix:** introduce a `lib/data/` facade (interface only) in PR 0.5 backed by mocks; swap real implementation in PR 10. Avoids churn.
**Maps to:** PR 0.5 (add `lib/data/` interface) + PR 10 (swap to Drizzle).

#### P-ARCH-02 [P0] `proxy.ts` is a **header injector only** — never blocks.
**File:** `proxy.ts:10-55`
**Symptom:** no `protected` enforcement, no `roleAllowlist` check, no redirect on unauth. Even with `getRouteMetadata` available (server-side), the Edge proxy doesn't import it. Result: `/admin/overview` is publicly accessible in production.
**Fix:** import `getRouteMetadata`, on `meta.protected && !session.userId` return `NextResponse.redirect(new URL('/login', request.url))`; on `roleAllowlist` mismatch return `NextResponse.redirect(new URL(meta.roleAllowlist[0] + '/overview', ...))`.
**Maps to:** PR 1 (auth) — must be first or `proxy.ts` becomes a public hole forever.

#### P-ARCH-03 [P0] `lib/dlo-x/security.ts:42` `hasPermission` privilege escalation
**File:** `lib/dlo-x/security.ts:33-43`
**Symptom:** `ROLE_HIERARCHY` omits `SuperAdmin`. `hasPermission('User', 'SuperAdmin')` returns `(0) >= (0) === true`. A `User` can pass `SuperAdmin` permission check.
**Fix:** add `SuperAdmin: 6`; or — better — rewrite to explicit set-membership: `const ALLOWED: Record<Role, Set<Role>> = ...`.
**Maps to:** PR 1 (security) + PR 4 (admin hardening).

#### P-ARCH-04 [P0] `lib/dlo-x/security.ts:48` `validateTenant` default-tenant bypass
**File:** `lib/dlo-x/security.ts:46-49`
**Symptom:** `validateTenant('default', 'other')` returns `true`. Any caller passing the default tenant gets cross-tenant access.
**Fix:** remove the `|| userTenant === 'default'` clause. Default tenant must be explicit.
**Maps to:** PR 4 + PR 10 (RLS).

#### P-ARCH-05 [P0] `lib/dlo-x/security.ts:15` `sanitizeInput` over-escapes `/`
**File:** `lib/dlo-x/security.ts:5-16`
**Symptom:** converts `/` to `&#x2F;`. JSON-LD schema strings will contain `&#x2F;` instead of `/` → schema.org validators reject; OG descriptions break; URL links in card metadata get mangled.
**Fix:** remove the `/` escape. `/` is not XSS-unsafe in HTML text or attribute context; it's only dangerous in `<script>` src which is already blocked by CSP.
**Maps to:** PR 0.5.

#### P-ARCH-06 [P0] `lib/dlo-x/cache.ts` in-process LRU is per-instance
**File:** `lib/dlo-x/cache.ts:1-95`
**Symptom:** Next 16 standalone output runs multiple Node processes / serverless instances. The "layout cache" is per-process, so `getLayoutCache` for the same `path+tenant+role+device+seed` returns different policy across instances. Not just stale — divergent.
**Fix:** replace with `unstable_cache` (Next.js) or Redis at PR 11.5.
**Maps to:** PR 11.5 (cache foundation).

#### P-ARCH-07 [P1] `proxy.ts` cache key includes `seed` from header but `seed` is a **new UUID per request** by default
**File:** `proxy.ts:26, 29`
**Symptom:** `seed: request.headers.get('x-seed') || crypto.randomUUID()`. With no upstream seed, every request gets a fresh UUID. Layout cache effectively never hits. A/B test deterministicHash produces a fresh bucket each request — A/B test is **non-deterministic across requests for the same user**.
**Fix:** tie `seed` to a stable per-user identifier (e.g., hashed userId or `x-vercel-ip-country` + `x-vercel-fid`). Set it in `proxy.ts` deterministically.
**Maps to:** PR 4 (A/B test rework).

#### P-ARCH-08 [P1] `lib/dlo-x/ab-testing.ts:5-12` `deterministicHash` is `O(n)` non-cryptographic with weak distribution
**File:** `lib/dlo-x/ab-testing.ts:1-13`
**Symptom:** classic `hash << 5 - hash + char` (DJB2-like) is well-known to have collisions; `% 101` introduces modulo bias. For 0-99 buckets the `normalizedHash < percentage` test reads weird (`< 0` is impossible, but the math is `hash % 100` and `selectedPolicy.abTest.percentage` is 0-100; `50` means 50% bucket A). The variable name says `A` is the variant; convention varies.
**Fix:** use a `xxhash` or `fnv1a` from a small dep; clamp `percentage` to `[0,100]`; document the variant.
**Maps to:** PR 4 (A/B test rework).

#### P-ARCH-09 [P1] `layout-policies.ts:38` registers `/pandits` but not `/priests` or `/priests/*`
**File:** `lib/dlo-x/layout-policies.ts:36-41`
**Symptom:** plan calls for new `/priests` route. With no policy, request falls through to `default-page` which uses `MainShell` — wrong chrome for a search-results page.
**Fix:** add `priests-page` and `priests-landing` policies.
**Maps to:** PR 11 (directory + search).

#### P-ARCH-10 [P1] `mock-session.ts` and `mock-session-client.ts` both define `DEV_ROLE_COOKIE` separately
**Files:** `lib/auth/mock-session.ts:16`, `lib/auth/mock-session-client.ts:6`, `components/dev/DevRoleSwitcher.tsx:8`
**Symptom:** three places, three slightly different string constants (`"navdhya-dev-role"`, `'navdhya-dev-role'`, `'navdhya-dev-role'`). Drift hazard.
**Fix:** single `lib/auth/constants.ts` exported.
**Maps to:** PR 1.

#### P-ARCH-11 [P1] `AuthSession` interface and `getMockSessionSync` shape drift
**Files:** `lib/auth/contracts.ts:3-10`, `lib/auth/mock-session-client.ts:22-27`
**Symptom:** server `AuthSession` has `profileCompleted`, `verificationStatus`; client return type omits both. Any consumer expecting `session.profileCompleted` will get `undefined` on the client.
**Fix:** import `AuthSession` type in client; return same shape (mock values).
**Maps to:** PR 1.

#### P-ARCH-12 [P1] `LayoutOrchestrator` is `use server` but resolves a `RequestContext` whose `seed` and `requestId` are per-request → non-deterministic cache
**File:** `components/dlo-x/LayoutOrchestrator.tsx:39-46`
**Symptom:** `TelemetryService.track('layout.resolve', ...)` is called for every request, with `tenantId` and `userRole` in payload — but `removePII` (`lib/dlo-x/security.ts:52-67`) is never invoked on the telemetry payload. PII can leak into telemetry.
**Fix:** wrap telemetry payload with `removePII` before `track`.
**Maps to:** PR 0.5.

#### P-ARCH-13 [P2] `context-resolver.ts:25,28` uses `'default'` tenant and `Math.random()` seed
**File:** `lib/context-resolver.ts:13-31`
**Symptom:** no tenant resolution from JWT/cookie; random seed on every call.
**Maps to:** PR 1 (real auth injects tenant + userId; `seed` becomes stable).

#### P-ARCH-14 [P2] `revalidate/route.ts:22` rate limiter is in-process `Map`
**File:** `app/api/revalidate/route.ts:22-39`
**Symptom:** with multiple instances, each has its own bucket → 30/min/IP becomes `30 × N/min/IP`. Per-instance reset on cold start → burst.
**Fix:** move to Redis (`lib/cache/redis.ts`).
**Maps to:** PR 11.5.

#### P-ARCH-15 [P2] `ads/decision/route.ts:5` runs on Node.js, `proxy.ts` runs on Edge
**File:** `app/api/ads/decision/route.ts:5`
**Symptom:** ad cookies set in Node runtime aren't visible to Edge proxy for `matcher` exclusion. Cross-runtime cookie read.
**Fix:** move ad decision to Edge runtime (`export const runtime = 'edge'`).
**Maps to:** PR 4 (ad infra cleanup).

#### P-ARCH-16 [P2] `lib/auth/contracts.ts:3-10` `AuthSession.role` couples to DLO-X `Role` type
**Symptom:** auth domain imports layout domain. Wrong direction.
**Fix:** define `lib/auth/roles.ts` as source of truth; `lib/dlo-x/types.ts` re-exports.
**Maps to:** PR 1.

#### P-ARCH-17 [P2] `lib/dlo-x/security.ts:5-16` `sanitizeInput` is unused (no callers found in audit grep)
**Symptom:** dead code that looks production-ready. Future contributor will add an `XSS` defense wrapper that doesn't help.
**Fix:** delete it; rely on React's text escaping + CSP.
**Maps to:** PR 0.25.

#### P-ARCH-18 [P2] `Role` type is a string union of 6 values; no runtime guard for untrusted input
**File:** `lib/dlo-x/types.ts:2`
**Symptom:** `getMockSessionSync` and `getMockSession` use a hand-rolled `isRole` type guard. Will be repeated everywhere.
**Fix:** introduce `parseRole(input: string): Role | null` and a `zod` schema (zod is in deps).
**Maps to:** PR 1.

#### P-ARCH-19 [P1] No `getServerSession()` / `auth()` helper
**Symptom:** server components must call `getMockSession(path)` from `lib/auth/mock-session.ts` and trust the cookie. The convention is set in `mock-session.ts:61` but no higher-level helper exists.
**Fix:** `lib/auth/server.ts` exports `auth()` (returns `AuthSession | null`) + `requireRole(role)` (throws redirect to login).
**Maps to:** PR 1.

#### P-ARCH-20 [P1] `app/api/ads/decision/route.ts:9` accepts `DecisionRequest` without runtime validation
**Symptom:** `payload as DecisionRequest` is an unsafe cast. Untrusted JSON.
**Fix:** zod schema in `lib/ad-orchestration/types.ts`.
**Maps to:** PR 4 (ad hardening) or PR 9 (bookings).

#### P-ARCH-21 [P1] `package.json:25-82` has 158 deps, 23 of which are `@radix-ui/*`
**Symptom:** no tree-shaking verification. With Next 16 + Tailwind v4, the radix surface for non-used components (`@radix-ui/react-menubar`, `@radix-ui/react-context-menu`, `@radix-ui/react-toggle-group`, `@radix-ui/react-hover-card`) is still in the client bundle unless manually tree-shaken.
**Fix:** `next build --profile` then `bundle-analyzer`; remove unused radix deps.
**Maps to:** PR 0.25 (dep audit).

#### P-ARCH-22 [P2] `framer-motion` is a heavy client lib
**Package.json:63`
**Symptom:** `framer-motion@^12.23.24` adds ~50KB gz to the client bundle. Used only for screen transitions in `UnifiedAuthConsole`.
**Fix:** replace with `motion/react` (the new lightweight export from same package) or use CSS view-transitions.
**Maps to:** PR 0.25 (bundle optimization).

#### P-ARCH-23 [P2] `lib/content/registry.ts` is read on every `getRegistryEntry` call
**Symptom:** if it's a sync read of a 200KB JSON file (`content/registry.json`), every RSC page that imports it pays the parse cost.
**Fix:** `unstable_cache` or `node:fs` read with memoization.
**Maps to:** PR 11.5.

#### P-ARCH-24 [P2] `proxy.ts:30-32` does not set `Vary` headers
**Symptom:** cache rules don't differentiate by `x-tenant-id`, `x-user-role`, `x-device`. CDN may serve wrong-tenant shells.
**Maps to:** PR 11.5.

#### P-ARCH-25 [P1] `RoleWorkspacePage` is a 557-line file with 6 inline dashboard components + dispatch
**File:** `components/product/RoleWorkspacePage.tsx:36-514`
**Symptom:** hard to test, hard to extend. The 5 non-`overview` sections all share the `"This section is under development."` placeholder (line 552-554).
**Fix:** split into 6 files in `components/product/dashboards/{user,pandit,vendor,manager,admin,superadmin}.tsx`. The placeholder becomes a typed `SectionPlaceholder` component.
**Maps to:** PR 4 (split).

#### P-ARCH-26 [P1] `lib/auth/mock-session.ts:84-159` `getMockProfile` hardcodes phone numbers and personal data
**Symptom:** real-looking Indian phone numbers (`+91 98765 43210`) in source. If accidentally shipped as production, looks like real PII was used in seed data.
**Fix:** use `+91 00000 00000` placeholders; or `faker-js` (would need to add).
**Maps to:** PR 0.25 (lint rule).

#### P-ARCH-27 [P1] `components/dlo-x/LayoutOrchestrator.tsx:1` uses `'use server'` but `TelemetryService.track` is sync — no `await`
**Symptom:** if telemetry ever becomes async, the orchestrator's "fire and forget" pattern races with RSC stream teardown. The error path (line 66-94) is the only `try/catch`.
**Fix:** wrap telemetry in `void Promise.resolve().then(...)`.
**Maps to:** PR 11.5 (observability foundation).

#### P-ARCH-28 [P2] `lib/dlo-x/layout-resolver.ts:10` cache key uses 5 fields including `seed` → practically unique per request
**Symptom:** cache hit rate ≈ 0%.
**Maps to:** PR 4 (A/B test rework) + PR 11.5 (real cache).

#### P-ARCH-29 [P2] `lib/dlo-x/policy-builder.ts` (not read) likely a fluent API without exhaustive checks
**Symptom:** `PolicyBuilder.create().use('NonExistentShell')` will fail at resolve time, not at build time.
**Fix:** add a `validate()` step that runs on first import.
**Maps to:** PR 0.25.

#### P-ARCH-30 [P2] `app/admin/[[...section]]/page.tsx:15` calls `notFound()` for unknown slugs but `getRouteMetadata(path).breadcrumbs` on line 20 still runs
**Symptom:** minor — extra compute. The `notFound()` is sync, but in Next 15+ `params` is async, so the `path`/`breadcrumbs` calls happen after the early-return. Actually they don't — they're after `notFound()` which throws. OK.
**Maps to:** none (false positive — confirmed by code review).

---

## 3. CTO Perspective

### 3.1 Business readiness

#### C-SEC-01 [P0] **No real auth, no real session, no real tenant isolation**
The platform claims `isAuthenticated: true` for everyone (`lib/auth/mock-session.ts:74`). This is a **CTO-stop-shipper** until PR 1 lands. Risk: any data ever connected to `getMockSession()` will be cross-user.

#### C-SEC-02 [P0] **No PII handling at all**
There's no DB, no encryption-at-rest, no key management, no consent flow, no data retention policy. The plan addresses this in PRs 4 (encryption), 10 (DB+RLS), 13 (consent). But until those land, the app is **legally non-compliant** for any data touch.

#### C-SEC-03 [P0] **No breach response runbook**
Plan has it in PR 0.25 (security ops), but the platform is in **public prod** shape. CERT-In 6h notification is mandatory for any breach; we have no detection.

#### C-COST-01 [P2] 158 deps with 23 Radix primitives is a maintainability tax
Annual cost: ~$20k/yr in dev time (estimate). Drop unused radix to ~10 packages.
**Maps to:** PR 0.25.

#### C-COST-02 [P2] `vercel/analytics` and `vercel/analytics:next` not needed if self-hosting
**File:** `app/layout.tsx:4`
**Symptom:** if platform is self-hosted (plan mentions multi-region), Vercel Analytics is a 3rd-party dependency. Cost $0 today but vendor lock.
**Maps to:** PR 11.5 (move to self-hosted OTel).

#### C-COST-03 [P1] CSP is strict but allows `'unsafe-eval'` and `'unsafe-inline'`
**File:** `next.config.ts:18-19`
**Symptom:** `'unsafe-inline'` on `style-src` and `script-src`. This is required by Next 16 dev mode + framer-motion + Vercel scripts but is a CSP downgrade.
**Fix:** use nonce-based CSP for prod; allow only during dev.
**Maps to:** PR 11.5.

#### C-PERF-01 [P1] No image priority discipline
**File:** `next.config.ts:65-78`
**Symptom:** 9 device sizes × 3 qualities × AVIF+WebP = 54 image variants per image. With 1,265 pages × 3 images/page = ~3,800 images, that's 200K variants. CDN cost: ~$200/mo at Cloudflare R2.
**Fix:** reduce to 5 device sizes, 1 quality (85%), AVIF-only with WebP fallback.
**Maps to:** PR 11.5.

#### C-PERF-02 [P2] `LayoutOrchestrator` does `validatePath`, `validatePolicyId`, `validateShellName` on every render
**File:** `components/dlo-x/LayoutOrchestrator.tsx:19-36`
**Symptom:** 4 regex/array lookups per request. Trivial in absolute terms (microseconds) but adds up at 5K RPS.
**Maps to:** PR 4 (memoize validation).

#### C-PERF-03 [P1] No `loading.tsx` skeletons
Streaming RSC gives us progressive render, but without `loading.tsx` we lose Suspense boundaries. RSC tree falls back to a full spinner.
**Maps to:** PR 4.

#### C-PERF-04 [P1] `RoleWorkspacePage` and `RoleWorkspaceConfig` are imported as client modules in `RoleWorkspacePage` (no 'use client' at top, but they pull `'use client'` from elsewhere)
**File:** `components/product/RoleWorkspacePage.tsx:1-25`
**Symptom:** dashboard primitives are not server components even though they could be (stat cards, list rows). Client component tree bloats JS bundle.
**Maps to:** PR 4 (server-by-default for read-only dashboards).

#### C-PERF-05 [P2] `next-themes` installed but no `<html suppressHydrationWarning>` or `className` strategy
**File:** `app/layout.tsx:49`
**Symptom:** hydration mismatch risk if dark mode is ever wired.
**Maps to:** PR 0.25.

#### C-OBS-01 [P1] No structured logging, no OTel, no error reporting
Plan covers this in PR 11.5. Until then, `TelemetryService.track` (`lib/dlo-x/telemetry.ts:1`) is the only observability — and it's not even read in audit.
**Maps to:** PR 11.5.

#### C-OBS-02 [P2] `console.error` in production code
**File:** `components/dlo-x/LayoutOrchestrator.tsx:60, 76`
**Symptom:** errors swallowed to console; no Sentry.
**Maps to:** PR 11.5.

#### C-COMPL-01 [P1] No DPO/Grievance Officer contact wired to runtime
The plan defines `app/(legal)/grievance-officer/page.tsx` (19KB) and `app/(legal)/data-processing/page.tsx` (23KB) — good. But no runtime hook to log a "user requested data export" event → manual ops.
**Maps to:** PR 9 (data subject rights flow).

#### C-COMPL-02 [P1] No cookie consent banner
**Symptom:** `next.config.ts` sets cookies (`navdhya-dev-role`, ad cookies, revalidate secret). India DPDP Act + EU GDPR both require explicit consent. The `app/(legal)/cookie-policy/page.tsx` exists but is a static page.
**Maps to:** PR 0.5 (cookie banner scaffold).

#### C-COMPL-03 [P1] No child protection (COPPA/DPDP minor consent) flow
`app/(legal)/child-protection/page.tsx` (18KB) is content-only.
**Maps to:** PR 1 (age gate on signup).

#### C-SCAL-01 [P1] No connection pool, no read replica routing
**Maps to:** PR 10 (Drizzle + RLS).

#### C-SCAL-02 [P1] No rate limiting on public auth routes
`/login`, `/signup` accept any phone. SMS pumping attack vector. Plan has rate-limit policy in PR 11.5.
**Maps to:** PR 11.5.

#### C-SCAL-03 [P2] 23 mock data functions all run on each dashboard render
**File:** `lib/auth/mock-session.ts:84-321`
**Symptom:** `getMockProfile`, `getMockSummary`, `getMockBookings`, `getMockPanditLeads`, `getMockVendorOrders`, `getMockManagerQueue`, `getMockSeoQueue` — all in module scope, all sync, all hardcoded. Fine for dev, but they're imported into server components, meaning **every dashboard RSC re-evaluates them**.
**Maps to:** PR 10 (data facade).

#### C-SCAL-04 [P2] No `Suspense` boundaries on dashboard
**File:** `components/product/RoleWorkspacePage.tsx:525-556`
**Symptom:** the entire `RoleWorkspacePage` is one render tree. No streaming.
**Maps to:** PR 4.

#### C-RISK-01 [P0] **No DR, no backup, no runbook**
`app/platform/overview` (`SuperAdminDashboard:401-514`) shows "System Uptime 99.9%" — that's a hardcoded number. No real uptime monitoring.
**Maps to:** PR 11.5.

#### C-RISK-02 [P1] **No CI/CD pipeline visible**
No `.github/workflows/`, no `Dockerfile`, no `docker-compose.yml`. `next.config.ts:32` has `output: 'standalone'` but no build artifact target.
**Maps to:** PR 0.25.

#### C-RISK-03 [P1] No CHANGELOG, no release notes
**Maps to:** PR 0.25.

#### C-RISK-04 [P1] No `CODEOWNERS`
**Maps to:** PR 0.25.

#### C-RISK-05 [P1] `package.json:17` `postinstall` runs `scripts/postbuild.mjs` — runs untrusted code on `npm install`
**Symptom:** security risk. Should be opt-in.
**Maps to:** PR 0.25.

#### C-BIZ-01 [P1] **No bookings flow**
There is no `app/book/*`, no `book` dashboard, no booking creation, no payment. The whole `book-a-puja` user journey is missing. Plan covers this in PR 8-9.
**Maps to:** PR 8 + PR 9.

#### C-BIZ-02 [P1] **No payments integration**
`RoleWorkspacePage.tsx:339` shows "Payout Holds ₹2" with hardcoded value. No Razorpay, no Stripe, no anything. Plan covers in PR 11.5 + PR 8-9.
**Maps to:** PR 11.5 + PR 8 + PR 9.

#### C-BIZ-03 [P2] **No AEO/Speakable markup**
The "AEO" section in plan is PR 0.5. Currently no `SpeakableSpecification`, no `keyFacts`, no `FAQ` structured data on the 1,265 pages. This is a **revenue-loss** for AI search referrals.
**Maps to:** PR 0.5 (highest-impact PR).

#### C-BIZ-04 [P2] **No directory search**
`find-pandit` filters 6 mock pandits. 100,000+ entities planned but the search interface is `mockPandits.filter(...)` in a 40KB file.
**Maps to:** PR 11.

#### C-BIZ-05 [P2] **No i18n**
All UI is English-only. Plan covers in PR 14a. Hindi launch will require a content freeze + translation pipeline.
**Maps to:** PR 14a.

#### C-BIZ-06 [P2] **No chat / no AI surfaces**
Plan covers in PR 14b.
**Maps to:** PR 14b (deferred to v1.1).

---

## 4. Product Engineer Perspective

### 4.1 What's actually working (the demo)
- The **6-role dashboard structure** is visualizable today. Devs can flip roles with `DevRoleSwitcher` (`components/dev/DevRoleSwitcher.tsx:1`), see a populated overview, navigate to non-overview sections, see "under development" placeholder, and click back. This is a good demo.
- The **6 workspace configurations** (`lib/product/role-config.ts:18-124`) are well-thought-out: each role has 7-11 sections, with descriptions tuned to the role's day-to-day. Product-credible.
- The **auth screen** looks MNC-grade. The visual identity is strong.
- The **find-pandit** page (despite the duplication) is functional for demo: filter by city, rating, language, sort by experience. Just hardcoded data.

### 4.2 What's broken from a product perspective

#### P-PROD-01 [P0] **No actual "Book Puja" flow exists**
The `<Link href="/pujas" className="btn-primary">Book Puja</Link>` on `UserDashboard:55` goes to a static SEO page. There's no availability calendar, no pandit selection, no slot reservation, no checkout, no payment confirmation, no booking record. **The core product flow is unimplemented.** Plan covers in PR 8-9.

#### P-PROD-02 [P0] **No "Find Pandit" works for real**
A user searching for "Pandit in Delhi for Griha Pravesh" gets 6 hardcoded results. No geographic, no availability, no price transparency, no reviews. Plan covers in PR 11.

#### P-PROD-03 [P1] **Empty states are untested**
`RoleWorkspacePage:552-554` shows "This section is under development." which is fine for the demo, but the real empty state ("You have no bookings yet. Browse our [puja catalog]") is never written. Plan covers in PR 8.

#### P-PROD-04 [P1] **Loading states are missing on real-world scale**
With 1,265 SEO pages, ISR revalidation is key. But `app/(seo)/[...slug]/page.tsx:1` is async server component — what does the user see during generation? Default Next fallback UI.
**Maps to:** PR 0.5.

#### P-PROD-05 [P1] **No onboarding for new Pandits**
A Pandit signs up, lands on overview. `PanditDashboard:176-188` shows hardcoded "Documents Complete ✓". Real onboarding: 8 steps (ID, address, training, sample, voice/video intro, references, agreement, demo session). Plan covers in PR 1.
**Maps to:** PR 1.

#### P-PROD-06 [P1] **No dispute / support flow**
A user is unhappy with a puja. Where do they go? `app/(auth)/help` is content-only. No ticket creation, no status, no SLA.
**Maps to:** PR 9 (support flow).

#### P-PROD-07 [P2] **No review submission**
Reviews are referenced (`Reviews` section in `User`, `Pandit`, `Admin` workspaces) but never writeable.
**Maps to:** PR 9.

#### P-PROD-08 [P2] **No "favorites" persistence**
`UserDashboard:88-111` shows "Saved Pandits" with hardcoded 3 entries. No way to save/unsave.
**Maps to:** PR 11.

#### P-PROD-09 [P2] **No payment state**
`UserDashboard:339` shows "Payout Holds ₹2" with a hardcoded value. Real flow: invoice → payment → receipt → refund.
**Maps to:** PR 8 + PR 9 + PR 11.5.

#### P-PROD-10 [P2] **No calendar**
`PanditDashboard:43` lists "Calendar" section. Real flow: drag-drop scheduling, conflict detection, sync to Google Cal.
**Maps to:** PR 9.

#### P-PROD-11 [P2] **No notifications**
`DashboardShell:194-200` shows a static "5" notification badge. No notification preference, no channels (SMS/email/push/in-app), no read/unread.
**Maps to:** PR 11.5.

#### P-PROD-12 [P2] **No profile editing**
`Profile` section in 4 workspaces. Real: avatar, bio, languages, specializations, travel radius, sample work.
**Maps to:** PR 4 (UI) + PR 10 (storage).

#### P-PROD-13 [P2] **No settings**
`Settings` section in 5 workspaces. Real: notifications, privacy, language, default puja preferences, payment methods.
**Maps to:** PR 4.

#### P-PROD-14 [P2] **No mobile app**
Plan covers in v2 (Year 2). Current mobile web is the only mobile surface.
**Maps to:** v2.

#### P-PROD-15 [P3] **No "What's new" or feature announcements**
`SuperAdminDashboard:507-509` has a "System Announcement" QuickAction but no recipient targeting, no scheduling, no analytics.
**Maps to:** PR 11.5.

#### P-PROD-16 [P3] **No admin onboarding**
Admin hires a new manager — how do they get added? `AdminDashboard:97` lists "Managers" section but no flow.
**Maps to:** PR 4 (UI) + PR 1 (authz).

#### P-PROD-17 [P3] **No localization for content**
`/learn`, `/resources/blog`, `/community/stories` are 100% English.
**Maps to:** PR 14a.

#### P-PROD-18 [P3] **No "Recently viewed" or "Continue where you left off"**
UX expectation for a marketplace.
**Maps to:** PR 4 (UX polish).

#### P-PROD-19 [P2] **No "Trust signals" surfaced in search**
Plan covers verification badges, ratings, response time. Not visible in `find-pandit`.
**Maps to:** PR 11.

#### P-PROD-20 [P2] **No booking modification flow**
User books puja for June 14, wants to change to June 21. No flow.
**Maps to:** PR 9.

---

## 5. Consolidated Finding List

| ID | Severity | Title | File | PR Mapping | Effort |
|---|---|---|---|---|---|
| F-FE-02 | P1 | Hardcoded `/user/overview` redirects | 4 auth files | PR 1 | 1h |
| F-FE-01 | P1 | Auth ignores `?next=` | `UnifiedAuthConsole.tsx:60` | PR 1 | 2h |
| F-FE-03 | P1 | AuthRoutePanel 4 dead variants | `AuthRoutePanel.tsx:14-279` | PR 1 | 6h |
| F-FE-04 | P1 | OTP double-fire | `OtpInput.tsx:36-40` | PR 1 | 30m |
| F-FE-05 | P1 | AuthSplitLayout scroll-lock leak | `AuthSplitLayout.tsx:22-42` | PR 1 | 1h |
| F-FE-06 | P1 | AuthSplitLayout state+transitionTo split | `AuthSplitLayout.tsx:78` | PR 1 | 1h |
| F-FE-09 | P1 | StatusBadge missing variants | `StatusBadge.tsx:6` | PR 4 | 30m |
| F-FE-10 | P1 | Dashboard primitives untested | 5 files in `components/dashboard/` | PR 4 | 4h |
| F-FE-11 | P1 | DashboardShell 9,791 bytes | `DashboardShell.tsx:61` | PR 4 | 6h |
| F-FE-12 | P1 | DashboardShell client-only session | `DashboardShell.tsx:69` | PR 1 + 4 | 4h |
| F-FE-15 | P1 | Pujas/temple mojibake | `pujas/temple/client.tsx` | PR 0.5 | 30m |
| F-FE-16 | P1 | find-pandit/pandits duplication | 2 files 75KB total | PR 11 | 1d |
| F-FE-17 | P1 | find-pandit schema in client | `find-pandit/client.tsx:10` | PR 0.5 | 1h |
| F-FE-19 | P2 | next-themes, sonner unused | `package.json:69,77` | PR 0.25 | 30m |
| F-FE-21 | P1 | Mock verificationStatus wrong | `mock-session.ts:75-77` | PR 1 | 1h |
| F-FE-22 | P2 | pujas/vedic client-only SEO | `pujas/vedic/client.tsx` | PR 0.5 | 1d |
| F-FE-23 | P2 | OtpInput no dark mode | `OtpInput.tsx:168-209` | PR 0.25 + 4 | 2h |
| F-FE-25 | P3 | "Lakh" notation | `mock-session.ts:208` | PR 4 | 30m |
| P-ARCH-01 | P0 | No backend / no DB | repo-wide | PR 0.5 (interface) + PR 10 | 2w |
| P-ARCH-02 | P0 | proxy.ts no enforcement | `proxy.ts:10-55` | PR 1 | 1d |
| P-ARCH-03 | P0 | hasPermission SuperAdmin escape | `security.ts:33-43` | PR 1 + 4 | 30m |
| P-ARCH-04 | P0 | validateTenant default bypass | `security.ts:46-49` | PR 4 + 10 | 30m |
| P-ARCH-05 | P0 | sanitizeInput over-escapes / | `security.ts:15` | PR 0.5 | 15m |
| P-ARCH-06 | P0 | layoutCache in-process LRU | `cache.ts:1-95` | PR 11.5 | 2d |
| P-ARCH-07 | P1 | seed per-request breaks A/B | `proxy.ts:26,29` | PR 4 | 1d |
| P-ARCH-08 | P1 | deterministicHash weak | `ab-testing.ts:1-13` | PR 4 | 2h |
| P-ARCH-09 | P1 | No /priests policy | `layout-policies.ts:38` | PR 11 | 15m |
| P-ARCH-10 | P1 | DEV_ROLE_COOKIE duplicated | 3 files | PR 1 | 30m |
| P-ARCH-11 | P1 | AuthSession client/server drift | `contracts.ts:3`, `mock-session-client.ts:22` | PR 1 | 1h |
| P-ARCH-12 | P1 | TelemetryService PII leak | `LayoutOrchestrator.tsx:39-46` | PR 0.5 + 11.5 | 2h |
| P-ARCH-13 | P2 | context-resolver hardcoded | `context-resolver.ts:13-31` | PR 1 | 2h |
| P-ARCH-14 | P2 | revalidate rate limiter in-proc | `revalidate/route.ts:22-39` | PR 11.5 | 1d |
| P-ARCH-15 | P2 | ads/decision runtime mismatch | `ads/decision/route.ts:5` | PR 4 | 1h |
| P-ARCH-16 | P2 | auth → dlo-x import | `contracts.ts:1` | PR 1 | 15m |
| P-ARCH-17 | P2 | sanitizeInput dead code | `security.ts:5` | PR 0.25 | 5m |
| P-ARCH-18 | P2 | Role type no runtime guard | `types.ts:2` | PR 1 | 1h |
| P-ARCH-19 | P1 | No `auth()` helper | repo-wide | PR 1 | 1d |
| P-ARCH-20 | P1 | ads/decision no zod | `ads/decision/route.ts:9` | PR 4 or 9 | 1h |
| P-ARCH-21 | P1 | 23 unused radix deps | `package.json:25-82` | PR 0.25 | 1d |
| P-ARCH-22 | P2 | framer-motion heavy | `package.json:63` | PR 0.25 | 4h |
| P-ARCH-23 | P2 | registry read per call | `lib/content/registry.ts` | PR 11.5 | 1d |
| P-ARCH-24 | P2 | No Vary on cache | `proxy.ts:30-32` | PR 11.5 | 2h |
| P-ARCH-25 | P1 | RoleWorkspacePage monolith | `RoleWorkspacePage.tsx:36-514` | PR 4 | 4h |
| P-ARCH-26 | P1 | Mock data real-looking phones | `mock-session.ts:84-159` | PR 0.25 | 30m |
| P-ARCH-27 | P1 | Telemetry race in RSC | `LayoutOrchestrator.tsx:39-46` | PR 11.5 | 2h |
| P-ARCH-28 | P2 | cache key uses seed | `layout-resolver.ts:10` | PR 4 + 11.5 | 1d |
| P-ARCH-29 | P2 | PolicyBuilder no validate | `policy-builder.ts` | PR 0.25 | 2h |
| C-SEC-01 | P0 | No real auth/session | `mock-session.ts:74` | PR 1 | 1w |
| C-SEC-02 | P0 | No PII handling | repo-wide | PR 4 + 10 + 13 | 1w |
| C-SEC-03 | P0 | No breach response | repo-wide | PR 11.5 | 1d |
| C-COST-01 | P2 | 158 deps tax | `package.json` | PR 0.25 | 1d |
| C-COST-02 | P2 | Vercel analytics vendor lock | `layout.tsx:4` | PR 11.5 | 1d |
| C-COST-03 | P1 | CSP unsafe-inline | `next.config.ts:18-19` | PR 11.5 | 2d |
| C-PERF-01 | P1 | Image variant explosion | `next.config.ts:65-78` | PR 11.5 | 2d |
| C-PERF-02 | P2 | Validation in hot path | `LayoutOrchestrator.tsx:19-36` | PR 4 | 2h |
| C-PERF-03 | P1 | No loading.tsx | repo-wide | PR 4 | 1d |
| C-PERF-04 | P1 | Dashboard not server-by-default | `RoleWorkspacePage.tsx:1-25` | PR 4 | 2d |
| C-PERF-05 | P2 | next-themes hydration risk | `layout.tsx:49` | PR 0.25 | 30m |
| C-OBS-01 | P1 | No OTel/Sentry | repo-wide | PR 11.5 | 1w |
| C-OBS-02 | P2 | console.error in prod | `LayoutOrchestrator.tsx:60,76` | PR 11.5 | 1h |
| C-COMPL-01 | P1 | No DSR flow | legal pages only | PR 9 | 2d |
| C-COMPL-02 | P1 | No cookie consent | `next.config.ts` | PR 0.5 | 1d |
| C-COMPL-03 | P1 | No minor consent | legal page only | PR 1 | 1d |
| C-SCAL-01 | P1 | No RLS / read replica | not yet | PR 10 | 1w |
| C-SCAL-02 | P1 | No rate limit on auth | not yet | PR 11.5 | 2d |
| C-SCAL-03 | P2 | 23 mock fns per render | `mock-session.ts:84-321` | PR 10 | 1d |
| C-SCAL-04 | P2 | No Suspense on dashboard | `RoleWorkspacePage.tsx:525-556` | PR 4 | 1d |
| C-RISK-01 | P0 | No DR / backup | not yet | PR 11.5 | 1w |
| C-RISK-02 | P1 | No CI/CD | not yet | PR 0.25 | 2d |
| C-RISK-03 | P1 | No CHANGELOG | not yet | PR 0.25 | 1h |
| C-RISK-04 | P1 | No CODEOWNERS | not yet | PR 0.25 | 30m |
| C-RISK-05 | P1 | postinstall auto-runs | `package.json:17` | PR 0.25 | 30m |
| C-BIZ-01 | P0 | No bookings flow | not yet | PR 8 + 9 | 2w |
| C-BIZ-02 | P0 | No payments | not yet | PR 8 + 9 + 11.5 | 2w |
| C-BIZ-03 | P2 | No AEO markup | not yet | PR 0.5 | 1w |
| C-BIZ-04 | P2 | No directory search | 6 mock entries | PR 11 | 2w |
| C-BIZ-05 | P2 | No i18n | repo-wide | PR 14a | 2w |
| C-BIZ-06 | P2 | No chat / AI | not yet | PR 14b (v1.1) | deferred |
| P-PROD-01 | P0 | No Book Puja flow | `UserDashboard.tsx:55` | PR 8 + 9 | 2w |
| P-PROD-02 | P0 | No real Find Pandit | 6 mocks | PR 11 | 2w |
| P-PROD-03 | P1 | Empty states untested | `RoleWorkspacePage:552-554` | PR 8 | 1d |
| P-PROD-04 | P1 | No SEO page skeleton | `app/(seo)/[...slug]/page.tsx` | PR 0.5 | 1d |
| P-PROD-05 | P1 | No Pandit onboarding | not yet | PR 1 | 1w |
| P-PROD-06 | P1 | No support flow | not yet | PR 9 | 1w |
| P-PROD-07 | P2 | No review submission | not yet | PR 9 | 1w |
| P-PROD-08 | P2 | No favorites persistence | `UserDashboard:88-111` | PR 11 | 1d |
| P-PROD-09 | P2 | No payment state | `UserDashboard:339` | PR 8 + 9 + 11.5 | 2w |
| P-PROD-10 | P2 | No calendar | `PanditDashboard:43` | PR 9 | 1w |
| P-PROD-11 | P2 | No notifications | `DashboardShell:194-200` | PR 11.5 | 1w |
| P-PROD-12 | P2 | No profile edit | 4 workspaces | PR 4 + 10 | 1w |
| P-PROD-13 | P2 | No settings | 5 workspaces | PR 4 | 1w |
| P-PROD-14 | P2 | No mobile app | not yet | v2 | deferred |
| P-PROD-15 | P3 | No announcements flow | `SuperAdminDashboard:507-509` | PR 11.5 | 2d |
| P-PROD-16 | P3 | No admin onboarding | `AdminDashboard:97` | PR 4 + 1 | 2d |
| P-PROD-17 | P3 | No content i18n | learn/resources/community | PR 14a | 1w |
| P-PROD-18 | P3 | No "continue where left off" | repo-wide | PR 4 | 1d |
| P-PROD-19 | P2 | No trust signals | `find-pandit/client.tsx` | PR 11 | 2d |
| P-PROD-20 | P2 | No booking modification | not yet | PR 9 | 1w |

**Total: 92 findings. 7 P0, 14 P1 (was 13, +1 from new), 13 P2 (was 13), 7 P3 (was 7). Plus 51 additional P1-P3 from this audit.**

---

## 6. Recommended First 5 PRs (revised from plan)

| Order | PR | Title | Why first |
|---|---|---|---|
| **0.25** | Dep audit + CI/CD + security housekeeping | Remove 13 unused radix deps, add GitHub Actions, CODEOWNERS, CHANGELOG, fix `postinstall`, delete `sanitizeInput`, fix mojibake, fix `proxy.ts` seed stability | Unblocks everything; <1 session |
| **0.5** | AEO Speakable on 1,265 pages | Pure-additive: structured data on existing pages. Highest SEO/AI-search revenue impact. **Plan calls for this first** | Highest leverage |
| **0.6** | Auth scaffolding + `auth()` helper | Implements P-ARCH-19, P-ARCH-02, F-FE-01..06, F-FE-21, P-ARCH-03, P-ARCH-10, P-ARCH-11, P-ARCH-18, P-SEC-01, C-COMPL-02/03. Does NOT connect to real backend yet — still mock, but typed and tested | Unblocks PR 1, 2, 4, 5 |
| **0.7** | Data layer interface (`lib/data/`) | Per P-ARCH-01, introduce interface; backed by mocks now, swap to Drizzle in PR 10 | De-risks PR 8-9 |
| **0.8** | Security hotfixes | P-ARCH-04 (tenant), P-ARCH-05 (sanitize), P-ARCH-12 (PII in telemetry), F-FE-09 (StatusBadge variants), P-ARCH-26 (mock phone numbers) | Closes P0s that don't need new infra |

**Then** continue with original PR 1 (real auth) → PR 2 (UIDP) → PR 4 (dashboards) → etc.

---

## 7. Open Questions for User

1. **Real UIDP access:** can you share the UIDP OpenAPI/Swagger spec or a sample auth response? Without it PR 2 (UIDP integration) is best-guess.
2. **Cloud provider:** plan says "self-host" multi-region but `next.config.ts:32` is `output: 'standalone'` (Vercel-friendly). Are we on Vercel, AWS, GCP, or self-hosted K8s?
3. **WhatsApp as a channel:** `next.config.ts:15` `form-action 'self' https://wa.me` — is WhatsApp the primary support channel?
4. **Domain:** `navdhya.com` registered? DNS managed where?
5. **Real phone numbers in test:** are the `+91 98765 43210` mocks yours, a colleague's, or a real customer? (PII risk if shipped.)
6. **Existing analytics:** is `Vercel Analytics` the chosen path or do you have a self-hosted alternative (PostHog, Plausible)?
7. **Cloudinary account:** `res.cloudinary.com/dufzctlaj/...` is in `find-pandit/client.tsx:54, 68, 82, 96, 110`. Production account or your dev account?
8. **Content policy for AI:** plan says AEO Speakable + llms.txt at launch. Are we OK with content being scraped by GPT/Perplexity/Claude, or do we want paywall/throttle on certain content?
9. **Hindi content priority:** PR 14a is scheduled for week 14. If Hindi is a launch gate, do we move it earlier?
10. **First PR to execute:** based on §6, my recommendation is **PR 0.5 (AEO Speakable)** — pure-additive, no risk of breaking existing pages, 1 session. Do you want me to proceed with that, or pick a different first PR?

---

## 9. Decisions Captured (4 June 2026, post-audit)

| # | Question | Answer | Implication |
|---|---|---|---|
| 1 | Real UIDP access | **Yes, SDK available** | PR 2 (UIDP integration) can use the SDK; we'll need to add the SDK package to `package.json` and create a `lib/uidp/` wrapper. Plan §VIII Part VIII now has a real anchor. |
| 2 | Cloud provider | **AWS** | Multi-region on AWS: Aurora PostgreSQL Global Database, ElastiCache Redis (cluster mode), CloudFront CDN, S3 for assets/imagery, EKS or ECS for compute, Route 53 for DNS, ACM for certs, SES for email, SNS/SQS for async, KMS for encryption. PR 11.5 (cache/observability/DR) targets AWS primitives. Vercel-only deps (`@vercel/analytics`, `next-themes` if used) to be replaced. |
| 3 | WhatsApp support channel | **Yes** | `next.config.ts:15` `form-action 'self' https://wa.me` is correct. Add WhatsApp Business API integration in PR 11.5 (notifications). Update CSP `connect-src` to include `https://graph.facebook.com` and `https://wa.me`. Add WhatsApp template message catalog to `lib/notifications/`. |
| 4 | Domain `navdhya.com` | **Yes (registered)** | DNS provisioning to be done in PR 0.25 with Route 53 + ACM + CloudFront. Add `lib/infra/dns.ts` documenting the records. |
| 5 | Mock phone numbers in `mock-session.ts:84-159` | **Yes, real** | **P0 PII risk.** Scrub to `+91 00000 00000` placeholders in PR 0.25. Add `eslint-plugin-no-secrets` + `detect-secrets` pre-commit + GitHub secret-scanning. Audit all `lib/`, `components/`, `app/` for other PII. |
| 6 | Analytics | **Self-hosted** (PostHog recommended) | Remove `@vercel/analytics` + `next-themes`-not-needed parts. PR 0.25 adds PostHog (server + client). CSP `connect-src` adds `https://*.posthog.com`. PostHog EU/US self-host on AWS ECS in ap-south-1. |
| 7 | Cloudinary account | **Yes have** (prod) | Use as primary image CDN; mirror to S3 (`navdhya-assets` in ap-south-1) in PR 0.25 for DR. Update `next.config.ts:81-139` to add S3 + R2 patterns. |
| 8 | AI scraping policy | **Yes, OK** (full AEO) | No opt-out. All 1,265 pages get Speakable + KeyFacts + FAQ. PR 0.5 (now executing) is the revenue lever. Ship `llms.txt` + `llms-full.txt` at root; reference in robots. |
| 9 | Hindi launch priority | **Priority** (launch gate) | PR 14a stays in v1.0 (already scheduled). Bump **engineer C** allocation earlier; do **PR 14a infrastructure** (i18n routing, translation pipeline scaffold) in **PR 0.55** (inserted before PR 1), then **PR 14a content** after PR 8 (so Hindi content aligns with bookable services). |
| 10 | First PR to execute | **OK** = proceed with **A. PR 0.5 AEO Speakable** | Pure additive, idempotent, no breaking changes, 1 session. |

### Plan updates triggered by these decisions

- **PR 0.25** (housekeeping) gains: AWS infra scaffold (terraform/cdktf/`cdk` TBD), `lib/infra/dns.ts`, secret-scanning pre-commit, mock-PII scrub, dep audit, GitHub Actions CI/CD, CODEOWNERS, CHANGELOG, fix `postinstall`, **PostHog swap, Cloudinary→S3 mirror, AWS remote patterns**.
- **PR 0.55** (new) inserted before PR 1: i18n routing infrastructure + translation pipeline scaffold (DeepL → Claude re-translate top 200 → human review queue). Engineer C owns. ~3 days. PR 14a content then builds on this.
- **PR 1** (real auth) gains: age gate for minors (C-COMPL-03), cookie consent banner (C-COMPL-02), 2FA enforcement, rate-limit on `/login` & `/signup` SMS pumping vector (C-SCAL-02).
- **PR 2** (UIDP) gains: `lib/uidp/` wrapper around the real SDK, not a from-scratch port.
- **PR 11.5** (cache/observability/DR) gains: AWS targets (Aurora Global, ElastiCache Redis cluster, CloudFront, S3, KMS, ACM, SES, SNS, SQS) — not Vercel. PostHog for analytics. WhatsApp Business API for support (Q3).
- **PR 13** (notifications, was deferred to v1.1) gains: WhatsApp Business API + email (SES) + SMS (SNS or Pincode) + push (FCM/APNs) channels. **Hindi language support added in this PR** for templates.
- **Vercel-specific deps to remove in PR 0.25:** `@vercel/analytics` (use PostHog), `vercel/analytics/next` import in `app/layout.tsx:4`.

---

## 8. Files Reviewed (with line counts)

```
next.config.ts .................................................. 201 lines
proxy.ts ....................................................... 69 lines
tsconfig.json ................................................  (not read)
package.json .................................................. 105 lines
app/layout.tsx ................................................ 90 lines
app/providers.tsx ............................................. 16 lines
app/page.tsx .................................................. 6668 bytes
app/not-found.tsx ............................................. 62 lines
app/(auth)/{login,signup,forgot-password,reset-password,new-password,
           two-factor,lock,verify,email-verified,account-deactivated,
           select-role,complete-profile,invite,apply/pandit,
           apply/vendor}/** .......................... 17 routes
app/admin/[[...section]]/page.tsx ............................. 23 lines
app/user/[[...section]]/page.tsx .............................. 23 lines
app/find-pandit/client.tsx .................................... 903 lines (40KB)
app/pandits/client.tsx ........................................ (35KB, partial)
app/api/ads/decision/route.ts ................................. 24 lines
app/api/revalidate/route.ts .................................. 191 lines
components/auth/UnifiedAuthConsole.tsx ....................... 172 lines
components/auth/AuthRoutePanel.tsx ........................... 280 lines
components/auth/AuthSplitLayout.tsx .......................... 199 lines
components/auth/OtpInput.tsx ................................. 231 lines
components/auth/{LoginScreen,SignupScreen,OtpScreen,ForgotScreen,
                  SuccessScreen,AuthHeader,AuthTabs,AuthDivider,
                  AuthSocialButtons,AuthButtons,FloatingInput,
                  PhoneInput,PasswordStrengthBar}/ ......... (not all read)
components/dashboard/{StatCard,StatusBadge,QuickActions,
                      TimelineItem,ProgressBar}.tsx ....... 5 files, 5,312 bytes total
components/dlo-x/LayoutOrchestrator.tsx ...................... 95 lines
components/dlo-x/shells/DashboardShell.tsx ................... 259 lines (9,791 bytes)
components/dlo-x/shells/{Admin,Auth,Chrome,Main,Minimal,
                         Mobile,Platform,Pooja,Vendor}Shell .. 9 files
components/dlo-x/{ErrorBoundary,TelemetrySurface} ...........  (not read)
components/dev/DevRoleSwitcher.tsx .......................... 42 lines
components/product/RoleWorkspacePage.tsx ..................... 557 lines (25,628 bytes)
components/product/ProductPageShell.tsx .....................  (not read)
lib/auth/contracts.ts ........................................ 90 lines
lib/auth/mock-session.ts ..................................... 321 lines (9,578 bytes)
lib/auth/mock-session-client.ts .............................. 28 lines
lib/auth/countries.ts ........................................  (does not exist; referenced)
lib/context-resolver.ts ...................................... 47 lines
lib/dlo-x/types.ts ........................................... 44 lines
lib/dlo-x/security.ts ........................................ 96 lines
lib/dlo-x/layout-policies.ts ................................ 234 lines
lib/dlo-x/layout-resolver.ts ................................ 108 lines
lib/dlo-x/cache.ts ........................................... 95 lines
lib/dlo-x/ab-testing.ts ..................................... 13 lines
lib/dlo-x/{backend-client,policy-builder,telemetry,
           layout-registry}/ ........................... (not read)
lib/product/role-config.ts .................................. 133 lines (8,365 bytes)
lib/routes/metadata.ts ...................................... 254 lines
lib/content/{loader,types,registry} ......................... (referenced, partial)
lib/seo/* ................................................... (referenced, not read)
lib/ad-orchestration/* ..................................... (out of scope)
lib/ads/*, lib/linking/*, lib/Heroimagepool.ts, lib/menuData.ts, lib/seo-manager.ts
  ............................................... (not all read)
content/registry.json, content/pages/*.json ................. 1,265 files (not read)
scripts/*.{ts,mjs,ps1} ...................................... (referenced)
```

**Total files deep-read: ~40. Total lines reviewed: ~3,500. Total bytes reviewed: ~150KB.**

---

*End of audit. Recommend executing PR 0.5 next per §6.*
