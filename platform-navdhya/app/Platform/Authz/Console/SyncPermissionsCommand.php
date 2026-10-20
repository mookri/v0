<?php

namespace App\Platform\Authz\Console;

use App\Platform\Authz\CapabilityParser;
use App\Platform\Authz\Contracts\CapabilitySelector;
use App\Platform\Authz\Exceptions\CapabilityParseException;
use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;
use App\Platform\Authz\Models\CapabilityMetadata;
use App\Platform\Authz\Models\Policy;
use App\Platform\Modules\ManifestValidator;
use App\Platform\Modules\ModuleRegistry;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan platform:sync-permissions` — materializes every
 * manifest-declared capability into the structured capability registry
 * (SEC-020, CON-005) and validates every stored policy selector
 * (SEC-021). Fail-closed: any malformed capability code, undeclared
 * action, or invalid policy selector aborts the sync with a named error
 * code BEFORE any write (AC-T-0K01-01-1). `--dry-run` prints the
 * per-tenant diff without touching the database.
 */
final class SyncPermissionsCommand extends Command
{
    protected $signature = 'platform:sync-permissions
        {--tenant= : Only sync the capability registry of this tenant}
        {--dry-run : Compute and print the diff without writing}';

    protected $description = 'Parse every manifest-declared capability into structured capability metadata and validate policy selectors (fail-closed)';

    public function handle(ModuleRegistry $registry, CapabilityParser $parser): int
    {
        [$declared, $errors] = $this->collectDeclaredCapabilities($registry, $parser);
        $errors = [...$errors, ...$this->validatePolicySelectors($registry)];

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->newLine();
            $this->error('Sync aborted before any write (fail-closed, SEC-020).');

            return self::FAILURE;
        }

        $tenants = $this->resolveTenants();

        if ($tenants === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $totals = ['add' => 0, 'update' => 0, 'remove' => 0];

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $diff = $this->diffForTenant($tenantId, $declared);

            foreach (array_keys($totals) as $kind) {
                $totals[$kind] += count($diff[$kind]);
            }

            $this->renderDiff((string) $tenant->code, $diff);

            if (! $dryRun) {
                $this->applyDiff($tenantId, $diff);
            }
        }

        if ($tenants->isEmpty()) {
            $this->info('No tenants registered; nothing to sync.');
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d add(s), %d update(s), %d removal(s) across %d tenant(s); %d capabilities declared, %d policies validated.',
            $dryRun ? 'Dry-run diff' : 'Sync complete',
            $totals['add'],
            $totals['update'],
            $totals['remove'],
            $tenants->count(),
            count($declared),
            Policy::withoutGlobalScopes()->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * Parse + vocabulary-check every manifest-declared capability.
     *
     * @return array{0: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, 1: list<string>}
     */
    private function collectDeclaredCapabilities(ModuleRegistry $registry, CapabilityParser $parser): array
    {
        $declared = [];
        $errors = [];

        foreach ($registry->modules() as $module) {
            $actions = [...ManifestValidator::CORE_ACTIONS, ...$module->extraActions];

            foreach ($module->permissions as $permission) {
                $code = (string) $permission['code'];

                try {
                    $capability = $parser->parse($code);
                } catch (CapabilityParseException $exception) {
                    $errors[] = sprintf('[capability_parse_failed] %s', $exception->getMessage());

                    continue;
                }

                if ($capability->module !== $module->code) {
                    $errors[] = sprintf(
                        "[capability_module_mismatch] capability '%s' is declared by module '%s'",
                        $code,
                        $module->code,
                    );

                    continue;
                }

                if (! in_array($capability->action, $actions, true)) {
                    $errors[] = sprintf(
                        "[capability_action_undeclared] capability '%s' action '%s' is outside module '%s' declared action vocabulary",
                        $code,
                        $capability->action,
                        $module->code,
                    );

                    continue;
                }

                $declared[$code] = [
                    'code' => $code,
                    'name' => (string) $permission['name'],
                    'module' => $capability->module,
                    'resource' => $capability->resource,
                    'action' => $capability->action,
                    'sensitive' => (bool) ($permission['sensitive'] ?? false),
                ];
            }
        }

        ksort($declared);

        return [$declared, $errors];
    }

    /**
     * Every stored policy selector must be grammatically valid and its
     * concrete module segment must name a registered module.
     *
     * @return list<string>
     */
    private function validatePolicySelectors(ModuleRegistry $registry): array
    {
        $errors = [];

        foreach (Policy::withoutGlobalScopes()->get() as $policy) {
            $selector = $policy->capability_selector;

            try {
                $parsed = CapabilitySelector::fromArray(is_array($selector) ? $selector : []);
            } catch (InvalidPolicyPayloadException $exception) {
                $errors[] = sprintf(
                    "[policy_selector_invalid] policy '%s' (%s): %s",
                    (string) $policy->name,
                    (string) $policy->getKey(),
                    $exception->getMessage(),
                );

                continue;
            }

            if ($parsed->module !== '*' && ! $registry->has($parsed->module)) {
                $errors[] = sprintf(
                    "[policy_selector_invalid] policy '%s' (%s): selector module '%s' is not a registered module",
                    (string) $policy->name,
                    (string) $policy->getKey(),
                    $parsed->module,
                );
            }
        }

        return $errors;
    }

    /**
     * @return Collection<int, Tenant>|null
     */
    private function resolveTenants(): ?Collection
    {
        $tenantId = $this->option('tenant');

        if ($tenantId === null) {
            return Tenant::all();
        }

        $tenants = Tenant::whereKey($tenantId)->get();

        if ($tenants->isEmpty()) {
            $this->error(sprintf("Tenant '%s' not found.", (string) $tenantId));

            return null;
        }

        return $tenants;
    }

    /**
     * @param  array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>  $declared
     * @return array{add: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, update: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, remove: array<string, CapabilityMetadata>}
     */
    private function diffForTenant(string $tenantId, array $declared): array
    {
        $existing = CapabilityMetadata::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('code');

        $add = [];
        $update = [];

        foreach ($declared as $code => $attributes) {
            $row = $existing->get($code);

            if ($row === null) {
                $add[$code] = $attributes;

                continue;
            }

            $changed = false;

            foreach (['name', 'module', 'resource', 'action', 'sensitive'] as $field) {
                if ($row->getAttribute($field) != $attributes[$field]) {
                    $changed = true;

                    break;
                }
            }

            if ($changed) {
                $update[$code] = $attributes;
            }
        }

        $remove = [];

        foreach ($existing as $code => $row) {
            if (! isset($declared[$code])) {
                $remove[$code] = $row;
            }
        }

        return ['add' => $add, 'update' => $update, 'remove' => $remove];
    }

    /**
     * @param  array{add: array<string, mixed>, update: array<string, mixed>, remove: array<string, CapabilityMetadata>}  $diff
     */
    private function renderDiff(string $tenantCode, array $diff): void
    {
        if ($diff['add'] === [] && $diff['update'] === [] && $diff['remove'] === []) {
            $this->line(sprintf('Tenant %s: no changes.', $tenantCode));

            return;
        }

        $rows = [];

        foreach ($diff['add'] as $code => $attributes) {
            $rows[] = [
                $tenantCode,
                $code,
                'add',
                sprintf('sensitive=%s', $attributes['sensitive'] ? 'yes' : 'no'),
            ];
        }

        foreach (array_keys($diff['update']) as $code) {
            $rows[] = [$tenantCode, $code, 'update', 'metadata changed; version bumped'];
        }

        foreach (array_keys($diff['remove']) as $code) {
            $rows[] = [$tenantCode, $code, 'remove', 'no longer declared by any manifest'];
        }

        $this->table(['Tenant', 'Capability', 'Change', 'Detail'], $rows);
    }

    /**
     * Kernel maintenance write path: rows carry an explicit tenant_id, so
     * the BelongsToTenant context-fill is bypassed by design (TEN-009
     * bypass site; covered by the isolation suite).
     *
     * @param  array{add: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, update: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, remove: array<string, CapabilityMetadata>}  $diff
     */
    private function applyDiff(string $tenantId, array $diff): void
    {
        if ($diff['add'] === [] && $diff['update'] === [] && $diff['remove'] === []) {
            return;
        }

        DB::connection('central')->transaction(function () use ($tenantId, $diff): void {
            foreach ($diff['add'] as $attributes) {
                CapabilityMetadata::create($attributes + ['tenant_id' => $tenantId, 'version' => 1]);
            }

            foreach ($diff['update'] as $code => $attributes) {
                $row = CapabilityMetadata::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('code', $code)
                    ->firstOrFail();

                $row->fill($attributes);
                $row->version = (int) $row->version + 1;
                $row->save();
            }

            foreach ($diff['remove'] as $row) {
                $row->delete();
            }
        });
    }
}
