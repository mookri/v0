<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Authoritative Server Duration Resolution Port (F2).
 * Evaluates duration rules for a given service SKU.
 */
interface ResolvesServiceDurations
{
    public function resolveDuration(
        string $sku,
        int $participants = 1,
        ?string $packageTier = null,
        ?string $tradition = null,
        ?\DateTimeInterface $at = null
    ): DurationResolutionResult;
}
