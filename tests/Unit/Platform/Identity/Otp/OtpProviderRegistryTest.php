<?php

namespace Tests\Unit\Platform\Identity\Otp;

use App\Platform\Identity\Otp\EmailOtpDelivery;
use App\Platform\Identity\Otp\NullEmailOtpDelivery;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verification Fabric S46: the provider registry fails closed (unknown
 * names throw — there is no fallback channel), and the default email
 * delivery is a deliberate no-op (S46 ships the port, S48 binds the
 * real channel).
 */
final class OtpProviderRegistryTest extends TestCase
{
    public function test_registered_provider_is_resolvable_by_name(): void
    {
        $provider = new class () implements PhoneVerificationProvider {
            public function name(): string
            {
                return 'vendor-otp';
            }

            public function send(OtpSendContext $context): OtpSendResult
            {
                return new OtpSendResult(providerRef: 'ref');
            }

            public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
            {
                return OtpVerifyOutcome::Mismatch;
            }
        };
        $registry = new OtpProviderRegistry(['vendor-otp' => $provider]);

        $this->assertTrue($registry->has('vendor-otp'));
        $this->assertSame($provider, $registry->get('vendor-otp'));
    }

    public function test_unknown_provider_throws_fail_closed(): void
    {
        $registry = new OtpProviderRegistry();

        $this->assertFalse($registry->has('firebase-customer'));

        $this->expectException(OtpProviderUnavailableException::class);
        $registry->get('firebase-customer');
    }

    public function test_null_email_delivery_is_a_silent_no_op(): void
    {
        $delivery = new NullEmailOtpDelivery();

        $this->assertSame('null', $delivery->name());

        // Must not throw, must not log — codes only surface via the
        // OTP_LOG_CODES drill seam.
        $delivery->deliver(new EmailOtpDelivery(
            to: 'user@example.com',
            code: '123456',
            challengeId: 'chl-1',
            purpose: 'signup',
            expiresAt: '2026-10-12 12:00:00',
        ));

        $this->addToAssertionCount(1);
    }
}
