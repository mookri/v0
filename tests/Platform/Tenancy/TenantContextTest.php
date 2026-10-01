<?php

namespace Tests\Platform\Tenancy;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * TenantContext hardening (TEN-007): set-exactly-once per scope,
 * connectionName + actor carriage, full reset on clear.
 */
final class TenantContextTest extends TestCase
{
    public function test_set_once_then_clear_allows_reuse(): void
    {
        $context = new TenantContext();
        $context->set('t1', 'm1', 'o1', 'tenant_shared', ['user_id' => 'u1']);

        $this->assertTrue($context->isResolved());
        $this->assertSame('t1', $context->tenantId());
        $this->assertSame('m1', $context->membershipId());
        $this->assertSame('o1', $context->orgId());
        $this->assertSame('tenant_shared', $context->connectionName());
        $this->assertSame(['user_id' => 'u1'], $context->actor());

        $this->expectException(TenantContextMissingException::class);
        $context->set('t2');
    }

    public function test_clear_resets_everything_including_new_fields(): void
    {
        $context = new TenantContext();
        $context->set('t1', 'm1', 'o1', 'tenant_shared', ['user_id' => 'u1']);
        $context->clear();

        $this->assertFalse($context->isResolved());
        $this->assertNull($context->tenantId());
        $this->assertNull($context->connectionName());
        $this->assertNull($context->actor());

        // Reuse after clear is legal (new scope).
        $context->set('t2');
        $this->assertTrue($context->isResolved());
        $this->assertNull($context->actor());
    }

    public function test_unresolved_context_reads_null(): void
    {
        $context = new TenantContext();

        $this->assertFalse($context->isResolved());
        $this->assertNull($context->tenantId());
        $this->assertNull($context->membershipId());
        $this->assertNull($context->orgId());
        $this->assertNull($context->connectionName());
        $this->assertNull($context->actor());
    }
}
