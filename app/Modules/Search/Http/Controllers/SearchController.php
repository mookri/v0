<?php

namespace App\Modules\Search\Http\Controllers;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController
{
    /**
     * GET /api/v1/search?q=&type=&city=&limit=&cursor=
     * Public anonymous search API (platform-global + tenant-scoped).
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $type = $request->query('type');
        $city = $request->query('city');
        $limit = min(max((int) $request->query('limit', 20), 1), 50);
        $cursor = $request->query('cursor');

        if ($query === '') {
            return response()->json([
                'data' => [
                    'results' => [],
                    'facets' => ['type' => [], 'city' => []],
                    'next_cursor' => null,
                    'took_ms' => 0,
                ],
            ]);
        }

        $start = microtime(true);

        $baseQuery = SearchDocument::active();

        // Default: platform-global (null tenant) + current tenant
        $tenantId = $request->headers->get('X-Tenant-Id');
        $baseQuery->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });

        if ($type) {
            $baseQuery->where('entity_type', $type);
        }

        if ($city) {
            $baseQuery->where('city', $city);
        }

        // FULLTEXT search (MySQL) or LIKE fallback (sqlite)
        $driver = config('database.connections.central.driver');

        if ($driver === 'mysql') {
            $baseQuery->whereRaw(
                'MATCH(title, subtitle, body) AGAINST(? IN BOOLEAN MODE)',
                [$this->toBooleanMode($query)]
            );
        } else {
            // SQLite fallback: simple LIKE ranking
            $baseQuery->where(function ($q) use ($query) {
                $terms = explode(' ', $query);
                foreach ($terms as $term) {
                    $q->where(function ($qq) use ($term) {
                        $qq->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('subtitle', 'LIKE', "%{$term}%")
                            ->orWhere('body', 'LIKE', "%{$term}%");
                    });
                }
            });
        }

        // Cursor pagination
        if ($cursor) {
            $baseQuery->where('id', '<', $cursor);
        }

        // Ordering: relevance (FULLTEXT score) + deterministic boosts
        if ($driver === 'mysql') {
            $baseQuery->orderByRaw('
                MATCH(title, subtitle, body) AGAINST(? IN BOOLEAN MODE) DESC,
                CASE WHEN JSON_EXTRACT(card_json, "$.verified") = true THEN 0 ELSE 1 END,
                CASE WHEN JSON_EXTRACT(card_json, "$.rating") IS NOT NULL THEN -JSON_EXTRACT(card_json, "$.rating") ELSE 0 END,
                title ASC,
                id DESC
            ', [$this->toBooleanMode($query)]);
        } else {
            $baseQuery->orderBy('title')->orderBy('id', 'desc');
        }

        $results = $baseQuery->limit($limit + 1)->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results = $results->take($limit);
        }

        $nextCursor = $hasMore ? $results->last()->id : null;

        // Facets
        $facets = $this->getFacets($tenantId, $type, $city);

        $tookMs = (int) ((microtime(true) - $start) * 1000);

        return response()->json([
            'data' => [
                'results' => $results->map(fn ($d) => $this->formatResult($d))->values()->toArray(),
                'facets' => $facets,
                'next_cursor' => $nextCursor,
                'took_ms' => $tookMs,
            ],
        ]);
    }

    /**
     * GET /api/v1/search/suggest?q=
     * Type-ahead suggestions across all entity types.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 10), 1), 20);

        if (strlen($query) < 2) {
            return response()->json(['data' => ['suggestions' => []]]);
        }

        $tenantId = $request->headers->get('X-Tenant-Id');

        $baseQuery = SearchDocument::active()->select('entity_type', 'title', 'slug', 'city', 'card_json')
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            });

        $driver = config('database.connections.central.driver');

        if ($driver === 'mysql') {
            $baseQuery->whereRaw('MATCH(title) AGAINST(? IN BOOLEAN MODE)', [$query . '*']);
        } else {
            $baseQuery->where('title', 'LIKE', "{$query}%");
        }

        $results = $baseQuery->limit($limit)->get();

        $suggestions = $results->groupBy('entity_type')->map(function ($items, $type) {
            return [
                'type' => $type,
                'label' => $this->typeLabel($type),
                'items' => $items->map(fn ($d) => [
                    'title' => $d->title,
                    'slug' => $d->slug,
                    'city' => $d->city,
                    'subtitle' => $this->buildSubtitle($d),
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        return response()->json(['data' => ['suggestions' => $suggestions]]);
    }

    /**
     * GET /api/v1/search/index-docs
     * Lite projection for BFF BM25 index (all types, no body HTML).
     */
    public function indexDocs(): JsonResponse
    {
        $docs = SearchDocument::active()
            ->whereNull('tenant_id') // platform-global for BFF (web is single-tenant)
            ->orWhere('tenant_id', config('platform.default_tenant'))
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
                'card' => $d->card_json,
            ])
            ->toArray();

        return response()->json(['data' => $docs]);
    }

    /**
     * GET /api/v1/search/entities/{type}/{slug}
     * Full public projection for entity detail pages (e.g., /temples/[slug]).
     */
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

    /**
     * POST /api/v1/search/analytics
     * Batched analytics ingest from BFF (sanitized, no identities).
     */
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
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'query_hash' => $hash,
                'query_text' => $text,
                'entity_type' => $q['type'] ?? null,
                'city' => $q['city'] ?? null,
                'result_count' => (int) ($q['results'] ?? 0),
                'zero_results' => ($q['results'] ?? 0) === 0,
                'searched_at' => now(),
            ];
        }

        if ($toInsert) {
            SearchQuery::insert($toInsert);
        }

        return response()->json(['data' => ['accepted' => count($toInsert)]]);
    }

    /**
     * GET /api/v1/search/trending
     * Top queries + zero-result opportunities.
     */
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

    private function formatResult(SearchDocument $doc): array
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
            'card' => $doc->card_json,
            'score' => 1.0, // placeholder; actual score from FULLTEXT when available
        ];
    }

    private function buildSnippet(?string $body, string $title): string
    {
        if (! $body) {
            return $title;
        }
        $body = strip_tags($body);
        return strlen($body) > 200 ? substr($body, 0, 197) . '...' : $body;
    }

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
        // Convert to MySQL boolean mode: escape special chars, add * for prefix
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

    private function buildSubtitle(SearchDocument $doc): string
    {
        $card = $doc->card_json ?? [];
        return match ($doc->entity_type) {
            'temple' => ($card['verified'] ? '✓ Verified • ' : '') . ($card['city'] ?? ''),
            'pandit' => ($card['rating'] ? "★ {$card['rating']} • " : '') . ($card['specializations'][0] ?? ''),
            'puja_service' => "₹{$card['price']} • " . ($card['attributes']['duration'] ?? ''),
            default => '',
        };
    }
}
