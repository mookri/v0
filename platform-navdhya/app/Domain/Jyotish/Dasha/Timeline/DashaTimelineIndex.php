<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha\Timeline;

use App\Domain\Jyotish\Dasha\Models\DashaInterval;

final readonly class DashaTimelineIndex
{
    /**
     * Binary search $O(\log N)$ to find the active period at target timestamp.
     *
     * @param list<DashaInterval> $intervals Sorted non-overlapping list
     * @param \DateTimeImmutable $targetTime
     * @return DashaInterval|null
     */
    public static function findActive(array $intervals, \DateTimeImmutable $targetTime): ?DashaInterval
    {
        $low = 0;
        $high = count($intervals) - 1;
        $targetTs = $targetTime->getTimestamp();

        while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
            $interval = $intervals[$mid];
            $startTs = $interval->startsAt->getTimestamp();
            $endTs = $interval->endsAt->getTimestamp();

            // Interval is [startsAt, endsAt)
            if ($targetTs >= $startTs && $targetTs < $endTs) {
                return $interval;
            }

            if ($targetTs < $startTs) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return null;
    }
}

