<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\OtpRateLimitedException;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Modules\Customers\Models\CustomerOtpChallenge;
use Tests\TenantIntegrationTestCase;

/**
 * OTP challenge supersession + atomic redemption (M2/M3, gates C1, C2,
 * C4 in sequential form — true multi-process concurrency is exercised by
 * the E2E §9b scenarios; these pin the row-level semantics):
 * - one active challenge per (tenant, purpose, identifier): a resend
 *   supersedes the previous one, which dies in the same uniform 401 as
 *   any other miss (G1, no supersession oracle);
 * - superseded rows keep counting toward the 5/hour issuance cap;
 * - redemption is single-use: a second redeem of a consumed challenge
 *   collapses identically (C1);
 * - attempt counting is exact: 1→2→3, row survives 3 failures, the next
 *   redeem deletes it (C4).
 */
final class CustomerOtpSupersessionTest extends TenantIntegrationTestCase
{
    private const SECRET = 'supersession-test-secret';

    private function otp(): SeededOtpCodes
    {
        $codes = new SeededOtpCodes();
        $this->app->singleton(CustomerOtpService::class, static fn (): CustomerOtpService => new CustomerOtpService($codes));
        config()->set('identity.hmac_secret', self::SECRET);

        return $codes;
    }

    /**
     * @return array{0: string, 1: string} [tenant id, phone]
     */
    private function knownIdentity(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill(['external_id' => 'cust_supersession'])->save();
        $this->makeMembership($tenant, $user);

        return [(string) $tenant->getKey(), '+911234567890'];
    }

    private function startLoginChallenge(string $tid): string
    {
        return $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');
    }

    public function test_resend_supersedes_the_previous_active_challenge(): void
    {
        $codes = $this->otp();
        [$tid] = $this->knownIdentity();

        $first = $this->startLoginChallenge($tid);
        $second = $this->startLoginChallenge($tid);

        // Exactly one active challenge survives (C2): the first is marked,
        // not deleted — the hourly cap still counts it.
        $this->assertNotNull(CustomerOtpChallenge::query()->find($first)->superseded_at);
        $this->assertNull(CustomerOtpChallenge::query()->find($second)->superseded_at);

        // G1: the superseded challenge cannot be redeemed — its failure is
        // indistinguishable from an unknown challenge id (no oracle; the
        // per-request request_id is the only byte that differs).
        $superseded = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $first, 'code' => $codes->lastFor($first),
        ])->assertUnauthorized()->json('error');
        $unknown = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => (string) \Illuminate\Support\Str::ulid(), 'code' => '123456',
        ])->assertUnauthorized()->json('error');
        $this->assertSame($unknown['code'], $superseded['code']);
        $this->assertSame($unknown['message'], $superseded['message']);

        // The surviving challenge redeems normally.
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $second, 'code' => $codes->lastFor($second),
        ])->assertOk();
    }

    public function test_supersession_keeps_the_hourly_issuance_cap(): void
    {
        $this->otp();
        [$tid] = $this->knownIdentity();

        for ($i = 0; $i < CustomerOtpService::MAX_PER_HOUR; $i++) {
            $this->startLoginChallenge($tid);
        }

        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');

        // Marking, not deleting: all issuances stay inside the window.
        $rows = CustomerOtpChallenge::query()
            ->where('tenant_id', $tid)->where('purpose', 'login')->where('identifier', '+911234567890')
            ->get();
        $this->assertCount(CustomerOtpService::MAX_PER_HOUR, $rows);
        $this->assertSame(1, $rows->whereNull('superseded_at')->count());
    }

    public function test_double_redeem_collapses_to_the_uniform_401(): void
    {
        $codes = $this->otp();
        [$tid] = $this->knownIdentity();

        $challengeId = $this->startLoginChallenge($tid);
        $code = $codes->lastFor($challengeId);

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $code,
        ])->assertOk();

        // C1 (sequential form): the consumed challenge is gone — the
        // second redeem cannot mint a second session.
        $replay = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $code,
        ])->assertUnauthorized()->json('error');
        $unknown = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => (string) \Illuminate\Support\Str::ulid(), 'code' => $code,
        ])->assertUnauthorized()->json('error');
        $this->assertSame($unknown['code'], $replay['code']);
        $this->assertSame($unknown['message'], $replay['message']);
    }

    public function test_attempts_count_exactly_and_exhaustion_deletes(): void
    {
        $codes = $this->otp();
        [$tid] = $this->knownIdentity();

        $challengeId = $this->startLoginChallenge($tid);

        // C4: each failure increments the counter by exactly one.
        foreach ([1, 2, 3] as $expected) {
            $this->postJson('/api/v1/customers/auth/login', [
                'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => '000000',
            ])->assertUnauthorized();
            $this->assertSame($expected, (int) CustomerOtpChallenge::query()->find($challengeId)->attempts);
        }

        // Row survives the third failure; the next redeem (even with the
        // correct code — no exhaustion oracle) deletes it.
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertUnauthorized();
        $this->assertNull(CustomerOtpChallenge::query()->find($challengeId));
    }

    public function test_service_level_rate_cap_throws_before_any_row(): void
    {
        $this->otp();
        [$tid] = $this->knownIdentity();

        $service = $this->app->make(CustomerOtpService::class);
        $now = \Carbon\CarbonImmutable::now();

        for ($i = 0; $i < CustomerOtpService::MAX_PER_HOUR; $i++) {
            $service->issue($tid, CustomerOtpChallenge::PURPOSE_LOGIN, '+911234567890', $now);
        }

        try {
            $service->issue($tid, CustomerOtpChallenge::PURPOSE_LOGIN, '+911234567890', $now);
            $this->fail('Expected OtpRateLimitedException.');
        } catch (OtpRateLimitedException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(
            CustomerOtpService::MAX_PER_HOUR,
            CustomerOtpChallenge::query()->where('identifier', '+911234567890')->count()
        );
    }
}
