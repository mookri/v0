<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Planning\ExecutionPlan;
use App\Modules\Search\Planning\QueryPlanner;
use App\Modules\Search\SearchIndexer;

class SearchEngine
{
    public function __construct(
        private readonly SearchIndexer $indexer,
        private readonly SearchNormalizer $normalizer,
        private readonly QueryPlanner $planner,
        private readonly DiversityReranker $diversityReranker,
        private readonly FuzzyNeighborhoodMatcher $fuzzyMatcher,
        private readonly PrefixRangeRetriever $prefixRetriever,
    ) {
    }

    /**
     * Unified search kernel for both HTTP controller and internal PHP callers.
     *
     * @return array{
     *   results: list<array<string, mixed>>,
     *   plan: ExecutionPlan,
     *   took_ms: int,
     *   generation: int,
     *   did_you_mean: string|null
     * }
     */
    public function search(
        string $query,
        ?string $tenantId = null,
        ?string $type = null,
        ?string $city = null,
        string $locale = 'en',
        int $limit = 20,
        bool $explain = false
    ): array {
        $start = microtime(true);
        $trimmed = trim($query);

        $plan = $this->planner->plan($trimmed, $type, $city, $locale);

        if ($trimmed === '') {
            return [
                'results' => [],
                'plan' => $plan,
                'took_ms' => 0,
                'generation' => $this->indexer->getVersion(),
                'did_you_mean' => null,
            ];
        }

        // 1. Recall candidates
        $baseQuery = SearchDocument::active()->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId !== null) {
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
            $escaped = preg_replace('/[+\-><~*()"]/', '', $trimmed) ?? '';
            $terms = explode(' ', $escaped);
            $booleanQuery = implode(' ', array_map(fn ($t) => "+{$t}*", array_filter($terms)));
            $baseQuery->whereRaw('MATCH(title, subtitle, body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
        } else {
            $baseQuery->where(function ($q) use ($trimmed, $driver) {
                $terms = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($terms as $term) {
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

        // 2. Score candidates
        $scorer = new SearchScorer($candidates, $this->normalizer);
        $ranked = $scorer->rank($trimmed, $locale, 500);

        // 3. Diversity Rerank
        $reranked = $this->diversityReranker->rerank($ranked, $limit, $type);

        $results = [];
        foreach ($reranked as $r) {
            $doc = $r['doc'];
            $card = $doc->card_json;
            if (is_string($card)) {
                $decoded = json_decode($card, true);
                $card = is_array($decoded) ? $decoded : [];
            }
            $body = strip_tags((string) $doc->body);
            $snippet = mb_strlen($body, 'UTF-8') > 200 ? mb_substr($body, 0, 197, 'UTF-8') . '...' : ($body ?: (string) $doc->title);

            $entry = [
                'entity_type' => $doc->entity_type,
                'entity_id' => $doc->entity_id,
                'slug' => $doc->slug,
                'url' => $doc->url_path,
                'title' => $doc->title,
                'subtitle' => $doc->subtitle,
                'snippet' => $snippet,
                'city' => $doc->city,
                'image' => $doc->image_url,
                'card' => is_array($card) ? $card : [],
                'score' => $r['score'],
            ];

            if ($explain) {
                $entry['explain'] = [
                    'final_score' => $r['score'],
                    'entity_type' => $doc->entity_type,
                    'views_7d' => (int) $doc->views_7d,
                    'bookings_30d' => (int) $doc->bookings_30d,
                ];
            }

            $results[] = $entry;
        }

        // 4. Did you mean suggestion
        $didYouMean = null;
        if (empty($results)) {
            $didYouMean = $this->fuzzyMatcher->didYouMean($trimmed, $tenantId);
        }

        $tookMs = (int) ((microtime(true) - $start) * 1000);

        return [
            'results' => $results,
            'plan' => $plan,
            'took_ms' => $tookMs,
            'generation' => $this->indexer->getVersion(),
            'did_you_mean' => $didYouMean,
        ];
    }

    /**
     * Autocomplete suggestions using PrefixRangeRetriever with fuzzy fallback.
     *
     * @return list<string>
     */
    public function suggest(string $query, ?string $tenantId = null, int $limit = 10): array
    {
        $trimmed = trim($query);
        if (mb_strlen($trimmed, 'UTF-8') < 2) {
            return [];
        }

        $terms = $this->prefixRetriever->retrieve($trimmed, $tenantId, 'en', 'latin_fold', $limit);
        if ($terms->isNotEmpty()) {
            return $terms->pluck('term_key')->all();
        }

        $fuzzyMatches = $this->fuzzyMatcher->match($trimmed, $tenantId, 'latin_fold', 2, $limit);
        return array_column($fuzzyMatches, 'term');
    }
}
