# Panchang, Muhurat, Festivals & Programmatic SEO — SeoContent Module Specification v3

**Module:** `app/Modules/SeoContent` (+ a new `app/Domain/Calendar` kernel — see §3, the central
decision of this document)
**Status:** Draft — for review. **§1.2 contains a measured correctness defect that is live in
production; the P0 epic in §16 exists to fix it before any new calendar surface ships.**
**Slices:** S85 → S104 (continues the register: Search v3 occupies S65–S84 in
[`docs/search/SPEC.md`](../search/SPEC.md))
**Extends:** ADR-0024–0027 (search), and the Customers Panchang engine shipped 2026-11-10
**Related:** [`docs/search/SPEC.md`](../search/SPEC.md) §5 (locality registry), §10 (telemetry) —
**shared dependency, not a duplicate build**
**Methodology:** every task in §16 follows [`docs/specs/README.md`](../specs/README.md) §2 template,
§3 AC shapes, §4 Definition of Done.

---

## 0. How to read this document

```text
Epic       E-SC-<nn>           e.g. E-SC-02
Task       T-SC<epic>-<nn>     e.g. T-SC02-03
Subtask    T-SC<epic>-<nn>.<s>
AC         AC-T-SC02-03-<n>
Defect     P-<nn>              verified defect in shipped calendar code (§1.2)
Profile    <calculation profile>  e.g. drik-lahiri-amanta
```

Normative words: **MUST** = blocker, **SHOULD** = justified deviation in the PR, **MAY** = optional.

§1 is facts checked against this working tree on 2026-11-10, including **executed computation**
(§1.3, appendix A holds the transcript and the reproduction script). Everything after §2 is a
proposal; where a proposal contradicts §1, §1 wins.

One thing this document will not do. A Panchang is religious and cultural infrastructure for real
people who will fast, travel, marry, perform śrāddha for their ancestors, and argue with their
elders about the date on the strength of what this platform prints. A wrong tithi is not a display
bug. Therefore: every calculation in §4–§7 is required to carry its profile, its version and a
plain-language explanation of how its answer was reached, and the platform **MUST NOT** present a
computed window as a guarantee of any outcome. The engine reports what a named convention computes.

---

## 1. Evidence baseline

### 1.1 What ships today

| Layer | Shipped reality | Verified at |
| --- | --- | --- |
| `SeoContent` module | **486 LOC, 9 files.** `SeoPage` + `SeoCluster` models, one controller, one import command, 2 migrations, 26-line route file | `app/Modules/SeoContent/` |
| Module manifest | `"permissions": []`, `"scopes": []`, `"events": []`, `"actions": []` — the module declares **nothing** | `SeoContent/module.json` |
| Content source | 1 265 hand-authored/`automation_generated_at` JSON pages in `apps/web/content/pages/`, imported via `seo:import` | `SeoImportCommand.php` |
| Page API | `GET /api/v1/seo_content/seo/{pages,registry,search-docs,pages/{slug}}`, `withoutMiddleware(['auth.any','tenant'])`, **`throttle:60,1`** | `SeoContent/Routes/api.php:19-25` |
| Search integration | `SeoPageSearchProvider` projects pages into `search_documents` (see Search spec §1.1) | `Search/Contracts/SeoPageSearchProvider.php` |
| **Astronomy** | **Exists — but in the `Customers` module:** 14 files / 815 LOC at `app/Modules/Customers/Domain/Panchang/` | `JulianDayCalculator`, `SolarLongitude`, `LunarLongitude`, `Elongation`, `TithiCalculator`, `NakshatraCalculator`, `LunarMonthCalculator`, `SunriseUtcCalculator`, `PanchangCalculator`, `AnchorOccurrenceResolver`, `PanchangOccurrence`, `PanchangConfidence`, `Contracts/ComputesPanchang`, `Exceptions/OutOfCorpusException` |
| Consumers of astronomy | ritual reminders only: `CustomerSpiritualController::storeReminder()` and `ProcessRitualRemindersCommand` | `Customers/Http/Controllers/CustomerSpiritualController.php:291-302` |
| Festival data | **none.** No festival table, no rule, no occurrence. `anchor_rule` JSON per user reminder is the only observance logic | grep: no `festival_*` migration anywhere |
| Muhurat | **none.** No Rahu Kaal, Abhijit, Choghadiya, Hora, Lagna, or interval code anywhere | grep over `*.php`/`*.ts`/`*.tsx` |
| Places | **none.** No `seo_places`, no coordinate registry; `city` is free-text (Search spec P-equiv D-04) | migrations sweep |
| Public calendar UI | `/resources/tools` advertises 6 calculators; the customer reminders page claims "astronomical Panchang calculation" | `apps/web/app/resources/tools/*`, `apps/customer/.../reminders/page.tsx:86` |

**The headline:** `SeoContent` today is a key–value content store with a JSON-LD-ish page shape. All
calendar intelligence that exists lives in another module, serves one narrow reminder path, and —
per §1.2 — is mathematically wrong in two of its three outputs.

### 1.2 Defect register

