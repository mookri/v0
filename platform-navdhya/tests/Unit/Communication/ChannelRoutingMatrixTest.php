<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Routing\ChannelRoutingService;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM03-03:
 * Proves that the versioned channel routing matrix enforces quiet hours rules,
 * priorities, and safe fallbacks (including the rule that WhatsApp blocks never substitute SMS for marketing).
 */
final class ChannelRoutingMatrixTest extends TenantIntegrationTestCase
{
    public function test_transactional_bypasses_quiet_hours_and_picks_first_available_priority(): void
    {
        $router = new ChannelRoutingService();

        // Transactional message during quiet hours still routes to WhatsApp if available
        $selected = $router->selectChannel(
            category: 'transactional',
            availableChannels: ['EMAIL', 'WHATSAPP'],
            quietHoursActive: true
        );

        $this->assertSame('WHATSAPP', $selected);
    }

    public function test_marketing_is_suppressed_during_quiet_hours_and_has_no_sms_fallback(): void
    {
        $router = new ChannelRoutingService();

        // Marketing suppressed during quiet hours
        $selected = $router->selectChannel(
            category: 'marketing',
            availableChannels: ['EMAIL', 'WHATSAPP'],
            quietHoursActive: true
        );
        $this->assertNull($selected);

        // Outside quiet hours, marketing selects email
        $selectedOutside = $router->selectChannel(
            category: 'marketing',
            availableChannels: ['EMAIL', 'WHATSAPP', 'SMS'],
            quietHoursActive: false
        );
        $this->assertSame('EMAIL', $selectedOutside);

        // If WhatsApp fails, marketing CANNOT fall back to SMS (Scenario G / Anti-spam rule)
        $fallback = $router->fallbackChannel('marketing', 'WHATSAPP');
        $this->assertNull($fallback);
    }

    public function test_transactional_fallback_sequence(): void
    {
        $router = new ChannelRoutingService();

        // WHATSAPP -> EMAIL -> SMS
        $this->assertSame('EMAIL', $router->fallbackChannel('transactional', 'WHATSAPP'));
        $this->assertSame('SMS', $router->fallbackChannel('transactional', 'EMAIL'));
        $this->assertNull($router->fallbackChannel('transactional', 'SMS'));
    }
}

