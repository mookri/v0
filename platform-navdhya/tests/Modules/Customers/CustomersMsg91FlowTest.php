<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Support\Facades\Crypt;
use OTPHP\TOTP;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

final class CustomersMsg91FlowTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', 'test-hmac-secret-for-actor-tokens-32-chars!!');
    }

    private function createCustomerUser(Tenant $tenant, string $phone, bool $mfa = false): array
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test User',
            'email' => preg_replace('/[^0-9]/', '', $phone).'@test.local',
            'phone' => $phone,
            'phone_verified_at' => now(),
            'external_id' => 'cust_test_'.bin2hex(random_bytes(8)),
            'password' => bcrypt('secret'),
            'mfa_enabled' => $mfa,
        ]);

        $secret = null;
        if ($mfa) {
            $totp = TOTP::generate();
            $secret = $totp->getSecret();
            $user->mfa_secret = Crypt::encryptString($secret);
        }

        $user->save();

        TenantMembership::query()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->getAuthIdentifier(),
            'state' => TenantMembership::STATE_ACTIVE,
        ]);

        return [$user, $secret];
    }

    public function test_signup_and_verify_signup_happy_path(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $phone = '+919876543210';

        // 1. Issue signup challenge
        $res = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tenantId,
            'phone' => $phone,
            'name' => 'Aarav Sharma',
        ]);
        $res->assertStatus(202);
        $challengeId = $res->json('data.challenge_id');
        $this->assertNotEmpty($challengeId);

        // 2. Verify with matching MSG91 token
        $token = $this->msg91TokenFor($phone);
        $verifyRes = $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $token,
        ]);
        $verifyRes->assertStatus(201);
        $this->assertNotEmpty($verifyRes->json('data.token'));
        $this->assertSame($tenantId, $verifyRes->json('data.tenant_id'));

        // 3. User was created in DB
        $user = User::query()->where('phone', $phone)->first();
        $this->assertNotNull($user);
        $this->assertSame('Aarav Sharma', $user->name);
    }

    public function test_token_mismatch_burns_attempts_and_three_strikes_deletes_challenge(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $phone = '+919876543210';

        $res = $this->postJson('/api/v1/customers/auth/signup', [
            'tenant_id' => $tenantId,
            'phone' => $phone,
            'name' => 'Aarav Sharma',
        ]);
        $challengeId = $res->json('data.challenge_id');

        $wrongToken = $this->msg91TokenFor('+919999999999');

        // Strike 1
        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $wrongToken,
        ])->assertStatus(401);

        // Strike 2
        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $wrongToken,
        ])->assertStatus(401);

        // Strike 3
        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $wrongToken,
        ])->assertStatus(401);

        // 4th attempt: challenge row has been deleted -> 401
        $this->postJson('/api/v1/customers/auth/verify', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $this->msg91TokenFor($phone),
        ])->assertStatus(401);
    }

    public function test_login_happy_path_and_token_replay_rejected(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $phone = '+919876543210';
        $this->createCustomerUser($tenant, $phone);

        // 1. Issue login challenge
        $res = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tenantId,
            'phone' => $phone,
        ]);
        $res->assertStatus(202);
        $challengeId = $res->json('data.challenge_id');

        // 2. Login with valid token
        $token = $this->msg91TokenFor($phone);
        $loginRes = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $token,
        ]);
        $loginRes->assertStatus(200);
        $this->assertNotEmpty($loginRes->json('data.token'));

        // 3. Replay: submitting same challenge again fails with 401 (row was consumed)
        $replayRes = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $token,
        ]);
        $replayRes->assertStatus(401);
    }

    public function test_two_phase_login_with_mfa_stepup_hold_and_totp_consume(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $phone = '+919876543210';
        [$user, $totpSecret] = $this->createCustomerUser($tenant, $phone, mfa: true);

        // 1. Request OTP
        $res = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tenantId,
            'phone' => $phone,
        ]);
        $challengeId = $res->json('data.challenge_id');

        // 2. Phase 1 login: submit token without totp_code -> held row -> 202 mfa_required
        $token = $this->msg91TokenFor($phone);
        $p1Res = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'token' => $token,
        ]);
        $p1Res->assertStatus(202)
            ->assertJsonPath('data.status', 'mfa_required')
            ->assertJsonPath('data.methods', ['totp']);

        // 3. Phase 2 login: submit totp_code without token -> consumes held row -> 200 session
        $totp = TOTP::createFromSecret($totpSecret);
        $totpCode = $totp->now();

        $p2Res = $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => $tenantId,
            'challenge_id' => $challengeId,
            'totp_code' => $totpCode,
        ]);
        $p2Res->assertStatus(200);
        $this->assertNotEmpty($p2Res->json('data.token'));
    }
}
