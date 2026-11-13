<?php

namespace App\Modules\SeoContent\Support;

use App\Domain\Calendar\Support\PlaceResolver;
use Carbon\CarbonImmutable;

/**
 * Anti-doorway publish eligibility gate.
 * Closes P-13: Prevents unbounded doorway scaling by enforcing strict indexability rules.
 */
final class SeoPublishEligibilityGate
{
    /**
     * Determine robots meta directive for a panchang page.
     *
     * @param string $placeSlug
     * @param CarbonImmutable $date
     * @return array{
     *     is_indexable: bool,
     *     robots_directive: string,
     *     reasons: list<string>
     * }
     */
    public static function evaluatePanchangDay(string $placeSlug, CarbonImmutable $date): array
    {
        $reasons = [];

        // 1. Place validation
        $place = PlaceResolver::resolve($placeSlug);
        if ($place === null || ! $place['is_verified']) {
            return [
                'is_indexable' => false,
                'robots_directive' => 'noindex,follow',
                'reasons' => ["Place '{$placeSlug}' is unverified or unresolved."],
            ];
        }

        // 2. Horizon window check
        $now = CarbonImmutable::now('UTC')->startOfDay();
        $targetDate = $date->startOfDay();
        $daysDiff = $targetDate->diffInDays($now, false); // negative if future

        $maxFuture = (int) config('seo.cardinality_limits.max_future_days_indexable', 30);
        $maxPast = (int) config('seo.cardinality_limits.max_past_days_indexable', 7);

        // $daysDiff > 0 means date is in the past ($now > $targetDate)
        // $daysDiff < 0 means date is in the future ($now < $targetDate)
        if ($daysDiff < -$maxFuture) {
            $reasons[] = "Date is {$daysDiff} days in future (exceeds {$maxFuture} day indexation limit).";
        } elseif ($daysDiff > $maxPast) {
            $reasons[] = "Date is {$daysDiff} days in past (exceeds {$maxPast} day indexation limit).";
        }

        $isIndexable = empty($reasons);
        $directive = $isIndexable ? 'index,follow' : 'noindex,follow';

        return [
            'is_indexable' => $isIndexable,
            'robots_directive' => $directive,
            'reasons' => $reasons,
        ];
    }
}

