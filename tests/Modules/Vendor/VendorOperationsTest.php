<?php

namespace Tests\Modules\Vendor;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Vendor\Models\VendorOrderTransition;
use App\Modules\Vendor\Models\VendorPayout;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorPayoutFactory;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Vendor operations vertical (P12e): products (Catalog ports behind the
 * partner surface — DRAFT start, per-tenant SKU conflict, archived
 * immutability as 409 state_forbidden), inventory (signed adjust that
 * never goes negative), orders (placement with snapshotted lines, linear
 * lifecycle with append-only transitions) and the read-only payouts stub
 * with cursor pages — every partner route ownership-scoped (403 foreign
 * same-tenant, 404 cross-tenant).
 */
final class VendorOperationsTest extends TenantIntegrationTestCase
{
    /**
     * @param  \App\Platform\Tenancy\Models\Tenant  $tenant
     * @param  array<string, string>  $headers
     */
    private function ownedVendor($tenant, User $user, array $headers): string
    {
        PartnerApplicationFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'identity_id' => (string) $user->getAuthIdentifier(),
            'track' => 'partner-vendor',
            'track_version' => 'partner-vendor-v1',
            'status' => 'APPROVED',
        ]);

        return (string) $this->actingAs($user)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Ops Store '.$user->getKey(),
        ], $headers)->assertCreated()->json('data.id');
    }

    public function test_products_start_draft_and_enforce_sku_conflicts(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $vendorId = $this->ownedVendor($tenant, $owner, $headers);
        $products = '/api/v1/vendor/partner/vendors/'.$vendorId.'/products';

        $created = $this->actingAs($owner)->postJson($products, [
            'sku' => 'SKU-1',
            'name' => 'Brass Diya',
            'price_minor' => 9900,
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('data.sku', 'SKU-1')
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.vendor_id', $vendorId)
            ->assertJsonPath('data.price_minor', 9900);

        $itemId = (string) $created->json('data.id');

        // Limit list: request_id meta only — products are not cursor-paged.
        $list = $this->actingAs($owner)->getJson($products, $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId);
        $this->assertArrayHasKey('request_id', $list->json('meta'));
        $this->assertArrayNotHasKey('page', $list->json('meta'));

        // Duplicate SKU (tenant-wide) → 409 conflict.
        $this->actingAs($owner)->postJson($products, [
            'sku' => 'SKU-1',
            'name' => 'Different Name',
        ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');

        // Validation failures → 422.
        $this->actingAs($owner)->postJson($products, [
            'sku' => 'SKU-2',
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        // Update: price changes, SKU stays put.
        $this->actingAs($owner)->patchJson($products.'/'.$itemId, [
            'price_minor' => 12900,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.price_minor', 12900)
            ->assertJsonPath('data.sku', 'SKU-1');

        // Unknown item → 404 not_found.
        $this->actingAs($owner)->patchJson($products.'/'.Str::ulid(), [
            'price_minor' => 1,
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        // Archived items are immutable → 409 state_forbidden.
        CatalogItem::withoutGlobalScopes()
            ->where('id', $itemId)
            ->update(['status' => CatalogItem::STATUS_ARCHIVED]);
        $this->actingAs($owner)->patchJson($products.'/'.$itemId, [
            'price_minor' => 13000,
        ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');

        // Foreign same-tenant → 403 on read and write.
        [$stranger] = $this->actingAsMembership($tenant);
        $this->actingAs($stranger)->getJson($products, $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');
        $this->actingAs($stranger)->postJson($products, [
            'sku' => 'SKU-X',
            'name' => 'Not Mine',
        ], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');

        // Cross-tenant → 404 (no existence leakage).
        $other = $this->makeTenant();
        [$outsider] = $this->actingAsMembership($other);
        $this->actingAs($outsider)->getJson($products, $this->tenantHeaders($other))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_inventory_adjust_is_scoped_and_never_negative(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $vendorId = $this->ownedVendor($tenant, $owner, $headers);
        $adjust = '/api/v1/vendor/partner/vendors/'.$vendorId.'/inventory:adjust';

        // First adjustment creates the row.
        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-1', 'delta' => 5], $headers)
            ->assertOk()
            ->assertJsonPath('data.object', 'vendor_inventory')
            ->assertJsonPath('data.sku', 'SKU-1')
            ->assertJsonPath('data.on_hand', 5)
            ->assertJsonPath('data.vendor_id', $vendorId);

        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-1', 'delta' => -3], $headers)
            ->assertOk()
            ->assertJsonPath('data.on_hand', 2);

        // Negative results conflict — existing and missing rows alike.
        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-1', 'delta' => -10], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict')
            ->assertJsonPath('error.message', 'Stock cannot go negative.');

        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-NEW', 'delta' => -1], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');

        // Validation failures → 422.
        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-1'], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
        $this->actingAs($owner)->postJson($adjust, ['sku' => 'SKU-1', 'delta' => 'abc'], $headers)
            ->assertStatus(422);

        // Cursor page on the list.
        $this->actingAs($owner)->getJson('/api/v1/vendor/partner/vendors/'.$vendorId.'/inventory', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.on_hand', 2)
            ->assertJsonStructure(['data', 'meta' => ['page' => ['limit', 'has_more', 'next_cursor']]]);

        // Ownership: foreign 403, cross-tenant 404.
        [$stranger] = $this->actingAsMembership($tenant);
        $this->actingAs($stranger)->postJson($adjust, ['sku' => 'SKU-1', 'delta' => 1], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');

        $other = $this->makeTenant();
        [$outsider] = $this->actingAsMembership($other);
        $this->actingAs($outsider)->getJson('/api/v1/vendor/partner/vendors/'.$vendorId.'/inventory', $this->tenantHeaders($other))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_order_lifecycle_appends_transitions_and_blocks_terminal_moves(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $vendorId = $this->ownedVendor($tenant, $owner, $headers);
        $orders = '/api/v1/vendor/partner/vendors/'.$vendorId.'/orders';

        $placed = $this->actingAs($owner)->postJson($orders, [
            'reference' => 'PO-1001',
            'customer_name' => 'Gita',
            'items' => [
                ['sku' => 'SKU-1', 'name' => 'Brass Diya', 'quantity' => 2, 'unit_minor' => 50000],
            ],
        ], $headers);

        $placed->assertCreated()
            ->assertJsonPath('data.status', 'NEW')
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonPath('data.total_minor', 100000)
            ->assertJsonPath('data.items.0.line_minor', 100000)
            ->assertJsonPath('data.items.0.quantity', 2);

        $orderId = (string) $placed->json('data.id');

        // Duplicate reference → 409 conflict; empty lines → 422.
        $this->actingAs($owner)->postJson($orders, [
            'reference' => 'PO-1001',
            'items' => [['sku' => 'SKU-1', 'name' => 'Brass Diya', 'quantity' => 1, 'unit_minor' => 50000]],
        ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict')
            ->assertJsonPath('error.message', 'Order reference already exists for this vendor.');

        $this->actingAs($owner)->postJson($orders, ['reference' => 'PO-1002', 'items' => []], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        // List rows omit line snapshots; detail resolves inside the vendor.
        $list = $this->actingAs($owner)->getJson($orders, $headers)
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'PO-1001');
        $this->assertArrayNotHasKey('items', $list->json('data.0'));

        $this->actingAs($owner)->getJson($orders.'/'.Str::ulid(), $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->actingAs($owner)->getJson($orders.'/'.$orderId, $headers)
            ->assertOk()
            ->assertJsonPath('data.items.0.sku', 'SKU-1');

        // Linear lifecycle to DELIVERED, then blocked.
        foreach (['PROCESSING', 'DISPATCHED', 'DELIVERED'] as $status) {
            $this->actingAs($owner)->postJson($orders.'/'.$orderId.':advance', [], $headers)
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $this->actingAs($owner)->postJson($orders.'/'.$orderId.':advance', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden')
            ->assertJsonPath('error.message', 'Order is already delivered.');

        // Cancel from a cancellable state; terminal moves stay blocked.
        $second = (string) $this->actingAs($owner)->postJson($orders, [
            'reference' => 'PO-1002',
            'items' => [['sku' => 'SKU-1', 'name' => 'Brass Diya', 'quantity' => 1, 'unit_minor' => 10000]],
        ], $headers)->assertCreated()->json('data.id');

        $this->actingAs($owner)->postJson($orders.'/'.$second.':cancel', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED');

        $this->actingAs($owner)->postJson($orders.'/'.$second.':advance', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.message', 'Cancelled orders cannot advance.');
        $this->actingAs($owner)->postJson($orders.'/'.$second.':cancel', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.message', 'Only new or processing orders can be cancelled.');

        // Append-only transition audit for the delivered order.
        $transitions = VendorOrderTransition::withoutGlobalScopes()
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $this->assertSame(
            ['NEW', 'PROCESSING', 'DISPATCHED', 'DELIVERED'],
            $transitions->pluck('to_status')->all(),
        );
        $this->assertNull($transitions->first()?->from_status);
        $this->assertSame((int) $owner->getAuthIdentifier(), (int) $transitions->first()?->actor_user_id);

        // Another vendor's order under this path is indistinguishable from absent.
        $otherVendor = $this->ownedVendor($tenant, $owner, $headers);
        $this->actingAs($owner)->getJson('/api/v1/vendor/partner/vendors/'.$otherVendor.'/orders/'.$orderId, $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        // Foreign same-tenant → 403 on list and lifecycle verbs.
        [$stranger] = $this->actingAsMembership($tenant);
        $this->actingAs($stranger)->getJson($orders, $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');
        $this->actingAs($stranger)->postJson($orders.'/'.$orderId.':advance', [], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');
    }

    public function test_payouts_stub_is_owner_scoped_with_cursor_pages(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant, ['vendor:payout:view']);
        $headers = $this->tenantHeaders($tenant);
        $vendorId = $this->ownedVendor($tenant, $owner, $headers);
        $payouts = '/api/v1/vendor/partner/vendors/'.$vendorId.'/payouts';

        // Empty stub page before settlement.
        $this->actingAs($owner)->getJson($payouts, $headers)
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonStructure(['meta' => ['page' => ['limit', 'has_more', 'next_cursor']]]);

        $paid = VendorPayoutFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => $vendorId,
            'status' => VendorPayout::STATUS_PAID,
            'amount_minor' => 123450,
            'reference' => 'PAY-1',
        ]);
        VendorPayoutFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => $vendorId,
            'period' => now()->subMonth()->format('Y-m'),
            'status' => VendorPayout::STATUS_PENDING,
            'amount_minor' => 50000,
            'reference' => null,
        ]);

        $all = $this->actingAs($owner)->getJson($payouts, $headers)->assertOk();
        $this->assertEqualsCanonicalizing(
            [123450, 50000],
            array_column($all->json('data'), 'amount_minor'),
        );

        // Status filter narrows the page.
        $this->actingAs($owner)->getJson($payouts.'?status=PAID', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $paid->getKey())
            ->assertJsonPath('data.0.status', 'PAID');

        // Cursor roundtrip: limit=1 pages through both rows.
        $first = $this->actingAs($owner)->getJson($payouts.'?limit=1', $headers)->assertOk();
        $this->assertCount(1, $first->json('data'));
        $this->assertTrue((bool) $first->json('meta.page.has_more'));
        $cursor = (string) $first->json('meta.page.next_cursor');
        $this->assertNotSame('', $cursor);

        $second = $this->actingAs($owner)->getJson($payouts.'?limit=1&cursor='.urlencode($cursor), $headers)
            ->assertOk();
        $this->assertCount(1, $second->json('data'));
        $this->assertNotSame($first->json('data.0.id'), $second->json('data.0.id'));

        // Ownership: foreign 403, cross-tenant 404.
        [$stranger] = $this->actingAsMembership($tenant);
        $this->actingAs($stranger)->getJson($payouts, $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');

        $other = $this->makeTenant();
        [$outsider] = $this->actingAsMembership($other);
        $this->actingAs($outsider)->getJson($payouts, $this->tenantHeaders($other))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied');
    }
}
