<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Policies\QuietHoursClampingService;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM08-03:
 * Proves that non-urgent routine notifications during quiet hours (22:00–07:00 local time)
 * are clamped and deferred to 07:00 morning rather than dropped.
 */
final class QuietHoursClampingTest extends TenantIntegrationTestCase
{
    public function test_routine_reminder_at_night_is_deferred_to_morning(): void
    {
        $service = new QuietHoursClampingService();

        // 02:00 AM IST on 2026-11-15 (Quiet hours!)
        $scheduledAt = CarbonImmutable::parse('2026-11-15 02:00:00', 'Asia/Kolkata');

        $clamped = $service->clampDispatchTime(
            scheduledAt: $scheduledAt,
            timezone: 'Asia/Kolkata',
            isUrgentOrTransactional: false
        );

        // Clamped time converted to Asia/Kolkata should be 07:00:00 AM on the same day
        $clampedLocal = $clamped->setTimezone('Asia/Kolkata');
        $this->assertSame('2026-11-15 07:00:00', $clampedLocal->format('Y-m-d H:i:s'));
    }

    public function test_urgent_or_transactional_messages_are_not_deferred(): void
    {
        $service = new QuietHoursClampingService();

        // 02:30 AM IST (Quiet hours)
        $scheduledAt = CarbonImmutable::parse('2026-11-15 02:30:00', 'Asia/Kolkata');

        $clamped = $service->clampDispatchTime(
            scheduledAt: $scheduledAt,
            timezone: 'Asia/Kolkata',
            isUrgentOrTransactional: true // Urgent / OTP / Transactional
        );

        $this->assertSame($scheduledAt->timestamp, $clamped->timestamp);
    }
}

