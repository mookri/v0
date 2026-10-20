<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * Contact verification (B3, docs/api/parity-customers.md): masked status
 * reads, OTP-proven phone/email changes bound to the requester, 409 on
 * already-verified values, and collapse of bad codes to one 401.
 */
final class CustomerVerificationTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();
        config()->set('identity.hmac_secret', 'verification-test-secret-at-least-32-chars!');
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => '+911234567890']);
        $user->forceFill([
            'email' => '911234567890@phone.local',
            'phone_verified_at' => now()->toDateTimeString(),
            'external_id' => 'cust_'.strtolower((string) \Illuminate\Support\Str::ulid()),
        ])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    public function test_status_masks_and_hides_synthetic_email(): void
    {
        [$tenant] = $this->customer();

        $status = $this->getJson('/api/v1/customers/verifications', $this->tenantHeaders($tenant))->assertOk();
        $this->assertSame('+91******90', $status->json('data.phone.value_masked'));
        $this->assertNotNull($status->json('data.phone.verified_at'));
        $this->assertNull($status->json('data.email.value_masked'));
        $this->assertNull($status->json('data.email.verified_at'));
    }

    public function test_change_phone_end_to_end(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);
        $newPhone = '+919876543210';

        $challengeId = $actor()->postJson('/api/v1/customers/verifications', [
            'channel' => 'phone', 'value' => $newPhone,
        ], $headers)->assertStatus(202)->json('data.challenge_id');

        // Wrong token collapses to single 401.
        $actor()->postJson("/api/v1/customers/verifications/{$challengeId}:confirm", [
            'token' => $this->msg91TokenFor('+919999999999'),
        ], $headers)->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        $confirmed = $actor()->postJson("/api/v1/customers/verifications/{$challengeId}:confirm", [
            'token' => $this->msg91TokenFor($newPhone),
        ], $headers)->assertOk();
        $this->assertSame('phone', $confirmed->json('data.channel'));
        $this->assertSame('+91******10', $confirmed->json('data.value_masked'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => $newPhone], 'central');
        $this->assertNotNull(User::query()->find($user->id)->phone_verified_at);
        $this->assertAuditRecorded($tenant, 'customers.verification.completed');

        // The new phone is now the login identity.
        $loginId = $this->postJson('/api/v1/customers/auth/otp', [
            'tenant_id' => (string) $tenant->getKey(), 'phone' => $newPhone,
        ])->assertStatus(202)->json('data.challenge_id');
        $this->postJson('/api/v1/customers/auth/login', [
            'tenant_id' => (string) $tenant->getKey(),
            'challenge_id' => $loginId,
            'token' => $this->msg91TokenFor($newPhone),
        ])->assertOk();
    }

    public function test_already_verified_value_conflicts(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        // Current phone is verified (factory verified_at) → 409.
        $this->actingAs($user)->postJson('/api/v1/customers/verifications', [
            'channel' => 'phone', 'value' => '+911234567890',
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_confirm_conflicts_when_contact_belongs_to_another_account(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $owner = User::factory()->create(['phone' => '+912222222222', 'email' => 'taken@example.com']);

        $challengeId = $this->actingAs($user)->postJson('/api/v1/customers/verifications', [
            'channel' => 'email', 'value' => 'taken@example.com',
        ], $headers)->assertStatus(202)->json('data.challenge_id');

        $this->actingAs($user)->postJson("/api/v1/customers/verifications/{$challengeId}:confirm", [
            'token' => $this->msg91TokenFor('taken@example.com'),
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        // Nothing was written: requester keeps their email, owner intact.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => '911234567890@phone.local'], 'central');
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'email' => 'taken@example.com'], 'central');
    }

    public function test_bad_shapes_are_422(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $actor()->postJson('/api/v1/customers/verifications', [
            'channel' => 'phone', 'value' => 'not-a-phone',
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $actor()->postJson('/api/v1/customers/verifications', [
            'channel' => 'email', 'value' => 'not-an-email',
        ], $headers)->assertStatus(422)->assertJsonPath('error.details.fields.value', ['invalid_email']);
    }

    public function test_challenge_bound_to_requester(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $newPhone = '+919876543210';

        // Attacker (second customer) tries to confirm the victim's challenge
        $victimId = $this->actingAs($user)->postJson('/api/v1/customers/verifications', [
            'channel' => 'phone', 'value' => $newPhone,
        ], $headers)->assertStatus(202)->json('data.challenge_id');

        $attacker = User::factory()->create(['phone' => '+911111111111']);
        $attacker->forceFill(['external_id' => 'cust_'.strtolower((string) \Illuminate\Support\Str::ulid())])->save();
        $this->makeMembership($tenant, $attacker);
        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $attacker->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($attacker)->postJson("/api/v1/customers/verifications/{$victimId}:confirm", [
            'token' => $this->msg91TokenFor($newPhone),
        ], $headers)->assertUnauthorized();

        $this->assertDatabaseHas('users', ['id' => $attacker->id, 'phone' => '+911111111111'], 'central');
    }
}
