<?php

namespace Tests\TenantIsolation\Concerns;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Tenant Isolation Suite matrix (TEN-018, doc 14 §5 item 1): for every
 * BelongsToTenant model, seeds tenants A/B, creates a row in A, and
 * asserts zero cross-tenant visibility across reads, counts, updates and
 * deletes — plus fail-closed behavior on an unresolved TenantContext.
 *
 * The using class MUST extend Tests\TenantIntegrationTestCase.
 */
trait AssertTenantIsolation
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  Closure(): array<string, mixed>  $attributes  Runs in tenant A's context; may seed FK dependencies.
     * @param  array<string, mixed>  $updateProbe  Column update used to probe cross-tenant writes.
     */
    protected function assertTenantIsolationMatrix(string $modelClass, Closure $attributes, array $updateProbe): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // Create the row in tenant A's context. The attribute set is
        // captured so the fail-closed create attempt below reuses the same
        // (already-seeded) FK dependencies.
        $this->resolveContext($tenantA);
        $attributeSet = $attributes();
        $row = $modelClass::create($attributeSet);

        PHPUnit::assertSame(
            (string) $tenantA->getKey(),
            (string) $row->getAttribute('tenant_id'),
            'tenant_id must be stamped from the ambient TenantContext on create.',
        );
        PHPUnit::assertNotNull($modelClass::find($row->getKey()));
        PHPUnit::assertSame(1, $modelClass::count());

        // Invisible + untouchable from tenant B.
        $this->resolveContext($tenantB);
        PHPUnit::assertNull($modelClass::find($row->getKey()), 'Cross-tenant read must return nothing.');
        PHPUnit::assertSame(0, $modelClass::count(), 'Cross-tenant count must be zero.');
        PHPUnit::assertSame(
            0,
            $modelClass::whereKey($row->getKey())->update($updateProbe),
            'Cross-tenant update must affect zero rows.',
        );
        PHPUnit::assertSame(
            0,
            $modelClass::whereKey($row->getKey())->delete(),
            'Cross-tenant delete must affect zero rows.',
        );

        // The row is still intact for its owner.
        $this->resolveContext($tenantA);
        PHPUnit::assertNotNull($modelClass::find($row->getKey()));

        // Fail-closed: an unresolved context yields nothing and refuses writes.
        $this->clearContext();
        PHPUnit::assertNull($modelClass::find($row->getKey()), 'Unresolved context must fail closed on reads.');
        PHPUnit::assertSame(0, $modelClass::count(), 'Unresolved context must fail closed on counts.');

        try {
            $modelClass::create($attributeSet);
            PHPUnit::fail('Creating '.(new $modelClass())->getTable().' outside a resolved context must throw TenantContextMissingException.');
        } catch (TenantContextMissingException) {
            // Expected: fail-closed write path (TEN-009).
        }

        $this->clearContext();
    }
}
