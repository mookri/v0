<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Otp\EmailOtpDelivery;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
use App\Platform\Identity\StepUpOtpService;
use App\Platform\Identity\StepUpRateLimitedException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Tests\TenantIntegrationTestCase;

/**
 * Step-up OTP issuance (I2): the challenge is stored, the code reaches the
 * actor through the email delivery port when the caller names an address,
 * the mailed code is the one that verifies, and the per-hour cap is a typed
 * refusal rather than a bare RuntimeException — a 500 for an expected
 * back-off teaches clients to treat rate limiting as a crash.
 */
final class StepUpOtpServiceTest extends TenantIntegrationTestCase
{
    private function deliverySpy(): EmailOtpDeliveryProvider
    {
        return new class () implements EmailOtpDeliveryProvider {
            /** @var list<EmailOtpDelivery> */
            public array $sent = [];

            public function name(): string
            {
                return 'spy';
            }

            public function deliver(EmailOtpDelivery $delivery): void
            {
                $this->sent[] = $delivery;
            }
        };
    }

    private function issue(
        EmailOtpDeliveryProvider $provider,
        string $tid,
        string $identifier,
        ?string $email,
        ?CarbonImmutable $now = null,
    ): array {
        return (new StepUpOtpService($provider))->issue(
            $tid,
            'mfa_enroll_stepup',
            $identifier,
            $now ?? CarbonImmutable::now(),
            ['external_id' => $identifier],
            $email,
        );
    }

    public function test_issued_code_is_delivered_to_the_actor_email(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $spy = $this->deliverySpy();

        $challenge = $this->issue($spy, $tid, 'ext_1', 'operator@example.test');

        $this->assertCount(1, $spy->sent);
        $delivery = $spy->sent[0];
        $this->assertSame('operator@example.test', $delivery->to);
        $this->assertSame($challenge['challenge_id'], $delivery->challengeId);
        $this->assertSame('mfa_enroll_stepup', $delivery->purpose);
        $this->assertSame($challenge['expires_at'], $delivery->expiresAt);
        $this->assertSame(
            StepUpOtpService::getTestCode($challenge['challenge_id']),
            $delivery->code,
            'the code in the mail must be the code the challenge accepts',
        );
    }

    public function test_the_delivered_code_redeems_the_challenge(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $spy = $this->deliverySpy();

        $challenge = $this->issue($spy, $tid, 'ext_2', 'operator@example.test');
        $verified = (new StepUpOtpService($spy))->verify(
            $tid,
            'mfa_enroll_stepup',
            $challenge['challenge_id'],
            $spy->sent[0]->code,
            CarbonImmutable::now(),
        );

        $this->assertNotNull($verified, 'a code the actor can read must be a code that works');
        $this->assertSame(['external_id' => 'ext_2'], $verified['payload']);
    }

    public function test_no_email_means_no_delivery_attempt(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $spy = $this->deliverySpy();

        $challenge = $this->issue($spy, $tid, 'ext_3', null);

        $this->assertSame([], $spy->sent);
        $this->assertNotSame('', $challenge['challenge_id']);
    }

    public function test_default_provider_stays_silent_and_the_challenge_still_works(): void
    {
        // Constructing without a provider binds the null delivery channel
        // (OTP_EMAIL_DELIVERY stays off): enrollment is unreachable, but
        // nothing sends and nothing breaks.
        Mail::fake();
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = (new StepUpOtpService())->issue(
            $tid,
            'mfa_enroll_stepup',
            'ext_4',
            CarbonImmutable::now(),
            [],
            'operator@example.test',
        );

        Mail::assertNothingSent();
        $this->assertNotNull(StepUpOtpService::getTestCode($challenge['challenge_id']));
    }

    public function test_hourly_cap_raises_the_typed_step_up_refusal(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $spy = $this->deliverySpy();
        $now = CarbonImmutable::now();

        for ($attempt = 1; $attempt <= StepUpOtpService::MAX_PER_HOUR; $attempt++) {
            $this->issue($spy, $tid, 'ext_5', null, $now);
        }

        $this->expectException(StepUpRateLimitedException::class);
        $this->issue($spy, $tid, 'ext_5', null, $now);
    }
}
