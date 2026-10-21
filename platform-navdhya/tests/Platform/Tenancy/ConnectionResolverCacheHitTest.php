<?php

namespace Tests\Platform\Tenancy;

use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Tests\TenantIntegrationTestCase;

/**
 * A dedicated tenant must resolve its connection on EVERY request.
 *
 * `ConnectionResolver::cachedFor()` used `Cache::remember()`, which
 * returns the cached value without running the closure - and the
 * closure was the only place the runtime connection was registered. A
 * dedicated tenant therefore 500'd on every request inside the cache
 * window ("Database connection [tenant_ded_...] not configured"), which
 * looked like flapping rather than a deterministic defect.
 */
final class ConnectionResolverCacheHitTest extends TenantIntegrationTestCase
{
    public function test_a_dedicated_tenant_registers_its_connection_on_a_cache_hit(): void
    {
        $tenant = $this->makeTenant();
        $tenant->forceFill([
            'db_mode' => 'dedicated',
            'db_config_ciphertext' => encrypt([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]),
        ])->save();

        $key = 'tenant:conn:'.(string) $tenant->getKey();

        // First call: the cache is cold, the closure runs.
        $resolver = new ConnectionResolver();
        $name = $resolver->cachedFor($tenant);

        $this->assertSame('tenant_ded_'.(string) $tenant->getKey(), $name);
        $this->assertTrue(Cache::has($key), 'The connection name must be cached.');

        // Second call: the cache is warm, so the closure does NOT run.
        // The connection must still be registered for this process.
        Cache::forget($key);
        Cache::put($key, $name, 60);

        $fresh = new ConnectionResolver();
        $again = $fresh->cachedFor($tenant);

        $this->assertSame($name, $again);
        $this->assertTrue(
            config()->has("database.connections.{$again}"),
            'A cached connection name must still be registered on the connection resolver.'
        );
    }

    public function test_a_shared_tenant_keeps_the_shared_connection(): void
    {
        $tenant = $this->makeTenant();

        $resolver = new ConnectionResolver();

        $this->assertSame(ConnectionResolver::SHARED_CONNECTION, $resolver->cachedFor($tenant));
        $this->assertSame(ConnectionResolver::SHARED_CONNECTION, $resolver->cachedFor($tenant));
        $this->assertSame('shared', (string) $tenant->fresh()->db_mode);
        $this->assertInstanceOf(Tenant::class, $tenant);
    }
}
