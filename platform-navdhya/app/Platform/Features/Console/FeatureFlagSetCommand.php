<?php

namespace App\Platform\Features\Console;

use App\Platform\Features\Exceptions\UnknownFeatureFlagException;
use App\Platform\Features\FeatureFlags;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * `php artisan flags:set {code} {enabled} [--tenant=]` — the operator
 * mutation surface for feature flags (doc 07 §10.2 rules 3-4).
 *
 * Without `--tenant` changes the global default (requires a seeded row;
 * central state, logged as `platform.flags.changed` — see FeatureFlags
 * docblock for why defaults are not tenant-audited). With `--tenant`
 * upserts a per-tenant override and audits into that tenant's chain
 * (context is resolved for the target tenant first, so AuditWriter
 * records the change where the runtime behavior lands). `reset` with
 * `--tenant` clears the override. Both take effect immediately (cache
 * forget), bounded by the 60 s TTL either way.
 */
final class FeatureFlagSetCommand extends Command
{
    protected $signature = 'flags:set
        {code : Flag code (e.g. payments.enabled, module.alpha)}
        {enabled : true|false — or "reset" (with --tenant) to clear the override}
        {--tenant= : Target tenant id for a per-tenant override}';

    protected $description = 'Set a feature-flag global default or per-tenant override (audited, immediate)';

    public function handle(FeatureFlags $flags, TenantContext $context): int
    {
        $code = (string) $this->argument('code');
        $raw = strtolower(trim((string) $this->argument('enabled')));
        $tenantId = $this->option('tenant');

        $isReset = $raw === 'reset';

        if ($isReset && $tenantId === null) {
            $this->error('"reset" is only valid with --tenant (global defaults are the seeded row).');

            return self::FAILURE;
        }

        if ($isReset) {
            $enabled = null;
        } else {
            $enabled = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($enabled === null) {
                $this->error('enabled must be true or false (or "reset" with --tenant).');

                return self::FAILURE;
            }
        }

        if ($tenantId !== null) {
            $tenantId = (string) $tenantId;

            if (! Tenant::query()->where('id', $tenantId)->exists()) {
                $this->error(sprintf('Tenant %s not found.', $tenantId));

                return self::FAILURE;
            }

            // Console scope has no ambient tenancy; resolve the target so
            // the change lands in that tenant's audit chain.
            $context->clear();
            $context->set($tenantId);

            try {
                $flags->setOverride($code, $enabled, $tenantId);
            } finally {
                $context->clear();
            }

            $this->info(sprintf(
                '%s %s for tenant %s (audited platform.flags.changed).',
                $enabled === null ? 'Cleared override' : 'Set override',
                $code,
                $tenantId,
            ));

            return self::SUCCESS;
        }

        try {
            $flags->setDefault($code, (bool) $enabled);
        } catch (UnknownFeatureFlagException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Global default %s = %s (platform.flags.changed logged).', $code, $enabled ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
