<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use Tests\TenantIntegrationTestCase;

/**
 * S48 HTTP pass-through: the auth surfaces forward captcha_token to
 * the provider as the send attestation, demand it (422) when the
 * audience's policy requires it, surface provider outages as typed
 * 503s, and expose the public captcha config clients render from.
 */
final class CustomerCaptchaPassThroughTest extends TenantIntegrationTestCase
{
    /**
     * @return object&PhoneVerificationProvider
     */
    private function firebaseSpy(): PhoneVerificationProvider
    {
        $spy = new class () implements PhoneVerificationProvider {
            public ?OtpSendContext $lastContext = null;

            public function name(): string
            {
                return 'firebase-customer';
            }

            public function send(OtpSendContext $context): OtpSendResult
            {
                $this->lastContext = $context;

                return new OtpSendResult(providerRef: 'ref-'.$context->challengeId);
            }

            public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
            {
                return OtpVerifyOutcome::Mismatch;
            }
        };

        $this->app->singleton(
            CustomerOtpService::class,
            static fn (): CustomerOtpService => new CustomerOtpService(
                new SeededOtpCodes(),
                new OtpProviderRegistry(['firebase-customer' => $spy]),
            )
        );
        config()->set('otp-providers.audiences.customer.provider', 'firebase');

        return $spy;
    }

    public function test_captcha_config_defaults_to_no_key_and_not_required(): void
    {
        // Hermetic: a local .env may carry the live
        // FIREBASE_CUSTOMER_RECAPTCHA_KEY / OTP_CAPTCHA_REQUIRED_*
        // (S49 creds) - config() reads both, so the "nothing
        // configured" default path must be pinned here.
        config()->set('otp-providers.audiences.customer.firebase.recaptcha_key', null);
        config()->set('otp-policy.captcha.customer', null);

        $this->getJson('/api/v1/customers/auth/captcha')
            ->assertOk()
            ->assertJsonPath('data.object', 'captcha_config')
            ->assertJsonPath('data.provider', 'recaptcha')
            ->assertJsonPath('data.site_key', null)
            ->assertJsonPath('data.required', false);
    }

    public function test_captcha_config_reflects_audience_config(): void
    {
        config()->set('otp-providers.audiences.customer.firebase.recaptcha_key', 'site-key-123');
        config()->set('otp-policy.captcha.customer', true);

        $this->getJson('/api/v1/customers/auth/captcha')
            ->assertOk()
            ->assertJsonPath('data.site_key', 'site-key-123')
            ->assertJsonPath('data.required', true);
    }

    public function test_otp_request_forwards_captcha_token_to_the_provider(): void
    {
        $spy = $this->firebaseSpy();
        $tid = (string) $this->makeTenant()->getKey();

        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid,
            'purpose' => 'signup',
            'identity' => '+911234567890',
            'captcha_token' => 'recaptcha-token',
        ])->assertStatus(202);

        $this->assertSame('recaptcha-token', $spy->lastContext->attestation);
    }

    public function test_signup_422s_when_policy_demands_captcha_and_none_arrives(): void
    {
        $this->firebaseSpy();
        config()->set('otp-policy.captcha.customer', true);
        $tid = (string) $this->makeTenant()->getKey();

        $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid,
            'phone' => '+911234567890',
            'name' => 'Ravi',
        ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_provider_outage_surfaces_as_a_typed_503(): void
    {
        $down = new class () implements PhoneVerificationProvider {
            public function name(): string
            {
                return 'firebase-customer';
            }

            public function send(OtpSendContext $context): OtpSendResult
            {
                throw new OtpProviderUnavailableException('firebase otp provider [firebase-customer] unavailable: http 429');
            }

            public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
            {
                return OtpVerifyOutcome::Unavailable;
            }
        };
        $this->app->singleton(
            CustomerOtpService::class,
            static fn (): CustomerOtpService => new CustomerOtpService(
                new SeededOtpCodes(),
                new OtpProviderRegistry(['firebase-customer' => $down]),
            )
        );
        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        $tid = (string) $this->makeTenant()->getKey();

        $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid,
            'phone' => '+911234567890',
            'name' => 'Ravi',
        ])->assertStatus(503)->assertJsonPath('error.code', 'dependency_unavailable');
    }
}
