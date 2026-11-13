<?php

namespace App\Domain\Verification\Fiscal;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Platform\Audit\AuditWriter;

/**
 * Freezes payout instruments immediately upon name/mandate/AML discrepancy (T-TR05-06.2).
 */
final class FreezePayoutOnDiscrepancyListener
{
    public function __construct(
        private readonly ClaimManager $claims,
        private readonly AuditWriter $audit,
    ) {
    }

    public function handle(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $actorId,
        string $reason,
    ): int {
        $instruments = VerificationBankInstrument::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->whereNull('discrepancy_frozen_at')
            ->get();

        $frozen = 0;
        foreach ($instruments as $inst) {
            $inst->forceFill([
                'discrepancy_frozen_at' => now()->utc(),
                'discrepancy_reason' => $reason,
                'verification_status' => 'hold',
            ])->save();

            if ($inst->claim_id !== null) {
                try {
                    $this->claims->revokeClaim($tenantId, $inst->claim_id, $actorId, $reason);
                } catch (\Throwable) {
                    // Claim might already be superseded or non-granted; instrument freeze remains binding.
                }
            }

            $frozen++;
        }

        if ($frozen > 0) {
            $this->audit->record(
                'verification.payout_instrument.frozen',
                ['module' => 'verification', 'resource_type' => $subjectType, 'resource_id' => $subjectId],
                null,
                ['reason' => $reason, 'frozen_count' => $frozen, 'actor_id' => $actorId],
            );
        }

        return $frozen;
    }
}

