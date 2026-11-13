<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Verification\Exceptions\TrusteeTakeoverBlockedException;
use App\Domain\Verification\Institutions\AuthorityScope;
use App\Domain\Verification\Institutions\EntityRelationshipGraphService;
use App\Domain\Verification\Institutions\InstitutionalLegalForm;
use App\Domain\Verification\Institutions\TempleAuthorityService;
use App\Domain\Verification\Models\VerificationJurisdiction;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class InstitutionVerificationController
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

    public function jurisdictions(Request $request): JsonResponse
    {
        $rows = VerificationJurisdiction::query()
            ->orderBy('code')
            ->get()
            ->map(static fn(VerificationJurisdiction $j): array => [
                'code' => $j->code,
                'state_code' => $j->state_code,
                'title' => $j->title,
                'checklist' => $j->checklist_json,
                'manual_mode_only' => (bool) $j->manual_mode_only,
            ])
            ->values()
            ->all();

        return $this->ok($request, ['jurisdictions' => $rows]);
    }

    public function verify(
        Request $request,
        TenantContext $context,
        TempleAuthorityService $service,
    ): JsonResponse {
        $kind = (string) $request->input('kind', 'existence');

        try {
            if ($kind === 'existence') {
                $data = $request->validate([
                    'entity_id' => ['required', 'string'],
                    'legal_form' => ['required', 'string'],
                    'jurisdiction_code' => ['required', 'string'],
                    'checklist' => ['required', 'array'],
                    'registration_number' => ['nullable', 'string'],
                    'normalized_name' => ['nullable', 'string'],
                    'application_id' => ['nullable', 'string'],
                ]);

                $form = InstitutionalLegalForm::tryFrom($data['legal_form']);
                if ($form === null) {
                    return $this->err($request, 'invalid_legal_form', 'Unsupported institutional legal form.', 422);
                }

                $result = $service->verifyExistence(
                    tenantId: $context->tenantId(),
                    entityId: $data['entity_id'],
                    legalForm: $form,
                    jurisdictionCode: $data['jurisdiction_code'],
                    reviewerId: (string) $request->user()?->getKey(),
                    checklistEvidence: $data['checklist'],
                    registrationNumber: $data['registration_number'] ?? null,
                    normalizedName: $data['normalized_name'] ?? null,
                    applicationId: $data['application_id'] ?? null,
                );

                return $this->ok($request, [
                    'claim_kind' => $result['existence_claim']->claim_kind,
                    'claim_id' => (string) $result['existence_claim']->getKey(),
                    'claim_mode' => $result['existence_claim']->mode,
                    'duplicate_detected' => $result['duplicate_detected'],
                    'registration_fingerprint_hmac' => $result['registration_fingerprint_hmac'],
                ]);
            }

            $data = $request->validate([
                'entity_id' => ['required', 'string'],
                'person_identity_id' => ['required', 'string'],
                'role_title' => ['required', 'string'],
                'authority_scope' => ['required', 'string'],
                'resolution_ref' => ['required', 'string'],
                'resolution_date' => ['required', 'date'],
                'effective_to' => ['nullable', 'date'],
                'legal_form' => ['nullable', 'string'],
                'jurisdiction_code' => ['nullable', 'string'],
                'registration_fingerprint_hmac' => ['nullable', 'string'],
                'application_id' => ['nullable', 'string'],
            ]);

            $scope = AuthorityScope::tryFrom($data['authority_scope']);
            if ($scope === null) {
                return $this->err($request, 'invalid_authority_scope', 'Unsupported authority scope.', 422);
            }

            $result = $service->verifyAuthority(
                tenantId: $context->tenantId(),
                entityId: $data['entity_id'],
                personIdentityId: $data['person_identity_id'],
                roleTitle: $data['role_title'],
                scope: $scope,
                resolutionRef: $data['resolution_ref'],
                resolutionDate: $data['resolution_date'],
                reviewerId: (string) $request->user()?->getKey(),
                effectiveTo: isset($data['effective_to']) ? Carbon::parse($data['effective_to']) : null,
                legalForm: $data['legal_form'] ?? null,
                jurisdictionCode: $data['jurisdiction_code'] ?? null,
                registrationFingerprintHmac: $data['registration_fingerprint_hmac'] ?? null,
                applicationId: $data['application_id'] ?? null,
            );

            if ($result['conflict_detected']) {
                return $this->err(
                    $request,
                    'conflicting_trustee_claim',
                    'Active competing trustee authority exists; escalated to enhanced compliance review.',
                    409,
                );
            }

            return $this->ok($request, [
                'conflict_detected' => false,
                'grant_id' => $result['grant']?->getKey(),
                'claim_kind' => $result['authority_claim']->claim_kind,
                'claim_id' => (string) $result['authority_claim']->getKey(),
            ]);
        } catch (TrusteeTakeoverBlockedException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }
    }

    public function checkApprove(
        string $entityId,
        Request $request,
        TenantContext $context,
        TempleAuthorityService $service,
    ): JsonResponse {
        $data = $request->validate(['applicant_identity_id' => ['required', 'string']]);

        try {
            $service->assertCanApproveInstitution($context->tenantId(), $entityId, $data['applicant_identity_id']);
        } catch (TrusteeTakeoverBlockedException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, ['eligible' => true]);
    }

    public function checkAttachPayout(
        string $entityId,
        Request $request,
        TenantContext $context,
        TempleAuthorityService $service,
    ): JsonResponse {
        $data = $request->validate(['applicant_identity_id' => ['required', 'string']]);

        try {
            $service->assertCanAttachPayoutInstrument($context->tenantId(), $entityId, $data['applicant_identity_id']);
        } catch (TrusteeTakeoverBlockedException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, ['eligible' => true]);
    }

    public function authorizeBankMandate(
        string $entityId,
        Request $request,
        TenantContext $context,
        TempleAuthorityService $service,
    ): JsonResponse {
        $data = $request->validate([
            'applicant_identity_id' => ['required', 'string'],
            'co_sign_trustee_id' => ['required', 'string'],
            'reviewer_id' => ['required', 'string'],
            'countersigner_id' => ['nullable', 'string'],
        ]);

        try {
            $result = $service->authorizeInstitutionalBankMandate(
                tenantId: $context->tenantId(),
                entityId: $entityId,
                applicantIdentityId: $data['applicant_identity_id'],
                coSignTrusteeId: $data['co_sign_trustee_id'],
                reviewerId: $data['reviewer_id'],
                countersignerId: $data['countersigner_id'] ?? (string) $request->user()?->getKey(),
            );
        } catch (TrusteeTakeoverBlockedException $e) {
            return $this->err($request, $e->errorCode, $e->getMessage(), $e->statusCode);
        }

        return $this->ok($request, $result);
    }

    public function graph(
        string $entityId,
        Request $request,
        TenantContext $context,
        EntityRelationshipGraphService $graph,
    ): JsonResponse {
        return $this->ok($request, [
            'entity_id' => $entityId,
            'edges' => $graph->inspectConnectedNodes($context->tenantId(), 'temple', $entityId),
        ]);
    }
}

