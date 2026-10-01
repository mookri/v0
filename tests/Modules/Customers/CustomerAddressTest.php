<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\CustomerAddress;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Address book (B4): capped creates, default swaps on create/:default,
 * promotion on default delete, foreign 404s, guest 401s.
 */
final class CustomerAddressTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create(['phone' => '+911234567890']);
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

    /**
     * @return array<string, mixed>
     */
    private function addressPayload(array $overrides = []): array
    {
        return [
            'label' => 'home',
            'line1' => '221B Baker Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            ...$overrides,
        ];
    }

    public function test_crud_default_swaps_and_promotion(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $first = $actor()->postJson('/api/v1/customers/addresses', $this->addressPayload(), $headers)
            ->assertCreated()->assertJsonPath('data.is_default', false);
        $firstId = $first->json('data.id');

        $second = $actor()->postJson('/api/v1/customers/addresses', $this->addressPayload([
            'label' => 'office', 'is_default' => true,
        ]), $headers)->assertCreated()->assertJsonPath('data.is_default', true);
        $secondId = $second->json('data.id');

        // Exactly one default after the swap.
        $listed = $actor()->getJson('/api/v1/customers/addresses', $headers)->assertOk();
        $defaults = collect($listed->json('data'))->where('is_default', true)->pluck('id')->all();
        $this->assertSame([$secondId], array_values($defaults));

        // Explicit default swap back.
        $actor()->postJson("/api/v1/customers/addresses/{$firstId}:default", [], $headers)
            ->assertOk()->assertJsonPath('data.is_default', true);

        // Deleting the default promotes the most recently updated survivor.
        $actor()->deleteJson("/api/v1/customers/addresses/{$firstId}", [], $headers)->assertOk();
        $after = $actor()->getJson('/api/v1/customers/addresses', $headers)->assertOk();
        $this->assertSame([$secondId], collect($after->json('data'))->where('is_default', true)->pluck('id')->all());

        // Update persists.
        $actor()->patchJson("/api/v1/customers/addresses/{$secondId}", ['city' => 'Pune'], $headers)
            ->assertOk()->assertJsonPath('data.city', 'Pune');

        $this->assertAuditRecorded($tenant, 'customers.address.added');
        $this->assertAuditRecorded($tenant, 'customers.address.removed');
    }

    public function test_cap_and_foreign_and_guests(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        // Guest first (actingAs persists).
        auth()->logout();
        $this->postJson('/api/v1/customers/addresses', $this->addressPayload(), $headers)
            ->assertUnauthorized();

        $actor = fn () => $this->actingAs($user);
        for ($i = 0; $i < CustomerAddress::MAX_PER_CUSTOMER; $i++) {
            $actor()->postJson('/api/v1/customers/addresses', $this->addressPayload(['label' => "place-{$i}"]), $headers)
                ->assertCreated();
        }
        $actor()->postJson('/api/v1/customers/addresses', $this->addressPayload(['label' => 'overflow']), $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        // Foreign address resolves to nothing (no existence leak).
        $other = $this->makeTenant();
        [$otherUser] = $this->actingAsMembership($other);
        $foreignId = $this->actingAs($otherUser)->postJson('/api/v1/customers/addresses', $this->addressPayload(), $this->tenantHeaders($other))
            ->assertCreated()->json('data.id');

        $actor()->getJson('/api/v1/customers/addresses', $headers)->assertOk();
        $actor()->patchJson("/api/v1/customers/addresses/{$foreignId}", ['city' => 'X'], $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $actor()->deleteJson("/api/v1/customers/addresses/{$foreignId}", [], $headers)->assertNotFound();
        $actor()->postJson("/api/v1/customers/addresses/{$foreignId}:default", [], $headers)->assertNotFound();
    }
}
