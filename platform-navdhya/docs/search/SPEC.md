# Search & Discovery — Module Specification v3

**Module:** `app/Modules/Search` (+ `app/Modules/Recommendations`, new)
**Status:** Draft — for review. No task below is `Approved` until §17 gates are staffed.
**Slices:** S65 → S84 (continues the register in ADR-0024 … ADR-0027; S59–S64 are shipped)
**Extends:** ADR-0024 (search core v2), ADR-0025 (API planes), ADR-0026 (shared package), ADR-0027 (realtime freshness)
**Supersedes:** nothing. It *retires* claims made in ADR-0024 §5 that are not true in the shipped code (§1.2 D-01, D-06).
**Owner:** Platform Search
**Methodology:** every task in §16 follows [`docs/specs/README.md`](../specs/README.md) §2 template, §3 AC shapes, §4 Definition of Done.

---

## 0. How to read this document

Identifier conventions are the house conventions, not inventions of this doc:

```text
Epic      E-SR-<nn>          e.g. E-SR-02
Task      T-SR<epic>-<nn>    e.g. T-SR02-03
Subtask   T-SR<epic>-<nn>.<s> e.g. T-SR02-03.2
AC        AC-T-SR02-03-<n>
Defect    D-<nn>             evidence-verified defect in shipped code (§1.2)
Event     <domain>.<object>.<action>   e.g. search.result.impressed
```

Normative words: **MUST** = blocker, **SHOULD** = justified deviation allowed in the PR, **MAY** = optional.

Everything in §1 is a fact checked against this working tree on 2026-11-10, with `file:line`.
Everything after §2 is a proposal. If a proposal contradicts a §1 fact, §1 wins.

---

## 1. Evidence baseline

### 1.1 What actually ships today

| Layer | Shipped | Verified at |
| --- | --- | --- |
| Devanagari→Latin fold | codepoint map, ~70 entries, single scheme | `Support/TransliterationMap.php:19-78` |
| Tokenizer | bigrams for Devanagari, fold, ASCII strip, stopwords, ≤3 synonyms | `Services/SearchNormalizer.php:34-81` |
| Recall | MySQL FULLTEXT `ngram` boolean mode / SQLite `LIKE` fallback | `Http/Controllers/SearchController.php:73-94` |
| Ranking | BM25F + exact/prefix/popularity/freshness/locale/proximity | `Services/SearchScorer.php:22-28, 203-279` |
| Suggestions | `search_terms` prefix `LIKE` + Levenshtein fallback | `SearchController.php:184-239` |
| Index write | synchronous observers → `upsertDocument` → term rebuild → `bumpVersion` | `Observers/*.php`, `SearchIndexer.php:45-54` |
| Projection | 4 providers: `temple`, `pandit`, `puja_service`, `seo_page` | `Contracts/*SearchProvider.php` |
| Analytics | `search_queries` rows via `POST /api/v1/search/analytics` | `SearchController.php:370-409` |
| Trace | `X-Search-Trace` header, `trace_id` + `search_version` columns | `SearchController.php:439-449` |
| API planes | public (no `auth.any`/`tenant`) + privileged (capability) | `Routes/api.php`, ADR-0025 |
| Web autocomplete | 150 ms debounce, `AbortController` + seq guard, IME guard, ARIA combobox, recent/trending chips | `apps/web/components/search/search-box.tsx` |
| Web federation | RRF (k=60) over in-process BM25 + Laravel leg, version-keyed cache | `apps/web/lib/search/federated.ts:304-433` |
| TS text layer | hand-mirrored fold map + tokenizer + BM25 | `packages/search/src/{transliteration,tokenizer,bm25}.ts` |

This is a real engine, not a stub. 29 PHPUnit tests plus `search-box` (8) and `bm25`/`params` suites are green.

### 1.2 Defect register — the reason this spec exists

Each row was verified in this tree. These are the requirements. Design that ignores a `D-` row is rejected at review.

**D-01 — The popularity signal is dead code.**
`search_documents.bookings_30d`, `views_7d`, `popularity_updated_at` are read at
`SearchScorer.php:285-286` and `SearchIndexer.php:193`, and **no production writer exists**.
A repo-wide grep (excluding `vendor`, `node_modules`, `.next`, phpstan cache) returns only the
migration, the model, the two read sites, and test fixtures. Consequence: `popularityRaw()` is `0`
for every document → `popularityNorm()` hits the `$max - $min < 1e-9` guard at
`SearchScorer.php:296-299` and returns `0.0` for every document. **`W_POP = 0.45` — the single
largest weight after BM25 — multiplies a constant zero, and term `weight` at
`SearchIndexer.php:193` is therefore always `1.0`.** ADR-0024 §5 documents "popularity
(log-normalised bookings/views)" as shipped ranking. It is not shipped. Ranking is BM25 +
prefix + freshness + locale.

**D-02 — Freshness measures reindex recency, not content recency.**
Every provider hardcodes `'indexed_at' => now()` inside `buildDocument()`. `SearchScorer.php:241-250`
then computes `fresh = exp(-ageDays/180)` from it. A full `search:reindex` therefore resets the
freshness of the entire corpus to `1.0`, and an unchanged-but-old temple decays purely because it
was not rewritten. Freshness must key the **source entity's** `updated_at`.

