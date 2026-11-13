<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Application;

use App\Domain\Trust\References\TrustReferenceGenerator;
use App\Domain\Trust\Subjects\TrustSubject;
use App\Domain\Trust\Subjects\TrustSubjectType;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Customers\Contracts\ResolvesCustomerParties;
use App\Modules\Feedback\Domain\Anomaly\ReviewIntegrityScanner;
use App\Modules\Feedback\Domain\Services\PiiRedactor;
use App\Modules\Feedback\Models\FeedbackAggregationState;
use App\Modules\Feedback\Models\FeedbackDimensionValue;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Feedback\Models\FeedbackModerationAction;
use App\Modules\Feedback\Models\FeedbackPublication;
use App\Modules\Feedback\Models\FeedbackSubmission;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Feedback submission write path enforcing:
 *  - Booking completion precondition (non-completed/NO_SHOW route to support_report, T-TS02-06)
 *  - Party entitlement (account holder or authorized family member, T-TS02-02)
 *  - Server-derived subject from PanditAssignment / Booking (never client pandit_id, TS-08 / T-TS02-07)
 *  - Separate submission / publication / aggregation records (TS-06)
 *  - Rule-first integrity scan -> HELD (never fraud labelling, TS-09)
 */
final class SubmitFeedbackUseCase
{
    public const EDIT_WINDOW_HOURS = 48;

