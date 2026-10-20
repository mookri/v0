# ADR-0024 — Search core v2 (custom scorer, tokenizer, versioned index)

Status: Accepted (S59, 2026-10-18)
Supersedes: none (extends the v0 Search module)

## Context

v0 Search relied on MySQL FULLTEXT (boolean mode) for both recall **and**
ordering, with `score = 1.0` placeholder and relevance-order + `id` cursor
pagination that broke on page 2+. The BFF already shipped a custom in-process
BM25 engine (`apps/web/lib/search/bm25.ts`) but the platform leg had no
scoring authority, no Devanagari support (tokenizer was `[^a-z0-9]+`), no
popularity signal, and no freshness/version contract for BFF invalidation.

## Decision (S59)

1. **Custom scorer is the authority.** `App\Modules\Search\Services\SearchScorer`
   implements BM25F (title×3, subtitle×2, body×1, k1=1.2, b=0.75) plus
   exact-title, prefix-token, popularity (log-normalised bookings/views),
   freshness, locale-match and proximity, with deterministic tie-break
   (popularity → title → id). FULLTEXT stays recall-only. Constants live in
   one config location per side; a shared golden fixture
   (`tests/Fixtures/Search/ranking-golden.json`) pins ordering for both PHP
   and the future TS `@navdhya/search` (S62) parity test.
2. **Unicode-aware normalizer.** `SearchNormalizer` folds Devanagari → Latin
   via a versioned data map (`Support/TransliterationMap`), emits Devanagari
   akshara bigrams for ngram recall, applies per-language stopwords and
   1-hop synonym expansion (capped at 3) from `search_synonyms`.
3. **Versioned index.** `search_meta` holds `search_version`, incremented on
   every document/term write (`SearchIndexer::bumpVersion()`). BFFs poll a
   cheap HEAD/ETag instead of a 10-min TTL. Observers already call
   `upsertDocument`, which now also rebuilds terms and bumps the version.
4. **Suggestion postings.** New `search_terms` table (one row per
   (normalized term, document)) with `weight`, `kind`, cached title/url/city,
   tenant + locale scoping. `search_synonyms` holds the locale-scoped
   1-hop graph.
5. **Popularity on documents.** `search_documents` gains `bookings_30d`,
   `views_7d`, `locale`, `popularity_updated_at`.
6. **Provider correctness.** `TempleSearchProvider`, `PanditSearchProvider`
   and `PujaServiceSearchProvider::getDocuments()`/`getDocumentsSince()` now
   `->map()` to `SearchDocument` (they previously returned raw models, so full
   reindex for those types never actually upserted — latent v0 bug).
7. **Chunked, idempotent reindex.** `search:reindex {type?} {--fresh}
   {--chunk=500}` reports terms written + new version.

## Consequences

- FULLTEXT remains the recall generator; ranking no longer depends on MySQL
  boolean-mode operator stripping.
- Hindi/Latin cross-script matching is best-effort (char-level fold); inherent
  vowel (schwa) handling is deferred to a later tokenizer slice.
- BFF freshness moves from 10-min TTL to version-poll (wired in S63).
- No new baseline entries: all new code passes phpstan level 6 cleanly.

## Tests

- `tests/Unit/Search/SearchNormalizerTest.php` (5)
- `tests/Unit/Search/SearchScorerTest.php` (7)
- `tests/Modules/Search/SearchCoreV2Test.php` (5)
- `tests/Modules/Search/SearchCoreV2GoldenTest.php` (1, 7 assertions)
- Existing `SearchModuleTest` (11) still green. Total Search-related: 29.