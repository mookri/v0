<?php

namespace App\Modules\Search\Http\Controllers;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchImpression;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\Models\SearchQueryEvent;
use App\Modules\Search\Models\SearchSelection;
use App\Modules\Search\Models\SearchTerm;
use App\Modules\Search\SearchIndexer;
use App\Modules\Search\Services\SearchNormalizer;
use App\Modules\Search\Services\SearchScorer;
use App\Modules\Search\Support\PiiScrubber;
use App\Modules\Search\Support\SearchDimensionRegistry;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController
{
    public function __construct(
        private readonly SearchIndexer $indexer,
        private readonly SearchNormalizer $normalizer,
    ) {
    }

    /**
     * GET /api/v1/search?q=&type=&city=&locale=&limit=&cursor=&debug=
     */
    public function search(Request $request): JsonResponse
    {
        $traceId = $this->traceId($request);
        $start = microtime(true);

        $query = trim((string) $request->query('q', ''));
        $type = $request->query('type');
        $city = $request->query('city');
        $locale = trim((string) $request->query('locale', 'en')) ?: 'en';
        $limit = min(max((int) $request->query('limit', 20), 1), 50);
        $cursor = $request->query('cursor');
        $debug = (bool) $request->query('debug', false) && config('app.debug');

        if ($query === '') {
            $resp = response()->json([
                'data' => [
                    'results' => [],
                    'facets' => ['type' => [], 'city' => []],
                    'next_cursor' => null,
                    'took_ms' => 0,
                    'search_version' => $this->indexer->getVersion(),
                    'did_you_mean' => null,
                ],
            ]);
            $this->attachTrace($resp, $traceId, 0, 0, 0);
            return $resp;
        }

        $tenantId = $this->resolveTenantId($request);

        // Recall: FULLTEXT (mysql) / LIKE (sqlite + tests).
        $recallStart = microtime(true);
        $baseQuery = SearchDocument::active();
        $baseQuery->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });
        if ($type && in_array($type, ['seo_page', 'temple', 'pandit', 'puja_service'], true)) {
            $baseQuery->where('entity_type', $type);
        }
        if ($city) {
            $baseQuery->where('city', $city);
        }

        $driver = config('database.connections.central.driver');
        if ($driver === 'mysql') {
            $baseQuery->whereRaw(
                'MATCH(title, subtitle, body) AGAINST(? IN BOOLEAN MODE)',
                [$this->toBooleanMode($query)]
            );
        } else {
            $baseQuery->where(function ($q) use ($query, $driver) {
                $terms = preg_split('/\s+/u', trim($query)) ?: [];
                foreach ($terms as $term) {
                    $term = trim($term);
                    if ($term === '') {
                        continue;
                    }
                    $escapedTerm = addcslashes($term, '%_');
                    $q->where(function ($qq) use ($escapedTerm, $driver) {
                        if ($driver === 'sqlite') {
                            $qq->whereRaw("title LIKE ? ESCAPE '\\'", ["%{$escapedTerm}%"])
                                ->orWhereRaw("subtitle LIKE ? ESCAPE '\\'", ["%{$escapedTerm}%"])
                                ->orWhereRaw("body LIKE ? ESCAPE '\\'", ["%{$escapedTerm}%"]);
                        } else {
                            $qq->where('title', 'LIKE', "%{$escapedTerm}%")
                                ->orWhere('subtitle', 'LIKE', "%{$escapedTerm}%")
                                ->orWhere('body', 'LIKE', "%{$escapedTerm}%");
                        }
                    });
                }
            });
        }

        $candidates = $baseQuery->limit(500)->get();
        $recallMs = (int) ((microtime(true) - $recallStart) * 1000);

        // Score.
        $scoreStart = microtime(true);
        $scorer = new SearchScorer($candidates, $this->normalizer);
        $ranked = $scorer->rank($query, $locale, 500);
        $scoreMs = (int) ((microtime(true) - $scoreStart) * 1000);

        // Cursor: opaque base64 of last doc id.
        $startIndex = 0;
        if ($cursor) {
            $decoded = base64_decode($cursor, true);
            if ($decoded !== false) {
                $lastId = $decoded;
                foreach ($ranked as $i => $r) {
                    if ($r['doc']['id'] === $lastId || $r['doc']['id'] === (string) $r['doc']['id'] && $r['doc']['id'] === $lastId) {
                        $startIndex = $i + 1;
                        break;
                    }
                }
            }
        }

        $slice = array_slice($ranked, $startIndex, $limit + 1);
        $hasMore = count($slice) > $limit;
        if ($hasMore) {
            $slice = array_slice($slice, 0, $limit);
        }
        $lastDoc = end($slice);
        $nextCursor = ($hasMore && $lastDoc) ? base64_encode((string) $lastDoc['doc']['id']) : null;

        $results = array_map(fn ($r) => $this->formatResult($r['doc'], $r['score']), $slice);

        // did_you_mean
        $didYouMean = null;
        if (empty($results)) {
            $didYouMean = $this->didYouMean($query, $tenantId);
        }

        $facets = $this->getFacets($tenantId, $type, $city);

        $totalMs = (int) ((microtime(true) - $start) * 1000);

        $data = [
            'results' => array_values($results),
            'facets' => $facets,
            'next_cursor' => $nextCursor,
            'took_ms' => $totalMs,
            'search_version' => $this->indexer->getVersion(),
            'did_you_mean' => $didYouMean,
        ];
        if ($debug) {
            $data['debug'] = [
                'recall_ms' => $recallMs,
                'score_ms' => $scoreMs,
                'candidates' => count($candidates),
                'ranked' => count($ranked),
            ];
        }

        $scrubbed = PiiScrubber::scrub($query);
        $scriptFamily = SearchDimensionRegistry::detectScript($query);
        $zeroReason = empty($results) ? 'no_token' : null;

        try {
            SearchQueryEvent::create([
                'trace_id' => $traceId,
                'session_id' => $request->header('X-Session-ID') ?? $request->header('X-Session-Token'),
                'user_id' => $request->user()?->id,
                'surface' => $request->header('X-Search-Surface', 'web_search_page'),
                'query_text_raw' => $scrubbed,
                'query_text_norm' => Str::lower($scrubbed),
                'script_family' => $scriptFamily,
                'region_code' => $city,
                'plan_type' => 'fulltext',
                'plan_json' => [
                    'limit' => $limit,
                    'candidates' => count($candidates),
                    'ranked' => count($ranked),
                    'locale' => $locale,
                ],
                'result_count' => count($results),
                'zero_result_reason' => $zeroReason,
                'generation' => $this->indexer->getVersion(),
                'stage_parse_ms' => 0,
                'stage_recall_ms' => $recallMs,
                'stage_rank_ms' => $scoreMs,
                'stage_total_ms' => $totalMs,
                'searched_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never break search response on telemetry write failure
        }

        $resp = response()->json(['data' => $data]);
        $this->attachTrace($resp, $traceId, $recallMs, $scoreMs, $totalMs);
        return $resp;
    }

    /**
     * GET /api/v1/search/suggest?q=
     */
    public function suggest(Request $request): JsonResponse
    {
        $traceId = $this->traceId($request);
        $query = trim((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 10), 1), 20);

        if (strlen($query) < 2) {
            $resp = response()->json(['data' => ['suggestions' => []]]);
            $this->attachTrace($resp, $traceId, 0, 0, 0);
            return $resp;
        }

        $tenantId = $this->resolveTenantId($request);
        $start = microtime(true);

        $normalized = $this->normalizer->normalize($query);
        $firstToken = $this->normalizer->tokenize($query, 'en');
        $firstToken = $firstToken[0] ?? $normalized;
        $firstTokenClean = addcslashes($firstToken, '%_');

        $terms = SearchTerm::with('document')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->where('normalized', 'LIKE', "{$firstTokenClean}%")
            ->orderByDesc('weight')
            ->limit($limit * 4)
            ->get();

        // Fuzzy fallback using Deletion Neighborhood (Closes D-06)
        if ($terms->isEmpty()) {
            $fuzzyMatcher = app(\App\Modules\Search\Services\FuzzyNeighborhoodMatcher::class);
            $fuzzyMatches = $fuzzyMatcher->match($firstToken, $tenantId, 'latin_fold', 2, $limit * 2);
            if (! empty($fuzzyMatches)) {
                $matchedTerms = array_column($fuzzyMatches, 'term');
                $terms = SearchTerm::with('document')
                    ->where(function ($q) use ($tenantId) {
                        $q->whereNull('tenant_id');
                        if ($tenantId) {
                            $q->orWhere('tenant_id', $tenantId);
                        }
                    })
                    ->whereIn('normalized', $matchedTerms)
                    ->limit($limit * 4)
                    ->get();
            }
        }

        // Fallback to SearchDocument prefix LIKE when no terms are indexed
        // (e.g. direct inserts in tests or pre-reindex state).
        if ($terms->isEmpty()) {
            $escaped = addcslashes($query, '%_');
            $docs = SearchDocument::active()
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id');
                    if ($tenantId) {
                        $q->orWhere('tenant_id', $tenantId);
                    }
                })
                ->where('title', 'LIKE', "{$escaped}%")
                ->limit($limit)
                ->get();
            $synthetic = collect();
            foreach ($docs as $d) {
                $synthetic->push((object) [
                    'title' => $d->title,
                    'url' => $d->url_path,
                    'city' => $d->city,
                    'weight' => 1.0,
                    'tenant_id' => $d->tenant_id,
                    'document' => $d,
                ]);
            }
            $terms = $synthetic;
        }

        // Group by entity type.
        $grouped = [];
        foreach ($terms as $t) {
            $type = isset($t->document) ? $t->document->entity_type : $t->kind;
            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            if (count($grouped[$type]) >= 5) {
                continue;
            }
            $doc = isset($t->document) ? $t->document : null;
            $grouped[$type][] = [
                'title' => $t->title ?? ($doc !== null ? $doc->title : null),
                'slug' => $doc !== null ? $doc->slug : null,
                'city' => $t->city,
                'subtitle' => $doc !== null ? $doc->subtitle : null,
                'url' => $t->url ?? ($doc !== null ? $doc->url_path : null),
            ];
        }

        $suggestions = [];
        foreach ($grouped as $type => $items) {
            $suggestions[] = [
                'type' => $type,
                'label' => $this->typeLabel($type),
                'items' => $items,
            ];
        }

        $tookMs = (int) ((microtime(true) - $start) * 1000);
        $resp = response()->json(['data' => ['suggestions' => $suggestions, 'took_ms' => $tookMs]]);
        $this->attachTrace($resp, $traceId, 0, 0, $tookMs);
        return $resp;
    }

    /**
     * GET /api/v1/search/index-docs
     */
    public function indexDocs(Request $request): JsonResponse
    {
        $version = $this->indexer->getVersion();

        $etag = '"' . $version . '-' . SearchDocument::active()->count() . '"';
        if ($request->header('If-None-Match') === $etag) {
            $r = response()->json([], 304);
            $r->headers->set('ETag', $etag);
            $r->headers->set('X-Search-Version', (string) $version);
            return $r;
        }

        $docs = SearchDocument::active()
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', config('platform.default_tenant'));
            })
            ->get(['entity_type', 'entity_id', 'slug', 'title', 'subtitle', 'body', 'city', 'card_json', 'url_path'])
            ->map(fn ($d) => [
                'entity_type' => $d->entity_type,
                'entity_id' => $d->entity_id,
                'slug' => $d->slug,
                'url' => $d->url_path,
                'title' => $d->title,
                'subtitle' => $d->subtitle,
                'body' => $d->body,
                'city' => $d->city,
                'card' => $this->cardOf($d),
            ])
            ->toArray();

        $resp = response()->json(['data' => $docs]);
        $resp->headers->set('ETag', $etag);
        $resp->headers->set('X-Search-Version', (string) $version);
        return $resp;
    }

    public function entity(Request $request, string $type, string $slug): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        // The tenant predicate is mandatory here: without it this
        // public, unauthenticated endpoint returned ANY tenant's search
        // document by slug - a cross-tenant read of the full row
        // (title, body, card_json, url_path, image_url, city).
        $doc = SearchDocument::active()
            ->where('entity_type', $type)
            ->where('slug', $slug)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->first();

        if (! $doc) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Entity not found'],
            ], 404);
        }

        return response()->json(['data' => $doc->toArray()]);
    }

    /**
     * Resolve the tenant a public search may read.
     *
     * The public search surfaces drop the `tenant` middleware, so the
     * `X-Tenant-Id` header was honoured verbatim: an anonymous caller
     * could name ANY tenant (or a non-existent one) and read its
     * indexed catalogue. The header is now only honoured for a tenant
     * that exists and is ACTIVE; anything else falls back to the
     * platform-default documents (`tenant_id IS NULL`).
     */
    private function resolveTenantId(Request $request): ?string
    {
        $header = trim((string) $request->headers->get('X-Tenant-Id', ''));

        if ($header === '') {
            return null;
        }

        $exists = Tenant::query()
            ->whereKey($header)
            ->where('state', Tenant::STATE_ACTIVE)
            ->exists();

        return $exists ? $header : null;
    }

    public function analytics(Request $request): JsonResponse
    {
        $queries = $request->input('queries', []);
        $impressions = $request->input('impressions', []);
        $selections = $request->input('selections', []);

        if ((! is_array($queries) && ! is_array($impressions) && ! is_array($selections))
            || count($queries) > 100 || count($impressions) > 100 || count($selections) > 100) {
            return response()->json([
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid batch (max 100)'],
            ], 422);
        }

        $toInsert = [];
        $toInsertEvents = [];
        foreach ($queries as $q) {
            $rawText = (string) ($q['q'] ?? $q['query'] ?? '');
            $text = PiiScrubber::scrub($rawText);
            if ($text === '') {
                continue;
            }

            $normalized = Str::lower(preg_replace('/\s+/', ' ', $text));
            $hash = hash('sha256', $normalized);
            $traceId = isset($q['trace_id']) ? (string) $q['trace_id'] : (string) Str::ulid();
            $resultCount = (int) ($q['results'] ?? 0);

            $toInsert[] = [
                'id' => (string) Str::ulid(),
                'query_hash' => $hash,
                'query_text' => $text,
                'entity_type' => $q['type'] ?? null,
                'city' => $q['city'] ?? null,
                'result_count' => $resultCount,
                'zero_results' => $resultCount === 0,
                'searched_at' => now(),
                'trace_id' => $traceId,
                'search_version' => isset($q['search_version']) ? (int) $q['search_version'] : null,
            ];

            $toInsertEvents[] = [
                'id' => (string) Str::ulid(),
                'trace_id' => $traceId,
                'session_id' => $q['session_id'] ?? null,
                'user_id' => isset($q['user_id']) ? (int) $q['user_id'] : null,
                'surface' => $q['surface'] ?? 'web_search_page',
                'query_text_raw' => $text,
                'query_text_norm' => $normalized,
                'script_family' => SearchDimensionRegistry::detectScript($text),
                'region_code' => $q['city'] ?? null,
                'plan_type' => $q['plan_type'] ?? 'client_recorded',
                'plan_json' => isset($q['plan_json']) ? json_encode($q['plan_json']) : null,
                'result_count' => $resultCount,
                'zero_result_reason' => $resultCount === 0 ? ($q['zero_result_reason'] ?? 'no_token') : null,
                'generation' => isset($q['search_version']) ? (int) $q['search_version'] : null,
                'stage_parse_ms' => 0,
                'stage_recall_ms' => 0,
                'stage_rank_ms' => 0,
                'stage_total_ms' => (int) ($q['took_ms'] ?? 0),
                'searched_at' => now(),
            ];
        }

        if ($toInsert) {
            SearchQuery::insert($toInsert);
            SearchQueryEvent::insert($toInsertEvents);
        }

        $acceptedImpressions = 0;
        if (is_array($impressions) && count($impressions) > 0) {
            $impRows = [];
            foreach ($impressions as $imp) {
                if (empty($imp['trace_id']) || empty($imp['entity_type']) || empty($imp['entity_id'])) {
                    continue;
                }
                $impRows[] = [
                    'id' => (string) Str::ulid(),
                    'trace_id' => (string) $imp['trace_id'],
                    'entity_type' => (string) $imp['entity_type'],
                    'entity_id' => (string) $imp['entity_id'],
                    'position' => (int) ($imp['position'] ?? 0),
                    'page_offset' => (int) ($imp['page_offset'] ?? 0),
                    'score' => isset($imp['score']) ? (float) $imp['score'] : null,
                    'dwell_ms' => (int) ($imp['dwell_ms'] ?? 0),
                    'viewport_ratio' => isset($imp['viewport_ratio']) ? (float) $imp['viewport_ratio'] : 0.50,
                    'impressed_at' => now(),
                ];
            }
            if ($impRows) {
                SearchImpression::insert($impRows);
                $acceptedImpressions = count($impRows);
            }
        }

        $acceptedSelections = 0;
        if (is_array($selections) && count($selections) > 0) {
            $selRows = [];
            foreach ($selections as $sel) {
                if (empty($sel['trace_id']) || empty($sel['entity_type']) || empty($sel['entity_id'])) {
                    continue;
                }
                $selRows[] = [
                    'id' => (string) Str::ulid(),
                    'trace_id' => (string) $sel['trace_id'],
                    'entity_type' => (string) $sel['entity_type'],
                    'entity_id' => (string) $sel['entity_id'],
                    'position' => (int) ($sel['position'] ?? 0),
                    'action' => (string) ($sel['action'] ?? 'click'),
                    'dwell_before_click_ms' => isset($sel['dwell_before_click_ms']) ? (int) $sel['dwell_before_click_ms'] : null,
                    'selected_at' => now(),
                ];
            }
            if ($selRows) {
                SearchSelection::insert($selRows);
                $acceptedSelections = count($selRows);
            }
        }

        return response()->json([
            'data' => [
                'accepted' => count($toInsert),
                'accepted_impressions' => $acceptedImpressions,
                'accepted_selections' => $acceptedSelections,
            ],
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 20), 1), 50);

        // Read from precomputed popular search terms (T-SR06-04)
        $popular = SearchTerm::where('kind', 'popular_query')
            ->orderByDesc('weight')
            ->limit($limit)
            ->get(['term', 'weight'])
            ->map(fn ($r) => [
                'query' => (string) $r->term,
                'count' => (int) round(exp((float) $r->weight - 5.0)),
                'zero_results' => false,
            ])
            ->toArray();

        if (empty($popular)) {
            // Fallback to pattern aggregation table
            $patterns = \App\Modules\Search\Models\SearchQueryPattern::where('pattern_type', 'popular_query')
                ->orderByDesc('sample_count')
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'query' => (string) $p->pattern_key,
                    'count' => (int) $p->sample_count,
                    'zero_results' => false,
                ])
                ->toArray();
            $popular = $patterns;
        }

        if (empty($popular)) {
            // Raw queries fallback
            $popular = SearchQuery::where('searched_at', '>=', now()->subDays(30))
                ->selectRaw('query_text, COUNT(*) as count, SUM(zero_results) as zero_count')
                ->groupBy('query_text')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => [
                    'query' => $r->query_text,
                    'count' => (int) $r->count,
                    'zero_results' => (bool) $r->zero_count,
                ])
                ->toArray();
        }

        return response()->json(['data' => ['trending' => $popular]]);
    }

    /**
     * GET /api/v1/search/health (Task T-SR06-05)
     */
    public function health(): JsonResponse
    {
        $generation = $this->indexer->getVersion();
        $docCount = SearchDocument::active()->count();
        $dictCount = \App\Modules\Search\Models\SearchDictionary::count();
        $outboxLag = 0;
        if (\Illuminate\Support\Facades\Schema::connection('tenant_shared')->hasTable('search_index_outbox')) {
            $outboxLag = \App\Modules\Search\Models\SearchIndexOutbox::where('status', 'PENDING')->count();
        }

        $latestDoc = SearchDocument::orderByDesc('indexed_at')->first(['indexed_at']);
        $freshnessSeconds = $latestDoc && $latestDoc->indexed_at
            ? max(0, now()->diffInSeconds($latestDoc->indexed_at))
            : 0;

        return response()->json([
            'status' => $outboxLag > 500 ? 'degraded' : 'healthy',
            'generation' => $generation,
            'documents_indexed' => $docCount,
            'dictionary_terms' => $dictCount,
            'outbox_lag' => $outboxLag,
            'freshness_seconds' => $freshnessSeconds,
        ]);
    }

    /**
     * POST /api/v1/search/typing-telemetry (Task T-SR06-06)
     */
    public function typingTelemetry(Request $request): JsonResponse
    {
        $sampleRate = (float) config('search.telemetry.typing_sample_rate', 0.25);
        if ((mt_rand() / mt_getrandmax()) > $sampleRate) {
            return response()->json(['status' => 'sampled_out']);
        }

        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'trace_id' => 'nullable|string|max:36',
            'query_text' => 'required|string|max:190',
            'keystroke_count' => 'nullable|integer',
            'backspace_count' => 'nullable|integer',
            'ime_composition' => 'nullable|boolean',
            'time_to_first_impression_ms' => 'nullable|integer',
            'total_typing_ms' => 'nullable|integer',
        ]);

        \App\Modules\Search\Models\SearchTypedSession::create([
            'session_id' => $validated['session_id'],
            'trace_id' => $validated['trace_id'] ?? null,
            'query_text' => PiiScrubber::scrub($validated['query_text']),
            'keystroke_count' => (int) ($validated['keystroke_count'] ?? 0),
            'backspace_count' => (int) ($validated['backspace_count'] ?? 0),
            'ime_composition' => (bool) ($validated['ime_composition'] ?? false),
            'time_to_first_impression_ms' => (int) ($validated['time_to_first_impression_ms'] ?? 0),
            'total_typing_ms' => (int) ($validated['total_typing_ms'] ?? 0),
            'settled_at' => now(),
        ]);

        return response()->json(['status' => 'recorded']);
    }

    // ---- helpers ----

    private function traceId(Request $request): string
    {
        $rid = $request->headers->get('X-Request-Id') ?: $request->headers->get('X-Trace-Id');
        return $rid ? (string) $rid : (string) Str::ulid();
    }

    private function attachTrace(JsonResponse $resp, string $traceId, int $recallMs, int $scoreMs, int $totalMs): void
    {
        $resp->headers->set('X-Search-Trace', sprintf(
            'tid=%s; recall=%dms; score=%dms; total=%dms',
            $traceId,
            $recallMs,
            $scoreMs,
            $totalMs
        ));
        $resp->headers->set('X-Request-Id', $traceId);
    }

    /** @return array<string,mixed> */
    private function formatResult(SearchDocument $doc, float $score): array
    {
        return [
            'entity_type' => $doc->entity_type,
            'entity_id' => $doc->entity_id,
            'slug' => $doc->slug,
            'url' => $doc->url_path,
            'title' => $doc->title,
            'subtitle' => $doc->subtitle,
            'snippet' => $this->buildSnippet($doc->body, $doc->title),
            'city' => $doc->city,
            'image' => $doc->image_url,
            'card' => $this->cardOf($doc),
            'score' => $score,
        ];
    }

    /** @return array<string,mixed> */
    private function cardOf(SearchDocument $doc): array
    {
        $card = $doc->card_json;
        if (is_string($card)) {
            $decoded = json_decode($card, true);
            $card = is_array($decoded) ? $decoded : [];
        }
        return is_array($card) ? $card : [];
    }

    private function buildSnippet(?string $body, string $title): string
    {
        if (! $body) {
            return $title;
        }
        $body = strip_tags($body);
        return strlen($body) > 200 ? substr($body, 0, 197) . '...' : $body;
    }

    /** @return array<string,mixed> */
    private function getFacets(?string $tenantId, ?string $type, ?string $city): array
    {
        $query = SearchDocument::active()
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            });

        if ($type) {
            $query->where('entity_type', $type);
        }
        if ($city) {
            $query->where('city', $city);
        }

        $typeCounts = (clone $query)->selectRaw('entity_type, COUNT(*) as count')
            ->groupBy('entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();

        $cityCounts = (clone $query)->selectRaw('city, COUNT(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(20)
            ->pluck('count', 'city')
            ->toArray();

        return [
            'type' => $typeCounts,
            'city' => $cityCounts,
        ];
    }

    private function toBooleanMode(string $query): string
    {
        $escaped = preg_replace('/[+\-><~*()"]/', '', $query);
        $terms = explode(' ', $escaped);
        return implode(' ', array_map(fn ($t) => "+{$t}*", array_filter($terms)));
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'seo_page' => 'Guides & Blogs',
            'temple' => 'Temples',
            'pandit' => 'Pandits',
            'puja_service' => 'Puja Services',
            default => ucfirst($type),
        };
    }

    private function didYouMean(string $query, ?string $tenantId): ?string
    {
        $matcher = app(\App\Modules\Search\Services\FuzzyNeighborhoodMatcher::class);
        return $matcher->didYouMean($query, $tenantId);
    }

    /**
     * POST /api/v1/search/reindex — reindex search documents.
     *
     * Capability `search:index:reindex`. Surfaces the `search:reindex`
     * artisan command over HTTP so the console can trigger it without shell
     * access. Optional `type` narrows to one entity type; `fresh` deletes
     * all existing documents first.
     */
    public function reindex(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $type = is_string($type) && $type !== '' ? $type : null;
        $chunk = (int) $request->input('chunk', 500);
        $chunk = $chunk > 0 ? $chunk : 500;
        $fresh = (bool) $request->boolean('fresh');

        // `fresh` handling mirrors the artisan command: delete all first.
        if ($fresh) {
            \App\Modules\Search\Models\SearchDocument::query()->delete();
        }

        $stats = $this->indexer->reindex($type, $chunk);

        return new JsonResponse([
            'data' => $stats,
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}
