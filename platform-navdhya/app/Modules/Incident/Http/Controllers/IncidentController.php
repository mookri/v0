<?php

declare(strict_types=1);

namespace App\Modules\Incident\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Incident\Application\IncidentCorrelationService;
use App\Modules\Incident\Application\IncidentLifecycleService;
use App\Modules\Incident\Application\InterimRestrictionService;
use App\Modules\Incident\Application\TempleAccessIncidentService;
use App\Modules\Incident\Domain\IncidentLifecycle;
use App\Modules\Incident\Models\Incident;
use App\Platform\Api\CursorPagination;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ceremony Incident Engine API Controller (E-TS-05).
 */
final class IncidentController
{
    public function __construct(
        private readonly IncidentLifecycleService $lifecycle,
        private readonly IncidentCorrelationService $correlations,
        private readonly TempleAccessIncidentService $templeAccess,
        private readonly InterimRestrictionService $restrictions,
        private readonly PolicyEvaluationEngine $authz,
    ) {
    }

    private function userHasCapability(?Authenticatable $user, string $capability): bool
    {
        if (! $user instanceof Authenticatable) {
            return false;
        }

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

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeSafety = $this->userHasCapability($user instanceof Authenticatable ? $user : null, 'support:safety_case:access');

        $query = Incident::query();
        if (! $canSeeSafety) {
            $query->where('safety_restricted', false)->where('severity', '!=', 'SEV_0');
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(fn (Incident $inc): array => $this->serialize($inc), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(IncidentLifecycle::TYPES)],
            'severity' => ['required', 'string', Rule::in(array_keys(IncidentLifecycle::SEVERITY_ORDER))],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'attributed_cause' => ['sometimes', 'string', Rule::in(IncidentLifecycle::CAUSES)],
            'impacts' => ['sometimes', 'array'],
        ]);

        $incident = $this->lifecycle->declareIncident([
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'attributed_cause' => $validated['attributed_cause'] ?? 'UNKNOWN',
            'actor_id' => (string) $user->getAuthIdentifier(),
            'impacts' => $validated['impacts'] ?? [],
        ]);

