<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Policies\QuietHoursClampingService;
use App\Domain\Delivery\Scheduling\BookingReminderScheduler;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM09-05:
 * Booking reminder scheduling bound to revisions, quiet-hours daylight clamping,
 * and automatic cancellation on reschedule/cancel.
 */
final class BookingReminderSchedulerTest extends TenantIntegrationTestCase
{
    public function test_schedule_reminders_creates_24h_and_3h_intents(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $scheduler = new BookingReminderScheduler(new QuietHoursClampingService());
        // Ritual in 30 hours from now at 10:00 AM
        $ritualStartsAt = CarbonImmutable::now()->addHours(30);

        $reminders = $scheduler->scheduleReminders(
            tenantId: $tenantId,
            bookingId: 'book_remind_01',
            userId: 333,
            ritualStartsAt: $ritualStartsAt,
            revision: 1
        );

        $this->assertCount(2, $reminders);
        $this->assertSame(BookingReminderScheduler::EVENT_REMINDER_24H, $reminders[0]->event_type);
        $this->assertSame(BookingReminderScheduler::EVENT_REMINDER_3H, $reminders[1]->event_type);
        $this->assertNotNull($reminders[0]->next_attempt_at);
        $this->assertNotNull($reminders[1]->next_attempt_at);
    }

    public function test_rescheduling_cancels_previous_reminders_and_schedules_new_revision(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $scheduler = new BookingReminderScheduler(new QuietHoursClampingService());
        $initialStart = CarbonImmutable::now()->addHours(40);

        // Schedule revision 1
        $scheduler->scheduleReminders(
            tenantId: $tenantId,
            bookingId: 'book_remind_02',
            userId: 333,
            ritualStartsAt: $initialStart,
            revision: 1
        );

        // Reschedule to revision 2
        $newStart = CarbonImmutable::now()->addHours(50);
        $rev2Reminders = $scheduler->scheduleReminders(
            tenantId: $tenantId,
            bookingId: 'book_remind_02',
            userId: 333,
            ritualStartsAt: $newStart,
            revision: 2
        );

        // Old reminders must be marked 'cancelled'
        $cancelledCount = NotificationOutbox::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_id', 'book_remind_02')
            ->where('status', 'cancelled')
            ->count();

        $this->assertSame(2, $cancelledCount);

        // New reminders must be pending
        $this->assertCount(2, $rev2Reminders);
        $this->assertSame(2, $rev2Reminders[0]->payload_json['revision']);
    }

    public function test_cancelling_booking_cancels_pending_reminders(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $scheduler = new BookingReminderScheduler(new QuietHoursClampingService());
        $scheduler->scheduleReminders(
            tenantId: $tenantId,
            bookingId: 'book_remind_03',
            userId: 333,
            ritualStartsAt: CarbonImmutable::now()->addHours(35),
            revision: 1
        );

        $cancelled = $scheduler->cancelPendingReminders($tenantId, 'book_remind_03');
        $this->assertSame(2, $cancelled);

        $pendingRemaining = NotificationOutbox::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_id', 'book_remind_03')
            ->where('status', NotificationOutbox::STATUS_PENDING)
            ->count();

        $this->assertSame(0, $pendingRemaining);
    }
}

