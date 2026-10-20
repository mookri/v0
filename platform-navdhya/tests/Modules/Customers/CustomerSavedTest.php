<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Saved bookmarks (B4): shape-validated adds, duplicate 409s, malformed
 * 422s, deletes with foreign 404s. Refs stay opaque — no catalog joins.
 */
final class CustomerSavedTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
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

    public function test_save_list_unsave_cycle(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);
        $ref = (string) Str::ulid();

        $saved = $actor()->postJson('/api/v1/customers/saved', [
            'ref_type' => 'puja', 'ref_id' => $ref,
        ], $headers)->assertCreated();
        $this->assertSame(strtoupper($ref), $saved->json('data.ref_id'));
        $id = $saved->json('data.id');

        // Duplicate (any case) is a 409, not a second row.
        $actor()->postJson('/api/v1/customers/saved', [
            'ref_type' => 'puja', 'ref_id' => strtolower($ref),
        ], $headers)->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        $listed = $actor()->getJson('/api/v1/customers/saved', $headers)->assertOk();
        $this->assertSame([$id], collect($listed->json('data'))->pluck('id')->all());
        $this->assertTrue($listed->json('meta.page.has_more') === false);

        $actor()->deleteJson("/api/v1/customers/saved/{$id}", [], $headers)->assertOk();
        $actor()->deleteJson("/api/v1/customers/saved/{$id}", [], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->assertAuditRecorded($tenant, 'customers.saved.added');
        $this->assertAuditRecorded($tenant, 'customers.saved.removed');
    }

    public function test_malformed_refs_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        auth()->logout();
        $this->postJson('/api/v1/customers/saved', ['ref_type' => 'puja', 'ref_id' => (string) Str::ulid()], $headers)
            ->assertUnauthorized();

        $actor = fn () => $this->actingAs($user);
        $actor()->postJson('/api/v1/customers/saved', [
            'ref_type' => 'puja', 'ref_id' => 'not-a-ulid',
        ], $headers)->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $actor()->postJson('/api/v1/customers/saved', [
            'ref_type' => 'spell', 'ref_id' => (string) Str::ulid(),
        ], $headers)->assertStatus(422);
    }
}
