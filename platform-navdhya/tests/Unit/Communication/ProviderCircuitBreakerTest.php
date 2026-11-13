<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Registry\ProviderCapabilityRegistry;
use App\Domain\Delivery\Reliability\ProviderCircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM03-05:
 * Proves that ProviderCircuitBreaker trips upon reaching the failure threshold (5),
 * and validates that ProviderCapabilityRegistry enforces channel-template compatibility.
 */
final class ProviderCircuitBreakerTest extends TenantIntegrationTestCase
{
    public function test_circuit_breaker_trips_after_five_failures(): void
    {
        Cache::flush();
        $cb = new ProviderCircuitBreaker(threshold: 5, cooldownSeconds: 300);

        $provider = 'msg91';

        $this->assertFalse($cb->isOpen($provider));

        // Record 4 failures — still closed
        for ($i = 0; $i < 4; $i++) {
            $cb->recordFailure($provider);
            $this->assertFalse($cb->isOpen($provider));
        }

        // 5th failure trips it open
        $cb->recordFailure($provider);
        $this->assertTrue($cb->isOpen($provider));

        // Recording success resets it back to closed
        $cb->recordSuccess($provider);
        $this->assertFalse($cb->isOpen($provider));
    }

    public function test_capability_registry_validates_channel_features(): void
    {
        $registry = new ProviderCapabilityRegistry();

        // WhatsApp supports interactive cards and buttons
        $this->assertTrue($registry->supportsFeature('WHATSAPP', 'interactive_cards'));
        $this->assertTrue($registry->supportsFeature('WHATSAPP', 'buttons'));

        // SMS does NOT support interactive cards or pdf attachments
        $this->assertFalse($registry->supportsFeature('SMS', 'interactive_cards'));
        $this->assertFalse($registry->supportsFeature('SMS', 'pdf_attachments'));

        // Template compatibility
        $this->assertTrue($registry->validateTemplateCompatibility('WHATSAPP', 'interactive_card'));
        $this->assertFalse($registry->validateTemplateCompatibility('SMS', 'interactive_card'));
    }
}

