<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Privacy\Consent\ConsentMissingException;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Exceptions\AppealIndependenceViolationException;
use App\Domain\Verification\Exceptions\RegulatoryGateLockedException;
use App\Domain\Verification\Lifecycle\ReviewerQualityMetricsService;
use App\Domain\Verification\Models\OnboardingAppeal;
use App\Domain\Verification\Risk\BackgroundCheckService;
use App\Domain\Verification\Risk\PoliceAntecedentStatus;
use App\Domain\Verification\Risk\SanctionsAndPepScreeningService;
use App\Modules\Onboarding\Services\AppealService;
use App\Platform\Api\CodedHttpException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComplianceLifecycleController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ok(Request $request, array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ], $status);
    }

    private function err(Request $request, string $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => ['code' => $code, 'message' => $message, 'request_id' => $this->requestId($request)],
        ], $status);
    }

    public function submitAppeal(Request $request, TenantContext $context, AppealService $appeals): JsonResponse
    {
        $data = $request->validate([
            'application_id' => ['required', 'string'],
            'grounds_code' => ['required', 'string', 'min:3'],
            'applicant_statement' => ['required', 'string', 'min:10'],
            'new_evidence_ids' => ['nullable', 'array'],
        ]);

        $appeal = $appeals->submitAppeal(
            tenantId: $context->tenantId(),
            applicationId: $data['application_id'],
            applicantIdentityId: (string) $request->user()?->getKey(),
            groundsCode: $data['grounds_code'],
            applicantStatement: $data['applicant_statement'],
            newEvidenceIds: $data['new_evidence_ids'] ?? [],
        );

        return $this->ok($request, [
            'appeal_id' => (string) $appeal->getKey(),
            'application_id' => $appeal->application_id,
            'sla_due_at' => $appeal->sla_due_at?->toIso8601String(),
        ], 201);
    }

    public function listAppeals(Request $request, TenantContext $context): JsonResponse
    {
        $rows = OnboardingAppeal::query()
            ->where('tenant_id', $context->tenantId())
            ->orderByDesc('created_at')
            ->get()
            ->map(static fn(OnboardingAppeal $a): array => [
                'id' => (string) $a->getKey(),
                'application_id' => $a->application_id,
                'grounds_code' => $a->grounds_code,
                'outcome' => $a->outcome,
                'sla_due_at' => $a->sla_due_at?->toIso8601String(),
                'decided_at' => $a->decided_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->ok($request, ['appeals' => $rows]);
    }

    public function decideAppeal(
        string $appealId,
        Request $request,
        TenantContext $context,
        AppealService $appeals,
    ): JsonResponse {
        $data = $request->validate([
            'outcome' => ['required', 'string', 'in:upheld,overturned,remanded'],
            'note' => ['required', 'string', 'min:5'],
        ]);

        try {
            $appeal = $appeals->decideAppeal(
                tenantId: $context->tenantId(),
                appealId: $appealId,
                appealReviewerId: (string) $request->user()?->getKey(),
                outcome: $data['outcome'],
                note: $data['note'],
            );
        } catch (AppealIndependenceViolationException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, [
            'appeal_id' => (string) $appeal->getKey(),
            'outcome' => $appeal->outcome,
            'assigned_reviewer_id' => $appeal->assigned_reviewer_id,
        ]);
    }

    public function revokeClaim(
        Request $request,
        TenantContext $context,
        ClaimManager $claims,
    ): JsonResponse {
        $data = $request->validate([
            'claim_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:3'],
            'countersigner_id' => ['required', 'string'],
        ]);

        $actorId = (string) $request->user()?->getKey();
        if ($data['countersigner_id'] === $actorId) {
            return $this->err($request, 'dual_control_required', 'Claim revocation requires a distinct countersigner.', 422);
        }

        $claim = $claims->revokeClaim(
            tenantId: $context->tenantId(),
            claimId: $data['claim_id'],
            actorId: $actorId,
            reason: $data['reason'],
        );

        return $this->ok($request, [
            'claim_id' => (string) $claim->getKey(),
            'status' => $claim->status,
            'revoked_at' => $claim->revoked_at?->toIso8601String(),
        ]);
    }

    public function recordBackgroundCheck(
        Request $request,
        TenantContext $context,
        BackgroundCheckService $checks,
    ): JsonResponse {
        $data = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'identity_id' => ['required', 'string'],
            'check_type' => ['required', 'string'],
            'lawful_basis' => ['required', 'string'],
            'legal_status' => ['required', 'string'],
            'findings_summary' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string'],
        ]);

        $status = PoliceAntecedentStatus::tryFrom($data['legal_status']);
        if ($status === null) {
            return $this->err($request, 'invalid_legal_status', 'Unsupported PoliceAntecedentStatus value.', 422);
        }

        try {
            $check = $checks->recordCheck(
                tenantId: $context->tenantId(),
                subjectType: $data['subject_type'],
                subjectId: $data['subject_id'],
                identityId: $data['identity_id'],
                checkType: $data['check_type'],
                lawfulBasis: $data['lawful_basis'],
                legalStatus: $status,
                reviewerId: (string) $request->user()?->getKey(),
                findingsSummary: $data['findings_summary'] ?? null,
                applicationId: $data['application_id'] ?? null,
            );
        } catch (CodedHttpException | ConsentMissingException $e) {
            return $this->err($request, 'consent_required', $e->getMessage(), 403);
        }

        return $this->ok($request, [
            'check_id' => (string) $check->getKey(),
            'legal_status' => $check->legal_status,
            'action_required' => $check->action_required,
            'claim_id' => $check->claim_id,
        ], 201);
    }

    public function clarifyBackgroundCheck(
        string $checkId,
        Request $request,
        TenantContext $context,
        BackgroundCheckService $checks,
    ): JsonResponse {
        $data = $request->validate([
            'clarification' => ['required', 'string', 'min:5'],
        ]);

        $check = $checks->recordClarificationAndRelevanceReview(
            tenantId: $context->tenantId(),
            checkId: $checkId,
            applicantClarification: $data['clarification'],
            reviewerId: (string) $request->user()?->getKey(),
        );

        return $this->ok($request, [
            'check_id' => (string) $check->getKey(),
            'human_relevance_assessed' => $check->human_relevance_assessed,
            'action_required' => $check->action_required,
        ]);
    }

    public function screenSanctions(Request $request, SanctionsAndPepScreeningService $screening): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        try {
            $res = $screening->screen($data['full_name'], $data['date_of_birth'] ?? null);
        } catch (RegulatoryGateLockedException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, $res);
    }

    public function reviewerMetrics(
        string $reviewerId,
        Request $request,
        TenantContext $context,
        ReviewerQualityMetricsService $metrics,
    ): JsonResponse {
        return $this->ok($request, $metrics->metricsForReviewer($context->tenantId(), $reviewerId));
    }
}

