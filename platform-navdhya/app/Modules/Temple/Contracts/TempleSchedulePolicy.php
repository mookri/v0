<?php

namespace App\Modules\Temple\Contracts;

use Carbon\CarbonImmutable;

/**
 * Temple schedule, aarti block, and capacity policy port (C4).
 */
interface TempleSchedulePolicy
{
    /**
     * Evaluates temple ritual feasibility, sanctum bounds, aarti collisions, and effective capacity.
     */
    public function evaluateSlot(
        string $tenantId,
        string $templeId,
        string $slotType,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $requestedUnits = 1
    ): TempleScheduleVerdict;
}