**D-03 — Pandit locality is structurally absent.**
`PanditSearchProvider::buildDocument()` sets `'city' => null` unconditionally. `PanditProfile` has
no `city`, `state`, `locality`, or `district` column (fillable set is
`… travel_buffer_minutes, travel_radius_km, timezone`); the only geo-adjacent facts are
`travel_radius_km` (default 25) and `timezone` (default `Asia/Kolkata`). So `GET /search?city=`
can *never* return a pandit, `facets.city` never contains a pandit city, and `search_terms.city`
is null for every pandit token. The brief ("multi-entity search across Pujas, Pandits, and Temples
with locality filtering") is unimplementable until a serviceability source exists. This is a data
modelling prerequisite, not a search algorithm task.

**D-04 — Puja locality is free text.**
`PujaServiceSearchProvider` takes `city` from `$attributes['city']`, an unvalidated JSON string on
`CatalogItem`. `search_documents.city` is `varchar(100)` indexed as a plain string. There is no
normalisation, so `Varanasi`, `varanasi`, `वाराणसी`, `Benares`, `Varanasi ` are five distinct facet
values. `packages/geography::canonicalRegionId()` exists and has **zero consumers** (grep for
`@navdhya/geography` matches only its own `src/ports/providers.ts`).

**D-05 — IAST and every romanisation except the house fold is destroyed.**
`SearchNormalizer.php:57` applies `preg_replace('/[^a-z0-9]+/u', ' ', $folded)`. A query
`rudrābhiṣeka` (IAST) is not Devanagari, so `TransliterationMap::fold()` passes it through
unchanged; the `ā`/`ṣ` then become **word separators**, producing tokens `rudr`, `bhi`, `eka`.
Harvard-Kyoto (`rudrAbhishEk`), WX, SLP1 and ITRANS fare no better. The user-facing consequence:
any user who types the scholarly romanisation of a Sanskrit ritual name gets zero results and a
`did_you_mean` built from mangled fragments. The stated requirement "phonetic search, e.g.
`Rudrabhishek` vs `रुद्राभिषेक`" works **only** for the one accidental case where house fold and
user spelling coincide.

**D-06 — Fuzzy is an unbounded scan, twice.**
`SearchController.php:197-211`: on a prefix miss, load **200 term rows**, then PHP `levenshtein()`
each in a `filter()`. `SearchController.php:544-579` (`did_you_mean`): `distinct()` pluck of **500
normalized terms**, then `O(query_tokens × 500)` `levenshtein()` calls, executed on every
zero-result search. There is no delete-neighborhood index, no candidate bound tied to distance,
and no cache. This is exactly the "fuzzy must not scan every stored term" rule, violated on the
two hottest failure paths (typo + no results).

**D-07 — The scorer rebuilds the whole inverted index on every request.**
`SearchController.php:101-103` constructs `new SearchScorer($candidates)` per query. `build()`
(`SearchScorer.php:153-200`) re-tokenizes title, subtitle and up to 3 000 chars of body for each of
up to 500 candidates, rebuilds the posting map, then re-walks it `O(candidates)` per score
(`:84-86`). `SearchScorer::suggest()` (`:124-134`) iterates the **entire posting map** with
`str_starts_with()` — a linear scan of every distinct term in the corpus, in PHP, per keystroke,
where a B-tree range would do. This, not algorithm choice, is the latency ceiling.

**D-08 — `bumpVersion()` fires once per document.**
`SearchIndexer.php:45-54` — every `upsertDocument()` runs `bumpVersion()` (`:88-100`, a SELECT +
an UPDATE/INSERT). A 5 000-row catalog import performs 5 000 version bumps and advances the global
`search_version` 5 000 times, invalidating every BFF corpus cache mid-import (ADR-0027 made
version the invalidation key). Versioning must be per **generation**, not per row.

**D-09 — Index writing is synchronous inside the business transaction, across connections.**
`Temple`/`PanditProfile`/`CatalogItem` live on `tenant_shared`; `search_documents`/`search_terms`
on `central`. `rebuildTermsForDocument()` (`SearchIndexer.php:180-216`) does `DELETE all terms` +
bulk `INSERT` on `central` inside the observer's `saved()` — no transaction spans the two
connections, so a failed term write leaves the source row committed with a stale or empty posting
set, and there is no outbox to repair it. There is also no `SKIP LOCKED` worker, no `--fresh`
rebuild ledger, and `search:reindex` is **not in any scheduler** (grep over `routes/` +
`app/Console` returns nothing).

**D-10 — Reindex orphan detection loads the whole type into PHP.**
`SearchIndexer.php:153-157` plucks every `entity_id` for the entity type into an array and
`array_diff`s in userland. On `puja_service` this is O(rows) memory per command run.

**D-11 — The web analytics loop is broken; the data-collection surface is 404.**
`apps/web/components/search/search-box.tsx:58` POSTs `/api/search/analytics` and `:94`
GETs `/api/search/trending`. `apps/web/app/api/search/` contains **only** `route.ts`,
`suggest/route.ts`, `revalidate/route.ts`. Both fetches are wrapped in swallowed catches, so they
fail silently forever. Consequence: the primary public surface writes **no** `search_queries` rows
server-side, `GET /api/v1/search/trending` returns an empty set, and the "trending" chips the
header renders are fed only by client-side `localStorage`. Every statement in ADR-0024/0025 about
the "zero-result feed" and "trending" is currently unfalsifiable because no data arrives.
This single gap is the highest-leverage item in the whole spec: no instrumentation exists to
justify any ranking or recommendation decision until it is closed.

**D-12 — SQLite recall path injects raw user input into a `LIKE` pattern.**
`SearchController.php:88-90` builds `"%{$term}%"` from unsanitised tokens — `%` and `_` are not
escaped (contrast `:184` and `:216`, which do call `addcslashes`). Not SQL injection (values are
bound), but a user typing `a%` or `_%` changes the match semantics and forces a leading-wildcard
scan of `longText body`, which is unindexed. `:184` also assigns `$escaped` and never uses it
before reassignment at `:216` (dead, flagged by the same pattern being absent on the hot path).

**D-13 — The PHP and TS text layers are hand-copied twins with no parity gate.**
`packages/search/src/transliteration.ts:1-3` literally says "Mirrors
`platform-navdhya/app/Modules/Search/Support/TransliterationMap.php`". Two files, no shared source,
no test asserting equality; the TS copy also carries a dead `const len = value.length` at `:27`
with an in-file comment admitting it is the wrong unit. The fold is codepoint-ordered in PHP
(`TransliterationMap.php:49-50` sorts keys by length) and differently sorted in TS. Any schema
upgrade in §4 MUST remove this fork rather than deepen it.

**D-14 — `recommendations`, `personalization`, `discovery` packages are unreferenced.**
Each is ~50 lines of ports plus a pass-through use case; zero app consumers. `packages/analytics`
is the opposite: ~90 files, real event registry with codegen, GA4 + measurement-protocol
adapters, batcher/outbox/retry, consent/retention/erasure, SRM checks, BigQuery reconciliation.
Its registry already registers `search` and `zero_results_search`
(`packages/analytics/src/registry/events/index.ts:46-78`) with the note *"Authoritative search log
stays in Laravel until B4 fold-in"*. **Conclusion: §10 rides `packages/analytics`; it does not
build a second pipeline.**

### 1.3 Assumptions that must be proved, not inherited

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | `central` can absorb a term/gram/delete index ~3–6× `search_documents` | disk + write-IOPS projection at target corpus | G-BENCH-0 |
| A-2 | MySQL `ngram` FULLTEXT token size is set to 2 and `ft_boolean_syntax` is understood | `SHOW VARIABLES LIKE 'ngram_token_size'` at deploy | G-BENCH-0 |
| A-3 | Booking facts exist that can compute 30-day bookings per entity | `Booking` module ownership check | G-DATA-0 |
| A-4 | Ext-intl is installed in the production PHP image | `php -m` in CI matrix | G-ENV-0 |
| A-5 | Search traffic distribution is ~prefix-dominant | measured from `search_queries` after E-SR-01 | G-META-1 |

A-5 is deliberately unanswerable today because of D-11. **E-SR-01 exists to answer it.**

---

## 2. Scope

### 2.1 In scope

1. Script-correct Sanskrit/Devanagari transliteration with a versioned multi-scheme key model (§4).
2. Locality as a first-class, canonical, filterable dimension across Puja / Pandit / Temple (§5).
3. Durable prefix, n-gram and delete-neighborhood index structures replacing the per-request rebuild (§6).
4. Cost-aware query planner with candidate budgets and a measurable execution trace (§7).
5. Live, calibrated, explainable ranking with working popularity and freshness (§8).
6. Outbox-driven incremental indexing with generation versioning and replay (§9).
7. Instrumentation to the floor of the product: impressions, positions, dwell, selection, reformulation, suppression, per-stage timings (§10).
8. Recommendation domain: Bayesian popularity, item-to-item CF, session KNN, Markov transitions, interest profiles, RRF fusion, MMR, reason codes, user controls (§11).
9. The two missing Next.js routes and the contract tests that stop that class of breakage (§13).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner if ever |
| --- | --- | --- |
| Elasticsearch / OpenSearch / Meilisearch / Typesense / any search service | platform boundary; §2.3 | new ADR required |
| Redis, Kafka, RabbitMQ, any queue broker | DB outbox + `tenant_shared` queue driver suffice | ADR-0023 lineage |
| Vector embeddings, ANN indexes, LLM rerank | no model host; §1.2 of the source brief's "semantic-ready" caveat | `docs/17-ai` |
| PHP ART, HAT-Trie, Double-Array Trie, FST, suffix automaton | research-only; §6.6 forbids production until G-BENCH-3 | E-SR-09 (bench only) |
| Neural session recommenders, ALS/matrix factorization | numerical cost in PHP unproven (A-1) | Wave 3, gate-gated |
| Cross-tenant federated search | tenancy invariant, `AssertTenantIsolation` | never |
| Replacing `packages/analytics` | D-14 | never |

### 2.3 Boundary preserved

No new language, no new service, no new infrastructure. Laravel DB is the durable source of truth;
a process-local structure is an **optimisation with a declared byte budget** (§6.5), never the
canonical index, because 8 PHP-FPM workers each holding a 300 MB tree is 2.4 GB of duplicated
memory with no shared invalidation.

---

## 3. Target architecture

```text
apps/web  search-box.tsx  (ARIA combobox, debounce, AbortController, IME, IntersectionObserver)
apps/customer|partner  proxies
        │
        ▼  L  A  P  I     plane split preserved (ADR-0025)
apps/web/app/api/search/{route,suggest,analytics,trending,revalidate}   ← D-11 closes here
        │
        ▼
platform-navdhya  /api/v1/search/*      /api/v1/discovery/*
├─ Http: SearchController, PrivilegedSearchController, SuggestController, InteractionController, DiscoveryPreferencesController
├─ Planning: QueryPlanner → ExecutionPlan (budgets, stages, trace)
├─ Text:   ScriptClassifier · Normalizer(versioned) · AksharaSegmenter · SchemaRegistry · KeyFolder
├─ Retrieval: PrefixRange · TermDictionary · NGramRetriever · DeleteNeighborhoodRetriever · PostingIntersection
├─ Ranking: Ranker(v3 weights) · PopularityProjector · DiversityReranker · TopKHeap
├─ Indexing: DocumentIndexer · IndexOutbox · IndexWorker · GenerationManager · SnapshotWriter
├─ Analytics: EventCollector · ImpressionLedger · PatternAggregator · FunnelMaterializer
└─ Recommendations (E-SR-07…09): CandidateSource×6 · CandidateFusion(RRF) · InterestProfile · MmrReranker · ReasonCodes
        │
        ▼
DB: central (search_*, reco_*, analytics)   tenant_shared (sources: temple/pandit/catalog/booking)
        ▲
        └── Observers → outbox row IN THE SOURCE TRANSACTION (fixes D-09) → artisan search:index-work
```

**One engine, two interfaces.** Embedded `app(SearchEngine::class)->suggest()` and the HTTP plane
MUST share normalizer, planner, retrievers, ranker and serializer. A second implementation is a
review blocker (this is the source brief's rule and it is already violated by D-13).

Module layout additions, following the existing `app/Modules/<Name>/` shape:

```text
app/Modules/Search/
  Contracts/      ProvidesSearchDocuments (existing) + RetrievalStage, RankableCandidate, SearchEventListener
  Text/           ScriptClassifier.php  AksharaSegmenter.php  TransliterationSchemes.php  KeyFolder.php
  Planning/       QueryPlanner.php  QueryFeatures.php  ExecutionPlan.php  CandidateBudget.php  ExecutionTrace.php
  Retrieval/      PrefixRangeRetriever.php  TermDictionary.php  NGramRetriever.php  DeleteNeighborhoodRetriever.php  PostingIntersection.php
  Ranking/        Ranker.php  ScoreComponents.php  PopularityProjector.php  TopKHeap.php  DiversityReranker.php
  Indexing/       IndexOutbox.php  IndexWorker.php  GenerationManager.php  DictionaryBuilder.php  SnapshotWriter.php
  Analytics/      EventNormalizer.php  ImpressionLedger.php  PatternAggregator.php  FraudGuard.php  FunnelMaterializer.php
  Services/       SearchEngine.php  SuggestEngine.php  SearchExplainer.php
app/Modules/Recommendations/          (new; same scaffold shape, module.json, routes, tests/)
```

Deptrac (`platform-navdhya/deptrac.yaml`) must be updated in the same task that adds a layer, or
the architecture gate fails per §4 DoD item 6.

---

## 4. Sanskrit & Devanagari transliteration engine

### 4.1 Model: one document, many keys — never one folded string

The shipped design folds everything to ASCII at `SearchNormalizer.php:55-57`, which is why D-05
exists. The replacement is a **key set**, generated at index time and at query time from the same
versioned function. A searchable value produces:

| Key | Script | Purpose | Display? |
| --- | --- | --- | --- |
| `display` | original | what the user reads | **yes — always** |
| `key_dev` | Devanagari, NFC | akshara + bigram retrieval | no |
| `key_lat` | Latin, case-folded, diacritics retained | IAST-preserving match | no |
| `key_fold` | Latin, diacritics stripped | the current house behaviour, kept as ONE key | no |
| `key_phon` | canonical phoneme-ish form per §4.3 | scheme-agnostic matching | no |
| `key_tr` | transliteration-equivalence root (scheme-normalised) | ties all romanisations together | no |

Nothing is destructively ASCII-folded any more; `key_fold` is one *member* of the set, so the
current passing tests keep passing while IAST/HK/SLP1 stop being destroyed.

### 4.2 Scheme registry

`Text/TransliterationSchemes.php` is **data, versioned, and the single source of truth** — the TS
side consumes a generated artifact, which is how D-13 is retired.

```php
// Schemes: devanagari (grapheme->code), iast, harvard_kyoto, wx, slp1, itrans, iapp, hk_fold, custom_navdhya
// Bidirectional where the relation is invertible; asymmetric where it is not.
```

Properties that MUST hold, each with a test:

- `scheme(sanskrit) → each target` is total over the registry (no silent passthrough of unmapped
  characters; unmapped → recorded in `search_unmapped_codepoints`, §10.4, not dropped).
- `dev → iast → dev` round-trips for 100% of the corpus golden set (lossless schemes only).
- Asymmetric folds are explicit: `श/ष/स → sh/sh/s`, `व → v|w`, `ड/ढ → ḍ/ḍh` are **declared
  equivalence classes in `key_tr`**, not accidental collisions in a lossy map. This is the fix for
  `Rudrabhishek` vs `रुद्राभिषेक` vs `rudrābhiṣeka` vs `rudrAbhishEk` all being one intent.
- The map is append-only. Editing an existing entry requires a `normalizer_version` increment and
  a full reindex (§9.4) — same discipline the current file header already asks for at
  `TransliterationMap.php:11-12`.

### 4.3 Akshara segmentation and the schwa problem

The current fold is **codepoint**-based (`TransliterationMap.php:54-76`). Indic orthography is
**grapheme**-based. Required pipeline, using `Normalizer::NFC`, `grapheme_extract`/`IntlChar`, and
`IntlBreakIterator` where ext-intl is present (A-4), with a documented degraded path when absent:

```text
raw → UTF-8 validate → NFC → script classify → akshara (grapheme) segmentation
    → consonant + matra/combining analysis → explicit schwa handling
    → virama/conjunct (halant) resolution → per-scheme emission → key set
```

Schwa rule (the one ADR-0024 deferred): an inherent `/a/` is appended to a bare consonant unless
followed by a matra, a virama, or word-final position where the language deletes it. Both forms are
**indexed**, never chosen: `रुद्र` yields `rudra` **and** `rudr`, so the current mangled-token
failure of D-05 disappears while existing exact-fold matches are unaffected. Word-final behaviour
differs by language (Sanskrit/Hindi vs Bengali/Gujarati schwa deletion) — the key set holds both,
and `lang` metadata records which is canonical.

### 4.4 PHP ↔ TS parity (closes D-13)

1. `TransliterationSchemes` + `AksharaSegmenter` emit a machine-readable artifact at build time:
   `packages/search/gen/transliteration.v{N}.json` (checked in, generated by
   `php artisan search:export-schemas`).
2. `packages/search/src/transliteration.ts` becomes a **loader** for that artifact, not a copy.
3. A shared vector file `tests/Fixtures/Search/translit-vectors.json` (input, scheme, expected keys)
   is asserted by PHPUnit **and** Vitest. Different runtime, identical table.
4. `tests/architecture/search-boundaries.test.ts` gains: fail if
   `packages/search/src/transliteration.ts` contains a Devanagari codepoint literal.

### 4.5 Non-negotiable display rule

`search_documents.title` (original) is the only text rendered. Suggestions may show
`display (scheme_variant)` — e.g. `रुद्राभिषेक (rudrābhiṣeka)` — and MUST NOT render a folded key.
`SearchController::formatResult()` already returns `title`; §12 preserves it.

---

## 5. Locality as a first-class dimension

### 5.1 Problem restatement

D-03/D-04: one entity has city+state, one has free-text JSON, one has **nothing**. Filtering and
faceting cannot be trusted while `city` is a `varchar(100)` string. Locality is a data-modelling
deliverable owned jointly with `Temple`/`Pandit`/`Catalog`, and E-SR-02 exists to force the
cross-module contract through `ProvidesSearchDocuments` rather than by joining across connections
(prohibited by §4 DoD item 6).

### 5.2 Canonical region registry

New on `central`, populated from a curated dataset (separate migration + seeder, reviewable):

```text
search_regions
  id (ulid PK) · parent_id (self FK, nullable) · kind enum(city|metro|district|state|locality|pincode_zone|parking_zone)
  name_en · name_dev · name_alt_json · geohash6 · lat_milli · lng_milli
  population_tier tinyint · is_serviceable bool · normalizer_version tinyint
  UNIQUE (kind, name_en, parent_id) · INDEX (parent_id, kind) · INDEX (geohash6)
```

Aliases (`Benares`/`Banaras`/`वाराणसी`/`Varanasi Cantonment`) resolve to one `region_id`.
`packages/geography::canonicalRegionId()` becomes a *consumer* of this registry instead of a
dead port — that is the first real use of `@navdhya/geography` (D-04).

### 5.3 Search document locality (additive, backward-compatible)

```text
search_documents
  + region_id (ulid, nullable, INDEX)         # resolved canonical, may be exact or via parent
  + locality_json (text, nullable)            # [{region_id, kind, role}] role=primary|served|nearby
  + geo_lat_milli, geo_lng_milli (int nullable)
  # city remains, unchanged, for every existing test and facet consumer
```

Provider changes (each is a task in its own epic, because write-set spans modules):

| Entity | Source of locality | Blocking dependency |
| --- | --- | --- |
| `temple` | `city`,`state` → resolve to `region_id`; `address` → geocode once at onboarding, cached | none |
| `puja_service` | `attributes.city` → resolve, else inherit temple/tenant default service area; **validate at write time** | Catalog validation rule |
| `pandit` | **new source required** | see below |

### 5.4 The pandit serviceability source (D-03, P0 blocker)

Two candidate models, one decision needed at spec approval:

- **Option A — `pandit_service_areas`** (`tenant_shared`): `(tenant_id, pandit_id, region_id, is_primary, travel_km_override, service_state)`. Explicit, auditable, matches how `pandit_service_eligibility` already models SKU qualification.
- **Option B — centroid + radius**: `pandit_base_region_id` + existing `travel_radius_km`, resolved by geohash at query time. Cheaper to write, costlier to read, and unverifiable without coordinates.

**Recommendation: A, with B as a derived cache.** The `travel_radius_km` column already exists and
is meaningless to search without a base point; A gives locality filtering, B gives "near me".
`PanditSearchProvider::buildDocument()` must then populate `city`, `region_id` and `locality_json`
and a negative test asserts `?city=X` returns the pandit. Until E-SR-02 lands, §7.5 requires
pandit locality misses to be **counted, not silently filtered** — a null `city` must not read as
"no results for this city".

### 5.5 Locality ranking policy

Filter ≠ rank. `region_id` equality is a hard predicate (`tenant/region applied in retrieval,
never post-filter — ADR-0025 §security, and the brief's rule that global top-10-then-filter leaks);
then a locality-aware boost, capped:

```text
L = 1.0 exact region_id
  = 0.6 child→parent (same district)
  = 0.35 metro sibling
  = 0.15 state
  = 0.0  else, and excluded entirely when ?city= was supplied
```

---

## 6. Index structures

### 6.1 Durable dictionary (fixes D-07 on the suggestion path)

The current `search_terms` is a per-(term, document) posting with cached title/url/city, and no
frequency — so prefix suggest sorts a `varchar(120)` by a constant `weight` (D-01 makes weight
1.0). Split it:

```text
search_dictionary                       # one row per distinct key, per tenant+locale+scheme
  id · tenant_id · locale · scheme · key(varchar 160) · key_len
  document_frequency int · popularity float · click_count int
  first_document_id (ulid, nullable)    # denormalised for 1-query suggestion rendering
  generation bigint · normalizer_version tinyint
  UNIQUE (tenant_id, locale, scheme, key) · INDEX (key, document_frequency DESC) · INDEX (key(4), scheme)

search_postings
  term_key_id (ulid FK) · search_document_id (ulid FK) · field enum(title|subtitle|body|alias|tag|region|scheme)
  field_weight decimal(6,4) · tf decimal(8,4) · positions_json text
  UNIQUE (term_key_id, search_document_id, field) · INDEX (term_key_id)
```

Prefix retrieval becomes a B-tree **range**: `key >= :p AND key < :p_upper` with `LIKE` escaped and
ordered by `document_frequency DESC` — verified by `EXPLAIN` in the task's AC, not by hope
(the brief's warning about assuming `LIKE 'app%'` is index-backed is correct and collation-
dependent; §17 R-06 owns the collation decision).

### 6.2 N-grams, bounded

`search_grams (gram char(3), key_hash, scheme, tenant_id, locale, kind enum(bigram|trigram|akshara))`.
Field-scoped only — `title`, `subtitle`, `alias`, `region`, and Devanagari akshara. **Body n-grams
are prohibited** without a projection signed off in G-BENCH-0 (write amplification is D-08's sibling
risk). Existing Devanagari bigram generation at `SearchNormalizer.php:44-52` moves here unchanged in
semantics.

### 6.3 Delete neighborhood — replaces both scans (fixes D-06)

```text
search_deletes (delete_form varchar(160), original_key_hash, scheme, locale, tenant_id, edits tinyint)
  INDEX (delete_form, tenant_id)   INDEX (original_key_hash)
```

Generated **at index time** for `edits ≤ 1`, and for `edits = 2` only when `key_len ≥ 7` —
bounded by the rule that candidate work grows combinatorially. Then:

- suggest typo fallback = `SELECT … WHERE delete_form = :d` for each deletion of the query, then
  **verify** with banded Levenshtein. Candidate set is derived from an index lookup, not a scan.
- `did_you_mean` = same structure, capped at 40 candidate keys, cached per `(locale, tenant, key)`
  with a 6 h TTL. `SearchController.php:544-579` is deleted, not optimised.

### 6.4 Damerau and weighted distance

Transposition (adjacent-key) tolerance for Latin only. **Keyboard-neighbour rules MUST NOT be
applied to Devanagari** — the source brief's rule; phonetic proximity in Indic is a scheme/
equivalence-class matter (§4.2), not a QWERTY matter.

### 6.5 Process-local acceleration, with a declared byte budget

Per-request scorer construction (D-07) is replaced by a **bounded** immutable read model per
worker, keyed by `generation`:

```php
final class SearchCache {        // LRU, per worker, NOT canonical
    public const MAX_BYTES = 24 * 1024 * 1024;   // must be a config value, verified in CI
    // generation-keyed; evicted entirely when search_meta.generation advances
}
```

Rules: `MAX_BYTES` is config, asserted by a test; the cache is populated by the same retrievers
that read the DB (no forked logic); and correctness NEVER depends on a warm cache — the AC is
"cold worker returns identical results, only slower". If the byte budget cannot hold the hot
dictionary, the dictionary shrinks (frequency-capped); the DB stays authoritative.

### 6.6 Research structures: forbidden in production

ART, HAT-Trie, Double-Array Trie, FST/DAWG, suffix array/automaton, WAND/Block-Max/MaxScore are
**benchmark-only** under E-SR-09 and MUST NOT be registered in `ModuleServiceProvider` until a
report shows a measurable win over §6.1/§6.3 at the target corpus. `Retrieval/` stays empty of them
in the shipped tree; prototypes live in `platform-navdhya/tmp/bench/` (gitignored) or a draft PR.
No empty placeholder classes — the source brief is right that pretending is worse than absence.

---

## 7. Query planner & retrieval

### 7.1 Features (computed once, then traced — §10 field list)

`query_len_graphemes · tokens · scripts[] · last_token_complete · diacritics_present ·
scheme_candidates[] · region_present · entity_types[] · tenant_id · locale · surface ·
normalizer_version · generation`

### 7.2 Stages, each individually budgeted and timed

```text
validate → normalise (versioned) → segment → classify → plan
  → exact → prefix(range)  ──┐
  → alias/scheme-equivalence ├→ fuse+dedupe → tenant/region predicate (in retrieval)
  → inverted intersection    │   → rank → top-K → diversity → serialize
  → ngram (only if asked)    │
  → delete-neighborhood ─────┘   (only when the stages above are insufficient)
```

`ExecutionPlan` is declarative and serialisable; it is emitted into `search_query_events.plan_json`
(§10.3) so every later ranking argument is backed by data rather than by taste. This is the
mechanism that makes "collect as much as possible" useful instead of merely expensive.

### 7.3 Budgets (config, not constants in code)

| Knob | Default | Hard cap |
| --- | --- | --- |
| suggestions returned | 8 | 20 |
| search returned | 20 | 50 (`SearchController.php:36` keeps) |
| prefix candidates | 300 | 1 000 |
| fuzzy candidates | 120 | 300 |
| ngram candidates | 240 | 600 |
| max edit distance | 1 | 2 (only `key_len ≥ 7`) |
| min graphemes before fuzzy | 3 | — |
| stage wall-clock | 8 ms | 25 ms then degrade, don't grow |
| query length | 128 graphemes | reject over |

`fuzzyOnlyIfInsufficient` and `minFuzzyGraphemes: 3` from the brief's pseudocode are adopted;
the one-grapheme/two-grapheme restrictions come from measured Indic token length (A-5).

### 7.4 Degrade order when a budget trips

exact → prefix → scheme equivalence → inverted intersection → **stop**. N-gram and delete-neighborhood
are the first things dropped. A request MUST NOT exceed its plan; the trace records
`truncated=1`, which is itself a metric (§10.5 `budget_exhaustion_rate`).

### 7.5 Locality-miss policy (D-03 stopgap)

While `pandit` locality is unpopulated, a `?city=` query MUST count and emit
`search.locality_unindexed_entity_type` per (entity_type, city) rather than returning an empty
group. Silent absence is how a data gap becomes a permanent product bug.

---

## 8. Ranking v3

### 8.1 Contract changes

1. Weights move from `SearchScorer.php:22-28` class constants to `config/search.php`, versioned as
   `ranking_policy_version`, and are emitted per candidate in the trace.
2. **Every weight component with no live signal is set to `0.0` and flagged**, rather than
   silently multiplying zeros. That is the honest state of `W_POP` today (D-01). The AC for E-SR-03
   is: *no ranking weight in the default policy may reference a column whose writer is absent*
   (enforced by a test that cross-checks policy keys against registered projector outputs).
3. Deterministic tie-break retained: score → popularity → title → id (`SearchScorer.php:93-108`).
   Stability is a user-visible property; results must not reorder between keystrokes.

### 8.2 v3 score (illustrative, to be calibrated against §14 relevance data)

```text
S = 1.00·BM25F + 0.40·E + 0.30·P + 0.20·Sch + 0.20·Loc + 0.25·U + 0.15·F + 0.10·L + 0.10·Prov
      − 0.35·TypoDist − 0.20·Staleness
```

`Sch` = scheme/equivalence-class match strength (§4.2) — new, and the single largest expected
recall gain for Sanskrit queries. `Loc` = §5.5. `Prov` = provenance quality (verified temple,
certified pandit via `pandit_service_eligibility.is_qualified`, seeded SEO page) — capped so
business boosts cannot dominate, per the brief.

### 8.3 Popularity projector (fixes D-01, and the precondition for all of §11)

```text
SearchRanking\PopularityProjector  (scheduled, tenant_shard_safe, chunked)
  bookings_30d  ← Booking module aggregate over tenant_shared, per entity, 30-day window
  views_7d      ← search_result.impressed/clicked ledger (§10), 7-day, position-corrected
  popularity    ← Bayesian-smoothed, time-decayed, fraud-weighted (§11.4)
  popularity_updated_at, generation
```

Writes go through the outbox (§9), never directly from the projector into a ranking read path.
Without this, E-SR-07 (recommendations) would rank on a constant and could not beat baseline —
which is gate G-REC-1's failure condition (§17).

### 8.4 Freshness (fixes D-02)

`fresh` keys a new `search_documents.source_updated_at` populated by the providers from the entity;
`indexed_at` is kept for its real meaning (staleness/ops) and stops being a ranking input.

### 8.5 Diversity

One `entity_type` may not exceed `ceil(limit/2)` slots on the global surface, and a single entity
may not appear twice via multiple keys (dedupe on `(entity_type, entity_id)` **before** top-K).
This is the search-side twin of MMR (§11.6).

---

## 9. Indexing, outbox, generations, recovery

### 9.1 Outbox (fixes D-09)

```text
search_index_outbox
  id(ulid) · tenant_id · entity_type · entity_id · revision · operation enum(upsert|delete)
  source_updated_at · status enum(pending|claimed|applied|failed|dead) · attempts
  lease_expires_at · last_error · created_at · claimed_at · applied_at
  INDEX (status, created_at) · INDEX (entity_type, entity_id, revision) · UNIQUE (entity_type, entity_id, revision)
```

- The observer writes **only** the outbox row, into the **same connection and transaction** as the
  source mutation. No `central` write inside a business transaction. `upsertDocument()` survives
  unchanged for backfill/admin paths and gains an explicit `$withinTransaction: false` flag so its
  misuse is a type error, not a surprise.
- `search:index-work` claims via `SELECT … FOR UPDATE SKIP LOCKED` (driver-guarded, with a
  documented lease-recovery path for SQLite), applies **idempotently**, and skips any event whose
  `revision` is older than the document's current revision — the stale-event rule from the brief.
- Retry with capped exponential backoff → `dead` + alert. Replaying the same revision cannot
  duplicate a posting: enforced by the `UNIQUE (term_key_id, search_document_id, field)` and an
  explicit idempotency test (AC in E-SR-04).

### 9.2 Two consistency classes

| Class | Path | Visibility |
| --- | --- | --- |
| Core: title, alias, entity_type, locality | synchronous projection on `central` (single row) | next read |
| Heavy: dictionary, postings, grams, deletes | worker | bounded SLO, published as `search_index_freshness_seconds` |

The SLO is advertised, not assumed. "Realtime" is not a claim this module may make (source brief
§9.3; and D-11 means we cannot yet even measure it).

### 9.3 Generations (fixes D-08)

`search_meta.generation` advances **once per worker batch**, not per document. A `bump-per-batch`
counter with a coalescing window (≤5 s) is the whole change; `X-Search-Version`/ETag semantics
(ADR-0027) are preserved. AC: a 5 000-row import produces **≤ 10** generation advances.

### 9.4 Rebuild, verify, snapshot

```text
search:reindex {type?} {--fresh} {--chunk=}          (exists; gains generation coalescing + streaming orphan diff)
search:index-work                                     (new worker)
search:build-dictionary                               (dictionary/grams/deletes from postings)
search:verify            index vs source counts, orphan postings, unmapped codepoints, collation probe
search:snapshot / compact                              (local files, optional; checksummed, last-known-good generation)
search:replay            rebuild from outbox → proves identical logical index (recovery AC)
search:benchmark                                     (§14)
search:export-schemas                                 (§4.4 parity artifact)
```

Orphan detection moves from `array_diff` over a plucked array (D-10) to a chunked
`NOT EXISTS`/keyset diff. Normalizer and ranking-policy versions live in `search_meta`;
`search:verify` fails when a reader's `normalizer_version` differs from the index's — the mismatch
that otherwise silently drops matches.

---

## 10. Instrumentation & behavioural data collection

This is the load-bearing section. Every ranking weight, every recommendation source, every
"we should promote X" argument in §8/§11 is unfalsifiable without it — and today it is broken at
the transport layer (D-11). Ordered by dependency, not by ambition.

### 10.1 Transport: reuse `packages/analytics`, do not rebuild

| Concern | Decision |
| --- | --- |
| Client → server | existing `packages/analytics` client (batcher, outbox, retry, breadcrumbs) posting to the existing collector route `apps/web/app/api/ae/v1/batch/route.ts` |
| Registry | new events added to `packages/analytics/src/registry/events/` — codegen + lint enforce the shape, so an unregistered event cannot ship |
| Consent | existing consent module gates non-essential search events; `essential` = index health only |
| Retention / erasure | existing retention + erasure pipeline; §10.7 states the windows this module requires |
| Laravel side | `search_*` tables remain the **authoritative** log (per the registry's own note); the analytics package mirrors for BI/BigQuery reconciliation |
| Prohibited | a bespoke `fetch('/api/search/analytics')` written fresh in a component. D-11 is precisely this bug; the AC in E-SR-01 forbids repeating it |

### 10.2 Event taxonomy

```text
search.query.submitted        search.query.reformulated    search.query.abandoned
search.results.returned        search.suggest.shown        search.suggest.selected
search.result.impressed        search.result.clicked        search.result.dismissed
search.zero_result             search.filter.applied        search.filter.cleared
search.did_you_mean.shown      search.did_you_mean.accepted search.no_locality_match
search.locality_unindexed_entity_type                       search.budget.exhausted
search.scheme.matched          search.unmapped_codepoint     search.index.freshness_lag
search.event.queued            search.index.revision_applied search.trend.selected
reco.surface.rendered          reco.item.impressed          reco.item.clicked
reco.item.saved                reco.item.hidden             reco.item.disliked
reco.exploration.arm_selected  reco.preference.changed      reco.profile.reset
```

Every event carries the common envelope (§10.3) — the discipline that makes cross-event joins the
normal analytical move rather than an archaeology project.

### 10.3 `search_query_events` — the per-request spine

One row per retrieval execution (not per keystroke; §10.6 handles typing). Replaces nothing;
`search_queries` stays as a compatibility view for one release.

```text
search_query_events
  id (ulid) · occurred_at(6) · received_at
  --- identity & partitioning ---
  tenant_id (nullable, NOT NULL-checked in AC) · locale · surface enum(global_search|search_page|
      customer_book|partner_dash|dashboard_staff|api) · plane enum(public|privileged)
  session_id (opaque hash, rotating) · user_id_hash (nullable, salted) · anon_marker bool
  device_class enum(mobile|tablet|desktop|other) · connect_class (nullable, from navigator.connection)
  --- request ---
  query_raw_len_bytes · query_graphemes · query_normalized (nullable, gated, §10.7) · query_hash
  tokens_json · scripts_json · diacritics_present · scheme_candidates_json
  requested_types_json · applied_filters_json (type, region_id, price, lang, tradition, gotra, shakha)
  requested_limit · cursor_supplied
  --- execution (the meta-grade part; every stage self-reports) ---
  normalizer_version · ranking_policy_version · index_generation · revision_seen
  plan_json        {stages:[{name, budget_ms, used_ms, candidates, truncated}], degrade_reason}
  stage_recall_ms · stage_plan_ms · stage_prefix_ms · stage_ngram_ms · stage_delete_ms
  stage_intersect_ms · stage_verify_ms · stage_rank_ms · stage_diversity_ms · stage_serialize_ms
  candidates_prefix · candidates_delete · candidates_ngram · candidates_merged · after_predicate
  after_dedupe · returned_count · result_ids_json (ids+position+score, capped 20)
  best_score · score_spread · tiebreak_used bool
  matched_fields_json · matched_schemes_json · locality_level enum(exact|district|metro|state|none)
  did_you_mean_supplied · did_you_mean_key · empty_result bool · error_code (nullable)
  trace_id · request_id · total_ms
  timestamps
  INDEX (tenant_id, occurred_at) · INDEX (query_hash, occurred_at)
  INDEX (surface, occurred_at, empty_result) · INDEX (index_generation) · INDEX (trace_id)
```

Rationale for the unusual columns:

- `plan_json` + per-stage ms: makes the "is it the DB or the algorithm?" argument decidable.
  The source brief's insistence on measuring stages independently is the correct engineering
  instinct and this is where it lands.
- `revision_seen` + `index_generation`: proves freshness claims per request instead of globally.
- `matched_schemes_json`: **measures whether §4 actually works** — the fraction of Sanskrit
  queries landing via `iast`/`hk`/`slp1` is the direct KPI for transliteration ROI (R-01).
- `locality_level`: turns D-03/D-04 from a code observation into a monitored product gap.
- `result_ids_json` with positions: mandatory for §11 impression attribution and position-bias
  correction. Without it, no recommendation claim can be tested.
- `score_spread` / `tiebreak_used`: detects the "ten near-identical scores" state that precedes
  user-visible ranking thrash.

### 10.4 `search_impressions` + `search_selections` — behaviour, not intent-guessing

```text
search_impressions   (one row per result the user actually SAW)
  id · query_event_id FK · position · entity_type · entity_id · surface · shown_at_ms
  visible_ms · viewable_ratio_max · viewport_bucket · suggestion_group (nullable) · card_variant
  INDEX (query_event_id) · INDEX (tenant_id, entity_type, entity_id, shown_at_ms)

search_selections    (one row per action on an impression)
  id · impression_id FK · event enum(click|save|book_start|call|hide|dislike|share|copy_link)
  weight_class · occurred_at · downstream enum(none|detail|booking_started|booking_confirmed)
  INDEX (impression_id) · INDEX (event, occurred_at)
```

Rules with teeth:

1. **Rendered ≠ seen.** `search_impressions` is written from an `IntersectionObserver` at
   ≥50 % area for ≥300 ms (constants in one config object). An API response body is *never*
   evidence of exposure — the source brief's critical detail. AC: results in a closed dropdown
   produce zero impression rows.
2. **No click ≠ negative.** Absence of a selection is unknown, never a label (§11.5).
3. `search_selections.downstream` is joined to Booking facts, so "success" can mean a confirmed
   ritual, not a click. This is the guard against optimising clicks.
4. `search_locality_demand` is derived, not collected: `GROUP BY region_id, entity_type` where
   `empty_result=1` → a **supply gap map per city**, refreshed daily. This is the single most
   commercially valuable table in the spec, and it exists only because of rules 1 and 3.
5. `search_unmapped_codepoint` records any codepoint the registry could not map (§4.2) with a
   count and a sample hash — the dictionary tells us what to add next, instead of us guessing.

### 10.5 Pattern mining — the "patterns" ask, kept cheap and PHP-native

`search_query_patterns`, materialised daily by `search:aggregate-patterns` from the spine
(never by scanning raw events at request time):

| Pattern family | Definition | Drives |
| --- | --- | --- |
| Reformulation chains | same session, ≤90 s apart, monotonic edit distance recorded | typo vs intent-change detection |
| Query n-grams | 1–4 gram of normalised tokens, with `empty_result_rate` | dictionary, synonym and **puja-name** discovery |
| Script/scheme mix | per query: dev / latin / IAST-diacritics / mixed | §4.2 scheme prioritisation, R-01 |
| Type distribution | requested vs returned vs clicked per `entity_type` | facet defaults, §8.5 caps |
| Locality demand/supply | `region_id × entity_type` clicks, zero-results, pandit gaps | E-SR-02 sequencing, ops outreach |
| Typo neighborhoods | observed selected-key vs typed-key pairs, ≤2 edits | §6.3 edit-2 threshold tuning |
| Cold queries | queries with zero results ≥N times, ranked | manual content backlog (product-visible) |
| Fresh-prefix load | top-100 prefixes by volume, with candidate counts | §10.8 precompute list |
| Session journeys | search → detail → puja → booking sequences | §11 Markov / sequential patterns |
| Zero-result taxonomy | cause enum(no_token\|filtered_out\|budget\|region\|stale_index\|error) | distinguishes "no data" from "our bug" — required to trust the D-11 fix |
| Query latency histogram | per surface, per plan, P50/95/99 | §14 gates, regression alarm |

### 10.6 Typing behaviour (autocomplete telemetry) — bounded on purpose

`search_typed_sessions`: first-keystroke ts, final query, keystroke count, backspaces, max
graphemes, debounce-merged request count, `abort_rate` (cancelled / issued), time-to-first-
impression, selected suggestion index, IME-composition events, whether the user switched script
mid-query. **Per-keystroke rows are prohibited** — one row per settled query.
Volume guard: `config('search.telemetry.typing_sample_rate')`, default 0.25.

### 10.7 Volume, PII and abuse guardrails (mandatory, not advisory)

| Guard | Setting |
| --- | --- |
| `query_raw` storage | **not stored** by default. `query_hash` + `query_normalized` only; raw text requires an explicit config flag + audit trail |
| PII scrub | digits, `+91…`, emails, ULID-ish identifiers masked before hashing (fail-closed: unparseable ⇒ drop the row, don't log it) |
| Client rate limit | per-session event cap (200/min); server dedupe on `(session_id, event, entity_id, 60 s)` |
| Impression cap | ≤ 30 rows per query event |
| Retention | events 90 d raw → aggregates 24 mo; `user_id_hash` columns purged on the existing erasure path |
| Cardinality | any new dimension must be added to `search_dimension_registry` with a distinct-value estimate; a dimension exceeding its estimate stops being collected and alerts, rather than silently growing the table 10× |
| Anti-fraud (§11.7) | per-session caps, exposure validation (an impression without a matching result row is discarded), delayed popularity aggregation, reversible contributions, `suspect` flag consumed by `FraudGuard` |
| Debug | `debug=1` output remains `config('app.debug')`-gated (`SearchController.php:38`) and must NEVER be reachable on the privileged plane without `search:pii:view` |

A `search_telemetry_health` daily report publishes rows/minute, drop rate by cause, and the top
masked-PII trigger — so the pipeline is observable when it is over-collecting, not only when it
breaks.

### 10.8 What gets derived and served back to the product

`search_terms.kind = 'popular_query'` was designed for this in the existing schema (migration
comment: "Nullable document_id allows non-document suggestions — popular queries, categories,
cities — in S64") and has never been populated. E-SR-05 populates it from `search_query_patterns`
with the smoothed CTR of §11.4, capped per prefix, and the `trending` endpoint reads it instead of
`GROUP BY query_text` on a raw table (`SearchController.php:411-429`). High-frequency prefixes get
a bounded precomputed suggestion list; everything else stays on the B-tree range (§6.1).

---

## 11. Recommendation & discovery engine (`app/Modules/Recommendations`, new)

Release-gated on §10. **G-REC-0 (E-SR-01/06 done, impressions reliable) precedes every algorithm
in this section.** A recommender trained on broken exposure data is how D-01 becomes D-01 again.

### 11.1 Shape

Shared retrieval/ranking kernel with §6–§8; **separate candidate-generation interfaces and a
separate API surface** — query completion and item recommendation have different relevance
objectives and must be evaluated independently. `packages/{recommendations,personalization,discovery}`
are currently unreferenced (D-14); they become the TS contract layer for this epic rather than
being replaced, so `tests/architecture/search-boundaries.test.ts` still holds.

Candidate sources, each an implementation of `CandidateSource`:

| Source | Algorithm | Wave | Priority |
| --- | --- | --- | --- |
| S1 trending/popularity | Bayesian smoothed CTR, time-decayed, position-corrected | 1 | **P0** |
| S2 content | attribute/taxonomy overlap: puja category, `pandit_shakhas.veda`, `tradition`, `gotra`, `pandit_service_eligibility.service_sku`, temple `type`/`amenities` | 1 | **P0** |
| S3 user interest | §11.3 profile match | 1 | P0 (explicit prefs only) |
| S4 item-to-item CF | co-occurrence at session AND user level, separately weighted | 2 | P1 |
| S5 session | session KNN + 1st/2nd-order Markov transitions | 2 | P1 |
| S6 editorial/rules | curated, serviceability-checked | 2 | P1 |
| S7 graph | personalized PageRank over puja↔pandit↔temple↔region↔shakha | 3 | P2, batch-only |
| S8 exploration | Thompson sampling, 1 slot max | 3 | P3, gated |

### 11.2 Schema

```text
reco_impressions · reco_interactions     (mirror §10.4 fields, own surface enum)
reco_item_neighbors   (tenant_id, source_type, source_id, neighbor_type, neighbor_id,
                        sim_session, sim_user, sim_content, sim_final, n_evidence, generation)
                        UNIQUE (source, neighbor) · INDEX (tenant_id, source_type, source_id, sim_final)
reco_transitions      (source_type, source_id, target_type, target_id, count, p_estimate, window)
reco_user_profiles    (user_id, version, short_terms_json, long_terms_json, explicit_json,
                        updated_at, personalization_enabled)
reco_user_interests   (user_id, kind enum(category|region|tradition|shakha|puja|price_band|language),
                        value, weight, last_evidence_at, source enum(explicit|inferred))
reco_exclusions       (user_id, entity_type, entity_id|category, reason, scope, expires_at)
reco_bandit_stats     (arm_key, alpha, beta, pulls, updated_at)
reco_experiments      (key, variant, assignment_rule, started_at, stopped_at, srm_json)
reco_item_features    (entity_type, entity_id, features_json, content_hash, updated_at)
```

All writes flow through the same outbox discipline as §9.1: incremental counters, never
"recompute a million events per request".

### 11.3 Interest profiles — two stores, explicit beats inferred

Short-term (session, fast decay) and long-term (slow, category-specific decay) are **separate**;
a user who books one Ganesha puja does not get a permanent Ganesha profile, and a user who
suddenly researches a different ritual family is not held to last year's pattern.
Intent hierarchy is the default policy, per surface, configurable:

```text
CurrentIntent > RecentSession > LongTermPreference > GlobalPopularity
```

`source = explicit` rows (selected interests, excluded categories, chosen language)
**always outrank** inferred rows of equal magnitude, and are never overwritten by inference.

### 11.4 Popularity without the feedback loop

```text
ctr_posterior = (clicks + α) / (impressions + α + β)      α,β per (tenant, entity_type, category)
popularity    = ctr_posterior · time_decay(events) · position_correction(shown_positions)
                subject to min_exposure(impressions ≥ 20)
```

New items get a bounded freshness allowance; nothing below `min_exposure` may enter a trending
slot; per-user and per-category caps prevent the rich-get-richer tail the source brief flags as
the second-largest risk. Raw click counts are **never** a ranking input. This function is the
`PopularityProjector` of §8.3 — one implementation, used by search and reco alike.

### 11.5 Fusion, then diversity

Scores from different sources are not commensurable, so fusion is by **position**, not by summing
raw values: `RRF(x) = Σ 1/(k + rank_r(x))`, k=60 — the same constant already used in
`apps/web/lib/search/federated.ts`, which keeps the two engines legible to one reviewer. Then
calibrated feature-based fusion is compared against RRF as a baseline (§14.3). Final list re-ranked
with MMR (λ default 0.7) plus §8.5 type caps and `reco_exclusions`.

Complementary vs substitute is an explicit relation on `reco_item_neighbors`
(`relation_kind enum(complementary|substitute|same)`): a mandap/booking add-on is complementary to
a puja; two pandits for the same shakha are substitutes and must not fill five slots.

### 11.6 Explainability is a product feature, not a debug string

Every recommendation carries exactly one primary `reason_code`, selected from the component that
**actually contributed most** to its score, and it must be re-derivable from stored features.
`REASON_UNVERIFIABLE` is a server error, not a fallback label.

```text
RECENT_SEARCH · RECENT_BOOKING · SESSION_PATTERN · SIMILAR_PUJA · SAME_SHAKHA · SAME_TRADITION
· PANDIT_QUALIFIED_FOR_SKU · NEARBY_VERIFIED_TEMPLE · POPULAR_IN_CATEGORY · POPULAR_NEAR_YOU
· RECENTLY_UPDATED · SELECTED_INTEREST · EXPLORATION
```

### 11.7 Trust, privacy and control (mandatory surfaces)

`GET/PATCH /api/v1/me/discovery-preferences`, `GET /api/v1/me/interests`,
`DELETE /api/v1/me/search-history`, `POST /api/v1/me/recommendations/reset`,
plus per-card hide/dislike with declared scope (hiding one pandit ≠ hiding the category).
Anonymous and authenticated identity are separated; **logout terminates the authenticated
recommendation context** and applies the session-reset policy — a shared device must not carry the
previous person's history. Personalization defaults to off for new accounts unless the user opts in
during onboarding (cold-start: never fabricate a profile; use context, locale, explicit selection
and diversified popularity). Anti-manipulation: exposure validation, per-session caps, dedupe,
delayed aggregation, reversible contributions.

### 11.8 Wave 3 (gated, not scheduled before G-REC-3)

Personalized PageRank (bounded, batch), PrefixSpan sequence mining (length- and support-limited),
Thompson sampling (≥1 month of reliable impressions), IPS/SN counterfactual evaluation, adaptive
algorithm selection driven by measured stage latencies (§7.2), drift and fatigue monitors.
Implicit ALS/matrix factorization stays out until PHP numerics are benchmarked (A-1).

---

## 12. API contracts

Planes preserved exactly as ADR-0025 (public plane `withoutMiddleware(['auth.any','tenant'])`,
per-surface named limiters). Tenant/permission predicates move **into retrieval**, never applied
after top-K — already enforced for `entity()` at `SearchController.php:320-333`, whose comment is
the reason the rule is permanent.

| Method | Path | Change |
| --- | --- | --- |
| GET | `/api/v1/search` | + `region`, `scheme`, `lang`, `tradition`, `gotra`, `shakha`, `qualified_for_sku`; `city` retained; `explain=1` adds per-result component breakdown (privileged/debug only) |
| GET | `/api/v1/search/suggest` | + grouping by type **and** locality, `mode=prefix\|typo\|query`, `personalize` opt-in |
| POST | `/api/v1/search/events` | **new** — batched §10 envelope, replaces the unimplemented `analytics` shape; existing `POST analytics` retained one release, marked deprecated |
| GET | `/api/v1/search/trending` | reads `search_terms kind=popular_query` (§10.8) |
| GET | `/api/v1/search/health` | **new** — generation, freshness lag, worker lag, unmapped-codepoint count, dictionary size, budget-exhaustion rate |
| POST | `/api/v1/admin/search/{reindex,verify,replay,snapshot}` | privileged, capability-gated |
| POST | `/api/v1/discovery` | **new** — surfaces `home_pujas`, `continue_ritual`, `nearby_temples`, `related_sanskars` |
| POST | `/api/v1/discovery/{impressions,interactions}` | **new** |
| GET/PATCH | `/api/v1/me/discovery-preferences` | **new** |
| GET · DELETE · POST | `/api/v1/me/interests` · `/api/v1/me/search-history` · `/api/v1/me/recommendations/reset` | **new** |

Envelope stays `{data, meta}`; OpenAPI fragments and contract tests land with each endpoint
(DoD item 4). Error codes: `VALIDATION_ERROR`, `RATE_LIMITED`, `TENANT_UNRESOLVED`,
`INDEX_UNAVAILABLE` (degrade path), `PLAN_BUDGET_EXCEEDED`. Limits: ≤ 20 suggestions, ≤ 512 bytes
per label, ≤ 50 results, no internal score or behavioural feature leakage to public callers.

---

## 13. Next.js layer

The frontend owns interaction only — never normalisation authority, never a downloaded dictionary.

| Item | Detail |
| --- | --- |
| **Close D-11** | add `apps/web/app/api/search/{analytics,trending}/route.ts` matching the shapes `search-box.tsx:58,94` already expects, delegating to `@navdhya/analytics` / Laravel. A contract test proves neither fetch 404s. |
| **Stop the class of bug** | Playwright E2E: assert one `search_query_events` row and ≥1 `search_impressions` row appear for a scripted search. A silently-swallowed telemetry fetch must fail CI, not the product. |
| Impression visibility | `IntersectionObserver` on listbox options and reco cards, 50 %/300 ms, flush on close/`visibilitychange`/unmount |
| Autocomplete | keep 150 ms debounce, `AbortController` + seq guard, IME `isComposing` gate, ARIA combobox/listbox/option with `aria-activedescendant` (all already correct — do not rewrite) |
| Indic min-length | minimum query = **2 graphemes**, measured with `Intl.Segmenter`, not `String.length`. Note `SearchController.php:171` uses `strlen()` (bytes): one akshara is 3 bytes so it accidentally passes, but any `.length` guard in TS is a latent trap for combining sequences |
| Script hint | detect Devanagari vs Latin vs IAST in the input; send `scheme` candidates; render `title` untouched, never a folded key (§4.5) |
| Reason chips | `RecommendationReason.tsx` renders `reason_code.label` only |
| Controls | `PersonalizationSettings.tsx` bound to §11.7 endpoints, optimistic + revert on error |
| Empty states | distinguish *no results* from *filtered out* from *index unavailable*, using the §10.5 zero-result taxonomy |

---

## 14. Benchmark & evaluation methodology

No fabricated number may appear in this document or its reports. Every figure is a measured
artifact committed to `docs/23-evidence/`.

### 14.1 Corpora

`php artisan search:benchmark --corpus={smoke|prod|stress}` — seeded, fixed seed, reproducible:

| Corpus | Docs | Runs |
| --- | --- | --- |
| smoke | 50 000 | CI, every PR touching `Modules/Search` |
| prod | 1 000 000 | nightly; realistic puja/pandit/temple/SEO mix, 4 locales, deliberate typo + IAST variants |
| stress | 10 000 000 | pre-release only; requires A-1 disk projection |

Query workloads: exact prefix · typo (1 and 2 edits) · substring · multi-term · Devanagari · IAST ·
Harvard-Kyoto · SLP1 · mixed script · romanised pandit names · city+type combinations · hot
single-grapheme prefixes · long combining-mark queries · malformed UTF-8 · adversarial candidate
floods. The assumed 70/15/10/5 split is a **test scenario**, replaced by measured distribution at
G-META-1.

### 14.2 Latency measurement

Stages measured **independently** (§10.3): normalize, segment, plan, prefix, intersect, delete,
ngram, verify, rank, diversity, serialize, DB time, total. `hrtime(true)`,
`memory_get_usage(true)`/`_peak`, query plans via `EXPLAIN`, **cold and warm cache reported
separately**, and — non-negotiable — **measured under PHP-FPM with real OPcache and worker
limits**, not CLI only. CLI numbers are advisory; D-07 is exactly what CLI measurement hides.

### 14.3 Head-to-heads (report only; production is gated)

Prefix: DB B-tree range · sorted array · Radix Trie · (ART, HAT, DAT, FST as prototypes).
Fuzzy: delete-neighborhood · BK-tree · Levenshtein automaton · banded DP · q-gram prefilter.
Substring: bigram · trigram · inverted intersection · suffix array.
Ranking: current policy · policy v3 · + scheme keys · + locality · RRF · calibrated fusion.
Recommendation: Baseline (popularity+content) · A (+explicit interests) · B (+session model) ·
Collaborative (+item-to-item) · Advanced (+MMR, +exploration) — all five against the same
historical window.

### 14.4 Quality metrics

`Recall@10` · `MRR@10` · `NDCG@10` · `Precision@10` · intra-list diversity · catalog coverage ·
Novelty@10 · per-user ranking delta · hide/dismiss rate · time-to-successful-result · reformulation
rate · abandonment · zero-result rate by cause · **scheme-specific recall** (the §4 KPI) ·
locality-gap detection rate · P50/P95/P99 · QPS · bytes per indexed term · total index memory ·
build duration · insert/delete throughput · compaction · recovery-replay duration · freshness lag ·
`budget_exhaustion_rate`.

### 14.5 Evaluation protocol

**Temporal split only**: train/profile window → index build → future evaluation window. Random
splits leak future interactions and are rejected at review. Business outcomes are tracked
alongside accuracy — confirmed bookings for customer surfaces, successful document discovery for
SEO — with returns/cancellations and dismissal rate as guardrails. The target is more successful
search, not more clicks.

---

## 15. Data governance recap

| Property | Value |
| --- | --- |
| Source of truth | `central` MySQL for indexes + telemetry; `tenant_shared` for entities and facts |
| Cross-connection access | via providers/outbox contracts only — never a JOIN (architecture test) |
| PII | never indexed into `search_documents.body` from pandit personal fields; addresses reduced to locality per `docs/geography/ownership.md:5` ("exact addresses never enter public search/SEO/AEO") |
| Tenant isolation | every new table has `tenant_id` NOT NULL or an explicit platform-global marker; `AssertTenantIsolation` matrix extended for all §10/§11 tables |
| Erasure | events aggregated then purged on the existing path; profiles resettable; no indefinite raw retention |
| Audit | generation changes, reindex/verify/replay, preference changes, profile reset → kernel audit (DoD item 5) |

---

## 16. Work breakdown

Estimate classes per methodology §1: `S ≤ 1 d`, `M ≤ 3 d`, `L ≤ 5 d`. Each task needs the full
9-section template in `docs/specs/tasks/` before it can be `Approved`; this table is the register,
not the task files. First task of each epic is the scaffold task so later tasks never debate
structure (§5 heuristics).

### E-SR-01 — Instrumentation transport *(P0, unblocks everything)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-SR01-01 | Add missing `/api/search/analytics` + `/api/search/trending` Next routes | S | AC-1 `POST /api/search/analytics` returns 2xx for the exact payload `search-box.tsx:56-67` sends. AC-2 no silently-caught telemetry fetch remains in `components/search` (lint rule). |
| T-SR01-02 | Register search/discovery event schemas in `packages/analytics` | M | AC: an unregistered event fails codegen. |
| T-SR01-03 | `search_query_events` spine + migration + writer | L | AC-tenancy: `AssertTenantIsolation` green. AC: `debug=1` never emits stage timings on the public plane. |
| T-SR01-04 | Per-stage timings + `plan_json` emitted by the engine | M | AC: Σ stage ms ≤ total ms ± 10 % over 10 000 sampled rows. |
| T-SR01-05 | Impression ledger + `IntersectionObserver` visibility rule | M | AC: an option rendered then closed within 200 ms produces zero impression rows. |
| T-SR01-06 | PII scrub + hashing + `search_dimension_registry` cardinality gate | M | AC: digit/phone/email shapes never reach the `query_hash` input (property test). |

### E-SR-02 — Locality foundation *(P0, unblocks the pandit half of the brief)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-SR02-01 | `search_regions` registry + seed + alias resolution | L | AC: `Benares`, `वाराणसी`, `varanasi` resolve to one `region_id`. |
| T-SR02-02 | `pandit_service_areas` table + partner CRUD + import (§5.4 decision) | L | AC-T-SR02-02-1 GIVEN a pandit with a service area for city X WHEN `GET /search?city=X&type=pandit` THEN the pandit is returned. AC-2 (negative) a pandit without one is absent and `search.no_locality_match` is counted. |
| T-SR02-03 | Locality columns on `search_documents` + temple/puja/pandit providers | M | AC: `facets.city` non-empty for all three entity types; `indexed_at` no longer drives `fresh`. |
| T-SR02-04 | Wire `@navdhya/geography` to the registry; retire the dead port | S | AC: ≥1 production consumer of `@navdhya/geography`. |

### E-SR-03 — Transliteration engine v3

| Task | Title | Est |
| --- | --- | --- |
| T-SR03-01 | `AksharaSegmenter` (NFC, grapheme, virama/conjunct, schwa) | L |
| T-SR03-02 | `TransliterationSchemes` registry (dev/iast/hk/wx/slp1/itrans/iapp) + equivalence classes | L |
| T-SR03-03 | Key-set generation replaces the single ASCII fold; document key columns | M |
| T-SR03-04 | `search:export-schemas` + TS loader + shared-vector parity test | M |
| T-SR03-05 | `search_unmapped_codepoint` + dictionary growth loop | S |
| T-SR03-06 | Sanskrit puja-name alias seed (~300 head terms × schemes) | M |

ACs: IAST `rudrābhiṣeka` returns the same top-5 as `रुद्राभिषेक` (golden vectors); the existing 29
Search tests remain green **unchanged**; `packages/search/src/transliteration.ts` contains no
Devanagari literal (architecture test); recall@10 reported per scheme.

### E-SR-04 — Index structures, outbox, generations

| Task | Title | Est |
| --- | --- | --- |
| T-SR04-01 | `search_dictionary` + `search_postings` split, backfill, dual-read window | L |
| T-SR04-02 | Prefix **range** retrieval replacing `LIKE`; collation decision + `EXPLAIN` artifact | M |
| T-SR04-03 | Field-scoped `search_grams` (body prohibited) | M |
| T-SR04-04 | `search_deletes` neighborhood + bounded verify; delete both scans (D-06) | L |
| T-SR04-05 | `search_index_outbox` + observer rewrite + `search:index-work` (lease, idempotency, revision guard) | L |
| T-SR04-06 | Generation coalescing; version bump per batch | S |
| T-SR04-07 | Streaming orphan diff (retire `array_diff` pluck) | S |
| T-SR04-08 | `search:verify` + `search:replay` recovery proof | M |
| T-SR04-09 | SQLite `LIKE` escaping fix (D-12) + dead `$escaped` removal | S |

ACs: a 5 000-row import produces ≤ 10 generation advances; replaying one outbox revision twice
yields identical posting counts; `EXPLAIN` shows a range scan for the prefix query; killing the
worker mid-batch loses and duplicates nothing after recovery.

### E-SR-05 — Planner, retrieval kernel, ranking v3

T-SR05-01 `QueryPlanner`/`ExecutionPlan`/`CandidateBudget` (config-driven, no constants in classes);
T-SR05-02 `SearchEngine` shared by HTTP + embedded; T-SR05-03 retire per-request `SearchScorer`
build in favour of a generation-keyed read model with a byte budget; T-SR05-04 BM25F on persisted
postings + `TopKHeap`; T-SR05-05 `PopularityProjector` (D-01); T-SR05-06 policy versioning +
`explain=1`; T-SR05-07 diversity/type caps; T-SR05-08 degrade path.

AC: cold worker and warm worker return identical ordering; **no weight in the default policy
references a column without a registered writer** (the D-01 invariant, enforced by test).

### E-SR-06 — Pattern aggregation & served intelligence

T-SR06-01 `search:aggregate-patterns` (all 11 families, §10.5); T-SR06-02
`search_locality_demand`; T-SR06-03 zero-result cause taxonomy; T-SR06-04 populate `search_terms`
`kind=popular_query|city|category` and switch `trending` to read it; T-SR06-05
`GET /search/health`; T-SR06-06 `search_typed_sessions` (sampled); T-SR06-07 telemetry health
report; T-SR06-08 **first measured workload distribution report** — answers A-5 and re-prioritises
§6.6 and §11.

### E-SR-07 → E-SR-10 — Recommendations (gate G-REC-0 first)

| Epic | Content |
| --- | --- |
| E-SR-07 | reco module scaffold, schema (§11.2), impression/interaction pipeline, S1+S2, reason codes, `/api/v1/discovery` |
| E-SR-08 | S4 item-to-item (session/user weighted separately), S5 Markov + session KNN, RRF fusion, MMR, complementary-vs-substitute kinds |
| E-SR-09 | interest profiles (short/long/explicit/negative), preference + erasure APIs, control centre UI, cold-start policy, fraud guard |
| E-SR-10 | Wave 3 research-only: PageRank, PrefixSpan, Thompson, counterfactual eval — each gated on G-REC-3 |

---

## 17. Acceptance gates

| Gate | Blocks | Evidence required |
| --- | --- | --- |
| **G-DATA-0** | E-SR-02/05 | booking facts available per entity; §5.4 pandit decision recorded |
| **G-ENV-0** | E-SR-03 | `ext-intl` present in the prod image, or a signed-off reduced-functionality fallback documented (never advertised as equivalent) |
| **G-INST-1** | **everything in §11** | E-SR-01 green in prod; ≥7 days of `search_query_events`; impressions/query ratio within 0.6–1.4 |
| **G-META-1** | §6.6, §11 priorities | A-5 answered — real prefix/fuzzy/substring/multi-term distribution replaces the assumed split |
| **G-BENCH-0** | E-SR-04 | index-size + write-IOPS projection at `prod` corpus signed off |
| **G-BENCH-1** | E-SR-05 | prefix P95 ≤ 25 ms, P99 ≤ 50 ms at `prod`, **under PHP-FPM**, cold and warm both reported |
| **G-BENCH-2** | ship | fuzzy/substring P95 ≤ 75 ms, P99 ≤ 150 ms; zero unbounded scans in the delete/ngram paths |
| **G-BENCH-3** | any §6.6 promotion | measured win over §6.1/§6.3 in latency **or** memory **or** write cost, report attached |
| **G-TEXT-1** | E-SR-03 exit | canonically equivalent text matches across scripts; IAST/HK/SLP1 recall@10 ≥ 95 % on the golden set; PHP↔TS parity test green |
| **G-LOCAL-1** | E-SR-02 exit | all three entity types filterable and facetable by canonical locality; zero null-`city` pandits in a serviced region |
| **G-CORRECT-1** | ship | 100 % exact-prefix recall vs oracle; ≥95 % Recall@10 on the 1-edit typo corpus; a deleted entity never resurfaces past the freshness window |
| **G-TENANT-0** | every epic | zero cross-tenant leakage; negative authorization AC per task; predicates applied before top-K |
| **G-REC-0** | E-SR-07 | G-INST-1 met; anonymous vs authenticated isolation proven; logout clears recommendation context |
| **G-REC-1** | E-SR-08 | personalized ranking **beats the non-personalized baseline** on the temporal split in ≥2 of {NDCG@10, Recall@10, catalog coverage}; else stop |
| **G-REC-2** | E-SR-09 | users can disable personalization, inspect interests, reset profile; every displayed reason re-derives from stored features |
| **G-REC-3** | E-SR-10 | G-REC-1 + G-REC-2 + ≥1 month of reliable exposure logs; personalization must not push prefix P95 over budget |
| **G-REL** | release | §14.3 report attached; all twelve reliability scenarios pass — incl. kill-mid-batch, DB blip, corrupt snapshot, rebuild under load, version mismatch, malformed UTF-8 |

Release train: **Alpha** = E-SR-01+03 (prefix + script-correct). **Beta** = +E-SR-02+04 (locality,
outbox, recovery). **RC1** = +E-SR-05 (planner, ranking, budgets). **RC2** = +E-SR-06 (patterns,
health). **Prod v3** = §14 gates green. **Wave 2** = E-SR-07…09 behind G-REC-*. No phase is
skipped because the next algorithm is more interesting.

---

## 18. Risks & kill criteria

| # | Risk | Control | Kill / revert trigger |
| --- | --- | --- | --- |
| R-01 | The transliteration engine fails to beat today's accidental fold | measure scheme-specific recall | if IAST recall@10 gain < 3 % absolute at `prod`, freeze T-SR03-06 expansion and ship the registry only |
| R-02 | N-gram + delete tables balloon `central` disk | field-scoped only, weekly size report, `search:compact` | > 6× `search_documents` ⇒ drop long-field grams, revert to prefix + alias |
| R-03 | Per-request rebuild returns via a tempting local cache | byte-budget test, DB authoritative, "correct cold" AC | > 24 MB/worker ⇒ shrink the frequency-capped dictionary, do not raise the budget |
| R-04 | Telemetry becomes the largest table in the product | sampling, caps, retention ladder, dimension registry, health report | > 20 % DB growth from events ⇒ cut typing sample rate, then stage timings |
| R-05 | Ranking tuned on a signal that does not exist (D-01 again) | policy-references-a-writer test; weights versioned and traced | any unattributed weight ⇒ policy rejected at review |
| R-06 | Collation silently makes prefix ranges non-indexed | explicit collation decision, `EXPLAIN` committed as artifact | plan shows a scan ⇒ range retrieval blocked from production |
| R-07 | FPM workers diverge | generation-keyed cache, `revision_seen` per request, `search:verify` | observed divergence ⇒ disable local cache, DB-only |
| R-08 | Feedback loop + click manipulation | smoothing, min exposure, caps, fraud flags, reversibility | unexplained CTR spike ⇒ freeze popularity at last-known-good generation |
| R-09 | False personalization destroys trust | explicit-beats-inferred, intent hierarchy, controls, opt-in default off | hide rate above baseline ⇒ demote S3/S4 to content-only |
| R-10 | Locality data never collected by partners (D-03 recurs) | `search.no_locality_match` monitored; onboarding gate | < 60 % pandit coverage in a launched city ⇒ block that city's search launch |
| R-11 | Twenty algorithms before a baseline | §6.6 prohibition, gates, one-engine rule | a prototype without a report ⇒ rejected |
| R-12 | PHP/TS drift recurs (D-13) | generated artifact + shared vectors + no-literal test | any hand-copied map ⇒ architecture test fails |

**Honest state of this design.** The architecture is defensible; nothing here is
production-proven. The cited research establishes that these algorithm families work in named
systems — it does not establish that this implementation beats a mature search product, and §14
exists to answer that rather than assert it. The five hard questions stay open, and the sequencing
in §16 is precisely a claim about their answers: (1) can PHP beat the DB's indexed prefix query —
probably not, so the DB is canonical and PHP caches; (2) bytes per term per worker — measured, not
assumed (R-03); (3) concurrent mutations without locking the index — outbox + generations;
(4) does fuzzy beat alias + prefix — decided by G-META-1, not taste; (5) do n-grams pay for their
storage — decided by G-BENCH-0.

The most valuable thing in this document is not a trie. It is **E-SR-01 and E-SR-02**: two missing
routes and one locality table that turn a search box which reports nothing about its own traffic
into a corpus whose third of entities cannot be filtered by place into something measurable.
Everything after those two epics is a claim until they produce data.

---

## 19. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0028 | Search text layer v3: key sets, scheme registry, generated cross-language artifact | supersedes ADR-0024 §2's fold-only decision; retires the hand-mirrored TS copy (D-13) |
| ADR-0029 | Locality as a canonical dimension | the §5.4 pandit decision; the registry as sole alias resolver |
| ADR-0030 | Indexing: transactional outbox + generation versioning | supersedes ADR-0024 §3's per-write bump; fixes D-08/D-09 |
| ADR-0031 | Search & discovery telemetry contract | event schemas as a public contract; the visibility rule; the cardinality registry (the D-11 lesson written down) |
| ADR-0032 | Recommendation domain boundary | shared kernel, separate APIs, G-REC-1, no fabricated profiles |
| ADR-0033 | No external search infrastructure (restated) | reaffirms §2.3 against future pressure, per the brief's boundary |

Also required on merge: `module.json` permission/event deltas for both modules (reindex, verify,
replay, discovery, personalization controls, `search:telemetry:manage`), Deptrac layer updates,
OpenAPI fragments, and a section in `docs/16-analytics/analytics.md` for the §10.2 taxonomy.

---

## 20. Verification (this document)

Accepted when:

1. Every `D-` row in §1.2 cites a `file:line` that still resolves, and no row contradicts the code.
2. Every epic in §16 maps to at least one gate in §17.
3. Every AC written into `docs/specs/tasks/` conforms to methodology §3 — observable, decidable,
   CI-testable, independently falsifiable; the banned words ("appropriate", "reasonable",
   "user-friendly") do not appear.
4. Every task carries a tenancy AC if it touches tenant data and a negative authorization AC if it
   touches authorization.
5. Every referenced event/permission/scope exists in a module manifest, or is added there in the
   same task.
6. No section cites a research or benchmark result that §14 has not produced.
7. Doc drift is a review blocker: a behaviour change without a change to this file fails DoD
   item 7.
