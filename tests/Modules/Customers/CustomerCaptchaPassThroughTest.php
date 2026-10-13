<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderRateLimitedException;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
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
        $this->useRemoteProvider($this->faultingProvider(
            send: new OtpProviderUnavailableException('firebase otp provider [firebase-customer] unavailable: http 429'),
        ));
        $tid = (string) $this->makeTenant()->getKey();

        // S50 observability: the typed 503 hides the provider's symbolic
        // error from the client, so the outage must also be report()ed
        // (the message carries only symbolic codes — no phone/token/code).
        $handler = Mockery::spy(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, $handler);

        $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid,
            'phone' => '+911234567890',
            'name' => 'Ravi',
        ])->assertStatus(503)->assertJsonPath('error.code', 'dependency_unavailable');

        $handler->shouldHaveReceived('report')
            ->with(Mockery::type(OtpProviderUnavailableException::class));
    }

    public function test_provider_back_off_surfaces_as_a_typed_429_not_a_503(): void
    {
        // Live 2026-10-13: a hammered number answers TOO_MANY_ATTEMPTS_TRY_LATER.
        // That is retryable, so it must not be reported as a dead channel —
        // and it still fails closed, with no challenge row and no downgrade.
        $this->useRemoteProvider($this->faultingProvider(
            send: new OtpProviderRateLimitedException(
                'firebase otp provider [firebase-customer] rate limited: http 400 TOO_MANY_ATTEMPTS_TRY_LATER',
            ),
        ));
        $tid = (string) $this->makeTenant()->getKey();

        $handler = Mockery::spy(ExceptionHandler::class);
        $this->app->instance(ExceptionHandler::class, $handler);

        $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid,
            'phone' => '+911234567890',
            'name' => 'Ravi',
        ])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');

        $handler->shouldHaveReceived('report')
            ->with(Mockery::type(OtpProviderUnavailableException::class));
    }

    public function test_verify_side_provider_fault_is_a_typed_503(): void
    {
        // The adapter now throws its typed fault from verify as well, so a
        // redemption outage keeps the dependency envelope instead of
        // falling through ErrorEnvelope to a bare 500 — and never collapses
        // into the uniform 401 a wrong code produces.
        $this->useRemoteProvider($this->faultingProvider(
            verify: new OtpProviderUnavailableException(
                'firebase otp provider [firebase-customer] unavailable: http 400 CAPTCHA_CHECK_FAILED',
            ),
        ));
        $tid = (string) $this->makeTenant()->getKey();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid,
            'phone' => '+911234567890',
            'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'code' => '123456',
        ])->assertStatus(503)->assertJsonPath('error.code', 'dependency_unavailable');

        // The rollback undoes the attempt bump, so the row is still there
        // and redeemable once the provider recovers.
        $this->assertDatabaseHas('customer_otp_challenges', [
            'id' => $challengeId,
            'provider' => 'firebase-customer',
        ]);
    }

    /**
     * Register a hand-written provider as the customer audience's remote
     * one (process config only — the row's own provenance drives verify).
     */
    private function useRemoteProvider(PhoneVerificationProvider $provider): void
    {
        $this->app->singleton(
            CustomerOtpService::class,
            static fn (): CustomerOtpService => new CustomerOtpService(
                new SeededOtpCodes(),
                new OtpProviderRegistry(['firebase-customer' => $provider]),
            )
        );
        config()->set('otp-providers.audiences.customer.provider', 'firebase');

        // These cases target the provider-fault mapping, not the
        // attestation policy (the 422 paths assert that separately), so
        // the guard is pinned off here — otherwise a captcha flag in the
        // developer's .env, which suites load, would 422 every send.
        config()->set('otp-policy.captcha.customer', false);
    }

    /**
     * Provider that fails on demand, so the adapter's typed exceptions and
     * the HTTP envelopes they map to are exercised without live calls.
     */
    private function faultingProvider(?\Throwable $send = null, ?\Throwable $verify = null): PhoneVerificationProvider
    {
        return new class ($send, $verify) implements PhoneVerificationProvider {
            public function __construct(
                private readonly ?\Throwable $sendFault,
                private readonly ?\Throwable $verifyFault,
            ) {
            }

            public function name(): string
            {
                return 'firebase-customer';
            }

            public function send(OtpSendContext $context): OtpSendResult
            {
                if ($this->sendFault !== null) {
                    throw $this->sendFault;
                }

                return new OtpSendResult(providerRef: 'opaque-session-info');
            }

            public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
            {
                if ($this->verifyFault !== null) {
                    throw $this->verifyFault;
                }

                return OtpVerifyOutcome::Mismatch;
            }
        };
    }
}
