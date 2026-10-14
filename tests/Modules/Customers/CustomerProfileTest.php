<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Customer profile (B2, docs/api/parity-customers.md): self-service read
 * and update, contact masking, and proof-required deactivation +
 * public-OTP reactivation.
 */
final class CustomerProfileTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', 'profile-test-secret-at-least-32-chars!');
    }

    /**
     * @return array{0: Tenant, 1: User, 2: BuyerProfile}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill([
            'email' => '911234567890@phone.local',
            'external_id' => 'cust_'.strtolower((string) Str::ulid()),
        ])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            $profile = BuyerProfile::factory()->create([
                'user_id' => $user->id,
                'display_name' => 'Original Name',
                'locale' => 'en',
            ]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user, $profile];
    }

    private function loginChallenge(string $tid): string
    {
        return $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => $tid, 'phone' => '+911234567890',
        ])->assertStatus(202)->json('data.challenge_id');
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

    public function test_update_modifies_display_name_and_locale(): void
    {
        [$tenant, , $profile] = $this->customer();

        $updated = $this->patchJson('/api/v1/customers/profile', [
            'display_name' => 'New Display Name',
            'locale' => 'hi',
        ], $this->tenantHeaders($tenant))->assertOk();

        $this->assertSame('New Display Name', $updated->json('data.display_name'));
        $this->assertSame('hi', $updated->json('data.locale'));

        $this->resolveContext($tenant);
        try {
            $fresh = BuyerProfile::query()->find($profile->id);
            $this->assertSame('New Display Name', $fresh->display_name);
            $this->assertSame('hi', $fresh->locale);
        } finally {
            $this->clearContext();
        }

        $this->assertAuditRecorded($tenant, 'customers.profile.updated');
    }

    public function test_update_rejects_unknown_fields(): void
    {
        [$tenant] = $this->customer();

        $this->patchJson('/api/v1/customers/profile', [
            'phone' => '+919999999999',
        ], $this->tenantHeaders($tenant))->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_deactivate_requires_fresh_proof_and_suspends(): void
    {
        [$tenant, $user] = $this->customer();
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // No fresh proof → 401 even with a valid session.
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => (string) Str::ulid(), 'token' => $this->msg91TokenFor('+911234567890'),
        ], $headers)->assertUnauthorized();

        $cid = $this->loginChallenge($tid);
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => $cid, 'token' => $this->msg91TokenFor('+911234567890'),
        ], $headers)->assertOk()->assertJsonPath('data.status', 'deactivated');

        // Membership enforcement is live: the same session is now denied
        $this->actingAs($user)->getJson('/api/v1/customers/profile', $headers)
            ->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');

        $this->assertAuditRecorded($tenant, 'customers.account.deactivated');
    }

    public function test_reactivate_is_public_otp_and_rejects_active(): void
    {
        [$tenant, $user] = $this->customer();
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        // Active account + fresh proof → 409 (no state change to make).
        $cid = $this->loginChallenge($tid);
        $this->postJson('/api/v1/customers/profile:reactivate', [
            'tenant_id' => $tid, 'challenge_id' => $cid, 'token' => $this->msg91TokenFor('+911234567890'),
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'state_forbidden');

        // Deactivate (authed + proof), drop the session, reactivate publicly.
        $this->travel(11)->minutes();
        $cid2 = $this->loginChallenge($tid);
        $this->actingAs($user)->postJson('/api/v1/customers/profile:deactivate', [
            'challenge_id' => $cid2, 'token' => $this->msg91TokenFor('+911234567890'),
        ], $headers)->assertOk();
        auth()->logout();

        // Fresh login challenge after TTL/cooldown
        $this->travel(11)->minutes();
        $cid3 = $this->loginChallenge($tid);
        $this->postJson('/api/v1/customers/profile:reactivate', [
            'tenant_id' => $tid, 'challenge_id' => $cid3, 'token' => $this->msg91TokenFor('+911234567890'),
        ], $headers)->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertAuditRecorded($tenant, 'customers.account.reactivated');
    }
}
