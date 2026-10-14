<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Otp\Msg91WidgetProvider;
use App\Platform\Identity\Otp\OtpChallengeManager;
use App\Platform\Identity\Otp\OtpRateLimitedException;
use App\Platform\Identity\Otp\OtpVerifyStatus;
use Carbon\CarbonImmutable;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Step-up OTP issuance (I2): the challenge is stored and managed via
 * OtpChallengeManager under the 'mfa_enroll_stepup' purpose.
 * The MSG91 widget token verifies the challenge and rate limits hold.
 */
final class StepUpOtpServiceTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
    }

    private function manager(): OtpChallengeManager
    {
        return app(OtpChallengeManager::class);
    }

    public function test_issued_stepup_challenge_is_stored_under_purpose(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $email = 'operator@example.test';
        $now = CarbonImmutable::now();

        $challenge = $this->manager()->issue(
            $tid,
            'mfa_enroll_stepup',
            $email,
            $now,
            ['external_id' => 'ext_1'],
        );

        $this->assertSame($tid, $challenge->tenantId);
        $this->assertSame('mfa_enroll_stepup', $challenge->purpose);
        $this->assertSame($email, $challenge->identifier);
        $this->assertSame(Msg91WidgetProvider::NAME, $challenge->provider);
        $this->assertSame(['external_id' => 'ext_1'], $challenge->payload);
    }

    public function test_attested_msg91_token_verifies_stepup_challenge(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $email = 'operator@example.test';
        $now = CarbonImmutable::now();

        $challenge = $this->manager()->issue(
            $tid,
            'mfa_enroll_stepup',
            $email,
            $now,
            ['external_id' => 'ext_2'],
        );

        $token = $this->msg91TokenFor($email);

        $result = $this->manager()->verify(
            $tid,
            ['mfa_enroll_stepup'],
            $challenge->id,
            $token,
            $now->addSeconds(5),
        );

        $this->assertSame(OtpVerifyStatus::Match, $result->status);
        $this->assertSame(['external_id' => 'ext_2'], $result->challenge?->payload);
    }

    public function test_fresh_stepup_challenge_is_gated_by_delivery_policy(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $email = 'operator@example.test';
        $now = CarbonImmutable::now();

        $this->manager()->issue($tid, 'mfa_enroll_stepup', $email, $now);

        // Immediate second issue is rate-limited
        $this->expectException(OtpRateLimitedException::class);
        $this->manager()->issue($tid, 'mfa_enroll_stepup', $email, $now->addSeconds(10));
    }
}
