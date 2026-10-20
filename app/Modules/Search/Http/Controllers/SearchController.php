<?php

namespace App\Modules\Search\Http\Controllers;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\Models\SearchTerm;
use App\Modules\Search\Models\SearchMeta;
use App\Modules\Search\SearchIndexer;
use App\Modules\Search\Services\SearchNormalizer;
use App\Modules\Search\Services\SearchScorer;
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

        $tenantId = $request->headers->get('X-Tenant-Id');

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
            $baseQuery->where(function ($q) use ($query) {
                $terms = preg_split('/\s+/u', trim($query)) ?: [];
                foreach ($terms as $term) {
                    $term = trim($term);
                    if ($term === '') {
                        continue;
                    }
                    $q->where(function ($qq) use ($term) {
                        $qq->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('subtitle', 'LIKE', "%{$term}%")
                            ->orWhere('body', 'LIKE', "%{$term}%");
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

        $tenantId = $request->headers->get('X-Tenant-Id');
        $start = microtime(true);

        $normalized = $this->normalizer->normalize($query);
        $firstToken = $this->normalizer->tokenize($query, 'en');
        $firstToken = $firstToken[0] ?? $normalized;

        $escaped = addcslashes($query, '%_\\');
        $terms = SearchTerm::with('document')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->where('normalized', 'LIKE', "{$firstToken}%")
            ->orderByDesc('weight')
            ->limit($limit * 4)
            ->get();

        // Fuzzy fallback if no prefix hits.
        if ($terms->isEmpty()) {
            $all = SearchTerm::with('document')
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id');
                    if ($tenantId) {
                        $q->orWhere('tenant_id', $tenantId);
                    }
                })
                ->limit(200)
                ->get();
            $terms = $all->filter(function ($t) use ($firstToken) {
                return levenshtein($t->normalized, $firstToken) <= 1;
            })->take($limit * 4);
        }

        // Fallback to SearchDocument prefix LIKE when no terms are indexed
        // (e.g. direct inserts in tests or pre-reindex state).
        if ($terms->isEmpty()) {
            $escaped = addcslashes($query, '%_\\');
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

    public function entity(string $type, string $slug): JsonResponse
    {
        $doc = SearchDocument::active()
            ->where('entity_type', $type)
            ->where('slug', $slug)
            ->first();

        if (! $doc) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Entity not found'],
            ], 404);
        }

        return response()->json(['data' => $doc->toArray()]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $queries = $request->input('queries', []);

        if (! is_array($queries) || count($queries) > 100) {
            return response()->json([
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid batch (max 100)'],
            ], 422);
        }

        $toInsert = [];
        foreach ($queries as $q) {
            $text = trim((string) ($q['q'] ?? ''));
            if ($text === '') {
                continue;
            }

            $normalized = Str::lower(preg_replace('/\s+/', ' ', $text));
            $hash = hash('sha256', $normalized);

            $toInsert[] = [
                'id' => (string) Str::ulid(),
                'query_hash' => $hash,
                'query_text' => $text,
                'entity_type' => $q['type'] ?? null,
                'city' => $q['city'] ?? null,
                'result_count' => (int) ($q['results'] ?? 0),
                'zero_results' => ($q['results'] ?? 0) === 0,
                'searched_at' => now(),
                'trace_id' => isset($q['trace_id']) ? (string) $q['trace_id'] : null,
                'search_version' => isset($q['search_version']) ? (int) $q['search_version'] : null,
            ];
        }

        if ($toInsert) {
            SearchQuery::insert($toInsert);
        }

        return response()->json(['data' => ['accepted' => count($toInsert)]]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 20), 1), 50);

        $topQueries = SearchQuery::where('searched_at', '>=', now()->subDays(30))
            ->selectRaw('query_text, COUNT(*) as count, SUM(zero_results) as zero_count')
            ->groupBy('query_text')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'query' => $r->query_text,
                'count' => $r->count,
                'zero_results' => (bool) $r->zero_count,
            ])
            ->toArray();

        return response()->json(['data' => ['trending' => $topQueries]]);
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
            $traceId, $recallMs, $scoreMs, $totalMs
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
        $terms = SearchTerm::where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        })
            ->select('normalized')
            ->distinct()
            ->limit(500)
            ->pluck('normalized')
            ->all();

        $tokens = $this->normalizer->tokenize($query, 'en');
        $suggestions = [];
        foreach ($tokens as $t) {
            $best = null;
            $bestDist = 3;
            foreach ($terms as $term) {
                $d = levenshtein($t, $term);
                if ($d > 0 && $d < $bestDist) {
                    $bestDist = $d;
                    $best = $term;
                }
            }
            if ($best !== null) {
                $suggestions[] = $best;
            } else {
                $suggestions[] = $t;
            }
        }

        $result = trim(implode(' ', $suggestions));
        return $result !== '' && $result !== $query ? $result : null;
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