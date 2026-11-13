<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Catalog\Models\CatalogItem;
use App\Platform\Authz\AuthorizationDecision;
use App\Platform\Authz\Contracts\DecidingStage;
use App\Platform\Authz\DecisionCache;
use App\Platform\Identity\Http\Middleware\EnforceTokenAudience;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TenantIntegrationTestCase;

final class TenantIsolationAndRevocationTest extends TenantIntegrationTestCase
{
    public function test_cross_tenant_read_and_missing_header_fail_closed(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        [$userA] = $this->actingAsMembership($tenantA, ['catalog:item:manage', 'catalog:item:view']);
        [$userB] = $this->actingAsMembership($tenantB, ['catalog:item:manage', 'catalog:item:view']);

        $created = $this->actingAs($userA)->postJson('/api/v1/catalog/items', [
            'sku' => 'ISO-A-1',
            'name' => 'Tenant A Item',
            'price_minor' => 15000,
        ], $this->tenantHeaders($tenantA))->assertCreated();

        $itemId = $created->json('data.id');

        // Member of Tenant B attempting to read Tenant A via X-Tenant-Id is rejected.
        $this->actingAs($userB)
            ->getJson("/api/v1/catalog/items/{$itemId}", $this->tenantHeaders($tenantA))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'membership_inactive');

        // Member of Tenant B querying Tenant A's item ID in Tenant B returns 404.
        $this->actingAs($userB)
            ->getJson("/api/v1/catalog/items/{$itemId}", $this->tenantHeaders($tenantB))
            ->assertNotFound();

        // Missing X-Tenant-Id header fails closed.
        $this->actingAs($userA)
            ->getJson('/api/v1/catalog/items')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'tenant_header_missing');
    }

    public function test_suspended_tenant_is_rejected_even_with_active_membership(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:view']);

        $tenant->forceFill(['state' => Tenant::STATE_SUSPENDED])->save();

        $this->actingAs($user)
            ->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'tenant_suspended');
    }

    public function test_suspending_membership_invalidates_decision_cache_immediately(): void
    {
        $tenant = $this->makeTenant();
        [$user, $membership] = $this->actingAsMembership($tenant, ['catalog:item:view']);

        // Warm DecisionCache for this membership.
        /** @var DecisionCache $cache */
        $cache = app(DecisionCache::class);
        $decision = new AuthorizationDecision(
            decision: AuthorizationDecision::DECISION_ALLOW,
            decidingStage: DecidingStage::AllowGrant,
            capability: 'catalog:item:view',
            principalId: (string) $membership->getKey(),
        );
        $cache->put((string) $tenant->getKey(), 'human', (string) $membership->getKey(), 'catalog:item:view', [], $decision);

        $this->assertNotNull(
            $cache->get((string) $tenant->getKey(), 'human', (string) $membership->getKey(), 'catalog:item:view', []),
        );

        // Suspend membership -> observer bumps tenant generation in DecisionCache immediately.
        $membership->forceFill(['state' => TenantMembership::STATE_SUSPENDED])->save();

        $this->assertNull(
            $cache->get((string) $tenant->getKey(), 'human', (string) $membership->getKey(), 'catalog:item:view', []),
        );

        // Live API call also fails closed with membership_inactive.
        $this->actingAs($user)
            ->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_authenticated_api_responses_carry_private_no_store_while_public_catalog_remains_public(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:view']);

        CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // Authenticated read carries private, no-store and Vary: Cookie, Authorization.
        $authedResp = $this->actingAs($user)
            ->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))
            ->assertOk();

        $cacheControl = (string) $authedResp->headers->get('Cache-Control', '');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $vary = (string) $authedResp->headers->get('Vary', '');
        $this->assertStringContainsString('Cookie', $vary);
        $this->assertStringContainsString('Authorization', $vary);

        // Public catalog browse retains explicit public max-age=60 caching.
        $publicResp = $this->getJson('/api/v1/catalog/public/items?tenant_id='.$tenant->getKey())
            ->assertOk();
        $this->assertStringContainsString('public', (string) $publicResp->headers->get('Cache-Control', ''));
    }

    public function test_enforce_token_audience_rejects_mismatched_surface_audience(): void
    {
        $middleware = app(EnforceTokenAudience::class);

        $deniedRequest = Request::create('/api/v1/ops/stats', 'GET');
        $deniedRequest->attributes->set('actor', [
            'user_id' => 'user_1',
            'aud' => 'navdhya-customer',
        ]);

        try {
            $middleware->handle($deniedRequest, static fn () => new Response('ok', 200), 'navdhya-dashboard', 'ops');
            $this->fail('Expected audience_mismatch CodedHttpException was not thrown.');
        } catch (\App\Platform\Api\CodedHttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('audience_mismatch', $e->envelopeCode);
        }

        $allowedRequest = Request::create('/api/v1/ops/stats', 'GET');
        $allowedRequest->attributes->set('actor', [
            'user_id' => 'user_1',
            'aud' => 'navdhya-dashboard',
        ]);

        $response = $middleware->handle(
            $allowedRequest,
            static fn () => new Response('ok', 200),
            'navdhya-dashboard',
            'ops',
        );
        $this->assertSame(200, $response->getStatusCode());
    }
}
