<?php

namespace Tests\Modules\Customers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Activity feed (B6): mutations across auth/profile/address/saved/
 * settings surfaces land as cursor-paginated, kind-filterable history
 * scoped to the caller — never kernel audit rows.
 */
final class CustomerActivityTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
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
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    public function test_mutations_land_in_scoped_paginated_feed(): void
    {
        [$tenant, $user] = $this->customer();
        $headers = $this->tenantHeaders($tenant);
        $actor = fn () => $this->actingAs($user);

        $actor()->patchJson('/api/v1/customers/profile', ['locale' => 'hi'], $headers)->assertOk();
        $actor()->postJson('/api/v1/customers/addresses', [
            'label' => 'home', 'line1' => '1 Main', 'city' => 'Mumbai',
            'state' => 'Maharashtra', 'postal_code' => '400001',
        ], $headers)->assertCreated();
        $actor()->postJson('/api/v1/customers/saved', [
            'ref_type' => 'puja', 'ref_id' => (string) Str::ulid(),
        ], $headers)->assertCreated();

        $first = $actor()->getJson('/api/v1/customers/activity?limit=2', $headers)->assertOk();
        $this->assertCount(2, $first->json('data'));
        $this->assertTrue($first->json('meta.page.has_more'));
        $this->assertArrayHasKey('label', $first->json('data.0'));
        // No kernel audit internals leak through the feed shape.
        $this->assertArrayNotHasKey('hash', $first->json('data.0'));
        $this->assertArrayNotHasKey('actor', $first->json('data.0'));

        $cursor = $first->json('meta.page.next_cursor');
        $second = $actor()->getJson('/api/v1/customers/activity?limit=2&cursor='.urlencode($cursor), $headers)->assertOk();
        $this->assertCount(1, $second->json('data'));

        $kinds = collect($first->json('data'))->pluck('kind')->merge(collect($second->json('data'))->pluck('kind'));
        $this->assertContains('profile_updated', $kinds->all());
        $this->assertContains('address_added', $kinds->all());
        $this->assertContains('saved_added', $kinds->all());

        $filtered = $actor()->getJson('/api/v1/customers/activity?kind=saved_added', $headers)->assertOk();
        $this->assertSame(['saved_added'], collect($filtered->json('data'))->pluck('kind')->unique()->all());

        // Unknown kinds are ignored, not errors.
        $actor()->getJson('/api/v1/customers/activity?kind=nope', $headers)->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_feed_is_scoped_and_guests_denied(): void
    {
        [$tenant, $user] = $this->customer();

        auth()->logout();
        $this->getJson('/api/v1/customers/activity', $this->tenantHeaders($tenant))->assertUnauthorized();

        // A second customer in the same tenant sees none of the first's rows.
        $other = User::factory()->create();
        $other->forceFill(['external_id' => 'cust_'.strtolower((string) Str::ulid())])->save();
        $this->makeMembership($tenant, $other);
        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $other->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user)->patchJson('/api/v1/customers/profile', ['locale' => 'hi'], $this->tenantHeaders($tenant))->assertOk();

        $empty = $this->actingAs($other)->getJson('/api/v1/customers/activity', $this->tenantHeaders($tenant))->assertOk();
        $this->assertSame([], $empty->json('data'));
    }
}
