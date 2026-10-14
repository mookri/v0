<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * OTP challenge supersession + atomic redemption:
 * - one active challenge per (tenant, purpose, identifier)
 * - redemption is single-use: second redeem of consumed challenge collapses to 401
 * - attempt counting is exact: 3 bad tokens delete the row
 */
final class CustomerOtpSupersessionTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    private const SECRET = 'supersession-test-secret-at-least-32-chars!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', self::SECRET);
    }

    /**
     * @return array{0: string, 1: string} [tenant id, phone]
     */
    private function knownIdentity(): array
    {
        $tenant = $this->makeTenant();
        $phone = '+911234567890';
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => 'cust_supersession'])->save();
        $this->makeMembership($tenant, $user);

        return [(string) $tenant->getKey(), $phone];
    }

    private function startLoginChallenge(string $tid, string $phone): string
    {
        return $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');
    }

    public function test_double_redeem_collapses_to_uniform_401(): void
    {
        [$tid, $phone] = $this->knownIdentity();

        $challengeId = $this->startLoginChallenge($tid, $phone);
        $token = $this->msg91TokenFor($phone);

        // First login succeeds
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $token,
        ])->assertOk();

        // Second redeem with same challenge fails with 401 (consumed row was deleted)
        $replay = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $token,
        ])->assertUnauthorized()->json('error');

        $unknown = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => (string) \Illuminate\Support\Str::ulid(), 'token' => $token,
        ])->assertUnauthorized()->json('error');

        $this->assertSame($unknown['code'], $replay['code']);
    }

    public function test_three_bad_tokens_exhaust_and_delete_challenge(): void
    {
        [$tid, $phone] = $this->knownIdentity();

        $challengeId = $this->startLoginChallenge($tid, $phone);
        $wrongToken = $this->msg91TokenFor('+919999999999');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/customers/auth/login', [
                'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $wrongToken,
            ])->assertUnauthorized();
        }

        // Row is now deleted
        $this->assertNull(CustomerOtpChallenge::query()->find($challengeId));

        // Subsequent call even with correct token fails with 401
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $this->msg91TokenFor($phone),
        ])->assertUnauthorized();
    }
}
