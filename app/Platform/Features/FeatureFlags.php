<?php

namespace App\Platform\Features;

use App\Platform\Audit\AuditWriter;
use App\Platform\Features\Exceptions\UnknownFeatureFlagException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FeatureFlags kernel service (doc 07 §10) — the reserved
 * `platform.feature_flags` binding consulted by ModuleRegistry for
 * per-tenant module gating and by domain code for feature switches
 * (PAYMENTS_ENABLED, NOTIFY_EMAIL_ENABLED, ops.outbox.pause).
 *
 * Evaluation (doc 07 §10.1): tenant override ?? plan bundle
 * (tenants.plan_code → plans.flag_bundle, flat {code: bool}) ??
 * feature_flags.default.
 * An undefined flag resolves to $defaultWhenUndefined — false (fail
 * closed) for domain checks, true for module gating (the registry's
 * documented default-safe branch: a flagged module stays enabled until
 * something turns it off).
 *
 * Rules (doc 07 §10.2): default-safe branch at every call site (rule 1);
 * flags are not settings — no org/user scope (rule 2); changes are
 * recorded as `platform.flags.changed` and take effect within 60 s
 * (rule 3 — TTL on both cache layers, plus explicit forget on change);
 * ops flags are operator-only (rule 4 — the console flags:* surface is
 * the only mutation path; there is no tenant HTTP surface for them).
 *
 * Audit scope: audit rows are tenant-scoped and hash-chained per tenant
 * (K-025), and these tables live on the CENTRAL connection, so a tenant
 * override is audited into the target tenant's chain (when the ambient
 * context matches it), while a global default change — central state,
 * changed from console scope with no tenant — is recorded via
 * `Log::info('platform.flags.changed')` plus the row's updated_at;
 * deploy-time defaults ride migrations/seed and are reviewed in git.
 */
final class FeatureFlags
{
    /**
     * doc 07 §10.2 rule 3: changes take effect within 60 s.
     */
    public const CACHE_TTL_SECONDS = 60;

    private const DEFAULTS_CACHE_KEY = 'feature_flags.defaults';

