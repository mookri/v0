<?php

namespace App\Platform\Authz\Http\Controllers;

use App\Platform\Api\CodedHttpException;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Authz\PrincipalResolver;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Authorization explain surface (T-0K01-02.3, SEC-022): restricted
 * endpoint returning the full precedence-chain trace for one capability
 * evaluation. Mounted behind auth + tenant resolution +
 * `platform:authz:explain` (routes/platform/api.php).
 *
 * Principals are "visible" when their membership belongs to the caller's
 * resolved tenant; anything else is denied with `authorization_denied`
 * (AC-T-0K01-02-4). Evaluation bypasses the decision cache so the trace
 * always reflects current authorization state. Deliberately does not
 * extend the app-shell Controller base class so the kernel layer keeps
 * its dependency boundary (doc 01 §7).
 */
final class AuthzExplainController
{
    private const ATTRIBUTE_FIELDS = ['amount', 'currency', 'org', 'time', 'status'];

    public function __construct(
        private readonly PolicyEvaluationEngine $engine,
        private readonly PrincipalResolver $principals,
        private readonly TenantContext $context,
    ) {
    }

    public function explain(Request $request): JsonResponse
    {
        $queryInput = $request->query();

        $validated = Validator::make(is_array($queryInput) ? $queryInput : [], [
            'membership_id' => ['required', 'string'],
            'capability' => ['required', 'string'],
            'amount' => ['sometimes', 'nullable', 'numeric'],
            'currency' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{3}$/'],
            'org' => ['sometimes', 'nullable', 'string', 'max:64'],
            'time' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string', 'max:64'],
        ])->validate();

        $membership = TenantMembership::query()
            ->whereKey((string) $validated['membership_id'])
            ->where('tenant_id', $this->context->tenantId())
            ->first();

        if ($membership === null) {
            // The caller may only explain principals visible in their own
            // tenant (AC-T-0K01-02-4); deny without disclosing existence.
            throw new CodedHttpException(
                403,
                'authorization_denied',
                'The requested principal is not visible to the caller.',
            );
        }

        $attributes = [];

        foreach (self::ATTRIBUTE_FIELDS as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $attributes[$field] = $validated[$field];
            }
        }

        $decision = $this->engine->evaluate(new AuthorizationRequest(
            principal: $this->principals->resolveById($membership->user_id),
            capability: (string) $validated['capability'],
            membershipId: (string) $membership->getKey(),
            attributes: $attributes,
        ), bypassCache: true);

        return new JsonResponse([
            'data' => $decision->toArray(),
            'meta' => ['request_id' => (string) ($request->attributes->get('request_id') ?? '')],
        ]);
    }
}
