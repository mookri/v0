<?php

namespace App\Domain\Verification\Decisions;

use App\Domain\Verification\Models\OnboardingDecision;
use App\Domain\Verification\StateMachine\ApplicationStateMachine;
use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;
use Illuminate\Support\Facades\DB;

/**
 * Server-resolved dual-control maker-checker and evidence-set hash binding (E-TR-02 / T-TR02-03, Closes T-06).
 */
final class DecisionService
{
    public function __construct(
        private readonly ApplicationStateMachine $stateMachine,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * Compute a deterministic SHA-256 over the canonical sorted set of uploaded documents and evidence payload.
     * Any document addition/replacement/tamper invalidates prior single-reviewer approvals (409 evidence_drifted).
     *
     * @param  array<string, mixed>|null  $applicationEvidence
     */
    public function computeEvidenceSetHash(string $tenantId, string $applicationId, ?array $applicationEvidence = null): string
    {
        $docs = DB::connection('tenant_shared')
            ->table('application_documents')
            ->where('tenant_id', $tenantId)
            ->where('application_id', $applicationId)
            ->orderBy('id')
            ->get(['id', 'kind', 'sha256', 'status', 'integrity_state'])
            ->map(static fn (object $row): array => [
                'id' => (string) $row->id,
                'kind' => (string) $row->kind,
                'sha256' => (string) ($row->sha256 ?? ''),
                'status' => (string) ($row->status ?? 'PENDING'),
                'integrity_state' => (string) ($row->integrity_state ?? 'unverified'),
            ])
            ->values()
            ->all();

        $normalizedEvidence = $this->recursiveKeySort($applicationEvidence ?? []);

        return hash(
            'sha256',
            (string) json_encode([
                'application_id' => $applicationId,
                'documents' => $docs,
                'evidence' => $normalizedEvidence,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Assert the acting reviewer did not submit the application (segregation of duties).
     */
    public function assertSegregationFromApplicant(string $applicationId, string $applicantIdentityId, string $reviewerId, string $verb): void
    {
        if ($applicantIdentityId !== '' && $reviewerId === $applicantIdentityId) {
            $this->audit->record(
                'onboarding.control.segregation_violation',
                ['module' => 'onboarding', 'resource_type' => 'partner_application', 'resource_id' => $applicationId],
                null,
                ['violation' => 'self_review', 'actor_id' => $reviewerId, 'verb' => $verb],
            );

            throw new CodedHttpException(
                403,
                'segregation_violation',
                'Applicants cannot review, approve, or countersign their own application.',
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $applicationEvidence
     */
    public function initiateCountersignedDecision(
        string $tenantId,
        string $applicationId,
        string $applicantIdentityId,
        string $currentStatus,
        string $primaryReviewerId,
        string $decision,
        ?string $reasonCode = null,
        ?string $note = null,
        ?array $applicationEvidence = null,
        string $policyVersion = 'policy-v2',
    ): OnboardingDecision {
        $this->assertSegregationFromApplicant($applicationId, $applicantIdentityId, $primaryReviewerId, "initiate:{$decision}");

        $effectiveFrom = in_array($currentStatus, ['SUBMITTED', 'QUEUED', 'APPEALED'], true)
            ? 'UNDER_REVIEW'
            : $currentStatus;

        $this->stateMachine->assertTransition($effectiveFrom, 'COUNTERSIGN_PENDING');

        $evidenceHash = $this->computeEvidenceSetHash($tenantId, $applicationId, $applicationEvidence);

        $row = new OnboardingDecision();
        $row->forceFill([
            'tenant_id' => $tenantId,
            'application_id' => $applicationId,
            'decision' => $decision,
            'status' => 'pending_countersign',
            'reason_code' => $reasonCode,
            'note' => $note,
            'primary_reviewer_id' => $primaryReviewerId,
            'evidence_set_hash' => $evidenceHash,
            'policy_version' => $policyVersion,
        ])->save();

        return $row;
    }

    /**
     * Server-resolved second reviewer countersignature. Validates:
     * 1. Second reviewer != applicant AND != primary reviewer (audits segregation violation if violated).
     * 2. Recomputed evidence_set_hash still matches initiated evidence_set_hash (409 evidence_drifted otherwise).
     * 3. Transition COUNTERSIGN_PENDING -> APPROVED | REJECTED.
     *
     * @param  array<string, mixed>|null  $applicationEvidence
     * @return array{decision: OnboardingDecision, target_status: string}
     */
    public function countersign(
        string $tenantId,
        string $decisionId,
        string $applicationId,
        string $applicantIdentityId,
        string $currentStatus,
        string $secondReviewerId,
        ?array $applicationEvidence = null,
        ?string $note = null,
    ): array {
        $this->assertSegregationFromApplicant($applicationId, $applicantIdentityId, $secondReviewerId, 'countersign');

        $decision = OnboardingDecision::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('application_id', $applicationId)
            ->where('id', $decisionId)
            ->first();

        if ($decision === null || $decision->status !== 'pending_countersign') {
            throw new CodedHttpException(409, 'state_forbidden', 'No pending countersignature found for this decision.');
        }

        if ($secondReviewerId === $decision->primary_reviewer_id) {
            $this->audit->record(
                'onboarding.control.segregation_violation',
                ['module' => 'onboarding', 'resource_type' => 'onboarding_decision', 'resource_id' => (string) $decision->getKey()],
                null,
                ['violation' => 'same_second_reviewer', 'actor_id' => $secondReviewerId, 'application_id' => $applicationId],
            );

            throw new CodedHttpException(
                403,
                'segregation_violation',
                'Countersignature must come from a different authenticated reviewer.',
            );
        }

        $currentHash = $this->computeEvidenceSetHash($tenantId, $applicationId, $applicationEvidence);
        if (! hash_equals($decision->evidence_set_hash, $currentHash)) {
            $decision->forceFill(['status' => 'cancelled'])->save();

            throw new CodedHttpException(
                409,
                'evidence_drifted',
                'Application documents or evidence changed after primary review; restart review.',
            );
        }

        $targetStatus = $decision->decision === 'approve' ? 'APPROVED' : 'REJECTED';
        $this->stateMachine->assertTransition($currentStatus, $targetStatus);

        $combinedNote = $note !== null && trim($note) !== ''
            ? trim((string) ($decision->note ?? '') . ' | Countersign note: ' . $note)
            : $decision->note;

        $decision->forceFill([
            'status' => 'finalized',
            'second_reviewer_id' => $secondReviewerId,
            'countersigned_at' => now()->utc(),
            'note' => $combinedNote,
        ])->save();

        $this->audit->record(
            'onboarding.decision.countersigned',
            ['module' => 'onboarding', 'resource_type' => 'onboarding_decision', 'resource_id' => (string) $decision->getKey()],
            ['status' => 'pending_countersign'],
            [
                'status' => 'finalized',
                'application_id' => $applicationId,
                'decision' => $decision->decision,
                'primary_reviewer_id' => $decision->primary_reviewer_id,
                'second_reviewer_id' => $secondReviewerId,
                'evidence_set_hash' => $currentHash,
            ],
        );

        return ['decision' => $decision, 'target_status' => $targetStatus];
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function recursiveKeySort(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveKeySort($value);
            }
        }

        return $data;
    }
}
