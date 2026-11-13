<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Privacy\Erasure\ProcessPartnerErasureJob;
use App\Domain\Privacy\Models\PrivacySubjectRequest;
use App\Domain\Verification\Claims\ClaimManager;
use App\Modules\Onboarding\Models\ApplicationDocument;
use App\Modules\Onboarding\Models\PartnerApplication;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Self-serve partner Data Subject Rights & granular consent surface (T-TR06-04 / Closes T-17).
 */
final class PrivacyRightsController
{
    public function __construct(
        private readonly ConsentManager $consents,
        private readonly ClaimManager $claims,
        private readonly AuditWriter $audit,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function grantConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string'],
            'policy_version' => ['sometimes', 'string', 'max:48'],
        ]);

        $purpose = ConsentPurpose::tryFrom((string) $validated['purpose']);
        if ($purpose === null) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Unknown consent purpose.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $identityId = (string) $request->user()->getAuthIdentifier();

        $consent = $this->consents->grant(
            tenantId: $tenantId,
            subjectIdentityId: $identityId,
            purpose: $purpose,
            policyVersion: (string) ($validated['policy_version'] ?? 'dpdp-2026-v1'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $consent->getKey(),
                'purpose' => $consent->purpose,
                'granted' => true,
                'policy_version' => $consent->policy_version,
                'active_purposes' => $this->consents->activePurposesFor($tenantId, $identityId),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function withdrawConsent(Request $request): JsonResponse
    {
        $validated = $request->validate(['purpose' => ['required', 'string']]);
        $purpose = ConsentPurpose::tryFrom((string) $validated['purpose']);
        if ($purpose === null) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Unknown consent purpose.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $identityId = (string) $request->user()->getAuthIdentifier();
        $revokedCount = $this->consents->withdraw($tenantId, $identityId, $purpose);

        return new JsonResponse([
            'data' => [
                'purpose' => $purpose->value,
                'revoked' => $revokedCount,
                'active_purposes' => $this->consents->activePurposesFor($tenantId, $identityId),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function access(Request $request): JsonResponse
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $identityId = (string) $request->user()->getAuthIdentifier();

        $apps = PartnerApplication::query()->where('identity_id', $identityId)->get();
        $docIds = ApplicationDocument::query()->whereIn('application_id', $apps->pluck('id')->all())->get(['id', 'application_id', 'kind', 'status', 'sha256', 'created_at']);
        $activeClaims = $this->claims->activeClaimsFor($tenantId, 'pandit', $identityId);

        $dsr = new PrivacySubjectRequest();
        $dsr->forceFill([
            'tenant_id' => $tenantId,
            'subject_identity_id' => $identityId,
            'request_type' => 'access',
            'status' => 'completed',
            'completed_at' => now()->utc(),
            'handled_by' => $identityId,
        ])->save();

        $this->audit->record(
            'privacy.dsr.accessed',
            ['module' => 'privacy', 'resource_type' => 'privacy_subject_request', 'resource_id' => (string) $dsr->getKey()],
            null,
            ['subject_identity_id' => $identityId],
        );

        return new JsonResponse([
            'data' => [
                'request_id' => (string) $dsr->getKey(),
                'subject_identity_id' => $identityId,
                'active_consents' => $this->consents->activePurposesFor($tenantId, $identityId),
                'applications' => $apps->map(static fn(PartnerApplication $a): array => [
                    'id' => (string) $a->getKey(),
                    'track' => $a->track,
                    'status' => $a->status,
                    'submitted_at' => $a->submitted_at?->toJSON(),
                ])->values()->all(),
                'documents' => $docIds->toArray(),
                'claims_count' => $activeClaims->count(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function correction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'correction_payload' => ['required', 'array'],
        ]);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $identityId = (string) $request->user()->getAuthIdentifier();

        $dsr = new PrivacySubjectRequest();
        $dsr->forceFill([
            'tenant_id' => $tenantId,
            'subject_identity_id' => $identityId,
            'request_type' => 'correction',
            'status' => 'received',
            'reason' => $validated['reason'],
            'correction_payload' => $validated['correction_payload'],
        ])->save();

        $this->audit->record(
            'privacy.dsr.correction_requested',
            ['module' => 'privacy', 'resource_type' => 'privacy_subject_request', 'resource_id' => (string) $dsr->getKey()],
            null,
            ['subject_identity_id' => $identityId],
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $dsr->getKey(),
                'request_type' => 'correction',
                'status' => 'received',
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function erasure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $identityId = (string) $request->user()->getAuthIdentifier();

        $dsr = new PrivacySubjectRequest();
        $dsr->forceFill([
            'tenant_id' => $tenantId,
            'subject_identity_id' => $identityId,
            'request_type' => 'erasure',
            'status' => 'received',
            'reason' => $validated['reason'] ?? 'partner_erasure_request',
        ])->save();

        ProcessPartnerErasureJob::dispatchSync(
            $tenantId,
            $identityId,
            (string) $dsr->getKey(),
            $identityId,
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $dsr->getKey(),
                'request_type' => 'erasure',
                'status' => (string) $dsr->fresh()?->status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}

