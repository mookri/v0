<?php

namespace App\Domain\Verification\Claims;

use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Lifecycle\ExpiryPolicyResolver;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationClaimScope;
use App\Domain\Verification\Models\VerificationEvidence;
use App\Domain\Verification\Projector\VerificationProjector;
use App\Platform\Audit\AuditWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Core Claims Kernel (E-TR-02 / T-TR02-01): grants, supersedes, expires, and revokes
 * evidence-backed verification claims, keeping partner model projection in sync.
 */
final class ClaimManager
{
    public function __construct(
        private readonly VerificationProjector $projector,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @param  array{
     *   tenant_id: string,
     *   subject_type: string,
     *   subject_id: string,
     *   claim_kind: string,
     *   mode: string,
     *   confidence?: string,
     *   policy_version?: string,
     *   application_id?: string|null,
     *   reason_code?: string|null,
     *   note?: string|null,
     *   decided_by: string,
     *   countersigned_by?: string|null,
     *   evidence_set_hash?: string|null,
     *   expires_at?: \DateTimeInterface|string|null,
     *   metadata?: array<string, mixed>|null
     * }  $attributes
     * @param  list<array{scope_type: string, scope_value: string}>  $scopes
     * @param  list<array{document_id?: string|null, evidence_kind: string, source_mode: string, source_ref?: string|null, sha256?: string|null, verified_by?: string|null, metadata?: array<string, mixed>|null}>  $evidenceItems
     */
    public function grantClaim(
        array $attributes,
        array $scopes = [],
        array $evidenceItems = [],
        ?Model $subjectModel = null,
    ): VerificationClaim {
        $tenantId = $attributes['tenant_id'];

        $conn = (new VerificationClaim())->getConnection();

        return $conn->transaction(function () use ($attributes, $scopes, $evidenceItems, $subjectModel, $tenantId): VerificationClaim {
            $this->supersedeActiveByKind(
                $tenantId,
                $attributes['subject_type'],
                $attributes['subject_id'],
                $attributes['claim_kind'],
            );

            $claim = new VerificationClaim();
            $claim->forceFill([
                'tenant_id' => $tenantId,
                'subject_type' => $attributes['subject_type'],
                'subject_id' => $attributes['subject_id'],
                'application_id' => $attributes['application_id'] ?? null,
                'claim_kind' => $attributes['claim_kind'],
                'mode' => $attributes['mode'],
                'confidence' => $attributes['confidence'] ?? ConfidenceLevel::MEDIUM->value,
                'status' => ClaimStatus::GRANTED->value,
                'policy_version' => $attributes['policy_version'] ?? 'policy-v1',
                'legacy_asserted' => false,
                'reason_code' => $attributes['reason_code'] ?? null,
                'note' => $attributes['note'] ?? null,
                'decided_by' => $attributes['decided_by'],
                'countersigned_by' => $attributes['countersigned_by'] ?? null,
                'evidence_set_hash' => $attributes['evidence_set_hash'] ?? null,
                'decided_at' => now()->utc(),
                'expires_at' => $attributes['expires_at'] ?? (new ExpiryPolicyResolver())->resolveExpiryFor($attributes['claim_kind']),
                'metadata' => $attributes['metadata'] ?? null,
            ])->save();

            foreach ($scopes as $scope) {
                $row = new VerificationClaimScope();
                $row->forceFill([
                    'tenant_id' => $tenantId,
                    'claim_id' => (string) $claim->getKey(),
                    'scope_type' => $scope['scope_type'],
                    'scope_value' => $scope['scope_value'],
                ])->save();
            }

            foreach ($evidenceItems as $ev) {
                $row = new VerificationEvidence();
                $row->forceFill([
                    'tenant_id' => $tenantId,
                    'claim_id' => (string) $claim->getKey(),
                    'document_id' => $ev['document_id'] ?? null,
                    'evidence_kind' => $ev['evidence_kind'],
                    'source_mode' => $ev['source_mode'],
                    'source_ref' => $ev['source_ref'] ?? null,
                    'sha256' => $ev['sha256'] ?? null,
                    'verified_at' => now()->utc(),
                    'verified_by' => $ev['verified_by'] ?? $attributes['decided_by'],
                    'metadata' => $ev['metadata'] ?? null,
                ])->save();
            }

            if ($subjectModel !== null) {
                $this->projector->projectForSubject($subjectModel, $attributes['subject_type']);
            }

            $this->audit->record(
                'onboarding.claim.decided',
                [
                    'module' => 'onboarding',
                    'resource_type' => 'verification_claim',
                    'resource_id' => (string) $claim->getKey(),
                ],
                null,
                [
                    'status' => ClaimStatus::GRANTED->value,
                    'claim_kind' => $claim->claim_kind,
                    'mode' => $claim->mode,
                    'subject_type' => $claim->subject_type,
                    'subject_id' => $claim->subject_id,
                    'policy_version' => $claim->policy_version,
                ],
            );

            return $claim;
        });
    }

    public function revokeClaim(
        string $tenantId,
        string $claimId,
        string $actorId,
        string $reason,
        ?Model $subjectModel = null,
    ): VerificationClaim {
        $claim = VerificationClaim::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($claimId);

        $before = ['status' => $claim->status];
        $claim->forceFill([
            'status' => ClaimStatus::REVOKED->value,
            'revoked_at' => now()->utc(),
            'revoked_by' => $actorId,
            'revocation_reason' => $reason,
        ])->save();

        if ($subjectModel !== null) {
            $this->projector->projectForSubject($subjectModel, $claim->subject_type);
        }

        $this->audit->record(
            'onboarding.claim.revoked',
            ['module' => 'onboarding', 'resource_type' => 'verification_claim', 'resource_id' => (string) $claim->getKey()],
            $before,
            ['status' => ClaimStatus::REVOKED->value, 'revocation_reason' => $reason, 'revoked_by' => $actorId],
        );

        return $claim;
    }

    public function supersedeActiveByKind(string $tenantId, string $subjectType, string $subjectId, string $claimKind): int
    {
        return VerificationClaim::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('claim_kind', $claimKind)
            ->where('status', ClaimStatus::GRANTED->value)
            ->update(['status' => ClaimStatus::SUPERSEDED->value]);
    }

    /**
     * @return Collection<int, VerificationClaim>
     */
    public function activeClaimsFor(string $tenantId, string $subjectType, string $subjectId): Collection
    {
        $now = now()->utc();

        return VerificationClaim::query()
            ->withoutGlobalScopes()
            ->with(['scopes', 'evidence'])
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('status', ClaimStatus::GRANTED->value)
            ->where(static function ($q) use ($now): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('decided_at')
            ->get();
    }

    /**
     * Derives the truthful customer-facing trust tier (never calling manual/legacy "official verified").
     *
     * @return array{
     *   has_active_claims: bool,
     *   badge_tier: string,
     *   label: string,
     *   legacy_asserted: bool,
     *   highest_mode: string|null,
     *   claim_kinds: list<string>
     * }
     */
    public function summarizeTrustProfile(string $tenantId, string $subjectType, string $subjectId): array
    {
        $claims = $this->activeClaimsFor($tenantId, $subjectType, $subjectId);

        if ($claims->isEmpty()) {
            return [
                'has_active_claims' => false,
                'badge_tier' => 'unverified',
                'label' => 'Unverified',
                'legacy_asserted' => false,
                'highest_mode' => null,
                'claim_kinds' => [],
            ];
        }

        $legacyOnly = $claims->every(static fn (VerificationClaim $c): bool => (bool) $c->legacy_asserted);
        $modes = $claims->pluck('mode')->map(static fn ($m): string => (string) $m)->all();

        $highestMode = EvidenceMode::NATIVE->value;
        foreach ([EvidenceMode::OFFICIAL->value, EvidenceMode::FINANCIAL->value, EvidenceMode::MANUAL->value] as $candidate) {
            if (in_array($candidate, $modes, true)) {
                $highestMode = $candidate;
                break;
            }
        }

        if ($legacyOnly) {
            return [
                'has_active_claims' => true,
                'badge_tier' => 'legacy_reviewed',
                'label' => 'Previously Onboarded (Legacy Record)',
                'legacy_asserted' => true,
                'highest_mode' => $highestMode,
                'claim_kinds' => array_values(array_unique($claims->pluck('claim_kind')->all())),
            ];
        }

        $modeEnum = EvidenceMode::tryFrom($highestMode) ?? EvidenceMode::MANUAL;

        return [
            'has_active_claims' => true,
            'badge_tier' => $modeEnum->allowsOfficialVerifiedLabel() ? 'verified_authoritative' : 'manually_reviewed',
            'label' => $modeEnum->badgeLabel(),
            'legacy_asserted' => false,
            'highest_mode' => $highestMode,
            'claim_kinds' => array_values(array_unique($claims->pluck('claim_kind')->all())),
        ];
    }
}
