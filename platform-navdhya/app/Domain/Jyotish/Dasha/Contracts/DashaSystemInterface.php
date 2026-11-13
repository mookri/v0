<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha\Contracts;

use App\Domain\Jyotish\Dasha\Models\DashaInterval;

interface DashaSystemInterface
{
    public function getSystemCode(): string;

    public function getTotalCycleYears(): float;

    /**
     * Compute Mahadasha sequence from Moon longitude & birth date.
     *
     * @param float $moonSiderealLongitude Sidereal longitude [0, 360)
     * @param \DateTimeImmutable $birthDateTime Exact birth UTC
     * @param int $maxLevels Hierarchy depth (1 = Maha, 2 = Antar, etc.)
     * @return list<DashaInterval>
     */
    public function calculate(
        float $moonSiderealLongitude,
        \DateTimeImmutable $birthDateTime,
        int $maxLevels = 2
    ): array;
}

