<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Policies\WhatsAppPolicyService;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM04-03:
 * Proves WhatsApp opt-in consent requirements, 24-hour service-window boundaries,
 * and user-block handling with zero SMS marketing substitution.
 */
final class WhatsAppOptInPolicyTest extends TenantIntegrationTestCase
{
    public function test_proactive_marketing_requires_consent_while_transactional_is_exempt(): void
    {
        $policy = new WhatsAppPolicyService();
        $userId = 8001;

        // 1. Transactional is permitted even with hasOptInConsent = false
        $txVerdict = $policy->validateProactiveSend($userId, 'TRANSACTIONAL', false);
        $this->assertTrue($txVerdict['permitted']);
        $this->assertSame('transactional_exemption', $txVerdict['reason']);

        // 2. Marketing without consent is rejected
        $mktVerdict = $policy->validateProactiveSend($userId, 'MARKETING', false);
        $this->assertFalse($mktVerdict['permitted']);
        $this->assertSame('CONSENT_MISSING', $mktVerdict['reason']);

        // 3. Marketing with consent is permitted
        $mktGranted = $policy->validateProactiveSend($userId, 'MARKETING', true);
        $this->assertTrue($mktGranted['permitted']);
        $this->assertSame('consent_granted', $mktGranted['reason']);
    }

    public function test_service_window_boundary_enforcement(): void
    {
        $policy = new WhatsAppPolicyService();
        $now = CarbonImmutable::parse('2026-11-15 12:00:00');

        // Within 24 hours (e.g. 5 hours ago)
        $recentInbound = $now->subHours(5);
        $this->assertTrue($policy->isInside24HourWindow($recentInbound, $now));

        // Outside 24 hours (e.g. 25 hours ago)
        $staleInbound = $now->subHours(25);
        $this->assertFalse($policy->isInside24HourWindow($staleInbound, $now));

        // Null inbound
        $this->assertFalse($policy->isInside24HourWindow(null, $now));
    }

    public function test_user_whatsapp_block_forbids_sms_marketing_substitution(): void
    {
        $policy = new WhatsAppPolicyService();
        $userId = 8002;

        $policy->recordUserBlock($userId);
        $this->assertTrue($policy->isUserBlocked($userId));

        // Proactive WhatsApp attempt fails with USER_BLOCKED_WHATSAPP
        $verdict = $policy->validateProactiveSend($userId, 'MARKETING', true);
        $this->assertFalse($verdict['permitted']);
        $this->assertSame('USER_BLOCKED_WHATSAPP', $verdict['reason']);

        // Anti-spam rule: CANNOT substitute SMS marketing
        $this->assertFalse($policy->canSubstituteMarketingSms($userId));
    }
}

