<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Domain\Trust\Parties\PartyEntitlement;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Support\Application\GrievanceService;
use App\Modules\Support\Domain\Grievance\NchReferralService;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Statutory Grievance Compliance & NCH Referral Controller (E-TS-03 / TS-11).
 */
final class GrievanceController
{
    public function __construct(
        private readonly GrievanceService $grievances,
        private readonly NchReferralService $nch,
        private readonly PolicyEvaluationEngine $authz,
    ) {
    }

    private function userHasCapability(Authenticatable $user, string $capability): bool
    {
        try {
            $this->authz->ensure(new AuthorizationRequest(principal: $user, capability: $capability));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function publicOfficerInfo(Request $request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->grievances->publicGrievanceOfficerInfo(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function promoteToGrievance(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Support case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        if ($case->visibility_scope === PartyEntitlement::SCOPE_RESTRICTED_SAFETY && ! $this->userHasCapability($user, 'support:safety_case:access')) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Support case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $isReporter = (int) $case->reporter_user_id === (int) $user->getAuthIdentifier();
        if (! $isReporter && ! $this->userHasCapability($user, 'support:grievance:manage')) {
            return new JsonResponse(['error' => ['code' => 'forbidden', 'message' => 'Only the reporter or Grievance team can formalize a grievance.', 'request_id' => $this->requestId($request)]], 403);
        }

        $firstMessage = SupportCaseMessage::query()
            ->where('tenant_id', $tenantId)
            ->where('case_id', $id)
            ->orderBy('created_at')
            ->first();

        $verbatim = (string) ($request->input('verbatim_complaint') ?: ($firstMessage?->body ?? $case->subject));
        $grievance = $this->grievances->registerFormalGrievance($id, $verbatim);

        return new JsonResponse([
            'data' => [
                'id' => (string) $grievance->getKey(),
                'case_id' => $id,
                'case_reference' => $case->case_reference,
                'complaint_record_id' => $grievance->complaint_record_id,
                'copy_issued_at' => $grievance->copy_issued_at?->toJSON(),
                'statutory_ack_due_at' => $grievance->statutory_ack_due_at?->toJSON(),
                'statutory_redress_due_at' => $grievance->statutory_redress_due_at?->toJSON(),
                'policy_version' => $grievance->policy_version,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function complaintCopy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Grievance not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $isReporter = (int) $case->reporter_user_id === (int) $user->getAuthIdentifier();
        if (! $isReporter && ! $this->userHasCapability($user, 'support:grievance:manage')) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Grievance not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        return new JsonResponse([
            'data' => $this->grievances->complaintCopyPayload($id),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function recordDecision(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', 'min:5', 'max:4000'],
        ]);

        $grievance = $this->grievances->recordOfficerDecision($id, (string) $user->getAuthIdentifier(), $validated['decision']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $grievance->getKey(),
                'case_id' => $grievance->case_id,
                'officer_id' => $grievance->officer_id,
                'officer_decision' => $grievance->officer_decision,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function nchReferral(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:prepare,record,reconcile'],
            'external_reference' => ['required_if:action,record', 'nullable', 'string', 'max:128'],
            'referral_id' => ['required_if:action,reconcile', 'nullable', 'string', 'max:26'],
            'external_status' => ['required_if:action,reconcile', 'nullable', 'string', 'max:64'],
        ]);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        if ($validated['action'] === 'prepare') {
            return new JsonResponse([
                'data' => $this->nch->prepareManualPacket($case),
                'meta' => ['request_id' => $this->requestId($request)],
            ]);
        }

        if ($validated['action'] === 'record') {
            $ref = $this->nch->recordExternalReference($id, (string) $validated['external_reference']);

            return new JsonResponse([
                'data' => [
                    'id' => (string) $ref->getKey(),
                    'case_id' => $ref->case_id,
                    'channel' => $ref->channel,
                    'external_reference' => $ref->external_reference,
                    'adapter_mode' => $ref->adapter_mode,
                    'external_status' => $ref->external_status,
                ],
                'meta' => ['request_id' => $this->requestId($request)],
            ], 201);
        }

        $ref = $this->nch->reconcileStatus((string) $validated['referral_id'], (string) $validated['external_status']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $ref->getKey(),
                'external_status' => $ref->external_status,
                'last_reconciled_at' => $ref->last_reconciled_at?->toJSON(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
