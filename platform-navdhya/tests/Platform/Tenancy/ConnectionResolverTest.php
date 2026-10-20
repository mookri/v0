<?php

namespace Tests\Platform\Tenancy;

use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Support\Facades\Crypt;
use Tests\TenantIntegrationTestCase;

/**
 * ConnectionResolver (TEN-012): shared tenants route to tenant_shared,
 * dedicated tenants decrypt + register runtime connections, unknown modes
 * and corrupt configs fail closed. Context carries the resolved name.
 */
final class ConnectionResolverTest extends TenantIntegrationTestCase
{
    public function test_shared_tenants_resolve_to_shared_connection(): void
    {
        $tenant = $this->makeTenant(['db_mode' => 'shared']);
        $resolver = new ConnectionResolver();

        $this->assertSame('tenant_shared', $resolver->for($tenant));
        $this->assertSame('tenant_shared', $resolver->cachedFor($tenant));
    }

    public function test_unknown_db_mode_fails_closed(): void
    {
        $tenant = $this->makeTenant(['db_mode' => 'weird']);
        $resolver = new ConnectionResolver();

        $this->expectException(TenantContextMissingException::class);
        $resolver->for($tenant);
    }

    public function test_dedicated_tenants_decrypt_and_register_runtime_connection(): void
    {
        $tenant = $this->makeTenant(['db_mode' => 'shared']);
        $tenant->forceFill([
            'db_mode' => 'dedicated',
            'db_config_ciphertext' => Crypt::encrypt(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']),
        ])->save();

        $resolver = new ConnectionResolver();
        $name = $resolver->for($tenant);

        $this->assertSame('tenant_ded_'.$tenant->getKey(), $name);
        $this->assertTrue(config()->has("database.connections.{$name}"));
        // Second call reuses the registration.
        $this->assertSame($name, $resolver->for($tenant));

        ConnectionResolver::bust($tenant);
        $this->assertSame($name, $resolver->cachedFor($tenant));
    }

    public function test_dedicated_without_config_fails_closed(): void
    {
        $tenant = $this->makeTenant(['db_mode' => 'dedicated']);
        $resolver = new ConnectionResolver();

        $this->expectException(TenantContextMissingException::class);
        $resolver->for($tenant);
    }

    public function test_audit_log_follows_context_connection(): void
    {
        $log = new \App\Platform\Audit\AuditLog();
        $this->assertSame('tenant_shared', $log->getConnectionName());

        app(TenantContext::class)->set('t1', null, null, 'tenant_ded_t1');
        $this->assertSame('tenant_ded_t1', (new \App\Platform\Audit\AuditLog())->getConnectionName());

        app(TenantContext::class)->clear();
    }
}
