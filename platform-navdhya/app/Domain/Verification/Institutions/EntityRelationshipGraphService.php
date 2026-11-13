<?php

namespace App\Domain\Verification\Institutions;

use App\Domain\Verification\Models\VerificationEntityRelationship;
use DateTimeInterface;

/**
 * Bounded relationship graph (§7.4 / T-TR04-02.2): records evidenced entity-person-payout edges
 * and supports bounded connectivity queries without automated rejection or guilt-by-association.
 */
final class EntityRelationshipGraphService
{
    public function recordEdge(
        string $tenantId,
        string $sourceType,
        string $sourceId,
        string $edgeType,
        string $targetType,
        string $targetId,
        ?string $claimId = null,
        ?string $evidenceId = null,
        ?DateTimeInterface $effectiveFrom = null,
        ?DateTimeInterface $effectiveTo = null,
    ): VerificationEntityRelationship {
        $edge = new VerificationEntityRelationship();
        $edge->forceFill([
            'tenant_id' => $tenantId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'edge_type' => $edgeType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'claim_id' => $claimId,
            'evidence_id' => $evidenceId,
            'effective_from' => $effectiveFrom ?? now()->utc(),
            'effective_to' => $effectiveTo,
            'is_active' => true,
        ])->save();

        return $edge->fresh();
    }

    /**
     * Returns bounded 1-hop relationships with evidence references for human reviewers.
     *
     * @return list<array{edge_id: string, edge_type: string, target_type: string, target_id: string, claim_id: string|null, evidence_id: string|null, is_active: bool}>
     */
    public function inspectConnectedNodes(string $tenantId, string $sourceType, string $sourceId): array
    {
        return VerificationEntityRelationship::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn (VerificationEntityRelationship $row): array => [
                'edge_id' => (string) $row->getKey(),
                'edge_type' => $row->edge_type,
                'target_type' => $row->target_type,
                'target_id' => $row->target_id,
                'claim_id' => $row->claim_id,
                'evidence_id' => $row->evidence_id,
                'is_active' => (bool) $row->is_active,
            ])
            ->values()
            ->all();
    }
}

