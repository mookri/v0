<?php

namespace App\Modules\Search\Planning;

use App\Modules\Search\Support\SearchDimensionRegistry;
use Illuminate\Support\Str;

class QueryPlanner
{
    /**
     * Plan query execution strategy and candidate budgets.
     */
    public function plan(
        string $rawQuery,
        ?string $entityType = null,
        ?string $city = null,
        string $locale = 'en'
    ): ExecutionPlan {
        $trimmed = trim($rawQuery);
        $norm = Str::lower($trimmed);
        $len = mb_strlen($trimmed, 'UTF-8');
        $script = SearchDimensionRegistry::detectScript($trimmed);
        $hasDiacritics = (bool) preg_match('/[\x{0100}-\x{024F}\x{1E00}-\x{1EFF}]/u', $trimmed);

        $tokens = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokenCount = count($tokens);

        $features = [
            'length' => $len,
            'token_count' => $tokenCount,
            'script_family' => $script,
            'has_diacritics' => $hasDiacritics,
            'entity_type' => $entityType,
            'city' => $city,
            'locale' => $locale,
        ];

        // Determine stage pipeline
        $stages = ['exact'];

        if ($len >= 2) {
            $stages[] = 'prefix';
        }

        if ($script === 'devanagari' || $script === 'iast' || $hasDiacritics) {
            $stages[] = 'scheme_equivalence';
        }

        if ($tokenCount > 1) {
            $stages[] = 'inverted_intersect';
        }

        if ($script === 'devanagari' || $script === 'mixed') {
            $stages[] = 'ngram';
        }

        if ($len >= 4) {
            $stages[] = 'delete_neighborhood';
        }

        $budgets = [
            'soft_candidates' => (int) config('search.budgets.prefix_candidates_soft', 300),
            'hard_candidates' => (int) config('search.budgets.prefix_candidates_hard', 1000),
            'fuzzy_candidates' => (int) config('search.budgets.fuzzy_candidates_soft', 120),
            'timeout_ms' => (int) config('search.budgets.stage_timeout_ms', 25),
        ];

        return new ExecutionPlan(
            queryRaw: $trimmed,
            queryNorm: $norm,
            stages: $stages,
            features: $features,
            budgets: $budgets
        );
    }
}
