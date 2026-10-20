<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Connection resolver (TEN-012): maps a tenant to its data connection.
 *
 * - `shared` → `tenant_shared`.
 * - `dedicated` → decrypts `db_config_ciphertext` (APP_KEY today, vault
 *   path per security baseline §3), registers a runtime connection
 *   `tenant_ded_<id>`, returns it. Schema is identical in both modes.
 * - Anything else fails closed: unknown db_mode never defaults.
 *
 * db_mode reads are cached 60s, busted on `platform.tenant.promoted`.
 */
final class ConnectionResolver
{
    public const SHARED_CONNECTION = 'tenant_shared';

    public function for(Tenant $tenant): string
    {
        $mode = (string) ($tenant->db_mode ?? 'shared');

        if ($mode === 'shared') {
            return self::SHARED_CONNECTION;
        }

        if ($mode !== 'dedicated') {
            throw new TenantContextMissingException("unknown db_mode {$mode} for tenant {$tenant->getKey()}");
        }

        $name = 'tenant_ded_'.$tenant->getKey();

        if ($this->isRegistered($name)) {
            return $name;
        }

        $config = $this->decryptConfig($tenant);
        config()->set("database.connections.{$name}", $config);

        return $name;
    }

    public function cachedFor(Tenant $tenant): string
    {
        $key = "tenant:conn:{$tenant->getKey()}";

        return Cache::remember($key, 60, fn (): string => $this->for($tenant));
    }

    public static function bust(Tenant $tenant): void
    {
        Cache::forget("tenant:conn:{$tenant->getKey()}");
    }

    private function isRegistered(string $name): bool
    {
        return config()->has("database.connections.{$name}");
    }

    /**
     * @return array<string, mixed>
     */
    private function decryptConfig(Tenant $tenant): array
    {
        $ciphertext = $tenant->db_config_ciphertext;
        if (! is_string($ciphertext) || $ciphertext === '') {
            throw new TenantContextMissingException("dedicated tenant {$tenant->getKey()} has no connection config");
        }

        try {
            $config = Crypt::decrypt($ciphertext);
        } catch (\Throwable) {
            throw new TenantContextMissingException("dedicated tenant {$tenant->getKey()} has corrupt connection config");
        }

        if (! is_array($config)) {
            throw new TenantContextMissingException("dedicated tenant {$tenant->getKey()} has invalid connection config");
        }

        return $config;
    }
}
