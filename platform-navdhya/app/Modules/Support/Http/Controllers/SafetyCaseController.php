<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Domain\Trust\Parties\PartyEntitlement;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Support\Application\SafetyCaseService;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseEvidence;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Platform\Api\CursorPagination;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Compartmentalized Safety Queue Controller (E-TS-07 / TS-15 / ADR-0067).
 * Strictly isolated from generic support staff via `support:safety_case:access`.
 */
final class SafetyCaseController
{
    public function __construct(
        private readonly SafetyCaseService $safety,
        private readonly PolicyEvaluationEngine $authz,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    private function isSafetyLead(Authenticatable $user): bool
    {
        try {
            $this->authz->ensure(new AuthorizationRequest(principal: $user, capability: 'support:safety_case:access'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function signposting(Request $request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->safety->emergencySignposting(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = SupportCase::query()
            ->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY);

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (SupportCase $c): array => [
                'id' => (string) $c->getKey(),
                'case_reference' => $c->case_reference,
                'category' => $c->category,
                'status' => $c->status,
                'urgency' => $c->urgency,
                'visibility_scope' => $c->visibility_scope,
            ], $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)
            ->first();

        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Safety case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $messages = SupportCaseMessage::query()->where('tenant_id', $tenantId)->where('case_id', $id)->orderBy('id')->get();
        $evidence = SupportCaseEvidence::query()->where('tenant_id', $tenantId)->where('case_id', $id)->get();

        return new JsonResponse([
            'data' => [
                'id' => (string) $case->getKey(),
                'case_reference' => $case->case_reference,
                'category' => $case->category,
                'status' => $case->status,
                'emergency_signposting' => $this->safety->emergencySignposting(),
                'messages' => $messages->map(fn (SupportCaseMessage $m): array => [
                    'id' => (string) $m->getKey(),
                    'visibility_scope' => $m->visibility_scope,
                    'body' => $this->safety->decryptMessageBody($m->body),
                ])->all(),
                'evidence' => $evidence->map(static fn (SupportCaseEvidence $e): array => [
                    'id' => (string) $e->getKey(),
                    'evidence_reference' => $e->evidence_reference,
                    'sensitivity_class' => $e->sensitivity_class,
                    'description' => $e->description,
                ])->all(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function addConfidentialMessage(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $id)->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Safety case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $isReporter = (int) $case->reporter_user_id === (int) $user->getAuthIdentifier();
        if (! $isReporter && ! $this->isSafetyLead($user)) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Safety case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $validated = $request->validate(['body' => ['required', 'string', 'min:3', 'max:5000']]);
        $msg = $this->safety->addConfidentialMessage($id, (string) $user->getAuthIdentifier(), $isReporter ? 'devotee' : 'safety_lead', $validated['body']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $msg->getKey(),
                'visibility_scope' => $msg->visibility_scope,
                'encrypted' => str_starts_with($msg->body, SafetyCaseService::ENCRYPTED_PREFIX),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function uploadEvidence(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $id)->where('visibility_scope', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Safety case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $isReporter = (int) $case->reporter_user_id === (int) $user->getAuthIdentifier();
        if (! $isReporter && ! $this->isSafetyLead($user)) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Safety case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:64'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $evidence = $this->safety->storeSafetyEvidence($id, (string) $user->getAuthIdentifier(), $validated['description'], $validated['purpose'], $validated['content']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $evidence->getKey(),
                'evidence_reference' => $evidence->evidence_reference,
                'sensitivity_class' => $evidence->sensitivity_class,
                'storage_reference' => $evidence->storage_reference,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function metrics(Request $request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->safety->triageSlaMetrics(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
