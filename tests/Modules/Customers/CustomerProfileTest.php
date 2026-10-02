<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Auth\CustomerOtpService;
use App\Modules\Customers\Auth\SeededOtpCodes;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Customer profile (B3, docs/api/parity-customers.md): masked reads,
 * strict updates, OTP-stepped deactivate with live membership
 * enforcement, public-OTP reactivate, staff-edge 404s, machine denial.
 */
final class CustomerProfileTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User, 2: BuyerProfile|null}
     */
    private function customer(bool $withProfile = true): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => '+911234567890']);
        // Signup-shaped row: synthetic email (reported as absent) + login link.
        $user->forceFill([
            'email' => '911234567890@phone.local',
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
        ])->save();
        $this->makeMembership($tenant, $user);

        $profile = null;
        if ($withProfile) {
            $this->resolveContext($tenant);
            try {
                $profile = BuyerProfile::factory()->create(['user_id' => $user->id]);
            } finally {
                $this->clearContext();
            }
        }

        $this->actingAs($user);

        return [$tenant, $user, $profile];
    }

    private function freshOtp(): SeededOtpCodes
    {
        $codes = new SeededOtpCodes();
        $this->app->singleton(CustomerOtpService::class, static fn (): CustomerOtpService => new CustomerOtpService($codes));
        config()->set('identity.hmac_secret', 'profile-test-secret');

        return $codes;
    }

    /**
     * @return array{0: string, 1: string} challenge id + current code
     */
    private function loginChallenge(string $tid, SeededOtpCodes $codes): array
    {
        $id = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'purpose' => 'login', 'identity' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');

        return [$id, $codes->lastFor($id)];
    }

    public function test_show_masks_contacts(): void
    {
        [$tenant] = $this->customer();

        $shown = $this->getJson('/api/v1/customers/profile', $this->tenantHeaders($tenant))->assertOk();
        $this->assertSame('+91******90', $shown->json('data.phone'));
        $this->assertNull($shown->json('data.email'));
        $this->assertNull($shown->json('data.email_verified_at'));
        $this->assertSame('active', $shown->json('data.status'));
    }

    public function test_show_without_profile_is_404(): void
    {
        [$tenant] = $this->customer(false);

        $this->getJson('/api/v1/customers/profile', $this->tenantHeaders($tenant))
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_guests_are_denied(): void
    {
        $tenant = $this->makeTenant();

        $this->getJson('/api/v1/customers/profile', $this->tenantHeaders($tenant))
            ->assertUnauthorized();
    }

    public function test_update_and_strict_fields(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $updated = $actor()->patchJson('/api/v1/customers/profile', [
            'display_name' => 'Ravi K', 'locale' => 'hi',
        ], $headers)->assertOk();
        $this->assertSame('Ravi K', $updated->json('data.display_name'));
        $this->assertSame('hi', $updated->json('data.locale'));

        $actor()->patchJson('/api/v1/customers/profile', ['role' => 'admin'], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.role', ['unknown_field']);

        $this->assertAuditRecorded($tenant, 'customers.profile.updated');
    }

    public function test_deactivate_needs_fresh_proof_then_denies_session(): void
    {
        $codes = $this->freshOtp();
        [$tenant, $user] = $this->customer();
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // No fresh proof → 401 even with a valid session.
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => (string) Str::ulid(), 'code' => '000000',
        ], $headers)->assertUnauthorized();

        [$cid, $code] = $this->loginChallenge($tid, $codes);
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => $cid, 'code' => $code,
        ], $headers)->assertOk()->assertJsonPath('data.status', 'deactivated');

        // Membership enforcement is live: the same session is now denied
        // (and a second deactivate can only meet the 403 gate, by design).
        $this->actingAs($user)->getJson('/api/v1/customers/profile', $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');

        $this->assertAuditRecorded($tenant, 'customers.account.deactivated');
    }

    public function test_reactivate_is_public_otp_and_rejects_active(): void
    {
        $codes = $this->freshOtp();
        [$tenant, $user] = $this->customer();
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // Active account + fresh proof → 409 (no state change to make).
        [$cid, $code] = $this->loginChallenge($tid, $codes);
        $this->postJson('/api/v1/customers/profile:reactivate', [
            'tenant_id' => $tid, 'challenge_id' => $cid, 'code' => $code,
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        // Deactivate (authed + proof), drop the session, reactivate publicly.
        [$cid2, $code2] = $this->loginChallenge($tid, $codes);
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => $cid2, 'code' => $code2,
        ], $headers)->assertOk();
        auth()->logout();

        [$cid3, $code3] = $this->loginChallenge($tid, $codes);
        $this->postJson('/api/v1/customers/profile:reactivate', [
            'tenant_id' => $tid, 'challenge_id' => $cid3, 'code' => $code3,
        ])->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertAuditRecorded($tenant, 'customers.account.reactivated');
    }

    public function test_machine_caller_denied(): void
    {
        [$tenant] = $this->customer();
        $tid = (string) $tenant->getKey();
        [$issuer] = $this->actingAsMembership($tenant);
        $this->stepUp($issuer);

        $secret = $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tid, 'name' => 'probe',
        ])->assertCreated()->json('data.secret');
        auth()->logout();

        $this->getJson('/api/v1/customers/profile', [
            'Authorization' => "Bearer {$secret}",
            'X-Tenant-Id' => $tid,
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
    }
}
