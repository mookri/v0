<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Identity\Otp\OtpDeliveryPolicy;
use App\Platform\Tenancy\Models\TenantMembership;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Customer auth (B2, docs/api/parity-customers.md): OTP request with no
 * oracle, signup→verify→token, login cycle, collapse of all code failures
 * to one 401, flood caps, deactivated denial, token acceptance on tenant
 * routes, and best-effort logout.
 */
final class CustomersAuthTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    private const SECRET = 'customer-test-secret-at-least-32-chars-long!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', self::SECRET);
    }

    private function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    public function test_signup_verify_issues_token_and_materializes_account(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        // Signup builds its own challenge (intent travels in the payload);
        // auth/otp + auth/verify only pairs for login.
        $otp = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => $phone, 'name' => 'Ravi',
        ])->assertStatus(202);
        $challengeId = $otp->json('data.challenge_id');
        $this->assertSame(OtpDeliveryPolicy::load()->ttlSeconds, $otp->json('data.expires_in'));

        $verified = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertCreated();
        $token = $verified->json('data.token');
        $this->assertSame($tid, $verified->json('data.tenant_id'));

        // The minted token verifies under the bridge secret (same algorithm
        // the frontend minter uses) and resolves the new user.
        $actor = ActorTokenVerifier::verify(self::SECRET, $token, $this->nowIso());
        $this->assertNotNull($actor);
        $this->assertStringStartsWith('cust_', $actor['user_id']);

        $this->assertDatabaseHas('users', ['phone' => $phone], 'central');
        $user = User::query()->where('phone', $phone)->firstOrFail();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $tid, 'user_id' => $user->getAuthIdentifier(), 'state' => TenantMembership::STATE_ACTIVE,
        ], 'central');
        $this->assertDatabaseHas('buyer_profiles', [
            'tenant_id' => $tid, 'user_id' => $user->getAuthIdentifier(),
        ], 'tenant_shared');
        $this->assertAuditRecorded($tenant, 'customers.account.signed_up');
    }

    public function test_signup_duplicate_phone_conflicts(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';
        User::factory()->create(['phone' => $phone]);

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => $phone, 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_login_cycle_backfills_profile_and_audits(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => 'cust_loginprobe'])->save();
        $this->makeMembership($tenant, $user);
        // No buyer profile: login must backfill instead of failing.

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $logged = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertOk();
        $this->assertSame('cust_loginprobe', $logged->json('data.actor.user_id'));

        $actor = ActorTokenVerifier::verify(self::SECRET, $logged->json('data.token'), $this->nowIso());
        $this->assertSame('cust_loginprobe', $actor['user_id']);

        $this->assertDatabaseHas('buyer_profiles', [
            'tenant_id' => $tid, 'user_id' => $user->getAuthIdentifier(),
        ], 'tenant_shared');
        $this->assertAuditRecorded($tenant, 'customers.auth.logged_in');
    }

    public function test_login_demands_totp_when_mfa_enrolled(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => 'cust_mfalogin'])->save();
        $this->makeMembership($tenant, $user);

        $service = new \App\Platform\Identity\MfaService();
        $service->enroll($user);
        $secret = \Illuminate\Support\Facades\Crypt::decryptString($user->fresh()->mfa_secret);
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        $service->confirm($user->fresh(), $totp->now(), time());

        // Step 1: initial login submit without totp returns 202 mfa_required
        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'mfa_required')
            ->assertJsonPath('data.methods', ['totp']);

        // Step 2: wrong TOTP fails with 401
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'totp_code' => '000000',
        ])->assertUnauthorized();

        // Step 3: correct TOTP completes login
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'totp_code' => $totp->now(),
        ])->assertOk()->assertJsonPath('data.actor.user_id', 'cust_mfalogin');
    }

    public function test_bad_tokens_collapse_to_one_401(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => 'cust_collapse'])->save();
        $this->makeMembership($tenant, $user);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $wrongToken = $this->msg91TokenFor('+919999999999');

        $bodies = [];
        // Three wrong tokens exhaust the challenge; the correct token afterwards
        // must fail identically (single-use + no oracle on exhaustion).
        for ($i = 0; $i < 3; $i++) {
            $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
                'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $wrongToken,
            ])->assertUnauthorized()->getContent();
        }
        $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'token' => $this->msg91TokenFor($phone),
        ])->assertUnauthorized()->getContent();

        // Unknown challenge ids fail the same way (no existence signal).
        $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => (string) \Illuminate\Support\Str::ulid(), 'token' => $this->msg91TokenFor($phone),
        ])->assertUnauthorized()->getContent();

        $decoded = array_map(static fn (string $body): array => (array) json_decode($body, true), $bodies);
        foreach ($decoded as $body) {
            $this->assertSame('unauthenticated', $body['error']['code']);
            $this->assertSame($decoded[0]['error']['message'], $body['error']['message']);
        }
    }

    public function test_unknown_identity_gets_decoy_then_401(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+910000000000';

        // Identical 202 shape to a real challenge (no oracle on existence).
        $decoy = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202);
        $this->assertNotEmpty($decoy->json('data.challenge_id'));
        $this->assertSame(OtpDeliveryPolicy::load()->ttlSeconds, $decoy->json('data.expires_in'));

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $decoy->json('data.challenge_id'),
            'token' => $this->msg91TokenFor($phone),
        ])->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_flood_returns_429(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';
        User::factory()->create(['phone' => $phone]);

        // First issue succeeds
        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202);

        // Immediate subsequent request hits the delivery policy fresh gate -> 429
        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
    }

    public function test_deactivated_login_denied(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill(['external_id' => 'cust_deact'])->save();
        $this->makeMembership($tenant, $user, ['state' => TenantMembership::STATE_SUSPENDED]);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_login_without_external_link_fails_closed(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';
        $user = User::factory()->create(['phone' => $phone]);
        $this->assertNull($user->external_id);
        $this->makeMembership($tenant, $user);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => $phone,
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertServerError()->assertJsonPath('error.code', 'internal_error');
    }

    public function test_token_authenticates_tenant_routes(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => $phone, 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertCreated()->json('data.token');

        // No session auth anywhere in this test: the bearer alone must pass
        // auth.any + tenant resolution on an unrelated module's routes.
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertOk()->assertJsonStructure(['data', 'meta' => ['request_id']]);
    }

    public function test_logout_and_guests(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $phone = '+911234567890';

        // Guest logout first: actingAs is never used in this test.
        $this->postJson('/api/v1/customers/auth/logout', [], ['X-Tenant-Id' => $tid])
            ->assertUnauthorized();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => $phone, 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');
        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertCreated()->json('data.token');

        $this->postJson('/api/v1/customers/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertOk()->assertJsonPath('data.signed_out', true);

        $this->assertAuditRecorded($tenant, 'customers.auth.logged_out');
    }

    public function test_unknown_tenant_is_404(): void
    {
        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => '01J0000000000000000000000', 'phone' => '+911234567890',
        ])->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }
}
