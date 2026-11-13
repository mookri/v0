<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Catalog\Models\CatalogItem;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\PlatformScope;
use App\Platform\Tenancy\TenantContext;
use InvalidArgumentException;
use RuntimeException;
use Tests\TenantIntegrationTestCase;

final class PlatformScopeTest extends TenantIntegrationTestCase
{

    public function test_run_requires_non_empty_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlatformScope::run('   ', static fn () => true);
    }

    public function test_run_with_tenant_id_scopes_queries_and_restores_previous_context(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $context = app(TenantContext::class);
        $context->clear();
        $context->set((string) $tenantA->getKey(), membershipId: 'mem_a', orgId: 'org_a', actor: ['id' => 1]);

        CatalogItem::factory()->create(['tenant_id' => (string) $tenantA->getKey(), 'sku' => 'SKU-A1']);
        PlatformScope::run('seed_b', static function () use ($tenantB): void {
            CatalogItem::factory()->create(['tenant_id' => (string) $tenantB->getKey(), 'sku' => 'SKU-B1']);
        }, tenantId: (string) $tenantB->getKey());

        // Inside Tenant A context before call -> sees only SKU-A1.
        $this->assertSame(['SKU-A1'], CatalogItem::query()->pluck('sku')->all());

        // Scoped PlatformScope switch to B sees only SKU-B1, then restores A's full metadata.
        $bSkus = PlatformScope::run('inspect_b', static fn () => CatalogItem::query()->pluck('sku')->all(), tenantId: (string) $tenantB->getKey());
        $this->assertSame(['SKU-B1'], $bSkus);

        $this->assertTrue($context->isResolved());
        $this->assertSame((string) $tenantA->getKey(), $context->tenantId());
        $this->assertSame('mem_a', $context->membershipId());
        $this->assertSame('org_a', $context->orgId());
        $this->assertSame(['SKU-A1'], CatalogItem::query()->pluck('sku')->all());
    }

    public function test_run_restores_context_cleanly_even_when_closure_throws(): void
    {
        $tenantA = Tenant::factory()->create();
        $context = app(TenantContext::class);
        $context->clear();
        $context->set((string) $tenantA->getKey());

        try {
            PlatformScope::run('failing_cross_tenant_task', static function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('Expected exception was not rethrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertFalse(PlatformScope::isSystemBypassActive());
        $this->assertNull(PlatformScope::currentReason());
        $this->assertTrue($context->isResolved());
        $this->assertSame((string) $tenantA->getKey(), $context->tenantId());
    }

    public function test_run_with_null_tenant_allows_cross_tenant_system_read_and_nested_tenant_override(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        PlatformScope::run('seed_a', static function () use ($tenantA): void {
            CatalogItem::factory()->create(['tenant_id' => (string) $tenantA->getKey(), 'sku' => 'SYS-A']);
        }, tenantId: (string) $tenantA->getKey());

        PlatformScope::run('seed_b', static function () use ($tenantB): void {
            CatalogItem::factory()->create(['tenant_id' => (string) $tenantB->getKey(), 'sku' => 'SYS-B']);
        }, tenantId: (string) $tenantB->getKey());

        $context = app(TenantContext::class);
        $context->clear();

        // Ambient unresolved context returns zero rows (fail-closed).
        $this->assertSame([], CatalogItem::query()->pluck('sku')->all());

        $results = PlatformScope::run('cross_tenant_audit', function () use ($tenantA): array {
            $all = CatalogItem::query()->orderBy('sku')->pluck('sku')->all();
            $onlyA = PlatformScope::run('inner_scoped_check', static fn () => CatalogItem::query()->pluck('sku')->all(), tenantId: (string) $tenantA->getKey());
            $stillAll = CatalogItem::query()->orderBy('sku')->pluck('sku')->all();

            return compact('all', 'onlyA', 'stillAll');
        });

        $this->assertSame(['SYS-A', 'SYS-B'], $results['all']);
        $this->assertSame(['SYS-A'], $results['onlyA']);
        $this->assertSame(['SYS-A', 'SYS-B'], $results['stillAll']);
        $this->assertFalse($context->isResolved());
        $this->assertFalse(PlatformScope::isSystemBypassActive());
    }
}
