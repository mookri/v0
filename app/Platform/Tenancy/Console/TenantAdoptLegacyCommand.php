<?php

namespace App\Platform\Tenancy\Console;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantPromotion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan tenant:adopt-legacy --owner= [--slug=] [--dry-run]`
 * (pre-era doctrine): adopt a single-tenant dataset into the multitenant
 * world. Provisions a `legacy` tenant, binds the owner, attributes every
 * NULL-tenant_id row to it, and verifies zero NULLs remain.
 *
 * Attribution, never fabrication: adopted rows point at the legacy tenant
 * and the command reports per-table counts, so operators can always tell
 * born-multitenant rows from adopted ones. Backed-out by restoring the
 * pre-freeze backup (there is no other copy — backup is mandatory and the
 * command refuses without --i-have-a-backup outside --dry-run).
 */
final class TenantAdoptLegacyCommand extends Command
{
    protected $signature = 'tenant:adopt-legacy
        {--owner= : User id to bind as tenant owner}
        {--slug=legacy : Slug for the adopted tenant}
        {--dry-run : Report what would change without writing}
        {--i-have-a-backup : Confirm a pre-freeze backup exists}';

    protected $description = 'Adopt pre-tenancy single-tenant data into a legacy tenant';

    public function handle(TenantPromotion $promotion): int
    {
        $ownerId = (string) $this->option('owner');
        $slug = (string) $this->option('slug');
        $dryRun = (bool) $this->option('dry-run');

        if ($ownerId === '') {
            $this->error('--owner is required');

            return self::FAILURE;
        }

        $owner = User::query()->find($ownerId);
        if ($owner === null) {
            $this->error("unknown user {$ownerId}");

            return self::FAILURE;
        }

        if (! $dryRun && ! (bool) $this->option('i-have-a-backup')) {
            $this->error('refusing without --i-have-a-backup (there is no other copy)');

            return self::FAILURE;
        }

        $existing = Tenant::query()->where('slug', $slug)->first();
        if ($existing !== null) {
            $this->error("slug {$slug} is already taken by tenant {$existing->getKey()}");

            return self::FAILURE;
        }

        $source = \App\Platform\Tenancy\ConnectionResolver::SHARED_CONNECTION;
        $tables = $promotion->tenantTables($source);

        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::connection($source)->table($table)->whereNull('tenant_id')->count();
        }
        $total = array_sum($counts);

        if ($dryRun) {
            foreach ($counts as $table => $count) {
                $this->line("[dry-run] {$table}: would attribute {$count} row(s)");
            }
            $this->info("dry run complete — {$total} row(s) would be adopted; no writes performed");

            return self::SUCCESS;
        }

        $tenant = new Tenant();
        $tenant->forceFill([
            'code' => $slug,
            'slug' => $slug,
            'name' => 'Legacy adopted tenant',
            'db_mode' => 'shared',
            'state' => Tenant::STATE_ACTIVE,
            'status' => Tenant::STATE_ACTIVE,
        ]);
        $tenant->save();

        $membership = new TenantMembership();
        $membership->forceFill([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $owner->getAuthIdentifier(),
            'state' => TenantMembership::STATE_ACTIVE,
        ]);
        $membership->save();

        $adopted = 0;
        foreach (array_keys($counts) as $table) {
            $adopted += DB::connection($source)->table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => (string) $tenant->getKey()]);
        }

        $remaining = 0;
        foreach ($promotion->tenantTables($source) as $table) {
            $remaining += DB::connection($source)->table($table)->whereNull('tenant_id')->count();
        }

        if ($remaining > 0) {
            $this->error("adoption incomplete: {$remaining} NULL row(s) remain");

            return self::FAILURE;
        }

        $this->info("adopted tenant {$tenant->getKey()} (slug {$slug}): {$adopted} row(s), owner {$ownerId}");

        return self::SUCCESS;
    }
}
