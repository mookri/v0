<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Application;

use App\Domain\Trust\Subjects\TrustSubjectType;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Feedback\Models\FeedbackInvitation;
use App\Modules\Pandit\Contracts\ManagesPanditAssignments;
use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Feedback invitation producer from booking completion (TS-07 / T-TS02-05).
 */
final class IssueFeedbackInvitationUseCase
{
    public const DEFAULT_WINDOW_DAYS = 14;

    public const EVENT_TYPES = [
        'booking.reservation.transitioned',
        'support.safety_case.escalated',
    ];

    public function __construct(
        private readonly ReadsBookings $bookings,
        private readonly ManagesPanditAssignments $assignments,
        private readonly Outbox $outbox,
    ) {
    }

    public function handleOutbox(OutboxEvent $event): void
    {
        $payload = $event->payload;
        if ($event->event_type === 'support.safety_case.escalated') {
            $bookingId = (string) ($payload['booking_id'] ?? '');
            if ($bookingId !== '') {
                $this->suppressForSafety($bookingId);
            }

            return;
        }

        $to = (string) ($payload['to'] ?? '');
        if ($to !== 'COMPLETED') {
            return;
        }

        $bookingId = (string) ($payload['booking_id'] ?? $event->aggregate_id);
        if ($bookingId !== '') {
            $this->issueForBooking($bookingId);
        }
    }

    public function issueForBooking(string $bookingId, int $windowDays = self::DEFAULT_WINDOW_DAYS): ?FeedbackInvitation
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $existing = FeedbackInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $snapshot = $this->bookings->bookingSnapshotForTrust($bookingId);
        if ($snapshot === null || $snapshot['status'] !== 'COMPLETED') {
            return null;
        }

        $assignment = $this->assignments->assignmentForBooking($bookingId);
        $subjectType = $assignment !== null ? TrustSubjectType::PANDIT->value : TrustSubjectType::SERVICE_INSTANCE->value;
        $subjectId = $assignment !== null ? $assignment['pandit_id'] : (string) ($snapshot['service_sku'] ?? $bookingId);

        $now = CarbonImmutable::now('UTC');
        $invitation = new FeedbackInvitation();
        $invitation->forceFill([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'invitee_user_id' => (int) $snapshot['user_id'],
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'window_opens_at' => $now->toDateTimeString(),
            'window_closes_at' => $now->addDays($windowDays)->toDateTimeString(),
            'state' => FeedbackInvitation::STATE_SENT,
            'channel' => 'email',
            'sent_at' => $now->toDateTimeString(),
        ]);
        $invitation->save();

        $this->outbox->append(
            $tenantId,
            'feedback',
            (string) $invitation->getKey(),
            'feedback.invitation.sent',
            [
                'invitation_id' => (string) $invitation->getKey(),
                'booking_id' => $bookingId,
                'invitee_user_id' => $snapshot['user_id'],
                'window_closes_at' => $now->addDays($windowDays)->toJSON(),
            ]
        );

        return $invitation;
    }

    /**
     * Suppress pending invitations when a safety incident opens (T-TS07-08).
     */
    public function suppressForSafety(string $bookingId): void
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();

        FeedbackInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->whereIn('state', [FeedbackInvitation::STATE_PENDING, FeedbackInvitation::STATE_SENT])
            ->update(['state' => FeedbackInvitation::STATE_SUPPRESSED_BY_SAFETY]);
    }
}

