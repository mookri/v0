# Navdhya Jyotish Engine — Implementation Plan (130 Tools, Automations, Rewards, Optimisation)

**Status:** Draft — awaiting approval
**Version:** 2.2.0 · **Date:** 2026-11-13
**Revision note (2.2.0):** comprehensive structural expansion of §6 into an exhaustive Phase, Subphase, and Task implementation register across all 9 phases (P0–P8, 38 subphases, 130 tools, 26 automations, 16 reward tasks, 24 optimizations), embedding concrete automated test matrices across 6 layers (Unit, Invariant, Boundary, Golden, Feature, E2E), decidable `AC-*` criteria, and strict Quality Gates per subphase. Retains prior verification notes on defects **D-12…D-16**, prerequisite tasks **T-JYAUT-00a…00e** and **JY-OPT-00**, and ground-truth corrections (PHP recommender is real; B2B SaaS billing distinction; dormant commerce contracts). Net effect: **two of Part II's three headline recommendations carry blocking prerequisites.**
**Structure:** *Part I* (§0–16) — the Jyotish calculation platform for JY-001…050 and the 24 product families. *Part II* (§17–22) — catalogue expansion to JY-130, the automation layer, the reward layer, and the smart-optimisation layer.
**Supersedes:** the "Recommended calculation stack (Rust)" section of the incoming engineering specification
**Execution Units:** `platform-navdhya` (Laravel 12 / PHP ^8.2), `apps/web` (3000), `apps/customer` (3001), `apps/partner` (3002), `apps/dashboard` (3003), `packages/{contracts,validation,geography,date-time,i18n,design-tokens,design-system,accessibility}`
**Conventions:** task IDs and AC shapes follow [`platform-navdhya/docs/specs/README.md`](../platform-navdhya/docs/specs/README.md) (§1 hierarchy, §2 template, §3 AC format, §4 DoD, §5 granularity). Release gates follow [`19-testing/testing-strategy.md`](../platform-navdhya/docs/19-testing/testing-strategy.md) (GATE-0…GATE-5).

---

## 0. Hard constraints (read first)

The source specification recommends Rust for the calculation kernel and Swiss Ephemeris as a C dependency. **This plan does not adopt either.** The governing constraint for this repository is: *ship inside the frameworks already present; introduce no new language and no new runtime.*

### 0.1 Stack translation

| Spec recommends | Navdhya ships | Basis in current repo |
| --- | --- | --- |
| Rust orchestration | **PHP 8.2 `final readonly` domain classes** under `App\Domain\Jyotish\*` | 60-file precedent `app/Domain/Calendar` (4,435 LOC), `deptrac` layer `Calendar: [Support]` |
| C ephemeris behind FFI | **Pure-PHP Meeus/ELP series behind an `EphemerisProvider` port** | `Astronomy/{SolarLongitude,LunarLongitude}.php` already pure PHP; `ComputesPanchang` port precedent |
| `#[derive(Clone,Copy)] struct [GrahaPosition; 9]` | **Fixed-shape array-of-floats snapshot + `readonly` profile VO** | `Profiles/CalculationProfile.php` `cacheKey()` pattern |
| IEEE-754 `f64` | **PHP `float` is IEEE-754 double** — exact match, no translation loss | `LunarLongitude::calculate(float $t): float` |
| Redis hot cache | **`Cache` store + `calendar_computation_log` precedent**, Redis only where already configured | `input_hash`/`cache_hit` columns exist |
| PostgreSQL JSONB | **`$table->json()` + `'cast' => 'array'`** (JSONB is never used in this repo) | ~25 existing sites |
| Object storage for PDFs | **Existing kernel file/storage service** | `07-kernel/kernel-services.md` §files |
| Next.js/TS UI | **Unchanged — Next 16.4.2, React 19, App Router** | 4 apps, enforced by `tests/architecture/app-boundaries.test.ts:44-56` |

