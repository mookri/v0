<?php

namespace App\Domain\Calendar\Muhurat;

use InvalidArgumentException;

/**
 * Robust Interval Algebra Engine (Sweep-line operations on half-open microsecond intervals [starts_at_us, ends_at_us)).
 */
final class IntervalAlgebra
{
    /**
     * Intersect two sets of intervals (A ∩ B).
     *
     * @param list<array{starts_at_us: int, ends_at_us: int}> $intervalsA
     * @param list<array{starts_at_us: int, ends_at_us: int}> $intervalsB
     * @return list<array{starts_at_us: int, ends_at_us: int}>
     */
    public static function intersect(array $intervalsA, array $intervalsB): array
    {
        $a = self::union($intervalsA);
        $b = self::union($intervalsB);

        $result = [];
        $i = 0;
        $j = 0;

        while ($i < count($a) && $j < count($b)) {
            $start = max($a[$i]['starts_at_us'], $b[$j]['starts_at_us']);
            $end = min($a[$i]['ends_at_us'], $b[$j]['ends_at_us']);

            if ($start < $end) {
                $result[] = [
                    'starts_at_us' => $start,
                    'ends_at_us' => $end,
                ];
            }

            if ($a[$i]['ends_at_us'] < $b[$j]['ends_at_us']) {
                $i++;
            } else {
                $j++;
            }
        }

        return $result;
    }

    /**
     * Subtract excluded intervals from base intervals (A \ B).
     * E.g. Auspicious window \ Rahu Kaal.
     *
     * @param list<array{starts_at_us: int, ends_at_us: int}> $base
     * @param list<array{starts_at_us: int, ends_at_us: int}> $excluded
     * @return list<array{starts_at_us: int, ends_at_us: int}>
     */
    public static function subtract(array $base, array $excluded): array
    {
        $baseClean = self::union($base);
        $exClean = self::union($excluded);

        $result = [];

        foreach ($baseClean as $b) {
            $curStart = $b['starts_at_us'];
            $curEnd = $b['ends_at_us'];

            foreach ($exClean as $e) {
                // If excluded interval is completely to the right of current segment, skip
                if ($e['starts_at_us'] >= $curEnd) {
                    continue;
                }

                // If excluded interval is completely to the left, skip
                if ($e['ends_at_us'] <= $curStart) {
                    continue;
                }

                // Excluded interval overlaps current segment
                if ($e['starts_at_us'] > $curStart) {
                    // Retain left subsegment
                    $result[] = [
                        'starts_at_us' => $curStart,
                        'ends_at_us' => min($e['starts_at_us'], $curEnd),
                    ];
                }

                // Advance start of remaining segment past excluded end
                $curStart = max($curStart, $e['ends_at_us']);
                if ($curStart >= $curEnd) {
                    break;
                }
            }

            if ($curStart < $curEnd) {
                $result[] = [
                    'starts_at_us' => $curStart,
                    'ends_at_us' => $curEnd,
                ];
            }
        }

        return self::union($result);
    }

    /**
     * Merge / simplify overlapping intervals into a sorted, disjoint list.
     *
     * @param list<array{starts_at_us: int, ends_at_us: int}> $intervals
     * @return list<array{starts_at_us: int, ends_at_us: int}>
     */
    public static function union(array $intervals): array
    {
        // Filter out zero-length or inverted intervals
        $valid = [];
        foreach ($intervals as $iv) {
            if ($iv['starts_at_us'] < $iv['ends_at_us']) {
                $valid[] = $iv;
            }
        }

        if (empty($valid)) {
            return [];
        }

        // Sort by starts_at_us ascending
        usort($valid, fn ($x, $y) => $x['starts_at_us'] <=> $y['starts_at_us']);

        $merged = [];
        $current = $valid[0];

        for ($i = 1; $i < count($valid); $i++) {
            $next = $valid[$i];

            if ($next['starts_at_us'] <= $current['ends_at_us']) {
                // Overlapping or abutting: merge
                $current['ends_at_us'] = max($current['ends_at_us'], $next['ends_at_us']);
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }

        $merged[] = $current;
        return $merged;
    }
}