**P-01 — There is no ayanamsa. Nakshatra is off by two mansions, measured.**
`PanchangCalculator.php:53-61` computes **tropical** apparent longitudes (`SolarLongitude`,
`LunarLongitude` are textbook Meeus tropical series; `grep -i ayanamsa` over the whole module
returns nothing) and feeds the tropical Moon longitude **directly** into
`NakshatraCalculator::calculate()` — which is a **sidereal** division of the ecliptic
(`NakshatraCalculator.php:3-8` says so itself: "The 360-degree zodiac is divided into 27 equal lunar
mansions"). The definition requires `L_m,side = mod(L_m − A, 360)` with `A ≈ 24.21°` (Lahiri, 2026).
24.21° ÷ 13°20′ = **1.82 nakshatras**. Executed against the real classes (appendix A §1):

```text
2026-11-10  SHIPPED=JYESHTHA        sidereal=VISHAKHA        delta=+2
2026-01-01  SHIPPED=ARDRA           sidereal=ROHINI          delta=+2
2027-05-15  SHIPPED=HASTA           sidereal=PURVA_PHALGUNI  delta=+2
```

This is not an edge case and not a precision limit. It is a constant, systematic shift of roughly
two lunar mansions across the entire supported date range.

**Blast radius today: nil, which is exactly why it is still live.** `AnchorOccurrenceResolver`
matches on `tithi number`, `index_in_paksha`, `paksha` and `masa`
(`:52-54, 91-109`) and **never reads nakshatra**. The only nakshatra values in the database are the
user-typed `customer_spiritual_profiles.nakshatra` / `customer_family_members.nakshatra` columns,
validated as `['nullable','string','max:50']` (`CustomerSpiritualController.php:89,183`) — free text,
not the 27-name enum. So the engine computes a wrong value, nothing consumes it, and the first
surface that does (muhurat suitability, Rohini-for-Janmashtami, `Tara Bala`, a printed Panchang page)
inherits the defect on its first day. **The fix must land in the same epic as the first consumer,
not before it and not after it.** See also P-17 for the separate problem in those user-typed columns.

**P-02 — Masa is off by one lunar month for ~81 % of the year, measured.**
`LunarMonthCalculator.php:39-44` derives the solar rashi index by bucketing **tropical** Sun
longitude into 30° slices, then maps rashi → masa name. Same missing ayanamsa. Executed
(appendix A §3):

```text
2026-11-10  sunLon=227.621  SHIPPED masa=KARTIKA (rashi 8)   sidereal masa=ASHVINA (rashi 7)
```

The two agree only while `sunLon mod 30 ≥ 24.21°`, i.e. 5.79° of every 30° — about **19 % of the
solar year**. For the other ~81 %, the platform names the wrong lunar month. Unlike P-01, this one
**does reach a user decision today**: `AnchorOccurrenceResolver::matchesAnchor()` compares
`$targetMasa` against the resolved masa (`:54`, `:106-109`), so `SHRADDHA` ancestral anchors — which
store `masa` + `paksha` + `tithi` in `customer_ritual_reminders.anchor_rule`
(`CustomerSpiritualController.php:283`; dedupe key built from the same fields at
`ProcessRitualRemindersCommand.php:101-108`) — are keyed to a month name that is usually not the one
a working Panchang would print. A person's ancestral-rite reminder can therefore fire in the wrong
lunar month. This is the defect class the brief calls "not minor display bugs", and it is the reason
E-SC-01 is P0 rather than a refactor.

**P-03 — CI cannot see P-01 or P-02, because `GoldenCorpusTest` contains no gold.**
`tests/Unit/Customers/Panchang/GoldenCorpusTest.php` asserts **engine self-consistency only** — e.g.
"resolve index 11 ⇒ name EKADASHI", "date > baseline". It never compares a single value against
Rashtriya Panchang, the Indian Astronomical Ephemeris, or any published reference. Worse: the one
test that names a masa (`test_resolves_shraddha_ancestral_anchor…`, rule `masa => 'KARTIKA'`)
**never asserts the resolved masa** — it asserts only `tithi number`, `paksha`, `name`. Tithi is the
one limb that is *correct* (see P-04), so the suite validates exactly the output that happens to
work. `PanchangMathTest` tests nakshatra by feeding hand-written synthetic longitudes (0° ⇒
ASHWINI), which is a correct test of the bucketing function and no evidence about the pipeline.
The green checkmark is real; the coverage is not.

**P-04 — Why this survived: tithi is ayanamsa-invariant.**
Elongation is a *difference* of longitudes, so the ayanamsa cancels. Measured (appendix A §2):
tropical elongation `8.226800°`, sidereal elongation `8.226800°`, difference `0.000000`. Tithi,
paksha, and therefore Ekadashi/Purnima/Amavasya resolution are all genuinely fine. A calendar
feature whose most-visible number is right and whose second-most-visible number is always wrong is
the least detectable failure mode available, and it explains the state of §1.1.

**P-05 — Sunrise is fabricated when no sunrise exists.**
`SunriseUtcCalculator.php:59-67`: `cosH > 1` (never rises) sets `$h = 0.0`, and `cosH < −1`
(midnight sun) *also* sets `$h = 0.0`. The inline comments claim a "default to 06:00" and "00:00"
respectively; neither is what happens, and neither would be acceptable. Executed (appendix A §4):

```text
Svalbard 78.22°N 21 Jun 2026 (midnight sun — no sunrise exists): returns 10:59:06
Svalbard 78.22°N 21 Dec 2026 (polar night — no sunrise exists):   returns 10:55:20
```

`PanchangCalculator::calculate()` then builds a complete `PanchangOccurrence` on top of that
invented instant, `AnchorOccurrenceResolver` caches it for 24 h under a versionless key
(`ProcessRitualRemindersCommand.php:72-76`), and it becomes the reminder's `next_occurrence_at`.
The brief's rule — special latitudes must return a defined unsupported response, never an ordinary
Panchang day — is the correct rule, and it is currently violated silently.

**P-06 — There is no timezone anywhere in the calculation path.**
`PanchangCalculator::calculate(int $year, int $month, int $day, float $lat, float $lng, string
$tradition)` takes **no timezone**. It computes sunrise as a UTC *time-of-day* and then pastes the
**input civil date** onto it (`PanchangCalculator.php:31-39`, `sprintf('%04d-%02d-%02dT%02d:%02d:%02dZ')`).
Two consequences:
1. The instant is labelled with whatever civil date the caller happened to pass. For India these
   coincide (appendix A §5, Delhi `00:54:46Z`); they do **not** coincide in general, and the
   content corpus is explicitly targeting diaspora time-zone coordination
   (`ancestor-rite-timing-for-diaspora-time-zone-coordination-consultation.json`).
2. `SunriseUtcCalculator.php:91` wraps the hour with `($hour + 1) % 24` and never increments the
   day. A 23:59:59→24:00 rollover silently re-dates the result by 24 hours.

Also note the `timezone` string reaching this path is **user-supplied**:
`CustomerSpiritualController.php:295` reads
`$validated['anchor_rule']['timezone'] ?? 'Asia/Kolkata'` straight out of client JSON — the brief's
"server-side canonical timezone" control is absent.

**P-07 — `PanchangOccurrence` carries 2 of the 5 limbs while documenting 5.**
`PanchangCalculator.php:7` states "Evaluates the 5 limbs of the Hindu calendar". Executed field list
(appendix A §6): `date, sunriseUtc, tithi, nakshatra, masa, tradition, confidence` —
**yoga: NO, karana: NO, vara: NO.** So 3 of the 5 limbs of the Panchang do not exist, and P-01 makes
the one that does exist (nakshatra) wrong. No rashi, no moonrise/moonset, no solar noon, no day
duration, no sankranti, no Adhika/Kshaya masa. `LunarMonthCalculator::calculate()` accepts an
`$isAdhika` parameter (`:36`) that **nothing ever passes** — the intercalary-month argument has no
caller and no detector; Adhika Masa is therefore structurally undetectable, and Masa-name correctness
without intercalation handling cannot be trusted even after P-02 is fixed.

**P-08 — The honesty mechanisms exist but assert unverified things.**
`PanchangConfidence` labels `HIGH` when far from a tithi boundary. That measures *boundary
proximity*, not *accuracy* — a wrong model is confidently wrong in the middle of a tithi, which is
the majority of days. Its docblock cites a "±15 min fixture tolerance" for a model class whose own
docblock states 0.01°/0.02° accuracy — Meeus truncated lunar series at that error are ~0.02° ≈ 1.2′
of arc, which near a tithi boundary (the Moon moves ~12°/day ⇒ 0.02° ≈ **2.4 minutes**, near a
nakshatra boundary ≈ 1.6 minutes) is fine, but **the lunar-series truncation error is the reason
the 15-minute claim needs external fixtures, not the reason it can skip them.** Separately
`AnchorOccurrenceResolver.php:60-62` throws `OutOfCorpusException` for `year > 2028` with the
comment "verified through 2028" — nothing in §1.2 or CI verifies 2026, 2027 **or** 2028. A range
guard that advertises verification it does not have is worse than no guard, because it converts a
missing validation programme into an apparent one.

**P-09 — The astronomy kernel is owned by the wrong module.**
`app/Modules/Customers/Domain/Panchang/` — 815 LOC of reusable, dependency-free astronomical
math, sitting inside Customer reminders. `SeoContent` cannot use it without importing another
module's internals, which DoD item 6 ("no new cross-module joins or non-contract imports")
and `deptrac.yaml` exist to forbid. So the natural next step — a public Panchang page — would
either duplicate the math a second time (next to the D-13 PHP/TS fold-map duplication) or breach
the architecture gate. This is the decision §3 resolves.

**P-10 — Zero muhurat engine, six advertised calculators, all six 404.**
`apps/web/app/resources/tools/client.tsx:39-84` lists six tools linking to
`/resources/tools/{horoscope,muhurat,panchang,kundli-matching,gemstones,vastu}`. **None of the six
route directories exists** (verified: `ls apps/web/app/resources/tools/<name>` → MISSING × 6). The
page metadata sells them as "Muhurat & Panchang Calculators" (`page.tsx:7-13`) and the JSON-LD
`CollectionPage` schema repeats the claim. This is the same bug class as Search spec **D-11**
(advertised surface, unimplemented route, no test on the seam) and it is currently the single
largest gap between what the site promises and what it computes.

**P-11 — UI asserts astronomical computation it does not perform.**
`apps/customer/app/(protected)/profile/reminders/page.tsx:34` initialises
`useState(15) // Amavasya (Mahalaya)` and `:196-202` lets the user free-pick "Tithi (1 to 15)", then
`:86` prints *"Ritual reminder created with astronomical Panchang calculation."* The server does call
a real engine (`CustomerSpiritualController.php:289-300`) — and on any exception sets
`next_occurrence_at = null` and **swallows it** (`catch (\Throwable)`), then returns 201 Created. So
the UI's success sentence is emitted whether or not the calculation ran, and the one piece of
astrology in the module (`remedies/client.tsx`, `gemstone/client.tsx`) contains no ephemeris at all.

**P-12 — `dateModified` is not true.**
Across the 1 265-page corpus, `lastUpdated`/`lastReviewed` take **five distinct values**:
`2026-05-27` × 585, `2026-03-20` × 342, `2026-05-25` × 243, `2026-03-19` × 92, `2024-05-15` × 3.
1 262 of 1 265 pages carry one of these bulk stamps. A mass-stamped modification date propagated
into Article `dateModified` misrepresents the page's history and is the pattern Google's spam
guidance associates with scaled content generation; the three 2024 pages show the corpus was seeded
in at least two batches and nobody noticed the drift.

**P-13 — Doorway-page pattern is already shipped at scale.**
`apps/web/content/pages/` contains `lakshmi-ganesh-puja-` prefixed pages × **21**
(`-delhi`, `-dwarka`, `-east-delhi`, `-ghaziabad`, `-gurgaon`, `-noida`, `-rohini`, `-north-delhi`,
`-south-delhi`, `-west-delhi`, plus variant guides), `festival-observance-planning-` × **5**,
`ancestor-rite-timing-` × **5** — the second and third families being combinatorial
"before-a-move / before-booking-a-priest / for-diaspora-time-zone / when-elders-want-scriptural-context
/ when-the-family-wants-a-second-opinion" permutations of one article. This is the exact thin-page
failure the brief warns about, present today, and it will be *multiplied* by any Panchang page
family built the obvious way (`city × date × language × tradition × convention`).

**P-14 — No locality/coordinate registry, and it is the same root cause as Search.**
There is no `seo_places` table and no place registry; locality is a free-text `city` string
(Search spec D-04, and D-03 for pandits). A Panchang page family needs the *same* canonical place
identity — coordinates, IANA zone, elevation, and the alias resolution `Benares`/`वाराणसी`/`Varanasi`.
**Building a second registry inside SeoContent would be the most expensive possible outcome of this
spec.** §8 makes it a shared dependency on Search spec E-SR-02 instead.

**P-15 — `seo` routes still use the retired shared throttle counter.**
`SeoContent/Routes/api.php:21` → `middleware(['throttle:60,1'])`. ADR-0025 retired precisely this
pattern for Search (`Search/ModuleServiceProvider.php` defines four named limiters) because the
`sha1(domain|IP)` shared-counter behaviour lets one surface exhaust another's budget (runbook §15).
`seo:registry` and `seo:search-docs` are the endpoints the ISR render path hits on every revalidate,
so a crawler burst can starve ordinary page renders. The module also declares no permissions,
events, or scopes at all (`module.json`), so there is nothing to gate a future admin surface with.

**P-16 — Cluster relations are keyed on non-unique text.**
`SeoCluster::$primaryKey = 'cluster'` (a string) and `SeoPage::cluster(): BelongsTo` joins on
`'cluster', 'cluster'` (`SeoPage.php:57`, `SeoCluster.php:29`). `SeoPageSearchProvider::clusterBasePath()`
then runs `$page->cluster()->first()` per document during reindex — an N+1 across the corpus,
and `url_path` is built from `$this->clusterBasePath($page) ?? $page->cluster`, so a missing cluster
row silently changes a **public URL**. Text-keyed relations with a fallback that rewrites URLs are a
publication hazard once programmatic pages exist.

**P-17 — Ritual-identity attributes are classified as special-category for analytics and as free
text for the product.**
`WarehouseExportService::transformToSignalEnvelope()` carries a `$bannedKeys` list commented
*"Special-category exclusion guard"* containing `gotra`, `nakshatra`, `rashi`, `kuladevata`,
`shraddha`, `family_member`, `birth_date`, `death_date`, `phone`, `email`, `name` — the platform has
already decided these attributes are too sensitive to ship to the warehouse, and enforces it with
`str_contains` matching plus a primitive allowlist. The same attributes are then returned in
plaintext by the product's own API (`CustomerSpiritualController.php:61,122`,
`CustomerPrivacyController.php:99,113`) and stored with **no value validation at all**:
`'nakshatra' => ['nullable','string','max:50']` (`:89,183`) — while the two canonical lists needed to
validate them already exist in this repo, `NakshatraCalculator::NAKSHATRAS` (the 27 names) and
`Pandit\Contracts\GotraList::CANONICAL` (the sapta/ashta-rishi lineages).
This is not an accusation of a leak; returning a user's own data to that authenticated user is
normal. It is a **classification inconsistency with three concrete consequences**:
1. The most sensitive class of data the platform holds has no stated retention or deletion posture,
   only an export ban. `customer_family_members` rows can describe people who are not the account
   holder — and the banlist explicitly protects `family_member` and `death_date`, so some rows
   describe deceased persons who cannot consent to anything.
2. Every future personalization keyed on these values is built on unvalidated strings. `Tara Bala`
   and `Chandra Bala` (§6.3) require a **natal nakshatra**; today that input is free text, so a
   typo'd `"shravna"` silently degrades an astrological suitability calculation that the platform
   will have presented as tradition-derived.
3. The fix is nearly free — `Rule::in(array_keys(NakshatraCalculator::NAKSHATRAS))`,
   `Rule::in(GotraList::CANONICAL)`, a declared retention class, and the erasure path extended to
   cover both tables. T-SC01-09.

Rule for the rest of this spec: **the canonical calendar vocabularies (nakshatra, rashi, masa,
yoga, karana, gotra) are enums owned by the kernel, never free text.** A surface that stores one of
these as a string is a review blocker, because after §3 the kernel is the only place those name sets
legitimately exist.

### 1.3 Measured evidence (reproducible)

All P-01…P-07 numbers came from executing the shipped classes, not from reading them. Appendix A
holds the transcript and the 60-line reproduction script. **Requirement: it becomes
`tests/Unit/Calendar/AyanamsaParityTest.php` in E-SC-01, so the defect cannot recur silently.**

