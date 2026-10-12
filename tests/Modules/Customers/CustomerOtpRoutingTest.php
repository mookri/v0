<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\OtpCaptchaRequiredException;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpPurposeUnknownException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * S48 routing contract: issue() picks the provider from the purpose
 * policy table through the audience registry — local by default
 * (behavior-identical), firebase-customer once the audience selects
 * firebase and the registry holds it, and NEVER a silent downgrade:
 * an unregistered remote name or a missing required attestation fails
 * closed before any provider call.
 */
final class CustomerOtpRoutingTest extends TenantIntegrationTestCase
{
    private function service(?OtpProviderRegistry $registry = null): CustomerOtpService
    {
        return new CustomerOtpService(new SeededOtpCodes(), $registry);
    }

    /**
     * @return array{0: OtpProviderRegistry, 1: object&PhoneVerificationProvider}
     */
    private function firebaseSpy(): array
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

        return [new OtpProviderRegistry(['firebase-customer' => $spy]), $spy];
    }

    public function test_default_config_stays_local(): void
    {
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = $this->service()->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+911234567890',
            CarbonImmutable::now(),
        );

        $fresh = CustomerOtpChallenge::query()->find($challenge->getKey());
        $this->assertSame('local', $fresh->provider);
        $this->assertNotNull($fresh->code_hash);
        $this->assertNull($fresh->provider_ref);
    }

    public function test_remote_audience_routes_the_send_to_the_firebase_provider(): void
    {
        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        [$registry, $spy] = $this->firebaseSpy();
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = $this->service($registry)->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+911234567890',
            CarbonImmutable::now(),
        );

        $fresh = CustomerOtpChallenge::query()->find($challenge->getKey());
        $this->assertSame('firebase-customer', $fresh->provider);
        $this->assertSame('sent', $fresh->status);
        $this->assertNotNull($fresh->provider_ref);
        $this->assertSame('ref-'.$fresh->getKey(), $fresh->provider_ref);
        // Remote sends expose no code — the local hash path never runs.
        $this->assertNull($fresh->code_hash);
        // The send context carries the policy's audience for the
        // provider (project binding, quota attribution).
        $this->assertSame('customer', $spy->lastContext->audience);
        $this->assertSame(CustomerOtpChallenge::PURPOSE_LOGIN, $spy->lastContext->purpose);
    }

    public function test_required_attestation_missing_fails_closed_before_any_send(): void
    {
        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        config()->set('otp-policy.captcha.customer', true);
        [$registry, $spy] = $this->firebaseSpy();
        $tid = (string) $this->makeTenant()->getKey();

        try {
            $this->service($registry)->issue(
                $tid,
                CustomerOtpChallenge::PURPOSE_LOGIN,
                '+911234567890',
                CarbonImmutable::now(),
            );
            $this->fail('expected OtpCaptchaRequiredException');
        } catch (OtpCaptchaRequiredException) {
        }

        // Fail closed with no side effects: no provider call, no row.
        $this->assertNull($spy->lastContext);
        $this->assertSame(0, CustomerOtpChallenge::query()->where('tenant_id', $tid)->count());
    }

    public function test_attestation_flows_into_the_provider_send(): void
    {
        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        config()->set('otp-policy.captcha.customer', true);
        [$registry, $spy] = $this->firebaseSpy();
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = $this->service($registry)->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+911234567890',
            CarbonImmutable::now(),
            attestation: 'recaptcha-token',
        );

        $this->assertSame('recaptcha-token', $spy->lastContext->attestation);
        $this->assertSame('firebase-customer', CustomerOtpChallenge::query()->find($challenge->getKey())->provider);
    }

    public function test_captcha_flag_never_gates_the_local_provider(): void
    {
        // Behavior-identical guard: the attestation requirement is a
        // remote-provider rule; local delivery must keep working with
        // the flag on and no token anywhere.
        config()->set('otp-policy.captcha.customer', true);
        $tid = (string) $this->makeTenant()->getKey();

        $challenge = $this->service()->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+911234567890',
            CarbonImmutable::now(),
        );

        $this->assertSame('local', CustomerOtpChallenge::query()->find($challenge->getKey())->provider);
    }

    public function test_unregistered_remote_provider_never_downgrades_to_local(): void
    {
        config()->set('otp-providers.audiences.customer.provider', 'firebase');
        $tid = (string) $this->makeTenant()->getKey();

        try {
            $this->service()->issue(
                $tid,
                CustomerOtpChallenge::PURPOSE_LOGIN,
                '+911234567890',
                CarbonImmutable::now(),
            );
            $this->fail('expected OtpProviderUnavailableException');
        } catch (OtpProviderUnavailableException) {
        }

        $this->assertSame(0, CustomerOtpChallenge::query()->where('tenant_id', $tid)->count());
    }

    public function test_unknown_purpose_fails_closed(): void
    {
        $tid = (string) $this->makeTenant()->getKey();

        try {
            $this->service()->issue(
                $tid,
                'transfer_ownership',
                '+911234567890',
                CarbonImmutable::now(),
            );
            $this->fail('expected OtpPurposeUnknownException');
        } catch (OtpPurposeUnknownException) {
        }

        $this->assertSame(0, CustomerOtpChallenge::query()->where('tenant_id', $tid)->count());
    }
}
