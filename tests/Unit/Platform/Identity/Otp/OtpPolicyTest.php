<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\OtpAudienceRouter;
use App\Platform\Identity\Otp\OtpPurposePolicy;
use App\Platform\Identity\Otp\OtpPurposeUnknownException;
use App\Platform\Identity\Otp\OtpRiskTier;
use Tests\TestCase;

/**
 * S48 policy table contract: every customer purpose maps to the
 * customer audience with the decided tier (T0 re-proof, T1 signup,
 * T2 contact swaps with 2-of-2 quorum), unknown purposes fail closed,
 * captcha demand is off by default, and the audience router follows
 * provider selection (local default, <selected>-<audience> remote).
 */
final class OtpPolicyTest extends TestCase
{
    public function test_every_customer_purpose_has_the_decided_tier(): void
    {
        $expected = [
            'login' => OtpRiskTier::T0,
            'verify_phone' => OtpRiskTier::T0,
            'verify_email' => OtpRiskTier::T0,
            'signup' => OtpRiskTier::T1,
            'change_phone' => OtpRiskTier::T2,
            'change_email' => OtpRiskTier::T2,
        ];

        foreach ($expected as $purpose => $tier) {
            $policy = OtpPurposePolicy::resolve($purpose);
            $this->assertSame('customer', $policy->audience, $purpose);
            $this->assertSame($tier, $policy->tier, $purpose);
            $this->assertSame($purpose, $policy->purpose);
        }
    }

    public function test_risk_tiers_declare_their_quorum_requirements(): void
    {
        $this->assertSame(1, OtpRiskTier::T0->quorumChannels());
        $this->assertSame(1, OtpRiskTier::T1->quorumChannels());
        $this->assertSame(2, OtpRiskTier::T2->quorumChannels());
        $this->assertSame(2, OtpRiskTier::T3->quorumChannels());
    }

    public function test_unknown_purpose_fails_closed(): void
    {
        $this->expectException(OtpPurposeUnknownException::class);
        OtpPurposePolicy::resolve('transfer_ownership');
    }

    public function test_captcha_is_off_by_default_and_follows_config(): void
    {
        $this->assertFalse(OtpPurposePolicy::resolve('login')->captchaRequired());
        $this->assertFalse(OtpPurposePolicy::resolve('signup')->captchaRequired());

        config()->set('otp-policy.captcha.customer', 'true');
        $this->assertTrue(OtpPurposePolicy::resolve('login')->captchaRequired());
    }

    public function test_audience_router_follows_provider_selection(): void
    {
        // Default config: every audience stays local.
        $this->assertSame('local', OtpAudienceRouter::providerName('customer'));
        $this->assertSame('local', OtpAudienceRouter::providerName('vendor'));

        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        $this->assertSame('firebase-customer', OtpAudienceRouter::providerName('customer'));
        $this->assertSame('local', OtpAudienceRouter::providerName('vendor'));

        $this->assertSame('firebase-vendor', OtpAudienceRouter::nameFor('vendor', 'firebase'));
        $this->assertSame('local', OtpAudienceRouter::nameFor('platform', 'local'));
        $this->assertSame('local', OtpAudienceRouter::nameFor('customer', ''));
    }
}
