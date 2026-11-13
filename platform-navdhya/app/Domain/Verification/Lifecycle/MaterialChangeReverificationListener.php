<?php

namespace App\Domain\Verification\Lifecycle;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Models\VerificationClaim;
use App\Platform\Audit\AuditWriter;

/**
 * Maps material partner changes cleanly ONLY to affected claim kinds (§13.4 / T-TR07-05.2).
 */
final class MaterialChangeReverificationListener
{
    /** @var array<string, list<string>> */
    private const AFFECTED_KINDS = [
        'legal_name_changed' => [ClaimKind::IDENTITY->value, ClaimKind::TAX->value, ClaimKind::FINANCIAL->value],
        'trustee_changed' => [ClaimKind::INSTITUTIONAL_AUTHORITY->value],
        'payout_instrument_changed' => [ClaimKind::FINANCIAL->value],
        'jurisdiction_moved' => [ClaimKind::TAX->value, ClaimKind::INSTITUTIONAL_EXISTENCE->value],
        'service_category_added' => [ClaimKind::COMPETENCY->value],
    ];

    public function __construct(
        private readonly ClaimManager $claims,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @return list<string> Invalidated claim IDs
     */
    public function handle(
        string $tenantId,
        string $changeEvent,
        string $subjectType,
        string $subjectId,
        string $actorId,
    ): array {
        $affectedKinds = self::AFFECTED_KINDS[$changeEvent] ?? [];
        if ($affectedKinds === []) {
            return [];
        }

        $claims = VerificationClaim::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('status', ClaimStatus::GRANTED->value)
            ->whereIn('claim_kind', $affectedKinds)
            ->get();

        $revokedIds = [];
        foreach ($claims as $claim) {
            $this->claims->revokeClaim(
                $tenantId,
                (string) $claim->getKey(),
                $actorId,
                "material_change:{$changeEvent}",
            );
            $revokedIds[] = (string) $claim->getKey();
        }

        if ($revokedIds !== []) {
            $this->audit->record(
                'onboarding.material_change.reverification_triggered',
                ['module' => 'onboarding', 'resource_type' => $subjectType, 'resource_id' => $subjectId],
                null,
                ['change_event' => $changeEvent, 'affected_claim_ids' => $revokedIds],
            );
        }

        return $revokedIds;
    }
}