**Laravel Octane** (which would restore the warm-process cache model the spec assumes) is *not* currently a dependency. It is permitted only via [ADR-JY-04](#12-adr-schedule) and no task in this plan depends on it.

### 0.2 Non-negotiable engineering rules

1. Compute each astronomical quantity **once** per calculation and reuse it via a shared snapshot.
2. Keep **astronomical mathematics** (`Domain/Jyotish/Astronomy`) separate from **traditional Jyotish rules** (`Domain/Jyotish/Rules`).
3. Never hardcode interpretation into a planetary calculation.
4. Never silently change ayanamsa, house system, ephemeris model or convention — every one is a versioned field of the calculation profile.
5. Every tool ships with measurable, CI-decidable acceptance criteria.
6. A passing software test does **not** establish that an astrological prediction is true. Astronomical output is validated against independent astronomical references; traditional rules are validated against named texts, published rule sets and practitioner review, and are labelled accordingly.

### 0.3 What is explicitly out of scope

| Excluded | Reason | Owner if later approved |
| --- | --- | --- |
| Rust/Go/C services, PHP FFI to Swiss Ephemeris | New language/runtime; AGPL-vs-Professional licensing gate unresolved | Own ADR + legal sign-off |
| LLM-computed planetary positions | Non-deterministic; violates rule 3 | Never |
| Disaster / earthquake / weather / public-safety forecasting (family T) | Must not substitute for scientific warning systems | Never |
| Fear-based Dosha messaging, auto-triggered gemstone upsell | Trust-sensitive; see §11.3 | Never |
| Claiming authentic lineage Nadi readings from generic text matching | Requires explicit source + human practitioner | Family S, gated |

---

## 1. Current-state inventory

### 1.1 Assets to reuse (do not rebuild)

| Asset | Path | Reused for |
| --- | --- | --- |
| Astronomical kernel (15 files) | `app/Domain/Calendar/Astronomy/` — `Ayanamsa`, `DeltaT`, `Elongation`, `EquationOfTime`, `JulianDayCalculator`, `SiderealTime`, `SolarLongitude`, `LunarLongitude`, `RootSolver`, `EventBoundaryResolver`, `SunriseUtcCalculator`, `SunsetUtcCalculator`, `MoonriseUtcCalculator`, `HorizonResult`, `HorizonStatusEnum` | JY-002, JY-004, JY-007, JY-021–026 |
| Panchang limb calculators (16 files) | `app/Domain/Calendar/Panchang/` — `Tithi`, `Vara`, `Nakshatra`, `Yoga`, `Karana`, `MoonPhase`, `Sankranti`, `AdhikaMasa`, `ChandraBala`, `TaraBala`, `PanchangCalculator` | JY-007, JY-021, JY-022, JY-028 |
| Muhurat primitives | `app/Domain/Calendar/Muhurat/` — `DayDivision`, `ChoghadiyaCalculator`, `HoraCalculator`, `AbhijitMuhurat`, `IntervalAlgebra`, `ActivityRuleInterpreter` | JY-026, JY-027, JY-030 |
| Versioned rule registry precedent | `Festivals/FestivalRuleRegistry.php` (`scripture` provenance + `rule_summary` + `getRule()`) | **The** template for every Jyotish ruleset |
| Generation lifecycle | `Profiles/GenerationManager.php` — `building → validated → active → retired`, atomic pointer swap inside `DB::connection('central')->transaction()` | Chart/report backfill |
| Profile value object | `Profiles/CalculationProfile.php` — `profile_code`, `ephemeris_model`, `ayanamsa_code`, `rules_version`, `cacheKey()` | JY-004, §4.1 |
| Batch build/validate/activate/replay/verify CLI | `calendar:{build,validate,activate,replay,verify-oracle}` | §5 backfill programme |
| Immutable result tables | `database/migrations/2026_11_13_000001_create_calendar_kernel_tables.php` — microsecond `bigint` instants, composite uniqueness | §4 table shapes |
| External gold-vector test precedent | `tests/Unit/Calendar/ExternalGoldValidationTest.php` (`EXTERNAL_GOLD_VECTORS`, `provenance: {authority, edition, is_self_generated: false}`) | §9.2 golden corpus |
| Non-destructive rule-versioning precedent | ADR-0035 + `2026_11_11_000010_mark_legacy_ritual_reminders_rules_v1.php` | §5 backfill |
| Error envelope / idempotency / outbox / flags | `Platform/Api/ErrorEnvelope.php`, `Booking/Domain/CommandDeduplicator.php`, `Platform/Outbox/*`, `Platform/Features/FeatureFlags.php` | §5 API surface |
| Orphaned Jyotish UI (4 files) | `apps/web/components/panchang/{DailyPanchangCard,MonthlyPanchangGrid,ChoghadiyaTable,MuhuratFinder}.tsx` — already typed against a real backend envelope, **zero importers** | §10.2 wiring |
| Brand tokens | `packages/design-tokens` — `SACRED_EMBER_PALETTE`, `SACRED_EMBER_SEMANTIC`, `contrastRatio()`, `assertValidSacredEmberPairing()`, `containsDisallowedGreen()` | §10.1 |
| Jyotish vocabulary already mirrored FE↔BE | `packages/validation` — `CANONICAL_NAKSHATRAS` (27), `CANONICAL_RASHIS` (12), `CANONICAL_GOTRAS` (20), mirrors `Modules/Pandit/Contracts/GotraList.php::CANONICAL` | §10, T-JY00-14 |
| Muhurat formatting | `packages/date-time` — `formatMuhuratWindow(start,end,tz)`, IST-aware `formatIst*` | §10.3 |

### 1.2 Defects and gaps that must be closed first

| # | Finding (verified) | Impact | Closed by |
| --- | --- | --- | --- |
| **D-1** | `CalendarBuildCommand::handle()` writes `sunset_at_us = sunrise + 12h` and `solar_noon_us = sunrise + 6h` as constants, although `SunsetUtcCalculator` exists. Every stored sunset/solar-noon row in the active generation is fake. | Every derived window (Rahu Kaal, Hora, Choghadiya, Abhijit, all Muhurat) is wrong away from the equinox | T-JY00-04 + §5 backfill B-1 |
| **D-2** | No planetary ephemeris. Only Sun and Moon longitudes exist; `grep` for Mercury/Venus/Jupiter/Saturn/Mars in `app/Domain` matches only `Muhurat/{Choghadiya,Hora}Calculator` (weekday-name tables, not positions). | JY-003 and therefore 40 of 50 tools have no substrate | Phase P1 |
| **D-3** | `LunarLongitude` is a truncated ELP-2000 series documented at "~0.05 degree precision" (≈3′). Evidence `G-ASTRO-1` tolerances are ±0.25° lunar, ±0.05° solar, ±120 s sunrise. | Fails the spec's 1 arc-second Lagna agreement target by ~3 orders of magnitude; nakshatra/pada boundaries (3°20′ apart) can shift by up to a full pada | T-JY00-05, ADR-JY-02 |
| **D-4** | **`app/Modules/Calendar/` does not exist.** The Calendar domain has no HTTP surface; the only panchang consumers are `CustomerSpiritualController` and `ProcessRitualRemindersCommand`. | Nothing callable from any Next.js app | T-JY00-07 |
| **D-5** | `apps/web/app/resources/tools/client.tsx` links to `/resources/tools/{panchang,horoscope,muhurat,kundli-matching}` — **these routes do not exist**. `/astrology/*` pages are `LayoutOrchestrator` SEO shells with no computation. | Live dead links on a public surface | Phase P2 |
| **D-6** | `apps/web/styles/globals.css:13,20,25,34` sets `--primary`/`--ring`/`--accent-foreground`/`--sidebar-primary` to `oklch(0.245 0.048 158.4)` — **a green** — plus `--color-sacred-gold` (`:118`) and `.ambient-glow-emerald` (`:186`). This directly violates "No green anywhere in the core identity" (`AGENTS.md`), and `packages/design-tokens` (whose `containsDisallowedGreen()` would catch it) has **only its own test file as an importer**. | Jyotish UI built on this stylesheet inherits an off-brand palette | T-JY00-08 |
| **D-7** | `packages/geography` `GeocodingProvider` is an unimplemented port and `CANONICAL_REGIONS` (11 cities) carries **no latitude/longitude**. `PlaceResolver::CANONICAL_HUBS` is a hardcoded const array of a handful of pilgrimage cities. | JY-001 cannot resolve arbitrary birthplaces; spec targets 300 geographies and 150 historical-timezone cases | Phase P1 |
| **D-8** | No house/Lagna computation exists. `SiderealTime.php` is present but unused for ascendant solving. | JY-009, JY-010, JY-011 have no implementation | Phase P1 |
| **D-9** | `app/Domain/Calendar` has **no service provider of its own** — its console commands are registered from `app/Modules/SeoContent/ModuleServiceProvider.php:19`. | Convention debt; must not be copied | T-JY00-02 |
| **D-10** | `recharts@2.15.4` is declared at root and in `apps/web` but has **zero imports repo-wide**; `packages/design-system` exports only 13 primitives and its `package.json` `exports` map has a single `"."` entry. | No charting home for dashas, Ashtakavarga, timelines | Phase P2/P4 |
| **D-11** | `lighthouserc.json` asserts a11y ≥ 0.95 and script ≤ 300 KB but is referenced by **no workflow** (`lhci` appears nowhere). `apps/{customer,partner,dashboard}` vitest configs use `environment: 'node'` → **no component tests possible there**. | SVG-heavy chart work lands where nothing guards perf or a11y | T-JY00-09 |
| **D-12** | **The reminder engine's idempotency key cannot fit its own column.** `customer_ritual_reminders.last_fired_period` is `string('last_fired_period', 32)` — the migration comment even states the intended format is `"2026-CH-K-11"` (13 chars) — but `ProcessRitualRemindersCommand` writes `sprintf('%s:%s-%s-%s-%02d:%dd', $reminder->id, …)`, where `id` is a **26-char ULID**. Minimum realistic key ≈ 52–56 chars (`id` is `$table->ulid('id')->primary()` = 26, `resolvedMasa()` returns a full masa name, `paksha` is `Shukla`/`Krishna`) — **the column was sized for a key format the code no longer produces.** It only works today because SQLite ignores `VARCHAR(n)`; on MySQL it silently truncates (→ duplicate notifications) and on strict MySQL/PostgreSQL it throws (→ the reminder never fires). | Every "advance days" devotee notification is at risk of duplicating or silently failing the moment the platform leaves SQLite | T-JYAUT-00a (**before** JY-AUT-01) |
| **D-13** | **Ritual reminder anchors are computed at fixed Ujjain coordinates for every devotee.** `AnchorOccurrenceResolver` hardcodes `23.1765, 75.7885` at lines 44–45 and 69–70, with `MAX_LOOKAHEAD_DAYS = 400` and a hard `if ($candidateDate->year > 2030) throw`. | Anchors that depend on local sunrise/masa (per ADR-0035) are wrong for anyone not in Ujjain; and the whole reminder engine stops at 2030 | T-JYAUT-00b, T-JYAUT-00c |
| **D-14** | **`rules_version` is cosmetic in the reminder path.** `(int) ($rule['rules_version'] ?? config('calendar.rules_version', 2))` is used *only* inside the cache key; there is no behavioural branch anywhere in the command. ADR-0035 §2 promises a "v1 compatibility layer" that does not exist in this code path. | A legacy tropical-referenced v1 anchor and a v2 sidereal anchor are evaluated identically — exactly the silent shift ADR-0035 was written to prevent | T-JYAUT-00d |
| **D-15** | **The compute-audit hook is dead code.** `ComputationAuditor::log()` has exactly one caller in the repository — `tests/Unit/Calendar/ComputationAuditTest.php:22`. Nothing in production writes `calendar_computation_log`, and nothing reads it. | Every performance claim and all of §20's ratchets would start from an **empty table**; there is currently zero measurement baseline | T-JYOPT-00 (activate before P7) |
| **D-16** | **The outbox has no dead-letter handling.** `app/Platform/Outbox/` contains only `Outbox`, `OutboxEvent`, `OutboxHandlers`; `app/Platform/Console/` only `CleanupExpiredCommand`, `DoctorCommand`, `OutboxDrainCommand`. `MAX_ATTEMPTS = 5`, backoff is "wait for the next `everyMinute` pass", and rows reaching 5 attempts are reported `exhausted` but **left pending forever** — no DLQ table, no replay command, no per-consumer cursor (so one poison event re-runs every consumer for that row). | Once reward accrual (§19) becomes an outbox consumer, a failed grant is **silently and permanently lost**, and a user-visible balance is the worst place to discover it | T-JYAUT-00e (**prerequisite for §19**) |

---

## 2. Target architecture

### 2.1 Layer map (deptrac, additive)

```
App\Support                     (stateless: Money today, Angle/Math next)
  ↑
App\Domain\Jyotish\**           ← NEW pure domain.  deptrac: Jyotish: [Support]
  Astronomy/   Ephemeris/        (no HTTP, no Eloquent in Astronomy)
  Chart/       Dasha/  Transit/
  Strength/    Ashtakavarga/
  Yoga/        Dosha/  Matching/
  Jaimini/     Kp/  Prashna/  Tajika/  LalKitab/
  Rules/       Registry/  Contracts/  Profiles/  Support/
  ↑
App\Modules\Jyotish\**          ← NEW module: HTTP, Models, Routes, Jobs, openapi.yaml
App\Modules\Calendar\**         ← NEW module: exposes the existing Calendar domain
App\Platform\**                 (kernel: authz, tenancy, outbox, flags, audit — unchanged)
```

Required `deptrac.yaml` edits: add `Jyotish` classLike collector `^App\\Domain\\Jyotish\\.*` with rules `Jyotish: [Support]`, and append `Jyotish` to the consumer lists of `ModuleCore`, `ModuleHttp`, `ModuleRoot`, `AppShell` (alongside the existing `Calendar, Delivery, Privacy, Verification, Trust`). Gate: `vendor/bin/deptrac analyse --no-cache` in GATE-0.

### 2.2 Shared calculation dependency graph (the core optimisation)

One snapshot powers every tool. A tool **MUST NOT** recompute an upstream node.

```
NormalizedBirthEvent  ──▶  AstronomicalTime (JD_UTC, JD_UT1, JD_TT)
        ──▶  EphemerisSnapshot[9 grahas] (λ, β, r, λ̇, β̇, ṙ at one instant)
                ──▶  SiderealLayer (ayanamsa → λ_sid, Rashi, Nakshatra, Pada)
                        ──▶  ChartGeometry (Lagna, 12 cusps, Chalit, Vargas)
                                ├─▶ Panchang & transition solver
                                ├─▶ Dasha / Transit
                                ├─▶ Strength / Ashtakavarga
                                ├─▶ Yoga / Dosha / Matching
                                └─▶ Specialist (Jaimini, KP, Prashna, Tajika)
                                        └─▶ Result Envelope → JSON / SVG / PDF
```

**Enforcement (not convention):** `tests/Architecture/JyotishSnapshotTest.php` scans `app/Domain/Jyotish/**` and fails if any class outside `Astronomy/` and `Ephemeris/` calls `EphemerisSnapshot::fromBirth()`, `SolarLongitude::calculate()` or `LunarLongitude::calculate()` directly. This is the machine-checkable form of rule 1.

### 2.3 Calculation result envelope (single shape for all ~250 tools)

Every response is the existing kernel `ErrorEnvelope` for failures, and this for success — extending `CalculationProfile::toArray()` provenance already used by Calendar:

```json
{
  "calculation_id": "calc_01JXYZ",
  "tool_id": "jyotish.ashtakavarga.sarva",
  "status": "completed",
  "data": { },
  "methodology": { "system": "parashari", "ayanamsa": "lahiri", "house_system": "sripati",
                   "node_mode": "mean", "ruleset": "parashari-in", "rules_version": 2 },
  "engine": { "profile_code": "drik-lahiri-purnimanta", "ephemeris_model": "meeus",
              "ephemeris_version": "v3", "generation": 17 },
  "quality": { "verification_status": "practitioner_reviewed", "warnings": [], "unsupported": [] },
  "created_at": "2026-11-13T06:00:00Z"
}
```

`verification_status` ∈ `unverified | golden_tested | practitioner_reviewed | disputed` — the field that stops "HTTP 200 means production-ready".

### 2.4 Jyotish Tool Registry

One registry, three consumers (customer app = basic, partner app = professional, dashboard = governance). Modelled on `FestivalRuleRegistry`:

```php
// app/Domain/Jyotish/Registry/JyotishToolRegistry.php
public const TOOLS = [
  'jyotish.chart.d1' => [
    'display_name' => 'Complete D1 Kundli',
    'category' => 'chart', 'methodology' => 'parashari',
    'spec_id' => 'JY-005', 'release' => 'R0', 'priority' => 'P0',
    'input_schema' => 'birth-event-v1', 'output_schema' => 'chart-d1-v1',
    'permission' => 'jyotish:calculation:execute',
    'execution' => ['mode' => 'synchronous', 'cacheable' => true],
    'verification_status' => 'pending',
  ], // …
];
public static function getTool(string $id): ?array;
```

Registry rows are **code constants + DB-cached**, not free-form config, so `deptrac`, `PHPStan` and golden tests can all reference them. Adding tool 51 = adding a ruleset + a registry row + a UI tile. Part II grows this registry from 50 to **130 rows** using the identical six-file recipe in §17.12, with a single data-driven test walking every row so coverage cannot rot as the catalogue scales.

---

## 3. API surface

Mount via the module registry (never edit `routes/api.php`, which declares zero endpoints and is asserted by `tests/Architecture/ModuleShapeTest.php`).

| Module | basePath | Namespace |
| --- | --- | --- |
| `jyotish` | `/api/v1/jyotish` | charts, vargas, grahas, bhavas, strength, ashtakavarga, yogas, doshas, dashas, transits, compatibility, jaimini, kp, prashna, tajika, lal-kitab, chakras, interpretations, reports |
| `calendar` | `/api/v1/calendar` | panchang, transitions, sunrise/sunset, rahu-kaal, hora, choghadiya, festival-calendar |

Route conventions to follow exactly: colon-verbs for state transitions (`Route::post('reports/{id}:regenerate', …)`), fail-closed `['auth.any','tenant']` at mount, per-route `scope:`/`capability:`/`throttle.tier`, inline `$request->validate([...])` (this repo has **no FormRequests in modules**), hand-written presenters (this repo has **no API Resources**), `Idempotency-Key` + `CommandDeduplicator` for every POST that persists.

Async: `202 Accepted` + status URL for Muhurat searches >30 days, batch generators and PDFs. Durable work uses the **transactional outbox** (`Outbox::append()` inside the tenant transaction, drained by `OutboxDrainCommand`) — this repo has no `Bus::batch` precedent, so batch fan-out is built on outbox + a `jyotish_batch_items` table, not on batches.

---

## 4. Data model

All new tables declare their connection explicitly (`Schema::connection('central')` / `('tenant_shared')`) — `TenantIntegrationTestCase::migrateDatabases` requires it. Instants are `bigint` microseconds (`*_at_us`), matching `calendar_panchang_days`. Never `json`-pack a value another table indexes.

### 4.1 `central` — calculation reference data (immutable, generation-managed)

| Table | Key columns | Purpose |
| --- | --- | --- |
| `jyotish_profiles` | `profile_code` uq(64), `ephemeris_model`, `ephemeris_version`, `ayanamsa_code`, `ayanamsa_version`, `house_system`, `node_mode`, `masa_system`, `day_anchor`, `rules_version`, `is_default` | Superset of `calendar_profiles`; the only permitted way to vary a convention |
| `jyotish_places` | `region_id` pk(26), `latitude` dec(10,7), `longitude` dec(10,7), `elevation_m`, `iana_timezone`, `tzdb_version`, `is_verified`, `name_en`, `name_dev` | Replaces hardcoded hubs; ≥300 Indian geographies |
| `jyotish_generations` | `profile_code`, `scope_year`, `state`(building/validated/active/retired), `metrics_json` | Reuses `GenerationManager` verbatim |
| `jyotish_ephemeris_snapshots` | `jd_tt`, `jd_ut1`, `profile_code`, `grahas_json`, `generation`, uq(`jd_tt`,`profile_code`,`generation`) | **One row per instant**, never per tool |
| `jyotish_astronomical_events` | `event_type`, `index_in_series`, `starts_at_us`, `ends_at_us`, `bracket_start_us`, `bracket_end_us`, `residual_deg`, `generation` | Transition solver output incl. bracket + residual |
| `jyotish_rulesets` | `ruleset_code`, `rules_version`, `school`, `source_reference`, `payload_json`, `status`, uq(`ruleset_code`,`rules_version`) | Versioned immutable traditional rule tables |
| `jyotish_ruleset_audit` | `ruleset_code`, `rules_version`, `actor_id`, `changed_at`, `diff_json` | "Never silently change a convention" |
| `jyotish_computation_log` | `input_hash`(64) idx, `profile_code`, `execution_ms`, `cache_hit`, `peak_mem_bytes` | Extends the calendar log with memory, for §8 |
| `jyotish_tool_registry` | `tool_id` pk, `spec_id`, `verification_status`, `status`, `entitlement`, `deprecated_at` | Cached mirror of §2.4 for dashboards/entitlements |

### 4.2 `tenant_shared` — devotee & practitioner data

| Table | Key columns | Notes |
| --- | --- | --- |
| `jyotish_birth_profiles` | `id` ulid, `tenant_id`, `owner_user_id`, `display_name`, `birth_date`, `birth_time` nullable, `birth_time_accuracy`(EXACT/APPROX/UNKNOWN), `fold_selection` nullable, `birth_place_text`, `place_id` → `jyotish_places`, `latitude`, `longitude`, `iana_timezone`, `tzdb_version`, `utc_at_us`, `provenance_json`, `consent_id`, `retention_expires_at`, softDeletes | Normalized input kept **beside**, never overwriting, original text (JY-001) |
| `jyotish_charts` | `id` ulid, `tenant_id`, `birth_profile_id`, `profile_code`, `generation`, `lagna_json`, `grahas_json`, `bhavas_json`, `chart_version`, immutable uq(`birth_profile_id`,`profile_code`,`chart_version`) | D1 canonical; JSON + indexed columns |
| `jyotish_varga_charts` | `chart_id`, `varga` (D2…D144), `placements_json`, `ruleset_code`, `rules_version` | Extended divisions labelled separately from core Shodasha |
| `jyotish_dasha_intervals` | `chart_id`, `system`, `parent_interval_id`, `level`(MAHA/ANTAR/PRATYANTAR/SOOKSHMA/PRANA), `lord`, `starts_at_us`, `ends_at_us`, `year_convention` | Sorted intervals for binary search |
| `jyotish_strength_results` | `chart_id`, `kind`(shadbala/bhava/vimsopaka), `components_json`, `totals_json`, `units`, `rules_version` | Components stored, not just totals |
| `jyotish_ashtakavarga` | `chart_id`, `graha`, `rashi_index`, `bindus`, `prastara_json`, `stage`(raw/trikona/ekadhipatya) | Fixed 8×12 rows; `CHECK (bindus BETWEEN 0 AND 8)` |
| `jyotish_findings` | `subject_type`(chart/couple/event), `subject_id`, `kind`(yoga/dosha/muhurat/matching), `finding_code`, `verdict`, `conditions_json`, `cancellation_json`, `strength_json`, `ruleset_code`, `verification_status`, `interpretation_ref` | Detection ≠ strength ≠ interpretation, as separate columns |
| `jyotish_matchings` | `party_a_profile_id`, `party_b_profile_id`, `methodology`(ashtakoota/dashakoota/porutham), `score_total`, `components_json`, `rules_version` | **Different methodologies are different rows; never summed** |
| `jyotish_muhurat_candidates` | `occasion`, `place_id`, `range_start_us`, `range_end_us`, `starts_at_us`, `ends_at_us`, `score`, `reasons_json`, `exclusions_json`, `weights_json` | Transparent ranking |
| `jyotish_reports` | `id` ulid, `subject_id`, `template`, `locale`, `status`, `storage_ref`, `chart_versions_json`, `watermarked` | PDF bytes live in object storage, never in cache |
| `jyotish_practitioner_notes` | `chart_id`, `author_id`, `body`, `annotations_json`, `visibility`(private/client_shared) | Family W |
| `jyotish_interpretations` | `calculation_id`, `knowledge_ref`, `text`, `locale`, `model_ref`, `grounded: bool` | Rows must reference a real `calculation_id` |

### 4.3 Migration files (naming: `YYYY_MM_DD_00000N_<verb phrase>.php`)

| File | Location | Tables |
| --- | --- | --- |
| `…_create_jyotish_reference_tables.php` | `database/migrations/` | §4.1 all |
| `…_create_jyotish_birth_profiles_and_charts.php` | `app/Modules/Jyotish/Database/Migrations/` | birth_profiles, charts, varga_charts |
| `…_create_jyotish_timing_and_strength_tables.php` | same | dasha_intervals, strength_results, ashtakavarga |
| `…_create_jyotish_findings_and_matchings.php` | same | findings, matchings, muhurata |
| `…_create_jyotish_reports_and_practice_tables.php` | same | reports, practitioner_notes, interpretations |
| `…_add_chart_and_rule_fields_to_spiritual_profiles.php` | `app/Modules/Customers/Database/Migrations/` | see §5 |

### 4.4 Rule-table seeding

Rule tables are **code constants versioned in git** (the `FestivalRuleRegistry` precedent), exported to DB tables for admin visibility. Seeding happens inside a `building` generation and is verified before activation. Nothing in `jyotish_rulesets` may be edited in place — supersede by inserting a new `rules_version` and re-pointing (same discipline as `Catalog` bitemporal prices: *"history retained (no updates — supersede by ending effective_to)"*).

---

## 5. Backfill programme

Follows ADR-0035: **stamp a version, never silently mutate.** Each backfill is an idempotent console command with `--dry-run`, `--since`, `--max`, and a written evidence row.

| ID | Target | Source → destination | Invariant that proves it |
| --- | --- | --- | --- |
| **B-1** | `calendar_panchang_days.sunset_at_us`, `solar_noon_us` | Constant `sunrise+12h/6h` → real `SunsetUtcCalculator`/solar-noon solve | Closes **D-1**. Recompute into a **new generation**; `calendar:replay` reports zero divergence on the corrected columns; activate atomically |
| **B-2** | `calendar_astronomical_events` | Re-derive tithi/nakshatra/yoga/karana boundaries at 1 s tolerance under the corrected sunrise anchors | Boundary monotonicity + gapless coverage for all 365/366 days, asserted by `calendar:validate` |
| **B-3** | `jyotish_places` | Seed ≥300 cities: `PlaceResolver::CANONICAL_HUBS` + `packages/geography` `CANONICAL_REGIONS`/`CANONICAL_ALIASES` (+ Devanagari names) → rows with WGS84 lat/long + IANA zone + `tzdb_version` | Closes **D-7**. `latitude BETWEEN -90 AND 90`, `is_verified=true` only for curated rows |
| **B-4** | `customer_spiritual_profiles.nakshatra`/`rashi` | Free-text `string(32)` values → canonical `CANONICAL_NAKSHATRAS`/`CANONICAL_RASHIS` indices via `normalizeCanonicalNakshatra()` | Non-canonical strings are **not** rewritten: they are flagged `needs_review` and surfaced for consent-bearing correction |
| **B-5** | `customer_spiritual_profiles.birth_place` (text) | Text → resolved `place_id` + coordinates in `jyotish_birth_profiles` | Ambiguous names require user confirmation (JY-001 AC); `birth_place` text is retained, never overwritten |
| **B-6** | `customer_spiritual_profiles` + `customer_family_members` → `jyotish_birth_profiles` | Copy-with-lineage (`provenance_json.source_row_id`), preserving `tradition` AMANTA/PURNIMANTA and `retention_expires_at`; consent-gated, special-category data | Row count parity minus opted-out; per-tenant reconciliation report; no cross-tenant reads (`AssertTenantIsolation`) |
| **B-7** | `customer_ritual_reminders.rules_version` | v1 legacy tropical anchors remain v1; re-issued reminders get v2 and an explicit upgrade notice | `StoredAnchorMigrationTest` passes; no reminder date shifts silently |
| **B-8** | `jyotish_rulesets` initial corpus | `FestivalRuleRegistry::PILOT_FESTIVALS` and `GotraList::CANONICAL` → versioned rows with `source_reference` | Every row has non-null `source_reference`; a test asserts `whereNull('source_reference')->count() === 0` |
| **B-9** | Golden corpus load | `tests/Fixtures/jyotish/golden/*.json` → `jyotish_generations`-pinned expectation sets | `is_self_generated: false` on ≥70 % of vectors, per `ExternalGoldValidationTest` provenance shape |
| **B-10** | Retired-generation sweep | After each rules_version bump: recompute affected charts into the new generation, mark old rows `retired`, keep them readable for reproducibility | Any stored `report_id` still renders byte-identical data from its recorded `chart_versions_json` |

**Rollout rule:** B-1 and B-2 execute and validate *before* any Panchang UI is exposed (Phase P2 entry condition). B-3…B-6 run per-tenant in the `building → validated → active` window, so a bad backfill is never activated.

---

## 6. Phases, subphases, tasks (Comprehensive Implementation Register)

Estimate classes: `S ≤ 1d`, `M ≤ 3d`, `L ≤ 5d`. Every task carries ACs in the canonical `GIVEN/WHEN/THEN` or invariant form, each mapped to a named automated check.

### Structure & Execution Methodology
The implementation plan is structured across nine coherent phases (**P0 through P8**) and 38 granular subphases, covering all 130 tools, 26 automations, 16 reward mechanisms, and 24 smart optimizations:
- **Phase P0**: Foundations, Contracts, Architectural Scaffolding & Kernel Correction (P0.1–P0.2, 14 tasks)
- **Phase P1 (R0)**: Birth Normalization, 9-Graha Ephemeris, Chart Geometry & Divisional Charts (P1.1–P1.4, 20 tools, 20 tasks)
- **Phase P2 (R1)**: Panchang, Precise Transitions, Reference Places Backfill & First-Wired UI (P2.1–P2.3, 9 tools, 16 tasks)
- **Phase P3 (R2)**: Electional Muhurat, Dasha Interval Engines, Transits & Booking Integration (P3.1–P3.4, 8 tools, 12 tasks)
- **Phase P4 (R3)**: Strength, Ashtakavarga, Compatibility, Yoga & Dosha Engine (P4.1–P4.5, 11 tools, 15 tasks)
- **Phase P5 (R4)**: Specialist Lineages, Horary & Annual Oracles, Practitioner Workbench & Reports (P5.1–P5.4, 2 tools/systems, 8 tasks)
- **Phase P6 (Breadth)**: Extended Catalogue Breadth, Hindu Calendar, Remedial Knowledge & AI Interpretation (P6.1–P6.4, 10 tasks)
- **Phase P7 (Part II)**: Automation Infrastructure, Loyalty Ledger & Depth Catalogue (P7.1–P7.5, 60 tools, 26 automations, 16 reward mechanisms, 35 tasks)
- **Phase P8 (Part II)**: Smart Optimisation, Content-Addressed Caching, Numerical Solvers & Scale Hardening (P8.1–P8.5, 20 tools, 24 optimizations, 22 tasks)

Every task specifies:
1. **Deliverables & Target File Paths**: The concrete write-set of files to be created or modified.
2. **Scope & Technical Deliverable**: Explicit functional and architectural boundaries.
3. **Automated Test Matrix**: Concrete PHPUnit / Jest / Playwright test classes across 6 test layers (Unit, Invariant, Boundary, Golden, Feature, E2E).
4. **Acceptance Criteria (`AC-*`)**: Decidable, observable, CI-verifiable criteria.
5. **Quality Gate & Exit Criteria**: Strict non-negotiable release requirements matching the platform definition of done.

---

### PHASE P0 — Foundations, Contracts, Architectural Scaffolding & Kernel Correction
**Timeline:** Sprint 1–2 · **Tools:** 0 user-facing tools · **Prerequisite for all downstream phases**
**Primary Objective:** Establish the pure domain architecture in `App\Domain\Jyotish`, module HTTP scaffolds in `App\Modules\{Jyotish,Calendar}`, configure strict Deptrac architectural boundaries, resolve blocking kernel defects (D-1, D-3, D-6, D-9, D-11), and wire the central tool registry.

#### P0.1 Domain Architecture, Deptrac Isolation & Kernel Defect Fixes

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY00-01** | `app/Domain/Jyotish/{Astronomy,Ephemeris,Chart,Rules,Registry,Profiles,Contracts,Support}/`<br>`app/Domain/Jyotish/Contracts/{ComputesChart.php,ComputesEphemeris.php,ResolvesRuleSet.php}` | Domain skeleton & core contracts: stateless pure-PHP domain models without framework dependencies; strict readonly value objects. | M |
| **T-JY00-02** | `app/Modules/Jyotish/{module.json,ModuleServiceProvider.php,Routes/api.php,Http/openapi.yaml,Models/,Console/}`<br>`app/Modules/Calendar/{module.json,ModuleServiceProvider.php,Routes/api.php,Http/openapi.yaml,Models/,Console/}` | Module scaffolding for both domains. **Fixes D-9**: give `Domain/Calendar` its own dedicated provider rather than mounting via `SeoContent`. Fail-closed routes mounted under `/api/v1/jyotish` and `/api/v1/calendar`. | M |
| **T-JY00-03** | `deptrac.yaml`<br>`tests/Architecture/JyotishBoundaryTest.php` | Add `Jyotish` classLike collector `^App\\Domain\\Jyotish\\.*` with rule `Jyotish: [Support]`. Append `Jyotish` to consumer lists of `ModuleCore`, `ModuleHttp`, `ModuleRoot`, `AppShell`. | S |
| **T-JY00-04** | `app/Domain/Calendar/Commands/CalendarBuildCommand.php`<br>`app/Domain/Calendar/Astronomy/SunsetUtcCalculator.php` | **Fixes D-1**: replace the legacy fake `sunrise+12h` and `sunrise+6h` constants with true `SunsetUtcCalculator` iterative solver and real solar noon calculation across all 30 standard geographical reference hubs. | M |
| **T-JY00-05** | `app/Domain/Jyotish/Ephemeris/EphemerisProviderInterface.php`<br>`app/Domain/Jyotish/Ephemeris/MeeusSeriesProvider.php`<br>`app/Domain/Calendar/Astronomy/{SolarLongitude,LunarLongitude}.php` | **Fixes D-3**: upgrade ELP-2000 truncated lunar series and VSOP87 solar terms to full precision (documented term count); introduce `EphemerisProviderInterface` port with `MeeusSeriesProvider` as initial pure-PHP engine. | L |
| **T-JY00-06** | `app/Domain/Jyotish/Ephemeris/EphemerisSnapshot.php`<br>`app/Domain/Jyotish/Profiles/JyotishProfile.php`<br>`app/Domain/Jyotish/Profiles/CalculationProfile.php` | Shared calculation snapshot holding all 9 grahas at one instant `(λ, β, r, λ̇, β̇, ṙ)`; immutable `JyotishProfile` VO extending `CalculationProfile` with layered hashing. | L |
| **T-JY00-07** | `app/Modules/Jyotish/Http/JyotishProblemResponder.php`<br>`app/Modules/Jyotish/Http/Presenters/JyotishResultEnvelope.php` | Standardized calculation result envelope wrapping §2.3; RFC 7807 problem details responder matching `BookingProblemResponder` precedent; fail-closed schema error mapping. | M |
| **T-JY00-08** | `apps/web/styles/globals.css`<br>`packages/design-tokens/src/tokens/palette.ts`<br>`packages/design-tokens/src/__tests__/sacred-ember-lint.test.ts` | **Fixes D-6**: import `SACRED_EMBER_*` tokens into `apps/web/styles/globals.css`, purge all `oklch` green band values (`--primary`, `--ring`, `--accent-foreground`, `--sidebar-primary`), remove `.ambient-glow-emerald`, and wire automated CI linting. | M |
| **T-JY00-09** | `apps/web/jest.config.ts`<br>`tests/architecture/forbidden-imports.test.ts`<br>`e2e/playwright.config.ts`<br>`.github/workflows/ci.yml`<br>`lighthouserc.json` | **Fixes D-11**: register `@navdhya/jyotish` in Jest `moduleNameMapper`; add `jyotish` to `BUSINESS_CAPABILITIES`; register `jyotish-*.spec.ts` in Playwright `testMatch`; integrate `lhci` into the CI test matrix. | M |

**Automated Test Matrix (P0.1)**
- Unit Tests: `tests/Unit/Jyotish/SnapshotSharingTest.php` (verifies one snapshot computes all 9 bodies at identical $t$), `tests/Unit/Calendar/SunsetUtcCalculatorTest.php` (verifies real sunset convergence vs equinox/solstice fixtures).
- Architecture & Boundary Tests: `tests/Architecture/JyotishBoundaryTest.php` (verifies zero Eloquent/HTTP imports in `Domain/Jyotish`), `tests/Architecture/JyotishSnapshotTest.php` (enforces rule 1: no downstream class independently calls `SolarLongitude::calculate()` or `LunarLongitude::calculate()`).
- Token & Palette Tests: `packages/design-tokens/src/__tests__/SacredEmberPaletteTest.ts` (verifies `containsDisallowedGreen() === false` and `assertValidSacredEmberPairing()` passes on all semantic pairings).
- CI Gates: `vendor/bin/deptrac analyse --no-cache`, `npm run check:architecture`, `npx playwright test --list`.

**Acceptance Criteria (P0.1)**
- `AC-T-JY00-01`: `deptrac analyse --no-cache` exits 0 with `Jyotish` restricted to `[Support]`.
- `AC-T-JY00-02`: `php artisan module:list` displays `jyotish` and `calendar` as registered modules; unauthenticated request to `GET /api/v1/jyotish/health` returns `401`/`403` (never `404`), verifying fail-closed mounting.
- `AC-T-JY00-04`: For all 30 backfilled place×date fixtures, stored `sunset_at_us` differs from `sunrise_at_us + 43200e6` and matches `SunsetUtcCalculator` within $\pm 60\text{ s}$.
- `AC-T-JY00-05`: Lunar longitude error vs. pinned JPL Horizons reference $\le 0.003^\circ$ (10.8 arcsec) across 1900–2100 CE; test fails on any undocumented term count change.
- `AC-T-JY00-06`: Downstream domain classes consume `EphemerisSnapshot`; zero direct ephemeris solver calls exist outside `Domain/Jyotish/Astronomy` and `Ephemeris`.
- `AC-T-JY00-07`: Malformed calculation request returns HTTP 422 with `error.code = "validation_failed"`, structured problem detail, and non-null `request_id`.
- `AC-T-JY00-08`: Grep gate: `apps/web/styles/globals.css` contains zero color definitions in the green hue band ($120^\circ$–$170^\circ$); `containsDisallowedGreen()` returns false for full CSS token tree.
- `AC-T-JY00-09`: `npx playwright test --list` discovers all `e2e/jyotish-*.spec.ts` test files.

---

#### P0.2 Tool Registry, Entitlements, Scopes & Audit Infrastructure

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY00-10** | `app/Domain/Jyotish/Registry/JyotishToolRegistry.php`<br>`database/migrations/2026_11_13_000001_create_jyotish_reference_tables.php` | Central tool registry class modeled on `FestivalRuleRegistry`: constants defining all 50 core `JY-0xx` tools, categories, input/output schemas, permission requirements, and `jyotish_tool_registry` cache table. | M |
| **T-JY00-11** | `app/Modules/Jyotish/module.json`<br>`app/Platform/Authz/Permissions/JyotishPermissions.php`<br>`tests/Feature/Jyotish/Authz/AuthorizationMatrixTest.php` | Permission and scope definitions: `jyotish:calculation:execute`, `jyotish:chart:read`, `jyotish:chart:write`, `jyotish:ruleset:manage` (sensitive staff), `jyotish:practitioner:read`; client scopes `jyotish:self`, `jyotish:read`, `jyotish:write`. | M |
| **T-JY00-12** | `app/Platform/Features/FeatureFlags.php`<br>`app/Modules/Jyotish/Http/Middleware/EnsureJyotishFeatureEnabled.php` | Fail-closed feature flag controls: `jyotish.enabled`, `jyotish.consumer_tools`, `jyotish.practitioner_studio`, `jyotish.specialist_schools`, `jyotish.ai_interpretation`. | S |
| **T-JY00-13** | `database/migrations/2026_11_13_000001_create_jyotish_reference_tables.php`<br>`app/Domain/Jyotish/Audit/RulesetAuditWriter.php`<br>`tests/Architecture/AuditCoverageReportTest.php` | Audit hooks for ruleset updates and generation promotions (`jyotish_ruleset_audit`); audit coverage registration for all mutating Jyotish commands. | M |
| **T-JY00-14** | `packages/contracts/src/api/jyotish.ts`<br>`packages/validation/src/constants/jyotish.ts`<br>`app/Domain/Jyotish/Support/GotraList.php`<br>`tests/Architecture/ContractsParityTest.php` | Exported TypeScript DTOs and canonical cross-language vocabularies: `CANONICAL_AYANAMSA`, `CANONICAL_HOUSE_SYSTEMS`, `CANONICAL_VARGAS`, `CANONICAL_DASHA_SYSTEMS`, mirrored verbatim in PHP. | L |

**Automated Test Matrix (P0.2)**
- Unit & Integrity Tests: `tests/Unit/Jyotish/Registry/ToolRegistryIntegrityTest.php` (walks all 50 registry rows, asserting non-empty `spec_id`, `permission`, `ruleset_code`, and `verification_status`).
- Feature & Authz Tests: `tests/Feature/Jyotish/Authz/AuthorizationMatrixTest.php` (verifies access matrix across customer, partner, internal staff, and guest roles; asserts mandatory negative authorization paths).
- Feature Flag Tests: `tests/Feature/Jyotish/FeatureFlagsTest.php` (verifies HTTP 404 and clean UI degradation when `jyotish.enabled=false`).
- Parity & Contract Tests: `tests/Architecture/ContractsParityTest.php` (reads TS and PHP canonical vocabulary files, asserting byte-for-byte enum parity), `pnpm --filter @navdhya/contracts run contract:check`.

**Acceptance Criteria (P0.2)**
- `AC-T-JY00-10`: All 50 registry entries declare valid schema names, category, spec ID, and initial status `verification_status = pending`.
- `AC-T-JY00-11`: A token lacking `jyotish:chart:read` receives `403 Forbidden` with envelope code `forbidden` on `GET /api/v1/jyotish/charts/{id}` (mandatory negative authorization test).
- `AC-T-JY00-12`: Disabling `jyotish.enabled` causes all `/api/v1/jyotish/*` endpoints to return `404 Not Found` and hides navigation items in web/customer apps.
- `AC-T-JY00-13`: `AuditCoverageReportTest` reports 100% audit coverage for `jyotish_rulesets` mutations and `jyotish_generations` status transitions.
- `AC-T-JY00-14`: TypeScript and PHP canonical lists (`AYANAMSA`, `HOUSE_SYSTEMS`, `VARGAS`, `DASHA`) match identically; `contracts:export --check` exits 0.

**P0 Exit Gate (GATE-0):** Deptrac boundary analysis clean (0 violations); all manifests validated; all module health checks green; zero green hue in design tokens; CI test harness operational.

---

### PHASE P1 — R0: Birth Normalization, 9-Graha Ephemeris, Chart Geometry & Divisional Charts
**Timeline:** Sprint 3–5 · **Tools:** JY-001 … JY-020 (20 tools) · **Release Milestone:** R0
**Primary Objective:** Deliver the deterministic astronomical calculation kernel: time scales, 9-graha sidereal ephemeris, Lagna solver, house cusps, dignity/motion/aspect matrices, and Shodasha Varga divisional charts.
**Go/No-Go Gate:** **Astronomical Correctness Gate** (1 arcsec Lagna agreement, $\le 10.8''$ Moon agreement vs JPL Horizons, 0 unexplained regressions).

#### P1.1 Birth Normalization and Time Kernel (JY-001, JY-002)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY01-01** | `app/Domain/Jyotish/Astronomy/BirthProfileNormalizer.php`<br>`app/Domain/Jyotish/Contracts/GeocodingProvider.php`<br>`app/Domain/Jyotish/Geography/PlaceResolver.php` | Normalization engine: geocodes birthplace strings to WGS84 coordinates via `jyotish_places`; resolves historical IANA timezone offsets and DST transitions; handles DST folds and ambiguity; records raw text and accuracy class. | L |
| **T-JY01-02** | `app/Modules/Jyotish/Database/Migrations/2026_11_13_000002_create_jyotish_birth_profiles_and_charts.php`<br>`app/Modules/Jyotish/Models/BirthProfile.php`<br>`app/Modules/Jyotish/Http/Controllers/BirthProfileController.php` | Eloquent persistence and API: `jyotish_birth_profiles` table with `BelongsToTenant`, `HasUlids`, explicit casts, soft deletes, and REST endpoints for creating/querying birth profiles. | M |
| **T-JY01-03** | `app/Domain/Jyotish/Astronomy/Time/AstronomicalTime.php`<br>`app/Domain/Jyotish/Astronomy/Time/{JdUtc,JdUt1,JdTt}.php`<br>`app/Domain/Calendar/Astronomy/DeltaT.php` | Strongly typed astronomical time values: distinct VO wrappers for `JD_UTC`, `JD_UT1`, and `JD_TT`; rigorous $\Delta T = \text{TT} - \text{UT}$ calculation; eliminates implicit time-scale conversions. | M |

**Automated Test Matrix (P1.1)**
- Unit Tests: `tests/Unit/Jyotish/Astronomy/BirthProfileNormalizerTest.php` (tests DST fold resolution, spring-forward invalid times, WGS84 bounding), `tests/Unit/Jyotish/Astronomy/TimeScaleConversionTest.php` (property-based test over 1,000 random civil instants verifying TT vs UTC offset).
- Feature Tests: `tests/Feature/Jyotish/BirthProfileApiTest.php` (CRUD API tests, validation failure codes, ambiguous place returns).
- Tenant Isolation Tests: `tests/TenantIsolation/BirthProfileTenantIsolationTest.php` (verifies tenant A cannot access or overwrite tenant B birth profiles using `AssertTenantIsolation`).

**Acceptance Criteria (P1.1)**
- `AC-T-JY01-01`: Given a birthplace text matching >1 verified place, API returns `422 Unprocessable Entity` with `error.code = "ambiguous_place"` and candidate array.
- `AC-T-JY01-01b`: Given a birth time falling within a DST fold, API returns `422` with `error.code = "ambiguous_local_time"` unless `fold_selection` (0 or 1) is supplied.
- `AC-T-JY01-01c`: Nonexistent local times (spring-forward gap) and latitudes outside $[-90, 90]$ are rejected with descriptive validation errors.
- `AC-T-JY01-02`: Stored `birth_place_text` exactly preserves the submitted string; `utc_at_us` accurately reflects $\text{local} - \text{offset}(\text{location}, \text{local})$.
- `AC-T-JY01-03`: Passing a `JdUtc` instance to a parameter expecting `JdTt` triggers a compile/runtime `TypeError`; $\text{JD}_\text{TT} - \text{JD}_\text{UTC} = (\text{TAI} - \text{UTC} + 32.184) / 86400$ accurate to $< 1\text{ ms}$.

---

#### P1.2 Nine-Graha Ephemeris and Sidereal Layer (JY-003, JY-004, JY-006, JY-007, JY-008)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY02-01** | `app/Domain/Jyotish/Ephemeris/MeeusSeriesProvider.php`<br>`app/Domain/Jyotish/Ephemeris/EphemerisSnapshot.php` | Nine-body planetary solver computing ecliptic longitude $\lambda$, latitude $\beta$, distance $r$, and instantaneous rates $(\dot{\lambda}, \dot{\beta}, \dot{r})$ for Sun, Moon, Mars, Mercury, Jupiter, Venus, Saturn, Rahu, and Ketu at a single instant $t$. | L |
| **T-JY02-02** | `app/Domain/Jyotish/Ephemeris/NodesResolver.php`<br>`tests/Unit/Jyotish/Ephemeris/LunarNodesTest.php` | Lunar nodes engine: implements both Mean Node and True (Osculating) Node modes; enforces rigorous $180^\circ$ opposition constraint $(\lambda_\text{Ketu} = [\lambda_\text{Rahu} + 180^\circ] \bmod 360^\circ)$. | M |
| **T-JY02-03** | `app/Domain/Jyotish/Astronomy/AyanamsaEngine.php`<br>`app/Domain/Calendar/Astronomy/Ayanamsa.php` | Multi-model Ayanamsa engine: Lahiri/Chitrapaksha, Raman, KP, and Fagan–Bradley; returns value, calculation mode, nutation in longitude, and epoch definition. | M |
| **T-JY02-04** | `app/Domain/Jyotish/Astronomy/SiderealLayer.php`<br>`app/Domain/Jyotish/Support/Angle.php` | Unified sidereal conversion pipeline: subtracts versioned Ayanamsa from tropical snapshot; enforces single canonical path (ADR-JY-02). | M |
| **T-JY02-05** | `app/Domain/Jyotish/Chart/BoundaryIndexer.php`<br>`app/Domain/Jyotish/Contracts/ZodiacConstants.php` | Boundary indexing service: maps sidereal longitudes into 12 Rashis ($30^\circ$), 27 Nakshatras ($13^\circ 20'$), and 108 Padas ($3^\circ 20'$); handles boundary wraps and syllable mappings. | S |

**Automated Test Matrix (P1.2)**
- Invariant & Property Tests: `tests/Unit/Jyotish/Invariants/EphemerisInvariantTest.php` (verifies $0^\circ \le \lambda < 360^\circ$, $|\dot{\lambda}_\text{central} - \dot{\lambda}_\text{analytic}| < \epsilon$, and $|(\lambda_\text{Rahu} - \lambda_\text{Ketu}) - 180^\circ| < 10^{-9}$ across 500 dates).
- Golden Vector Tests: `tests/Unit/Jyotish/Golden/JplHorizonsEphemerisGoldenTest.php` (compares pure-PHP Meeus positions against 1,000 pinned JPL Horizons ephemeris vectors; maximum error bounds: Sun $< 0.001^\circ$, Moon $< 0.003^\circ$, planets $< 0.005^\circ$).
- Boundary Tests: `tests/Unit/Jyotish/Boundaries/ZodiacBoundaryTest.php` (verifies exact transitions at $0^\circ$, $29^\circ 59' 59''$, $30^\circ$, nakshatra borders, and pada borders).

**Acceptance Criteria (P1.2)**
- `AC-T-JY02-01`: All 9 bodies in a snapshot share the exact same `jd_tt`; requesting calculations at different instants produces independent snapshot records.
- `AC-T-JY02-01b`: Ecliptic longitude rate of change $\dot{\lambda}$ matches numerical central finite difference $(\pm 30\text{ s})$ in sign and magnitude ($< 1\%$ delta) for all 9 bodies.
- `AC-T-JY02-02`: Invariant holds: $|\text{angleDiff}(\lambda_\text{Rahu}, \lambda_\text{Ketu}) - 180^\circ| \le 10^{-9}$ across all dates and node modes.
- `AC-T-JY02-03`: Lahiri, Raman, and KP Ayanamsa implementations agree with independent references to within $0.1$ arcsec; switching `ayanamsa_code` invalidates downstream cached charts.
- `AC-T-JY02-05`: Boundary indexing correctly maps $0^\circ \to$ Mesha Pada 1, $30^\circ \to$ Vrishabha Pada 1, $360^\circ \to$ Mesha Pada 1; unknown birth time returns date-bounded Nakshatra distribution with explicit warnings.

---

#### P1.3 Chart Geometry, Ascendant and House Systems (JY-005, JY-009, JY-010, JY-011)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY03-01** | `app/Domain/Jyotish/Astronomy/AscendantSolver.php`<br>`app/Domain/Calendar/Astronomy/SiderealTime.php`<br>`app/Domain/Calendar/Astronomy/HorizonResult.php` | Ascendant (Lagna) solver: calculates Local Sidereal Time (LST), true obliquity of the ecliptic, and solves the horizon intersection $\sin\phi\sin\delta + \cos\phi\cos\delta\cos H = 0$; handles polar horizon cases via `HorizonStatusEnum`. | L |
| **T-JY03-02** | `app/Domain/Jyotish/Chart/HouseSystem/HouseCuspCalculator.php`<br>`app/Domain/Jyotish/Chart/HouseSystem/{WholeSign,Sripati,Placidus}System.php` | House cusp engines behind `HouseSystemInterface`: implements Whole Sign (Rashi = Bhava), Sripati (Porphyry-variant), and Placidus; returns 12 cusps in cyclic order. | L |
| **T-JY03-03** | `app/Domain/Jyotish/Chart/BhavaChalitGenerator.php`<br>`app/Domain/Jyotish/Chart/Models/BhavaChalitChart.php` | Bhava Chalit projection: calculates house midpoint and sandhi (cusp boundaries); maps planets into Bhavas based on midpoint intervals without mutating D1 rashi longitudes. | M |
| **T-JY03-04** | `app/Domain/Jyotish/Chart/D1ChartGenerator.php`<br>`app/Modules/Jyotish/Models/Chart.php`<br>`app/Modules/Jyotish/Http/Controllers/ChartController.php` | Canonical D1 Kundli aggregate: combines Lagna, 9 graha placements, 12 bhava cusps, and Chalit placements; persists immutable row to `jyotish_charts`. | M |

**Automated Test Matrix (P1.3)**
- Unit & Numerical Tests: `tests/Unit/Jyotish/Astronomy/AscendantSolverTest.php` (verifies continuous Lagna movement with respect to time; tests northern, southern, and equatorial latitudes).
- House Boundary Tests: `tests/Unit/Jyotish/Chart/HouseCuspPrecisionTest.php` (tests Whole Sign, Sripati, and Placidus across 50 international birth locations).
- Model Invariant Tests: `tests/Unit/Jyotish/Chart/BhavaChalitTest.php` (verifies every planet is assigned to exactly one Rashi and exactly one Bhava; asserts Chalit assignment never mutates D1 celestial longitudes).
- Feature & API Tests: `tests/Feature/Jyotish/ChartGenerationTest.php` (executes POST `/api/v1/jyotish/charts/d1` and asserts complete result envelope).

**Acceptance Criteria (P1.3)**
- `AC-T-JY03-01`: Calculated Lagna agrees with pinned high-precision reference within **1 arcsecond** ($0.000277^\circ$) for non-polar latitudes ($|\phi| < 66^\circ$).
- `AC-T-JY03-01b`: Latitude $|\phi| \ge 66^\circ$ where ecliptic does not intersect horizon returns `HorizonStatusEnum::UNSUPPORTED_POLAR` with explicit envelope warning.
- `AC-T-JY03-02`: Whole Sign, Sripati, and Placidus house cusps are mathematically distinct and independently golden-tested; cusp cusps are strictly monotonic.
- `AC-T-JY03-03`: Invariant: each planet belongs to exactly one Bhava $[1, 12]$ and one Rashi $[0, 11]$; no planetary position is duplicated or omitted.
- `AC-T-JY03-04`: Generated D1 JSON payload and SVG renderer consume the identical `jyotish_charts` database row.

---

#### P1.4 Dignity, Motion, Aspects and Divisional Vargas (JY-012…JY-020)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY04-01** | `app/Domain/Jyotish/Rules/DignityEngine.php`<br>`app/Domain/Jyotish/Rules/AvasthaEvaluator.php`<br>`app/Domain/Jyotish/Rules/Datasets/ParashariDignities.php` | Dignity and Avastha evaluator: calculates Uchcha (exaltation), Neecha (debilitation), Moolatrikona, Swakshetra (own sign), Mitra/Shatru/Sama (friend/enemy/neutral); evaluates Baladi, Jagradadi, and Deeptadi avasthas. | L |
| **T-JY04-02** | `app/Domain/Jyotish/Astronomy/PlanetaryMotionClassifier.php`<br>`app/Domain/Calendar/Astronomy/RootSolver.php` | Motion analyzer: classifies direct, retrograde (Vakri), and stationary (Sthira) states using angular rate $\dot{\lambda}$ and root-solving for station moments with temporal brackets. | M |
| **T-JY04-03** | `app/Domain/Jyotish/Rules/CombustionCalculator.php`<br>`app/Domain/Jyotish/Rules/PlanetaryWarResolver.php` | Combustion (Asta) and Graha Yuddha engine: calculates planet-specific combustion orbs (direct vs retrograde); detects Graha Yuddha ($< 1^\circ$ separation of true planets) and declares winner by latitude/brightness rules. | M |
| **T-JY04-04** | `app/Domain/Jyotish/Rules/AspectMatrixCalculator.php`<br>`app/Domain/Jyotish/Rules/GrahaDrishtiEvaluator.php` | Aspect evaluator: builds $9 \times 12$ Graha Drishti matrix (Parashari special aspects for Mars [4th/8th], Jupiter [5th/9th], Saturn [3rd/10th]); segregates Jaimini Rashi Drishti into a separate ruleset. | M |
| **T-JY04-05** | `app/Domain/Jyotish/Rules/ConjunctionAnalyzer.php`<br>`tests/Unit/Jyotish/Rules/ConjunctionsTest.php` | Graha Yuti analyzer: identifies sign-based, degree-based, and orb-bounded conjunctions; handles wraparound at the $360^\circ / 0^\circ$ border. | S |
| **T-JY04-06** | `app/Domain/Jyotish/Chart/DispositorGraphResolver.php`<br>`app/Domain/Jyotish/Rules/FunctionalLordshipResolver.php` | Dispositor graph and functional nature: builds planet-to-sign-lord directed graph; detects Parivartana Yogas (mutual receptions); evaluates functional benefic/malefic status per Lagna. | M |
| **T-JY04-07** | `app/Domain/Jyotish/Chart/Varga/VargaCalculator.php`<br>`app/Domain/Jyotish/Chart/Varga/VargaHarmonicEngine.php` | Shodasha Varga engine: implements exact mathematical division algorithms for all 16 classical divisional charts (D2 Hora, D3 Drekkana, D4 Chaturthamsha, D7 Saptamsha, D9 Navamsha, D10 Dashamsha, D12, D16, D20, D24, D27, D30, D40, D45, D60). | L |
| **T-JY04-08** | `app/Domain/Jyotish/Chart/Varga/NavamshaAnalyzer.php`<br>`app/Domain/Jyotish/Chart/Varga/DashamshaAnalyzer.php` | D9 and D10 deep analyzers: handles odd/even sign starting rules; detects Vargottama placements (identical sign in D1 and D9); persists vargas to `jyotish_varga_charts`. | M |

**Automated Test Matrix (P1.4)**
- Unit Tests: `tests/Unit/Jyotish/Rules/PlanetaryDignityTest.php` (tests all 9 grahas across 12 signs against BPHS Chapter 3 rules), `tests/Unit/Jyotish/Astronomy/StationaryMotionTest.php` (verifies station detection within 60 seconds of zero velocity).
- Matrix Tests: `tests/Unit/Jyotish/Rules/AspectMatrixCalculatorTest.php` (tests full $9 \times 12$ aspect table for all 12 ascendants).
- Golden Vector Tests: `tests/Unit/Jyotish/Golden/D9NavamshaGoldenTest.php` (validates all 108 Rashi×Pada combinations for D9 Navamsha against classical tables), `tests/Unit/Jyotish/Chart/VargaAllDivisionsTest.php` (evaluates D2 through D60 for 50 golden charts).

**Acceptance Criteria (P1.4)**
- `AC-T-JY04-01`: Every dignity classification includes `ruleset_code` and `rules_version`; mixing different avastha rulesets raises an explicit domain exception.
- `AC-T-JY04-02`: Planetary station events occur within $\le 60\text{ s}$ of the true ephemeris velocity sign inversion; stationary search on monotonic orbits yields zero false positives.
- `AC-T-JY04-03`: Combustion evaluated using planet-specific threshold orbs; Graha Yuddha correctly flags participants, calculates ecliptic latitude separation, and outputs winning planet.
- `AC-T-JY04-04`: Graha Drishti and Rashi Drishti evaluation outputs never share a ruleset ID; all 7 classical planetary aspects verified across 12 houses.
- `AC-T-JY04-07`: All 16 Shodasha Varga charts compute deterministically from the single natal snapshot; division boundaries adhere strictly to classical odd/even rules.
- `AC-T-JY04-08`: Vargottama status correctly identified for planets occupying the same zodiac sign in both D1 and D9.

**P1 Exit Gate (Astronomical Correctness Gate & GATE-1):**
- Pinned astronomical oracle test `calendar:verify-oracle --report=docs/23-evidence/G-JYOTISH-R0-validation-report.md` passes with 0 failures.
- Lagna agreement $\le 1''$; Moon longitude agreement $\le 10.8''$; Sun agreement $\le 3.6''$.
- All 20 tools (JY-001…JY-020) registered with `verification_status >= golden_tested`.
- PHPStan Level 6 clean (0 new baseline entries); Deptrac clean; Tenant isolation verified.

---

### PHASE P2 — R1: Panchang, Precise Transitions, Reference Places Backfill & First-Wired UI
**Timeline:** Sprint 6–8 · **Tools:** JY-021 … JY-029 (9 tools) · **Release Milestone:** R1
**Primary Objective:** Deliver the five-limb Panchang engine, precision transition solver for astronomical events, topocentric rise/set calculations, backfill reference tables and historical data (B-1, B-2, B-3), launch `@navdhya/jyotish`, and wire the orphaned frontend tools.
**Go/No-Go Gate:** **Timing Precision Gate** ($\le 1\text{ s}$ transition convergence, gapless 366-day coverage, zero dead links in customer navigation).

#### P2.1 Five-Limb Panchang & Transition Solver (JY-021…JY-029 backend)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY05-01** | `app/Domain/Jyotish/Panchang/PanchangOrchestrator.php`<br>`app/Domain/Calendar/Panchang/{Tithi,Vara,Nakshatra,Yoga,Karana}.php` | Integrated five-limb Panchang calculator consuming the shared Sun/Moon ephemeris snapshot; eliminates separate calculation paths; produces start/end timestamps and elapsed percentages for each limb. | L |
| **T-JY05-02** | `app/Domain/Jyotish/Astronomy/TransitionSolver.php`<br>`app/Domain/Calendar/Astronomy/EventBoundaryResolver.php`<br>`database/migrations/2026_11_13_000001_create_jyotish_reference_tables.php` | Precision astronomical event boundary solver: uses coarse bracket search followed by Brent/bisection root solving down to 1-second tolerance; persists brackets and residuals to `jyotish_astronomical_events`. | L |
| **T-JY05-03** | `app/Domain/Jyotish/Astronomy/TopocentricRiseSetCalculator.php`<br>`app/Domain/Calendar/Astronomy/{SunriseUtcCalculator,SunsetUtcCalculator,MoonriseUtcCalculator}.php` | Topocentric solar and lunar horizon engine: accounts for observer elevation, atmospheric refraction ($34'$ at horizon), solar semidiameter ($16'$), and lunar horizontal parallax; explicitly handles polar day/night. | M |
| **T-JY05-04** | `app/Domain/Jyotish/Panchang/DayDivisionCalculator.php`<br>`app/Domain/Calendar/Muhurat/{ChoghadiyaCalculator,HoraCalculator}.php` | Dynamic day/night division engine: recalculates Rahu Kaal, Yamaganda, Gulika, Hora, and Choghadiya as true octiles/divisions of actual computed daylight and night hours (resolving defect D-1 downstream). | M |
| **T-JY05-05** | `app/Domain/Jyotish/Panchang/ChandraTaraBalaEvaluator.php`<br>`app/Domain/Calendar/Panchang/{ChandraBala,TaraBala}.php` | Auspicious strength evaluators: Chandra Bala (Moon's house from natal Moon) and Tara Bala (nakshatra distance modulo 9 cycle with 27-to-1 wraparound); configurable regional beneficence weighting. | S |
| **T-JY05-06** | `app/Domain/Jyotish/Panchang/Festivals/VedicFestivalResolver.php`<br>`app/Domain/Calendar/Festivals/FestivalRuleRegistry.php` | Vedic festival and Vrat determination engine: determines observance dates for Amanta and Purnimanta lunar months; evaluates precise Paran timing windows for Ekadashi based on tithi and sunrise transitions. | L |
| **T-JY05-07** | `app/Modules/Calendar/Http/Controllers/PanchangController.php`<br>`app/Modules/Calendar/Http/Controllers/FestivalCalendarController.php`<br>`app/Modules/Calendar/Routes/api.php` | Calendar module HTTP endpoints: `GET /api/v1/calendar/panchang`, `GET /api/v1/calendar/panchang/monthly`, `GET /api/v1/calendar/transitions`, `GET /api/v1/calendar/festival-calendar`; profile-keyed HTTP caching. | M |

**Automated Test Matrix (P2.1)**
- Unit Tests: `tests/Unit/Jyotish/Panchang/FiveLimbPanchangTest.php` (verifies all 30 tithis, 27 nakshatras, 27 yogas, 60 karanas, and 7 varas map accurately without missing degrees), `tests/Unit/Jyotish/Astronomy/TransitionSolverPrecisionTest.php` (verifies root solver reaches $|\text{residual}| \le 10^{-6}\text{ rad}$ and temporal delta $\le 1\text{ s}$ vs analytical equations).
- Solar/Lunar Horizon Tests: `tests/Unit/Jyotish/Astronomy/TopocentricRiseSetTest.php` (verifies topocentric parallax correction shifts moonrise by up to $1^\circ$; verifies polar day/night edge cases return explicit status).
- Feature & API Tests: `tests/Feature/Calendar/PanchangApiEndpointsTest.php` (tests authenticated calendar queries, monthly grid retrieval, and idempotent cache responses).

**Acceptance Criteria (P2.1)**
- `AC-T-JY05-01`: Five-limb Panchang is evaluated with individual start/end times per limb; a single static midday timestamp is never returned as valid for the entire civil day.
- `AC-T-JY05-02`: Every stored astronomical transition row contains bracket bounds and $|\text{residual}| \le 10^{-6}\text{ rad}$; querying `direction=both` returns both previous and subsequent transitions.
- `AC-T-JY05-03`: Sunrise, sunset, moonrise, and moonset include atmospheric refraction and topocentric parallax; polar latitudes return non-null status enums instead of invalid time numbers.
- `AC-T-JY05-04`: Rahu Kaal and Choghadiya segments are calculated as exact eighths of computed daylight $(\text{sunset} - \text{sunrise})/8$; asserted to differ from fixed 90-minute blocks away from the equinox.
- `AC-T-JY05-07`: `GET /api/v1/calendar/panchang` returns HTTP 200 with result envelope; repeat request within cache TTL returns byte-identical payload from cache with `cache_hit=true`.

---

#### P2.2 Database Schemas, Reference Places & Data Backfill Programme

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY05-08** | `database/migrations/2026_11_13_000001_create_jyotish_reference_tables.php`<br>`database/seeders/JyotishPlacesSeeder.php`<br>`tests/Feature/Calendar/PlaceCatalogueIntegrityTest.php` | **Backfill B-3 & Closes D-7**: create `jyotish_places` schema; seed $\ge 300$ verified Indian and international hubs with exact WGS84 coordinates, elevation, IANA timezones, and Devanagari names. | M |
| **T-JY05-09** | `app/Domain/Calendar/Commands/RecalculatePanchangDayDivisionsCommand.php`<br>`tests/Feature/Calendar/PanchangBackfillValidationTest.php` | **Backfill B-1 & Closes D-1**: recompute all stored `calendar_panchang_days` rows (`sunset_at_us` and `solar_noon_us`) using `SunsetUtcCalculator` across all active generations; verify zero divergence with atomic generation swap. | L |
| **T-JY05-10** | `app/Domain/Calendar/Commands/BackfillAstronomicalEventsCommand.php`<br>`tests/Feature/Calendar/AstronomicalEventsContinuityTest.php` | **Backfill B-2**: re-derive tithi, nakshatra, yoga, and karana boundaries with 1-second precision under corrected sunrise anchors for the entire supported calendar epoch. | L |

**Automated Test Matrix (P2.2)**
- Database Integrity Tests: `tests/Feature/Calendar/PlaceCatalogueIntegrityTest.php` (verifies $\ge 300$ rows in `jyotish_places`, $-90 \le \text{latitude} \le 90$, valid IANA timezone identifiers, and verified flags).
- Backfill Validation Tests: `tests/Feature/Calendar/PanchangBackfillValidationTest.php` (executes `calendar:replay` comparing legacy vs recalculated generations; asserts zero divergence on valid columns and correct delta on corrected sunset/noon columns).
- Event Continuity Tests: `tests/Feature/Calendar/AstronomicalEventsContinuityTest.php` (verifies gapless, monotonic coverage across 365/366 days for all four lunar limbs).

**Acceptance Criteria (P2.2)**
- `AC-T-JY05-08`: `jyotish_places` contains $\ge 300$ curated rows with verified WGS84 coordinates and verified IANA timezone identifiers; `is_verified=true` only for curated records.
- `AC-T-JY05-09`: Backfill B-1 completes with atomic pointer swap in `GenerationManager`; `calendar:replay` asserts zero regression outside corrected sunset/noon columns.
- `AC-T-JY05-10`: Backfill B-2 proves gapless, monotonic event boundaries across all 365/366 days of the calendar year without discontinuities.

---

#### P2.3 Frontend Client Integration, Design-System Charts & Orphan Wiring

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY06-01** | `packages/jyotish/{package.json,tsconfig.json,src/index.ts}`<br>`packages/jyotish/src/domain/{panchang,chart,dasha,matching}.ts`<br>`packages/jyotish/src/ports/` | Shared TypeScript package `@navdhya/jyotish`: domain models, contract ports, schema validators, and canonical re-exports; registered in monorepo boundaries. | M |
| **T-JY06-02** | `apps/web/app/api/panchang/route.ts`<br>`apps/web/app/api/panchang/monthly/route.ts`<br>`apps/web/adapters/backend/panchang-adapter.ts` | Next.js App Router BFF proxy routes: communicates with Laravel backend via authenticated client; provides `laravelEnabled()` mock fallback for resilience. | M |
| **T-JY06-03** | `apps/web/app/resources/tools/panchang/page.tsx`<br>`apps/web/app/resources/tools/muhurat/page.tsx`<br>`apps/web/components/panchang/{DailyPanchangCard,MonthlyPanchangGrid,ChoghadiyaTable,MuhuratFinder}.tsx` | **Fixes D-5 & wires orphaned components**: mount existing 4 orphaned components onto real public routes `/resources/tools/panchang` and `/resources/tools/muhurat`; fix dead links in `resources/tools/client.tsx`. | M |
| **T-JY06-04** | `packages/design-system/src/charts/{NorthIndianChart,SouthIndianChart,EastIndianChart,RashiWheel}.tsx`<br>`packages/design-system/src/index.ts` | Accessible Sacred Ember chart canvas library: pure inline SVG renderers for North Indian diamond, South Indian square, East Indian, and circular Rashi wheel charts; WCAG 2.1 AA contrast; keyboard navigation. | L |
| **T-JY06-05** | `apps/web/app/resources/tools/kundli/page.tsx`<br>`apps/web/components/kundli/BirthDetailCaptureForm.tsx`<br>`apps/web/components/kundli/AmbiguousPlaceDialog.tsx` | Devotee Kundli tool: interactive birth detail capture form reusing `@navdhya/validation` canonicals; client-side ambiguity resolution dialog and DST fold selector (JY-001 UX). | L |
| **T-JY06-06** | `apps/customer/app/(protected)/profile/spiritual/page.tsx`<br>`apps/customer/presentation/profile/SpiritualProfileForm.tsx` | Devotee spiritual profile integration: reads and writes `jyotish_birth_profiles`; displays engine-derived Nakshatra and Rashi alongside legacy values with transparent discrepancy alerts. | M |

**Automated Test Matrix (P2.3)**
- Component Unit Tests: `apps/web/components/panchang/__tests__/DailyPanchangCard.test.tsx`, `apps/web/components/panchang/__tests__/MonthlyPanchangGrid.test.tsx`, `packages/design-system/src/charts/__tests__/NorthIndianChart.test.tsx` (tests SVG rendering, role attributes, and aria-labels).
- Accessibility Tests: `packages/design-system/src/charts/__tests__/ChartAccessibility.test.tsx` (runs `@axe-core` assertion, verifying 0 WCAG AA violations; asserts minimum $44\text{px}$ touch targets).
- E2E Integration Tests: `e2e/jyotish-panchang-workflow.spec.ts` (navigates to `/resources/tools/panchang`, switches date/place, asserts live limbs render without error), `e2e/jyotish-dead-links.spec.ts` (verifies all `/resources/tools/*` routes return HTTP 200).

**Acceptance Criteria (P2.3)**
- `AC-T-JY06-01`: `@navdhya/jyotish` builds cleanly in Turborepo; exports complete TypeScript type definitions matching backend envelopes.
- `AC-T-JY06-03`: All 4 previously orphaned panchang components render real data on `/resources/tools/panchang` and `/resources/tools/muhurat` with zero console errors.
- `AC-T-JY06-04`: Every chart SVG contains `role="img"`, accessible name, meets `isWcagAaTextContrast()` against container fill, and provides text alternatives for all glyphs.
- `AC-T-JY06-05`: Submitting ambiguous birthplaces triggers the disambiguation dialog; nonexistent times surface inline validation errors without submitting.
- `AC-T-JY06-06`: Devotee spiritual profile displays calculated Nakshatra/Rashi derived dynamically from birth data, flagging legacy mismatches for review.
- `AC-P2`: All previously dead routes in `/resources/tools/client.tsx` resolve with HTTP 200.

**P2 Exit Gate (Timing Precision Gate & GATE-2):**
- Backfills B-1, B-2, and B-3 successfully executed and verified with `calendar:replay` (divergence = 0).
- GATE-2 integration test suite passes; token-flow E2E tests green; Lighthouse a11y score $\ge 0.95$ on all new public tool pages.

---

### PHASE P3 — R2: Electional Muhurat, Dasha Interval Engines, Transits & Booking Integration
**Timeline:** Sprint 9–11 · **Tools:** JY-030 … JY-037 (8 tools) · **Release Milestone:** R2
**Primary Objective:** Deliver the high-performance interval-algebra Muhurat search engine, Vimshottari and multi-system Dasha calculation engines, planetary transit/Gochar solvers, Saturn Sade Sati tracking, and the marketplace puja booking seam.
**Go/No-Go Gate:** **Temporal and Rule Validation Gate** ($O(\text{events})$ computational scaling, 120-year Dasha sequence conservation, contracts green).

#### P3.1 Interval Algebra Muhurat Search & Transparent Scoring (JY-030…JY-032)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY07-01** | `app/Domain/Jyotish/Muhurat/IntervalSearchEngine.php`<br>`app/Domain/Calendar/Muhurat/IntervalAlgebra.php`<br>`database/migrations/2026_11_13_000003_create_jyotish_timing_and_strength_tables.php` | Generic electional Muhurat search engine: uses interval set intersection and subtraction over precomputed astronomical transitions; avoids per-second polling; stores candidates in `jyotish_muhurat_candidates`. | L |
| **T-JY07-02** | `app/Domain/Jyotish/Muhurat/Scoring/MuhuratScorer.php`<br>`app/Domain/Jyotish/Muhurat/Models/MuhuratCandidate.php` | Transparent scoring framework: evaluates $\text{Score}(c) = \sum w_i f_i(c)$; returns individual contributing weights, positive factors, and disqualifications in result payload; enforces hard exclusions. | M |
| **T-JY07-03** | `app/Domain/Jyotish/Muhurat/Rules/OccasionRuleRegistry.php`<br>`app/Domain/Jyotish/Muhurat/Rules/Occasions/{Vivah,GrihaPravesh,Namkaran,BusinessOpening}.php` | Specific occasion ruleset registry: encodes traditional astrological constraints for Vivah, Griha Pravesh, Bhoomi Pujan, Namkaran, Annaprashan, Mundan, Upanayana, and Business Openings. | L |

**Automated Test Matrix (P3.1)**
- Complexity & Scalability Tests: `tests/Unit/Jyotish/Muhurat/IntervalSearchPerformanceTest.php` (verifies 90-day search executes in $O(\text{events})$ ephemeris evaluations rather than $O(\text{seconds} \times \text{bodies})$).
- Rule Invariant Tests: `tests/Unit/Jyotish/Muhurat/OccasionRulesValidationTest.php` (verifies hard disqualifications—such as Rikta Tithi or Rahu Kaal—completely eliminate candidates regardless of positive point weightings).
- Feature Tests: `tests/Feature/Jyotish/MuhuratSearchApiTest.php` (executes POST `/api/v1/jyotish/muhurat/search` for various occasions and verifies structure of reasons and exclusions).

**Acceptance Criteria (P3.1)**
- `AC-T-JY07-01`: A 90-day electional Muhurat search completes within the performance budget ($\le 4	ext{ s}$ uncached, $\le 50	ext{ ms}$ cached) and performs strictly $O(	ext{events})$ boundary checks.
- `AC-T-JY07-02`: Every candidate Muhurat window includes `reasons[]` and `exclusions[]`; any candidate violating a hard scriptural exclusion is strictly excluded.
- `AC-T-JY07-03`: Independent rulesets exist for Vivah, Griha Pravesh, and business openings; each ruleset references verified classical scriptures in `source_reference`.

---

#### P3.2 Vimshottari & Multi-System Dasha Interval Engines (JY-033…JY-035)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY07-04** | `app/Domain/Jyotish/Dasha/VimshottariCalculator.php`<br>`app/Domain/Jyotish/Dasha/Models/DashaInterval.php`<br>`database/migrations/2026_11_13_000003_create_jyotish_timing_and_strength_tables.php` | 120-year Vimshottari Dasha engine: computes elapsed Moon balance $D = Y_\text{lord} \times (1 - p)$ down to millisecond precision; generates 5-level hierarchy (Maha, Antar, Pratyantar, Sookshma, Prana); records explicit `year_convention` (365.25 d); persists to `jyotish_dasha_intervals`. | L |
| **T-JY07-05** | `app/Domain/Jyotish/Dasha/Systems/{Ashtottari,Yogini,Chara,Kalachakra}Calculator.php`<br>`app/Domain/Jyotish/Dasha/Contracts/DashaSystemInterface.php` | Non-Vimshottari timing systems: implements Ashtottari (108 y), Yogini (36 y), Chara (Jaimini rashi dasha), and Kalachakra; each system maintains independent sequence, duration, and anchor parameters. | L |
| **T-JY07-06** | `app/Domain/Jyotish/Dasha/Timeline/DashaTimelineIndex.php`<br>`app/Domain/Jyotish/Dasha/Services/DashaLookupService.php` | Continuous timeline service: stores sorted non-overlapping intervals in database; provides $O(\log N)$ binary search lookup for active periods at any target timestamp $t$. | M |

**Automated Test Matrix (P3.2)**
- Arithmetic & Precision Tests: `tests/Unit/Jyotish/Dasha/VimshottariBalancePrecisionTest.php` (verifies initial dasha balance is accurate to 1 second; verifies total duration across 9 Mahadashas equals exactly 120 solar years).
- Multi-System Disjointness Tests: `tests/Unit/Jyotish/Dasha/AlternativeDashaSystemsTest.php` (asserts sequence and duration arrays for Chara, Yogini, and Ashtottari are completely distinct from Vimshottari).
- Boundary & Invariant Tests: `tests/Unit/Jyotish/Invariants/DashaNoGapNoOverlapInvariantTest.php` (property-based test across 10,000 random query dates verifying exactly one active period exists at every level without gaps or overlaps).

**Acceptance Criteria (P3.2)**
- `AC-T-JY07-04`: Vimshottari balance matches $Y_\text{lord} \times (1 - p)$ to the millisecond; the sum of all 9 Mahadashas closes the 120-year period exactly.
- `AC-T-JY07-05`: Alternative dasha systems implement `DashaSystemInterface`; Chara Dasha cannot borrow Vimshottari sequences (enforced by automated architecture test).
- `AC-T-JY07-06`: Continuous timeline indexing ensures exactly one active interval per level at any instant; boundary timestamps consistently resolve to the starting interval.

---

#### P3.3 Planetary Gochar, Sade Sati & Dhaiya Tracking (JY-036, JY-037)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY07-07** | `app/Domain/Jyotish/Transit/GocharCalculator.php`<br>`app/Domain/Jyotish/Transit/Models/TransitAspect.php` | Gochar transit engine: computes planetary ingress into signs relative to natal Moon, natal Lagna, or arbitrary reference Rashi; calculates exact entry, station, and exit timestamps. | M |
| **T-JY07-08** | `app/Domain/Jyotish/Transit/SadeSatiDetector.php`<br>`app/Domain/Jyotish/Transit/Models/SadeSatiPhase.php` | Saturn Sade Sati and Dhaiya analyzer: derives exact 12th, 1st, and 2nd house transits from true Saturn ephemeris longitudes (never lookup tables); handles retrograde re-entries into previous signs as distinct phases. | M |

**Automated Test Matrix (P3.3)**
- Ingress Boundary Tests: `tests/Unit/Jyotish/Transit/GocharIngressSolverTest.php` (verifies solver identifies planetary sign transitions to within 1 second of actual ephemeris crossing).
- Sade Sati Continuity Tests: `tests/Unit/Jyotish/Transit/SadeSatiContinuousTrackingTest.php` (tests retrograde station re-crossings across sign borders, verifying phase intervals are accurately split into disjoint segments).

**Acceptance Criteria (P3.3)**
- `AC-T-JY07-07`: Planetary Gochar ingress moments agree with true ephemeris positions; retrogrades causing temporary return to prior signs generate discrete sub-intervals.
- `AC-T-JY07-08`: Sade Sati and Dhaiya phases are computed from continuous Saturn longitudes; phases split cleanly when retrograde motion causes sign re-entry.

---

#### P3.4 Devotee Muhurat Studio, Dasha Timelines & Booking Seam

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY07-09** | `apps/web/app/resources/tools/muhurat/finder/page.tsx`<br>`apps/web/components/tools/MuhuratResultView.tsx` | Interactive Muhurat search interface: date-range selector, occasion picker, location selector, ranked result cards, transparent factor breakdown, and empty-result explanation. | L |
| **T-JY07-10** | `packages/design-system/src/charts/DashaTimeline.tsx`<br>`apps/web/app/resources/tools/kundli/dasha/page.tsx` | Dasha timeline visualizer: multi-level drill-down timeline built with accessible Recharts horizontal bars; color-coded lords, keyboard navigation, and text-based date labels. | L |
| **T-JY07-11** | `apps/web/app/resources/tools/transit/page.tsx`<br>`apps/web/components/transit/{GocharWheel,SadeSatiBanner}.tsx` | Transit dashboard: Gochar chart overlaid on natal D1; Sade Sati status banner with entry/exit dates; formatted timestamps via `@navdhya/date-time`. | M |
| **T-JY07-12** | `apps/web/lib/booking-handoff/muhurat-seam.ts`<br>`apps/customer/application/bookings/create-booking-from-muhurat.ts` | Marketplace booking integration seam: "Book Puja in this Muhurat" button constructs draft booking pre-populated with `calculation_id`, occasion, and auspicious time window. | M |

**Automated Test Matrix (P3.4)**
- Frontend Component Tests: `apps/web/components/tools/__tests__/MuhuratFinder.test.tsx`, `packages/design-system/src/charts/__tests__/DashaTimeline.test.tsx` (tests drill-down interactions and aria landmark conformance).
- Booking Handoff Tests: `apps/web/lib/booking-handoff/__tests__/muhurat-seam.test.ts` (verifies query parameters, HMAC integrity, and session draft creation).
- End-to-End Playwright Tests: `e2e/muhurat-to-booking-flow.spec.ts` (executes full user flow: search Vivah muhurat -> view candidate window -> click book -> verify draft booking contains `calculation_id`).

**Acceptance Criteria (P3.4)**
- `AC-T-JY07-09`: Empty Muhurat search results explain specific astrological disqualifying factors rather than displaying a blank state.
- `AC-T-JY07-10`: Dasha timeline visualizer supports full keyboard navigation (left/right/enter to drill down); passes axe-core accessibility checks.
- `AC-T-JY07-12`: Selecting a Muhurat window hands off to `/book/start` with immutable `calculation_id` recorded on the draft booking.

**P3 Exit Gate (Temporal & Rule Validation Gate & GATE-3):**
- Contract tests pass (`pnpm --filter @navdhya/contracts run contract:check`).
- $O(	ext{events})$ performance scaling verified by automated benchmark.
- GATE-3 release gate green; zero unhandled edge cases in dasha or transit queries.

---

### PHASE P4 — R3: Strength, Ashtakavarga, Compatibility, Yoga & Dosha Engine
**Timeline:** Sprint 12–15 · **Tools:** JY-038 … JY-048 (11 tools) · **Release Milestone:** R3
**Primary Objective:** Deliver the complete mathematical strength engine (Shadbala, Bhava Bala, Vimsopaka), Ashtakavarga with staged Shodhana, multi-methodology relationship compatibility (Ashtakoota & Porutham side-by-side), and the declarative Yoga/Dosha predicate engine.
**Go/No-Go Gate:** **Practitioner Validation Gate** (exact component summation, independent non-summed compatibility scores, practitioner sign-off in `jyotish_ruleset_audit`).

#### P4.1 Six-Fold Shadbala & House Bala Matrix (JY-038…JY-040)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY08-01** | `app/Domain/Jyotish/Strength/ShadbalaCalculator.php`<br>`app/Domain/Jyotish/Strength/Components/{Sthana,Dig,Kala,Cheshta,Naisargika,Drik}Bala.php`<br>`database/migrations/2026_11_13_000003_create_jyotish_timing_and_strength_tables.php` | Complete Parashari Shadbala calculation: computes all 6 primary components and their sub-factors (Uchcha, Saptavargaja, Ojhayugma, Kendra, Drekkana, Dina/Ratri, Ayana, Yuddha); outputs values in Virupas and Rupas; persists breakdown to `jyotish_strength_results`. | L |
| **T-JY08-02** | `app/Domain/Jyotish/Strength/BhavaBalaCalculator.php`<br>`app/Domain/Jyotish/Strength/VimsopakaCalculator.php`<br>`app/Domain/Jyotish/Strength/IshtaKashtaCalculator.php` | Extended strength engines: Bhava Bala (Bhuvadhipati, Bhava Dig, Bhava Drishti); Vimsopaka Bala across Shadvarga, Dashavarga, and Shodashavarga schemes with versioned weights; Ishta and Kashta Phala. | L |

**Automated Test Matrix (P4.1)**
- Arithmetic & Summation Tests: `tests/Unit/Jyotish/Strength/ShadbalaComponentSumTest.php` (verifies $\text{Total Shadbala} = \sum_{i=1}^6 \text{Component}_i$ exactly; asserts missing intermediate values emit `not_evaluated` warnings rather than defaulting to 0).
- Golden Vector Tests: `tests/Unit/Jyotish/Golden/ShadbalaClassicalGoldenTest.php` (validates Shadbala and Bhava Bala outputs against standard classical textbook charts from *Graha and Bhava Balas*).

**Acceptance Criteria (P4.1)**
- `AC-T-JY08-01`: Shadbala total matches the exact mathematical sum of its 6 validated components; each component retains its raw Virupa unit.
- `AC-T-JY08-02`: Bhava Bala correctly calculates individual lord, directional, and aspectual contributions; Vimsopaka weights sum to exactly 20 points per scheme.

---

#### P4.2 Ashtakavarga Matrices, SAV & Staged Shodhana (JY-041…JY-043)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY08-03** | `app/Domain/Jyotish/Ashtakavarga/BavCalculator.php`<br>`app/Domain/Jyotish/Ashtakavarga/Models/BavMatrix.php`<br>`database/migrations/2026_11_13_000003_create_jyotish_timing_and_strength_tables.php` | Bhinna Ashtakavarga (BAV): evaluates 7 classical planetary contributor tables (Sun through Saturn); builds $8 \times 12$ matrices; records contributor bitmasks in `prastara_json`; persists rows to `jyotish_ashtakavarga`. | M |
| **T-JY08-04** | `app/Domain/Jyotish/Ashtakavarga/SavCalculator.php`<br>`app/Domain/Jyotish/Ashtakavarga/PrastaraTable.php` | Sarvashtakavarga (SAV): aggregates 7 BAV matrices into 12 sign bindu totals; strict rule: Rahu and Ketu are never silently added; provides full Prastara view. | S |
| **T-JY08-05** | `app/Domain/Jyotish/Ashtakavarga/ShodhanaEngine.php`<br>`app/Domain/Jyotish/Ashtakavarga/Reductions/{TrikonaShodhana,EkadhipatyaShodhana,ShodhyaPinda}.php` | Staged Shodhana reductions: applies Trikona Shodhana (triplicity reduction) followed by Ekadhipatya Shodhana (single lordship reduction); computes final Shodhya Pinda (Graha Pinda + Rashi Pinda); intermediate stages stored and queryable. | L |
| **T-JY08-06** | `app/Domain/Jyotish/Ashtakavarga/KakshyaTransitSolver.php`<br>`tests/Unit/Jyotish/Ashtakavarga/KakshyaTest.php` | Kakshya analysis: divides each Rashi into 8 equal $3^\circ 45'$ Kakshyas; determines transit auspiciousness based on whether the Kakshya lord contributes a bindu in the planet's BAV. | M |

**Automated Test Matrix (P4.2)**
- Matrix Consistency Tests: `tests/Unit/Jyotish/Ashtakavarga/BavCalculationTest.php` (verifies all BAV bindus are bounded $0 \le B \le 8$), `tests/Unit/Jyotish/Ashtakavarga/SavInvariantSumTest.php` (verifies $\sum \text{SAV} = \sum \text{BAV}$ across all signs; asserts SAV total is constant 337 bindus in standard 7-planet system).
- Reduction Pipeline Tests: `tests/Unit/Jyotish/Ashtakavarga/ShodhanaTransformStagesTest.php` (tests intermediate matrices after Trikona reduction and Ekadhipatya reduction against classical worked examples).

**Acceptance Criteria (P4.2)**
- `AC-T-JY08-03`: Each planet's BAV contains exactly 12 sign entries with bindus between 0 and 8; unique composite constraint `(chart_id, graha, rashi_index, stage)` prevents duplicates.
- `AC-T-JY08-04`: SAV sign total strictly equals the sum of its contributing BAV values; total bindus equal 337 in the classical Parashari system.
- `AC-T-JY08-05`: Shodhana reduction stages execute sequentially; intermediate matrices are accessible in the response envelope; Shodhya Pinda calculations reproduce published references.

---

#### P4.3 Multi-Methodology Compatibility & Manglik Evaluation (JY-044…JY-046)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY08-07** | `app/Domain/Jyotish/Matching/AshtakootaMatcher.php`<br>`app/Domain/Jyotish/Matching/Kootas/{Varna,Vashya,Tara,Yoni,GrahaMaitri,Gana,Bhakoot,Nadi}.php` | 36-point Ashtakoota compatibility engine: evaluates all 8 kootas with source rule citations, exceptions, and detailed point allocations; persists to `jyotish_matchings`. | L |
| **T-JY08-08** | `app/Domain/Jyotish/Matching/DashakootaMatcher.php`<br>`app/Domain/Jyotish/Matching/Porutham/{Rajju,Vedha,Mahendra,StreeDeergha}.php` | South Indian 10-Porutham engine: evaluates Dina, Gana, Mahendra, Stree Deergha, Yoni, Rashi, Rashi Adhipati, Vashya, Rajju, and Vedha; Rajju and Vedha doshas evaluated as hard disqualifiers. | L |
| **T-JY08-09** | `app/Domain/Jyotish/Matching/ManglikAnalyzer.php`<br>`app/Domain/Jyotish/Matching/Rules/ManglikCancellationRules.php` | Kuja Dosha (Manglik) analyzer: evaluates Mars in 1st, 2nd, 4th, 7th, 8th, and 12th houses from Lagna, Moon, and Venus independently; applies classical cancellation rules (e.g., Mars in own/exalted sign, mutual aspect of Jupiter). | M |

**Automated Test Matrix (P4.3)**
- Boundary & Arithmetic Tests: `tests/Unit/Jyotish/Matching/AshtakootaScoreBoundsTest.php` (verifies $0 \le \sum \text{Koota}_i \le 36$; asserts each koota score matches its specific ceiling).
- Architecture & Isolation Tests: `tests/Architecture/CompatibilityMethodologyIsolationTest.php` (statically verifies Ashtakoota and Dashakoota result arrays are never added or blended into a single composite number).
- Cancellation Verification Tests: `tests/Unit/Jyotish/Matching/ManglikCancellationRulesTest.php` (tests all 14 classical cancellation exceptions with positive and negative controls).

**Acceptance Criteria (P4.3)**
- `AC-T-JY08-07`: Total Ashtakoota score is mathematically bounded between 0 and 36; each component discloses rule citation, raw score, and maximum possible score.
- `AC-T-JY08-08`: Evaluating a couple under both Ashtakoota and Porutham generates distinct result rows; no code path sums or averages the two methodologies.
- `AC-T-JY08-09`: Manglik evaluation outputs Mars placements and cancellation reasons independently; the payload schema strictly prohibits deterministic relationship outcome predictions.

---

#### P4.4 Declarative Yoga & Dosha Predicate Rule Engine (JY-047, JY-048)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY08-10** | `app/Domain/Jyotish/Rules/YogaDetector.php`<br>`app/Domain/Jyotish/Rules/Yogas/{RajaYoga,DhanaYoga,Mahapurusha,Gajakesari,NeechaBhanga}.php` | Declarative Yoga detection engine: compiles AST predicate logic $(P_1 \land P_2 \land \neg P_3)$; detects classical combinations (Pancha Mahapurusha, Gajakesari, Budhaditya, Neecha Bhanga Raja Yoga, Parivartana, Viparita Raja Yogas); records in `jyotish_findings`. | L |
| **T-JY08-11** | `app/Domain/Jyotish/Rules/DoshaAnalyzer.php`<br>`app/Domain/Jyotish/Rules/Doshas/{KaalSarp,PitraDosha,GuruChandal,GrahanDosha,Gandmool}.php` | Dosha detection and cancellation engine: analyzes Kaal Sarp, Pitra, Shrapit, Guru Chandal, Angarak, and Gandanta; detects mitigating placements; separates detection from severity and cancellation. | L |

**Automated Test Matrix (P4.4)**
- Unit & Rule Tests: `tests/Unit/Jyotish/Rules/YogaPredicateEngineTest.php` (evaluates detection of Gajakesari, Malavya, and Hamsa yogas across verified horoscope configurations).
- Mitigation Tests: `tests/Unit/Jyotish/Rules/DoshaDetectionAndCancellationTest.php` (verifies Kaal Sarp partial vs full variants and verifies cancellation when benefic planets aspect the nodal axis).
- Schema Conformance Tests: `tests/Unit/Jyotish/Rules/FindingsSchemaValidationTest.php` (asserts every finding contains distinct `finding_code`, `verdict`, `conditions_json`, `cancellation_json`, and `verification_status`).

**Acceptance Criteria (P4.4)**
- `AC-T-JY08-10`: Every detected Yoga enumerates all satisfied conditions and ruleset citations; duplicate findings from alternative evaluation paths are deduplicated.
- `AC-T-JY08-11`: Unverified or disputed Dosha findings carry `verification_status = unverified|disputed`; the result envelope strictly bans fatalistic or fear-based language.

---

#### P4.5 Visual Ashtakavarga Studio, Guna Milan & Truth-First Evidence UI

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY08-12** | `packages/design-system/src/charts/{AshtakavargaGrid,BinduHeatmap}.tsx`<br>`apps/web/app/resources/tools/kundli/ashtakavarga/page.tsx` | Ashtakavarga visual studio: interactive BAV/SAV grid explorer, Prastara drill-down, staged Shodhana transform toggle, and token-colored bindu heatmap with non-color numerical alternatives. | L |
| **T-JY08-13** | `apps/web/app/resources/tools/kundli/strength/page.tsx`<br>`packages/design-system/src/components/StrengthBreakdown.tsx` | Planetary strength dashboard: 6-component Shadbala breakdown with individual Virupa units and minimum requirement thresholds; eliminates single-label oversimplifications. | M |
| **T-JY08-14** | `apps/web/app/resources/tools/kundli-matching/page.tsx`<br>`apps/web/components/matching/GunaMilanComparisonView.tsx` | Kundli Matching studio: dual birth profile entry, per-koota visual bars, explanation drawers, and side-by-side Ashtakoota vs Porutham views. | L |
| **T-JY08-15** | `packages/design-system/src/components/EvidencePanel.tsx`<br>`apps/web/components/jyotish/YogaDoshaEvidenceCard.tsx` | Truth-first evidence panel: displays conditions met, conditions failed, scripture citations, and verification status chips; **strictly suppresses remedy CTAs on unverified or disputed findings**. | M |

**Automated Test Matrix (P4.5)**
- Accessibility Tests: `packages/design-system/src/charts/__tests__/AshtakavargaGrid.test.tsx` (tests table header row/column relationships, keyboard navigation, and screen reader announcements).
- UI Component Tests: `apps/web/components/matching/__tests__/GunaMilanComparisonView.test.tsx` (verifies side-by-side display of Ashtakoota and Porutham without summed score).
- Remedial Safety Tests: `apps/web/components/jyotish/__tests__/YogaDoshaEvidenceCard.test.tsx` (verifies zero remedy or commercial CTAs render when `verification_status !== "practitioner_reviewed"`).
- Playwright E2E Tests: `e2e/compatibility-matching.spec.ts` (executes dual birth profile input and verifies complete matching report).

**Acceptance Criteria (P4.5)**
- `AC-T-JY08-12`: Ashtakavarga heat cells provide text-based bindu values alongside visual fills; passes Lighthouse accessibility ($\ge 0.95$).
- `AC-T-JY08-14`: Ashtakoota (out of 36) and Porutham (out of 10) render in distinct visual columns; no blended composite score is visible.
- `AC-T-JY08-15`: Finding cards display verification chips (`practitioner_reviewed`, `disputed`); no remedy purchase button appears for unverified findings.

**P4 Exit Gate (Practitioner Validation Gate & GATE-3):**
- Practitioner review sign-offs recorded in `jyotish_ruleset_audit` for all deployed Yoga, Dosha, and Compatibility rulesets.
- GATE-3 integration and contract test suites green.

---

### PHASE P5 — R4: Specialist Lineages, Horary & Annual Oracles, Practitioner Workbench & Reports
**Timeline:** Sprint 16–19 · **Tools:** JY-049, JY-050 (Specialist systems & Workbench) · **Release Milestone:** R4
**Primary Objective:** Deliver specialized astrological traditions (Jaimini Sutras, KP Placidus Sub-lords, Prashna Tantra, Tajika Varshaphal solar returns, Lal Kitab), sacred Chakra renderers, the Professional Practitioner Studio, and high-fidelity PDF/SVG reporting.
**Go/No-Go Gate:** **Methodology Acceptance Gate** (sub-lord width exactness, solar return root convergence, byte-level report reproducibility, GATE-4 load green).

#### P5.1 Jaimini Astrology & KP Sub-Lord Systems (JY-049a, JY-049b)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY09-01** | `app/Domain/Jyotish/Jaimini/JaiminiEngine.php`<br>`app/Domain/Jyotish/Jaimini/{CharaKarakaCalculator,ArudhaLagnaCalculator,UpapadaCalculator,ArgalaEvaluator}.php` | Complete Jaimini Sutra calculation engine: supports both 7-Karaka and 8-Karaka schemes with explicit reversed-Rahu degree logic; computes Arudha Lagna and Padas with classical exception rules; evaluates Upapada Lagna, Karakamsha, Swamsha, Argala, and Virodha Argala; calculates Jaimini Rashi Drishti. | L |
| **T-JY09-02** | `app/Domain/Jyotish/Kp/KpEngine.php`<br>`app/Domain/Jyotish/Kp/{KpCuspCalculator,SubLordDivider,RulingPlanetsResolver}.php` | Krishnamurti Paddhati (KP) engine: KP Ayanamsa and Placidus cuspal calculations; 9 sub-lord divisions per nakshatra with $\sum \text{width} = 800'$; star, sub, and sub-sub lords for planets and cusps; significators (levels A, B, C, D); horary numbers 1–249. | L |

**Automated Test Matrix (P5.1)**
- Jaimini Karaka Tests: `tests/Unit/Jyotish/Jaimini/CharaKarakaModesTest.php` (tests 7-karaka vs 8-karaka modes on identical charts; asserts Rahu inclusion/exclusion rules and tie-breaking by arcseconds).
- Arudha Exception Tests: `tests/Unit/Jyotish/Jaimini/ArudhaExceptionRulesTest.php` (tests 1st and 7th house exception rules—e.g. lord in 1st yields 10th house arudha).
- KP Mathematical Exactness Tests: `tests/Unit/Jyotish/Kp/KpSubLordExactSumTest.php` (verifies all 9 sub-divisions within each nakshatra sum to exactly 800 arcminutes; asserts sub-lord widths equal $800' \times \text{Years}_i / 120$).
- Architecture Isolation Tests: `tests/Architecture/KpParashariAspectIsolationTest.php` (asserts KP engine never imports Parashari Graha Drishti rules).

**Acceptance Criteria (P5.1)**
- `AC-T-JY09-01`: 7-karaka and 8-karaka modes produce distinct, independently tested outputs; Arudha Lagna exceptions correctly apply classical 10th/4th house adjustments.
- `AC-T-JY09-02`: KP sub-lord divisions sum to exactly 800 arcminutes ($13^\circ 20'$); cusp sub-lords match approved KP horary reference tables; zero cross-methodology contamination.

---

#### P5.2 Horary Prashna, Tajika Varshaphal & Solar Return (JY-049c, JY-049d)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY09-03** | `app/Domain/Jyotish/Prashna/PrashnaEngine.php`<br>`app/Domain/Jyotish/Prashna/{JamakkolResolver,PanchPakshiCalculator}.php`<br>`database/migrations/2026_11_13_000004_create_jyotish_findings_and_matchings.php` | Prashna Tantra horary engine: constructs chart for query timestamp and place; implements Jamakkol Arudam, Ashtamangala, and Pancha Pakshi biorhythms; stores question contexts isolated from birth profiles. | L |
| **T-JY09-04** | `app/Domain/Jyotish/Tajika/VarshaphalEngine.php`<br>`app/Domain/Jyotish/Tajika/{SolarReturnSolver,MunthaCalculator,SahamCalculator,MuddaDashaCalculator}.php` | Tajika Varshaphal annual solar return engine: root-solves for exact instant when tropical solar longitude equals natal solar longitude $(\lambda_\odot(t) = \lambda_{\odot,\text{natal}})$; calculates annual Lagna, Muntha, 50 Sahams, and Mudda/Patyayini Dasha. | L |

**Automated Test Matrix (P5.2)**
- Context Isolation Tests: `tests/Unit/Jyotish/Prashna/QuestionContextIsolationTest.php` (verifies Prashna charts have null `birth_profile_id` and cannot be accessed via devotee birth chart APIs).
- Solar Return Convergence Tests: `tests/Unit/Jyotish/Tajika/SolarReturnPrecisionTest.php` (verifies solar return solver identifies annual return timestamp where $|\lambda_\odot(t) - \lambda_{\odot,\text{natal}}| \le 10^{-6}\text{ rad}$).
- Saham Mathematical Tests: `tests/Unit/Jyotish/Tajika/SahamCalculationsTest.php` (verifies day vs night formulas for Punya Saham, Vidya Saham, and Karma Saham).

**Acceptance Criteria (P5.2)**
- `AC-T-JY09-03`: Prashna query without location or timestamp returns HTTP 422; question records remain strictly segregated from natal charts.
- `AC-T-JY09-04`: Solar return instant accurately reproduces natal solar longitude within angular tolerance; return instant lies strictly within target solar year interval.

---

#### P5.3 Lal Kitab Modules & Sacred Chakra Visualization Library (JY-049e, JY-049f)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY09-05** | `app/Domain/Jyotish/LalKitab/LalKitabEngine.php`<br>`app/Domain/Jyotish/LalKitab/{TevaChartCalculator,KismatGrahaResolver,LalKitabDashaCalculator}.php` | Lal Kitab analysis system: fixed-house convention (Aries permanently in 1st house); Teva (natal, varshphal, blind/sleeping houses); Kismat and Dharmi grahas; 35-year Lal Kitab dasha; traditional Upayas as reference. | L |
| **T-JY09-06** | `app/Domain/Jyotish/Chakra/ChakraRendererService.php`<br>`packages/design-system/src/charts/chakras/{Sarvatobhadra,Sudarshan,Kota,PanchPakshi}Canvas.tsx` | Sacred Vedic Chakra renderer engine: contract-driven rendering for Sarvatobhadra Chakra ($9 \times 9$ grid with 28 nakshatras incl. Abhijit), Sudarshan Chakra (3 concentric rings), Kota Chakra, and Koorma Chakra. | L |

**Automated Test Matrix (P5.3)**
- Methodology Segregation Tests: `tests/Unit/Jyotish/LalKitab/LalKitabMethodologyIsolationTest.php` (verifies Lal Kitab and Parashari results are never merged in a single response payload).
- Chakra Geometry Tests: `tests/Unit/Jyotish/Chakra/SarvatobhadraChakraMatrixTest.php` (validates cell coordinates, corner aksharas, and planetary transit vedhas across 28 nakshatras).
- Visual Snapshot Tests: `tests/Unit/Jyotish/Chakra/ChakraRenderingSnapshotTest.php` (verifies visual SVG render output is byte-identical for identical chart inputs).

**Acceptance Criteria (P5.3)**
- `AC-T-JY09-05`: Lal Kitab operations use fixed Aries-ascendant geometry; request requesting both Lal Kitab and Parashari returns separate envelopes.
- `AC-T-JY09-06`: Sarvatobhadra Chakra accurately places Abhijit nakshatra in northern quadrant; SVG visual snapshots verify deterministic rendering.

---

#### P5.4 Practitioner Studio Workbench, Rectification & Enterprise Reports (JY-050)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY09-07** | `apps/partner/app/jyotish-studio/{page.tsx,workspace/page.tsx}`<br>`apps/partner/components/studio/{MultiChartWorkspace,RectificationTool,PractitionerNotes}.tsx` | Professional Practitioner Studio in `apps/partner`: multi-chart side-by-side workspace, assistive birth-time rectification tool, client timeline event correlation, and private consultation notes. | L |
| **T-JY09-08** | `app/Domain/Jyotish/Reports/ReportGeneratorService.php`<br>`app/Modules/Jyotish/Http/Controllers/ReportController.php`<br>`database/migrations/2026_11_13_000005_create_jyotish_reports_and_practice_tables.php` | Enterprise PDF and SVG report generator: async generation via transactional outbox, watermarked client preview, storage in object store, and recorded `chart_versions_json` ensuring byte-reproducibility. | L |

**Automated Test Matrix (P5.4)**
- Assistive Rectification Tests: `apps/partner/app/jyotish-studio/__tests__/RectificationTool.test.tsx` (verifies candidate rankings against devotee-supplied events; asserts UI displays assistive confidence scores without stating predictions as absolute facts).
- Report Storage Tests: `tests/Feature/Jyotish/ReportGenerationAndStorageTest.php` (verifies async PDF generation job stores output in configured S3/MinIO bucket and stores metadata).
- Byte Reproducibility Tests: `tests/Feature/Jyotish/ReportReproducibilityTest.php` (re-renders report from historical `chart_versions_json` and asserts exact byte equality).
- Load Tests: `tests/Performance/k6-jyotish-practitioner-load.js` (executes 50 rps mixed chart and report workload).

**Acceptance Criteria (P5.4)**
- `AC-T-JY09-07`: Rectification workspace explicitly labels candidate birth times as assistive mathematical fits without making factual claims.
- `AC-T-JY09-08`: Any historical `report_id` re-renders byte-identical data from its recorded generation and chart version (reproducibility prover JY-130).

**P5 Exit Gate (Methodology Acceptance Gate & GATE-4):**
- k6 load tests pass at 50 rps with p95 latency $\le 150	ext{ ms}$ for uncached D1 and $\le 50	ext{ ms}$ for cached queries.
- Peak PHP-FPM worker memory $\le 256	ext{ MB}$; steady state $\le 128	ext{ MB}$.
- GATE-4 load and security release gates green.

---

### PHASE P6 — Extended Catalogue Breadth, Hindu Calendar, Remedial Knowledge & AI Interpretation
**Timeline:** Sprint 20–23 · **Scope:** 24 Product Families breadth expansion & AI Interpretation
**Primary Objective:** Deliver the broad catalogue additions across all 24 product families, complete Pan-Indian Hindu calendar converters, sacred Upaya and mantra references, the grounded 3-service AI interpretation engine, and multilingual localization.
**Go/No-Go Gate:** **Full Catalogue Quality Gate** (zero kernel file edits for new tools, non-hallucinating interpretation proofs, 100% i18n bundle parity).

#### P6.1 Extended Chart Variations, Speeds & Special Lagnas (Families A, C, I, L)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY10-01** | `app/Domain/Jyotish/Chart/SpecialLagnaCalculator.php`<br>`app/Domain/Jyotish/Chart/SpecialLagnas/{BhavaLagna,HoraLagna,GhatiLagna,VarnadaLagna,InduLagna,SriLagna}.php` | Special Lagnas: calculates Bhava Lagna, Hora Lagna, Ghati Lagna, Varnada Lagna, Indu Lagna (wealth), and Sri Lagna from sunrise, birth time, and planetary speeds. | L |
| **T-JY10-02** | `app/Domain/Jyotish/Nakshatra/NakshatraAnalytics.php`<br>`app/Domain/Jyotish/Ephemeris/UpagrahaCalculator.php`<br>`app/Domain/Jyotish/Ephemeris/Upagrahas/{Dhuma,Vyatipata,Parivesha,Indrachapa,Upaketu,Mandi,Gulika}.php` | Nakshatra analytics and Upagrahas: 27-nakshatra diagnostic matrices, lunar velocity profiles, Gandanta boundary alerts; evaluates all 5 mathematical Upagrahas and 5 day-division Upagrahas (Mandi/Gulika). | L |

**Automated Test Matrix (P6.1)**
- Special Lagna Tests: `tests/Unit/Jyotish/Chart/SpecialLagnasCalculationTest.php` (validates Bhava, Hora, and Ghati lagna positions against BPHS Chapter 5 reference horoscopes).
- Upagraha Mathematical Invariants: `tests/Unit/Jyotish/Ephemeris/UpagrahaInvariantsTest.php` (verifies Dhuma $= \lambda_\odot + 133^\circ 20'$, Vyatipata $= 360^\circ - \text{Dhuma}$, Parivesha $= \text{Vyatipata} + 180^\circ$, Indrachapa $= 360^\circ - \text{Parivesha}$, Upaketu $= \text{Indrachapa} + 16^\circ 40'$).

**Acceptance Criteria (P6.1)**
- `AC-T-JY10-01`: Special Lagnas calculate deterministically from sunrise and birth instant without re-evaluating the central ephemeris.
- `AC-T-JY10-02`: Upagraha longitudes satisfy mathematical symmetry formulas; Mandi and Gulika day/night division boundaries accurately reflect local sunrise and sunset.

---

#### P6.2 Hindu Calendar Calculations, Regional Samvats & Extended Muhurats (Families J, K)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY10-03** | `app/Domain/Jyotish/Calendar/SamvatConverter.php`<br>`tests/Unit/Jyotish/Calendar/SamvatEraConversionTest.php` | Pan-Indian era converter: bidirectional conversions between Gregorian calendar and Vikram Samvat (Chaitradi and Kartakadi), Shaka Samvat, Kollam Era, Bengali San, and Nepali Sambat. | M |
| **T-JY10-04** | `app/Domain/Jyotish/Calendar/VedicObservanceEngine.php`<br>`app/Domain/Jyotish/Calendar/Observances/{EkadashiParan,PradoshVrat,SankashtiChaturthi,NavratriKalashtami}.php` | Comprehensive Vrat and festival engine: calculates precise fasting and Paran (break-fast) windows based on tithi fractions and solar sunrise; generates custom devotee PDF almanacs. | L |
| **T-JY10-05** | `app/Domain/Jyotish/Muhurat/Occasions/ExtendedMuhuratFinder.php`<br>`app/Domain/Jyotish/Muhurat/Occasions/{VehiclePurchase,PropertyRegistration,TravelMuhurat,Vidyarambh}.php` | Complete 23-occasion electional finder: vehicle purchase, property registration, travel (Yatra with Disha Shool), thread ceremony (Upanayana), and educational commencement (Vidyarambh). | L |

**Automated Test Matrix (P6.2)**
- Calendar Era Tests: `tests/Unit/Jyotish/Calendar/SamvatEraConversionTest.php` (verifies 200 historical dates across all 6 era systems with zero off-by-one errors).
- Fasting Rule Precision Tests: `tests/Unit/Jyotish/Calendar/EkadashiParanTimingTest.php` (tests Hari Vasara ending moments and evaluates Paran windows that fall strictly prior to Dwadashi expiration).
- Electional Constraints Tests: `tests/Feature/Jyotish/ExtendedMuhuratExecutionTest.php` (verifies property registration constraints reject Tuesday and Saturday windows).

**Acceptance Criteria (P6.2)**
- `AC-T-JY10-03`: Era conversions reproduce published almanac years across ancient, medieval, and modern eras.
- `AC-T-JY10-04`: Ekadashi Paran windows accurately identify cases requiring early morning breakfast before Dwadashi tithi expires.
- `AC-T-JY10-05`: Travel Muhurat strictly enforces Disha Shool (directional prohibitions based on weekdays) and Yogini direction exclusions.

---

#### P6.3 Upaya Remedial References, Sacred Mantras & Adjacent Knowledge (Families V, Z)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY10-06** | `app/Domain/Jyotish/Remedies/RemedyKnowledgeBase.php`<br>`app/Domain/Jyotish/Remedies/{MantraRegistry,PujaVidhiLibrary,GemstoneKnowledgeBase}.php` | Vedic Upaya and remedial reference repository: scriptural mantra texts with audio recitation links, traditional Puja Vidhi procedures, and educational gemstone references. **Non-predictive; zero fear-based upsell; zero automated commercial trigger.** | L |
| **T-JY10-07** | `app/Domain/Jyotish/Adjacent/NumerologyEngine.php`<br>`app/Domain/Jyotish/Adjacent/VastuPurushaEngine.php`<br>`app/Domain/Jyotish/Adjacent/{ChaldeanPythagoreanCalculator,LoShuGrid,VastuMandalaCanvas}.php` | Adjacent Indian knowledge suites: Numerology (Life Path, Destiny, Soul Urge, Chaldean name analysis, Lo Shu $3 \times 3$ grid); Vastu Purusha Mandala generator (16 directional zones, room allocation rules). Strictly segregated namespaces. | L |

**Automated Test Matrix (P6.3)**
- Ethical Guardrail Tests: `tests/Unit/Jyotish/Remedies/RemedyEthicalGuardrailsTest.php` (asserts gemstone payloads contain medical/financial disclaimers and prohibit definitive promises of wealth or health cures).
- Numerology Accuracy Tests: `tests/Unit/Jyotish/Adjacent/NumerologyGridCalculationTest.php` (validates compound numbers and master numbers [11, 22, 33] in Chaldean and Pythagorean models).
- Vastu Direction Tests: `tests/Unit/Jyotish/Adjacent/VastuMandalaOrientationTest.php` (validates magnetic declination corrections and 16-zone angular boundaries).

**Acceptance Criteria (P6.3)**
- `AC-T-JY10-06`: Remedy content is educational and scriptural only; no gemstone or puja purchase is automatically attached to any Dosha calculation.
- `AC-T-JY10-07`: Numerology and Vastu systems reside in their own isolated namespaces and never silently import Jyotish ayanamsa or ephemeris constants.

---

#### P6.4 Three-Service AI Interpretation Layer & Pan-Indian Multilingual System (Families X, S, T)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JY10-08** | `app/Domain/Jyotish/Interpretation/GroundedInterpretationEngine.php`<br>`app/Domain/Jyotish/Interpretation/Services/{CalculationContextExtractor,ScriptureRetrievalService,SafeExplanationGenerator}.php`<br>`database/migrations/2026_11_13_000005_create_jyotish_reports_and_practice_tables.php` | Grounded AI interpretation layer: strict 3-service architecture (Deterministic Calculation Engine $\to$ Knowledge Retrieval $\to$ LLM Explainer); LLM prompts are constructed exclusively from stored `calculation_id` payloads; persists to `jyotish_interpretations` with `grounded=true`. | L |
| **T-JY10-09** | `packages/i18n/src/glossary/jyotish-glossary.ts`<br>`packages/i18n/src/locales/{en,hi,bn,mr,gu,ta,te,kn,ml,pa,or}/jyotish.json`<br>`apps/web/app/[locale]/resources/tools/` | Pan-Indian multilingual platform: controlled Jyotish glossary translating Vedic terminology into 11 regional languages; locale-prefixed routing `/[locale]/*`; automated bundle synchronization via `pnpm i18n:check`. | L |
| **T-JY10-10** | `app/Domain/Jyotish/Research/NadiResearchWorkbench.php`<br>`app/Domain/Jyotish/Research/SamhitaResearchWorkbench.php` | Research workbenches for Nadi lineages and Samhita mundane cycles: practitioner-led analytical workspaces; **strictly excludes disaster forecasting, public safety predictions, or automated fortune-telling**. | M |

**Automated Test Matrix (P6.4)**
- Grounding & Non-Hallucination Tests: `tests/Unit/Jyotish/Interpretation/NonHallucinatingGroundedPromptTest.php` (evaluates LLM responses against calculation inputs; asserts model never hallucinates planetary placements not present in `calculation_id`).
- Multilingual Parity Tests: `packages/i18n/tests/jyotish-glossary-parity.test.ts` (verifies all 120 canonical Jyotish glossary keys exist across all 11 locale json files; checks for untranslated strings).
- Public Safety Guardrail Tests: `tests/Architecture/PublicSafetyPredictionBanTest.php` (asserts Samhita and Mundane APIs cannot output earthquake, weather, or political disaster predictions).

**Acceptance Criteria (P6.4)**
- `AC-T-JY10-08`: Every interpretation record links to a valid `calculation_id` and non-null `knowledge_ref`; automated eval suite proves explanations never contradict numerical calculation data.
- `AC-T-JY10-09`: `pnpm i18n:check` passes across all 11 supported languages; Hindi bundle reaches `PRODUCTION_READY_LOCALES`.
- `AC-T-JY10-ALL`: Adding a new tool to any family requires zero modifications to `Domain/Jyotish/Astronomy/` or `Ephemeris/` (verified by `tests/Architecture/KernelImmutabilityTest.php`).

**P6 Exit Gate (Full Catalogue Quality Gate & GATE-5):**
- Full test suite passes across all 24 product families.
- Multilingual bundles green; zero untranslated tokens in Hindi and regional locales.
- Architecture test confirms adding tool 51 through 130 touched 0 kernel calculation files.

---

### PHASE P7 — Automation Infrastructure, Loyalty Ledger & Depth Catalogue
**Timeline:** Sprint 24–28 · **Tools:** JY-051…108, JY-129/130 (60 tools) · **Automations:** JY-AUT-01…26 · **Rewards:** JY-RWD-01…16
**Primary Objective:** Resolve latent reminder defects (D-12…D-16), establish outbox Dead Letter Queue durability, schedule autonomous generation management, activate devotee/practitioner friction-free automations, deploy the event-sourced loyalty points ledger with TypeScript parity, and roll out tool families AA through AF.
**Go/No-Go Gate:** **Automation & Ledger Gate** (zero unscheduled console commands, outbox DLQ verified under poisoned payloads, ledger parity between PHP and TS contracts, zero points accrued from fear-based Dosha facts).

#### P7.1 Reminder Engine Corrections, Idempotency & Outbox DLQ (T-JYAUT-00a…00e)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYAUT-00a** | `app/Modules/Customers/Database/Migrations/2026_11_14_000002_widen_customer_ritual_reminders_period_key.php`<br>`app/Modules/Customers/Console/ProcessRitualRemindersCommand.php` | **Fixes D-12**: widen `customer_ritual_reminders.last_fired_period` from `string(32)` to `string(191)`; bind idempotency to the existing delivery-level claim: `communication_deliveries` `dedup_key(160) NOT NULL` with `unique(tenant_id, dedup_key)`. | S |
| **T-JYAUT-00b** | `app/Modules/Customers/Services/AnchorOccurrenceResolver.php`<br>`tests/Modules/Customers/PlaceAwareAnchorOccurrenceTest.php` | **Fixes D-13**: replace hardcoded Ujjain coordinates (`23.1765, 75.7885`) with the devotee's resolved `jyotish_places` coordinates; calculate local sunrise/noon anchors specific to devotee residence. | M |
| **T-JYAUT-00c** | `app/Modules/Customers/Services/AnchorOccurrenceResolver.php`<br>`tests/Modules/Customers/Post2030AnchorGenerationTest.php` | **Fixes D-13 (horizon cliff)**: remove `MAX_LOOKAHEAD_DAYS = 400` and the hard `year > 2030` throw; bind lookahead to the active precomputed astronomical event generations. | M |
| **T-JYAUT-00d** | `app/Modules/Customers/Console/ProcessRitualRemindersCommand.php`<br>`tests/Modules/Customers/RulesVersionBranchingTest.php` | **Fixes D-14**: implement genuine behavioral branching for `rules_version`: v1 legacy tropical anchors execute through the v1 compatibility layer; v2 sidereal anchors execute through the v2 calculation pipeline. | M |
| **T-JYAUT-00e** | `app/Platform/Outbox/DeadLetterStore.php`<br>`app/Platform/Console/OutboxReplayCommand.php`<br>`app/Platform/Database/Migrations/2026_11_14_000003_create_outbox_dead_letters_table.php`<br>`app/Platform/Outbox/OutboxHandlers.php` | **Fixes D-16 & Prerequisite for §19**: add `outbox_dead_letters` table; implement per-consumer cursors so one failing consumer does not block or re-trigger healthy consumers; implement `outbox:replay` command with `--all-exhausted` or `--id` flags. | L |

**Automated Test Matrix (P7.1)**
- MySQL Driver Width Enforcement Tests: `tests/Modules/Customers/ReminderDedupKeyWidthMySQLTest.php` (executes against MySQL driver; verifies complete 56-character ULID-based period key inserts without truncation).
- Place-Aware Anchor Tests: `tests/Modules/Customers/PlaceAwareAnchorOccurrenceTest.php` (verifies identical anchor rules evaluated for London, Ujjain, and Tokyo yield different local sunrise dates and times).
- Horizon Continuity Tests: `tests/Modules/Customers/Post2030AnchorGenerationTest.php` (evaluates anchor occurrences for 2035 CE; asserts successful resolution without exception).
- Outbox DLQ & Replay Tests: `tests/Platform/Outbox/DeadLetterAndPerConsumerCursorTest.php` (injects poisoned handler that throws 5 consecutive times; asserts row moves to `outbox_dead_letters`, healthy consumers succeed, and `outbox:replay` re-processes row upon resolution).

**Acceptance Criteria (P7.1)**
- `AC-T-JYAUT-00a`: Longest producible period key fits within `last_fired_period` with $\ge 25\%$ headroom; verified on MySQL driver enforcing column widths.
- `AC-T-JYAUT-00b`: Anchor occurrences for devotees in different geographic locations produce distinct timestamps matching local sunrise rules.
- `AC-T-JYAUT-00c`: Anchors beyond year 2030 resolve accurately against generation tables; `grep` confirms `MAX_LOOKAHEAD_DAYS` is eliminated from codebase.
- `AC-T-JYAUT-00d`: A v1 anchor and a v2 anchor evaluate down distinct code paths, evidenced by explicit `rules_version` metadata in the generated reminder fact.
- `AC-T-JYAUT-00e`: Outbox events failing 5 attempts are quarantined into `outbox_dead_letters`; other consumers for that row proceed without failure; `outbox:replay` command successfully drains the dead-letter queue.

---

#### P7.2 Platform Self-Maintenance & Autonomous Generation Lifecycles (JY-AUT-01…10)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYAUT-01** | `routes/console.php`<br>`app/Modules/Customers/Console/ProcessRitualRemindersCommand.php` | Schedule `customers:process-reminders` at `06:15 Asia/Kolkata` daily with `withoutOverlapping(30)` lock; active only after T-JYAUT-00a…00d are completed. | S |
| **T-JYAUT-02** | `app/Domain/Calendar/Commands/AutonomousPanchangGenerationCommand.php`<br>`routes/console.php` | Autonomous ahead-of-time generation: builds year $Y+1$ into a `building` generation; runs `calendar:validate` and `calendar:verify-oracle`; executes atomic swap to `active`. | M |
| **T-JYAUT-03** | `app/Domain/Calendar/Commands/GenerationHorizonWatchdogCommand.php` | Generation horizon watchdog: runs hourly; alerts operations if active generation horizon is $< 90$ days. | S |
| **T-JYAUT-04** | `app/Domain/Calendar/Commands/TzdbDriftDetectionCommand.php` | TZDB refresh detector: monthly daemon checking IANA tzdb updates; flags affected `jyotish_places` rows and emits `jyotish.tzdb.drift_detected`. | S |
| **T-JYAUT-05** | `app/Domain/Jyotish/Console/RulesetStalenessSweepCommand.php` | Ruleset staleness sweeper: monthly job scanning active rulesets; demotes rulesets older than maximum review interval to `due_for_review`. | S |
| **T-JYAUT-06** | `app/Domain/Geography/Console/PlaceCatalogueVerificationCommand.php` | Place catalogue growth worker: continuously verifies crowdsourced/newly entered geographic coordinates via GeocodingProvider port; quarantines unverified places. | M |
| **T-JYAUT-07** | `e2e/jyotish-boundary-fuzz.spec.ts`<br>`.github/workflows/nightly.yml` | Nightly boundary fuzz automation: probes random instants within $\pm 60	ext{ s}$ of tithi/nakshatra/yoga transitions; verifies root solver bracket and residual tolerances. | M |
| **T-JYAUT-08** | `app/Domain/Calendar/Commands/CrossEngineDivergenceProbeCommand.php` | Cross-engine divergence probe: nightly cron evaluating a rotating 0.5% sample of stored charts against offline high-precision oracle fixtures; logs divergence to `docs/23-evidence/`. | M |
| **T-JYAUT-09** | `app/Platform/Console/ComputePerformanceRatchetCommand.php` | Performance ratchet reporter: weekly cron aggregating `jyotish_computation_log`; publishes p50, p95, p99, and memory consumption trends. | M |
| **T-JYAUT-10** | `scripts/sync-jyotish-seo-registry.ts`<br>`package.json` | Automated SEO sync pipeline: runs on deploy touching tool definitions; triggers `sync:registry`, `seo:normalize`, and `qa-seo-v2`. | S |

**Automated Test Matrix (P7.2)**
- Console Schedule Tests: `tests/Feature/Calendar/ConsoleScheduleIntegrityTest.php` (parses `routes/console.php`; verifies `customers:process-reminders` and all maintenance jobs are registered with proper mutex locks).
- Generation Lifecycle Automation Tests: `tests/Feature/Calendar/AutonomousGenerationLifecycleTest.php` (simulates unattended generation build; asserts prior generation continues serving if validation fails).
- Ruleset Demotion Tests: `tests/Feature/Jyotish/RulesetStalenessDemotionTest.php` (verifies stale rulesets transition to `due_for_review` and update result payload status).

**Acceptance Criteria (P7.2)**
- `AC-T-JYAUT-01`: `php artisan schedule:list` includes `customers:process-reminders`; re-running command twice on identical date produces 0 duplicate notifications.
- `AC-T-JYAUT-02`: Autonomous annual Panchang generation executes without human intervention; any generation failing verification is rejected without activating.
- `AC-T-JYAUT-05`: Active rulesets older than declared review cycle are demoted to `due_for_review`; UI chips reflect updated status.
- `AC-T-JYAUT-ALL`: Architecture gate: every console command in `app/**/Console/` must be registered in `routes/console.php` or listed as an interactive tool.

---

#### P7.3 Devotee & Practitioner Frictionless Workflow Automations (JY-AUT-11…26)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYAUT-11..14** | `app/Modules/Customers/Listeners/{AutoGenerateDevoteeKundli,AmbiguityResolutionNudge}.php`<br>`app/Modules/Customers/Services/PersonalizedDayListService.php` | Devotee workflow: one-tap Kundli from saved birth profile (JY-AUT-11); auto-chart generation and derived Nakshatra/Rashi display on birth profile creation (JY-AUT-12); proactive ambiguity nudge on DST fold or ambiguous birthplace (JY-AUT-13); daily personalized Panchang/festival list (JY-AUT-14). | L |
| **T-JYAUT-15..18** | `app/Modules/Customers/Listeners/{DashaChangeAlertHandler,SadeSatiTransitAlertHandler}.php`<br>`app/Modules/Jyotish/Jobs/{BatchFamilyKundliGenerator,AnnualSolarReturnReportJob}.php` | Life event alerts: Dasha level-change alerts and Sade Sati entry/exit alerts (JY-AUT-15); Muhurat-to-booking one-click transfer (JY-AUT-16); family batch chart calculation using shared ephemeris snapshot (JY-AUT-17); annual Varshaphal birthday report auto-generator (JY-AUT-18). | L |
| **T-JYAUT-19..22** | `app/Modules/Customers/Services/{ReminderSelfHealingService,SmartDefaultPlaceResolver,SankalpTextGenerator,MonthlyVratPlanner}.php` | Practice assistants: reminder self-healing on ruleset upgrade without silent shifts (JY-AUT-19); device-assisted smart default location (JY-AUT-20); automated Vedic Sankalp text pre-fill for puja checkout (JY-AUT-21); monthly Vrat fasting planner (JY-AUT-22). | L |
| **T-JYAUT-23..26** | `app/Modules/Jyotish/Jobs/PractitionerBatchReportGenerator.php`<br>`app/Domain/Jyotish/Reports/EvidencePackBuilder.php`<br>`app/Modules/Jyotish/Services/{ConsultationPrepCacheWarmer,QualityTriageQueueService}.php` | Practitioner studio automations: batch Kundli/PDF generator (JY-AUT-23); automated finding evidence pack builder (JY-AUT-24); pre-appointment cache warmer (JY-AUT-25); quality triage queue for disputed or divergent calculations (JY-AUT-26, reuses `temple_booking_approvals` state machine). | L |

**Automated Test Matrix (P7.3)**
- Workflow Automation Tests: `tests/Feature/Customers/DevoteeAutomatedKundliTest.php`, `tests/Feature/Customers/ReminderSelfHealingUpgradeTest.php` (verifies devotee is prompted with side-by-side comparison before any reminder anchor date changes).
- Family Batch Snapshot Tests: `tests/Feature/Jyotish/FamilyBatchSnapshotTest.php` (verifies batch chart calculation for 5 family members evaluates ephemeris snapshots once per unique instant).
- Quality Triage SLA Tests: `tests/Feature/Jyotish/QualityTriageQueueTest.php` (verifies disputed calculation results transition through PENDING -> ASSIGNED -> REVIEWED states with SLA expiration timers).

**Acceptance Criteria (P7.3)**
- `AC-T-JYAUT-11..14`: Devotee with stored birth profile accesses full Kundli with zero additional form inputs; ambiguous birthplaces surface immediate clarification questions.
- `AC-T-JYAUT-16`: Booking draft generated from auspicious Muhurat records originating `calculation_id` and pre-fills devotee Gotra/Nakshatra into ritual Sankalp.
- `AC-T-JYAUT-19`: When an Ayanamsa or ruleset bump alters a reminder anchor date, devotee receives transparent notice; stored date does not update until explicitly acknowledged.
- `AC-T-JYAUT-26`: Any calculation result generating an external oracle divergence $\ge 10''$ is automatically routed to the quality triage queue and flagged `disputed`.

---

#### P7.4 Loyalty Points Ledger, Referral Fulfillment & Tier Engines (JY-RWD-01…16)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYRW-01** | `app/Modules/Loyalty/Database/Migrations/2026_11_14_000004_create_loyalty_ledger_tables.php`<br>`app/Modules/Loyalty/Models/{LoyaltyAccount,LoyaltyEntry,LoyaltyPolicy,LoyaltyRedemption}.php` | Schema and models: `loyalty_accounts` (tenant_shared), `loyalty_entries` (append-only ledger with cryptographic SHA-256 hash chaining), `loyalty_policies` (versioned accrual rules), and `loyalty_redemptions`. | M |
| **T-JYRW-02** | `app/Domain/Loyalty/LoyaltyLedgerService.php`<br>`tests/Unit/Loyalty/LoyaltyBalanceReducerParityTest.php` | Pure event-sourced ledger service: implements `rebuildPointsBalance` with exact byte-for-byte parity to TypeScript reducer in `packages/commerce/src/domain/loyalty.ts`; maintains reconciled cached balance column. | L |
| **T-JYRW-03** | `app/Modules/Loyalty/Handlers/LoyaltyAccrualHandler.php`<br>`app/Modules/Loyalty/module.json` | Transactional outbox accrual consumer: listens for platform facts (`jyotish.chart.completed`, `customers.reminder.fired`, `referral.qualified`); evaluates versioned policy; executes idempotent insert. **Strict hardcoded rule: zero accrual for Dosha facts.** | L |
| **T-JYRW-04** | `app/Modules/Customers/Services/ReferralRewardService.php`<br>`app/Modules/Customers/Database/Migrations/2026_11_14_000005_add_reward_fields_to_referral_attributions.php` | Referral reward engine: completes the deferred referral scope on `referral_attributions`; qualifies referrals via `canQualifyReferral()`; awards points to referrer and referee upon completed booking. | M |
| **T-JYRW-05** | `app/Modules/Loyalty/Http/Controllers/RedemptionController.php`<br>`app/Modules/Loyalty/Routes/api.php` | Two-phase redemption API: implements `:reserve` (creates `CreditHold`), `:settle`, and `:release` colon-verbs; ensures double-spend is mathematically impossible; enforces redemption catalogue restrictions. | M |
| **T-JYRW-06** | `app/Domain/Loyalty/Calculators/{PracticeTierEvaluator,StreakTracker,BadgeCalculator}.php` | Practice recognition engine: evaluates practice tiers from engagement breadth (never spend alone); tracks practice streaks over evaluated reminder slots; calculates practice badges. | L |
| **T-JYRW-07** | `apps/customer/app/(protected)/rewards/{page.tsx,ledger/page.tsx}`<br>`apps/customer/components/rewards/{PointsBalanceCard,LedgerTimeline,RedemptionCatalog,BadgeShelf}.tsx` | Devotee Rewards UI: live points balance, transparent ledger timeline displaying `sourceType` and `policyVersion`, redemption catalogue, and practice badge shelf. | L |
| **T-JYRW-08** | `apps/dashboard/app/governance/loyalty/{page.tsx,policies/page.tsx}`<br>`apps/dashboard/components/loyalty/{PolicyEditor,FraudHoldQueue,LedgerAuditView}.tsx` | Governance & Anti-Abuse console: policy management, tier threshold controls, FraudGuard integration (reusing `App\Modules\Recommendations\Services\FraudGuard`), and ledger audit viewer. | M |
| **T-JYRW-09** | `app/Modules/Finance/Services/LoyaltyLiabilityAccountingService.php`<br>`tests/Feature/Loyalty/FinancialAccountingLedgerTest.php` | Financial liability accounting integration: tracks unredeemed issued points as monetary balance-sheet liabilities; reconciles period-end redemptions and expired point cancellations. | L |

**Automated Test Matrix (P7.4)**
- TypeScript / PHP Reducer Parity Tests: `tests/Unit/Loyalty/LoyaltyBalanceReducerParityTest.php` (processes 200 synthetic ledger events through both PHP `LoyaltyLedgerService` and TS `rebuildPointsBalance()`; asserts identical final balances).
- Append-Only Invariant Tests: `tests/Feature/Loyalty/AppendOnlyLedgerTest.php` (asserts raw SQL `UPDATE` or `DELETE` on `loyalty_entries` triggers a database trigger exception).
- Idempotency & Exclusion Tests: `tests/Feature/Loyalty/OutboxAccrualDedupTest.php` (emits duplicate facts; asserts exactly one ledger entry created), `tests/Feature/Loyalty/DoshaFactAccrualExclusionTest.php` (emits `jyotish.dosha.detected` fact; asserts zero points are accrued).
- Concurrency & Double-Spend Tests: `tests/Feature/Loyalty/TwoPhaseRedemptionDoubleSpendTest.php` (dispatches 10 parallel redemption reservation requests for an account with balance = 100 points; verifies only 1 can settle).
- Anti-Dark-Pattern UI Tests: `apps/customer/components/rewards/__tests__/PointsBalanceCard.test.tsx` (asserts zero points expiry warnings or marketing countdowns render on or near Dosha or remedial views).

**Acceptance Criteria (P7.4)**
- `AC-T-JYRW-02`: PHP ledger service and TypeScript `rebuildPointsBalance` return identical point balances across all test vectors; `loyalty_entries` table strictly forbids updates and deletions.
- `AC-T-JYRW-03`: Emitting a fact twice results in exactly one accrual entry due to `(source_type, source_id, policy_version)` uniqueness.
- `AC-T-JYRW-03b`: Facts carrying Dosha identifiers accrue strictly 0 points; verified by automated positive assertion test.
- `AC-T-JYRW-05`: Attempted redemption exceeding balance returns `409 Conflict` with `insufficient_points`; concurrent two-phase reserves prevent double-spending.
- `AC-T-JYRW-05b`: Redemption catalog forbids selecting physical gemstones, rudrakshas, yantras, or paid Dosha remedies.
- `AC-T-JYRW-ALL`: Cross-tenant reward access returns HTTP 403 Forbidden under `AssertTenantIsolation`.

---

#### P7.5 Catalogue Depth Expansion: Families AA to AF (JY-051…108, JY-129, JY-130)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYCAT-01** | `app/Domain/Jyotish/Catalogue/FamilyAa/{UncertaintyPropagator,RectificationScorer,SolarTimeView,PolarFallbackHandler,RegionalEraCalculator,AyanamsaComparator}.php` | Family AA (JY-051…060): birth-data intelligence and uncertainty propagation; computes probability distributions for Lagna across time intervals $\pm \sigma$; birth-data quality score (0–100). | L |
| **T-JYCAT-02** | `app/Domain/Jyotish/Catalogue/FamilyAb/SpecialLagnaSuite.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAb/{InduLagna,SriLagna,VarnadaLagna,RudraLagna,PranapadaLagna}.php` | Family AB (JY-061…072): special Lagnas and chart geometry suite; implements unified `SpecialLagnaInterface { compute(snapshot, natalD1, profile): float }` serving all 12 special ascendants. | L |
| **T-JYCAT-03** | `app/Domain/Jyotish/Catalogue/FamilyAc/UpagrahaCalculations.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAc/{Kala,Mrityu,ArthaPraharaka,YamaGandaka,LagnaKaraka,KamaKaraka}.php` | Family AC (JY-073…084): sensitive points and Upagrahas; evaluates 12 derived mathematical points using closed-form coordinate relationships. | M |
| **T-JYCAT-04** | `app/Domain/Jyotish/Catalogue/FamilyAd/VargaExtensions.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAd/{D81NavaNavamsha,D108Ashtottaramsa,D144Dvadasamsa,VargottamaCensus}.php` | Family AD (JY-085…092): extended divisional charts; implements D81, D108, D144, and cross-varga Vargottama census; explicitly marked with `verification_status = extended_classical`. | L |
| **T-JYCAT-05** | `app/Domain/Jyotish/Catalogue/FamilyAe/StrengthDepthSuite.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAe/{IshtaKashtaPhala,KakshyaAshtakavarga,DrigBalaExact,SaptavargajaTable}.php` | Family AE (JY-093…100): strength and Ashtakavarga depth; degree-precise Drig Bala, 150 Kakshya analysis, Saptavargaja breakdown, and planetary strength heatmaps. | L |
| **T-JYCAT-06** | `app/Domain/Jyotish/Catalogue/FamilyAf/TimingBreadthSuite.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAj/{DivergenceExplorer,ReproducibilityProver}.php` | Family AF (JY-101…108) & Family AJ (JY-129, JY-130): timing breadth (Ashtottari, Yogini, Kalachakra, Mandooka); divergence explorer and automated reproducibility prover. | L |

**Automated Test Matrix (P7.5)**
- Uncertainty Propagation Tests: `tests/Unit/Jyotish/Catalogue/FamilyAaUncertaintyTest.php` (verifies Lagna spread increases monotonically as birth-time uncertainty $\sigma$ increases).
- Special Lagna Precision Tests: `tests/Unit/Jyotish/Catalogue/SpecialLagnasExtendedTest.php` (evaluates Indu, Sri, and Pranapada lagnas across 50 golden charts).
- All 130 Tools Registry Data Test: `tests/Unit/Jyotish/Catalogue/All130ToolsContractAndRegistryTest.php` (data-driven test iterating all 130 registry rows; asserts non-placeholder schemas, valid permissions, explicit ruleset codes, and non-empty scripture sources).
- Reproducibility Prover Tests: `tests/Feature/Jyotish/ReproducibilityProverTest.php` (executes JY-130 for 100 historical calculation IDs; confirms bit-level equality of reconstructed results).

**Acceptance Criteria (P7.5)**
- `AC-T-JYCAT-01`: Birth-time uncertainty propagator outputs bounded probability distribution for Lagna without returning a falsely confident point estimate.
- `AC-T-JYCAT-02`: All 12 special Lagnas in Family AB calculate deterministically using the shared snapshot without additional ephemeris passes.
- `AC-T-JYCAT-06`: Tool JY-130 successfully attests byte-level reproducibility for historical reports and charts.
- `AC-T-JYCAT-ALL`: Data-driven registry test passes across all 130 tools; every tool provides full OpenAPI specification and passes contract export.

**P7 Exit Gate (Automation & Ledger Gate & GATE-3):**
- All prerequisite bugfixes (T-JYAUT-00a…00e) verified in MySQL CI test environment.
- Outbox Dead Letter Queue and replay capability operational.
- Loyalty ledger parity verified with `@navdhya/commerce`; zero points accrued from Dosha facts.
- All 130 tools registered and validated.

---

### PHASE P8 — Smart Optimisation, Content-Addressed Caching, Numerical Solvers & Scale Hardening
**Timeline:** Sprint 29–32 · **Optimisations:** JY-OPT-01 … JY-OPT-24 · **Adjacent Catalogue:** JY-109 … JY-128
**Primary Objective:** Deliver high-performance caching (canonical HMAC-SHA256 layered hashing), numerical optimizations (speed-adaptive sampling, Brent solver, integer microdegrees, precompiled AST predicates), dynamic edge delivery, and scale hardening.
**Go/No-Go Gate:** **Full Performance, Scale & Integrity Gate** (p95 execution budgets met under real OPcache, monotonic compute ratchets committed, zero silent precision downgrades, GATE-4 green).

#### P8.1 Compute Telemetry Baseline, Audit Instrumentation & Monotonic Ratchets (JY-OPT-00, JY-OPT-04…07)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYOPT-00** | `app/Domain/Calendar/Audit/ComputationAuditor.php`<br>`app/Domain/Jyotish/Support/ComputationTelemetryHook.php` | **Fixes D-15 & Prerequisite for §20**: wire `ComputationAuditor::log()` into production calculation kernel and tool orchestrators; eliminate dead code; establish live baseline in `jyotish_computation_log`. | S |
| **T-JYOPT-04** | `database/migrations/2026_11_14_000006_expand_jyotish_computation_log_columns.php`<br>`app/Domain/Jyotish/Support/ComputationMetricCollector.php` | Expand computation telemetry columns: record `ephemeris_calls`, `root_iterations`, `ruleset_evals`, `db_queries`, and `peak_mem_bytes` on every calculation request. | M |
| **T-JYOPT-05** | `tests/Architecture/JyotishComputeBudgetTest.php`<br>`tests/Architecture/Fixtures/compute-budgets.json` | Architectural compute budget ratchet tests: enforce monotonic ceilings on complete D1 calculation ($\le 1$ snapshot, $\le 1$ ayanamsa eval) and 30-day Muhurat search ($\le 1,000$ coarse samples); fails in CI if budgets are exceeded. | M |
| **T-JYOPT-06** | `tests/Architecture/JyotishQueryPlanBudgetTest.php`<br>`tests/Architecture/Fixtures/query-plans.json` | Database query plan budget tests: runs `EXPLAIN QUERY PLAN` on SQLite and `EXPLAIN` on MySQL; asserts all hot queries (snapshot lookup, dasha intervals, ashtakavarga, birth profile) use index lookups (`assertIndexed()`); zero full table scans. | M |
| **T-JYOPT-07** | `app/Platform/Console/NightlyPerformanceRegressionCommand.php`<br>`.github/workflows/nightly-perf.yml` | Nightly performance regression suite: detects week-over-week p95 latency drift exceeding 10%; separates cold vs warm cache; automatically generates GitHub issues with flamegraphs. | M |

**Automated Test Matrix (P8.1)**
- Telemetry Live Logging Tests: `tests/Feature/Jyotish/Telemetry/ComputationAuditorLiveLoggingTest.php` (executes D1 calculation; verifies `jyotish_computation_log` row is created with non-zero execution time, memory bytes, and accurate cache-hit flag).
- Architecture Ratchet Tests: `tests/Architecture/JyotishComputeBudgetTest.php` (simulates calculation calls; asserts ephemeris evaluation count does not exceed ceiling of 1).
- Query Plan Budget Tests: `tests/Architecture/JyotishQueryPlanBudgetTest.php` (validates 10 hot calculation SQL queries against SQLite and MySQL; asserts index usage).

**Acceptance Criteria (P8.1)**
- `AC-T-JYOPT-00`: `ComputationAuditor` is invoked on all calculation requests; `jyotish_computation_log` records real production telemetry.
- `AC-T-JYOPT-05`: `JyotishComputeBudgetTest` enforces monotonic ceilings; any PR that increases ephemeris evaluations or query counts fails CI automatically.
- `AC-T-JYOPT-06`: Hot Jyotish read queries execute via indexed paths without triggering full table scans (`SCAN` or `ALL`).

---

#### P8.2 Content-Addressed HMAC Cache, Layered Partitioning & Invalidation (JY-OPT-01…03, JY-OPT-23)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYOPT-01** | `app/Domain/Jyotish/Cache/CalculationCacheHasher.php`<br>`app/Domain/Jyotish/Cache/CalculationIdentity.php` | Content-addressed calculation cache keys: computes canonical HMAC-SHA256 digests over sorted calculation identity tuples; prevents geographic coordinates from leaking into plaintext cache key spaces. | M |
| **T-JYOPT-02** | `app/Domain/Jyotish/Cache/LayeredCacheCoordinator.php`<br>`app/Domain/Jyotish/Profiles/CalculationProfile.php` | 4-Tier layered key partitioning: `snap:*` (astronomical positions, immortal) $	o$ `sid:*` (ayanamsa projection) $	o$ `chart:*` (house cusps, chalit) $	o$ `render:*` (locale, theme); switching Ayanamsa reuses `snap:*` tier without re-running ephemeris. | L |
| **T-JYOPT-03** | `app/Domain/Jyotish/Cache/MultiTierCacheManager.php`<br>`config/cache.php` | Multi-tier cache storage: Redis hot memory cache with persistent `jyotish_ephemeris_snapshots` database tier fallback; request-scoped in-memory memoization registry for multi-module reports. | M |
| **T-JYOPT-23** | `tests/Unit/Jyotish/Cache/CacheKeyVersionDriftTest.php`<br>`app/Domain/Jyotish/Cache/VersionDriftDetector.php` | Cache version drift detector: ensures bumping `rules_version`, `ephemeris_version`, or `ayanamsa_version` invalidates only the appropriate downstream cache layers. | M |

**Automated Test Matrix (P8.2)**
- Cache Key Determinism Tests: `tests/Unit/Jyotish/Cache/HmacCacheKeyDeterminismTest.php` (verifies key hashing is invariant to input JSON key ordering and produces identical digests for identical physical parameters).
- Layered Invalidation Tests: `tests/Unit/Jyotish/Cache/LayeredCacheInvalidationIndependenceTest.php` (warms full cache hierarchy; modifies Ayanamsa version; asserts `snap:*` hits while `sid:*` and `chart:*` miss).
- Multi-Tier Hit/Miss Tests: `tests/Feature/Jyotish/Cache/MultiTierCacheHitMissTest.php` (verifies Redis cache hit resolves in $< 10	ext{ ms}$; database snapshot fallback resolves in $< 25	ext{ ms}$).

**Acceptance Criteria (P8.2)**
- `AC-T-JYOPT-01`: Cache keys are HMAC-SHA256 digests; raw birth coordinates never appear in cleartext cache key strings.
- `AC-T-JYOPT-02`: Changing Ayanamsa model from Lahiri to Raman reuses the existing astronomical `snap:*` tier with 0 additional ephemeris evaluations.
- `AC-T-JYOPT-23`: Bumping a calculation ruleset version invalidates downstream chart and finding cache keys without wiping raw ephemeris data.

---

#### P8.3 Algorithmic Refinement, Brent Solvers & Integer Microdegrees (JY-OPT-08…16)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYOPT-08..09** | `app/Domain/Jyotish/Astronomy/TransitionSolver.php`<br>`app/Domain/Calendar/Astronomy/RootSolver.php` | Adaptive numerical root solver: implements speed-adaptive coarse sampling based on $\dot{\lambda}$ followed by Brent root finding; reduces boundary iterations by $> 50\%$ while preserving $\le 1	ext{ s}$ precision. | M |
| **T-JYOPT-10..11** | `app/Domain/Jyotish/Astronomy/PiecewiseAyanamsaTable.php`<br>`app/Domain/Jyotish/Support/Microdegrees.php` | Piecewise polynomial Ayanamsa tables and integer arithmetic: precomputed century polynomial tables with bounded error $< 10^{-7}$; integer microdegree arithmetic ($1^\circ = 3,600,000\ \mu^\circ$) eliminating floating-point rounding errors at zodiac sign boundaries. | M |
| **T-JYOPT-12..13** | `app/Domain/Jyotish/Rules/CompiledPredicateGraph.php`<br>`app/Domain/Jyotish/Matching/ImmutableCompatibilityMatrix.php` | Compiled rule predicates and static matrix lookups: precompiles Yoga and Dosha AST rule trees into ordered bytecode plans per ruleset version; converts compatibility matrices (Yoni, Gana, Nadi) into immutable static memory lookup tables. | M |
| **T-JYOPT-14..16** | `app/Domain/Jyotish/Dasha/Timeline/IntervalMergeIntersection.php`<br>`app/Domain/Jyotish/Ephemeris/BatchEphemerisProvider.php` | Scaled interval processing: sorted-interval merge algorithms for dasha and transit intersections; vectorized batch ephemeris evaluations hoisting time loops for multi-date Muhurat searches. | L |

**Automated Test Matrix (P8.3)**
- Brent vs Bisection Equivalence Tests: `tests/Unit/Jyotish/Astronomy/BrentSolverEquivalenceTest.php` (compares Brent solver against bisection over 1,000 transitions; verifies exact root equivalence down to $10^{-9}	ext{ rad}$ with $\le 40$ iterations).
- Integer Microdegree Boundary Tests: `tests/Unit/Jyotish/Astronomy/IntegerMicrodegreesBoundaryPrecisionTest.php` (verifies zero float leakage across all 108 Pada and 12 Rashi boundaries).
- Compiled Predicate Benchmark: `tests/Unit/Jyotish/Rules/PrecompiledPredicateExecutionSpeedTest.php` (verifies compiled predicate tree executes 100 Yogas in $< 5	ext{ ms}$).

**Acceptance Criteria (P8.3)**
- `AC-T-JYOPT-08..09`: Transition root finding converges in $\le 40$ iterations with residual $\le 10^{-6}	ext{ rad}$; speed-adaptive sampling skips empty search spaces safely.
- `AC-T-JYOPT-11`: Boundary calculations using integer microdegrees never misclassify placements at border thresholds ($29^\circ 59' 59.999''$).
- `AC-T-JYOPT-12`: Compiled predicate engine executes complete Yoga/Dosha rule evaluations without runtime AST parsing.

---

#### P8.4 Edge Delivery, Code-Splitting, Dynamic Precomputation & UI Caching (JY-OPT-17…21)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYOPT-17** | `apps/web/app/resources/tools/panchang/page.tsx`<br>`apps/web/next.config.mjs` | Incremental Static Regeneration (ISR): public daily Panchang and festival pages generate statically at build time with 24-hour revalidation; serves public reads with 0 PHP execution. | M |
| **T-JYOPT-18** | `app/Domain/Calendar/Jobs/PredictivePrecomputationJob.php`<br>`app/Domain/Calendar/Services/DemandShapedCacheWarmer.php` | Demand-shaped predictive cache warming: leverages `packages/analytics` traffic telemetry and festival calendar dates to precompute and warm top 50 cities for upcoming festival days. | M |
| **T-JYOPT-19** | `packages/design-system/src/charts/index.ts`<br>`apps/web/components/charts/DynamicChartLoader.tsx` | Dynamic code-splitting for SVG charts: lazily imports ChartCanvas submodules; ensures Jyotish visual tooling does not exceed the 300 KB client bundle budget. | M |
| **T-JYOPT-20..21** | `app/Modules/Jyotish/Http/Middleware/AddCalculationETag.php`<br>`apps/web/adapters/backend/laravel-client.ts` | Edge caching and conditional HTTP requests: computes `ETag` from `generation + chart_version`; returns HTTP 304 Not Modified for unchanged client requests; layers theme and locale downstream of calculation keys. | M |

**Automated Test Matrix (P8.4)**
- Static Generation Verification: `apps/web/tests/isr-panchang-delivery.test.ts` (verifies public panchang route returns `HIT` from Next.js ISR cache without invoking PHP backend).
- Bundle Size Analysis: `apps/web/tests/chart-bundle-size-budget.test.ts` (asserts initial client JavaScript chunk size remains $\le 300	ext{ KB}$ with chart modules code-split).
- HTTP ETag Validation: `tests/Feature/Jyotish/Http/ETag304NotModifiedTest.php` (sends request with `If-None-Match`; verifies HTTP 304 response with empty body).

**Acceptance Criteria (P8.4)**
- `AC-T-JYOPT-17`: Public Panchang pages achieve 0 PHP compute overhead on cacheComponents hit.
- `AC-T-JYOPT-19`: Client bundle size across all 80 new tool routes satisfies Lighthouse script budget ($\le 300	ext{ KB}$).
- `AC-T-JYOPT-20..21`: Returning devotees with cached charts receive HTTP 304 Not Modified; changing UI theme or language does not invalidate the underlying calculation cache.

---

#### P8.5 Trust-Preserving Correctness Guards & Adjacent Electional Suite (JY-OPT-22…24, JY-109…128)

| Task ID | Deliverable / Target File Paths | Scope & Technical Deliverable | Est |
| --- | --- | --- | --- |
| **T-JYOPT-22..24** | `app/Domain/Jyotish/Support/PrecisionGuard.php`<br>`tests/Unit/Jyotish/Quality/PrecisionDowngradeRejectionTest.php` | Correctness and anti-downgrade guards: throws explicit exception if low-precision engine tier is requested for high-precision calculations (JY-OPT-22); invalidates cached results when underlying rules are demoted (JY-OPT-24). | M |
| **T-JYCAT-07** | `app/Domain/Jyotish/Catalogue/FamilyAg/{KshayaMasa,RiktaYoga,Panchaka,AmitGhadi,Varjyam,DurMuhurta,Bhadra,LagnaShuddhi}.php` | Family AG (JY-109…116): complete electional completeness tools; evaluates Kshaya Masa, Rikta Yoga, Panchaka, Varjyam, Dur Muhurta, Bhadra, and Lagna Shuddhi using IntervalAlgebra. | L |
| **T-JYCAT-08** | `app/Domain/Jyotish/Catalogue/FamilyAh/{RajjuMatcher,VedhaMatcher,MahendraPorutham,StreeDeergha}.php`<br>`app/Domain/Jyotish/Catalogue/FamilyAi/{AdvancedNumerology,VastuRoomPlacement}.php` | Family AH (JY-117…122) & Family AI (JY-123…128): in-depth Porutham matching algorithms and adjacent Vastu/Numerology studios in isolated namespaces. | L |

**Automated Test Matrix (P8.5)**
- Anti-Downgrade Tests: `tests/Unit/Jyotish/Quality/PrecisionDowngradeRejectionTest.php` (asserts requesting sub-arcsecond Lagna calculation against low-order series throws exception rather than returning degraded precision).
- Electional Window Coverage Tests: `tests/Unit/Jyotish/Catalogue/ElectionalPanchakaShuddhiTest.php` (verifies Panchaka and Dur Muhurta intervals correctly exclude inauspicious time slices).
- Methodology Segregation Tests: `tests/Architecture/PoruthamAshtakootaNonSummationTest.php` (asserts Porutham matching scores and Ashtakoota points are never added together in any code path).

**Acceptance Criteria (P8.5)**
- `AC-T-JYOPT-22`: System strictly rejects silent precision downgrades; calculations disclose the exact ephemeris engine and version used.
- `AC-T-JYCAT-07`: Electional tools in Family AG calculate via interval algebra over precomputed event boundaries.
- `AC-T-JYCAT-08`: Adjacent systems in Family AI maintain zero imports from Jyotish kernel modules; compatibility tools in Family AH maintain strict methodology isolation.

**P8 Exit Gate (Full Performance, Scale & Integrity Gate & GATE-4):**
- All 24 optimization mechanisms deployed and verified with before/after benchmarks.
- Measured p95 latency targets achieved: Cached D1 $\le 20	ext{ ms}$; uncached D1 $\le 150	ext{ ms}$; 30-day Muhurat search $\le 2	ext{ s}$.
- Worker RSS memory $\le 128	ext{ MB}$ steady / $\le 256	ext{ MB}$ peak.
- All 130 tools and 26 automations operating under production load profiles.

---

## 7. Extended catalogue → phase mapping (24 families)

| Family | Name | Phase | Notes |
| --- | --- | --- | --- |
| A | Advanced Kundli & chart construction | P1 core, P6 breadth | 26 tools; chart renderers land T-JY06-04 |
| B | Divisional charts (Shodasha Varga + D81/108/144) | P1 (T-JY04-07/08) | core set kept distinct from extended |
| C | Graha analysis (35 tools) | P1, P2 | matrix/speed/interaction views in P6 |
| D | Planetary strength / Bala | P4 | components + units mandatory |
| E | Ashtakavarga studio | P4 | staged Shodhana |
| F | Dasha systems (20) + 15 tools | P3 | independent systems, never blended |
| G | Yoga detection | P4 | predicate engine |
| H | Dosha & Shanti | P4 | trust-sensitive UI |
| I | Nakshatra & lunar intelligence | P1/P2, P6 | 40+ tools |
| J | Panchang & Hindu calendar | P2 (exists) + P6 | reuse Calendar domain |
| K | Muhurat studio (23 occasions + 20 sub-tools) | P3 | marketplace seam T-JY07-12 |
| L | Gochar & transit | P3 | ingress/station calendars |
| M | Marriage & compatibility | P4 | methodologies never summed |
| N | Jaimini | P5 | own rulesets |
| O | KP | P5 | own ayanamsa + cusps |
| P | Prashna & horary | P5 | question context, not birth |
| Q | Tajika & Varshaphal | P5 | solar return solver |
| R | Lal Kitab | P5 | separate tradition |
| S | Nadi / lineage | P6 research | practitioner-led, non-predictive |
| T | Samhita / mundane | P6 research | public-safety predictions excluded |
| U | Chakras (24 + 7 specialist views) | P5 | shared renderer contract |
| V | Upaya / mantra / remedial reference | P6 | knowledge + ritual discovery only |
| W | Practitioner & research workbench | P5 | differentiation |
| X | AI Jyotish assistant | P6 | three-service architecture |
| Y | Reports & exports | P5 | shared report engine |
| Z | Adjacent Indian knowledge utilities | P6 | own namespaces |

---

## 8. Performance targets (re-based for the PHP request model)

The spec's budgets assume a long-lived native process. Under PHP-FPM the process-scoped LRU is replaced by the distributed cache; numbers below are **targets to be proven on real hardware**, recorded in `jyotish_computation_log.execution_ms` / `peak_mem_bytes`.

| Operation | Target p95 | Mechanism |
| --- | --- | --- |
| Cached Rashi / Nakshatra | 10 ms | profile-keyed cache hit |
| Cached basic Kundli read | 20 ms | `jyotish_charts` row read |
| Uncached full D1 | 150 ms | one snapshot, one ayanamsa evaluation |
| Basic Panchang snapshot | 50 ms | active-generation read (precomputed B-1/B-2) |
| Tithi transition lookup | 100 ms | stored event row |
| Refined boundary | 250 ms | bracket + Brent, ≤ 40 iterations |
| Ashtakoota match | 50 ms | precompiled matrices |
| Dasha lookup | 15 ms | binary search over stored intervals |
| Full strength analysis | 150 ms | shared vargas |
| Muhurat 30 / 90 days | 2 s / 4 s | interval algebra, not polling; >90 d async |
| Full PDF report | async | `202` + status URL |

**Memory:** worker steady RSS ≤ 128 MB / peak ≤ 256 MB measured per PHP-FPM worker; hot snapshot ≤ 2 KB; simple tool result ≤ 10 KB; saved chart ≤ 50 KB; PDFs only in object storage. **Load acceptance:** 50 rps mixed, 100 concurrent charts with no RSS growth, 30-min soak, no cross-request state leakage, p95 and p99 reported separately, cold vs warm cache separated. Measure calculation latency and total API latency distinctly; provider/network latency never folded into calculation benchmarks.

---

## 9. Quality programme

### 9.1 Test layers (all mapped to existing phpunit suites)

| Layer | Home | Release condition |
| --- | --- | --- |
| Unit (math) | `tests/Unit/Jyotish/` — bare `PHPUnit\Framework\TestCase`, no Laravel boot (Calendar precedent) | all pass |
| Property / invariants | `tests/Unit/Jyotish/Invariants/*` | all pass |
| Boundary | `tests/Unit/Jyotish/Boundaries/*` — every sign, nakshatra, pada, tithi, karana, cusp transition | all pass |
| Golden | `tests/Unit/Jyotish/Golden/*` + `tests/Fixtures/jyotish/golden/*.json` | all pass |
| Cross-engine | `calendar:verify-oracle` extension → `docs/23-evidence/` | within approved tolerances |
| Practitioner | rule-specific reviewed cases recorded in `jyotish_ruleset_audit` | approved per ruleset |
| Integration | `tests/Feature/Jyotish/*` | all pass |
| Module/API | `tests/Modules/Jyotish/*` (contract + envelope + authz) | all pass |
| Tenant isolation | `tests/TenantIsolation/*` citing `AssertTenantIsolation` | all pass |
| Architecture | `tests/Architecture/*` + `deptrac` + `npm run check:architecture` | all pass |
| Performance | k6 (GATE-4) | §8 SLO |
| Security | ASVS nightly + secret scan | no critical finding |
| Regression | generation-to-generation diff (`calendar:replay` pattern) | no unexplained change |
| E2E | `e2e/jyotish-*.spec.ts` — **must be added to the `testMatch` regex** | nightly green |

### 9.2 Golden corpus (initial coverage targets, non-exclusive)

1,000+ birth charts · 300 Indian geographies · 150 historical-timezone cases · 200 nakshatra/rashi boundaries · 150 retrograde/ingress cases · 100 polar/unusual-location cases · 100 uncertain/incomplete birth records. Each vector carries the `provenance` shape already used by `ExternalGoldValidationTest` (`authority`, `edition`, `is_self_generated: false`). ≥ 70 % of astronomical vectors must be externally sourced. **Two third-party astrology vendors agreeing is not evidence** — they may share the same library and the same inherited assumptions.

Cross-reference sources: independent high-precision ephemeris vectors (JPL-Horizons-class), USNO references for JD and sidereal time, published Panchang almanacs for daily comparison, named texts + qualified practitioners for traditional rules.

### 9.3 Mandatory automated invariants (property tests, not manual checks)

```
0 ≤ λ < 360                                  Σ(27 nakshatra widths) = 360°
27 × 4 = 108 padas                           30 × 12° = 360°
60 × 6° = 360°                               Σ(9 Vimshottari years) = 120
angleDiff(Rahu, Ketu) = 180°                 wrap360 idempotent
Every planet in exactly 1 Rashi and 1 Bhava   Dasha intervals: no gaps, no overlaps
SAV(sign) = Σ BAV(p, sign) over the declared set   Σ Ashtakoota ≤ 36
```

### 9.4 Code-quality bars (unchanged, and binding)

PHPStan **level 6** with Larastan — **no new baseline entries** (the 16 existing ones are legacy `app/Http` scaffold only). Pint (`psr12`). Deptrac. TS strict; `no-restricted-imports` bans `..` (use `@/*`, `@navdhya/*`); turbo `boundaries` forbids cross-app imports and `pkg:shared → app:*`. Lighthouse: a11y ≥ 0.95, perf ≥ 0.9, LCP ≤ 2500 ms, CLS ≤ 0.1, script ≤ 300 KB — SVG charts must be code-split, or this budget will be blown.

### 9.5 Product KPIs before declaring production-ready

Calculation reproducibility · boundary coverage % · external reference agreement % · calculation error rate · API p95 · report success rate · cross-engine divergence · rule traceability % · unsupported-calculation rate · practitioner-reviewed methodology coverage %.

**The gate is not the number of calculators shipped — it is whether each result is reproducible, explainable and correctly labelled.**

---

## 10. UI / UX system

### 10.1 Design foundation

Reuse `packages/design-tokens` verbatim (`SACRED_EMBER_PALETTE`, `SACRED_EMBER_SEMANTIC`, `sacredEmberTailwindColors`), and **fix D-6** so tokens are actually consumed instead of bypassed. Enforce the brand rules from `AGENTS.md` mechanically: no green (hue band gate), orange as signature not background flood, aubergine as structural anchor, ivory/sand for negative space, Deep Text for body only. Charts must remain readable reduced to aubergine + flame only, and in monochrome. Do not copy the hardcoded `bg-[#FFF7ED]` classes currently in the orphaned panchang components.

### 10.2 Component inventory to build (in `packages/design-system`, exported via `src/index.ts`)

`NorthIndianChart` · `SouthIndianChart` · `EastIndianChart` · `RashiWheel` · `VargaGrid` · `AshtakavargaGrid` · `BinduHeatmap` · `DashaTimeline` · `TransitOverlay` · `PanchangStrip` · `MuhuratWindowList` · `CompatibilityBars` · `StrengthBreakdown` · `EvidencePanel` (yoga/dosha conditions) · `MethodologyBadge` · `VerificationStatusChip` · `ChakraCanvas` (family U contract) · `ProvenanceFooter`.

`packages/design-system/package.json` currently exports only `"."` — add subpaths rather than deep-importing (would break the alias/architecture tests).

### 10.3 Interaction and content rules

1. **Every** number is traceable: results carry a "how this was calculated" drawer showing profile, ayanamsa, house system, node mode, ephemeris model/version, generation, ruleset + version.
2. Methodology is a **user-visible first-class control**, never an implicit default; switching it re-labels rather than silently mutates, and stale results are marked superseded.
3. Traditional-rule output is labelled as traditional astrological opinion, with source citation and `verification_status`; calculations are presented separately from interpretations.
4. No fear-based language. Dosha output never states cursed/doomed/infertile/divorce outcomes; no remedy CTA without a verified finding and no outcome promises for gems/parades.
5. Birth-data UX must handle the honest edge cases: unknown time (bounded-result messaging), approximate time (uncertainty propagated into results), ambiguous place (confirmation step), DST fold (explicit choice).
6. All times shown via `packages/date-time` (`formatIst*`, `formatMuhuratWindow`) with both local and UTC available.
7. Loading/empty/error follow the existing BFF degradation pattern (`force-dynamic` RSC + try/catch), but an empty Muhurat result must **explain why**, not render a blank card.
8. i18n: Devanagari-first labelling is already supported (`CANONICAL_REGIONS.nameDev`, `LOCALE_LABELS.hi`, `HREFLANG_BY_LOCALE`); use the controlled glossary rather than literal translation.
9. A11y: `packages/accessibility` primitives (`SACRED_FOCUS_RING_CLASS`, `MIN_STANDARD_TOUCH_TARGET_PX`, `buildLiveAnnouncement`, `getNextRovingIndex`, `auditPageLandmarks`) — plus a recommended first-ever `jest-axe`/`@axe-core/playwright` integration, since none exists today.
10. Placement per app: consumer tools → `apps/web` `/resources/tools/*` + `apps/customer` `(protected)`; practitioner studio → `apps/partner`; registry/ruleset/quality governance → `apps/dashboard` + `apps/web/app/admin`.

---

## 11. Governance

### 11.1 Rule-set registry discipline

Ashtakoota, KP, Parashari, Jaimini, Tajika, Lal Kitab and regional Panchang conventions each own their `ruleset_code`. They never overwrite one another; conflicts are returned for manual review rather than resolved silently. Every published finding stores the ruleset id + version that produced it.

### 11.2 Immutable provenance

Any saved chart or report is reproducible: `(birth profile, profile_code, generation, chart_version, rules_version)` reconstructs identical numbers. Retiring a generation never deletes the data needed to re-render history.

### 11.3 Trust and safety requirements

Calculation vs interpretation vs prediction are three distinct labels in the product. Medical, legal, financial and relationship decisions carry a "not a substitute for professional advice" notice. Astrological public-safety claims are excluded (§0.3). Lineage traditions are attributed, never simulated as authentic.

### 11.4 Admin controls (dashboard/console)

Profile CRUD · ayanamsa/house-system/node-mode defaults · ruleset version publish + rollback · generation build/validate/activate/replay · feature-flag toggles (console-only mutation, per `FeatureFlagSetCommand`) · golden-corpus management · divergence reports · verification-status promotion · tool registry deprecation. Every action audited.

---

## 12. ADR schedule

| ADR | Decision | Needed by |
| --- | --- | --- |
| **ADR-JY-01** (`ADR-0079`) | Jyotish lives in `App\Domain\Jyotish` (pure, `Jyotish: [Support]`) with `Modules/{Jyotish,Calendar}` as HTTP surfaces — no new language/runtime, Rust recommendation formally declined | P0 |
| **ADR-JY-02** (`ADR-0080`) | Pure-PHP ephemeris, precision policy, and the *single* sidereal pipeline; Swiss Ephemeris retained as an **offline validation oracle only** (AGPL/Professional gate stays closed) | P0 |
| **ADR-JY-03** (`ADR-0081`) | Canonical time scale typing (UT1/TT/UTC) + historical-timezone + tzdb-versioning policy | P1 |
| **ADR-JY-04** (`ADR-0082`) | Caching topology: computation-log + Cache-store keys by calculation identity, HMAC'd; whether Octane is adopted | P1 |
| **ADR-JY-05** (`ADR-0083`) | Ruleset registry + practitioner validation workflow + `verification_status` promotion | P2 |
| **ADR-JY-06** (`ADR-0084`) | Interpretation layer: three-service split and non-hallucination guarantees | P6 |
| **ADR-JY-07** (`ADR-0085`) | Multilingual expansion and the controlled Jyotish glossary | P6 |

---

## 13. Risk register and open decisions

| # | Risk / decision | Mitigation | Decidable by |
| --- | --- | --- | --- |
| R-1 | Pure-PHP precision ceiling for 1″ Lagna agreement | ADR-JY-02 sets a realistic per-convention tolerance; relax only with an evidence-recorded reference disagreement | P1 gate |
| R-2 | PHP CPU cost of higher-order series at Muhurat scale | interval algebra + snapshot reuse + async jobs; measure via `jyotish_computation_log` | P3 |
| R-3 | Scope explosion (250+ tools) | registry-driven adds; waves; no kernel edits in P6 | continuous |
| R-4 | Traditional rules unsourceable / disputed | block `practitioner_reviewed` status; surface `disputed`; never ship as confirmed | P4 |
| R-5 | Fear-based or commercially-motivated remedy output | schema-level bans + UI tests (§10.3-4) | P4 |
| R-6 | SVG chart payload blowing the 300 KB Lighthouse script budget | code-split chart package; wire `lhci` (currently unenforced) | P2 |
| R-7 | Silent green violation from `styles/globals.css` | `containsDisallowedGreen()` CI gate (D-6 fix) | P0 |
| R-8 | Backfill shifting devotee reminder dates unnoticed | ADR-0035 discipline: version-stamp, never mutate; user-visible notice | B-7 |
| R-9 | **Which methodologies does Navdhya officially support, and who validates them?** | named practitioner board + per-ruleset sign-off | before P4 |
| R-10 | **Which calculations are in-house vs provider-backed?** | ADR-JY-02 (in-house PHP; provider = offline oracle) | before P1 |
| R-11 | **Can practitioners publish their own methodology tools without touching the kernel?** | registry + sandboxed ruleset rows; validated by T-JY09-07 | before P6 |

**Failure modes to reject in code review** (from the spec, restated as review blockers): recomputing planets inside a tool · assuming today's city offset historically · midnight-based Panchang · rounding longitude before indexing nakshatra · hardcoding one ayanamsa · equating Bhava with Rashi · hardcoding Dasha dates · per-second Muhurat scanning · one API for every methodology · LLM-computed positions · unverified Dosha claims · PDFs in cache · chart values without their calculation version.

---

## 14. Release-gate summary

| Release | Tools | Backend | Tables | Backfill | Frontend | Gate |
| --- | --- | --- | --- | --- | --- | --- |
| **P0** | 0 | Domain+module scaffold, deptrac, envelope, registry, D-1/D-3/D-6 fixes | §4.1 reference tables | — | tokens conformance only | GATE-0 |
| **P1** | JY-001…020 | 9-graha ephemeris, ayanamsa, Lagna, houses, Chalit, dignity, motion, aspects, vargas | §4.2 charts/birth | B-3 | birth form + 4 chart renderers | **Astronomical correctness** + GATE-1 |
| **P2** | JY-021…029 | Panchang, transition solver, rise/set, Rahu Kaal, Hora/Choghadiya, balas, festivals, `Modules/Calendar` HTTP | events corrected | **B-1, B-2** | wire the 4 orphan components, kill dead links, profile integration | **Timing precision** + GATE-2 |
| **P3** | JY-030…037 | Muhurat interval search, Vimshottari + systems, timelines, gochar, Sade Sati | dasha_intervals, muhurata | B-9 | Muhurat finder, Dasha timeline, transit panels, booking handoff | **Temporal & rule validation** + GATE-3 |
| **P4** | JY-038…048 | Shadbala, Bhava/Vimsopaka, BAV/SAV/Shodhana, Ashtakoota, Dashakoota, Manglik, Yoga+Dosha predicate engine | strength, ashtakavarga, findings, matchings | B-4, B-5, B-6, B-7, B-8 | Ashtakavarga studio, Guna Milan, evidence panel | **Practitioner validation** + GATE-3 |
| **P5** | JY-049…050 | Jaimini, KP, Prashna, Tajika, Lal Kitab, Chakras, reports, practitioner workbench | varga/notes/reports | B-10 | partner studio, report centre, chakra renderer | **Methodology acceptance** + GATE-4 |
| **P6** | Extended catalogue | family breadth, interpretation layer, multilingual | — | ongoing | consumer + practitioner + admin surfaces | GATE-5 |
| **P7** | JY-051…108, JY-129/130 (§17) | 26 automations incl. scheduling the dormant reminder engine (§18) | full loyalty ledger + accrual + redemption (§19) | measurement baseline: request memo, `ephemeris_calls` logging | tools pages, balance view, automation status | §18.6 + §19.7, GATE-2/3 — *may start at P3 exit* |
| **P8** | JY-109…128 (§17) | nightly boundary fuzz, divergence probe, perf ratchet reporter | projections served from cache only | 24 optimisations: HMAC layered cache, adaptive Brent, compiled predicates, static generation (§20) | code-split charts, perf dashboards | GATE-4 — *may start at P4 exit* |

### 14.1 Per-tool release checklist (all five releases)

Source implementation · OpenAPI fragment in `app/Modules/Jyotish/Http/openapi.yaml` + `contracts:export --check` green · mathematical specification section · golden tests · boundary tests · property invariants · performance benchmark recorded · error/warning code definitions · versioned rule table with source citation · audit metadata · operator runbook · **explicit unsupported-case handling** · `verification_status` set.

> A tool is **not** promoted because its API returns HTTP 200. It is promoted when its own acceptance suite passes.

### 14.2 Task Definition of Done (inherited verbatim from the repo methodology)

1. All ACs pass in CI (not locally-only). 2. Pint, PHPStan (no new baseline), Deptrac green. 3. `module.json` updated for any permission/scope/event/navigation change; `module:list` reflects it. 4. OpenAPI fragment updated and contract tests green. 5. Audit coverage for every new state change. 6. No cross-module joins or non-contract imports. 7. Documentation delta landed — the domain section cited by the task matches implemented behaviour (**doc drift is a review blocker**).

---

## 15. Companion artefacts to create (not part of this doc)

| Artefact | Path |
| --- | --- |
| Jyotish domain doc | `platform-navdhya/docs/14-domains/jyotish/jyotish.md` |
| ADRs 0079–0085 | `platform-navdhya/docs/21-adrs/` |
| Per-task specs | `platform-navdhya/docs/specs/tasks/JY/T-JY*.md` (9-part template) |
| Evidence reports | `platform-navdhya/docs/23-evidence/G-JYOTISH-R{0..4}-validation-report.md` |
| Practitioner review log | `platform-navdhya/docs/23-evidence/jyotish-ruleset-review.md` |
| Operator runbook | `platform-navdhya/docs/22-runbooks/jyotish-generation-build.md` |

---

## 16. Architecture verdict

Navdhya should not become a frontend over 50 external astrology APIs. The durable asset is a **deterministic calculation platform** whose kernel is already ~70 % of the way to being real in `app/Domain/Calendar` — and whose remaining risk is concentrated in the four defects this plan names (fake sunset backfill, no planetary ephemeris, insufficient lunar precision for a 1″ Lagna claim, and a domain with no HTTP surface).

Build in this order: **shared astronomical kernel → ruleset registry → validation framework → result envelope.** Only then the tools. Add an `EphemerisProvider` port so a licensed engine can be swapped in later without touching a single rule module — and decide the licensing question deliberately, not by accident of convenience.

Give Navdhya five connected components: **Calculation Engine · Tool Platform · Knowledge Engine · Practitioner Studio · Consumer Experience.** The success test is that tool 51 costs one ruleset, one registry row and one screen.

---
---

# PART II — CATALOGUE EXPANSION, AUTOMATION, REWARDS AND SMART OPTIMISATION

Sections 17–22 extend Part I. Nothing here changes the P0–P5 kernel design; Part II is only justified because the kernel is shared. **Every rule in §0.2 and every blocker list in §13 still applies.**

**Grounding findings that make Part II cheap rather than expensive (all verified in-repo):**

| Finding | Evidence | Consequence |
| --- | --- | --- |
| **A full loyalty/points ledger contract already exists in TypeScript — with no server behind it.** `LoyaltyPointsEntry` (`EARN/REDEEM/EXPIRE/REVERSE/ADJUST`, `points`, `sourceType`, `sourceId`, `policyVersion`), `LoyaltyAccount` (`programId`, `programVersion`, `ACTIVE/SUSPENDED/CLOSED`), `rebuildPointsBalance()` as an event-sourced reducer, plus a referral state machine `CREATED→CLAIMED→QUALIFIED→REWARDED/EXPIRED/CANCELLED` with `canQualifyReferral()` | `packages/commerce/src/domain/loyalty.ts` (53 lines); **zero** `loyalty` matches anywhere under `platform-navdhya/app` or `database` | The reward layer is *implement the server side of a contract the frontend already declares* — not invent a subsystem |
| **Referral rewards were deliberately deferred.** The schema comment reads verbatim: *"Reward rules are explicitly out of scope — this wave is attribution only."* | `app/Modules/Customers/Database/Migrations/2026_10_01_000010_create_referral_codes_table.php`, plus `2026_10_01_000011_create_referral_attributions_table.php` | §19 lands in a pre-declared seam; `referral_attributions` is the trigger source |
| **A complete, working devotee automation engine exists and is never run.** `customers:process-reminders` implements the whole pattern — tenant iteration, version-partitioned `Cache::remember`, astronomical anchor resolution, `advance_days = [7,3,1,0]` slots, `last_fired_period` idempotency, `NotifiesCustomers::notify(eventId:)`, and a `FactAppender` outbox fact | `app/Modules/Customers/Console/ProcessRitualRemindersCommand.php` (155 lines). `routes/console.php` is **39 lines total** and schedules only `cleanup:expired`, `passport:purge`, `outbox:drain`, `booking:reap-expired-holds`, `verification:expire`, `privacy:purge-expired`, `booking:scan-missing-arrivals`, `support:scan-grievance-slas`, `payments:reconcile-unknown` — **no reminders, and no `calendar:build/validate/activate/replay/verify-oracle` either** | §18's first job is four small fixes then a scheduled line — see **D-12, D-13, D-14** in §1.2, which block JY-AUT-01. Today the whole Panchang generation pipeline and every ritual reminder depend on a human typing commands, and the engine that would run is one width-overflow and one hardcoded coordinate away from being safe to schedule |
| **Outbox facts are the existing event bus**, and a tamper-evident append pattern already exists beside it | `app/Modules/Customers/Outbox/FactAppender.php` → `Outbox::append($tenantId, 'customer_profile', $userId, $eventName, $payload)`; docblock: *"AuditWriter preserves tamper-evident staff compliance logs with hash chains. FactAppender appends domain events … to drive asynchronous projections, telemetry ingestion, and notification daemons."* | Reward accrual is *just another outbox consumer* (§19.2); no new queue |
| **The "smart" TypeScript packages are stubs — but a real recommender already exists in PHP.** `packages/analytics` = 88 files / **12,331 lines** and genuinely wired (`apps/web/app/api/ae/v1/batch/route.ts`, `services/workers/analytics-collector`). `packages/recommendations` (59 lines), `personalization` (57), `experimentation` (49) and `attribution` (14) are thin unimported ports. **Meanwhile `app/Modules/Recommendations/Services/` ships `ThompsonSamplingBandit`, `MmrReranker`, `PersonalizedPageRank`, `MarkovTransitionModel`, `PrefixSpanMiner`, `RrfCandidateCombiner`, `IpsEvaluator`, `CollaborativeFilteringAggregator`, `FraudGuard`, `ReasonCodeGenerator` over `reco_*` tables with public `POST /discovery`, `/impressions`, `/interactions` routes.** | line counts + `app/Modules/Recommendations/{Services,Database/Migrations/2026_11_12_000001..000004,Routes/api.php}` | §20 and §19 reuse the **PHP** recommender (and its `FraudGuard` for reward anti-abuse, `IpsEvaluator` for offline measurement) instead of the dead TS ports. Personalisation is available today; no new engine needs to be promised |
| **Budget-ratchet enforcement is an established technique here** | `tests/Architecture/ScopeExitBudgetTest.php` — *"Monotonic ceiling (`LEGACY_CEILING = 54`) on legacy sites"*, *"ZERO in HTTP controllers"*, *"every `PlatformScope::run(...)` specifies a non-empty reason"*. `tests/Architecture/QueryPlanTest.php` — *"New hot queries get added here, not reviewed by hope"*, EXPLAINs each query shape per driver | §20 reuses the ratchet for *compute cost*, not just query plans |
| **The cache/queue plumbing is multi-store but nothing is switched on.** `config/cache.php`: `default = env('CACHE_STORE','database')`, stores available `array, database, file, memcached, redis, octane, null` — **the `octane` entry is framework boilerplate; Octane and Horizon are not installed** (`composer.json` has four runtime deps: framework, passport, tinker, otphp). `config/queue.php`: `default = env('QUEUE_CONNECTION','database')`, connections `sync, database, beanstalkd, sqs, redis`. **No APCu anywhere.** | `config/cache.php`, `config/queue.php`, `composer.json`, `vendor/laravel/` | Tiered caching in §20 needs a **configuration decision plus a deploy note**, not new infrastructure — but it also starts from near-zero, because there is not one `Cache::remember` call under `app/Domain` today |
| **PHP-FPM measurement discipline is already written down** | `platform-navdhya/docs/seo-content/SPEC.md`: *"Measured under PHP-FPM with real OPcache and worker limits, cold and warm reported separately … the reason CLI timings in this module are advisory only"*; capacity gates **G-CAP-0** (row-scale projection) and **G-CAP-1** (annual `calendar:build --year` wall clock); budget *"Uncached single-day compute 500 ms"* | §20's budgets are stated in the same units and inherit the same gates instead of inventing a parallel standard |
| **Mass page generation is already scripted** | `scripts/generate-seo-v2.ts`, `generate-delhi-expansion-pages.ts`, `generate-vedic-education-pages.ps1`, `generate-hindu-ceremony-pages.ps1`, `generate-expanded-seo-batches.ps1`, `backfill-speakable-faqs.ts`, `rebuild-content-registry.ts`, `normalize-seo-catalog.ts`, `qa-seo-v2.ts` + root `sync:registry` / `seo:*` npm scripts | 80 new tool pages (§17) ride an existing generator pipeline, not a new one |

---

## 17. Catalogue expansion: JY-051 … JY-130

Part I defines JY-001…050 (spec) and maps 24 product families. Part II adds **80 further tools**. They are grouped by *the kernel capability they consume*, because that is what determines cost. Every one of them is reachable with the P0–P5 kernel plus a versioned ruleset — **none requires an edit to `Astronomy/` or `Ephemeris/`**, which is the point of §2.2.

### 17.1 Column meanings

* **Depends on** = the shared nodes it reads. If a tool needs a node not listed in Part I, that is called out as a *kernel delta* and is a review blocker until approved.
* **Verifies via** = the acceptance route: `golden` (external reference), `invariant` (property test), `practitioner` (ruleset sign-off per §11), `derived` (no new math — proven by construction from already-verified nodes).

### 17.2 Family AA — Birth-data intelligence and uncertainty (JY-051…060)

| ID | Tool | Depends on | Verifies via | Phase |
| --- | --- | --- | --- | --- |
| JY-051 | Birth-Time Uncertainty Propagator — recompute Lagna/Rashi/Nakshatra across ±σ, return a distribution instead of a false-precision answer | JY-001, JY-003, JY-009 | invariant (monotone spread in σ) | P7 |
| JY-052 | Rectification Candidate Scorer — rank candidate times by fit to a devotee-supplied life-event list | JY-051, JY-033, JY-036 | practitioner | P7 |
| JY-053 | True Local Solar Time view (display layer only; never mutates stored UTC) | JY-002, JY-024 | derived | P7 |
| JY-054 | High-Latitude / Polar Fallback Handler — declared substitute conventions when the ascending point is undefined | JY-009, `HorizonStatusEnum` | golden | P7 |
| JY-055 | Regional Era Converter — Vikram Samvat, Shaka Samvat, Kollam, Bengali, Nepala Sambat, Gujarati, Sundi | JY-022, JY-029 | golden per era | P7 |
| JY-056 | Ayanamsa Delta Comparator — per-body, per-date offset table across Lahiri/Raman/KP/FF | JY-004 | derived | P7 |
| JY-057 | Timezone Provenance Auditor — reports tzdb version + offset used for any stored chart | JY-001, `jyotish_places.tzdb_version` | invariant | P7 |
| JY-058 | Relocation Chart — same instant, new coordinates | JY-003, JY-009 | golden | P7 |
| JY-059 | Partial-Date Band Calculator — year-only or month-only birth data yields bounded results | JY-001, JY-007 | invariant | P7 |
| JY-060 | Birth-Data Quality Score — one 0–100 confidence figure surfaced on every chart | JY-060 inputs = 001/051/057 | invariant | P7 |

**Kernel delta:** none. JY-051 and JY-052 are the only two that add real compute cost; both are budgeted in §20.4.

### 17.3 Family AB — Special Lagnas and chart geometry (JY-061…072)

JY-061 Bhava Lagna · JY-062 Hora Lagna · JY-063 Ghati Lagna · JY-064 Indu Lagna · JY-065 Varnada Lagna · JY-066 Sri Lagna · JY-067 Tri Shadi Lagna · JY-068 Rudra Lagna · JY-069 Pranapada Lagna · JY-070 Bhru Kuwari Lagna · JY-071 Dhatr Lagna · JY-072 Vimsopaka Lagna.

All twelve consume the same `EphemerisSnapshot` + `SiderealTime` + `Horizon` trio (JY-003, JY-009) plus one ruleset row each. **Verifies via** `practitioner` + `golden`. One implementation serves all twelve: a `SpecialLagnaInterface { compute(snapshot, natalD1, profile): float }` resolved by registry `tool_id`, so the twelfth costs one class, not one sprint. Phase P7.

### 17.4 Family AC — Upagrahas and sensitive points (JY-073…084)

JY-073 Dhuma · JY-074 Vyatipata · JY-075 Parivesha · JY-076 Indrachapa · JY-077 Upaketu · JY-078 Kaala · JY-079 Mrityu · JY-080 Artha Praharaka · JY-081 Yama Gandaka · JY-082 Gulika / Mandi (day-division derived) · JY-083 Lagna-Karaka · JY-084 Kama Karaka.

Twelve derived points, each a closed-form function of already-computed longitudes or day divisions (`Ketu = λ_Rahu + 180°` is the precedent). **Verifies via** `invariant` (fixed offsets/relations) + `golden`. Phase P7. No kernel delta.

### 17.5 Family AD — Divisional and Varga extensions (JY-085…092)

JY-085 D81 Nava-Navamsa · JY-086 D108 · JY-087 D144 · JY-088 D48 Shadashtaka · JY-089 Varga-of-Varga explorer (e.g. D9 within D10) · JY-090 Vargottama census across all supported Vargas · JY-091 Varga Devata mapping · JY-092 Varga Gochara (transit into divisional signs).

Extends the T-JY04-07 rule-module registry. **These stay labelled *extended*, never merged into the core Shodasha set** — the source catalogue is explicit that universal acceptance differs. **Verifies via** `golden` per division. Phase P6/P7.

### 17.6 Family AE — Strength, Phala and Ashtakavarga depth (JY-093…100)

JY-093 Ishta Phala · JY-094 Kashta Phala · JY-095 Kakshya-level Ashtakavarga (150 Kakshas) · JY-096 Drig Bala degree-precise variant · JY-097 Shadbala in Rupas + relative-to-minimum normalisation · JY-098 Saptavargaja component table explorer · JY-099 Bhinna-of-Navamsa Ashtakavarga · JY-100 Planetary strength heatmap across chart × Varga × time.

**Verifies via** `golden` + `invariant` (totals equal components — the same check as AC-T-JY08-01). Phase P7.

### 17.7 Family AF — Timing-system breadth (JY-101…108)

JY-101 Ashtottari-108 · JY-102 Yogini-36 · JY-103 Kalachakra (8 and 12 variants) · JY-104 Mandooka · JY-105 Dwadashottari-143 · JY-106 Shodashottari-176 · JY-107 Panchottari-132 · JY-108 Shatabdika-175.

Each is one ruleset + one sequence module under the T-JY07-05 contract. **Hard rule inherited:** a system's sequence array must be provably distinct from Vimshottari's, and `Chara` can never borrow Vimshottari progression (AC-T-JY07-05). **Verifies via** `golden` + `practitioner`. Phase P6.

### 17.8 Family AG — Panchang and electional completeness (JY-109…116)

JY-109 Kshaya Masa detector (mirror of the existing `AdhikaMasaDetector`) · JY-110 Rikta Yoga / Mrityu Yoga day flags · JY-111 Panchaka windows · JY-112 Amit Ghadi · JY-113 Varjyam · JY-114 Dur Muhurta · JY-115 Bhadra / Nishechita evaluation · JY-116 Lagna Shuddhi and Tithi/Vara/Nakshatra Shuddhi scoring primitives.

All are interval computations over already-solved boundaries — pure `IntervalAlgebra` consumers, so they are nearly free. **Verifies via** `invariant` (gapless, non-overlapping coverage of the local day) + `golden`. Phase P6 (they also feed JY-030's constraint set).

### 17.9 Family AH — Compatibility depth (JY-117…122)

JY-117 Rajju matcher · JY-118 Vedha matcher · JY-119 Mahendra Porutham · JY-120 Stree Deergha · JY-121 Dina / Chida poruthams · JY-122 D9-based spouse-indicator comparison.

Independent evaluators under T-JY08-08. **The rule that matters:** a couple evaluated under Ashtakoota and under Porutham yields two rows and two UI columns; **no code path may add them** (enforced by the AC-T-JY08-08 architecture test). Phase P6.

### 17.10 Family AI — Adjacent Indian knowledge systems (JY-123…128)

JY-123 Numerology suite (Life Path, Birth, Destiny, Name/Chaldean, Lo Shu grid) · JY-124 Name-compatibility numerology · JY-125 Business-name numerology · JY-126 Vastu Purusha Mandala generator · JY-127 Plot-orientation / entrance-direction assessor · JY-128 Room-placement annotator.

**Separate namespaces and separate rulesets** (family Z in Part I). They must not silently borrow Jyotish ayanamsa or ephemeris. Phase P6.

### 17.11 Family AJ — Platform, governance and developer tools (JY-129…130)

JY-129 Divergence Explorer — side-by-side result of the same input under two profiles/generations, with the delta attributed to a named field · JY-130 Reproducibility Prover — given a `report_id` or `calculation_id`, re-execute the exact stored version chain and attest byte-equality.

JY-130 is the machine proof of §11.2 and is the gate every other release leans on. Phase P5.

### 17.12 The tool recipe — why tool 51 is cheap

A new tool touches **exactly six places**; a task touching a seventh (anything under `Astronomy/` or `Ephemeris/`) is a design smell requiring reviewer sign-off, per AC-T-JY10-ALL:

| # | File | Content |
| --- | --- | --- |
| 1 | `app/Domain/Jyotish/<Cap>/<Tool>.php` | pure function over `EphemerisSnapshot` + ruleset |
| 2 | `JyotishToolRegistry::TOOLS` row | `spec_id`, permission, schemas, `verification_status` |
| 3 | `jyotish_rulesets` seed row (or code const) | versioned rule data + `source_reference` |
| 4 | `app/Modules/Jyotish/Http/openapi.yaml` fragment | operation + `contracts:export --check` |
| 5 | `packages/contracts/src/api/jyotish.ts` DTO | hand-written, mirroring existing `api/*.ts` style |
| 6 | one tool page: `apps/web/app/resources/tools/<slug>/{page.tsx,client.tsx}` + BFF `app/api/jyotish/<slug>/route.ts` | `seoManager.generateMetadata` + `generateSchema` exactly as `resources/tools/page.tsx:1-25` already does, `LayoutOrchestrator` shell, `laravelEnabled()` mock-mode fallback |

Tests per tool are generated from the same registry row (a data-driven `tests/Unit/Jyotish/ToolContractTest.php` walks all 130 `TOOLS` and asserts schema, permission, ruleset source and `verification_status` are present and non-placeholder), so coverage cannot rot as the catalogue grows.

---

## 18. Automation layer (JY-AUT-01 … JY-AUT-26)

The goal is *ease*: the devotee should get the right ritual, at the right muhurat, in the right place, booked and reminded, with as little typing as possible — and the platform should keep its own astronomical data correct without an operator intervening.

### 18.0 Prerequisite corrections — do not schedule what is broken

The headline of §18 was "the reminder engine already exists, just schedule it." On verification that is **not safe yet**: four defects sit in front of it (D-12…D-15), and one sits in front of §19 (D-16). Scheduling an engine with an overflowing dedup key would convert a latent bug into a mass duplicate-notification incident — which is worse than the current dormant state.

| Task | Fix | Est | Blocking |
| --- | --- | --- | --- |
| **T-JYAUT-00a** | Widen `last_fired_period` to `string(191)` in a new migration; better, stop hand-rolling dedup and reuse the **existing delivery-level claim**: `communication_deliveries` has `dedup_key(160) NOT NULL` with `unique(tenant_id, dedup_key)` (migration `2026_11_14_000001_evolve_communication_deliveries_table.php`) — that constraint *is* the idempotency mechanism, already production-shaped | S | JY-AUT-01 |
| **T-JYAUT-00b** | Make `AnchorOccurrenceResolver` place-aware: pass the devotee's resolved `jyotish_places` lat/lon instead of the hardcoded `23.1765, 75.7885` at lines 44–45 / 69–70 (same coordinate pair the `PlaceResolver` seeds as `reg_ujjain_central`) | M | JY-AUT-01, 14, 15, 22 |
| **T-JYAUT-00c** | Replace `MAX_LOOKAHEAD_DAYS = 400` + `if ($candidateDate->year > 2030) throw` with a lookup into the precomputed `jyotish_astronomical_events` generation (which JY-AUT-02 keeps rolling forward), so the engine has no 2030 cliff | M | JY-AUT-01, JY-AUT-02 |
| **T-JYAUT-00d** | Give `rules_version` the behavioural branch ADR-0035 promised: v1 anchors evaluate under the v1 compatibility path (or are surfaced for upgrade) rather than sharing the v2 code path with only a different cache key | M | JY-AUT-01, B-7 |
| **T-JYAUT-00e** | Add outbox durability before making rewards depend on it: a `outbox_dead_letters` table, an `outbox:replay` command (accepting `--id` or `--all-exhausted`), and **per-consumer cursors** so one poison consumer cannot make every consumer re-run for a row. Today `attempts >= 5` rows are reported `exhausted` and stay pending forever | L | all of §19 |

**Revised gate:** JY-AUT-01 may not ship until 00a–00d are green; §19 may not start until 00e is green. The reminder engine is otherwise genuinely well-built — `NotificationConsentGateService` already maps `REMINDER`/`PANCHANG_ALERT`/`RITUAL` → `PURPOSE_RITUAL_REMINDERS`, applies the transactional exemption correctly, and checks `PreferencesReader::isChannelEnabled()` — that part needs no work, only to be reused.

### 18.1 The one automation primitive (do not build a second one)

Every automation below is the loop already proven in `ProcessRitualRemindersCommand`, generalised — with the two fixes above applied:

```
for tenant in ACTIVE:                     # TenantContext->set(...) / finally clear()
  key = <scope>:<version-partition>:<day>:<anchor_hash>   # profile + ephemeris + ayanamsa + rules_version
  value = Cache::remember(key, ttl, fn)   # never recompute per user
  if ConditionEvaluator->evaluate(trigger_conditions, value)[0]:      # REUSED, not re-invented
      ConsentGate->canDispatch(userId, category, channel) or continue  # REUSED, fail-closed
      NotifiesCustomers->notify(eventId: period_key)   # dedup claim = communication_deliveries unique(tenant_id, dedup_key)
      FactAppender->append('<domain>.<event>', userId, payload, tenantId)
```

**Do not write a new rules or condition engine.** Two generic evaluators already exist and are the right homes for declarative automation and reward conditions:

| Existing component | What it gives §18/§19 |
| --- | --- |
| `App\Platform\Authz\ConditionEvaluator::evaluate(array $conditions, array $attributes): array{0: bool, 1: failures[]}` | whitelist-bounded field/operator grammar, returns per-condition failure detail — exactly the "reason trace" Part I demands of Muhurat and Yoga results |
| `App\Platform\Authz\PolicyEvaluationEngine::evaluate(AuthorizationRequest, bool $bypassCache)` + `DecisionCache` | a six-stage fail-closed pipeline with generation-based invalidation, i.e. the pattern for reward eligibility checks |
| `App\Domain\Verification\Policy\PolicyVersionResolver` + `Models\VerificationPolicyVersion` | **the in-repo precedent for `policyVersion`**, which §19.1 needs for loyalty policies — resolve-before-write, never mutate in place |
| `App\Modules\Temple\Application\TempleApprovalService` (`implements ManagesTempleApprovals`), `temple_booking_approvals` (`state` PENDING/APPROVED/DECLINED/ESCALATED/EXPIRED, `required_role`, `sla_expires_at`, `hard_expires_at`, `decision_reason(500)`) + `Console\ProcessTempleApprovalSlaCommand` | a complete approval-with-SLA queue, so JY-AUT-26's quality-triage queue is a **copy of an existing shape**, not a new subsystem |
| `App\Modules\Recommendations\Services\FraudGuard` | anti-abuse signal already built for a different purpose; §19.5 reuses it rather than writing a second fraud detector |

New engines implement `App\Domain\Jyotish\Contracts\AutomatesWorkflow` (`scopeKey()`, `triggerSlots()`, `evaluate()`) and register a **scheduled** command. Anything not registered in `routes/console.php` does not exist — that is the honest lesson of the dormant reminder command, and the reason AC-T-JYAUT-ALL exists.

**Declared event inventory (real constraint):** the 15 `module.json` files declare ~118 events, including `customers.reminder.fired`, `customers.referral.attributed`, `customers.consent.changed`, `customers.spiritual_profile.updated`, `customers.family_member.updated`, and `recommendations.feed.generated`. **None is reward-, loyalty- or purchase-milestone-related**, and only **4 outbox consumers are registered** (`booking.lifecycle-notifier`, `customers.lifecycle-bridge`, `feedback.invitation-producer`, `vendor.lifecycle-notifier`). §19 therefore adds both new event names to `module.json` and new handler registrations — a forgotten manifest entry is the failure mode this repo's `ModuleShapeTest` already catches.

### 18.2 Platform self-maintenance (must be scheduled; currently manual)

| ID | Automation | Cadence | Mechanism |
| --- | --- | --- | --- |
| JY-AUT-01 | **Schedule `customers:process-reminders`** — ship the dormant engine | `dailyAt('06:15')` Asia/Kolkata, `withoutOverlapping(30)` | one line in `routes/console.php` — **but only after T-JYAUT-00a…00d land**; the command is already registered at `Customers/ModuleServiceProvider.php:72-73`, it is simply never invoked by anything except its two tests |
| JY-AUT-02 | **Ahead-of-time Panchang generation** — build year Y+1 into a `building` generation on a rolling schedule, then `validate → verify-oracle → activate` | weekly; and `0 3 15 11 *` for the annual cutover | `calendar:build --year=Y+1` → `calendar:validate` → `calendar:verify-oracle` → `calendar:activate` (atomic swap already in `GenerationManager`) |
| JY-AUT-03 | **Generation watchdog** — alert if the active generation's horizon ends in fewer than N days | hourly | `jyotish_generations` scope check → `notifications` |
| JY-AUT-04 | **tzdb / IANA refresh detector** — record `tzdb_version` drift and flag affected `jyotish_places` rows | monthly | diff against installed zoneinfo; emits `jyotish.tzdb.drift_detected` |
| JY-AUT-05 | **Ruleset staleness sweep** — every active ruleset older than its review interval is demoted from `practitioner_reviewed` to `due_for_review` | monthly | `jyotish_rulesets` + `jyotish_ruleset_audit` |
| JY-AUT-06 | **Place-catalogue growth** — ingest/verify new `jyotish_places` rows on demand and backfill coordinates (extends B-3) | daily | `GeocodingProvider` port + `is_verified=false` quarantine until reviewed |
| JY-AUT-07 | **Boundary fuzz-nightly** — random instants near every tithi/nakshatra/yoga/karana boundary, assert solver bracket + residual | nightly | new `e2e/jyotish-boundary-fuzz.spec.ts` **plus its addition to the `testMatch` regex** |
| JY-AUT-08 | **Cross-engine divergence probe** — re-check a rotating 0.5 % sample of stored charts against the offline oracle vectors | nightly | `calendar:verify-oracle` sampling mode → `docs/23-evidence/` |
| JY-AUT-09 | **Performance ratchet reporter** — publish p50/p95/p99 + `peak_mem_bytes` + `ephemeris_calls` deltas week-over-week | weekly | reads `jyotish_computation_log` (§20.6) |
| JY-AUT-10 | **SEO/registry resync for tool pages** — regenerate tool pages, `sync:registry`, `seo:normalize`, `qa-seo-v2` | on deploy touching `resources/tools` | existing `scripts/*` (already npm-wired) |

### 18.3 Devotee "ease" automations

| ID | Automation | Trigger | Notes |
| --- | --- | --- | --- |
| JY-AUT-11 | **One-tap Kundli from saved birth data** — the devotee's own chart needs zero form input | profile has a `jyotish_birth_profiles` row | family AA makes the result honest about uncertainty |
| JY-AUT-12 | **Auto chart on birth-data entry** — computing Rashi/Nakshatra immediately on save, so the profile shows derived values, not typed ones (B-4/B-6 UX) | `jyotish_birth_profiles` insert | |
| JY-AUT-13 | **Ambiguity resolution nudge** — for multi-match birthplaces or DST folds, ask one targeted question instead of guessing (JY-001) | `422 ambiguous_*` | the *anti*-automation guard: never silently pick |
| JY-AUT-14 | **Festival-aware personal day list** — today's Panchang plus only the festivals this devotee's tradition + place observes | daily, per devotee | reuses JY-029 rule profiles + JY-AUT-01's slot machinery |
| JY-AUT-15 | **Dasha-change and Sade-Sati entry/exit alerts** | outbox fact from JY-AUT-02 generation build | boundary-driven, never calendar-table-driven (AC-T-JY07-08) |
| JY-AUT-16 | **Muhurat → booking handoff** — pick an auspicious window, get the matching puja + available pandit in one flow; `calculation_id` recorded on the draft | user action | extends `apps/web/lib/booking-handoff/handoff-url.ts` and T-JY07-12 |
| JY-AUT-17 | **Family batch operations** — one action computes charts/reminders for every member in `customer_family_members` | user action | **one snapshot per member, not per tool** (§2.2) |
| JY-AUT-18 | **Annual report auto-generation** on the devotee's solar-return birthday | JY-050 solar return | async job; PDF to object storage; delivery through `NotifiesCustomers` |
| JY-AUT-19 | **Reminder self-healing** — when a rules_version bump shifts an anchor, offer the devotee a choice rather than moving the date silently (B-7, ADR-0035) | generation activation | consent-preserving; never auto-mutate |
| JY-AUT-20 | **Smart default place** — derive the working location from device time zone + serviceable region, confirmable in one tap | session start | uses `packages/geography` serviceability, no coordinate trust from the client (`PlaceResolver` closes P-14) |
| JY-AUT-21 | **Sankalp pre-fill** — generate the ritual Sankalp text from gotra, nakshatra, place, date and tradition | booking confirm | `CANONICAL_GOTRAS` + `jyotish_birth_profiles` |
| JY-AUT-22 | **Vrat fast planner** — month view of Ekadashi/Pradosh/Sankashti with the devotee's Paran rule applied | monthly | JY-029 output, cached per profile |

### 18.4 Practitioner and ops automations

| ID | Automation | Notes |
| --- | --- | --- |
| JY-AUT-23 | **Batch generators** — client-folder kundli set, compatibility set, Panchang set, bulk PDF; async with per-item status | outbox + `jyotish_batch_items`; **not** `Bus::batch` (no precedent in this repo) |
| JY-AUT-24 | **Evidence pack builder** — every finding in a report gets its ruleset version, source citation and conditions, assembled automatically | the machine form of §11.1 |
| JY-AUT-25 | **Consultation prep** — before an appointment, precompute the client's chart + active dashas + transits so the practitioner opens on answers, not loads | warm cache keyed by the profile (§20.2) |
| JY-AUT-26 | **Quality triage queue** — any result whose `verification_status` is `disputed`, or whose oracle probe diverged, is routed to a reviewer queue before further serving | **Copies the existing `temple_booking_approvals` shape** — `TempleApprovalService implements ManagesTempleApprovals`, states PENDING/APPROVED/DECLINED/ESCALATED/EXPIRED, `required_role`, `sla_expires_at`/`hard_expires_at`, `decision_reason(500)`, driven by `ProcessTempleApprovalSlaCommand`. Same queue, same SLA reaper pattern, new domain |

### 18.5 Automation safety rules

1. **Every automation is idempotent** by a deterministic `period_key`, exactly as `last_fired_period` does today; re-running a day must produce zero duplicate notifications.
2. **Every automation is tenant-iterating with explicit `TenantContext` set/clear in a `finally`** — the existing shape; no cross-tenant leakage (GATE-1).
3. **No automation may infer missing birth data.** An absent time stays absent; the result degrades to a bounded answer (JY-051/059) rather than a silent default.
4. **No automation sends fear-based content.** Messaging is governed by §10.3-4 and §11.3; a Dosha notification must carry its ruleset and never an outcome promise.
5. **Every automation must be observable**: it emits an outbox fact, records `last_run_at`/`last_fired_period`, and is listed in a `jyotish:automations` status command.
6. **Kill switch**: each automation is gated by a fail-closed feature flag so ops can stop one without stopping the scheduler.

### 18.6 Acceptance criteria

- AC-T-JYAUT-00a The longest producible period key fits `last_fired_period` with ≥ 25 % headroom (a test builds the key from a real 26-char ULID and asserts `strlen <= column_width`), **and the assertion is executed against a driver that enforces width** — running it only on SQLite proves nothing, so the case is added to the MySQL suite and the migration's declared length is read from schema, not hardcoded.
- AC-T-JYAUT-00b Two devotees with identical `anchor_rule` JSON but different resolved places produce **different** computed occurrence dates wherever the rule depends on local sunrise; a test asserts they are not byte-identical.
- AC-T-JYAUT-00c No reminder date, however far ahead, raises the year-2030 exception: a fixture dated 2038 resolves from the precomputed generation. `grep` gate: `MAX_LOOKAHEAD_DAYS` no longer appears in any runtime path.
- AC-T-JYAUT-00d A v1 anchor and a v2 anchor with the same logical content evaluate down **different code paths**, proven by a test that asserts a distinguishable `rules_version` in the result envelope — closing the gap between ADR-0035 §2's promise and the implementation.
- AC-T-JYAUT-00e A handler that throws on every attempt lands in `outbox_dead_letters` within `MAX_ATTEMPTS`, is visible to `outbox:replay --list-exhausted`, and replays successfully once the cause is fixed. A test forces failure, then asserts the row is neither silently dropped nor re-run against the 3 healthy consumers.
- AC-T-JYAUT-01 `php artisan schedule:list` shows `customers:process-reminders`; running it twice for the same UTC day produces **exactly one** notification per reminder per slot (asserted by a Feature test **on the MySQL driver**, per AC-T-JYAUT-00a's reasoning).
- AC-T-JYAUT-02 After the annual generation automation completes unattended, `calendar:validate` reports gapless coverage for all 366 days and `calendar:replay` reports zero divergence — with no human in the loop.
- AC-T-JYAUT-02b A generation that fails `verify-oracle` is **never activated**; the command exits non-zero and the prior generation keeps serving.
- AC-T-JYAUT-05 Any ruleset past its review interval is served with `verification_status = due_for_review` and its UI chip changes; a test asserts the demotion fires.
- AC-T-JYAUT-11..22 Each automation is proven by a Feature test asserting (a) the trigger fact, (b) the notification payload, (c) idempotency on replay, and (d) the negative: with its flag off, nothing happens.
- AC-T-JYAUT-16 The booking draft created from a Muhurat window stores the originating `calculation_id`, and the muhurat row references the draft — a two-way traceability test.
- AC-T-JYAUT-19 When a rules_version bump changes a reminder's computed date, the devotee receives an explicit before/after notice and the stored date does **not** change until acknowledged (extends `StoredAnchorMigrationTest`).
- AC-T-JYAUT-23 A 500-chart batch completes with per-item status, and peak worker RSS stays inside §8 limits.
- AC-T-JYAUT-ALL `grep` gate: no new command exists in `app/**/Console/` that is absent from `routes/console.php` or `jyotish:automations` — **no more silent engines.**

---

## 19. Reward layer (JY-RWD-01 … JY-RWD-16)

Rewards exist to build *practice*, not to farm spend. The design constraint that governs this whole section: **a reward may never be contingent on believing or acting on an astrological claim.** Nobody earns points for buying a gemstone, and nothing unlocks because a Dosha was found.

### 19.1 Ledger first (implement the contract that already exists)

The TypeScript contract in `packages/commerce/src/domain/loyalty.ts` is adopted **unchanged** as the wire format, so the frontend needs no new model:

| TS declaration | Server-side realisation |
| --- | --- |
| `LoyaltyAccount { id, customerId, programId, programVersion, status }` | `loyalty_accounts` (tenant_shared) |
| `LoyaltyPointsEntry { id, accountId, type: EARN/REDEEM/EXPIRE/REVERSE/ADJUST, points, sourceType, sourceId, policyVersion, createdAt }` | `loyalty_entries` — **append-only, never updated or deleted** |
| `rebuildPointsBalance(entries)` | the authoritative balance is a **projection**, not a column some code path forgets to update; a cached `balance` column is reconciled by replay and asserted equal in tests |
| `Referral` state machine + `canQualifyReferral()` | reuses the existing `referral_codes` / `referral_attributions` tables, filling exactly the gap their comment declared out of scope |
| `policyVersion` | a `jyotish_rulesets`-style **versioned reward policy**, so a policy change never retro-renumbers history |

`loyalty_policies` (central) holds versioned accrual rules as declarative data, using the `FestivalRuleRegistry` const → DB pattern, with `source_reference` replaced by `approved_by` + `effective_from` + `caps_json`.

### 19.2 Accrual: rewards are an outbox consumer

No code path grants points directly. A `LoyaltyAccrualHandler` registers against `ModuleServiceProvider::bootModule()` and consumes facts already emitted by the platform, which makes accrual testable by emitting a fact and asserting the resulting ledger entry:

| Fact | Earns | Rule |
| --- | --- | --- |
| `jyotish.chart.completed` | small, once per distinct birth profile | **not** per request — otherwise the reward is for compute, which would fight §20 |
| `customers.reminder.fired` → acknowledged | yes | practice, not purchase |
| `jyotish.vrat.completed` / japa session finished | yes, capped per day | streak-friendly, never streak-shaming |
| `jyotish.report.generated` | modest, first-per-period | guards against report spam |
| `jyotish.muhurat.converted_to_booking` | yes, on completed+settled booking only | **earned at settlement, not at click**, so it cannot reward fear-driven conversion |
| `referral.qualified` (via `canQualifyReferral()`) | yes, both sides | requires the referee's own completed action |
| `jyotish.feedback.submitted` / practitioner-review contribution | yes | grows the golden corpus and the review backlog (§9.2) as a side effect |
| `jyotish.data.corrected` (devotee fixed a wrong birth entry) | yes | **incentivises accuracy, which is the single most valuable thing this platform can buy** |
| anything Dosha-related | **zero** | hard-coded exclusion; a test asserts no Dosha `sourceType` can accrue |

**Three constraints §19 inherits from the code it sits on:**

1. **Every reward notification passes `NotificationConsentGateService::canDispatch($userId, $category, $channel)` — and reward messaging is *marketing*, not transactional.** That service maps `REMINDER`/`PANCHANG_ALERT`/`RITUAL` → `PURPOSE_RITUAL_REMINDERS`, grants a `transactional_exemption` only for ACCOUNT/BOOKING/SECURITY/TRANSACTIONAL, and otherwise demands the newest `consent_ledger` row be `STATE_GRANTED` before checking `PreferencesReader::isChannelEnabled()`. A "you've earned 50 points!" nudge has no transactional exemption, so **the reward programme is consent-gated by construction and will legitimately be silent for users who declined marketing.** Design the UI for that, and never relabel a reward category as `BOOKING` to smuggle it through the exemption — the reason strings (`consent_missing_or_withdrawn_for_{purpose}`) make such a workaround visible in logs.
2. **Referral accrual must be reconciled against how attribution actually works.** `referral_attributions` declares `unique(referred_user_id)` (one referee can ever be attributed once) and `CustomerAuthController` **silently ignores** self-codes and invalid codes at issuance. Silent ignoring is acceptable for attribution and **unacceptable for rewards** — a user told "share to earn" who earns nothing because their own code was quietly dropped is a support ticket. §19 replaces the silent branch with an explicit `error.code = "self_referral_not_rewardable"` and a ledger-visible reason.
3. **New events must be declared in `module.json`.** No reward, loyalty or purchase-milestone event exists among the ~118 declared today, and only 4 outbox consumers are registered — so `loyalty.points.earned`, `loyalty.points.redeemed`, `loyalty.entry.expired` and the handler registrations are part of T-JYRW-03, not an afterthought.

### 19.3 Redemption catalogue

Points redeem against *access and service*, never against remedies: full-detail PDF reports, additional saved family birth profiles, practitioner consultation minutes, priority pandit scheduling slots, ad-free/dark-mode premium theming, early access to new tools, invoice credit on the devotee's own booking (see caveat below), and **daan donated on the devotee's behalf** — which is the only redemption that touches ritual spend, and is deliberately non-monetisable back to Navdhya margin.

Explicitly excluded from redemption: gemstones, rudraksha, yantra, puja samagri, and any "Dosha shanti" product.

**Redemption mechanics are already contracted, in the same dormant-commerce family as `loyalty.ts`** — adopt these rather than inventing a fourth model:

| Existing TS declaration | File | Use |
| --- | --- | --- |
| `Coupon`, `RedemptionLedger`, `redeemCoupon()` returning `COUPON_INVALID / EXPIRED / LIMIT_REACHED` | `packages/pricing/src/promotions/coupon.ts` | the redemption + ledger shape; `RedemptionLedger` and `loyalty_entries` share one append-only discipline |
| `Promotion`, `isPromotionActive`, `promotionEligible`, `promotionDiscount`, `selectPromotions`, `Stacking` = `EXCLUSIVE / NON_STACKABLE / STACKABLE` | `packages/pricing/src/promotions/promotion-engine.ts` | points-as-discount composition rules, so a points redemption and a campaign discount have defined precedence instead of an ad-hoc one |
| `Credit`, `CreditHold`, `reserveCredit()` | `packages/commerce/src/domain/credit.ts` | **the two-phase reservation §19.5 needs to make double-spend impossible**: hold, then settle or release |
| `rebuildWalletBalance()`, `canSpend()` | `packages/commerce/src/domain/wallet.ts` | same event-sourced reducer pattern as `rebuildPointsBalance()`, keeping points and prepaid value as distinct ledgers |

**Two corrections to the naive reading of "reuse existing tables":**

- **`platform_saas_billing` is B2B, not consumer billing.** Its three tables (`platform_subscription_plans`, `tenant_subscriptions`, `platform_invoices`) model *tenant* plans with `provider_subscription_id` and `status` ACTIVE/PAST_DUE/CANCELLED/TRIALING. There is **no customer-level subscription** anywhere. So "month-free plan credit" must be expressed as an **invoice credit against the devotee's own booking total** (`platform_invoices` is tenant-scoped and the wrong table to touch), not as a subscription perk.
- **`entitlement` already means two unrelated things here.** `App\Domain\Trust\Parties\PartyEntitlement::canViewScope(PartyRole, $visibilityScope, $hasSafetyCapability)` is *data visibility* (SCOPE_PUBLIC / PARTICIPANT / PROVIDER_REDACTED / REPORTER_PRIVATE / INTERNAL_STAFF / RESTRICTED_SAFETY) and `App\Domain\Delivery\Policies\RecipientEntitlementGuard` is *privacy scrubbing*; `packages/commerce/src/domain/entitlement.ts` is an unimported TS stub and **there is no entitlements table.** Reward gating therefore gets its **own** explicitly named concept — `loyalty_tiers` — and does not overload the word "entitlement" a fourth time. Tier *definitions* reuse the real `catalog_package_tiers` shape (`code` unique STANDARD/PREMIUM…, `sort`, `status`) plus the `..._000017_add_tier_axes_to_catalog_prices_table` axis pattern.

### 19.4 Tiers, streaks and badges

Tier from **practice signals combined, never from spend alone**; `PLAN` tier axes stay commercial and separate. A tier is a versioned policy row so demotions/promotions are explainable. Streaks use the existing `advance_days` slot concept (a "week of practice" is seven evaluated slots, not seven page-loads). Badges are earned by **breadth of accurate practice** — e.g. *first chart with exact birth time*, *ten vrat observed*, *one methodology comparison completed* — and each badge has a `source_policy_version`.

**Anti-dark-pattern requirements (product rules, not preferences):**
1. No countdown, expiry nag, or "points about to lapse" message attached to Dosha or remedy content.
2. No reward, badge, unlock or streak whose text implies an astrological outcome ("Level up your Manglik shield" is prohibited).
3. Expiry must be disclosed at accrual time in the ledger entry, not discovered later.
4. Balance, every earn and every burn is user-visible with `sourceType` + `policyVersion` — a devotee can always answer "why did I get this?".
5. Minors and sensitive-category data never feed reward scoring.

### 19.5 Integrity and abuse resistance

Reuse in-repo machinery instead of new machinery:

| Concern | Component that already exists | How §19 uses it |
| --- | --- | --- |
| Exactly-once accrual | `Idempotency-Key` + `App\Modules\Booking\Domain\CommandDeduplicator` — insert-then-catch `UniqueConstraintViolationException`, canonical payload hash, 24 h expiry, versioned `idempotency_hash_version` | accrual dedup on `(sourceType, sourceId, policyVersion)`, **plus** the delivery-level claim already enforced by `communication_deliveries` `unique(tenant_id, dedup_key)` — two independent layers, because D-12 proved one narrow column is not a guarantee |
| No double-spend | `reserveCredit()` / `CreditHold` in `packages/commerce/src/domain/credit.ts` | two-phase `:reserve` → `:settle` / `:release` around every redemption; concurrent reserves cannot both settle |
| Tamper evidence | `AuditWriter` discipline (its own docblock: *"preserves tamper-evident staff compliance logs with hash chains"*) | hash-chained `loyalty_entries`; a row cannot be edited without breaking the chain |
| Sybil / self-referral / velocity | `App\Modules\Recommendations\Services\FraudGuard` (production, wired to `reco_*` tables) and `ReviewIntegrityScanner` in `app/Modules/Feedback/Domain/Anomaly/` | reward-specific signal names added to the **existing** detectors rather than a parallel fraud stack; velocity caps reuse `throttle.tier` middleware |
| Honest measurement | `IpsEvaluator` (inverse propensity scoring) and the 31-test `packages/analytics` collector pipeline | reward-programme A/B and uplift claims use off-policy evaluation the platform already runs, instead of raw conversion comparisons that a streak banner would bias |
| Negative balance impossible | — (new, small) | `CHECK (balance >= 0)` on the projection plus reservation-before-redemption |

Reversals are real `REVERSE` ledger entries, never deletions or edits — which is also what makes a wrongly-awarded point explainable to the devotee who loses it.

**Naming hazard to avoid in implementation:** the token `discount` in PHP today means **only** a tax-line discount (`app/Modules/Tax/Domain\CommercialQuoteService`), and `promotion` means **only** RBAC tenant promotion (`app\Platform\Tenancy\TenantPromotion`). Introducing consumer "promotions" or "discounts" under those names will confuse both humans and `deptrac`-style greps; name the reward concepts `loyalty_*` and `reward_*` throughout.

### 19.6 Tasks

**Hard prerequisite: T-JYAUT-00e (outbox dead-letter + replay + per-consumer cursors) must be Done before T-JYRW-03 starts.** Without it, a reward grant that fails five times stays pending forever with no operator path to find or replay it — so a devotee's balance can silently under-count, and the first anyone hears about it is a complaint. Monetary-adjacent state must not sit on a bus that has no dead-letter handling.

Note also that the commerce contracts referenced in §19.3 (`loyalty.ts`, `credit.ts`, `wallet.ts`, `gift-card.ts`, `membership.ts`, `entitlement.ts`, `promotion-engine.ts`, `coupon.ts`) currently have **zero importers, zero tests and zero tables** — they are unexercised design intent. T-JYRW-02's job is partly to make them real, which means adding the first tests this repo has ever had for them; treat any surprise there as information, not as a stable dependency.

| Task | Deliverable | Est |
| --- | --- | --- |
| **T-JYRW-01** | `loyalty_accounts`, `loyalty_entries` (append-only + hash chain), `loyalty_policies`, `loyalty_redemptions` migrations + models with `BelongsToTenant`, `HasUlids`, `casts()` | M |
| **T-JYRW-02** | Ledger domain service implementing `rebuildPointsBalance` **byte-compatible with the TS contract**, plus the reconciliation projection | L |
| **T-JYRW-03** | `LoyaltyAccrualHandler` outbox consumer + policy interpreter (declarative rows, versioned) | L |
| **T-JYRW-04** | Referral reward completion on `referral_attributions` (closing the deferred scope) with `canQualifyReferral` parity | M |
| **T-JYRW-05** | Redemption endpoints + entitlement wiring (`:reserve`, `:settle`, `:reverse` colon-verbs) | M |
| **T-JYRW-06** | Tier/streak/badge calculators as scheduled automations (JY-AUT family) | L |
| **T-JYRW-07** | `packages/commerce` UI wiring in `apps/customer`: balance, ledger timeline, redemption picker, badge shelf | L |
| **T-JYRW-08** | Governance console in `apps/dashboard`: policy publish/rollback, cap overrides, abuse holds, full audit | M |
| **T-JYRW-09** | Financial accounting link: issued liability, redemption cost, expiry reversal (Finance/Tax modules own this; never hand-roll) | L |

### 19.7 Acceptance criteria

- AC-T-JYRW-02 For a fixed 200-entry fixture, the PHP projection and `rebuildPointsBalance()` from `packages/commerce` return the identical balance; a parity test pins the TS function's semantics, so the contract cannot drift silently.
- AC-T-JYRW-02b `loyalty_entries` rejects `UPDATE` and `DELETE` at the database layer (trigger/constraint or append-only table policy); the only way to reverse is a `REVERSE` row.
- AC-T-JYRW-03 Emitting the same fact twice accrues **exactly one** entry (`(source_type, source_id, policy_version)` unique).
- AC-T-JYRW-03b **A Dosha-related fact accrues nothing** — asserted positively, not by omission: a test feeds a `jyotish.dosha.detected` fact and asserts zero ledger rows.
- AC-T-JYRW-04 A self-referral, a referee with no completed action, and a duplicate code each return `422` with a specific `error.code`.
- AC-T-JYRW-05 Redemption beyond balance returns `409 insufficient_points`; concurrent redemptions cannot both succeed (no double-spend test).
- AC-T-JYRW-05b No redemption record may reference a product in the remedy categories (enforced by a constraint plus a catalogue-driven test).
- AC-T-JYRW-06 A policy change never alters the balance computed from prior entries (replay-identical, versioned `policyVersion`).
- AC-T-JYRW-07 The devotee UI shows every ledger row with its reason and policy version, passes a11y ≥ 0.95, and contains no expiry nag within 14 days of any Dosha or remedy surface (component test).
- AC-T-JYRW-08/09 Policy publish is audited and reversible; period-end issued-liability totals equal `Σ entries` per policy version.
- AC-T-JYRW-ALL Cross-tenant reward reads return `403`/empty (`AssertTenantIsolation` matrix), and reward accrual works with `jyotish.enabled` false — rewards are a platform concern, not a Jyotish dependency.

---

## 20. Smart optimisation layer (JY-OPT-01 … JY-OPT-24)

Part I §4 already forbids the biggest waste (recomputing planets per tool). Part II's optimisations are ordered by measured benefit, and **none may be adopted without a number attached**. The culture is already written down in `QueryPlanTest`: *"New hot queries get added here, not reviewed by hope."*

### 20.1 Content-addressed snapshot cache (the largest single win)

JY-OPT-01 — key every snapshot on a canonical, sorted, HMAC-SHA256 digest of its calculation identity, exactly as the spec proposes:

```
normalized_instant · lat · lon · elevation_policy · ephemeris_id · ephemeris_version ·
ayanamsa_id · sidereal_mode · house_system · node_mode · calculation_flags ·
timezone_database_version · ruleset_version            →  key = HMAC_SHA256(canonical_json)
```

HMAC (with a key from the existing secret store) keeps raw birth coordinates out of cache keys — this is a **privacy** decision as much as a performance one, since a plaintext coordinate key in a shared cache is a special-category data leak. Store in the already-configured `redis` cache store, `database` fallback, with a per-tenant namespace for anything user-derived.

JY-OPT-02 — **layered keys**, which is the part people get wrong. The tropical planetary snapshot does **not** depend on ayanamsa, house system, display language or time zone; only the sidereal projection does. Today `CalculationProfile::cacheKey()` folds `profile_code` (which includes ayanamsa) into one key — so switching a user between Lahiri and Raman needlessly re-runs the ephemeris. Split into `snap:<instant, lon, ephemeris_*>` (astronomical, immortal) → `sid:<snap_key, ayanamsa_*, node_mode>` → `chart:<sid_key, house_system, flags>` → `render:<chart_key, locale, theme>`. **This is the concrete realisation of rule 4** (never silently change a convention): a convention change moves the key *from that layer down*, never the layer above it.

JY-OPT-03 — one-row-per-instant snapshots in `jyotish_ephemeris_snapshots` (§4.1) so the *database* is a cache tier too, not only Redis; a year of daily-granularity snapshots for a place is tiny and is exactly what Panchang reads want.

**Starting point, stated honestly:** the platform has essentially no computation caching today. There is **not one `Cache::remember` call anywhere under `app/Domain`** — the only cache users there are `Delivery/Policies/WhatsAppPolicyService.php` (`comm:wa:blocked:{userId}`, 365 d) and `Delivery/Reliability/ProviderCircuitBreaker.php`. The single existing panchang result cache is the one `Cache::remember(key, 86400, …)` inside `ProcessRitualRemindersCommand`, and **that command is never scheduled** (D-12 family) — so even it does not warm anything for real users. `Authz\DecisionCache` (generation-based invalidation) is the one genuinely sophisticated cache in the codebase and is the design model for key-layering, not the analytics stack.

### 20.2 Compute-cost ratchets (reuse `ScopeExitBudgetTest`'s technique)

JY-OPT-00 — **activate the measurement before optimising anything (D-15).** `ComputationAuditor::log(input, profile, executionMs, cacheHit, result)` exists and writes `calendar_computation_log`, but its **only caller in the entire repository is `tests/Unit/Calendar/ComputationAuditTest.php:22`** — production never writes the table and nothing reads it. Wire it into the snapshot and tool-execution paths as the first optimisation task, otherwise every "before" number below is measured against an empty table.

JY-OPT-04 — extend that now-live log with `ephemeris_calls`, `root_iterations`, `ruleset_evals`, `db_queries` and `peak_mem_bytes` (the current columns are only `input_hash`, `profile_code`, `execution_ms decimal(8,2)`, `cache_hit`).

JY-OPT-05 — `tests/Architecture/JyotishComputeBudgetTest.php`: a **monotonic ceiling** test, `CEILING = <measured>`, failing when the complete-D1 path or the 30-day Muhurat path exceeds the recorded best. This repo already proves the technique twice over: `ScopeExitBudgetTest` uses `token_get_all` static scanning with `LEGACY_CEILING = 54`, an inventory file listing every offender, and a "must carry a non-empty reason" rule; `tests/Modules/Search/QueryPlannerBudgetTest.php` asserts explicit candidate budgets (`soft_candidates === 300`, `hard_candidates === 1000`). **Copy the Search precedent's shape for Muhurat candidate budgets** — it is the same problem, already solved with a test. Ratchet down as work lands; never up without a comment explaining why.

JY-OPT-06 — `QueryPlanTest` additions for every new hot read (snapshot lookup by key, dasha interval binary search, ashtakavarga grid, chart by `(birth_profile_id, profile_code, chart_version)`). That test runs `EXPLAIN QUERY PLAN` → `detail` on SQLite and `EXPLAIN` → `type` on MySQL, and `assertIndexed()` fails on `/^(SCAN\s|ALL$)/i`; it already carries **16 hardcoded hot shapes**, including one on `referral_attributions (tenant_id, referrer_user_id)`. Its docblock is the rule: *"New hot queries get added here, not reviewed by hope."*

JY-OPT-05b — **there is currently no query-count or N+1 budget test anywhere** (no `enableQueryLog`-based assertion exists in `tests/Architecture`). §20's per-path `db_reads` budgets in §20.7 are therefore genuinely new capability, not a tightening of an existing guard — which also means the first version will find real N+1s in code nobody has counted before. Budget a discovery pass, not just an enforcement pass.

JY-OPT-07 — a nightly perf-regression diff (JY-AUT-09) that files an issue when p95 drifts beyond 10 % week-over-week, distinguishing cold vs warm cache as the SeoContent spec requires.

### 20.3 Numerical and algorithmic smart

| ID | Optimisation | Why it is safe |
| --- | --- | --- |
| JY-OPT-08 | **Speed-adaptive coarse sampling** in the transition solver: step size from `λ̇` (the snapshot already carries rates) instead of a fixed grid | only changes *where* it looks; final refinement and the ≤1 s tolerance are unchanged; bracket+residual still persisted and asserted |
| JY-OPT-09 | **Brent instead of plain bisection** in `RootSolver` | same guarantees on a bracketed sign change; fewer iterations; both paths kept behind a flag until parity is proven to 1e-9 |
| JY-OPT-10 | **Piecewise-polynomial ayanamsa table** with a declared error bound, precomputed per century block | replaces per-call polynomial evaluation; equivalence test against the live formula must hold below the stored bound |
| JY-OPT-11 | **Integer index math for Rashi / Nakshatra / Pada** on scaled arcseconds (`microdegrees`), avoiding float division at boundaries | Part I AC-T-JY02-05 already requires every boundary to pass; integer comparison at boundaries is *more* robust, not less |
| JY-OPT-12 | **Precompiled predicate graphs** for Yoga/Dosha: a ruleset is compiled once per `rules_version` into an ordered plan and cached, not re-interpreted per request | `ActivityRuleInterpreter` is already an interpreter of this shape; compile-once is the natural extension |
| JY-OPT-13 | **Immutable compatibility matrices** (Yoni, Gana, Nadi, Tara, Maitri, Porutham) as loaded-once lookup tables | Part I already flags "precompiled compatibility matrices"; these are static data, so memoising them cannot go stale |
| JY-OPT-14 | **Sorted-interval dasha + transit overlay with merge-based intersection** rather than per-candidate recomputation | binary search already mandated (JY-035) |
| JY-OPT-15 | **Batch ephemeris**: one provider call for N instants of the same body set | the snapshot loop is hoisted; JY-AUT-17 (family batch) depends on this |
| JY-OPT-16 | **Interval-intersection Muhurat search** — already mandated in T-JY07-01; here it gains a *proof*: candidate count is logged and the 90-day budget is ratcheted by JY-OPT-05 | the computation-count test is the guarantee, not the SLO alone |

### 20.4 PHP/FPM-specific reality (this is where Rust-style advice must be translated)

1. **OPcache is the platform — and it is the only shared-memory tier available.** There is **no APCu** anywhere (zero hits across `app/`, `config/`, `docker-compose.yml`), and `CACHE_STORE` defaults to `database` with `redis` merely *available*. So `platform-preload.php` for the hot kernel classes (`EphemerisSnapshot`, `CalculationProfile`, all 130 registry/ruleset consts), `opcache.validate_timestamps=0` in production, and JIT `tracing` reviewed in ADR-JY-04 — with **every measurement taken with real OPcache**, per the SeoContent spec's explicit rule that otherwise CLI timings are "advisory only". Switching `CACHE_STORE` to `redis` is a configuration change with a deployment consequence, not a free win; state it in the ADR.
2. **Microseconds as integers through the hot path.** `*_at_us` is already the storage type; passing `int` µs is materially cheaper than `CarbonImmutable` construction. Keep `CarbonImmutable` at the boundary (API, presentation), not inside solver loops — the current `CalendarBuildCommand` constructs Carbon objects per day inside the innermost loop.
3. **Arrays of floats, not object graphs, inside a snapshot.** Part I §2's fixed-size struct becomes one packed array per body `[λ, β, r, λ̇, β̇, ṙ]` plus a `readonly` wrapper; allocation and GC pressure drop, and `json` encoding for storage stays at the boundary.
4. **No Eloquent in the compute path.** `Astronomy/` is already Eloquent-free (`Calendar: [Support]`); hold that line for `Jyotish/`. Repositories fetch, plain values compute.
5. **Request-scoped memo** (`current-request` registry) so a 12-module report page hits one snapshot, independent of the distributed cache.
6. **Async for everything that renders bytes** (PDF, batch, >30-day Muhurat) via the outbox, keeping `202` semantics; PHP-FPM worker seconds are the scarcest resource.
7. **Octane is the open question, not the answer — and it is genuinely absent.** `composer.json` requires only four runtime packages (`laravel/framework`, `laravel/passport`, `laravel/tinker`, `spomky-labs/otphp`), and `vendor/laravel/` contains `framework, passport, pint, prompts, sail, serializable-closure, tinker` — **no Octane, no Horizon**. The `octane` entry in `config/cache.php`'s store list is stock framework boilerplate and must not be read as evidence that Octane is available. Octane would permit a real in-process LRU and warm ephemeris state, which is what the source spec's memory budgets quietly assume. ADR-JY-04 decides whether to add the dependency; **adding it is the single largest dependency decision in this plan** and it changes the tenancy-safety story (warm process state must be proven not to leak one tenant's chart into another's request — the exact class of failure GATE-1 exists to catch). §8's budgets are met first by precomputation, cache tiering and ratchets; those are the honest lever, and Octane is an amplifier rather than a substitute for them.

### 20.5 Delivery-layer smart

JY-OPT-17 — **static generation for public Panchang/horoscope pages** from the active generation at build time; `apps/web` already runs `cacheComponents: true`, so most daily-public reads never reach PHP.
JY-OPT-18 — **demand-shaped precomputation**: warm only the `(place, date)` pairs people actually request plus festival spikes predicted from the festival calendar and `packages/analytics` (the one 12,331-line smart package that is real), instead of precomputing every place forever. Feeds JY-AUT-02's place scope.
JY-OPT-19 — **code-split the SVG chart package** so the 80 new tools do not blow the 300 KB Lighthouse script budget (D-11 / R-6).
JY-OPT-20 — **one calculation, many renderings**: `locale` and `theme` sit *below* the chart key in JY-OPT-02's layering, so a language switch never recomputes a planet — the spec's own stated goal, made structurally impossible to violate.
JY-OPT-21 — **ETag / `Last-Modified` derived from `generation` + `chart_version`** on read-only endpoints, so a returning devotee pays nothing.

### 20.6 Smart correctness (guardrails that stop optimisation from lying)

JY-OPT-22 — **no silent precision downgrade.** A cheaper engine tier may serve only tool classes whose declared tolerance it provably meets; the profile records which engine answered, and requesting a body the cheap tier does not support throws instead of falling back.
JY-OPT-23 — **cache-key/version drift detector**: any change to `rules_version`, `ephemeris_version` or `ayanamsa_version` must invalidate the layers above it; a test warms a key, bumps one version field, and asserts a miss on exactly the layers that should miss and a hit on the ones that must not.
JY-OPT-24 — **cached result is never re-interpreted.** `verification_status` travels with the payload; when a ruleset is demoted to `due_for_review` (JY-AUT-05), cached results are re-labelled, not re-served as reviewed.

### 20.7 Targeted budgets (measured under real OPcache, cold and warm reported separately)

| Path | Budget | Ratchet mechanism |
| --- | --- | --- |
| Complete D1 (warm snapshot) | ≤ 1 `EphemerisSnapshot`, ≤ 1 ayanamsa eval, ≤ 45 db reads | JY-OPT-05 test |
| Full report (12 modules) | ≤ 1 snapshot for the whole page (request memo) | JY-OPT-05 + JY-OPT-04 logging |
| 30-day Muhurat | ≤ 1,000 coarse samples (vs 2.6 M seconds polled) | candidate-count log |
| 90-day Muhurat | ≤ 3,000 samples, async beyond that | JY-OPT-16 |
| Boundary refine | ≤ 40 iterations, `residual ≤ 1e-6` | already asserted per Part I AC-T-JY05-02 |
| Ashtakoota | 0 ephemeris calls (both charts already stored) | JY-OPT-05 |
| Shadbala | ≤ 16 varga derivations from one snapshot | JY-OPT-05 |
| Public Panchang page | 0 PHP compute on cacheComponents hit | JY-OPT-17 |
| Uncached single-day compute | ≤ 500 ms | inherited from `docs/seo-content/SPEC.md` §budgets |

**Anti-premature-optimisation rule:** an optimisation lands only with (a) a before/after number in the PR, (b) a ratchet or assertion test preventing regression, and (c) a tolerance proof for the numerics. "It should be faster" is not a task.

---

## 21. Added phases, and amendments to §14

### Phase P7 — Automation, rewards and catalogue depth (80 new tools)
Tasks: all of §18 (T-JYAUT-01…26), all of §19 (T-JYRW-01…09), tool families AA–AF (JY-051…108), JY-129/130.
**Entry:** P3 exit (Muhurat + Dasha + Transit verified), because JY-AUT-15/16 and every reward accrual depend on those facts existing.
**Exit gate:** all §18.6 and §19.7 ACs green; **no unscheduled command** (AC-T-JYAUT-ALL); ledger parity with `packages/commerce` proven; zero accrual from Dosha facts; GATE-2/GATE-3 suites green.

### Phase P8 — Optimisation and scale hardening
Tasks: all of §20 (JY-OPT-01…24), tool families AG–AJ (JY-109…128), JY-AUT-07/08/09 made permanent.
**Entry:** P4 exit (the expensive strength/Ashtakavarga/matching surfaces exist, so there is something worth optimising).
**Exit gate:** GATE-4 load profiles met at the §8 and §20.7 numbers with `jyotish_computation_log` evidence published; JY-OPT-05 ratchets committed with measured ceilings; JY-OPT-22/23/24 guardrail tests green; Octane decided by ADR-JY-04 either way.

### 21.1 Amendments to the §14 release table

| Release | Tools | Automation | Rewards | Optimisation | Gate |
| --- | --- | --- | --- | --- | --- |
| **P7** | JY-051…108, JY-129, JY-130 | **T-JYAUT-00a…00e first** (D-12…D-15), then JY-AUT-01…26 (schedule the reminder engine; ahead-of-time generation; 12 devotee automations) | JY-RWD-01…16 full ledger + accrual + redemption + tiers — gated on T-JYAUT-00e | activate `ComputationAuditor` (JY-OPT-00) as the measurement baseline, then request-memo + `ephemeris_calls` logging | §18.6 + §19.7, GATE-2/3 |
| **P8** | JY-109…128 | nightly fuzz, divergence probe, perf ratchet reporter as standing jobs | reward projections served from cache only (no N+1 accrual reads) | JY-OPT-01…24 (HMAC layered cache, Brent + adaptive sampling, compiled predicates, static generation, code-split charts) | GATE-4 |

### 21.2 New risks introduced by Part II

| # | Risk | Mitigation |
| --- | --- | --- |
| R-12 | **Gamifying belief.** Rewards that nudge users toward fear-based content would be a trust catastrophe, and it is the kind of thing that ships because "engagement went up" | §19.4 rules 1–2 enforced by tests, not policy: no reward source may reference a Dosha fact (AC-T-JYRW-03b) and no remedy category may be redeemable (AC-T-JYRW-05b) |
| R-13 | **Reward farming attacks compute cost** | accrual keyed per *birth profile*, not per request; caps in `caps_json`; `throttle.tier`; REVERSE for detected abuse rather than silent clawback |
| R-14 | **Ledger integrity** — points are a financial liability | append-only + hash-chained entries, projection-vs-cache reconciliation, Finance/Tax own the accounting link (T-JYRW-09) |
| R-15 | **Automation amplifies a bad generation** — a scheduled job that fires 40,000 reminders from wrong data is worse than no job. This is no longer hypothetical: D-12/D-13 show the engine would have shipped duplicate notifications (32-char dedup column vs a ≥54-char key) and Ujjain-relative anchors for every devotee | JY-AUT-01 blocked on T-JYAUT-00a…00d; JY-AUT-02's activation gated by `validate` + `verify-oracle`; a `building` generation cannot notify; every automation has a fail-closed flag; **assertions run on the MySQL driver, since SQLite hides D-12 entirely** |
| R-16 | **Cache poisoning / stale convention serving** | §20.2 key layering + JY-OPT-23 drift test + HMAC keys so coordinates never leak into key spaces |
| R-17 | **Trusting dormant code as if it were a dependency.** The eight commerce/pricing TS files (`loyalty`, `credit`, `wallet`, `gift-card`, `membership`, `entitlement`, `promotion-engine`, `coupon`) have zero importers, zero tests and zero tables; `packages/{recommendations,personalization,experimentation,attribution}` are 14–59-line stubs; `ComputationAuditor` is dead; `calendar_computation_log` is never written. Meanwhile the *real* intelligence is in PHP: `Modules/Recommendations/Services` (`ThompsonSamplingBandit`, `FraudGuard`, `IpsEvaluator`, `PersonalizedPageRank`, `MmrReranker`, …) over `reco_*` tables with live routes, and `packages/analytics` (12,331 lines, 31 tests, wired to `app/api/ae/v1/batch` + `services/workers/analytics-collector`) | §19 treats the TS files as **unexercised design intent to be made real and tested for the first time**, not as a stable base. §20 reuses the PHP recommender, `FraudGuard` and `IpsEvaluator`. JY-OPT-00 activates the auditor before any optimisation is claimed. `apps/web/lib/ad-orchestration/` (~3,052 lines, incl. its own `ExperimentEngine`) is the live app-layer path and must not be duplicated by a package-level reimplementation |
| R-19 | **SQLite masking production bugs.** `VARCHAR(32)` is unenforced on the configured dev/test driver, which is precisely why D-12 has survived | CI must exercise MySQL-path assertions for every width/length/collateral-damage invariant (`QueryPlanTest` already branches per driver, so the harness exists); treat "green on SQLite" as insufficient for any schema-dependent AC |
| R-20 | **Rewards create a financial liability with no accounting owner yet** | T-JYRW-09 is not optional and must land in the same release as the first EARN; issued liability is reconciled to `Σ entries` per `policyVersion` before the programme leaves a single pilot tenant |
| R-18 | **Catalogue sprawl** — 130 tools is 130 chances to be quietly wrong | registry-driven data tests walking all 130 rows (§17.12), `verification_status` surfaced in UI, and the AC-T-JY10-ALL rule that adding a tool must not touch the kernel |

---

## 22. Part II verdict

The three most valuable things in Part II cost almost nothing, which is the argument for doing them before more calculators:

1. **Fix four small defects, then add one `Schedule::command(...)` line** (T-JYAUT-00a…00d → JY-AUT-01). `customers:process-reminders` is a genuinely well-built engine — tenant iteration, version-partitioned `Cache::remember`, advance-day slots, consent gating through `NotificationConsentGateService`, idempotent event ids, an outbox fact — and nothing has ever run it except its own two tests. But it cannot be scheduled as-is: its dedup key is ≥ 54 characters written into a `string(32)` column (invisible only because SQLite ignores widths), its anchors are computed at hardcoded Ujjain coordinates for every devotee, it hits a wall at the year 2030, and its `rules_version` field changes only the cache key rather than the evaluation path that ADR-0035 promised. Each fix is measured in tens of lines. The same is true of the entire Panchang generation pipeline (`calendar:build/validate/activate`), which today depends on a human remembering to run it before the year turns over.
2. **Implement the server for `loyalty.ts` — after giving the outbox a dead-letter path.** The ledger model, entry types, event-sourced reducer and referral state machine are already written, and `referral_codes` carries a comment deferring reward rules verbatim, so closing the gap is contract fulfilment rather than invention. Two caveats that the first pass of this plan missed: those eight commerce/pricing files have **zero importers, tests or tables**, so they are unexercised intent that T-JYRW-02 will test for the first time; and the outbox currently leaves a five-times-failed event pending forever with no replay command, which is an acceptable place to lose a telemetry row and an unacceptable place to lose a user's points (T-JYAUT-00e).
3. **Split the cache key by dependency layer** (JY-OPT-02). `CalculationProfile::cacheKey()` folds ayanamsa into the astronomical snapshot, so a user comparing Lahiri against Raman re-runs the ephemeris for free. Layering the key fixes the performance problem *and* enforces rule 4 structurally — the convention a result was computed under becomes part of its identity rather than an ambient default. Do it in the same change that activates `ComputationAuditor`, because that auditor has never been called from production code and there is presently no baseline to optimise against.

**The meta-lesson, and it is the one thing I would carry into any other module here:** this codebase contains a significant amount of *aspirational* code that reads as capability but is not — a compute auditor with no production caller, a panchang cache in an unscheduled command, eight dormant commerce contracts, four stub "smart" packages, and an off-brand green palette sitting next to a design-token package that has a function specifically to detect it. The genuinely live intelligence is elsewhere: `app/Domain/Calendar` (4,435 verified astronomical lines), `Modules/Recommendations/Services` (bandits, rerankers, `FraudGuard`, `IpsEvaluator` over real tables and real routes), and `packages/analytics` (12,331 lines, 31 tests, wired end to end). **Any plan for this platform is only as good as its ability to tell those two lists apart**, and Part II's first draft got three of them wrong before verification.

Everything else — 80 more tools, 26 automations, 16 reward mechanisms, 24 optimisations — inherits those properties, which is precisely the test the platform had to pass before scaling. The honest constraint stays: **do not ship a reward or an automation whose underlying calculation has not been validated**, because automation and incentives are how a small inaccuracy becomes a large one.
