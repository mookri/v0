# Partner App Frontend — Plan & Backend Alignment

**Status:** Proposed (audit of current state)
**Date:** 2026-10-19
**Scope:** `apps/partner` (Next.js 16) ↔ `platform-navdhya` (Laravel kernel + modules)
**Related:** `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md` (W1–W5), ADR-0020/0021/0022.

> Every claim below was read from the working tree. Backend endpoints are under
> `/api/v1/{module}/{resource}` (module routes) and `/api/v1/platform/*` (kernel).
> The partner BFF builds `${NAVDHYA_API_BASE}/api/v1${path}` and forwards the
> caller's session token as `Bearer` plus `X-Tenant-Id` from `PARTNER_TENANT_ID`.

---

## 0. TL;DR

The partner app is **substantially complete for read surfaces** and **thin on mutations**.
Read paths (temple, vendor, onboarding, pandit, search) are wired end-to-end with
mock fallbacks. Mutation paths (create/update/delete/advance/cancel/decide) exist
as BFF routes for some surfaces but have **no UI** for most of them. The plan
below closes that gap slice by slice, each with a test gate.

---

## 1. Current-state inventory

### 1.1 Backend endpoints available to partners

| Module | Endpoint | Method | Capability / Scope | Partner BFF? | Partner Page? |
|---|---|---|---|---|---|
| **Onboarding** | `applications` | POST | `onboarding:applicant` | ✅ start | ✅ onboarding/[id] |
| | `applications/{id}` | GET | `onboarding:applicant` | ✅ | ✅ |
| | `applications/{id}/submit` | POST | `onboarding:applicant` | ✅ submit | ✅ |
| | `applications/{id}/submit/resend` | POST | `onboarding:applicant` | ✅ resend | ✅ |
| | `applications/{id}/submit/confirm` | POST | `onboarding:applicant` | ✅ confirm | ✅ |
| | `applications/{id}/step/{step}` | PUT | `onboarding:applicant` | ✅ step | ✅ |
| | `applications/{id}/documents` | GET | `onboarding:applicant` | ✅ | ✅ |
| | `review/queue` | GET | `onboarding:review:view` | ✅ queue | ✅ review |
| | `review/{id}:approve` | POST | `onboarding:review:approve` + stepup:2 | ✅ decision | ✅ review/[id] |
| | `review/{id}:reject` | POST | `onboarding:review:reject` + stepup:2 | ✅ decision | ✅ review/[id] |
| | `review/{id}:request-info` | POST | `onboarding:review:view` | ❌ | ❌ |
| **Temple** | `partner/temples` | GET/POST | `scope:temple:partner` | ✅ list | ✅ temples |
| | `partner/temples/{id}` | GET/PATCH | `scope:temple:partner` | ✅ detail | ✅ temples/[id] |
| | `partner/temples/{id}/pujas` | GET/POST | `scope:temple:partner` | ✅ pujas | ✅ dashboard/pujas |
| | `partner/temples/{id}/pujas/{pujaId}` | GET/PUT/DELETE | `scope:temple:partner` | ✅ | ✅ |
| | `partner/temples/{id}/managers` | GET/POST/PUT/DELETE | `scope:temple:partner` + `capability:temple:manager:manage` (W3) | ✅ GET/POST/PUT/DELETE | ✅ managers |
| | `partner/temples/{id}/dashboard/*` | GET | `scope:temple:partner` | ✅ overview/pujas/bookings/calendar/analytics/settings | ✅ dashboard/* |
| **Vendor** | `partner/vendors` | GET/POST | `scope:vendor:partner` | ✅ list/create | ✅ vendors, vendors/new |
| | `partner/vendors/{id}` | GET/PATCH | `scope:vendor:partner` | ✅ detail | ✅ vendors/[id] |
| | `partner/vendors/{id}/products` | GET/POST/PATCH | `scope:vendor:partner` | ✅ list | ✅ vendors/[id]/products |
| | `partner/vendors/{id}/inventory` | GET | `scope:vendor:partner` | ✅ | ✅ vendors/[id]/inventory |
| | `partner/vendors/{id}/inventory:adjust` | POST | `scope:vendor:partner` | ✅ adjust | ❌ no UI |
| | `partner/vendors/{id}/orders` | GET/POST | `scope:vendor:partner` | ✅ list/create | ✅ vendors/[id]/orders |
| | `partner/vendors/{id}/orders/{orderId}` | GET | `scope:vendor:partner` | ✅ show | ✅ vendors/[id]/orders/[orderId] |
| | `partner/vendors/{id}/orders/{orderId}:advance` | POST | `scope:vendor:partner` | ✅ advance | ❌ no UI |
| | `partner/vendors/{id}/orders/{orderId}:cancel` | POST | `scope:vendor:partner` | ✅ cancel | ❌ no UI |
| | `partner/vendors/{id}/payouts` | GET | `scope:vendor:partner` + `capability:vendor:payout:view` (W3) | ✅ list | ✅ vendors/[id]/payouts |
| | `partner/vendors/{id}/settings` | (dashboard) | `scope:vendor:partner` | ✅ | ✅ vendors/[id]/settings |
| **Pandit** | `pandit/apply` | POST | `scope:pandit:self` | ❌ | ❌ |
| | `pandit/application` | GET | `scope:pandit:self` | ❌ | ❌ |
| | `pandit/profile` | GET/PATCH | `scope:pandit:self` | ✅ GET/PATCH | ❌ no page |
| | `pandit/availability` | GET/PUT | `scope:pandit:self` | ✅ GET/PUT | ❌ no page |
| | `pandit/assignments` | GET | `scope:pandit:self` | ✅ GET | ❌ no page |
| | `pandit/assignments/{id}:confirm` | POST | `scope:pandit:self` | ✅ POST | ❌ no page |
| | `pandit/assignments/{id}:decline` | POST | `scope:pandit:self` | ✅ POST | ❌ no page |
| | `pandit/assignments/{id}:complete` | POST | `scope:pandit:self` | ✅ POST | ❌ no page |
| | `pandit/earnings` | GET | `scope:pandit:self` | ✅ GET | ❌ no page |
| | `pandit/reviews` | GET | `scope:pandit:self` | ✅ GET | ❌ no page |
| **Onboarding** | `applications` | POST | `onboarding:applicant` | ✅ start | ✅ onboarding/[id] |
| | `applications/{id}` | GET | `onboarding:applicant` | ✅ | ✅ |
| | `applications/{id}/submit` | POST | `onboarding:applicant` | ✅ submit | ✅ |
| | `applications/{id}/submit/resend` | POST | `onboarding:applicant` | ✅ resend | ✅ |
| | `applications/{id}/submit/confirm` | POST | `onboarding:applicant` | ✅ confirm | ✅ |
| | `applications/{id}/step/{step}` | PUT | `onboarding:applicant` | ✅ step | ✅ |
| | `applications/{id}/documents` | GET | `onboarding:applicant` | ✅ | ✅ |
| | `review/queue` | GET | `onboarding:review:view` | ✅ queue | ✅ review |
| | `review/{id}:approve` | POST | `onboarding:review:approve` + stepup:2 | ✅ decision | ✅ review/[id] |
| | `review/{id}:reject` | POST | `onboarding:review:reject` + stepup:2 | ✅ decision | ✅ review/[id] |
| | `review/{id}:request-info` | POST | `onboarding:review:view` | ❌ | ❌ |
| **Search** | `search` | GET | public | ✅ | ❌ no page |
| | `search/suggest` | GET | public | ❌ | ❌ |
| | `search/trending` | GET | public | ❌ | ❌ |
| | `search/entities/{type}/{slug}` | GET | public | ❌ | ❌ |
| **Auth** | authorize/callback/login/logout/me | various | federation | ✅ | ✅ login |

### 1.2 Pages that exist

| Route | Page | Status |
|---|---|---|
| `/` | `app/page.tsx` | ✅ home |
| `/assignments` | `app/assignments/page.tsx` | ✅ |
| `/onboarding/[id]` | `app/onboarding/[id]/page.tsx` | ✅ |
| `/onboarding/[id]/review` | `app/onboarding/[id]/review/page.tsx` | ✅ |
| `/onboarding/[id]/[step]` | `app/onboarding/[id]/[step]/page.tsx` | ✅ |
| `/pandit` | `app/pandit/page.tsx` | ✅ (just added, read-only) |
| `/review` | `app/review/page.tsx` | ✅ |
| `/review/[id]` | `app/review/[id]/page.tsx` | ✅ |
| `/temples` | `app/temples/page.tsx` | ✅ |
| `/temples/[id]` | `app/temples/[id]/page.tsx` | ✅ |
| `/temples/[id]/dashboard/*` | 6 pages | ✅ |
| `/temples/[id]/managers` | `app/temples/[id]/managers/page.tsx` | ✅ (just added) |
| `/vendors` | `app/vendors/page.tsx` | ✅ |
| `/vendors/new` | `app/vendors/new/page.tsx` | ✅ |
| `/vendors/[id]` | `app/vendors/[id]/page.tsx` | ✅ |
| `/vendors/[id]/inventory` | `app/vendors/[id]/inventory/page.tsx` | ✅ |
| `/vendors/[id]/orders` | `app/vendors/[id]/orders/page.tsx` | ✅ |
| `/vendors/[id]/orders/[orderId]` | `app/vendors/[id]/orders/[orderId]/page.tsx` | ✅ |
| `/vendors/[id]/payouts` | `app/vendors/[id]/payouts/page.tsx` | ✅ |
| `/vendors/[id]/products` | `app/vendors/[id]/products/page.tsx` | ✅ |
| `/vendors/[id]/settings` | `app/vendors/[id]/settings/page.tsx` | ✅ |

### 1.3 Gaps (BFF exists, no UI)

| Capability | BFF Route | Missing UI |
|---|---|---|
| `onboarding:review:request-info` | ❌ no BFF route | Review decision button |
| `temple:manager:manage` (store/update/destroy) | ❌ no BFF routes | Add/edit/remove manager buttons |
| `vendor:product:manage` (store/update) | ❌ no BFF routes | Create/edit product forms |
| `vendor:order:manage` (advance/cancel) | ✅ BFF routes exist | Advance/cancel buttons on order detail |
| `vendor:inventory:adjust` | ✅ BFF route exists | Adjust button on inventory page |
| `pandit:profile` (updateOwn) | ✅ BFF PATCH route | Profile edit form |
| `pandit:availability` (replaceOwn) | ✅ BFF PUT route | Availability editor |
| `pandit:assignments` (confirm/decline/complete) | ✅ BFF POST routes | Action buttons on assignment list |
| `pandit:earnings` (own) | ✅ BFF route | Earnings view (currently JSON dump) |
| `pandit:reviews` (ownIndex) | ✅ BFF route | Reviews view |

### 1.4 Gaps (BFF missing entirely)

| Capability | Backend Endpoint | Missing BFF + UI |
|---|---|---|
| `onboarding:review:request-info` | `POST /onboarding/review/{id}:request-info` | BFF route + button |
| `temple:manager:manage` (store) | `POST /temple/partner/temples/{id}/managers` | BFF route + form |
| `temple:manager:manage` (update) | `PUT /temple/partner/temples/{id}/managers/{mid}` | BFF route + form |
| `temple:manager:manage` (destroy) | `DELETE /temple/partner/temples/{id}/managers/{mid}` | BFF route + button |
| `vendor:product:manage` (store) | `POST /vendor/partner/vendors/{id}/products` | BFF route + form |
| `vendor:product:manage` (update) | `PATCH /vendor/partner/vendors/{id}/products/{itemId}` | BFF route + form |
| `search:suggest` | `GET /search/suggest` | BFF route + UI |
| `search:trending` | `GET /search/trending` | BFF route + UI |
| `search:entities` | `GET /search/entities/{type}/{slug}` | BFF route + UI |
| `search:analytics` | `POST /search/analytics` | BFF route (fire-and-forget) |

---

## 2. Plan — phased workstreams

### P0 — Mutation buttons on existing surfaces (highest ROI, ~2 days)

These surfaces already have BFF routes; they just need UI buttons.

| Surface | Action | BFF Route | Capability | Tests |
|---|---|---|---|---|
| Vendor orders detail | Advance order | `POST /api/vendors/{id}/orders/{orderId}/advance` ✅ exists | `vendor:order:manage` | BFF test + button test |
| Vendor orders detail | Cancel order | `POST /api/vendors/{id}/orders/{orderId}/cancel` ✅ exists | `vendor:order:manage` | BFF test + button test |
| Vendor inventory page | Adjust stock | `POST /api/vendors/{id}/inventory:adjust` ✅ exists | `vendor:product:manage` | BFF test + button test |
| Pandit assignments | Confirm | `POST /api/pandit/assignments/{id}/confirm` ✅ exists | `scope:pandit:self` | BFF test + button test |
| Pandit assignments | Decline | `POST /api/pandit/assignments/{id}/decline` ✅ exists | `scope:pandit:self` | BFF test + button test |
| Pandit assignments | Complete | `POST /api/pandit/assignments/{id}/complete` ✅ exists | `scope:pandit:self` | BFF test + button test |
| Review detail | Request info | ❌ no BFF route | `onboarding:review:view` | new BFF route + button |

**Deliverables:** 6 mutation buttons wired into existing pages + 1 new BFF route (request-info) + tests.

### P1 — Missing BFF routes for existing backend endpoints (~1 day)

| BFF Route | Backend Endpoint | Notes |
|---|---|---|
| `POST /api/review/{id}/request-info` | `POST /onboarding/review/{id}:request-info` | No step-up needed |
| `POST /api/temples/{id}/managers` | `POST /temple/partner/temples/{id}/managers` | `capability:temple:manager:manage` |
| `PUT /api/temples/{id}/managers/{mid}` | `PUT /temple/partner/temples/{id}/managers/{mid}` | `capability:temple:manager:manage` |
| `DELETE /api/temples/{id}/managers/{mid}` | `DELETE /temple/partner/temples/{id}/managers/{mid}` | `capability:temple:manager:manage` |
| `POST /api/vendors/{id}/products` | `POST /vendor/partner/vendors/{id}/products` | `capability:vendor:product:manage` |
| `PATCH /api/vendors/{id}/products/{itemId}` | `PATCH /vendor/partner/vendors/{id}/products/{itemId}` | `capability:vendor:product:manage` |
| `GET /api/search/suggest` | `GET /search/suggest` | Public, no auth |
| `GET /api/search/trending` | `GET /search/trending` | Public, no auth |
| `GET /api/search/entities/{type}/{slug}` | `GET /search/entities/{type}/{slug}` | Public, no auth |

**Deliverables:** 9 new BFF route files + tests.

### P2 — Missing UI surfaces (~2 days)

| Surface | Page | View Component | Backed By |
|---|---|---|---|
| Temple managers CRUD | `temples/[id]/managers/page.tsx` (exists, read-only) | `TempleManagersView` (exists, read-only) | Add invite/edit/remove forms |
| Vendor product create/edit | `vendors/[id]/products/page.tsx` (exists, list-only) | `VendorProductsView` | Add create/edit forms |
| Pandit profile editor | `pandit/profile/page.tsx` (new) | `PanditProfileView` (new) | GET/PATCH `/pandit/profile` |
| Pandit availability editor | `pandit/availability/page.tsx` (new) | `PanditAvailabilityView` (new) | GET/PUT `/pandit/availability` |
| Pandit assignments (with actions) | `pandit/assignments/page.tsx` (new) | `PanditAssignmentsView` (new) | GET + confirm/decline/complete |
| Pandit earnings view | `pandit/earnings/page.tsx` (new) | `PanditEarningsView` (new) | GET `/pandit/earnings` |
| Pandit reviews view | `pandit/reviews/page.tsx` (new) | `PanditReviewsView` (new) | GET `/pandit/reviews` |
| Search (suggest/trending) | integrate into existing search | — | GET `/search/suggest`, `/search/trending` |

**Deliverables:** 7 new pages + views + forms + tests.

### P3 — Mutation UI on existing surfaces (~1 day)

| Surface | Mutation | UI |
|---|---|---|
| Vendor orders detail | Advance/Cancel buttons | Add to `VendorOrderDetailView` |
| Vendor inventory | Adjust stock button | Add to `VendorInventoryView` |
| Temple managers | Invite/Edit/Remove | Add to `TempleManagersView` |
| Vendor products | Create/Edit | Add to `VendorProductsView` |
| Pandit assignments | Confirm/Decline/Complete | Add to `PanditAssignmentsView` |
| Pandit profile | Edit form | Add to `PanditProfileView` |
| Pandit availability | Replace editor | Add to `PanditAvailabilityView` |

**Deliverables:** 7 mutation UIs + tests.

### P4 — Test estate (~1 day)

| Test | File | Scope |
|---|---|---|
| BFF route tests | `app/api/**/__tests__/*.test.ts` | Envelope contract, auth, error mapping for all new routes |
| View tests | `presentation/**/__tests__/*.test.tsx` | Row rendering, empty state, action buttons |
| E2E | `e2e/partner.spec.ts` | Login → onboarding → review → temple → vendor → pandit flows |

---

## 3. Test strategy

| Layer | Where | Asserts | Command |
|---|---|---|---|
| BFF unit | `apps/partner/app/api/**/__tests__/*.test.ts` | envelope, auth, error mapping, tenant header | `pnpm --filter @navdhya/partner-app run test` |
| View unit | `apps/partner/presentation/**/__tests__/*.test.tsx` | rows, empty, action buttons | ″ |
| Typecheck | — | `tsc --noEmit` | `pnpm --filter @navdhya/partner-app run typecheck` |
| E2E | `e2e/partner.spec.ts` | full partner flows against live stack | `pnpm --dir e2e test` |

**Guiding rules:**
- Every mutation button has a corresponding BFF test proving the direct API call is forwarded correctly.
- Step-up-gated actions (approve/reject) reuse the `stepup:2` challenge; the UI prompts for TOTP.
- Mock fallbacks stay honest: `fallback: true` renders a config notice, never fake success.

---

## 4. Sequencing & milestones

| Milestone | Workstreams | Exit criteria |
|---|---|---|
| **P-M1 — Mutation buttons** | P0 | All 6 existing-surface mutations have working buttons + tests |
| **P-M2 — Missing BFF routes** | P1 | 9 new BFF routes + tests; contract test covers partner paths |
| **P-M3 — Missing UI surfaces** | P2 | 7 new pages + views + tests |
| **P-M4 — Mutation UI** | P3 | All mutation surfaces wired |
| **P-M5 — Test estate** | P4 | Full partner test suite green; E2E smoke |

**Full gate before any merge:**
`pnpm --filter @navdhya/partner-app run typecheck` + `pnpm --filter @navdhya/partner-app run test` + `pnpm run check:architecture`.

---

## 5. Open decisions

- **PD1 — Pandit self-serve location.** Should pandit self-serve live in `apps/partner` (alongside temple/vendor) or a dedicated pandit app? *Recommendation: apps/partner — it already has the auth infrastructure and the onboarding flow where pandits apply.*
- **PD2 — Search surfacing.** Should search suggest/trending/entities be a partner-facing surface, or admin-only? *Recommendation: partner-facing (vendors/temples search their own data).*
- **PD3 — Mutation UI pattern.** Form posts (server actions) vs fetch-based (client components)? *Recommendation: fetch-based with client components, matching the console kit pattern from apps/web.*

---

## 6. Proposed ADRs

- **ADR-0028 — Partner console mutation contract** (BFF mutation routes follow the same envelope/error-mapping as read routes).
- **ADR-0029 — Step-up in partner consoles** (TOTP challenge flow for stepup:2-gated actions).
