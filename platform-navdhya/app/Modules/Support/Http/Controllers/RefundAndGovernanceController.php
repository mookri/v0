<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Support\Application\ProviderImprovementPlanService;
use App\Modules\Support\Application\SupportAppealService;
use App\Modules\Support\Application\SupportDossierBuilder;
use App\Modules\Support\Application\SupportRefundService;
use App\Modules\Support\Models\SupportCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Refunds, Independent Case Appeals, Closed-Loop Dossiers, & Improvement Plans (E-TS-08).
 */
final class RefundAndGovernanceController
{
    public function __construct(
        private readonly SupportRefundService $refunds,
        private readonly SupportAppealService $appeals,
        private readonly SupportDossierBuilder $dossiers,
        private readonly ProviderImprovementPlanService $plans,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function requestRefund(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'basis' => ['required', 'string', 'max:190'],
            'proposed_type' => ['required', 'string', 'in:FULL,PARTIAL'],
            'remedy_instrument' => ['required', 'string', 'max:40'],
            'devotee_freely_consented_at' => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            $req = $this->refunds->requestRefund(
                $id,
                $validated['booking_id'],
                (int) $validated['amount_minor'],
                $validated['basis'],
                $validated['proposed_type'],
                $validated['remedy_instrument'],
                (string) ($user?->getAuthIdentifier() ?? 'system'),
                isset($validated['devotee_freely_consented_at'])
                    ? CarbonImmutable::parse($validated['devotee_freely_consented_at'])->utc()
                    : null,
            );
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'forced_credit_forbidden') ? 'forced_credit_forbidden' : 'validation_failed';

            return new JsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $req->getKey(),
                'case_id' => $req->case_id,
                'amount_minor' => $req->amount_minor,
                'remedy_instrument' => $req->remedy_instrument,
                'state' => $req->state,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function approveRefund(Request $request, string $refundId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'finance_execution_reference' => ['required', 'string', 'max:64'],
        ]);

        $req = $this->refunds->approveAndRecordFinanceExecution(
            $refundId,
            (string) ($user?->getAuthIdentifier() ?? 'system'),
            $validated['finance_execution_reference'],
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $req->getKey(),
                'state' => $req->state,
                'finance_execution_reference' => $req->finance_execution_reference,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function fileAppeal(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:devotee,provider'],
            'grounds' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $appeal = $this->appeals->fileAppeal($id, (string) $user->getAuthIdentifier(), $validated['role'], $validated['grounds']);

        return new JsonResponse([
            'data' => ['id' => (string) $appeal->getKey(), 'status' => $appeal->status],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function decideAppeal(Request $request, string $appealId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'overturn' => ['required', 'boolean'],
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $appeal = $this->appeals->decideAppeal($appealId, (string) ($user?->getAuthIdentifier() ?? 'system'), (bool) $validated['overturn'], $validated['notes']);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => ['code' => 'independence_violation', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 409);
        }

        return new JsonResponse([
            'data' => ['id' => (string) $appeal->getKey(), 'status' => $appeal->status, 'outcome' => $appeal->outcome],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function dossier(Request $request, string $id): JsonResponse
    {
        $case = SupportCase::query()->where('id', $id)->first();
        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        return new JsonResponse([
            'data' => $this->dossiers->build($id),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function proposePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'max:32'],
            'subject_id' => ['required', 'string', 'max:64'],
            'trigger_pattern' => ['required', 'string', 'max:120'],
            'incident_refs' => ['required', 'array'],
            'actions' => ['required', 'array'],
            'booking_ids' => ['sometimes', 'array'],
            'involves_safety_case' => ['sometimes', 'boolean'],
        ]);

        try {
            $plan = $this->plans->propose(
                $validated['subject_type'],
                $validated['subject_id'],
                $validated['trigger_pattern'],
                $validated['incident_refs'],
                $validated['actions'],
                $validated['booking_ids'] ?? [],
                (bool) ($validated['involves_safety_case'] ?? false),
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => ['code' => 'safety_pip_forbidden', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $plan->getKey(),
                'state' => $plan->state,
                'excluded_external_causes_count' => $plan->excluded_external_causes_count,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function acknowledgePlan(Request $request, string $planId): JsonResponse
    {
        $validated = $request->validate(['notes' => ['required', 'string', 'max:2000']]);
        $plan = $this->plans->acknowledge($planId, $validated['notes']);

        return new JsonResponse([
            'data' => ['id' => (string) $plan->getKey(), 'state' => $plan->state],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