        return new JsonResponse([
            'data' => $this->serialize($incident),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function templeDenial(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'temple_ref' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:1000'],
            'pandit_id' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $incident = $this->templeAccess->recordAccessDenial(
            $validated['booking_id'],
            $validated['temple_ref'],
            (string) $user->getAuthIdentifier(),
            $validated['reason'],
            $validated['pandit_id'] ?? null,
        );

        return new JsonResponse([
            'data' => $this->serialize($incident),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function assignRole(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(IncidentLifecycle::COMMAND_ROLES)],
            'actor_id' => ['required', 'string', 'max:64'],
        ]);

        $role = $this->lifecycle->assignRole($id, $validated['role'], $validated['actor_id'], (string) ($user?->getAuthIdentifier() ?? 'system'));

        return new JsonResponse([
            'data' => [
                'id' => (string) $role->getKey(),
                'incident_id' => $role->incident_id,
                'role' => $role->role,
                'actor_id' => $role->actor_id,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function changeSeverity(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'severity' => ['required', 'string', Rule::in(array_keys(IncidentLifecycle::SEVERITY_ORDER))],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $incident = $this->lifecycle->changeSeverity($id, $validated['severity'], (string) ($user?->getAuthIdentifier() ?? 'system'), $validated['reason']);

        return new JsonResponse([
            'data' => $this->serialize($incident),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function setOutcome(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'attributed_cause' => ['required', 'string', Rule::in(IncidentLifecycle::CAUSES)],
            'devotee_outcome' => ['required', 'string', 'max:40'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $impact = $this->lifecycle->setImpactOutcome(
            $id,
            $validated['booking_id'],
            $validated['attributed_cause'],
            $validated['devotee_outcome'],
            $validated['notes'] ?? null,
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $impact->getKey(),
                'booking_id' => $impact->booking_id,
                'attributed_cause' => $impact->attributed_cause,
                'devotee_outcome' => $impact->devotee_outcome,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function postmortem(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'summary' => ['required', 'string', 'min:10', 'max:4000'],
            'root_cause_notes' => ['required', 'string', 'min:10', 'max:4000'],
            'contributing_factors' => ['sometimes', 'array'],
        ]);

        $pm = $this->lifecycle->recordPostmortem(
            $id,
            (string) ($user?->getAuthIdentifier() ?? 'system'),
            $validated['summary'],
            $validated['root_cause_notes'],
            $validated['contributing_factors'] ?? [],
        );

        return new JsonResponse([
            'data' => ['id' => (string) $pm->getKey(), 'incident_id' => $pm->incident_id, 'completed_at' => $pm->completed_at?->toJSON()],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function transition(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(IncidentLifecycle::TRANSITIONS))],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $incident = $this->lifecycle->transition(
                $id,
                $validated['status'],
                (string) ($user?->getAuthIdentifier() ?? 'system'),
                $validated['reason'],
                $this->userHasCapability($user instanceof Authenticatable ? $user : null, 'support:safety_case:access'),
            );
        } catch (\DomainException $e) {
            return new JsonResponse([
                'error' => ['code' => 'closure_precondition_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $this->serialize($incident),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function decideCorrelation(Request $request, string $correlationId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:confirm,revoke'],
            'reason' => ['required_if:decision,revoke', 'nullable', 'string', 'max:500'],
        ]);

        $actorId = (string) ($user?->getAuthIdentifier() ?? 'system');
        $corr = $validated['decision'] === 'confirm'
            ? $this->correlations->confirm($correlationId, $actorId)
            : $this->correlations->revoke($correlationId, $actorId, (string) $validated['reason']);

        return new JsonResponse([
            'data' => ['id' => (string) $corr->getKey(), 'status' => $corr->status],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function imposeRestriction(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'max:32'],
            'subject_id' => ['required', 'string', 'max:64'],
            'restriction_kind' => ['required', 'string', 'max:32'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'proportionality_note' => ['required', 'string', 'min:5', 'max:1000'],
            'expires_at' => ['required', 'date'],
            'incident_id' => ['sometimes', 'nullable', 'string', 'max:26'],
            'support_case_id' => ['sometimes', 'nullable', 'string', 'max:26'],
        ]);

        try {
            $rec = $this->restrictions->impose(
                $validated['subject_type'],
                $validated['subject_id'],
                $validated['restriction_kind'],
                $validated['reason'],
                $validated['proportionality_note'],
                (string) ($user?->getAuthIdentifier() ?? 'system'),
                (string) ($user?->getAuthIdentifier() ?? 'system'),
                CarbonImmutable::parse($validated['expires_at'])->utc(),
                $validated['incident_id'] ?? null,
                $validated['support_case_id'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $rec->getKey(),
                'restriction_kind' => $rec->restriction_kind,
                'expires_at' => $rec->expires_at?->toJSON(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function liftRestriction(Request $request, string $restrictionId): JsonResponse
    {
        $user = $request->user();
        $rec = $this->restrictions->lift($restrictionId, (string) ($user?->getAuthIdentifier() ?? 'system'));

        return new JsonResponse([
            'data' => ['id' => (string) $rec->getKey(), 'lifted_at' => $rec->lifted_at?->toJSON()],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Incident $incident): array
    {
        return [
            'id' => (string) $incident->getKey(),
            'incident_reference' => $incident->incident_reference,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'status' => $incident->status,
            'attributed_cause' => $incident->attributed_cause,
            'title' => $incident->title,
            'safety_restricted' => (bool) $incident->safety_restricted,
            'commander_id' => $incident->commander_id,
            'safety_lead_id' => $incident->safety_lead_id,
            'detected_at' => $incident->detected_at?->toJSON(),
            'resolved_at' => $incident->resolved_at?->toJSON(),
            'closed_at' => $incident->closed_at?->toJSON(),
        ];
    }
}