    public function __construct(
        private readonly TenantContext $context,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * Resolve a flag. Tenant scope: explicit $tenant (string id or a
     * model with getKey()) wins; null consults the ambient TenantContext
     * when resolved, else evaluates the global default only.
     *
     * @param  mixed  $tenant  string tenant id | object with getKey() | null (ambient)
     * @param  bool  $defaultWhenUndefined  value for a flag with no feature_flags row
     */
    public function enabled(string $code, mixed $tenant = null, bool $defaultWhenUndefined = false): bool
    {
        $defaults = $this->defaults();
        $tenantId = $this->tenantIdFor($tenant);

        if ($tenantId !== null) {
            $scope = $this->tenantScope($tenantId);

            $override = $scope['overrides'][$code] ?? null;
            if (is_bool($override)) {
                return $override;
            }

            $bundle = $scope['bundle'][$code] ?? null;
            if (is_bool($bundle)) {
                return $bundle;
            }
        }

        if (isset($defaults[$code])) {
            return (bool) $defaults[$code]['default'];
        }

        return $defaultWhenUndefined;
    }

    /**
     * Change a global default (console/ops path). Requires an existing
     * row — flags are seeded by migration, never invented by a typo.
     * Takes effect immediately (defaults cache forgotten).
     */
    public function setDefault(string $code, bool $enabled): void
    {
        $before = $this->defaultRow($code)['default'] ?? null;

        if ($before === null) {
            throw new UnknownFeatureFlagException($code);
        }

        DB::connection('central')->table('feature_flags')
            ->where('code', $code)
            ->update(['default' => $enabled, 'updated_at' => now()]);

        Cache::forget(self::DEFAULTS_CACHE_KEY);

        // Central state: no tenant chain can hold this row (see class
        // docblock) — operator visibility via the application log.
        Log::info('platform.flags.changed', [
            'scope' => 'global',
            'code' => $code,
            'before' => $before,
            'after' => $enabled,
        ]);
    }

    /**
     * Set (or with $enabled = null, clear) a per-tenant override. May
     * target any code — including `module.*` flags declared in module
     * manifests, which carry no seeded row. Audited into the tenant's
     * chain when the ambient context is that tenant (console command
     * resolves it first). Takes effect immediately.
     */
    public function setOverride(string $code, ?bool $enabled, string $tenantId): void
    {
        $table = DB::connection('central')->table('tenant_feature_flags');
        $before = $table->where('tenant_id', $tenantId)->where('code', $code)->value('enabled');

        $table->where('tenant_id', $tenantId)->where('code', $code)->delete();

        if ($enabled !== null) {
            $table->insert([
                'id' => (string) Str::ulid(),
                'code' => $code,
                'tenant_id' => $tenantId,
                'enabled' => $enabled,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget($this->tenantCacheKey($tenantId));

        Log::info('platform.flags.changed', [
            'scope' => 'tenant',
            'tenant_id' => $tenantId,
            'code' => $code,
            'before' => $before === null ? null : (bool) $before,
            'after' => $enabled,
        ]);

        if ($this->context->isResolved() && $this->context->tenantId() === $tenantId) {
            $this->audit->record(
                'platform.flags.changed',
                ['module' => 'platform', 'resource_type' => 'feature_flag', 'resource_id' => $code],
                ['enabled' => $before === null ? null : (bool) $before],
                ['enabled' => $enabled],
            );
        }
    }

    /**
     * Every seeded flag: code => {kind, default, description}. 60 s cache.
     *
     * @return array<string, array{kind: string, default: bool, description: string|null}>
     */
    public function defaults(): array
    {
        return Cache::remember(self::DEFAULTS_CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            $rows = DB::connection('central')->table('feature_flags')->get();

            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row->code] = [
                    'kind' => (string) $row->kind,
                    'default' => (bool) $row->default,
                    'description' => $row->description === null ? null : (string) $row->description,
                ];
            }

            return $map;
        });
    }

    /**
     * Per-tenant scope: explicit overrides ?? plan bundle. 60 s cache.
     *
     * @return array{overrides: array<string, bool>, bundle: array<string, bool>}
     */
    private function tenantScope(string $tenantId): array
    {
        return Cache::remember($this->tenantCacheKey($tenantId), self::CACHE_TTL_SECONDS, function () use ($tenantId): array {
            $overrides = [];
            $rows = DB::connection('central')->table('tenant_feature_flags')
                ->where('tenant_id', $tenantId)->get();
            foreach ($rows as $row) {
                $overrides[(string) $row->code] = (bool) $row->enabled;
            }

            // Plan bundle: the plan's flat {code: bool} bundle
            // (tenants.plan_code -> plans.flag_bundle); only boolean-
            // parseable values count, anything else falls through.
            $bundle = [];
            $planCode = DB::connection('central')->table('tenants')
                ->where('id', $tenantId)->value('plan_code');
            $raw = $planCode === null ? null : DB::connection('central')->table('plans')
                ->where('code', $planCode)->value('flag_bundle');
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($decoded)) {
                foreach ($decoded as $code => $value) {
                    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if (is_string($code) && $parsed !== null) {
                        $bundle[$code] = $parsed;
                    }
                }
            }

            return ['overrides' => $overrides, 'bundle' => $bundle];
        });
    }

    private function tenantCacheKey(string $tenantId): string
    {
        return 'feature_flags.tenant.'.$tenantId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function defaultRow(string $code): ?array
    {
        $row = DB::connection('central')->table('feature_flags')
            ->where('code', $code)->first();

        return $row === null ? null : (array) $row;
    }

    private function tenantIdFor(mixed $tenant): ?string
    {
        if (is_string($tenant) && $tenant !== '') {
            return $tenant;
        }

        if (is_object($tenant) && method_exists($tenant, 'getKey')) {
            $key = $tenant->getKey();

            return $key === null ? null : (string) $key;
        }

        return $this->context->isResolved() ? $this->context->tenantId() : null;
    }
}