### 1.4 Assumptions to prove, not inherit

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | A truncated Meeus lunar series is accurate enough to decide a tithi/nakshatra boundary for the supported year range | compare ≥500 boundary instants against IMD Rashtriya Panchang / IAE, same convention, same place | G-ASTRO-1 |
| A-2 | `SolarLongitude`/`LunarLongitude` are tropical (⇒ ayanamsa required) | confirmed by P-01/P-02 execution | done |
| A-3 | Lahiri/Chitrapaksha is the right default for the intended audience, and the audience accepts a documented default | named editorial + consultant sign-off | G-PROF-1 |
| A-4 | Festival observance rules can be expressed as auditable declarative data | 12 pilot festivals written and reviewed by a qualified source | G-FEST-1 |
| A-5 | `central` can hold event rows at `places × days × profiles` scale | disk/row projection signed off before the page family opens | G-CAP-0 |
| A-6 | PHP can precompute a year for N places inside the deploy window | measured `calendar:build --year` wall clock | G-CAP-1 |

---

## 2. Scope

### 2.1 In scope
1. Correct sidereal astronomy: ayanamsa registry, time scales, ΔT, solvers, polar handling (§4).
2. All five limbs with **transition instants**, plus rashi, moonrise/set, sankranti, Adhika/Kshaya masa (§5).
3. Muhurat as a rule engine + interval algebra (§6) — nothing is a hardcoded clock range.
4. Festival **definitions and observance rules**, with derived occurrences and explanations (§7).
5. Relocating the kernel to a shared domain module, with the Customers consumer migrated (§3).
6. Canonical places, shared with Search (§8) — explicitly **not** a second registry.
7. Generation-versioned persistence, atomic annual publication, replay (§9).
8. A validation regime with real external gold (§13) — the highest-value section in this document.
9. Selective SEO publishing with doorway suppression and truthful structured data (§11).
10. The six missing calculator surfaces, implemented against the engine (§15).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner |
| --- | --- | --- |
| Astrological *phalita* claims (predictions, remedies efficacy, gemstone recommendation as fact) | not a computation this platform can validate; religious/health-adjacent harm | §11.6 disclosure rules; content policy |
| Kundali matching / Lagna-based natal charts as a shipped calculator | needs full-graha ephemeris + dasha systems; distinct product | separate ADR + epic |
| External astronomy APIs or a computed-in-JS ephemeris | §2.3 boundary; and two sources of truth for a date is the worst outcome | none |
| Swiss Ephemeris as a **runtime** dependency (C extension) | deployment-proven path unavailable; see §4.2 — permitted as a *validation oracle* only | G-ASTRO-1 |
| Elasticsearch/Redis/Kafka/vector DB, any broker | platform boundary | ADR-0033 |
| A second locality registry | P-14 | Search E-SR-02 |
| Automated ritual-instruction authoring | P-12/P-13: mass-generated devotional text is a trust and SEO liability | §11.4 editorial gate |

### 2.3 Boundary
Laravel + existing relational DB + local files. No external astronomy service on the request path.
Server-rendered calendar content; the browser never computes a tithi.

---

## 3. The ownership decision (read this before §16)

Two options were considered.

**Option B — build the engine inside `app/Modules/SeoContent/Domain/`.** Matches the module's name
and the brief's directory sketch. Consequence: a **second** astronomy implementation alongside the
815 LOC already shipping in `Customers`, i.e. two tithi engines disagreeing about a festival date,
which is precisely the D-13 failure mode the Search spec had to spend an epic retiring.

**Option A (recommended): one shared kernel, two consumers.**

