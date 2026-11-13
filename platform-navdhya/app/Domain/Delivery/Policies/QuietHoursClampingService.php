<?php

namespace App\Domain\Delivery\Policies;

use Carbon\CarbonImmutable;
use DateTimeZone;

/**
 * Recipient timezone resolution and quiet-hours clamping service (E-CM-08 / Task T-CM08-03).
 * Non-urgent messages during quiet hours (22:00–07:00 recipient local time) are deferred to 07:00.
 */
final class QuietHoursClampingService
{
    public function clampDispatchTime(
        CarbonImmutable $scheduledAt,
        string $timezone = 'Asia/Kolkata',
        bool $isUrgentOrTransactional = false
    ): CarbonImmutable {
        if ($isUrgentOrTransactional) {
            return $scheduledAt;
        }

        try {
            $tz = new DateTimeZone($timezone);
        } catch (\Throwable) {
            $tz = new DateTimeZone('Asia/Kolkata');
        }

        $localTime = $scheduledAt->setTimezone($tz);
        $hour = (int) $localTime->format('G');

        // Quiet hours: 22:00 (10 PM) to 07:00 (7 AM)
        if ($hour >= 22 || $hour < 7) {
            $deferredLocal = $hour >= 22
                ? $localTime->addDay()->setTime(7, 0, 0)
                : $localTime->setTime(7, 0, 0);

            return $deferredLocal->setTimezone(new DateTimeZone('UTC'));
        }

        return $scheduledAt;
    }
}

