<?php

namespace Tests\Modules\Customers;

use App\Modules\Customers\Domain\Panchang\AnchorOccurrenceResolver;
use App\Modules\Customers\Models\CustomerNotification;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Platform\Outbox\OutboxEvent;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class ReminderPipelineDaemonTest extends TenantIntegrationTestCase
{
    public function test_reminder_pipeline_daemon_recomputes_cursor_and_fires_idempotently(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 1004;

        // Resolve next Ekadashi using real Panchang engine
        $resolver = new AnchorOccurrenceResolver();
        $baseline = CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $nextEkadashi = $resolver->occurrenceForAnchor([
            'type' => 'CUSTOM_TITHI',
            'index_in_paksha' => 11,
        ], $baseline, 'Asia/Kolkata', 'PURNIMANTA');

        $occurrenceDate = CarbonImmutable::parse($nextEkadashi->date, 'Asia/Kolkata')->startOfDay();
        $daysUntil = (int) $baseline->diffInDays($occurrenceDate, false);

        // Create ritual reminder with advance slot matching current daysUntil
        $reminder = CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => CustomerRitualReminder::TYPE_CUSTOM_TITHI,
            'title' => 'Sacred Ekadashi Vrata',
            'anchor_rule' => [
                'type' => 'CUSTOM_TITHI',
                'index_in_paksha' => 11,
                'tradition' => 'PURNIMANTA',
            ],
            'advance_days' => [$daysUntil, 3, 1, 0],
            'next_occurrence_at' => null, // Stale/empty cursor
            'last_fired_period' => null,
            'is_active' => true,
        ]);

        $this->clearContext();

        // 1. Run the reminder daemon
        $this->artisan('customers:process-reminders', [
            '--tenant' => $tenantId,
        ])->assertSuccessful();

        // 2. Verify cursor recomputed & notification fired
        $this->resolveContext($tenant);
        $reminder->refresh();
        $this->assertNotNull($reminder->next_occurrence_at);
        $this->assertSame($nextEkadashi->date, $reminder->next_occurrence_at->toDateString());
        $this->assertNotNull($reminder->last_fired_period);

        // Verify CustomerNotification row was created in-app
        $notification = CustomerNotification::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (string) $userId)
            ->where('kind', 'reminder')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Sacred Ekadashi Vrata', $notification->title);

        // Verify outbox fact emitted
        $fact = OutboxEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('event_type', 'customers.reminder.fired')
            ->first();

        $this->assertNotNull($fact);
        $this->assertSame((string) $reminder->getKey(), $fact->payload['reminder_id']);

        $notificationsCountBefore = CustomerNotification::query()->count();
        $factsCountBefore = OutboxEvent::query()->where('event_type', 'customers.reminder.fired')->count();

        // 3. Re-run daemon immediately to assert Deduplication / Idempotency
        $this->clearContext();
        $this->artisan('customers:process-reminders', [
            '--tenant' => $tenantId,
        ])->assertSuccessful();

        // Notification and fact count must remain EXACTLY identical
        $this->resolveContext($tenant);
        $this->assertSame($notificationsCountBefore, CustomerNotification::query()->count());
        $this->assertSame($factsCountBefore, OutboxEvent::query()->where('event_type', 'customers.reminder.fired')->count());
    }

    public function test_out_of_corpus_horizon_skips_cleanly_without_aborting_batch(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();
        $userId = 1005;

        // Reminder with impossible far future date criteria
        CustomerRitualReminder::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'CUSTOM_TITHI',
            'title' => 'Far Horizon Reminder',
            'anchor_rule' => [
                'type' => 'CUSTOM_TITHI',
                'index_in_paksha' => 99, // Impossible index
            ],
            'advance_days' => [7],
            'is_active' => true,
        ]);

        $this->clearContext();

        // Should complete successfully with exit code 0 and log warning
        $this->artisan('customers:process-reminders', [
            '--tenant' => $tenantId,
        ])->assertSuccessful();
    }
}
