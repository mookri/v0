<?php

namespace App\Domain\Verification\Projector;

use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationClaim;
use Illuminate\Database\Eloquent\Model;

/**
 * Single authorized writer for derived partner `verification_status` columns
 * on PanditProfile, Temple, and Vendor (ADR-0041, T-TR01-05).
 */
final class VerificationProjector
{
    private static int $projectionDepth = 0;

    public static function isProjecting(): bool
    {
        return self::$projectionDepth > 0;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutWriteBlock(callable $callback): mixed
    {
        self::$projectionDepth++;

        try {
            return $callback();
        } finally {
            self::$projectionDepth--;
        }
    }

    /**
     * Record a manually reviewed claim (explicitly labeled mode=manual unless upgraded)
     * and derive the projected `verification_status` on the subject model.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function grantManualClaimAndProject(
        Model $subject,
        string $subjectType,
        string $actorId,
        string $claimKind = ClaimKind::IDENTITY->value,
        ?string $applicationId = null,
        array $metadata = [],
    ): VerificationClaim {
        $tenantId = (string) ($subject->getAttribute('tenant_id') ?? '');

        return $subject->getConnection()->transaction(function () use (
            $subject,
            $subjectType,
            $actorId,
            $claimKind,
            $applicationId,
            $metadata,
            $tenantId,
        ): VerificationClaim {
            $claim = new VerificationClaim();
            $claim->forceFill([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => (string) $subject->getKey(),
                'application_id' => $applicationId,
                'claim_kind' => $claimKind,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::MEDIUM->value,
                'status' => ClaimStatus::GRANTED->value,
                'policy_version' => 'policy-v1',
                'legacy_asserted' => false,
                'decided_by' => $actorId,
                'decided_at' => now()->utc(),
                'metadata' => $metadata,
            ])->save();

            $this->projectForSubject($subject, $subjectType);

            return $claim;
        });
    }

    /**
     * Record a structured claim (any mode) and re-derive the partner column.
     *
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
     *   legacy_asserted?: bool,
     *   metadata?: array<string, mixed>|null
     * }  $attributes
     */
    public function recordClaimAndProject(Model $subject, array $attributes): VerificationClaim
    {
        return $subject->getConnection()->transaction(function () use ($subject, $attributes): VerificationClaim {
            $claim = new VerificationClaim();
            $claim->forceFill([
                'tenant_id' => $attributes['tenant_id'],
                'subject_type' => $attributes['subject_type'],
                'subject_id' => $attributes['subject_id'],
                'application_id' => $attributes['application_id'] ?? null,
                'claim_kind' => $attributes['claim_kind'],
                'mode' => $attributes['mode'],
                'confidence' => $attributes['confidence'] ?? ConfidenceLevel::MEDIUM->value,
                'status' => ClaimStatus::GRANTED->value,
                'policy_version' => $attributes['policy_version'] ?? 'policy-v1',
                'legacy_asserted' => (bool) ($attributes['legacy_asserted'] ?? false),
                'reason_code' => $attributes['reason_code'] ?? null,
                'note' => $attributes['note'] ?? null,
                'decided_by' => $attributes['decided_by'],
                'countersigned_by' => $attributes['countersigned_by'] ?? null,
                'evidence_set_hash' => $attributes['evidence_set_hash'] ?? null,
                'decided_at' => now()->utc(),
                'expires_at' => $attributes['expires_at'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
            ])->save();

            $this->projectForSubject($subject, $attributes['subject_type']);

            return $claim;
        });
    }

    /**
     * Recompute the legacy `verification_status` projection column from active claims.
     */
    public function projectForSubject(Model $subject, string $subjectType): string
    {
        $tenantId = (string) ($subject->getAttribute('tenant_id') ?? '');
        $subjectId = (string) $subject->getKey();
        $now = now()->utc();

        $hasActiveGrant = VerificationClaim::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('status', ClaimStatus::GRANTED->value)
            ->where(static function ($q) use ($now): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->exists();

        if ($hasActiveGrant) {
            $derived = 'VERIFIED';
        } else {
            $hasRejectionOrRevocation = VerificationClaim::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->whereIn('status', [ClaimStatus::REJECTED->value, ClaimStatus::REVOKED->value])
                ->exists();

            // Vendor model has PENDING|VERIFIED vocabulary; Pandit/Temple also support REJECTED.
            $supportsRejected = $subjectType !== 'vendor';
            $derived = ($hasRejectionOrRevocation && $supportsRejected) ? 'REJECTED' : 'PENDING';
        }

        if ((string) $subject->getRawOriginal('verification_status') !== $derived || (string) $subject->getAttribute('verification_status') !== $derived) {
            self::withoutWriteBlock(static function () use ($subject, $derived): void {
                $subject->forceFill(['verification_status' => $derived])->save();
            });
        }

        return $derived;
    }
}
