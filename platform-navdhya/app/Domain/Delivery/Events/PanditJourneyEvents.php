<?php

namespace App\Domain\Delivery\Events;

final class PanditJourneyEvents
{
    public const PANDIT_ASSIGNED = 'pandit.assigned';
    public const PANDIT_DEPARTED = 'pandit.departed';
    public const PANDIT_ARRIVED = 'pandit.arrived';
    public const PANDIT_DELAYED = 'pandit.delayed';
    public const PANDIT_NO_SHOW = 'pandit.no_show';

    public static function isMaterialDelay(int $delayMinutes): bool
    {
        return $delayMinutes >= 15;
    }
}

