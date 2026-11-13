<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Domain\Trust\Parties\PartyEntitlement;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Support\Application\OpenSupportCaseUseCase;
use App\Modules\Support\Application\ResolveSupportCaseUseCase;
use App\Modules\Support\Application\SupportCaseTransitionService;
use App\Modules\Support\Domain\Enums\CaseCategory;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\CaseType;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseMessage;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Authoritative SupportCase API controller (TS-03 / TS-04 / TS-10 / TS-15).
 * Generic staff queue (`support:case:view`) strictly excludes RESTRICTED_SAFETY cases.
 */
final class SupportCaseController
{
    public function __construct(
        private readonly OpenSupportCaseUseCase $opener,
        private readonly SupportCaseTransitionService $transitions,
        private readonly ResolveSupportCaseUseCase $resolver,
        private readonly \App\Modules\Support\Application\SafetyCaseService $safety,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'case_type' => ['required', 'string', Rule::in(array_map(static fn (CaseType $t): string => $t->value, CaseType::cases()))],
            'category' => ['required', 'string', Rule::in(array_map(static fn (CaseCategory $c): string => $c->value, CaseCategory::cases()))],
            'booking_id' => ['sometimes', 'nullable', 'string', 'max:26'],
            'intake_channel' => ['sometimes', 'string', 'in:web,app,phone,email,nch_referral'],
        ]);

        try {
            $case = $this->opener->execute([
                'reporter_user_id' => (int) $user->getAuthIdentifier(),
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'case_type' => CaseType::from($validated['case_type']),
                'category' => CaseCategory::from($validated['category']),
                'booking_id' => $validated['booking_id'] ?? null,
                'intake_channel' => $validated['intake_channel'] ?? 'web',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $data = $this->serializeCase($case);
        if ($case->visibility_scope === PartyEntitlement::SCOPE_RESTRICTED_SAFETY) {
            $data['emergency_signposting'] = $this->safety->emergencySignposting();
        }

        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        [$cases, $page] = CursorPagination::paginate(
            SupportCase::query()->where('reporter_user_id', $user->getAuthIdentifier()),
            $request,
        );

        return new JsonResponse([
            'data' => array_map(fn (SupportCase $case): array => $this->serializeCase($case), $cases),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function staffIndex(Request $request): JsonResponse
    {
        $query = SupportCase::query()
            ->where('visibility_scope', '!=', PartyEntitlement::SCOPE_RESTRICTED_SAFETY);

        [$cases, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(fn (SupportCase $case): array => $this->serializeCase($case), $cases),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function staffShow(Request $request, string $id): JsonResponse
    {
        $case = SupportCase::query()
            ->where('id', $id)
            ->where('visibility_scope', '!=', PartyEntitlement::SCOPE_RESTRICTED_SAFETY)
            ->first();

        if ($case === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Support case not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $messages = SupportCaseMessage::query()
            ->where('case_id', (string) $case->getKey())
            ->orderBy('id')
            ->get();

        $resolution = SupportCaseResolution::query()
            ->where('case_id', (string) $case->getKey())
            ->latest('id')
            ->first();

        return new JsonResponse([
            'data' => [
                ...$this->serializeCase($case),
                'messages' => $messages->map(static fn (SupportCaseMessage $m): array => [
                    'id' => (string) $m->getKey(),
                    'author_id' => $m->author_id,
                    'author_role' => $m->author_role,
                    'visibility_scope' => $m->visibility_scope,
                    'body' => $m->body,
                ])->all(),
                'resolution' => $resolution === null ? null : [
                    'id' => (string) $resolution->getKey(),
                    'remedy_type' => $resolution->remedy_type,
                    'decision_summary' => $resolution->decision_summary,
                    'decided_by_id' => $resolution->decided_by_id,
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function transition(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_map(static fn (CaseStatus $s): string => $s->value, CaseStatus::cases()))],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $case = $this->transitions->transition(
                $id,
                CaseStatus::from($validated['status']),
                (string) $user->getAuthIdentifier(),
                'staff',
                'transition',
                $validated['reason'],
            );
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'SupportCaseResolution')
                ? 'resolution_required'
                : 'state_forbidden';

            return new JsonResponse([
                'error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], $code === 'resolution_required' ? 422 : 409);
        }

        return new JsonResponse([
            'data' => $this->serializeCase($case),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'remedy_type' => ['required', 'string', Rule::in(array_map(static fn (RemedyType $r): string => $r->value, RemedyType::cases()))],
            'decision_summary' => ['required', 'string', 'min:5', 'max:2000'],
            'monetary_eligible' => ['sometimes', 'boolean'],
            'close' => ['sometimes', 'boolean'],
            'close_immediately' => ['sometimes', 'boolean'],
        ]);

        try {
            $case = $this->resolver->resolve(
                $id,
                RemedyType::from($validated['remedy_type']),
                $validated['decision_summary'],
                (string) $user->getAuthIdentifier(),
                (bool) ($validated['monetary_eligible'] ?? false),
                null,
                null,
                (bool) ($validated['close'] ?? $validated['close_immediately'] ?? false),
            );
        } catch (\InvalidArgumentException $e) {
            $code = match (true) {
                str_contains($e->getMessage(), 'safety_no_mediation_forbidden') => 'safety_no_mediation_forbidden',
                str_contains($e->getMessage(), 'forced_credit_forbidden') => 'forced_credit_forbidden',
                default => 'validation_failed',
            };

            return new JsonResponse([
                'error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'grievance_decision_required') ? 'grievance_decision_required' : 'state_forbidden';

            return new JsonResponse([
                'error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 409);
        }

        return new JsonResponse([
            'data' => $this->serializeCase($case),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCase(SupportCase $case): array
    {
        return [
            'id' => (string) $case->getKey(),
            'case_reference' => $case->case_reference,
            'case_type' => $case->case_type,
            'category' => $case->category,
            'status' => $case->status,
            'urgency' => $case->urgency,
            'visibility_scope' => $case->visibility_scope,
            'booking_id' => $case->booking_id,
            'reopen_count' => $case->reopen_count,
            'created_at' => $case->created_at?->toJSON(),
            'resolved_at' => $case->resolved_at?->toJSON(),
            'closed_at' => $case->closed_at?->toJSON(),
        ];
    }
}

