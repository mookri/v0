<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Privacy\Consent\ConsentMissingException;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Exceptions\InvalidCredentialCategoryException;
use App\Domain\Verification\Exceptions\ModeCeilingViolationException;
use App\Domain\Verification\Issuers\CompetencyAssessor;
use App\Domain\Verification\Issuers\CredentialRevocationService;
use App\Domain\Verification\Issuers\IssuerOutreachService;
use App\Domain\Verification\Issuers\OutreachStage;
use App\Domain\Verification\Models\VerificationIssuer;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QualificationController
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

    public function issuers(Request $request): JsonResponse
    {
        $rows = VerificationIssuer::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (VerificationIssuer $issuer): array => [
                'id' => (string) $issuer->getKey(),
                'code' => $issuer->code,
                'name' => $issuer->name,
                'name_devanagari' => $issuer->name_devanagari,
                'kind' => $issuer->kind,
                'jurisdiction_state' => $issuer->jurisdiction_state,
                'evidence_grade' => $issuer->evidence_grade,
                'equivalence_notes' => $issuer->equivalence_notes,
            ])
            ->values()
            ->all();

        return $this->ok($request, ['issuers' => $rows]);
    }

    public function registerCredential(
        Request $request,
        TenantContext $context,
        IssuerOutreachService $service,
    ): JsonResponse {
        $data = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'issuer_code' => ['required', 'string'],
            'credential_type' => ['required', 'string'],
            'certificate_ref' => ['required', 'string', 'min:3'],
            'award_year' => ['nullable', 'integer', 'min:1940', 'max:2100'],
            'programme_ref' => ['nullable', 'string'],
            'subject_scopes' => ['nullable', 'array'],
            'application_id' => ['nullable', 'string'],
        ]);

        try {
            $result = $service->registerCredential(
                tenantId: $context->tenantId(),
                identityId: (string) $request->user()?->getKey(),
                subjectType: $data['subject_type'],
                subjectId: $data['subject_id'],
                issuerCodeOrId: $data['issuer_code'],
                credentialTypeInput: $data['credential_type'],
                certificateRef: $data['certificate_ref'],
                awardYear: $data['award_year'] ?? null,
                programmeRef: $data['programme_ref'] ?? null,
                subjectScopes: $data['subject_scopes'] ?? [],
                applicationId: $data['application_id'] ?? null,
            );
        } catch (InvalidCredentialCategoryException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, [
            'credential_id' => (string) $result['credential']->getKey(),
            'credential_type' => $result['credential']->credential_type,
            'duplicate_detected' => $result['duplicate_detected'],
            'disclosure' => $result['disclosure'],
        ], 201);
    }

    public function verifyOutreach(
        Request $request,
        TenantContext $context,
        IssuerOutreachService $service,
    ): JsonResponse {
        $data = $request->validate([
            'credential_id' => ['required', 'string'],
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        try {
            $outreach = $service->initiateOutreach(
                tenantId: $context->tenantId(),
                credentialId: $data['credential_id'],
                actorId: (string) $request->user()?->getKey(),
                slaDays: $data['sla_days'] ?? 14,
            );
        } catch (ConsentMissingException $e) {
            return $this->err($request, 'consent_required', $e->getMessage(), 422);
        }

        return $this->ok($request, [
            'response_id' => (string) $outreach->getKey(),
            'outreach_stage' => $outreach->outreach_stage,
            'sla_due_at' => $outreach->sla_due_at?->toIso8601String(),
        ]);
    }

    public function recordOutcome(
        string $credentialId,
        Request $request,
        TenantContext $context,
        IssuerOutreachService $service,
    ): JsonResponse {
        $data = $request->validate([
            'stage' => ['required', 'string'],
            'mode' => ['nullable', 'string'],
            'responder_name' => ['nullable', 'string'],
            'responder_role' => ['nullable', 'string'],
            'official_response_ref' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $stage = OutreachStage::tryFrom($data['stage']);
        if ($stage === null) {
            return $this->err($request, 'invalid_outreach_stage', 'Unsupported outreach stage.', 422);
        }

        $mode = EvidenceMode::tryFrom($data['mode'] ?? EvidenceMode::MANUAL->value) ?? EvidenceMode::MANUAL;

        try {
            $result = $service->recordOutreachOutcome(
                tenantId: $context->tenantId(),
                credentialId: $credentialId,
                stage: $stage,
                actorId: (string) $request->user()?->getKey(),
                requestedMode: $mode,
                responderName: $data['responder_name'] ?? null,
                responderRole: $data['responder_role'] ?? null,
                officialResponseRef: $data['official_response_ref'] ?? null,
                notes: $data['notes'] ?? null,
            );
        } catch (ModeCeilingViolationException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, [
            'outreach_stage' => $result['response']->outreach_stage,
            'claim_id' => $result['claim']?->getKey(),
            'claim_mode' => $result['claim']?->mode,
            'claim_confidence' => $result['claim']?->confidence,
        ]);
    }

    public function assessCompetency(
        Request $request,
        TenantContext $context,
        CompetencyAssessor $assessor,
    ): JsonResponse {
        $data = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'assessor_identity_id' => ['required', 'string'],
            'ritual_category' => ['required', 'string'],
            'scope_tags' => ['required', 'array', 'min:1'],
            'rubric_scores' => ['required', 'array', 'min:1'],
            'sampradaya' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $assessor->recordAssessment(
            tenantId: $context->tenantId(),
            subjectType: $data['subject_type'],
            subjectId: $data['subject_id'],
            assessorIdentityId: $data['assessor_identity_id'],
            ritualCategory: $data['ritual_category'],
            scopeTags: $data['scope_tags'],
            rubricScores: $data['rubric_scores'],
            actorId: (string) $request->user()?->getKey(),
            sampradaya: $data['sampradaya'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return $this->ok($request, [
            'attestation_id' => (string) $result['attestation']->getKey(),
            'passed' => $result['attestation']->passed,
            'claim_id' => $result['claim']?->getKey(),
        ]);
    }

    public function revokeCredential(
        string $credentialId,
        Request $request,
        TenantContext $context,
        CredentialRevocationService $revoker,
    ): JsonResponse {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $credential = $revoker->revoke(
            tenantId: $context->tenantId(),
            credentialId: $credentialId,
            actorId: (string) $request->user()?->getKey(),
            reason: $data['reason'],
        );

        return $this->ok($request, [
            'credential_id' => (string) $credential->getKey(),
            'revoked_at' => $credential->revoked_at?->toIso8601String(),
        ]);
    }
}
