<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Feedback\Application\ModerateFeedbackAndAppealService;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Application\SubmitFeedbackUseCase;
use App\Modules\Feedback\Models\FeedbackProviderResponse;
use App\Modules\Feedback\Models\FeedbackPublication;
use App\Modules\Feedback\Models\FeedbackSubmission;
use App\Platform\Api\CursorPagination;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feedback & Honest Quality API Controller (TS-05, TS-06, TS-08, TS-09).
 * Standalone POJO controller (Deptrac ModuleHttp compliant).
 */
final class FeedbackController
{
    public function __construct(
        private readonly SubmitFeedbackUseCase $submitter,
        private readonly ModerateFeedbackAndAppealService $moderation,
        private readonly RecomputeFeedbackQualityService $quality,
        private readonly ReadsBookings $bookings,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function checkEligibility(Request $request, string $bookingId): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $snapshot = $this->bookings->bookingSnapshotForTrust($bookingId);
        if ($snapshot === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Booking not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $redirectToSupport = in_array($snapshot['status'], ['CANCELLED', 'NO_SHOW', 'FAILED', 'EXPIRED'], true);

        return new JsonResponse([
            'data' => [
                'booking_id' => $bookingId,
                'eligible' => $snapshot['status'] === 'COMPLETED' && ! $this->quality->hasActiveSafetySuppression($bookingId),
                'status' => $snapshot['status'],
                'redirect_flow' => $redirectToSupport ? 'support_report' : null,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'booking_id' => ['required', 'string', 'max:26'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'share_publicly' => ['sometimes', 'boolean'],
            'pandit_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'dimensions' => ['sometimes', 'array'],
        ]);

        try {
            $result = $this->submitter->execute([
                'booking_id' => $validated['booking_id'],
                'acting_user_id' => (int) $user->getAuthIdentifier(),
                'overall_rating' => (int) $validated['overall_rating'],
                'comment' => $validated['comment'] ?? null,
                'share_publicly' => (bool) ($validated['share_publicly'] ?? true),
                'client_subject_id' => $validated['pandit_id'] ?? null,
                'dimensions' => $validated['dimensions'] ?? [],
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 404);
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'subject_mismatch') ? 'subject_mismatch' : 'validation_failed';

            return new JsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        } catch (\OverflowException $e) {
            return new JsonResponse(['error' => ['code' => 'conflict', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 409);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'redirect_support_report') ? 'redirect_support_report' : 'state_forbidden';

            return new JsonResponse([
                'error' => [
                    'code' => $code,
                    'message' => $e->getMessage(),
                    'redirect_flow' => $code === 'redirect_support_report' ? 'support_report' : null,
                    'request_id' => $this->requestId($request),
                ],
            ], 409);
        }

        $sub = $result['submission'];
        $pub = $result['publication'];

        return new JsonResponse([
            'data' => [
                'id' => (string) $sub->getKey(),
                'review_reference' => $sub->review_reference,
                'subject_type' => $sub->subject_type,
                'subject_id' => $sub->subject_id,
                'overall_rating' => $sub->overall_rating,
                'publication_state' => $pub->state,
                'reason_code' => $pub->reason_code,
                'held_anomalies' => $result['held_anomalies'],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function respond(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'response_text' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $sub = FeedbackSubmission::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($sub === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Submission not found.', 'request_id' => $this->requestId($request)]], 404);
        }

        $resp = FeedbackProviderResponse::query()->create([
            'tenant_id' => $tenantId,
            'submission_id' => (string) $sub->getKey(),
            'subject_type' => $sub->subject_type,
            'subject_id' => $sub->subject_id,
            'author_user_id' => (int) $user->getAuthIdentifier(),
            'response_text' => $validated['response_text'],
            'state' => 'PUBLISHED',
        ]);

        return new JsonResponse([
            'data' => [
                'id' => (string) $resp->getKey(),
                'submission_id' => $resp->submission_id,
                'response_text' => $resp->response_text,
                'state' => $resp->state,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function appeal(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:devotee,provider'],
            'grounds' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $appeal = $this->moderation->fileAppeal($id, (string) $user->getAuthIdentifier(), $validated['role'], $validated['grounds']);

        return new JsonResponse([
            'data' => [
                'id' => (string) $appeal->getKey(),
                'submission_id' => $appeal->submission_id,
                'status' => $appeal->status,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function moderate(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:PUBLISH,REDACT,HOLD,WITHHOLD'],
            'reason_code' => ['required', 'string', 'max:64'],
            'redacted_text' => ['sometimes', 'nullable', 'string'],
            'counts_toward' => ['sometimes', 'array'],
        ]);

        try {
            $pub = $this->moderation->moderate(
                $id,
                (string) $user->getAuthIdentifier(),
                $validated['action'],
                $validated['reason_code'],
                $validated['redacted_text'] ?? null,
                $validated['counts_toward'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => ['code' => 'validation_failed', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 422);
        }

        return new JsonResponse([
            'data' => [
                'submission_id' => $pub->submission_id,
                'state' => $pub->state,
                'redacted_text' => $pub->redacted_text,
                'reason_code' => $pub->reason_code,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function decideAppeal(Request $request, string $appealId): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return new JsonResponse(['error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)]], 401);
        }

        $validated = $request->validate([
            'overturn' => ['required', 'boolean'],
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $appeal = $this->moderation->decideAppeal(
                $appealId,
                (string) $user->getAuthIdentifier(),
                (bool) $validated['overturn'],
                $validated['notes'],
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => ['code' => 'independence_violation', 'message' => $e->getMessage(), 'request_id' => $this->requestId($request)]], 409);
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $appeal->getKey(),
                'status' => $appeal->status,
                'decider_id' => $appeal->decider_id,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function providerQuality(Request $request, string $subjectType, string $subjectId): JsonResponse
    {
        $summary = $this->quality->summaryForSubject($subjectType, $subjectId);

        return new JsonResponse([
            'data' => $summary,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function providerReviews(Request $request, string $subjectType, string $subjectId): JsonResponse
    {
        $query = FeedbackSubmission::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);

        [$submissions, $page] = CursorPagination::paginate($query, $request);
        $ids = array_map(static fn (FeedbackSubmission $s): string => (string) $s->getKey(), $submissions);

        $pubs = FeedbackPublication::query()
            ->whereIn('submission_id', $ids)
            ->where('state', FeedbackPublication::STATE_PUBLISHED)
            ->get()
            ->keyBy('submission_id');

        $responses = FeedbackProviderResponse::query()
            ->whereIn('submission_id', $ids)
            ->where('state', 'PUBLISHED')
            ->get()
            ->keyBy('submission_id');

        $items = [];
        foreach ($submissions as $sub) {
            $pub = $pubs->get((string) $sub->getKey());
            if ($pub === null) {
                continue;
            }
            $resp = $responses->get((string) $sub->getKey());
            $items[] = [
                'id' => (string) $sub->getKey(),
                'review_reference' => $sub->review_reference,
                'overall_rating' => $sub->overall_rating,
                'text' => $pub->redacted_text ?? $sub->original_text,
                'is_redacted' => $pub->redacted_text !== null,
                'published_at' => $pub->published_at?->toJSON(),
                'provider_response' => $resp?->response_text,
            ];
        }

        return new JsonResponse([
            'data' => $items,
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function methodology(Request $request): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'verified_booking_rule' => 'Only completed bookings verified via server assignment and party entitlement qualify.',
                'aggregation_method' => 'Category-specific Bayesian average with exponential time decay and Wilson 95% lower bounds for binary rates.',
                'confidence_thresholds' => 'Providers with fewer than 5 reviews show New provider without a prominent headline average.',
                'exclusion_policy' => 'Incidents attributed to venue, weather, platform, or devotee factors are excluded from provider reliability scores.',
                'certification_disclosure' => 'Implements BIS IS 19000:2022 review-integrity principles internally; not a third-party or government quality mark.',
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}

