<?php

namespace App\Modules\Pandit\Contracts;

use Carbon\CarbonImmutable;

/**
 * Pandit schedule evaluation policy (blackouts, travel buffers, availability rules).
 */
interface PanditSchedulePolicy
{
    /**
     * Determine whether the given pandit is available for a ritual during [$start, $end].
     */
    public function isAvailableAt(
        string $tenantId,
        string $panditId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): ScheduleVerdict;

    /**
     * Compute effective booking window padded by the pandit's travel buffers.
     */
    public function getEffectiveWindow(
        string $tenantId,
        string $panditId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): EffectiveWindow;
}
