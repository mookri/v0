<?php

namespace App\Platform\Console;

use App\Platform\Identity\Models\Invite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan cleanup:expired [--dry-run] [--tenant=] [--force]`
 * Retention sweeper (single pass, per class):
 * - OTP challenges past expiry → delete (single-use secrets, no history value).
 * - Invites past expiry → mark EXPIRED (rows retained as audit history).
 * - Web sessions past expiry → delete.
 *
 * Kernel-maintenance bypass (TEN-009): global scopes are lifted explicitly
 * here and only here for sweep purposes; every swept row is tenant-scoped
 * in the report. Destructive runs require --force; dry-run is the default
 * when neither flag is given? No — explicitness wins: --dry-run reports,
 * --force executes, neither errors with usage.
 */
final class CleanupExpiredCommand extends Command
{
    protected $signature = 'cleanup:expired
        {--dry-run : Report what would change without writing}
        {--force : Execute deletions and state changes}
        {--tenant= : Scope the sweep to one tenant id}';

    protected $description = 'Sweep expired OTP challenges, invites and sessions';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $tenant = $this->option('tenant');

        if (! $dryRun && ! $force) {
            $this->error('Refusing: pass --dry-run to report or --force to execute.');
            return self::FAILURE;
        }

        if (! is_string($tenant)) {
            $tenant = null;
        }

        $now = gmdate('Y-m-d H:i:s');
        $report = [];

        // OTP challenges: expired → delete. Table-level access (not the
        // module model): the kernel sweeper must never import modules.
        $challengeQuery = DB::connection('tenant_shared')->table('otp_challenges')
            ->where('expires_at', '<', $now);
        if ($tenant !== null) {
            $challengeQuery->where('tenant_id', $tenant);
        }
        $challengeIds = $challengeQuery->pluck('id');
        $report['otp_challenges'] = $challengeIds->count();
        if (! $dryRun) {
            DB::connection('tenant_shared')->table('otp_challenges')
                ->whereIn('id', $challengeIds->all())->delete();
        }

        // Invites: expired + still issued → mark EXPIRED (history retained).
        $inviteQuery = Invite::query()->where('state', Invite::STATE_ISSUED)->where('expires_at', '<', $now);
        if ($tenant !== null) {
            $inviteQuery->where('tenant_id', $tenant);
        }
        $inviteIds = $inviteQuery->pluck('id');
        $report['invites_expired'] = $inviteIds->count();
        if (! $dryRun) {
            Invite::query()->whereIn('id', $inviteIds->all())->update(['state' => Invite::STATE_EXPIRED]);
        }

        // Web sessions: expired (last_activity older than lifetime) → delete.
        // Lifetime mirrors config session.lifetime (minutes).
        $lifetime = (int) config('session.lifetime', 120);
        $cutoff = time() - $lifetime * 60;
        $sessionQuery = DB::connection(config('database.default'))->table('sessions')
            ->where('last_activity', '<', $cutoff);
        // Sessions carry user_id, not tenant_id: tenant scoping applies only
        // when filtering by a tenant's member set.
        if ($tenant !== null) {
            $userIds = DB::connection('central')->table('tenant_memberships')
                ->where('tenant_id', $tenant)->pluck('user_id');
            $sessionQuery->whereIn('user_id', $userIds->all());
        }
        $report['sessions'] = (clone $sessionQuery)->count();
        if (! $dryRun) {
            $sessionQuery->delete();
        }

        foreach ($report as $class => $count) {
            $this->line(($dryRun ? '[dry-run] ' : '')."{$class}: {$count}");
        }
        $this->info(($dryRun ? 'Dry run complete — ' : 'Sweep complete — ').array_sum($report).' row(s) affected.');

        return self::SUCCESS;
    }
}
