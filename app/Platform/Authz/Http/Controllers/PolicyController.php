<?php

namespace App\Platform\Authz\Http\Controllers;

use App\Platform\Api\CodedHttpException;
use App\Platform\Api\CursorPagination;
use App\Platform\Authz\Contracts\Effect;
use App\Platform\Authz\Contracts\PolicyStatus;
use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Policy CRUD admin surface (T-0K01-01.4) at /api/v1/policies (SEC-021).
 *
 * Mounted behind auth + tenant resolution + capability middleware
 * (routes/platform/api.php): `platform:policy:view` for reads,
 * `platform:policy:configure` (sensitive) for mutations. Deliberately does
 * not extend the app-shell Controller base class so the kernel layer keeps
 * its dependency boundary (doc 01 §7).
 */
final class PolicyController
{
    private const FILTERABLE = ['status', 'effect'];

    private const RESERVED_PARAMS = ['limit', 'cursor'];

    public function __construct(private readonly PolicyService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->assertKnownQueryParams($request);

        $query = Policy::query();

        $status = $request->query('status');

        if (is_string($status) && $status !== '') {
            if (PolicyStatus::tryFrom($status) === null) {
                throw new InvalidPolicyPayloadException('status', 'status must be draft, active or disabled');
            }

            $query->where('status', $status);
        }

        $effect = $request->query('effect');

        if (is_string($effect) && $effect !== '') {
            if (Effect::tryFrom($effect) === null) {
                throw new InvalidPolicyPayloadException('effect', 'effect must be allow or deny');
            }

            $query->where('effect', $effect);
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(
                fn (Policy $policy): array => $this->serialize($policy),
                $items,
            ),
            'meta' => [
                'request_id' => $this->requestId($request),
                'page' => $page,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $policy = $this->service->create($request->all());

        return new JsonResponse([
            'data' => $this->serialize($policy),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function show(Request $request, string $policy): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->serialize(Policy::findOrFail($policy)),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function update(Request $request, string $policy): JsonResponse
    {
        $updated = $this->service->update(Policy::findOrFail($policy), $request->all());

        return new JsonResponse([
            'data' => $this->serialize($updated),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function activate(Request $request, string $policy): JsonResponse
    {
        $updated = $this->service->transitionStatus(Policy::findOrFail($policy), PolicyStatus::Active);

        return new JsonResponse([
            'data' => $this->serialize($updated),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function disable(Request $request, string $policy): JsonResponse
    {
        $updated = $this->service->transitionStatus(Policy::findOrFail($policy), PolicyStatus::Disabled);

        return new JsonResponse([
            'data' => $this->serialize($updated),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function assertKnownQueryParams(Request $request): void
    {
        $unknown = array_diff(
            array_keys($request->query()),
            [...self::FILTERABLE, ...self::RESERVED_PARAMS],
        );

        if ($unknown !== []) {
            throw new CodedHttpException(
                422,
                'filter_not_allowed',
                sprintf('Undeclared filter(s): %s. Filterable fields: %s.', implode(', ', $unknown), implode(', ', self::FILTERABLE)),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Policy $policy): array
    {
        return [
            'id' => (string) $policy->getKey(),
            'object' => 'policy',
            'tenant_id' => $policy->tenant_id,
            'name' => $policy->name,
            'description' => $policy->description,
            'effect' => $policy->effect,
            'capability_selector' => $policy->capability_selector,
            'scope_bindings' => $policy->scope_bindings ?? [],
            'conditions' => $policy->conditions ?? [],
            'priority' => (int) $policy->priority,
            'status' => $policy->status,
            'version' => (int) $policy->version,
            'created_at' => $policy->created_at?->toISOString(),
            'updated_at' => $policy->updated_at?->toISOString(),
        ];
    }

    private function requestId(Request $request): string
    {
        return (string) ($request->attributes->get('request_id') ?? '');
    }
}
