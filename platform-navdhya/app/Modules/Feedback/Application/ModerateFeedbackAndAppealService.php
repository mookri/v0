<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Application;

use App\Modules\Feedback\Domain\Services\PiiRedactor;
use App\Modules\Feedback\Models\FeedbackAggregationState;
use App\Modules\Feedback\Models\FeedbackAppeal;
use App\Modules\Feedback\Models\FeedbackModerationAction;
use App\Modules\Feedback\Models\FeedbackPublication;
use App\Modules\Feedback\Models\FeedbackSubmission;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Moderation, PII-only redaction (original untouched), counts_toward toggling,
 * and independent two-way appeal decisions (TS-06, ADR-0061).
 */
final class ModerateFeedbackAndAppealService
{
    public function __construct(
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @param  list<string>|null  $countsToward
     */
    public function moderate(
        string $submissionId,
        string $moderatorId,
        string $action,
        string $reasonCode,
        ?string $customRedactedText = null,
        ?array $countsToward = null,
    ): FeedbackPublication {
        if (strtoupper($reasonCode) === 'RELIGIOUS_DISAGREEMENT') {
            throw new \InvalidArgumentException('Religious or tradition disagreement is prohibited as a review suppression ground (§5.3).');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        return (new FeedbackSubmission())->getConnection()->transaction(function () use (
            $tenantId,
            $submissionId,
            $moderatorId,
            $action,
            $reasonCode,
            $customRedactedText,
            $countsToward,
            $now
        ): FeedbackPublication {
            $submission = FeedbackSubmission::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $submissionId)
                ->firstOrFail();

            $pub = FeedbackPublication::query()
                ->where('tenant_id', $tenantId)
                ->where('submission_id', $submissionId)
                ->lockForUpdate()
                ->firstOrFail();

            $actionUpper = strtoupper($action);
            $newState = match ($actionUpper) {
                'PUBLISH', 'REDACT' => FeedbackPublication::STATE_PUBLISHED,
                'HOLD' => FeedbackPublication::STATE_HELD,
                'WITHHOLD' => FeedbackPublication::STATE_WITHHELD,
                default => throw new \InvalidArgumentException("Unsupported moderation action: {$action}"),
            };

            $redacted = $pub->redacted_text;
            if ($actionUpper === 'REDACT') {
                $redacted = $customRedactedText ?? PiiRedactor::redact($submission->original_text)['redacted'];
            }

            $pub->forceFill([
                'state' => $newState,
                'redacted_text' => $redacted,
                'reason_code' => $reasonCode,
                'published_at' => $newState === FeedbackPublication::STATE_PUBLISHED ? ($pub->published_at ?? $now->toDateTimeString()) : null,
            ])->save();

            if ($countsToward !== null) {
                FeedbackAggregationState::query()
                    ->where('tenant_id', $tenantId)
                    ->where('submission_id', $submissionId)
                    ->update(['counts_toward' => $countsToward]);
            }

            FeedbackModerationAction::query()->create([
                'tenant_id' => $tenantId,
                'submission_id' => $submissionId,
                'action' => $actionUpper,
                'actor_id' => $moderatorId,
                'reason_code' => $reasonCode,
                'policy_version' => 'v1',
                'appealable' => true,
                'occurred_at' => $now->toDateTimeString(),
            ]);

            $this->audit->record(
                'feedback.publication.moderated',
                ['module' => 'feedback', 'resource_type' => 'feedback_publication', 'resource_id' => $submissionId],
                null,
                ['action' => $actionUpper, 'reason_code' => $reasonCode, 'moderator_id' => $moderatorId]
            );

            return $pub;
        });
    }

    public function fileAppeal(string $submissionId, string $appellantId, string $role, string $grounds): FeedbackAppeal
    {
        if (! in_array($role, ['devotee', 'provider'], true)) {
            throw new \InvalidArgumentException('Appellant role must be devotee or provider.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();

        return FeedbackAppeal::query()->create([
            'tenant_id' => $tenantId,
            'submission_id' => $submissionId,
            'appellant_id' => $appellantId,
            'appellant_role' => $role,
            'grounds' => $grounds,
            'status' => FeedbackAppeal::STATUS_PENDING,
        ]);
    }

    public function decideAppeal(string $appealId, string $deciderId, bool $overturn, string $notes): FeedbackAppeal
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $appeal = FeedbackAppeal::query()->where('tenant_id', $tenantId)->where('id', $appealId)->firstOrFail();

        $wasPriorModerator = FeedbackModerationAction::query()
            ->where('tenant_id', $tenantId)
            ->where('submission_id', $appeal->submission_id)
            ->where('actor_id', $deciderId)
            ->exists();

        if ($wasPriorModerator) {
            throw new \DomainException('independence_violation: Appeal decider must be distinct from the prior moderator.');
        }

        $appeal->forceFill([
            'status' => $overturn ? FeedbackAppeal::STATUS_OVERTURNED : FeedbackAppeal::STATUS_UPHELD,
            'decider_id' => $deciderId,
            'decision_notes' => $notes,
            'decided_at' => $now->toDateTimeString(),
        ])->save();

        if ($overturn) {
            FeedbackPublication::query()
                ->where('tenant_id', $tenantId)
                ->where('submission_id', $appeal->submission_id)
                ->update([
                    'state' => FeedbackPublication::STATE_PUBLISHED,
                    'reason_code' => 'APPEAL_OVERTURNED',
                    'published_at' => $now->toDateTimeString(),
                ]);
        }

        return $appeal;
    }
}

