<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use App\Platform\Identity\Otp\EmailOtpDelivery;
use App\Platform\Identity\Otp\EmailOtpDeliveryProvider;
use App\Platform\Identity\Otp\OtpProviderRegistry;
use App\Platform\Identity\Otp\OtpProviderUnavailableException;
use App\Platform\Identity\Otp\OtpSendContext;
use App\Platform\Identity\Otp\OtpSendResult;
use App\Platform\Identity\Otp\OtpVerifyOutcome;
use App\Platform\Identity\Otp\OtpVerifyRequest;
use App\Platform\Identity\Otp\PhoneVerificationProvider;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Verification Fabric S46 wiring: issue() records provenance on the row
 * (provider/status/nullable-hash columns), routes the email identifier
 * through the delivery port, and verify() dispatches on the row's own
 * `provider` column via the registry — Unavailable fails closed as
 * OtpProviderUnavailableException (never a uniform 401, never a
 * fallback provider).
 */
final class CustomerOtpPortTest extends TenantIntegrationTestCase
{
    private function service(
        ?OtpProviderRegistry $registry = null,
        ?EmailOtpDeliveryProvider $emailDelivery = null,
    ): CustomerOtpService {
        return new CustomerOtpService(new SeededOtpCodes(), $registry, $emailDelivery);
    }

    public function test_issue_stamps_local_provenance_on_the_row(): void
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
        $this->assertSame('sent', $fresh->status);
        $this->assertNull($fresh->provider_ref);
        $this->assertNotNull($fresh->code_hash);
        $this->assertSame(hash('sha256', '100001'), $fresh->code_hash);
        $this->assertNull($fresh->decision);
    }

    public function test_email_identifier_flows_through_the_delivery_port(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $spy = new class () implements EmailOtpDeliveryProvider {
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

        $service = $this->service(emailDelivery: $spy);

        $email = $service->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_SIGNUP,
            'user@example.com',
            CarbonImmutable::now(),
        );
        $phone = $service->issue(
            $tid,
            CustomerOtpChallenge::PURPOSE_LOGIN,
            '+919876543210',
            CarbonImmutable::now(),
        );

        $this->assertCount(1, $spy->sent);
        $delivery = $spy->sent[0];
        $this->assertSame('user@example.com', $delivery->to);
        $this->assertSame((string) $email->getKey(), $delivery->challengeId);
        $this->assertSame(CustomerOtpChallenge::PURPOSE_SIGNUP, $delivery->purpose);
        $this->assertSame(hash('sha256', $delivery->code), (string) $email->code_hash);
        // Phone identifiers never touch the email port.
        $this->assertNotSame((string) $phone->getKey(), $delivery->challengeId);
    }

    public function test_verify_dispatches_on_the_row_provider(): void
    {
        $tid = (string) $this->makeTenant()->getKey();
        $fake = new class () implements PhoneVerificationProvider {
            public function name(): string
            {
                return 'remote-fake';
            }

            public function send(OtpSendContext $context): OtpSendResult
            {
                return new OtpSendResult(providerRef: 'ref-'.$context->challengeId);
            }

            public function verify(OtpVerifyRequest $request): OtpVerifyOutcome
            {
                return $request->code === '424242'
                    ? OtpVerifyOutcome::Match
                    : OtpVerifyOutcome::Mismatch;
            }
        };
        $service = $this->service(new OtpProviderRegistry(['remote-fake' => $fake]));
        $now = CarbonImmutable::now();

        // A remote row (no code_hash, provider_ref set) issued under the
        // fake provider: match consumes it, mismatch burns an attempt.
        $remote = $this->remoteChallenge($tid, 'remote-fake');
        $returned = $service->verify($tid, CustomerOtpChallenge::PURPOSE_LOGIN, (string) $remote->getKey(), '424242', $now);
        $this->assertNotNull($returned);
        $this->assertNull(CustomerOtpChallenge::query()->find($remote->getKey()));

        $other = $this->remoteChallenge($tid, 'remote-fake');
        $this->assertNull($service->verify($tid, CustomerOtpChallenge::PURPOSE_LOGIN, (string) $other->getKey(), '000000', $now));
        $this->assertSame(1, (int) CustomerOtpChallenge::query()->find($other->getKey())->attempts);

        // A row referencing an unregistered provider fails closed — it
        // must never fall back to the local provider (no oracle, no
        // downgrade) and never return a plain null.
        $unknown = $this->remoteChallenge($tid, 'retired-provider');
        $this->expectException(OtpProviderUnavailableException::class);
        $service->verify($tid, CustomerOtpChallenge::PURPOSE_LOGIN, (string) $unknown->getKey(), '424242', $now);
    }

    private function remoteChallenge(string $tid, string $provider): CustomerOtpChallenge
    {
        return CustomerOtpChallenge::factory()->create([
            'tenant_id' => $tid,
            'purpose' => CustomerOtpChallenge::PURPOSE_LOGIN,
            'identifier' => '+911234567890',
            'code_hash' => null,
            'provider' => $provider,
            'provider_ref' => 'session-info-opaque',
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);
    }
}
