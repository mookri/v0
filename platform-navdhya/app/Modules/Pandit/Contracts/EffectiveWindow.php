<?php

namespace App\Modules\Pandit\Contracts;

use Carbon\CarbonImmutable;

/**
 * An evaluation window padded with travel buffers before and after.
 */
final class EffectiveWindow
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly int $bufferMinutes,
        public readonly CarbonImmutable $bufferedStart,
        public readonly CarbonImmutable $bufferedEnd,
    ) {
    }
}

