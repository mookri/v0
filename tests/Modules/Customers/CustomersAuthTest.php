<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Tenancy\Models\TenantMembership;
use Tests\TenantIntegrationTestCase;

/**
 * Customer auth (B2, docs/api/parity-customers.md): OTP request with no
 * oracle, signup→verify→token, login cycle, collapse of all code failures
 * to one 401, flood caps, deactivated denial, token acceptance on tenant
 * routes, and best-effort logout.
 */
final class CustomersAuthTest extends TenantIntegrationTestCase
{
    private const SECRET = 'customer-test-secret';

    private function otp(): SeededOtpCodes
    {
        $codes = new SeededOtpCodes();
        $this->app->singleton(CustomerOtpService::class, static fn (): CustomerOtpService => new CustomerOtpService($codes));
        config()->set('identity.hmac_secret', self::SECRET);

        return $codes;
    }

    private function service(): CustomerOtpService
    {
        return $this->app->make(CustomerOtpService::class);
    }

    private function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    public function test_signup_verify_issues_token_and_materializes_account(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Signup builds its own challenge (intent travels in the payload);
        // auth/otp + auth/verify only pairs for login.
        $otp = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => '+911234567890', 'name' => 'Ravi',
        ])->assertStatus(202);
        $challengeId = $otp->json('data.challenge_id');
        $this->assertSame(600, $otp->json('data.expires_in'));

        $verified = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertCreated();
        $token = $verified->json('data.token');
        $this->assertSame($tid, $verified->json('data.tenant_id'));

        // The minted token verifies under the bridge secret (same algorithm
        // the frontend minter uses) and resolves the new user.
        $actor = ActorTokenVerifier::verify(self::SECRET, $token, $this->nowIso());
        $this->assertNotNull($actor);
        $this->assertStringStartsWith('cust_', $actor['user_id']);

        $this->assertDatabaseHas('users', ['phone' => '+911234567890'], 'central');
        $user = User::query()->where('phone', '+911234567890')->firstOrFail();
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
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        User::factory()->create(['phone' => '+911234567890']);

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => '+911234567890', 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_login_cycle_backfills_profile_and_audits(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill(['external_id' => 'cust_loginprobe'])->save();
        $this->makeMembership($tenant, $user);
        // No buyer profile: login must backfill instead of failing.

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $logged = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
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
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill(['external_id' => 'cust_mfalogin'])->save();
        $this->makeMembership($tenant, $user);

        $service = new \App\Platform\Identity\MfaService();
        $service->enroll($user);
        $secret = \Illuminate\Support\Facades\Crypt::decryptString($user->fresh()->mfa_secret);
        $totp = \OTPHP\TOTP::createFromSecret($secret);
        $service->confirm($user->fresh(), $totp->now(), time());

        $loginWith = function (?string $totpCode) use ($codes, $tid) {
            $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
                'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
            ])->assertStatus(202)->json('data.challenge_id');

            $payload = [
                'tenant_id' => $tid, 'challenge_id' => $challengeId,
                'code' => $codes->lastFor($challengeId),
            ];
            if ($totpCode !== null) {
                $payload['totp_code'] = $totpCode;
            }

            return $this->postJson('/api/v1/customers/auth/login', $payload);
        };

        // No TOTP, wrong TOTP: same single 401 (no oracle on MFA state).
        $loginWith(null)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $loginWith('000000')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        // Correct TOTP: full two-factor login.
        $loginWith($totp->now())->assertOk()->assertJsonPath('data.actor.user_id', 'cust_mfalogin');
    }

    public function test_bad_codes_collapse_to_one_401(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill(['external_id' => 'cust_collapse'])->save();
        $this->makeMembership($tenant, $user);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $bodies = [];
        // Three wrong codes exhaust the challenge; the correct code afterwards
        // must fail identically (single-use + no oracle on exhaustion).
        foreach (['000000', '111111', '222222'] as $wrong) {
            $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
                'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $wrong,
            ])->assertUnauthorized()->getContent();
        }
        $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertUnauthorized()->getContent();

        // Unknown challenge ids fail the same way (no existence signal).
        $bodies[] = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => (string) \Illuminate\Support\Str::ulid(), 'code' => '123456',
        ])->assertUnauthorized()->getContent();

        $decoded = array_map(static fn (string $body): array => (array) json_decode($body, true), $bodies);
        foreach ($decoded as $body) {
            $this->assertSame('unauthenticated', $body['error']['code']);
            $this->assertSame($decoded[0]['error']['message'], $body['error']['message']);
        }
    }

    public function test_unknown_identity_gets_decoy_then_401(): void
    {
        $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Identical 202 shape to a real challenge (no oracle on existence).
        $decoy = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+910000000000',
        ])->assertStatus(202);
        $this->assertNotEmpty($decoy->json('data.challenge_id'));
        $this->assertSame(600, $decoy->json('data.expires_in'));

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $decoy->json('data.challenge_id'), 'code' => '123456',
        ])->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_flood_returns_429(): void
    {
        $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        // Known identity: unknown identities get decoy 202s (no rows, no
        // caps — IP-level throttle is the only brake there, by design).
        User::factory()->create(['phone' => '+911234567890']);

        for ($i = 0; $i < CustomerOtpService::MAX_PER_HOUR; $i++) {
            $this->postJson('/api/v1/customers/auth/otp', [
                'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
            ])->assertStatus(202);
        }

        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
    }

    public function test_deactivated_login_denied(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill(['external_id' => 'cust_deact'])->save();
        $this->makeMembership($tenant, $user, ['state' => TenantMembership::STATE_SUSPENDED]);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_login_without_external_link_fails_closed(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        // Legacy user row with no external_id: minting must refuse rather
        // than issue an unresolvable token.
        $user = User::factory()->create(['phone' => '+911234567890']);
        $this->assertNull($user->external_id);
        $this->makeMembership($tenant, $user);

        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertServerError()->assertJsonPath('error.code', 'internal_error');
    }

    public function test_token_authenticates_tenant_routes(): void
    {
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => '+911234567890', 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');

        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
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
        $codes = $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Guest logout first: actingAs is never used in this test.
        $this->postJson('/api/v1/customers/auth/logout', [], ['X-Tenant-Id' => $tid])
            ->assertUnauthorized();

        $challengeId = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tid, 'phone' => '+911234567890', 'name' => 'Ravi',
        ])->assertStatus(202)->json('data.challenge_id');
        $token = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tid, 'challenge_id' => $challengeId, 'code' => $codes->lastFor($challengeId),
        ])->assertCreated()->json('data.token');

        $this->postJson('/api/v1/customers/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => $tid,
        ])->assertOk()->assertJsonPath('data.signed_out', true);

        $this->assertAuditRecorded($tenant, 'customers.auth.logged_out');
    }

    public function test_unknown_tenant_is_404(): void
    {
        $this->otp();

        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => '01J0000000000000000000000', 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_drill_code_log_is_opt_in(): void
    {
        $this->otp();
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        User::factory()->create(['phone' => '+911234567890']);

        $handler = new \Monolog\Handler\TestHandler();
        app('log')->channel()->getLogger()->pushHandler($handler);

        config()->set('customers.log_otp_codes', false);
        $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202);
        $this->assertFalse($handler->hasInfoRecords());

        config()->set('customers.log_otp_codes', true);
        $challengeId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        $logged = array_values(array_filter(
            $handler->getRecords(),
            static fn ($record): bool => ($record['message'] ?? null) === 'OTP challenge issued (drill-only code log)'
        ));
        $this->assertCount(1, $logged);
        $this->assertSame($challengeId, $logged[0]['context']['challenge_id']);
        $this->assertNotEmpty($logged[0]['context']['code']);
    }
}