```text
app/Domain/Calendar/                 (new first-class kernel; no HTTP, no Eloquent)
  Astronomy/   JulianDate, TimeScales, DeltaT, Ayanamsa{Registry,Lahiri,…},
               Ephemeris{Solar,Lunar,Provider interface}, CoordinateTransformer,
               RootSolver{Bracket,Bisect,SafeguardedNewton}, Horizon{Sunrise,Sunset,Moonrise,Moonset}
  Panchang/    Tithi, Nakshatra, Yoga, Karana, Vara, Rashi, LunarMonth,
               AdhikaMasa, KshayaMasa, Sankranti, BoundaryResolver, PanchangDay
  Muhurat/     DayDivision, RahuKaal, Yamaganda, Gulika, Abhijit, Choghadiya, Hora,
               Lagna(P2), IntervalAlgebra, ActivityRuleInterpreter, RuleRegistry
  Festivals/   FestivalDefinition, ObservanceAnchor, RuleSet, TraditionProfile,
               RegionalProfile, Resolver, OccurrenceSet, ExplanationBuilder
  Profiles/    CalculationProfile (ephemeris+ayanamsa+masa-system+sunrise-convention+rule versions)
  Contracts/   ComputesPanchang (moved verbatim), ComputesOccurrence, PlaceSource, EphemerisProvider
  Data/        versioned coefficient + ayanamsa + rule files (immutable, checksummed)

app/Modules/SeoContent/     consumes the kernel; owns publications, not mathematics
app/Modules/Customers/      its `Domain/Panchang/*` classes DELETED after the consumer is rewired
```

Migration is a **move, not a rewrite**: `ComputesPanchang`, `PanchangOccurrence`,
`AnchorOccurrenceResolver` relocate with their tests; `Customers` then depends on the kernel
contract. Deptrac gains a `Calendar` layer that may be depended on by any module and may depend on
nothing above it. `module.json` for SeoContent gains the permissions/events it currently lacks.

Why this is the decision that matters more than any algorithm here: the single most dangerous
outcome available to this product is **two code paths printing two different dates for the same
place and day** — one on a reminder, one on a public SEO page, one in a WhatsApp notification body.
P-01/P-02 are only survivable because they are currently uniform. They are not survivable once they
are duplicated.

---

## 4. Astronomical kernel

### 4.1 Ayanamsa first
`Ayanamsa::at(JulianDate)` — versioned registry with Lahiri/Chitrapaksha as the documented default
(A-3), and true-nirayana/Raman/KPV/Yale as selectable profile values. Every sidereal quantity in the
system MUST be produced by an explicit call, never implied by a variable name. The **invariant test**
is cheap and is the whole fix for P-01/P-02:

```
nakshatra(moon) MUST equal nakshatra(moon_tropical − ayanamsa(t))
masa(sun)       MUST equal masa(sun_tropical  − ayanamsa(t))
elongation(sun,moon) MUST be ayanamsa-invariant   (the P-04 property, now pinned)
```

### 4.2 Model ladder, and where each is allowed
| Stage | Model | Allowed for |
| --- | --- | --- |
| v1 (today, corrected) | Meeus truncated solar + lunar principal terms | production, **only after** G-ASTRO-1 |
| v2 | expanded lunar terms / truncated ELP2000 series, coefficients in checksummed data files | production when v1 fails a boundary gate |
| oracle | JPL DE440 via Horizons, or `Swiss Ephemeris` CLI | **validation only**, never runtime, never on a request path |

The brief is right that VSOP87 has variants and that mixing coefficient sets is a bug; whichever is
adopted, `CalculationProfile` records `ephemeris_model + ephemeris_version + ayanamsa_code +
ayanamsa_version + series_variant` and the data files carry a checksum verified at boot.
`SolarLongitude`'s `0.00569°` aberration and nutation terms are already frame-conflated in a way that
a VSOP87/ELP adoption must be checked against — do not "improve accuracy" by adding terms from a
different reference frame.

### 4.3 Time scales — currently conflated
Implemented today: civil date → UTC time-of-day only (P-06). Required, and each is separately tested:
`JD`, `JD2000 centuries T (TT)`, `ΔT = TT − UT` (NASA polynomial, piecewise by era, extrapolation
flagged), `UT1` (declared ±0.9 s limit where `ΔUT1` is not modelled), GMST → **local** sidereal time,
IANA zone resolution (`DateTimeZone`, never a hardcoded `+05:30`), date-boundary normalisation, and
equation-of-time/solar-noon. ΔT matters: 100 s of ΔT error moves the Moon ~0.0006°/s·100 ≈ 0.06′,
negligible for a tithi today, but the requirement is to *know* that rather than assume it.

### 4.4 Event boundaries — the missing half of the design
Every current limb value is an **instantaneous reading at sunrise**. A Panchang needs
`starts_at`/`ends_at` per limb, so §5 emits boundaries via: coarse uniform sampling → sign-change
detection → bracket → bisection (mandatory, always correct) → optional safeguarded Newton refinement
→ declared precision stored in a boundary-precision column. Unconstrained Newton near a poorly
conditioned crossing is prohibited. No hardcoded "a tithi is 24 hours" anywhere, ever.

### 4.5 Sunrise/sunset, honestly
Retain the fast Meeus/USNO-style approximation for *display* day-parts, but: compute from a proper
`JulianDate` sequence with ΔT, apply the −0.833° upper-limb apparent threshold as a **profile
constant**, accept elevation and a refraction model choice, and handle **no-rise / no-set as a
first-class result**. `HorizonResult{risen:bool, at:?Instant, reason:?enum}` — polar day/night,
always-up, always-down, invalid coordinates, elevation-unsupported. The P-05 fix is not a different
fallback time; it is **refusing to return a `PanchangDay`** and rendering a defined unsupported
state. Given P-06, the API contract MUST take `place_id` and resolve zone server-side; a
client-supplied zone is not accepted (removes the `anchor_rule.timezone` manipulation vector).

### 4.6 Numerics
Double for angles; **integer microseconds from UNIX epoch for every instant**; UTC canonical storage;
named zone only for display; boundary comparisons on integers, never on formatted strings; no
rounding a longitude before deriving a limb. `CarbonImmutable` throughout, never `now()` inside a
pure calculator.

---

## 5. Panchang engine

### 5.1 The five limbs, complete
`tithi` (+paksha, +index_in_paksha, +both boundary instants) · `nakshatra` (+pada, +boundaries) ·
**`yoga`** (sum of sidereal longitudes ÷ 13°20′ — the brief's warning that subtracting instead of
adding is the classic error gets its own unit test) · **`karana`** (6° half-tithi, with the explicit
7-moving + 4-fixed naming table; no arithmetic shortcut that rotates the fixed four — `Vani`/
`Bava`/`Balava`/`Kaulava` at the correct fixed slots) · **`vara`**, with `civil` vs
`sunrise-anchored` both emitted and the page declaring which it prints (brief §4.5).

Also: `rashi` (Sun, Moon, sidereal), moon phase and age, sunrise/sunset/solar noon/day+night
duration, moonrise/moonset (topocentric, or explicitly unsupported), sankranti instants, lunar month
(Amanta **and** Purnimanta derived from one underlying sequence — never two calendars), Adhika Masa,
Kshaya Masa.

### 5.2 Day anchoring
`PanchangDay = [sunrise(d) , sunrise(d+1))` with the `Udaya Tithi` rule preserved for the headline
value, and **all** limbs listed with their intervals so a day containing two tithis shows both, and
a tithi prevailing at two successive sunrises shows as repeated. `sunrise-anchored` vs
`midnight-anchored` is a profile dimension, not a code fork.

### 5.3 Adhika / Kshaya masa (fixes the dead `$isAdhika`)
Detect by counting solar ingresses per lunation over the *sidereal* Sun (P-02): zero ingress ⇒
Adhika; two ⇒ Kshaya. These drive the `LunarMonthCalculator::$isAdhika` parameter that currently has
no caller (P-07). Adhika Masa is a named-SEO-surface event: an intercalary month appearing and
vanishing in festival counts is exactly the case where an unexplained gap looks like a bug to a
user, so §7.5's explanation object is mandatory there.

---

## 6. Muhurat engine

### 6.1 Day-part rules (P0, all currently absent per P-08/P-10)
`DayDivision` splits sunrise→sunset and sunset→sunrise into 8 (and 15 for Abhijit-adjacent) **equal
local** parts. Rahu Kaal / Yamaganda / Gulika: weekday→part maps are **config rows** per rule set,
with the common 8-part table from the brief as the default profile and alternative regional divisions
as *separate profiles*, never silently merged. Abhijit: one-fifteenth of daylight centred on local
apparent solar noon — explicitly **not** a fixed 11:45–12:30, which is the failure the brief names
and which would otherwise be copied into every city page. Choghadiya: separate day and night
sequences, weekday-specific. Hora: 12 unequal day + 12 unequal night hours, planetary sequence
starting from the weekday ruler, Chaldean order, per profile.

All of these MUST be computed from the actual local sunrise/sunset of that date at that place, so
Delhi and Chennai print different times for the same named muhurat — the fact that makes it a
calculation rather than a template.

### 6.2 Interval algebra (the real algorithmic requirement)
`IntervalAlgebra` — normalize criteria into half-open intervals on one absolute UTC axis, merge,
intersect required sets, subtract prohibited sets (Rahu Kaal, Yamaganda, Gulika, **and the tithi
windows a named tradition excludes**), accumulate the satisfied-condition set per emitted interval,
merge adjacent equal-qualification intervals, return each window **with the conditions that produced
it and the conditions that truncated it**. `O(B log B)` for B boundaries; `O(B)` sweep; no nested
per-day filtering. This one class replaces the entire "hardcoded exclusions appended to a daily
calendar" failure mode.

### 6.3 Activity rules — data, never code
`ActivityRuleInterpreter` over a constrained JSON/DTO rule grammar — `REQUIRE_TITHI`,
`REQUIRE_PAKSHA`, `REQUIRE_MASA`, `REQUIRE_NAKSHATRA`, `REQUIRE_WEEKDAY`, `REQUIRE_YOGA`,
`REQUIRE_KARANA`, `REQUIRE_SUNRISE_OVERLAP`, `REQUIRE_{PRADOSHA,NISHITA,MADHYAHNA,MOONRISE}_OVERLAP`,
`EXCLUDE_{RAHU_KAAL,YAMAGANDA,GULIKA,BHADRA,PANCHAKA,CONFLICTING_INTERVAL}`,
`REQUIRE_ORDERED_CONDITIONS`, with AND/OR, required-vs-optional, priority and effective-dating.
**Storing executable PHP in the database is prohibited** (the brief is right, and it is also the
`search_deletes`-style injection surface in miniature): the grammar is validated against a JSON
Schema at write time, has a fixed maximum node count and evaluation budget, and is executed by a
total interpreter with no `eval`, no dynamic function names, and no file/network access.

Activity profiles: marriage, griha pravesh, naming, vehicle purchase, business inauguration, travel
commencement, property ceremony, general auspicious. Each rule set names its **source and reviewer**
(§13.6). `Tara Bala`/`Chandra Bala`/`Panchaka`/`Graha` conditions are P2/P3 and MUST display the
school and rule version they came from.

---

## 7. Festival & observance engine

### 7.1 Rules, not dates
The primary store is festival **definitions** + **observance rules**; occurrences are derived and
cached. A curated date table is never the source of truth. A manual approval may override a derived
occurrence, and the override MUST retain reason, source, reviewer, rule version and the derived value
it replaced — otherwise the audit chain in §9.4 is decorative.

### 7.2 Anchors beyond sunrise (the brief's most important calendar insight)
Pradosha, Nishita, Madhyahna, Moonrise, Full-interval, Combined, Sunset. Modelling only "tithi at
sunrise" is what makes Janmashtami, Mahashivratri and Ekadashi parana wrong while looking plausible,
because those turn on an *interval overlap* rather than a sunrise reading. `ObservanceWindow` is
therefore a first-class object with both bounds, computed per place and profile, and a festival
occurrence is `(festival, place, profile, date, window_start, window_end, anchor, rule_version,
explanation)`.

### 7.3 Pilot set for G-FEST-1
Twelve rules written, reviewed and asserted first: Diwali (Lakshmi + Govardhan + Bhaidooj as distinct
observances), Janmashtami (Smarta vs Vaishnava), Mahashivratri, Holi (**Holika Dahan and Holi as two
separate occurrences**), Ram Navami, Ganesh Chaturthi, **Ekadashi + parana (the Dashami-contamination
rule, both traditions)**, Ganga Dussehr, Ayudha Puja, Diwali-region variants, Makar Sankranti
(solar ingress-driven, not a fixed 14 Jan), Thai Pongal / Uzhvar (regional solar names). Each gets a
golden expectation per place and profile.

### 7.4 Tradition and regional profiles
Ten profile families (North Indian, South Indian, Gujarati, Bengali, Marathi, Tamil, Telugu, Kannada,
Malayalam, Odia) as **configuration families with documented rules** — not claims that a region is
monolithic. Where two observance dates are both valid, the product shows both with reasons; it does
not silently pick one, and it does not let a page template pick differently from a notification body.

### 7.5 Explanation is part of the result
Every occurrence carries `festival_date_explanation`: selected date, anchor, required tithi/nakshatra,
applied conditions, profile, rule version, alternatives, human-readable sentence, and the sources.
**This is the differentiating product** — not today's tithi, but when it starts, when it ends, why it
applies to this city, which tradition is in force, and how the observance date was chosen.

---

## 8. Places — shared with Search, not duplicated

Consumes Search spec **E-SR-02** (`search_regions`). Calendar needs four fields that the current
free-text `city` cannot give it, and they land in the shared registry rather than a `seo_places`
table:

```text
region_id (canonical) · latitude · longitude · elevation_m · iana_timezone · geohash6
+ kind (city|metro|district|state|locality) + is_pilgrimage_centre · is_serviceable
+ coordinate_source (surveyed|curated|geocoded) + coordinate_confidence + places_version
```

Hard requirements: coordinates and zone are **server-resolved** (closes the P-06 manipulation
vector); `coordinate_version`, `timezone_version` and `places_version` participate in every cache key
and generation (§10.4); a place with unverified coordinates may be read but MUST NOT become a
published Panchang page (P-14); Ujjain is modelled as a place, not as a magic default in a function
signature (`PanchangCalculator.php:26-27`).

---

## 9. Persistence, generations, publication

### 9.1 Tables (additive; nothing here changes `seo_pages` semantics)

```text
calendar_profiles            ephemeris model+version+series, ayanamsa code+version, masa system,
                             sunrise convention, day anchor, rule set versions, checksum, is_default
calendar_places              region_id FK (§8), lat, lng, elevation_m, iana_timezone, versions
calendar_astronomical_events place, profile, event_type enum(tithi|nakshatra|yoga|karana|sankranti|
                             new_moon|full_moon|sunrise|sunset|moonrise|moonset|lagna), index_in_series,
                             starts_at_us, ends_at_us, precision_us, generation
                             UNIQUE (place, profile, event_type, index_in_series, starts_at_us)
calendar_panchang_days       place, profile, local_date, sunrise_at_us, sunset_at_us, solar_noon_us,
                             masa_amanta, masa_purnimanta, is_adhika, is_kshaya, vara_civil,
                             vara_sunrise, horizon_status, confidence, explanation_ref, generation
                             UNIQUE (place, profile, local_date)
calendar_muhurat_windows     place, profile, rule_set, date, kind enum(rahu_kaal|yamaganda|gulika|
                             abhijit|choghadiya|hora), sequence, window bounds, source bounds
calendar_festival_definitions slug, canonical names json, devanagari, description_ref, is_active
calendar_festival_rules      definition, tradition_profile, regional_profile, anchor, required/
                             optional/excluded condition sets, precedence, effective_from/to, rules_version
calendar_festival_occurrences definition, place, profile, observance_date, window bounds, rule_version,
                             generation, explanation json, approval_status, reviewed_by, overridden_from
calendar_generations         id, profile, scope(year|place-set), started/finished, row counts,
                             validation_report json, state enum(building|validated|active|retired)
calendar_computation_log     input hash, profile, versions chain, resolved values, ms, cache state
seo_content_versions / _reviews   revision history + editorial approval (currently absent, P-12)
```

`seo_panchang_days` in the brief's sketch is fine in substance; the names are prefixed `calendar_*`
because the tables are consumed by `SeoContent`, `Customers` and (per Search §11) reminder/reco
surfaces — a module-prefixed name would re-commit P-09.

### 9.2 One profile per printed number
`profile_code` is required on every read. A page, a notification and an API response for the same
city and date MUST resolve the same profile, enforced by an integration test that renders all three
and diffs.

### 9.3 Atomic annual publication
A build writes a **new generation**, validates it, then activates it with a single pointer swap.
Partial overwrite of a published annual calendar is prohibited. `calendar_generations.state` is the
only thing the read path consults. Retirement keeps the previous generation queryable for
reproduction and dispute resolution.

### 9.4 The audit chain, persisted
Per the brief's release requirement, every stored result MUST be re-derivable:
`input → profile → ephemeris+version → ayanamsa+version → derived values → calendar rule+version →
result → validation status`. Stored in `calendar_computation_log` / the explanation object, so a
date disputed six months later can be reproduced *and explained*, and so P-01-class defects are
findable by re-running a generation rather than by archaeology.

---

## 10. Performance without cheating on correctness

### 10.1 Workload split

| Workload | Strategy |
| --- | --- |
| Single cached daily Panchang | row read; no ephemeris evaluation |
| Uncached daily Panchang | bounded on-demand compute, then persisted |
| Monthly / annual | **precomputed generation**, never on the request path |
| Boundary instants | batch-computed once per place/profile/year |
| Muhurat day-parts | precomputed with the day rows |
| Activity muhurat search (≤90 days) | on-demand interval algebra **over precomputed events** |
| Festival occurrences | precomputed + editorially approved |
| SEO metadata / sitemap | emitted at publication time from eligible rows |

**The request path never computes a year.** The API MUST refuse a range beyond budget and return a
defined `RANGE_TOO_LARGE` rather than degrading into an unbounded loop — the Search spec's
candidate-budget discipline applies to astronomical work too, and a 10-year × 200-place query is an
outage shaped like a feature request.

### 10.2 Shared evaluation
Evaluate Sun and Moon **once per instant** and reuse for tithi, nakshatra, yoga, karana, rashi and
phase. Today `PanchangCalculator` does this for one instant only; the boundary resolver must do it
across a sampling grid without re-evaluating the lunar series per limb.

### 10.3 Adaptive sampling
Coarse grid (e.g. 6 h) → detect crossing by sign change → bracket → bisect → optional safeguarded
Newton → store with declared precision. Grid density is a profile parameter, so G-ASTRO-1 can raise
it without restructuring the code.

### 10.4 Version-aware caching
Cache key = `local_date | place_id | coordinate_version | timezone_version | places_version |
profile_code | ephemeris_version | ayanamsa_version | masa_system | sunrise_convention |
rules_version | generation`. Any change invalidates affected rows structurally rather than by
remembering to flush. **This closes a live defect**: `ProcessRitualRemindersCommand.php:72` caches
`"panchang:{tenant}:{date}:{anchorHash}"` for 86400 s with no version component, so a corrected
engine cannot propagate to a cached occurrence until the day rolls over.

### 10.5 Targets (proposed, to be measured — not capabilities)

| Operation | P95 |
| --- | --- |
| Cached daily Panchang | 15 ms |
| Monthly calendar | 30 ms |
| Festival lookup | 20 ms |
| Daily muhurat lookup | 25 ms |
| Activity muhurat, 90-day range | 500 ms |
| SEO page retrieval | 20 ms |
| Uncached single-day compute | 500 ms |
| Annual build for 1 profile × N places | measured at G-CAP-1, published |

Measured under PHP-FPM with real OPcache and worker limits, cold and warm reported separately —
the Search spec's rule, and the reason CLI timings in this module are advisory only.

---

## 11. SEO publishing engine

### 11.1 The trap, stated plainly
`P-13` shows the corpus already contains combinatorial locality/variant pages. The obvious
implementation of this brief — `/panchang/{city}/{date}` — produces **places × days** pages and
multiplies that problem by two orders of magnitude. Google's spam guidance names both doorway pages
and scaled content abuse; a calendar is uniquely susceptible because every cell of the matrix
"looks like" a page. So the publishing engine is a **filter**, not a generator.

### 11.2 Index policy (eligibility is computed, not asserted)

```text
EligibilityScore = f(search demand from real queries, distinct substantive content vs sibling
                   pages, canonical place resolution, data completeness, computation validation
                   status, editorial approval)
```

| Family | Default |
| --- | --- |
| `/panchang` (hub) | index |
| `/today-panchang/{place}` | index for the **top-N** validated places only |
| `/panchang/{yyyy}/{mm}/{place}` | index if demand + distinct monthly content |
| `/panchang/{yyyy}/{mm}/{dd}/{place}` | **noindex,follow** by default; opt-in per place × date band |
| `/hindu-calendar/{yyyy}` | index |
| `/festivals/{slug}` (evergreen guide) | index |
| `/festivals/{slug}/{yyyy}` | index only when the year page adds independent value (dates + regional differences) |
| `/festivals/{yyyy}` | index |
| `/muhurat/{activity}` | editorial page, index; **calculator is an app, not an indexable page** |
| `/rahu-kaal/{place}` | index top-N only |
| `/festivals/{slug}/{region}` | curated only |
| anything × × × convention/language/profile combination not demand-justified | blocked by a **cardinality budget** |

Mechanism (not policy prose): a `seo_publish_eligibility` gate evaluated at publication time, a
**hard page-family budget** (`config('seo.max_indexable_pages.<family>')`), a CI assertion that a
generated sitemap cannot exceed it, and a duplicate-similarity check (SimHash over rendered body)
that fails the build when a new page is ≥ threshold similar to a sibling. P-12/P-13 get a standing
guard rather than a one-time cleanup.

### 11.3 Separation of evergreen from computed
A Diwali guide is one document with annual computed sections injected from the occurrence engine —
not a new article each year. `seo_content_versions` + `seo_content_reviews` (absent today) hold the
editorial history; the computed section is never stored as if it were authored text.

### 11.4 Editorial integrity gates
Unreviewed devotional or ritual content MUST NOT publish. Every guide requires author, reviewer,
tradition/profile followed, sources consulted, last review date, calculation version, change
history. No generated ritual instruction is presented as authoritative religious advice.

### 11.5 Structured data — truthful or absent

| Page | Type |
| --- | --- |
| Festival guide | `Article` (or `WebPage`) with real `datePublished`/`dateModified` from the version table |
| Calendar landing | `WebPage` + `BreadcrumbList` |
| Festival *event* with a real scheduled public event, venue, time, tickets/attendance | `Event`, **only then** |
| Navigation | `BreadcrumbList` |

Do not attach `Event` to religious observance dates by default — the brief's caution is correct and
it is also the fastest way to a manual action. **`dateModified` MUST derive from the content-version
table, not from the bulk `lastUpdated` stamp (P-12)**; a JSON-LD validator test asserts that every
emitted `dateModified` corresponds to an actual version row. Canonical resolution, `hreflang` via
`@navdhya/i18n` `HREFLANG_BY_LOCALE` (already real), sitemap built from eligible pages only, no
empty/duplicate date-location pages, and profile/convention parameters **excluded from the indexable
URL space** (query-parameter variants are canonicalised away).

### 11.6 Claim discipline (product + legal + trust)
Every computed surface names its methodology and its limits in user-facing text, not in a footer:
"Calculated using the Drik Siddhānta convention with Lahiri (Chitrapaksha) ayanamsa, generation
`G-2027-03`, validated against … — see method." Where validation is not yet established (§13) the
page says so. Traditional muhurat suitability is reported as *what the selected tradition
prescribes*, never as an established fact about outcomes. P-11's "created with astronomical Panchang
calculation" sentence becomes conditional on the calculation having actually run.

---

## 12. Instrumentation

Rides the transport established in Search spec **E-SR-01** (`packages/analytics` registry, batcher,
collector) — no second pipeline. Calendar-specific, because the failure modes differ from search:

```text
calendar.day.viewed          calendar.month.viewed      calendar.festival.viewed
calendar.muhurat.computed    calendar.muhurat.searched  calendar.place.selected
calendar.profile.switched    calendar.computation.slow  calendar.computation.unsupported
calendar.date.disputed       calendar.outcome.recorded  calendar.export.requested
calendar.reminder.created    calendar.reminder.fired    calendar.reminder.cancelled
seo.page.rendered            seo.page.budget_blocked    seo.page.similarity_rejected
```

| Table | Purpose |
| --- | --- |
| `calendar_query_events` | one row per computation request: place, profile, date range, generation, cache state (`hit\|miss\|computed`), stage ms (astro, boundary, muhurat, assembly), horizon status, degrade reason, unsupported reason |
| `calendar_computation_stats` | hourly P50/95/99 by operation × profile × place bucket, plus unsupported-latitude counts |
| `calendar_validation_samples` | sampled live results paired with reference values (§13.5) — accuracy measured **in production**, not only in CI |
| `calendar_date_disputes` | user-flagged date disagreements with place, profile, expected value and resolution — the highest-value table here; a disputed festival date is a cultural-trust event and today has nowhere to go |
| `seo_page_engagement` | dwell, scroll depth, return visits per family vs `eligible`/`indexed` → the metric that proves a page family earns its existence |
| `calendar_outcome_links` | panchang/festival view → booking/reminder/consult, so "useful" is measured as ritual completion, not pageviews |

Guardrails mirror Search §10.7: session caps, dedupe, sampling for high-volume `seo.page.rendered`,
no PII in `calendar_date_disputes.free_text`, and retention ladders. Cardinality is genuinely
dangerous here — `generation × profile × place × date` explodes, so every dimension registers an
expected distinct count before it ships.

---

## 13. Validation regime — the highest-value section in this document

The brief's own framing is the right one: this module needs a stronger validation regime than
ordinary SEO content, and P-03 proves the current one is a name rather than a practice. **The rule
that must never be broken: gold is never generated by the engine it validates.**

### 13.1 Reference sources, and how each is used

| Source | Used for | Comparison caveat |
| --- | --- | --- |
| IMD **Rashtriya Panchang** | tithi/nakshatra/yoga/karana/vaar, sunrise/sunset, moonrise/set, nirayana longitudes, principal festivals | IMD uses an **adopted Central Station** for some timings and local meridians for others. A local-city result MUST NOT be scored against a central-station value and called an error. Explicit **reference profiles** are required, and each assertion names which IMD convention it compares to |
| **Indian Astronomical Ephemeris** | solar/lunar longitude, ΔT, element values | frame and epoch must match |
| **JPL Horizons / DE440**, or Swiss Ephemeris as offline oracle | independent high-accuracy positions + boundaries | validation only (§4.2), never runtime |
| **USNO** rise/set algorithm write-up | method cross-check + its own accuracy statement | the source explicitly warns no single formula is uniformly accurate — that warning becomes our tolerance justification |
| **IANA tz database** | civil time, DST, zone history | version pinned and recorded in the cache key |
| Named regional almanacs / consultant review | festival observance decisions (§13.6) | authority is *traditional*, not astronomical — recorded separately |

### 13.2 Golden corpus (real, this time)
Ten places × **four** years (2026–2029) × every limb, stored as checked-in data with per-cell
provenance and comparison-convention labels:

```text
Delhi · Mumbai · Kolkata · Chennai · Bengaluru · Varanasi · Ujjain · Jaipur · Guwahati · Srinagar
per day: sunrise/sunset/moonrise/moonset (+ zone and horizon convention used)
         tithi index/name/bounds · nakshatra index/pada/bounds · yoga · karana · vara (both anchors)
         masa (amanta + purnimanta) · is_adhika · rashi · phase
         festival occurrences per tradition×regional profile · reference citation per cell
```

`GoldenCorpusTest` is **replaced**, not extended: today it asserts self-consistency (P-03); the new
suite loads the external table and compares, tolerances declared per field, with an explicit
`assertNotSame` guard preventing the fixture from being produced by `calendar:export-self` — the
anti-P-03 mechanism, written as a test.

### 13.3 Accuracy gates (proposed; each must be *measured* before it is claimed)

| Measurement | Gate |
| --- | --- |
| Solar sidereal longitude | ≤ 1′ |
| Lunar sidereal longitude | ≤ 1′ (model ladder escalates if not) |
| Tithi / nakshatra / yoga / karana boundary | ≤ 60 s |
| Sunrise / sunset | ≤ 2 min, at the reference convention |
| Muhurat interval arithmetic | exact to stored µs precision |
| Festival rule evaluation | 100 % agreement with approved fixtures |
| Timezone conversion | exact for the pinned IANA version |
| Transition continuity | no unexplained gap or duplicate across a year |

**A model that fails a gate does not become the production default** — including the current one.
P-08's "verified through 2028" wording is removed from `AnchorOccurrenceResolver` until §13.2
evidence exists for each year; honesty about an unverified range is worth more than a reassuring
constant.

### 13.4 Property tests (invariants, not examples)
`elongation` invariant under ayanamsa shift (the P-04 discovery, pinned forever) · at a tithi
boundary elongation ≡ 12n° ± tol · at a nakshatra boundary sidereal moon ≡ 13°20′·n ± tol · yoga sum
≡ 13°20′·n (and the **add, don't subtract** discriminator) · karana transitions ≡ +6° · day interval
`[sunrise(d), sunrise(d+1))` where a sunrise exists · masa derived from sidereal Sun ·
Adhika ⇔ zero ingress in a lunation · monotonic, gapless boundary sequences · **polar inputs produce
`HorizonResult.unsupported`, never a day** · determinism: same input + same profile ⇒ byte-identical
JSON · total non-throwing behaviour on invalid latitude/longitude/date instead of P-05's silent
zeroing.

### 13.5 Continuous validation
`calendar:verify --against=imd --year=Y --place=P` diffs live gold, publishing per-field pass rate,
max and p95 absolute error, boundary flip count vs previous generation, and the list of festival
dates that moved. Nightly on a 30-place panel, sampled against real served values
(`calendar_validation_samples`), with a CI gate: **a boundary flip between generations without a
recorded version change fails the build.** That single rule is the mechanism that would have caught
P-01/P-02 the day they shipped.

### 13.6 Editorial / traditional authority
Festival and muhurat rule sets require a named source and a qualified reviewer per rule, recorded in
`calendar_festival_rules` (source, reviewer, reviewed_at, rules_version). G-FEST-1 cannot pass on
code review alone. The platform reports what a convention computes; §11.6 wording is mandatory.

### 13.7 Migration hazard introduced by fixing P-01/P-02 (plan this, do not discover it)
Correcting the ayanamsa **changes outputs**. `anchor_rule` rows in `customer_ritual_reminders` were
written under **masa** values that are wrong for ~81 % of the year (P-02), and users picked them
against the platform's own UI, so the correction genuinely moves reminder dates. Nakshatra is not
affected by migration because it is never stored as an anchor (`AnchorOccurrenceResolver` matches
`tithi`/`paksha`/`index_in_paksha`/`masa` only, P-01). So the fix requires a decision recorded in an
ADR: reinterpret stored masa anchors, migrate them explicitly with an explanation to the user, or
version the rule interpretation and honour `rules_version` per row. Silent re-derivation would move
a person's śrāddha reminder without telling them — which is precisely the harm this module exists to
avoid. E-SC-01 therefore includes an impact analysis enumerating every consumer of the affected
fields **and a count of currently-active reminders whose resolved masa changes** before the
correction is deployed.

---

## 14. API contracts

One kernel behind every endpoint: HTTP, Artisan, ISR render and reminder worker MUST resolve the same
date for the same place/profile/day. A second implementation is a review blocker (this is the
`D-13` lesson from the Search spec, applied before it happens rather than after).

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/api/v1/panchang/day?date&place&profile&calendar` | `place` is a `region_id`/slug, never raw coordinates |
| GET | `/api/v1/panchang/month?year&month&place&profile` | precomputed rows only |
| GET | `/api/v1/panchang/year?year&place&profile` | festival-annotated |
| GET | `/api/v1/panchang/events?type&from&to&place&profile` | boundary instants |
| GET | `/api/v1/muhurat/daily?date&place&profile` | day-part windows with source bounds |
| POST | `/api/v1/muhurat/find` | activity + place + range + profile; bounded range; returns windows **and explanations** |
| GET | `/api/v1/festivals` · `/festivals/{slug}` · `/festivals/calendar?year&place&profile` | definitions, guide, occurrences |
| GET | `/api/v1/places/search?q` | resolves against the shared §8 registry |
| GET | `/api/v1/seo/pages/{slug}` | published editorial content |
| GET | `/api/v1/calendar/health` | **new** — active generation, validation pass rate, unsupported-latitude count, oldest published place-day |
| POST | `/api/v1/admin/calendar/{build,validate,activate,replay}` | capability-gated |
| GET | `/api/v1/calendar/export.ics?place&profile&from&to` | **new** — see §18 |

Envelope `{data, meta}`; profile, generation, versions, and the day anchor always in `meta` (they
are part of the answer, not debug extras). `ETag` keyed on `generation`, `Cache-Control` per
volatility. Errors: `PLACE_UNRESOLVED`, `PROFILE_UNKNOWN`, `DATE_OUT_OF_RANGE`, `RANGE_TOO_LARGE`,
`HORIZON_UNSUPPORTED` (P-05's honest successor), `NOT_VALIDATED`, `STALE_GENERATION`,
`RATE_LIMITED`. Rate limiters become **named per surface** (`panchang-day`, `panchang-month`,
`muhurat-find`, `calendar-ics`) replacing the inline `throttle:60,1` (P-15), matching the ADR-0025
pattern; `module.json` gains the permissions and events it currently lacks.

---

## 15. Next.js experience

Three surfaces, and the honesty rule above all of them.

**Daily Panchang** — place (searched, not free-text), Gregorian + Hindu date, tithi with both bounds,
paksha, nakshatra + pada, yoga, karana, both vara conventions, sunrise/sunset/moonrise/moonset,
day-part windows (Rahu Kaal et al.), festival indicator, and a persistent
**"how this was calculated"** panel: profile, ayanamsa, generation, validation status, method page
link. All limb intervals server-rendered.

**Monthly calendar** — month nav, festival + major-observance markers, tithi strip, Amanta/
Purnimanta switch (re-renders from profile-aware rows, not client math), regional profile switch,
day-detail drawer, print layout, per-locale labels via `@navdhya/i18n`.

**Muhurat finder** — activity, place, range, tradition; qualifying windows each annotated with
*why it qualified and what truncated it*; the rule source and version beside the answer; no green
tick presented as a religious or scientific guarantee.

Closes P-10/P-11: implement the six routes `/resources/tools/{panchang,muhurat,horoscope,
kundli-matching,gemstones,vastu}` — the **first two backed by the real engine**; the remaining four
either implemented against documented inputs or **delisted from the tool grid and from JSON-LD**,
because advertising a calculator that does not exist is worse than advertising fewer tools. A
Playwright test asserts every `href` in the tool grid resolves to a non-404, so this bug class
cannot return (identical to Search spec E-SR-01's AC).

Other rules: `next/revalidate` on generation activation (reuse the ADR-0027 mechanism, not a TTL);
`stale-while-revalidate` for the current day, long `max-age` for closed past ranges; `robots:
{index:false}` on every ineligible variant; the unsupported-latitude state designed as a first-class
empty state, not a wrong number; a dispute CTA wired to `calendar.date.disputed`; locale-specific
number/date formatting via `@navdhya/i18n`; the browser never computes a tithi.

---

## 16. Work breakdown

Estimate classes per methodology §1. Scaffold task first in each epic (§5 heuristics). Tasks need
the full 9-section template before `Approved`.

### E-SC-01 — Correctness triage *(P0. Nothing else in this document is safe to start before it)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-SC01-01 | Enumerate every consumer of nakshatra/masa/vara in PHP + TS, incl. stored `anchor_rule` values | S | AC: written impact analysis attached to the PR; every consumer named or the task is not Done. |
| T-SC01-02 | `Ayanamsa` registry + sidereal conversion at every use site (fixes P-01, P-02) | M | AC-1: `nakshatra(moon_tropical) != nakshatra(moon_tropical − ayanamsa)` fails, and equality holds after the fix. AC-2: §1.3 harness assertions green as `tests/Unit/Calendar/AyanamsaParityTest.php`. AC-3: elongation invariance test proves tithi output is **unchanged** by this fix. |
| T-SC01-03 | `HorizonResult` + refuse a day when no sunrise exists (P-05) | M | AC: Svalbard 21 Jun/21 Dec returns `unsupported`, never a `PanchangDay`; no fallback-constant path remains. |
| T-SC01-04 | Timezone correctness: server-resolved zone from place, day-rollover fix (P-06) | M | AC: no `timezone` read from client input anywhere; wrap test asserts the **date** increments; `($hour+1)%24` removed. |
| T-SC01-05 | Replace `GoldenCorpusTest` with external-gold comparison; delete unverified "verified through 2028" claim | L | AC: fixture provenance test rejects a self-exported corpus; every assertion names its reference convention. |
| T-SC01-06 | Stored-anchor migration decision + ADR (§13.7) | M | AC: ADR approved before the ayanamsa fix ships; per-row `rules_version` honoured or anchors migrated with user-visible explanation. |
| T-SC01-07 | Fix `ProcessRitualRemindersCommand` cache key to include all versions (§10.4) | S | AC: changing `ayanamsa_version` invalidates a cached occurrence within the same day. |
| T-SC01-08 | Named rate limiters + `module.json` permissions/events for SeoContent (P-15) | S | AC: no inline `throttle:n,m` in the module; `module:list` reflects the manifest. |
| T-SC01-09 | Canonical-vocabulary validation + retention class for ritual-identity attributes (P-17) | M | AC-1: `nakshatra`/`rashi`/`gotra` inputs reject any value outside `NakshatraCalculator::NAKSHATRAS` / `GotraList::CANONICAL` (negative test per field). AC-2: a declared retention class exists for `customer_spiritual_profiles` + `customer_family_members`, and the erasure path covers both — asserted, not documented. AC-3: the special-category classification in `WarehouseExportService` and the API surface agree, recorded in ADR-0040. |

### E-SC-02 — Kernel relocation & profile/version machinery
T-SC02-01 create `app/Domain/Calendar` + Deptrac layer; T-SC02-02 move the 14 classes with tests,
rewire `Customers`, delete the old namespace (P-09); T-SC02-03 `CalculationProfile` +
`GenerationManager` + atomic activation; T-SC02-04 `calendar_*` schema (§9.1); T-SC02-05
`calendar:build`/`validate`/`activate`/`replay`; T-SC02-06 computation audit chain (§9.4).
AC: two modules calling the kernel return byte-identical JSON for the same place/profile/day.

### E-SC-03 — Astronomy completion
T-SC03-01 Julian/`TimeScales`/ΔT with pinned era polynomials; T-SC03-02 GMST/LST + solar noon +
equation of time; T-SC03-03 `RootSolver` (bracket/bisect/safeguarded-Newton, precision declared);
T-SC03-04 boundary resolver for all four angular limbs; T-SC03-05 moonrise/moonset + elevation and
refraction as profile constants; T-SC03-06 JPL/Swiss offline oracle harness + `calendar:verify`;
T-SC03-07 model ladder v1→v2 escalation with measurements (A-1).
AC: §13.3 gates pass for the ten places × four years; every measured number committed as an
artifact, none asserted in prose.

### E-SC-04 — Panchang completeness (fixes P-07)
T-SC04-01 Yoga (with the add-vs-subtract discriminator test); T-SC04-02 Karana with the fixed-four
naming table; T-SC04-03 Vara both conventions; T-SC04-04 Rashi + phase + age; T-SC04-05 sankranti
set; T-SC04-06 Adhika/Kshaya detection wiring the dead `$isAdhika`; T-SC04-07 Amanta + Purnimanta
from one sequence; T-SC04-08 `PanchangDay` publication + `calendar:generate-days`; T-SC04-09 update
the "5 limbs" docblock to match reality.
AC: a day containing two tithis renders both; a repeated-at-sunrise tithi renders as repeated;
Adhika Masa appears in exactly the years the reference says it does.

### E-SC-05 — Places registry *(depends on Search E-SR-02)*
T-SC05-01 extend the shared region registry with coordinates/elevation/zone/versions (§8);
T-SC05-02 `calendar_places` + survey/curated provenance; T-SC05-03 `places:import` + validation of
coordinate sanity; T-SC05-04 block publication for unverified coordinates; T-SC05-05 retire
free-text `city` as a *filter* input in calendar paths.
AC: `place` resolves to exactly one coordinate+zone pair; a request supplying raw latitude is
rejected by validation.

### E-SC-06 — Muhurat
T-SC06-01 `DayDivision`; T-SC06-02 Rahu/Yamaganda/Gulika as config rule sets; T-SC06-03 Abhijit
from solar noon; T-SC06-04 Choghadiya day+night; T-SC06-05 Hora unequal planetary hours; T-SC06-06
`IntervalAlgebra`; T-SC06-07 rule grammar + schema-validated `ActivityRuleInterpreter` (no `eval`,
node and time budgets); T-SC06-08 activity profiles; T-SC06-09 Lagna (P2, bounded).
AC: Delhi and Chennai print different Rahu Kaal ranges for the same date; no muhurat constant
exists as a literal clock time in the codebase (grep gate); every emitted window carries the
conditions that produced and truncated it.

### E-SC-07 — Festivals
T-SC07-01 definitions + rules schema + validation; T-SC07-02 interval anchors (Pradosha, Nishita,
Madhyahna, Moonrise, Combined); T-SC07-03 `FestivalResolver` + `OccurrenceSet`; T-SC07-04 tradition +
regional profile registry (§7.4); T-SC07-05 the twelve pilot rules with named sources/reviewers
(A-4); T-SC07-06 override workflow with reason/source/reviewer; T-SC07-07 `ExplanationBuilder`;
T-SC07-08 alternative-date display; T-SC07-09 `festivals:resolve --year`.
AC: Holika Dahan and Holi resolve as two occurrences; Ekadashi parana resolves under both traditions;
every published occurrence references definition + rule + profile + generation + explanation +
approval state, enforced by a DB-level or test-level invariant.

### E-SC-08 — Publishing, pages, telemetry
T-SC08-01 content-version + review tables (P-12); T-SC08-02 `seo_publish_eligibility` gate + page
families (§11.2); T-SC08-03 cardinality budget + CI sitemap-size assertion (P-13); T-SC08-04
SimHash sibling-similarity gate; T-SC08-05 truthful JSON-LD + validator test tying `dateModified` to
a version row; T-SC08-06 canonical/hreflang/sitemap; T-SC08-07 evergreen/computed separation;
T-SC08-08 `cluster` relation keyed properly + reindex N+1 (P-16); T-SC08-09 calendar telemetry
(§12) on the Search E-SR-01 transport; T-SC08-10 `calendar:verify --report=seo` (thin-page ratio,
zero-content pages, unapproved published pages).
AC: a page family exceeding its budget cannot be activated; `calendar_computed_section`-less day
pages default `noindex,follow`.

### E-SC-09 — Public surfaces *(gated on E-SC-01…07)*
T-SC09-01 `/resources/tools/panchang` + `/muhurat` real calculators; T-SC09-02 the tool-grid
non-404 Playwright gate (P-10); T-SC09-03 delist or implement the remaining four tools honestly
(P-10); T-SC09-04 monthly view + print; T-SC09-05 method/explanation panels (P-08, §11.6);
T-SC09-06 dispute CTA + queue; T-SC09-07 fix the reminder copy to reflect what actually ran (P-11);
T-SC09-08 ICS export with place/profile/timezone baked in (§18); T-SC09-09 multi-city and profile
comparison views; T-SC09-10 historical/future range exploration UI inside the validated range.

### E-SC-10 — Advanced (P2/P3, gated)
Tara Bala / Chandra Bala / Panchaka rule sets with visible school + version; graha conditions
(needs a full-planet ephemeris — separate epic and A-1 evidence); natal-chart product (out of
scope here); calendar subscription feeds; notification pipelines using Search spec §11 preference
controls; per-tradition festival disagreement explainers; multi-year Adhika Masa UX.

---

## 17. Acceptance gates

| Gate | Blocks | Evidence |
| --- | --- | --- |
| **G-CORRECT-0** | **everything** | E-SC-01 merged: ayanamsa applied, polar refusal, timezone correctness, cache-key versioning, and the §1.3 harness living as a test |
| **G-ASTRO-1** | E-SC-03 exit, any public day | §13.3 tolerance table measured against named external references at the matching convention; report attached; **no self-generated gold** |
| **G-ASTRO-2** | model ladder | ladder choice justified by measured error, not by preference |
| **G-PROF-1** | default profile ship | ayanamsa + convention default signed off by a named reviewer; profile displayed on every surface; alternatives offered honestly |
| **G-LIMB-1** | "Panchang" wording in UI | all five limbs emitted with bounds; the "5 limbs" claim in code and UI is true, or both are corrected |
| **G-CAL-1** | monthly/annual pages | a full year of place-days generated with zero gaps/duplicates; Adhika + Kshaya cases exercised synthetically |
| **G-MUH-1** | muhurat surfaces | every window traceable to a rule set id + version + source; `IntervalAlgebra` property tests green; no literal clock-time constants |
| **G-FEST-1** | **public festival calendar** | twelve pilot rules reviewed by a named qualified source; 100 % agreement with approved fixtures; overrides carry reason/source/reviewer. **The brief's own red line: do not launch the public festival calendar until observance rules are independently reviewed** |
| **G-PUB-1** | annual pages live | atomic generation activation; `calendar:replay` reproduces an identical logical calendar; stale generation never served |
| **G-SEO-1** | any new page family | eligibility gate + cardinality budget + similarity gate green; sitemap size within budget; JSON-LD validator green; real `dateModified` provenance |
| **G-SEC-1** | admin surfaces | capabilities exist in `module.json`; audit on every rule/profile/override change; no executable code stored as a rule |
| **G-PLACE-1** | city pages | coordinates server-resolved and provenance-checked; unverified place cannot publish |
| **G-UX-1** | tool grid claims | Playwright proves every advertised calculator resolves and computes |
| **G-HONESTY-1** | any computed surface | unverified ranges are refused (P-08), every surface names profile + generation + validation status, `HORIZON_UNSUPPORTED` is designed not improvised, and **no UI asserts a calculation that did not run** |
| **G-CAP-0/1** | precompute scale | row/disk projection for `places × days × profiles`; annual build wall-clock measured and published (A-5, A-6) |

Release train: **Alpha** = E-SC-01+02 (correct, relocated, versioned; internal consumers only).
**Beta** = +E-SC-03+04+05 (full astronomy + five limbs + places; validated against external gold).
**RC** = +E-SC-06+07 (muhurat + festivals, G-MUH-1/G-FEST-1 green). **Prod** = +E-SC-08+09
(selective publishing, real public surfaces). E-SC-10 never before G-FEST-1 and G-HONESTY-1.

---

## 18. Feature additions worth having

Panchang comparison across profiles · multi-city comparison (Delhi/Mumbai/Chennai/Ujjain side by
side — the most-asked question in this domain, and the only honest answer is "here is what each
convention computes") · historical and future exploration strictly inside the validated range ·
sankranti calendar · Amavasya/Purnima calendar · Ekadashi + parana calendar · vrata calendar ·
printable month/annual layouts · ICS export with place, profile and timezone embedded ·
calendar subscription feeds · festival reminders honouring Search §11.7 privacy controls ·
regional festival-difference explainers · muhurat comparison across rule profiles · methodology
pages written for a non-specialist · editorial audit view · multilingual festival content (Hindi +
regional, using the real `@navdhya/i18n`).

**ICS export is the highest value-to-cost item in the whole spec**: no external infrastructure, it
carries place/profile/timezone so it survives contact with a foreign calendar app, and it converts a
page visit into a recurring, self-updating relationship with one city's observances. The export must
embed the profile name in `SUMMARY`/`DESCRIPTION` so an exported event cannot later be mistaken for
a different convention's answer.

---

## 19. Brutal engineering assessment

| Dimension | Assessment |
| --- | --- |
| Panchang mathematics | well-specified in the source brief |
| **Shipped Panchang correctness** | **provably wrong in 2 of 3 outputs today (P-01, P-02, measured)** |
| Native PHP feasibility | feasible; arithmetic is cheap, boundary solving is the work |
| Astronomical accuracy | **unvalidated** — no external gold exists (P-03) |
| Muhurat engine | does not exist; hard, and mostly a rule-governance problem |
| Festival intelligence | does not exist; harder than the astronomy |
| Programmatic SEO | feasible, but P-12/P-13 show the failure mode is already present |
| No external infrastructure | fine — computation is local and cacheable |
| Production readiness | **not established; today's calendar output should not be trusted for an observance decision** |

### Hidden assumptions inherited from the brief
That one default convention satisfies the launch audience (A-3); that coordinates are maintainable
(P-14/G-PLACE-1); that the truncated model covers the intended years (A-1 — currently *claimed* to
2028, never verified); that festival traditions can be expressed as auditable rules (A-4); that
`places × days × profiles` fits the database (A-5). All five are open, and five of the seven defects
in §1.2 are consequences of having asserted them rather than measured them.

### The five real weaknesses
1. Astronomy is not business logic: ~0.02° of lunar error is a flipped boundary, and P-01 is a 24°
   error — an order of magnitude beyond any precision debate.
2. Festival rules are harder than tithi. Computing Ashtami is arithmetic; deciding *which day is
   Janmashtami* for a given family is authority work. The codebase currently has neither rules nor
   reviewers, and P-01 means it would have applied the wrong nakshatra even if it had.
3. Muhurat is not one algorithm. Traditions differ; alternatives must be profiles, never merged.
4. SEO scale is a liability. 1 265 pages with 21 near-duplicate Lakshmi-Ganesh variants is evidence
   about our own behaviour, not a hypothetical risk.
5. Provenance. If a user disputes a date, the answer must be computable, not remembered. Right now
   `calendar_computation_log` does not exist and `dateModified` is a fiction.

### Adversarial questions a reviewer will ask, with the answer this doc commits to
*Why compute at all when a published almanac exists?* Because almanac data is not licensable as an
opaque blob, has no per-place granularity, and cannot explain itself; our differentiator is
explanation (§7.5), which requires our own kernel — and requires §13's external comparison to
justify it. *Why not embed Swiss Ephemeris?* Permitted as a validation oracle (§4.2), rejected as a
runtime dependency on deployment-proven grounds — and that rejection must be revisited if G-ASTRO-1
fails. *Why an SEO module owning astronomy?* It shouldn't; that is P-09 and §3. *Why five
limbs × places × days when only ten places have demand?* They shouldn't all publish; that is
§11.2's eligibility gate, and P-13 is why it is mandatory rather than advisable.

### The strongest version
An auditable computation kernel with a shared profile/version identity; **external** validation;
sidereal correctness; interval-based muhurat from declarative reviewed rules; festival occurrences
derived and explainable; generation-atomic publication; selective indexability; and user-visible
methodology with honest limits. More valuable than publishing every possible calendar page, and the
only version that can be trusted with someone's śrāddha date.

---

## 20. Risks & kill criteria

| # | Risk | Control | Kill / revert |
| --- | --- | --- | --- |
| R-01 | A second astronomy implementation appears (`Customers` vs new kernel) — the D-13 failure, in religion | §3 relocation, Deptrac `Calendar` layer, one-engine AC | any duplicate tithi function ⇒ build fails |
| R-02 | Fixing P-01/P-02 silently moves users' stored reminders | §13.7 migration ADR, per-row `rules_version`, user-visible explanation | deploy blocked without the migration plan |
| R-03 | Accuracy is asserted, not measured (P-03/P-08 again) | external gold only; provenance test; measured gates committed as artifacts | a gate with a self-generated fixture is a rejected PR |
| R-04 | Festival rule error becomes a religious harm, not a bug | G-FEST-1 review, alternatives displayed, override audit, never a guarantee claim | any unreviewed rule cannot publish an occurrence |
| R-05 | Wrong dates for a *published* profile | profile names versions; `NOT_VALIDATED` refuses serving; `HORIZON_UNSUPPORTED` | a failing `calendar:verify` blocks activation of the generation |
| R-06 | Boundary work explodes rows/disk | field-partitioned tables, profile budgets, retention, G-CAP-0 | > budget ⇒ shrink places/years before raising budget |
| R-07 | Request path starts computing years | range budgets, precompute-only reads, `RANGE_TOO_LARGE` | any year-scope compute on a request path is a blocker |
| R-08 | Muhurat pages become superstition marketing | §11.6 wording, rule source/version on screen, no outcome claims | a claims-review failure blocks the family |
| R-09 | SEO engine regurgitates `P-13` at 100× scale | eligibility + cardinality + similarity gates, CI sitemap-size assertion, `noindex` default | a family exceeding its budget cannot activate |
| R-10 | `dateModified`/schema untruth repeats | version-table provenance + validator test | a JSON-LD mismatch fails the build |
| R-11 | Profile fragmentation confuses users into picking "the answer they want" | one documented default, comparison pages, plain explanation of divergence | a non-default profile cannot be canonicalised into a separate indexable URL |
| R-12 | Timezone/coordinate drift over years (political changes) | IANA pinned + versioned, `places_version` in cache keys, annual re-verification | unversioned cache key anywhere is a blocker |
| R-13 | Rule storage becomes an injection surface | schema-validated grammar, total interpreter, node/time budgets, no `eval` | any dynamic execution in rule data is a blocker |

**Verdict.** Promising, technically substantial, achievable in Laravel + Next.js. The astronomy and
festival-rule validation are the highest-risk workstreams, and the honest current state is worse than
"not yet established": §1.3 shows the shipped engine *computes* a nakshatra two mansions off — and a
lunar month wrong for ~81 % of the year, which **does** reach a user decision today through
masa-matched śrāddha anchors (P-02), inside a test suite structurally incapable of noticing either.
That is still a better starting point than it looks, because both errors trace to one missing
constant with a one-line-each invariant test, and because tithi — the part everything visibly depends
on — turns out
to be sound.

**Sequencing claim this document is making.** Do not build the festival engine, the muhurat engine,
or a single public Panchang page before **E-SC-01**. Two missing routes plus one ayanamsa constant
plus real external gold is the difference between a calendar platform and a generator of confidently
wrong religious dates.

---

## 21. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0034 | Calendar kernel ownership (`app/Domain/Calendar`), `Customers` retirement | the §3 decision, the one-engine rule, Deptrac layer |
| ADR-0035 | Sidereal reference frame + ayanamsa default + profile model | the P-01/P-02 correction, alternatives preserved not merged, §13.7 stored-anchor migration |
| ADR-0036 | Validation against external authorities | gold provenance rule, tolerance table, the IMD central-station-vs-local comparison caveat, model ladder |
| ADR-0037 | Muhurat & festival rules as declarative reviewed data | grammar, interpreter bounds, no executable rule storage, review workflow |
| ADR-0038 | Calendar publishing eligibility & indexability | page families, cardinality budget, similarity gate, truthful JSON-LD, P-13 remediation |
| ADR-0039 | Generations & atomic activation | build→validate→activate→retire, replay equivalence, stale-generation refusal |
| ADR-0040 | Ritual-identity attribute data classification (P-17) | one declared classification for `gotra`/`nakshatra`/`rashi`/`kuladevata`/`shraddha` + family-member rows, reconciling the `WarehouseExportService` special-category guard with the API and storage paths; retention class, erasure coverage, canonical-enum validation, and the deceased-person consent question |

Also required on merge: `SeoContent/module.json` permissions·events·scopes (currently empty, P-15),
`Customers/module.json` delta for the moved namespace, Deptrac `Calendar` layer, OpenAPI fragments
for §14, named rate limiters, and a section in `docs/16-analytics/analytics.md` for §12.

---

## 22. Verification (this document)

1. Every `P-` row cites a `file:line` that still resolves, and every measured number is reproducible
   from appendix A.
2. Every epic in §16 maps to at least one gate in §17; no gate is satisfied by a self-generated fixture.
3. Every AC written into `docs/specs/tasks/` conforms to methodology §3 — observable, decidable,
   CI-testable, independently falsifiable; "appropriate", "reasonable", "user-friendly" do not appear.
4. Every task touching tenant data carries a tenancy AC; every task touching authorization or
   privilege carries a negative AC.
5. Every referenced event/permission/scope exists in a manifest, or is added in the same task.
6. No section cites accuracy, coverage or validation that §13 has not produced. **A number in this
   document that came from a model rather than a measurement is a defect.**
7. Doc drift is a review blocker; a behaviour change without a change here fails DoD item 7.
8. No astronomical claim appears in §4–§7 without its profile, its supported range and its measured
   error.

---

## Appendix A — Reproduction of the §1.2 measurements

Harness (throwaway; the permanent version is `tests/Unit/Calendar/AyanamsaParityTest.php`, T-SC01-02).
Run from `platform-navdhya/` with the composer autoloader.

```php
require __DIR__.'/../vendor/autoload.php';
use App\Modules\Customers\Domain\Panchang\{JulianDayCalculator, SolarLongitude, LunarLongitude,
    NakshatraCalculator, LunarMonthCalculator, Elongation, SunriseUtcCalculator, PanchangCalculator};

$AYANAMSMA_2026 = 24.21;                                  // Lahiri, degrees
$jd   = JulianDayCalculator::fromUtc(2026,11,10,0,30,0);  // ≈ Delhi sunrise, UTC
$t    = JulianDayCalculator::centuriesSinceJ2000($jd);
$moon = LunarLongitude::calculate($t);                    // tropical
$sun  = SolarLongitude::calculate($t);                    // tropical

// A1  nakshatra: shipped vs sidereal  (P-01)
echo NakshatraCalculator::calculate($moon)['name'], ' / ',
     NakshatraCalculator::calculate($moon - $AYANAMSMA_2026)['name'], PHP_EOL;

// A2  tithi is ayanamsa-invariant      (P-04)
printf('%.6f %.6f', Elongation::calculate($moon,$sun),
                     Elongation::calculate($moon-$AYANAMSMA_2026,$sun-$AYANAMSMA_2026));

// A3  masa: shipped vs sidereal        (P-02)
echo LunarMonthCalculator::calculate($sun,1)['amanta'], ' / ',
     LunarMonthCalculator::calculate($sun-$AYANAMSMA_2026,1)['amanta'], PHP_EOL;

// A4  polar fabrication                (P-05)
print_r(SunriseUtcCalculator::calculate(2026,6,21,78.2232,15.6267));

// A5  limb completeness                (P-07)
print_r(array_keys(get_object_vars(PanchangCalculator::calculate(2026,11,10))));
```

Observed output on this tree, 2026-11-10:

```text
=== 1. NAKSHATRA: tropical input vs sidereal input ===
2026-11-10  moonLon=235.848  SHIPPED=JYESHTHA            sidereal=VISHAKHA            deltaIdx=2
2026-01-01  moonLon=67.041   SHIPPED=ARDRA               sidereal=ROHINI              deltaIdx=2
2027-05-15  moonLon=167.504  SHIPPED=HASTA               sidereal=PURVA_PHALGUNI      deltaIdx=2

=== 2. TITHI is ayanamsa-invariant (elongation), so it is NOT affected ===
elongation tropical=8.2268  sidereal=8.2268  diff=0.000000

=== 3. MASA: solar rashi index from tropical vs sidereal sun ===
2026-11-10 sunLon=227.621  SHIPPED masa=KARTIKA (rashi  8)  sidereal masa=ASHVINA (rashi  7)
2026-04-20 sunLon= 29.959  SHIPPED masa=CHAITRA (rashi  1)  sidereal masa=CHAITRA (rashi  1)

=== 4. POLAR / NO-SUNRISE fabrication (Svalbard 78.2232N 15.6267E) ===
21 Jun 2026 (midnight sun, NO sunrise exists): {"hour":10,"minute":59,"second":6,"fractional_hour":10.985071856779593}
21 Dec 2026 (polar night,  NO sunrise exists): {"hour":10,"minute":55,"second":20,"fractional_hour":10.922240772381791}

=== 5. Civil date pasted onto a UTC instant (no timezone in the path) ===
New York 2026-03-21 sunrise UTC parts = 10:58:12 -> labelled 2026-03-21T10:58:12Z
Delhi    2026-03-21 sunrise UTC parts = 00:54:46 -> labelled 2026-03-21T00:54:46Z

=== 6. Shipped PanchangOccurrence fields ===
fields: date, sunriseUtc, tithi, nakshatra, masa, tradition, confidence
tithi="PRATIPADA" nakshatra="JYESHTHA" masa="KARTIKA" sunriseUtc="2026-11-10T01:07:00Z"
yoga present? NO   karana present? NO   vara present? NO
```

Reading note for A4: both polar results are ordinary clock times with no basis in the sky — the
engine invents a sunrise and everything downstream treats it as an observation.

Reading note for A3: the second row is *not* evidence of correctness. The two derivations agree only
where `sunLon mod 30 ≥ ayanamsa`, ≈ 19 % of the solar year; the first row is the other 81 %.
