<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchLocalityDemand;
use App\Modules\Search\Models\SearchQueryEvent;
use App\Modules\Search\Models\SearchQueryPattern;
use App\Modules\Search\Models\SearchRegion;
use App\Modules\Search\Models\SearchTerm;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PatternAggregator
{
    /**
     * Run aggregation across 11 pattern families and populate locality demand.
     *
     * @return array{
     *   patterns_created: int,
     *   demand_rows: int,
     *   popular_terms_seeded: int
     * }
     */
    public function aggregate(?Carbon $start = null, ?Carbon $end = null): array
    {
        $periodStart = $start ?? now()->subDay()->startOfDay();
        $periodEnd = $end ?? now();

        $queryEvents = SearchQueryEvent::whereBetween('searched_at', [$periodStart, $periodEnd])->get();

        $patternsCreated = 0;

        if ($queryEvents->isEmpty()) {
            return [
                'patterns_created' => 0,
                'demand_rows' => 0,
                'popular_terms_seeded' => 0,
            ];
        }

        $now = now();
        $patternRows = [];

        // 1. Script distribution
        $scriptCounts = [];
        foreach ($queryEvents as $ev) {
            $s = $ev->script_family ?: 'unknown';
            $scriptCounts[$s] = ($scriptCounts[$s] ?? 0) + 1;
        }
        foreach ($scriptCounts as $script => $count) {
            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'script_distribution',
                'pattern_key' => $script,
                'sample_count' => $count,
                'success_count' => $count,
                'empty_count' => 0,
                'metrics_json' => json_encode(['ratio' => round($count / count($queryEvents), 4)]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 2. Cold queries (repeated zero-result searches)
        $zeroQueries = [];
        foreach ($queryEvents as $ev) {
            if ($ev->result_count === 0 && ! empty($ev->query_text_norm)) {
                $zeroQueries[$ev->query_text_norm] = ($zeroQueries[$ev->query_text_norm] ?? 0) + 1;
            }
        }
        foreach ($zeroQueries as $qText => $zeroCount) {
            if ($zeroCount >= 2) {
                $patternRows[] = [
                    'id' => (string) Str::ulid(),
                    'pattern_type' => 'cold_query',
                    'pattern_key' => $qText,
                    'sample_count' => $zeroCount,
                    'success_count' => 0,
                    'empty_count' => $zeroCount,
                    'metrics_json' => json_encode(['zero_result_frequency' => $zeroCount]),
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // 3. High-frequency prefixes (first 2-3 characters)
        $prefixCounts = [];
        foreach ($queryEvents as $ev) {
            $t = $ev->query_text_norm;
            if (mb_strlen($t, 'UTF-8') >= 2) {
                $p2 = mb_substr($t, 0, 2, 'UTF-8');
                $prefixCounts[$p2] = ($prefixCounts[$p2] ?? 0) + 1;
            }
        }
        arsort($prefixCounts);
        foreach (array_slice($prefixCounts, 0, 20, true) as $prefix => $count) {
            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'high_freq_prefix',
                'pattern_key' => $prefix,
                'sample_count' => $count,
                'success_count' => $count,
                'empty_count' => 0,
                'metrics_json' => json_encode(['count' => $count]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 4. Zero-result taxonomy
        $zeroTaxonomy = [];
        foreach ($queryEvents as $ev) {
            if ($ev->result_count === 0) {
                $reason = $ev->zero_result_reason ?: 'no_token';
                $zeroTaxonomy[$reason] = ($zeroTaxonomy[$reason] ?? 0) + 1;
            }
        }
        foreach ($zeroTaxonomy as $reason => $count) {
            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'zero_result_taxonomy',
                'pattern_key' => $reason,
                'sample_count' => $count,
                'success_count' => 0,
                'empty_count' => $count,
                'metrics_json' => json_encode(['count' => $count]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 5. Latency histogram
        $latencies = $queryEvents->pluck('stage_total_ms')->sort()->values()->all();
        if (! empty($latencies)) {
            $n = count($latencies);
            $p50 = $latencies[(int) floor(0.50 * $n)];
            $p95 = $latencies[(int) floor(0.95 * $n)];
            $p99 = $latencies[(int) floor(0.99 * $n)];

            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'latency_histogram',
                'pattern_key' => 'global_total_ms',
                'sample_count' => $n,
                'success_count' => $n,
                'empty_count' => 0,
                'metrics_json' => json_encode(['p50' => $p50, 'p95' => $p95, 'p99' => $p99]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 6. Reformulation chains (same session, <= 90s apart)
        $bySession = $queryEvents->whereNotNull('session_id')->groupBy('session_id');
        $reformulations = [];
        foreach ($bySession as $sessionId => $events) {
            $sorted = $events->sortBy('searched_at')->values();
            for ($i = 0; $i + 1 < count($sorted); $i++) {
                $t1 = $sorted[$i]->searched_at ? Carbon::parse($sorted[$i]->searched_at) : null;
                $t2 = $sorted[$i + 1]->searched_at ? Carbon::parse($sorted[$i + 1]->searched_at) : null;
                if ($t1 && $t2 && abs($t2->diffInSeconds($t1)) <= 90) {
                    $pair = $sorted[$i]->query_text_norm . ' -> ' . $sorted[$i + 1]->query_text_norm;
                    $reformulations[$pair] = ($reformulations[$pair] ?? 0) + 1;
                }
            }
        }
        foreach ($reformulations as $chain => $count) {
            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'reformulation',
                'pattern_key' => $chain,
                'sample_count' => $count,
                'success_count' => $count,
                'empty_count' => 0,
                'metrics_json' => json_encode(['chain_frequency' => $count]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 7. Popular queries
        $queryCounts = [];
        foreach ($queryEvents as $ev) {
            if ($ev->result_count > 0 && ! empty($ev->query_text_norm)) {
                $queryCounts[$ev->query_text_norm] = ($queryCounts[$ev->query_text_norm] ?? 0) + 1;
            }
        }
        arsort($queryCounts);
        foreach (array_slice($queryCounts, 0, 50, true) as $qText => $count) {
            $patternRows[] = [
                'id' => (string) Str::ulid(),
                'pattern_type' => 'popular_query',
                'pattern_key' => $qText,
                'sample_count' => $count,
                'success_count' => $count,
                'empty_count' => 0,
                'metrics_json' => json_encode(['count' => $count]),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($patternRows)) {
            SearchQueryPattern::insert($patternRows);
            $patternsCreated = count($patternRows);
        }

        // 8. Locality Demand Map (Task T-SR06-02)
        $localityDemand = [];
        $today = $periodEnd->toDateString();
        foreach ($queryEvents as $ev) {
            $region = $ev->region_code ?: 'All Regions';
            $plan = $ev->plan_json ?? [];
            $type = isset($plan['entity_type']) && is_string($plan['entity_type']) ? $plan['entity_type'] : 'all';
            $key = $region . '|' . $type;
            if (! isset($localityDemand[$key])) {
                $localityDemand[$key] = [
                    'region_name' => $region,
                    'entity_type' => $type === 'all' ? null : $type,
                    'total' => 0,
                    'empty' => 0,
                ];
            }
            $localityDemand[$key]['total']++;
            if ($ev->result_count === 0) {
                $localityDemand[$key]['empty']++;
            }
        }

        $demandRows = 0;
        foreach ($localityDemand as $data) {
            $regionModel = SearchRegion::where('name_en', $data['region_name'])->first();
            $unservedRate = round(($data['empty'] / $data['total']) * 100, 2);

            SearchLocalityDemand::updateOrCreate(
                [
                    'region_name' => $data['region_name'],
                    'entity_type' => $data['entity_type'],
                    'period_date' => $today,
                ],
                [
                    'region_id' => $regionModel?->id,
                    'total_searches' => $data['total'],
                    'empty_searches' => $data['empty'],
                    'unserved_rate' => $unservedRate,
                ]
            );
            $demandRows++;
        }

        // 9. Populate search_terms kind='popular_query' (Task T-SR06-04)
        $popularTermsSeeded = 0;
        foreach (array_slice($queryCounts, 0, 30, true) as $qText => $count) {
            $termKey = mb_strtolower(trim($qText), 'UTF-8');
            SearchTerm::updateOrCreate(
                [
                    'term' => $qText,
                    'kind' => 'popular_query',
                    'tenant_id' => null,
                ],
                [
                    'normalized' => $termKey,
                    'weight' => 5.0 + log(1.0 + $count),
                    'locale' => 'en',
                ]
            );
            $popularTermsSeeded++;
        }

        return [
            'patterns_created' => $patternsCreated,
            'demand_rows' => $demandRows,
            'popular_terms_seeded' => $popularTermsSeeded,
        ];
    }
}
