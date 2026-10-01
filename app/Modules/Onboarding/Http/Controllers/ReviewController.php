<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\PartnerApplication;
use App\Modules\Onboarding\Models\ReviewRecord;
use App\Modules\Onboarding\TrackCatalog;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reviewer surface (stage 7 port): SLA-flagged queue plus explicit verb
 * actions (approve / reject / request-info) so each decision carries its
 * own capability. Rejections require a reason and a countersigning second
 * reviewer; every decision appends to the audit trail (CON-004).
 */
final class ReviewController
{
    private const REVIEW_STATES = [
        PartnerApplication::STATUS_SUBMITTED,
        PartnerApplication::STATUS_UNDER_REVIEW,
        PartnerApplication::STATUS_APPEALED,
    ];

    public function __construct(private readonly AuditWriter $audit)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function queue(Request $request): JsonResponse
    {
        $items = PartnerApplication::query()
            ->whereIn('status', self::REVIEW_STATES)
            ->orderBy('submitted_at')
            ->get()
            ->map(function (PartnerApplication $application): array {
                $track = TrackCatalog::find($application->track, $application->track_version);
                $slaHours = $track['sla_hours'] ?? 72;
                $remaining = $application->submitted_at !== null
                    ? (strtotime($application->submitted_at) + $slaHours * 3600) * 1000 - (int) (microtime(true) * 1000)
                    : null;

                return [
                    'id' => (string) $application->getKey(),
                    'object' => 'partner_application',
                    'track' => $application->track,
                    'status' => $application->status,
                    'submitted_at' => $application->submitted_at,
                    'sla_breached' => $remaining === null ? null : $remaining < 0,
                    'sla_remaining_ms' => $remaining,
                ];
            })->values()->all();

        return new JsonResponse([
            'data' => $items,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        return $this->decide($request, $id, ReviewRecord::DECISION_APPROVED, null, null);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason_code' => ['required', 'string'],
            'second_reviewer' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validated['second_reviewer'] === (string) $request->user()->getAuthIdentifier()) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Rejections need a different second reviewer.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return $this->decide($request, $id, ReviewRecord::DECISION_REJECTED, $validated['reason_code'], $validated['note'] ?? null, $validated['second_reviewer']);
    }

    public function requestInfo(Request $request, string $id): JsonResponse
    {
        return $this->decide($request, $id, ReviewRecord::DECISION_REQUEST_INFO, null, null);
    }

    private function decide(
        Request $request,
        string $id,
        string $decision,
        ?string $reasonCode,
        ?string $note,
        ?string $secondReviewer = null,
    ): JsonResponse {
        $application = PartnerApplication::query()->find($id);

        if ($application === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Application not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        if ($application->status === PartnerApplication::STATUS_SUBMITTED) {
            $application->forceFill(['status' => PartnerApplication::STATUS_UNDER_REVIEW])->save();
        }

        if ($application->status === PartnerApplication::STATUS_SUBMITTED) {
            $application->forceFill(['status' => PartnerApplication::STATUS_UNDER_REVIEW])->save();
        }

        $allowed = match ($decision) {
            ReviewRecord::DECISION_APPROVED => [PartnerApplication::STATUS_UNDER_REVIEW, PartnerApplication::STATUS_APPEALED],
            ReviewRecord::DECISION_REJECTED => [PartnerApplication::STATUS_UNDER_REVIEW, PartnerApplication::STATUS_APPEALED],
            default => [PartnerApplication::STATUS_UNDER_REVIEW],
        };

        if (! in_array($application->status, $allowed, true)) {
            return new JsonResponse([
                'error' => ['code' => 'state_forbidden', 'message' => 'Cannot review in this state.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $to = match ($decision) {
            ReviewRecord::DECISION_APPROVED => PartnerApplication::STATUS_APPROVED,
            ReviewRecord::DECISION_REJECTED => PartnerApplication::STATUS_REJECTED,
            default => PartnerApplication::STATUS_DRAFT,
        };

        $now = gmdate('Y-m-d\TH:i:s.v\Z');
        $application->forceFill([
            'status' => $to,
            'reviewed_at' => $now,
            'reviewer_id' => (string) $request->user()->getAuthIdentifier(),
            'rejection_code' => $decision === ReviewRecord::DECISION_REJECTED ? $reasonCode : null,
        ])->save();

        $record = new ReviewRecord();
        $record->forceFill([
            'application_id' => (string) $application->getKey(),
            'reviewer_id' => (string) $request->user()->getAuthIdentifier(),
            'decision' => $decision,
            'reason_code' => $reasonCode,
            'note' => $secondReviewer !== null ? "[countersigned:{$secondReviewer}] ".($note ?? '') : $note,
        ]);
        $record->save();

        $this->audit->record(
            'onboarding.review.decided',
            ['resource_type' => 'partner_application', 'resource_id' => (string) $application->getKey()],
            null,
            ['decision' => $decision, 'reason_code' => $reasonCode]
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $application->getKey(),
                'object' => 'partner_application',
                'status' => $application->status,
                'review_id' => (string) $record->getKey(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
