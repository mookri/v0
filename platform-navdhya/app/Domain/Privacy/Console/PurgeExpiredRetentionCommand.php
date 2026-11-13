<?php

namespace App\Domain\Privacy\Console;

use App\Domain\Privacy\Models\PrivacyRetentionPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Automated retention sweep (T-TR06-02 / Closes T-17).
 * Deletes raw evidence objects on rejected/withdrawn or expired applications past retention SLA,
 * honoring central PrivacyRetentionPolicy legal_hold flags.
 */
final class PurgeExpiredRetentionCommand extends Command
{
    protected $signature = 'privacy:purge-expired
        {--tenant= : Optional single tenant ULID}
        {--dry-run : Report counts without deleting blobs}';

    protected $description = 'Purge raw uploaded documents past retention policy via Storage::delete()';

    public function handle(): int
    {
        $tenantFilter = is_string($this->option('tenant')) && $this->option('tenant') !== ''
            ? $this->option('tenant')
            : null;
        $dryRun = (bool) $this->option('dry-run');

        $rejectedPolicy = PrivacyRetentionPolicy::query()->where('dataset_key', 'rejected_application_documents')->first();
        if ($rejectedPolicy?->legal_hold === true) {
            $this->info('Legal hold active on rejected_application_documents; skipping purge.');

            return self::SUCCESS;
        }

        $retentionDays = $rejectedPolicy?->retention_days
            ?? (int) config('privacy.default_retention_days.rejected_application_documents', 90);
        $cutoff = now()->utc()->subDays($retentionDays);
        $disk = Storage::disk((string) config('filesystems.default', 'local'));

        $query = DB::connection('tenant_shared')
            ->table('application_documents as d')
            ->join('partner_applications as a', 'a.id', '=', 'd.application_id')
            ->whereIn('a.status', ['REJECTED', 'WITHDRAWN', 'EXPIRED'])
            ->where('d.object_key', '!=', '[ERASED]')
            ->where('d.created_at', '<=', $cutoff)
            ->select(['d.id', 'd.tenant_id', 'd.object_key']);

        if ($tenantFilter !== null) {
            $query->where('d.tenant_id', $tenantFilter);
        }

        $purged = 0;

        foreach ($query->get() as $row) {
            $key = (string) $row->object_key;
            $purged++;

            if (! $dryRun) {
                if ($key !== '' && $disk->exists($key)) {
                    $disk->delete($key);
                }

                DB::connection('tenant_shared')
                    ->table('application_documents')
                    ->where('id', $row->id)
                    ->update(['object_key' => '[ERASED]', 'quarantine_status' => 'purged_retention']);
            }
        }

        $this->info(sprintf(
            'privacy:purge-expired (%s): %d document(s) %s.',
            $dryRun ? 'dry-run' : 'applied',
            $purged,
            $dryRun ? 'eligible' : 'purged',
        ));

        return self::SUCCESS;
    }
}

