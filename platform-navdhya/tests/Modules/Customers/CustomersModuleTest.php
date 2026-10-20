<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Identity\Contracts\TrackSeeder;
use App\Platform\Modules\ModuleRegistry;
use Tests\TenantIntegrationTestCase;

/**
 * Customers module skeleton (B1): registry discovery, customer-track
 * seeding (buyer profile) with delegation to onboarding for supply
 * tracks, and the phone-identity columns on central users.
 */
final class CustomersModuleTest extends TenantIntegrationTestCase
{
    public function test_modules_are_discovered(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertTrue($registry->has('customers'));
        $this->assertTrue($registry->has('booking'));
        $this->assertSame('/api/v1/customers', $registry->find('customers')?->api['basePath'] ?? null);
        $this->assertSame('/api/v1/booking', $registry->find('booking')?->api['basePath'] ?? null);
    }

    public function test_customer_track_seeds_buyer_profile(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant);

        try {
            $result = app(TrackSeeder::class)->seed('customer', (string) $user->getAuthIdentifier());
            $rowKey = (string) BuyerProfile::query()->firstOrFail()->getKey();
        } finally {
            $this->clearContext();
        }

        $this->assertSame($result['buyer_profile_id'], $rowKey);
        $this->assertDatabaseHas('buyer_profiles', [
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $user->getAuthIdentifier(),
            'status' => BuyerProfile::STATUS_ACTIVE,
        ], 'tenant_shared');

        // Repeat seeding reuses the row (idempotent invite re-acceptance).
        $this->resolveContext($tenant);
        try {
            $again = app(TrackSeeder::class)->seed('customer', (string) $user->getAuthIdentifier());
        } finally {
            $this->clearContext();
        }
        $this->assertSame($result['buyer_profile_id'], $again['buyer_profile_id']);
    }

    public function test_supply_tracks_delegate_to_onboarding(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant);

        try {
            $result = app(TrackSeeder::class)->seed('pandit', (string) $user->getAuthIdentifier());

            // Delegation seeds nothing customer-side (scoped read: zero rows
            // in THIS tenant; unresolved contexts match nothing by design).
            $this->assertSame(0, BuyerProfile::query()->count());
        } finally {
            $this->clearContext();
        }

        $this->assertArrayHasKey('application_id', $result);
    }

    public function test_users_carry_phone_identity(): void
    {
        $user = User::factory()->create(['phone' => '+911234567890']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '+911234567890'], 'central');
        $this->assertNull($user->phone_verified_at);
    }
}
