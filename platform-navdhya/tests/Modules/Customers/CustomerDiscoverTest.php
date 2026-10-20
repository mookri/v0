<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Customers\Models\SavedItem;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Discover feed (B6): recent/popular/recommended composition through
 * Contracts ports, popularity fallback, and novelty exclusion.
 */
final class CustomerDiscoverTest extends TenantIntegrationTestCase
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

    /**
     * @return list<string> skus created
     */
    private function seedCatalog(Tenant $tenant, array $skus): array
    {
        $this->resolveContext($tenant);
        try {
            foreach ($skus as $sku) {
                CatalogItem::factory()->create([
                    'sku' => $sku, 'name' => $sku, 'status' => CatalogItem::STATUS_ACTIVE,
                ]);
            }
        } finally {
            $this->clearContext();
        }

        return $skus;
    }

    private function seedBookings(Tenant $tenant, User $user, array $skus): void
    {
        $this->resolveContext($tenant);
        try {
            foreach ($skus as $sku) {
                Booking::factory()->create(['user_id' => $user->id, 'service_sku' => $sku]);
            }
        } finally {
            $this->clearContext();
        }
    }

    public function test_popular_ranked_and_recommended_excludes_seen(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $this->seedCatalog($tenant, ['SKU-A', 'SKU-B', 'SKU-C']);
        // A booked twice (by anyone in the tenant), B once.
        $this->seedBookings($tenant, $user, ['SKU-A', 'SKU-A', 'SKU-B']);

        $feed = $actor()->getJson('/api/v1/customers/discover', $headers)->assertOk();
        $popular = collect($feed->json('data.popular'))->pluck('title')->all();
        $this->assertSame('SKU-A', $popular[0]);
        $this->assertSame('SKU-B', $popular[1]);

        // Customer booked A and B → recommended surfaces only C.
        $recommended = collect($feed->json('data.recommended'))->pluck('title')->all();
        $this->assertContains('SKU-C', $recommended);
        $this->assertNotContains('SKU-A', $recommended);
        $this->assertNotContains('SKU-B', $recommended);

        $this->assertCount(3, $feed->json('data.recent'));
    }

    public function test_popular_falls_back_to_recent_without_bookings(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);

        $this->seedCatalog($tenant, ['SKU-A', 'SKU-B']);

        $feed = $this->actingAs($user)->getJson('/api/v1/customers/discover', $headers)->assertOk();
        $this->assertSame(
            collect($feed->json('data.recent'))->pluck('id')->all(),
            collect($feed->json('data.popular'))->pluck('id')->all()
        );
    }

    public function test_saved_items_excluded_from_recommended(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $this->seedCatalog($tenant, ['SKU-A', 'SKU-B']);
        $this->seedBookings($tenant, $user, ['SKU-A']);

        $this->resolveContext($tenant);
        try {
            $itemB = CatalogItem::query()->where('sku', 'SKU-B')->firstOrFail();
            SavedItem::factory()->create([
                'user_id' => $user->id, 'ref_type' => 'puja', 'ref_id' => (string) $itemB->getKey(),
            ]);
        } finally {
            $this->clearContext();
        }

        $feed = $actor()->getJson('/api/v1/customers/discover', $headers)->assertOk();
        $recommendedIds = collect($feed->json('data.recommended'))->pluck('id')->all();
        $this->assertNotContains((string) $itemB->getKey(), $recommendedIds);
    }

    public function test_guests_are_denied(): void
    {
        $tenant = $this->makeTenant();

        $this->getJson('/api/v1/customers/discover', $this->tenantHeaders($tenant))->assertUnauthorized();
    }
}
