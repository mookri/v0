<?php

namespace App\Domain\Delivery\Scheduling;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Policies\QuietHoursClampingService;
use App\Domain\Delivery\Support\DedupKeyGenerator;
use Carbon\CarbonImmutable;

final class BookingReminderScheduler
{
    public const EVENT_REMINDER_24H = 'booking.reminder.24h';
    public const EVENT_REMINDER_3H = 'booking.reminder.3h';

    public function __construct(
        private readonly ?QuietHoursClampingService $quietHoursService = null
    ) {
    }

    /**
     * Schedule 24h and 3h reminder intents for a confirmed booking.
     *
     * @return list<NotificationOutbox>
     */
    public function scheduleReminders(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        CarbonImmutable $ritualStartsAt,
        int $revision = 1,
        string $userTimezone = 'Asia/Kolkata'
    ): array {
        // Cancel any pending reminders from previous revisions
        $this->cancelPendingReminders($tenantId, $bookingId);

        $now = CarbonImmutable::now($userTimezone);
        $reminders = [];

        // 1. Schedule 24-hour reminder
        $time24h = $ritualStartsAt->subHours(24);
        if ($time24h->isAfter($now)) {
            $effectiveTime24h = $this->quietHoursService?->clampDispatchTime($time24h, $userTimezone) ?? $time24h;
            $reminders[] = $this->createReminderOutbox(
                tenantId: $tenantId,
                bookingId: $bookingId,
                userId: $userId,
                eventType: self::EVENT_REMINDER_24H,
                scheduledFor: $effectiveTime24h,
                revision: $revision,
                reminderWindow: '24h'
            );
        }

        // 2. Schedule 3-hour reminder
        $time3h = $ritualStartsAt->subHours(3);
        if ($time3h->isAfter($now)) {
            $effectiveTime3h = $this->quietHoursService?->clampDispatchTime($time3h, $userTimezone) ?? $time3h;
            $reminders[] = $this->createReminderOutbox(
                tenantId: $tenantId,
                bookingId: $bookingId,
                userId: $userId,
                eventType: self::EVENT_REMINDER_3H,
                scheduledFor: $effectiveTime3h,
                revision: $revision,
                reminderWindow: '3h'
            );
        }

        return $reminders;
    }

    /**
     * Invalidate and cancel all pending reminders for a booking (e.g. on reschedule or cancellation).
     *
     * @return int Number of cancelled reminders
     */
    public function cancelPendingReminders(string $tenantId, string $bookingId): int
    {
        return NotificationOutbox::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_type', 'booking_reminder')
            ->where('aggregate_id', $bookingId)
            ->where('status', NotificationOutbox::STATUS_PENDING)
            ->update([
                'status' => 'cancelled',
            ]);
    }

    private function createReminderOutbox(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        string $eventType,
        CarbonImmutable $scheduledFor,
        int $revision,
        string $reminderWindow
    ): NotificationOutbox {
        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: $eventType,
            recipient: (string) $userId,
            subjectEntity: "booking:{$bookingId}:{$reminderWindow}",
            revision: $revision
        );

        return NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => "evt_remind_{$bookingId}_{$reminderWindow}_{$revision}",
            'aggregate_type' => 'booking_reminder',
            'aggregate_id' => $bookingId,
            'event_type' => $eventType,
            'payload_json' => [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'revision' => $revision,
                'reminder_window' => $reminderWindow,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'template_kind' => 'ritual_reminder',
                'dedup_key' => $dedupKey,
            ],
            'status' => NotificationOutbox::STATUS_PENDING,
            'attempt_count' => 0,
            'next_attempt_at' => $scheduledFor,
        ]);
    }
}
