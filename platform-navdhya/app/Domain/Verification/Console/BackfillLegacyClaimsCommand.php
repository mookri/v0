<?php

namespace App\Domain\Verification\Console;

use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationClaim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * T-TR01-05 (T-01): Idempotently backfill existing VERIFIED partner rows into
 * verification_claims marked legacy_asserted=true, mode=manual, confidence=unassessed
 * so legacy records are never confused with official verification.
 */
final class BackfillLegacyClaimsCommand extends Command
{
    protected $signature = 'verification:backfill-legacy-claims
        {--tenant= : Restrict backfill to one tenant_id}
        {--dry-run : Report counts without writing claims}';

    protected $description = 'Backfill legacy VERIFIED partner models into verification_claims with legacy_asserted=true.';

    public function handle(): int
    {
        $tenantFilter = is_string($this->option('tenant')) && $this->option('tenant') !== ''
            ? $this->option('tenant')
            : null;
        $dryRun = (bool) $this->option('dry-run');

        $targets = [
            ['type' => 'pandit', 'table' => 'pandit_profiles', 'column' => 'VERIFIED'],
            ['type' => 'temple', 'table' => 'temples', 'column' => 'VERIFIED'],
            ['type' => 'vendor', 'table' => 'vendors', 'column' => 'VERIFIED'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($targets as $target) {
            $query = DB::connection('tenant_shared')
                ->table($target['table'])
                ->select(['id', 'tenant_id'])
                ->where('verification_status', $target['column']);

            if ($tenantFilter !== null) {
                $query->where('tenant_id', $tenantFilter);
            }

            foreach ($query->orderBy('id')->cursor() as $record) {
                $tenantId = (string) $record->tenant_id;
                $subjectId = (string) $record->id;

                $alreadyHasClaim = VerificationClaim::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('subject_type', $target['type'])
                    ->where('subject_id', $subjectId)
                    ->whereIn('status', [ClaimStatus::GRANTED->value, ClaimStatus::PENDING->value])
                    ->exists();

                if ($alreadyHasClaim) {
                    $skipped++;
                    continue;
                }

                $created++;

                if (! $dryRun) {
                    $claim = new VerificationClaim();
                    $claim->forceFill([
                        'tenant_id' => $tenantId,
                        'subject_type' => $target['type'],
                        'subject_id' => $subjectId,
                        'claim_kind' => ClaimKind::LEGACY->value,
                        'mode' => EvidenceMode::MANUAL->value,
                        'confidence' => ConfidenceLevel::UNASSESSED->value,
                        'status' => ClaimStatus::GRANTED->value,
                        'policy_version' => 'legacy-migration-v0',
                        'legacy_asserted' => true,
                        'decided_by' => 'system:legacy-backfill',
                        'decided_at' => now()->utc(),
                        'metadata' => ['source_table' => $target['table']],
                    ])->save();
                }
            }
        }

        $this->info(sprintf(
            'verification:backfill-legacy-claims (%s): %d claim(s) %s, %d skipped.',
            $dryRun ? 'dry-run' : 'applied',
            $created,
            $dryRun ? 'would be created' : 'created',
            $skipped,
        ));

        return self::SUCCESS;
    }
}
