<?php

namespace App\Modules\Recommendations\Services;

class PrefixSpanMiner
{
    private const MAX_DEPTH = 5;

    /**
     * Mine sequential patterns from sequences.
     *
     * @param list<list<string>> $sequences
     * @param float $minSupport Fraction in [0.0, 1.0]
     * @param int $maxPatternLength Bound on maximum pattern length (max 5)
     * @return list<array{pattern: list<string>, count: int, support: float}>
     */
    public function mine(
        array $sequences,
        float $minSupport = 0.05,
        int $maxPatternLength = self::MAX_DEPTH
    ): array {
        $totalSequences = count($sequences);
        if ($totalSequences === 0) {
            return [];
        }

        $minCount = max(1, (int) ceil($minSupport * $totalSequences));
        $maxLen = min($maxPatternLength, self::MAX_DEPTH);

        $results = [];
        $this->prefixSpan([], $sequences, $minCount, $totalSequences, $maxLen, $results);

        // Sort by support descending, then pattern length descending
        usort($results, function ($a, $b) {
            if ($b['count'] !== $a['count']) {
                return $b['count'] <=> $a['count'];
            }
            return count($b['pattern']) <=> count($a['pattern']);
        });

        return $results;
    }

    /**
     * @param list<string> $prefix
     * @param list<list<string>> $projectedDb
     * @param int $minCount
     * @param int $totalSequences
     * @param int $maxLen
     * @param list<array{pattern: list<string>, count: int, support: float}> $results
     */
    private function prefixSpan(
        array $prefix,
        array $projectedDb,
        int $minCount,
        int $totalSequences,
        int $maxLen,
        array &$results
    ): void {
        if (count($prefix) >= $maxLen) {
            return;
        }

        // Count item frequencies in projected database (at most once per sequence)
        $itemCounts = [];
        foreach ($projectedDb as $seq) {
            $seenInSeq = [];
            foreach ($seq as $item) {
                if (! isset($seenInSeq[$item])) {
                    $seenInSeq[$item] = true;
                    $itemCounts[$item] = ($itemCounts[$item] ?? 0) + 1;
                }
            }
        }

        foreach ($itemCounts as $item => $count) {
            if ($count < $minCount) {
                continue;
            }

            $newPattern = array_merge($prefix, [$item]);
            $results[] = [
                'pattern' => $newPattern,
                'count' => $count,
                'support' => round($count / $totalSequences, 4),
            ];

            // Build projected database for item
            $nextProjectedDb = [];
            foreach ($projectedDb as $seq) {
                $pos = array_search($item, $seq, true);
                if ($pos !== false && $pos + 1 < count($seq)) {
                    $nextProjectedDb[] = array_slice($seq, $pos + 1);
                }
            }

            if (! empty($nextProjectedDb)) {
                $this->prefixSpan($newPattern, $nextProjectedDb, $minCount, $totalSequences, $maxLen, $results);
            }
        }
    }
}

