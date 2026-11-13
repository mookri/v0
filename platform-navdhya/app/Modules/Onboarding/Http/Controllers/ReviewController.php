<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Decisions\DecisionService;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\StateMachine\ApplicationStateMachine;
use App\Domain\Verification\StateMachine\IllegalStateTransitionException;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\Models\ReviewRecord;
use App\Modules\Onboarding\TrackCatalog;
use App\Platform\Api\CodedHttpException;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reviewer surface (stage 7 + v2 Trust Spec): SLA-flagged queue, structured
 * state-machine transitions, self-approval segregation guards, and server-resolved
 * dual-control initiate -> countersign workflow with immutable evidence_set_hash.
 */
final class ReviewController
{
    private const REVIEW_STATES = [
        PartnerApplication::STATUS_SUBMITTED,
        PartnerApplication::STATUS_QUEUED,
        PartnerApplication::STATUS_UNDER_REVIEW,
        PartnerApplication::STATUS_ESCALATED,
        PartnerApplication::STATUS_COUNTERSIGN_PENDING,
        PartnerApplication::STATUS_APPEALED,
    ];

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly DecisionService $decisions,
        private readonly ApplicationStateMachine $stateMachine,
        private readonly ClaimManager $claims,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function queue(Request $request): JsonResponse
    {
        // Cursor-only pagination (doc 06 §3.3): offset is banned on kernel
        // surfaces, so the queue walks newest-first like every other list.
        // SLA urgency rides per-item flags, not row order; `?status=`
        // narrows within the review states.
        $query = PartnerApplication::query()->whereIn('status', self::REVIEW_STATES);

        $status = $request->query('status');
        if (is_string($status) && in_array($status, self::REVIEW_STATES, true)) {
            $query->where('status', $status);
        }

        [$applications, $page] = CursorPagination::paginate($query, $request);

        $items = array_map(
            fn(PartnerApplication $application): array => self::serializeQueueItem($application),
            $applications
        );

        return new JsonResponse([
            'data' => $items,
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeQueueItem(PartnerApplication $application): array
    {
        $track = TrackCatalog::find($application->track, $application->track_version);
        $slaHours = $track['sla_hours'] ?? 72;
        $remaining = $application->submitted_at !== null
            ? (strtotime($application->submitted_at) + $slaHours * 3600) * 1000 - (int) (microtime(true) * 1000)
            : null;

        return [
            'id' => (string) $application->getKey(),
            'object' => 'partner_application',
            'track' => $application->track,
            'status' => $application->status,
            'submitted_at' => $application->submitted_at,
            'sla_breached' => $remaining === null ? null : $remaining < 0,
            'sla_remaining_ms' => $remaining,
        ];
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        return $this->decide($request, $id, ReviewRecord::DECISION_APPROVED, null, null);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason_code' => ['required', 'string'],
            'second_reviewer' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validated['second_reviewer'] === (string) $request->user()->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Rejections need a different second reviewer.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return $this->decide($request, $id, ReviewRecord::DECISION_REJECTED, $validated['reason_code'], $validated['note'] ?? null, $validated['second_reviewer']);
    }

    public function requestInfo(Request $request, string $id): JsonResponse
    {
        return $this->decide($request, $id, ReviewRecord::DECISION_REQUEST_INFO, null, null);
    }

    public function initiateDecision(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'reason_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validated['decision'] === 'reject' && empty($validated['reason_code'])) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'reason_code is required to initiate rejection.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $application = PartnerApplication::query()->find($id);
        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $reviewerId = (string) $request->user()->getAuthIdentifier();
        try {
            $decisionRow = $this->decisions->initiateCountersignedDecision(
                tenantId: (string) $application->tenant_id,
                applicationId: (string) $application->getKey(),
                applicantIdentityId: (string) $application->identity_id,
                currentStatus: $application->status,
                primaryReviewerId: $reviewerId,
                decision: $validated['decision'],
                reasonCode: $validated['reason_code'] ?? null,
                note: $validated['note'] ?? null,
                applicationEvidence: is_array($application->evidence) ? $application->evidence : [],
            );
        } catch (CodedHttpException $e) {
            if ($e->envelopeCode === 'evidence_drifted') {
                $this->stateMachine->assertTransition($application->status, PartnerApplication::STATUS_UNDER_REVIEW);
                $application->forceFill(['status' => PartnerApplication::STATUS_UNDER_REVIEW])->save();
            }

            throw $e;
        } catch (IllegalStateTransitionException $e) {
            return new JsonResponse([
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], $e->statusCode);
        }

        $application->forceFill([
            'status' => PartnerApplication::STATUS_COUNTERSIGN_PENDING,
            'reviewer_id' => $reviewerId,
        ])->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'status' => $application->status,
                'decision_id' => (string) $decisionRow->getKey(),
                'evidence_set_hash' => $decisionRow->evidence_set_hash,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function countersign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision_id' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $application = PartnerApplication::query()->find($id);
        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $secondReviewerId = (string) $request->user()->getAuthIdentifier();
        try {
            $result = $this->decisions->countersign(
                tenantId: (string) $application->tenant_id,
                decisionId: (string) $validated['decision_id'],
                applicationId: (string) $application->getKey(),
                applicantIdentityId: (string) $application->identity_id,
                currentStatus: $application->status,
                secondReviewerId: $secondReviewerId,
                applicationEvidence: is_array($application->evidence) ? $application->evidence : [],
                note: $validated['note'] ?? null,
            );
        } catch (CodedHttpException $e) {
            if ($e->envelopeCode === 'evidence_drifted') {
                $this->stateMachine->assertTransition($application->status, PartnerApplication::STATUS_UNDER_REVIEW);
                $application->forceFill(['status' => PartnerApplication::STATUS_UNDER_REVIEW])->save();
            }

            throw $e;
        } catch (IllegalStateTransitionException $e) {
            return new JsonResponse([
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], $e->statusCode);
        }

        /** @var \App\Domain\Verification\Models\OnboardingDecision $decisionRow */
        $decisionRow = $result['decision'];
        $targetStatus = $result['target_status'];
        $now = gmdate('Y-m-d\TH:i:s.v\Z');

        $application->forceFill([
            'status' => $targetStatus,
            'reviewed_at' => $now,
            'reviewer_id' => $decisionRow->primary_reviewer_id,
            'rejection_code' => $targetStatus === PartnerApplication::STATUS_REJECTED ? $decisionRow->reason_code : null,
        ])->save();

        $record = new ReviewRecord();
        $record->forceFill([
            'application_id' => (string) $application->getKey(),
            'reviewer_id' => $decisionRow->primary_reviewer_id,
            'decision' => $targetStatus === PartnerApplication::STATUS_APPROVED ? ReviewRecord::DECISION_APPROVED : ReviewRecord::DECISION_REJECTED,
            'reason_code' => $decisionRow->reason_code,
            'note' => "[countersigned_by:{$secondReviewerId}|decision:{$decisionRow->getKey()}] " . ($decisionRow->note ?? ''),
        ]);
        $record->save();

        if ($targetStatus === PartnerApplication::STATUS_APPROVED) {
            $this->claims->grantClaim([
                'tenant_id' => (string) $application->tenant_id,
                'subject_type' => $application->track === 'pandit' ? 'pandit' : ($application->track === 'temple' ? 'temple' : 'vendor'),
                'subject_id' => (string) $application->identity_id,
                'application_id' => (string) $application->getKey(),
                'claim_kind' => ClaimKind::IDENTITY->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::MEDIUM->value,
                'policy_version' => $decisionRow->policy_version,
                'decided_by' => $decisionRow->primary_reviewer_id,
                'countersigned_by' => $secondReviewerId,
                'evidence_set_hash' => $decisionRow->evidence_set_hash,
            ]);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'status' => $application->status,
                'review_id' => (string) $record->getKey(),
                'decision_id' => (string) $decisionRow->getKey(),
                'evidence_set_hash' => $decisionRow->evidence_set_hash,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function decide(
        Request $request,
        string $id,
        string $decision,
        ?string $reasonCode,
        ?string $note,
        ?string $secondReviewer = null,
    ): JsonResponse {
        $application = PartnerApplication::query()->find($id);

        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $reviewerId = (string) $request->user()->getAuthIdentifier();
        $this->decisions->assertSegregationFromApplicant(
            (string) $application->getKey(),
            (string) $application->identity_id,
            $reviewerId,
            strtolower($decision),
        );

        if ($secondReviewer !== null && $secondReviewer === (string) $application->identity_id) {
            $this->audit->record(
                'onboarding.control.segregation_violation',
                ['module' => 'onboarding', 'resource_type' => 'partner_application', 'resource_id' => (string) $application->getKey()],
                null,
                ['violation' => 'applicant_as_countersigner', 'actor_id' => $reviewerId],
            );

            return new JsonResponse([
                'error' => ['code' => 'segregation_violation', 'message' => 'Applicant cannot act as countersigner.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        if ($application->status === PartnerApplication::STATUS_SUBMITTED) {
            $this->stateMachine->assertTransition($application->status, PartnerApplication::STATUS_UNDER_REVIEW);
            $application->forceFill(['status' => PartnerApplication::STATUS_UNDER_REVIEW])->save();
        }

        $allowed = match ($decision) {
            ReviewRecord::DECISION_APPROVED => [PartnerApplication::STATUS_UNDER_REVIEW, PartnerApplication::STATUS_APPEALED],
            ReviewRecord::DECISION_REJECTED => [PartnerApplication::STATUS_UNDER_REVIEW, PartnerApplication::STATUS_APPEALED],
            default => [PartnerApplication::STATUS_UNDER_REVIEW],
        };

        if (!in_array($application->status, $allowed, true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot review in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $to = match ($decision) {
            ReviewRecord::DECISION_APPROVED => PartnerApplication::STATUS_APPROVED,
            ReviewRecord::DECISION_REJECTED => PartnerApplication::STATUS_REJECTED,
            default => PartnerApplication::STATUS_DRAFT,
        };

        $this->stateMachine->assertTransition($application->status, $to);

        $evidenceSetHash = $this->decisions->computeEvidenceSetHash(
            (string) $application->tenant_id,
            (string) $application->getKey(),
            is_array($application->evidence) ? $application->evidence : [],
        );

        $now = gmdate('Y-m-d\TH:i:s.v\Z');
        $application->forceFill([
            'status' => $to,
            'reviewed_at' => $now,
            'reviewer_id' => $reviewerId,
            'rejection_code' => $decision === ReviewRecord::DECISION_REJECTED ? $reasonCode : null,
        ])->save();

        $record = new ReviewRecord();
        $record->forceFill([
            'application_id' => (string) $application->getKey(),
            'reviewer_id' => $reviewerId,
            'decision' => $decision,
            'reason_code' => $reasonCode,
            'note' => $secondReviewer !== null ? "[countersigned:{$secondReviewer}] " . ($note ?? '') : $note,
        ]);
        $record->save();

        $this->audit->record(
            'onboarding.review.decided',
            ['resource_type' => 'partner_application', 'resource_id' => (string) $application->getKey()],
            null,
            ['decision' => $decision, 'reason_code' => $reasonCode, 'evidence_set_hash' => $evidenceSetHash]
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'status' => $application->status,
                'review_id' => (string) $record->getKey(),
                'evidence_set_hash' => $evidenceSetHash,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