    public function __construct(
        private readonly ReadsBookings $bookings,
        private readonly ManagesPanditAssignments $assignments,
        private readonly ResolvesCustomerParties $parties,
        private readonly ReviewIntegrityScanner $scanner,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * @param  array{
     *   booking_id: string,
     *   acting_user_id: int,
     *   overall_rating: int,
     *   comment?: string|null,
     *   share_publicly: bool,
     *   client_subject_id?: string|null,
     *   dimensions?: array<string, int>,
     * }  $input
     * @return array{submission: FeedbackSubmission, publication: FeedbackPublication, held_anomalies: int}
     */
    public function execute(array $input): array
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $bookingId = $input['booking_id'];
        $snapshot = $this->bookings->bookingSnapshotForTrust($bookingId);

        if ($snapshot === null) {
            throw new \RuntimeException('not_found: Booking not found.');
        }

        if (! $this->parties->isAuthorizedPartyForOwner((int) $input['acting_user_id'], (int) $snapshot['user_id'])) {
            throw new \RuntimeException('not_found: Booking not found for caller party.');
        }

        if (in_array($snapshot['status'], ['CANCELLED', 'NO_SHOW', 'FAILED', 'EXPIRED'], true)) {
            throw new \DomainException('redirect_support_report: Cancelled or NO_SHOW bookings cannot be rated; report what happened via Support.');
        }

        if ($snapshot['status'] !== 'COMPLETED') {
            throw new \DomainException('uncompleted_booking: Only completed bookings are eligible for feedback.');
        }

        $invitation = FeedbackInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($invitation !== null && $invitation->state === FeedbackInvitation::STATE_SUPPRESSED_BY_SAFETY) {
            throw new \DomainException('safety_suppressed: Feedback is paused while a safety review is active.');
        }

        if ($invitation !== null && CarbonImmutable::now('UTC')->gt($invitation->window_closes_at)) {
            throw new \DomainException('window_expired: Feedback submission window has closed.');
        }

        if (FeedbackSubmission::query()->where('tenant_id', $tenantId)->where('booking_id', $bookingId)->exists()) {
            throw new \OverflowException('conflict: Feedback already submitted for this booking.');
        }

        // Server-derived subject resolution (TS-08)
        $assignment = $this->assignments->assignmentForBooking($bookingId);
        $subject = $assignment !== null
            ? new TrustSubject(TrustSubjectType::PANDIT, $assignment['pandit_id'], 'server_assignment')
            : new TrustSubject(TrustSubjectType::SERVICE_INSTANCE, (string) ($snapshot['service_sku'] ?? $bookingId), 'booking_revision');

        if (isset($input['client_subject_id']) && $input['client_subject_id'] !== '' && $input['client_subject_id'] !== $subject->id) {
            throw new \InvalidArgumentException('subject_mismatch: Client-supplied subject does not match server-derived booking assignment.');
        }

        $now = CarbonImmutable::now('UTC');
        $reviewRef = TrustReferenceGenerator::mint(
            TrustReferenceGenerator::KIND_REVIEW,
            $tenantId,
            (new FeedbackSubmission())->getConnectionName(),
            'feedback_submissions',
            'review_reference',
        );

        return (new FeedbackSubmission())->getConnection()->transaction(function () use (
            $tenantId,
            $bookingId,
            $reviewRef,
            $input,
            $subject,
            $snapshot,
            $assignment,
            $invitation,
            $now
        ): array {
            $submission = new FeedbackSubmission();
            $submission->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'review_reference' => $reviewRef,
                'reviewer_user_id' => (int) $input['acting_user_id'],
                'subject_type' => $subject->type->value,
                'subject_id' => $subject->id,
                'service_instance_id' => (string) ($snapshot['service_sku'] ?? $bookingId),
                'booking_revision_id' => $snapshot['current_revision_id'],
                'service_category' => (string) ($snapshot['service_sku'] ?? 'default'),
                'overall_rating' => (int) $input['overall_rating'],
                'original_text' => $input['comment'] ?? null,
                'share_publicly' => (bool) $input['share_publicly'],
                'edit_history' => [],
                'submitted_at' => $now->toDateTimeString(),
                'edit_window_closes_at' => $now->addHours(self::EDIT_WINDOW_HOURS)->toDateTimeString(),
            ]);
            $submission->save();

            $submissionId = (string) $submission->getKey();

            // Record non-expert sub-dimensions
            foreach ($input['dimensions'] ?? [] as $code => $val) {
                FeedbackDimensionValue::query()->create([
                    'tenant_id' => $tenantId,
                    'submission_id' => $submissionId,
                    'dimension_code' => (string) $code,
                    'value' => (int) $val,
                    'dimension_version' => 'v1',
                ]);
            }

            // Integrity rule-first scan
            $anomalies = $this->scanner->scan($tenantId, $submission, $assignment['pandit_user_id'] ?? null);
            $hasAnomalies = $anomalies !== [];

            // Separate Publication record with auto PII redaction if needed
            $pii = PiiRedactor::redact($submission->original_text);
            $pubState = match (true) {
                $hasAnomalies => FeedbackPublication::STATE_HELD,
                ! $submission->share_publicly => FeedbackPublication::STATE_WITHHELD,
                default => FeedbackPublication::STATE_PUBLISHED,
            };

            $reasonCode = match (true) {
                $hasAnomalies => 'INTEGRITY_HOLD_PENDING_REVIEW',
                ! $submission->share_publicly => 'PRIVATE_FEEDBACK_ONLY',
                $pii['changed'] => 'AUTO_PII_REDACTED',
                default => 'AUTO_ELIGIBLE_PUBLISHED',
            };

            $publication = new FeedbackPublication();
            $publication->forceFill([
                'tenant_id' => $tenantId,
                'submission_id' => $submissionId,
                'state' => $pubState,
                'redacted_text' => $pii['changed'] ? $pii['redacted'] : null,
                'reason_code' => $reasonCode,
                'published_at' => $pubState === FeedbackPublication::STATE_PUBLISHED ? $now->toDateTimeString() : null,
            ]);
            $publication->save();

            // Separate Aggregation State (counts_toward excludes technical chanting conformity)
            FeedbackAggregationState::query()->create([
                'tenant_id' => $tenantId,
                'submission_id' => $submissionId,
                'counts_toward' => $hasAnomalies ? [] : ['overall', 'punctuality_perceived', 'conduct', 'chanting_audibility_clarity'],
                'excluded_by_cause' => false,
            ]);

            if ($invitation !== null) {
                $invitation->forceFill(['state' => FeedbackInvitation::STATE_SUBMITTED])->save();
            }

            FeedbackModerationAction::query()->create([
                'tenant_id' => $tenantId,
                'submission_id' => $submissionId,
                'action' => $hasAnomalies ? 'HOLD' : ($pubState === FeedbackPublication::STATE_PUBLISHED ? 'PUBLISH' : 'WITHHOLD'),
                'actor_id' => 'system_intake_policy',
                'reason_code' => $reasonCode,
                'notes' => $hasAnomalies ? 'Held by rule-based integrity scanner (appeal available).' : null,
                'policy_version' => 'v1',
                'appealable' => true,
                'occurred_at' => $now->toDateTimeString(),
            ]);

            $this->audit->record(
                'feedback.submission.recorded',
                ['module' => 'feedback', 'resource_type' => 'feedback_submission', 'resource_id' => $submissionId],
                null,
                [
                    'review_reference' => $reviewRef,
                    'subject_type' => $subject->type->value,
                    'subject_id' => $subject->id,
                    'publication_state' => $pubState,
                    'anomalies' => count($anomalies),
                ]
            );

            $this->outbox->append(
                $tenantId,
                'feedback',
                $submissionId,
                $hasAnomalies ? 'feedback.submission.held' : 'feedback.submission.recorded',
                [
                    'submission_id' => $submissionId,
                    'review_reference' => $reviewRef,
                    'subject_type' => $subject->type->value,
                    'subject_id' => $subject->id,
                    'publication_state' => $pubState,
                ]
            );

            return [
                'submission' => $submission,
                'publication' => $publication,
                'held_anomalies' => count($anomalies),
            ];
        });
    }
}

