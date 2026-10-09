<?php

namespace Tests\Platform\Outbox;

use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * Transactional outbox (F2): same-transaction writes (rollback discards
 * the fact with the state change), exactly-once dispatch under the
 * claim, failure recording with retry, the retry cap, and the
 * unhandled-event posture consumers inherit when they land later.
 *
 * Assertions read withoutGlobalScopes: the drain resolves each tenant's
 * context itself, tests make no ambient assumption (TEN-009).
 */
final class OutboxTest extends TenantIntegrationTestCase
{
    public function test_append_commits_and_rolls_back_with_the_producer_transaction(): void
    {
        $tenant = $this->makeTenant();
        $outbox = new Outbox();

        try {
            DB::connection('tenant_shared')->transaction(function () use ($outbox, $tenant): void {
                $outbox->append((string) $tenant->getKey(), 'booking', 'bkg_1', 'booking.created', ['status' => 'DRAFT']);
                throw new \RuntimeException('producer rolled back');
            });
        } catch (\RuntimeException) {
            // expected — the state change failed, so the fact must go too
        }

        $this->assertSame(
            0,
            OutboxEvent::withoutGlobalScopes()->count(),
            'rollback must discard the outbox row — it was written in the same transaction',
        );

        DB::connection('tenant_shared')->transaction(function () use ($outbox, $tenant): void {
            $event = $outbox->append((string) $tenant->getKey(), 'booking', 'bkg_1', 'booking.created', ['status' => 'DRAFT']);
            $this->assertSame((string) $tenant->getKey(), $event->tenant_id, 'explicit tenant_id must be respected');
        });

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $this->assertSame('booking.created', $row->event_type);
        $this->assertSame(['status' => 'DRAFT'], $row->payload);
        $this->assertNull($row->dispatched_at);
    }

    public function test_drain_dispatches_each_event_exactly_once_across_tenants(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $outbox = new Outbox();

        /** @var OutboxHandlers $handlers */
        $handlers = app(OutboxHandlers::class);
        $seen = [];
        $handlers->register('booking.created', function (OutboxEvent $event) use (&$seen): void {
            $seen[] = (string) $event->id;
        });

        $outbox->append((string) $tenantA->getKey(), 'booking', 'a1', 'booking.created', []);
        $outbox->append((string) $tenantA->getKey(), 'booking', 'a2', 'booking.created', []);
        $outbox->append((string) $tenantB->getKey(), 'booking', 'b1', 'booking.created', []);

        $this->assertSame(0, Artisan::call('outbox:drain'));
        $this->assertCount(3, $seen, 'every pending event dispatches exactly once');
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->whereNull('dispatched_at')->count());

        $this->assertSame(0, Artisan::call('outbox:drain'));
        $this->assertCount(3, $seen, 'a second drain must not re-dispatch already-dispatched events');
    }

    public function test_failed_handler_records_the_error_and_retries_next_pass(): void
    {
        $tenant = $this->makeTenant();
        $outbox = new Outbox();
        $outbox->append((string) $tenant->getKey(), 'booking', 'bkg_f', 'booking.created', []);

        /** @var OutboxHandlers $handlers */
        $handlers = app(OutboxHandlers::class);
        $shouldFail = true;
        $calls = 0;
        $handlers->register('booking.created', function () use (&$shouldFail, &$calls): void {
            $calls++;
            if ($shouldFail) {
                throw new \RuntimeException('handler exploded');
            }
        });

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $this->assertNull($row->dispatched_at, 'failed claim must roll back — event stays pending');
        $this->assertSame(1, $row->attempts);
        $this->assertNotNull($row->last_error);
        $this->assertStringContainsString('handler exploded', (string) $row->last_error);

        $shouldFail = false;
        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row->refresh();
        $this->assertNotNull($row->dispatched_at, 'retry must dispatch once the handler recovers');
        $this->assertSame(2, $row->attempts);
        $this->assertNull($row->last_error, 'success clears the recorded failure');
        $this->assertSame(2, $calls);
    }

    public function test_exhausted_events_are_left_for_ops_not_reclaimed(): void
    {
        $tenant = $this->makeTenant();
        $outbox = new Outbox();
        $outbox->append((string) $tenant->getKey(), 'booking', 'bkg_x', 'booking.created', []);

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $row->forceFill(['attempts' => OutboxEvent::MAX_ATTEMPTS, 'last_error' => 'cap reached'])->save();

        /** @var OutboxHandlers $handlers */
        $handlers = app(OutboxHandlers::class);
        $calls = 0;
        $handlers->register('booking.created', function () use (&$calls): void {
            $calls++;
        });

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row->refresh();
        $this->assertSame(0, $calls, 'a row at the retry cap is never re-claimed');
        $this->assertNull($row->dispatched_at);
        $this->assertSame(OutboxEvent::MAX_ATTEMPTS, $row->attempts);
    }

    public function test_unhandled_event_types_stay_pending_without_burning_attempts(): void
    {
        $tenant = $this->makeTenant();
        $outbox = new Outbox();
        $outbox->append((string) $tenant->getKey(), 'booking', 'bkg_u', 'booking.without.consumer', []);

        /** @var OutboxHandlers $handlers */
        $handlers = app(OutboxHandlers::class);
        // A consumer for a DIFFERENT type exists, so the drain runs; the
        // unregistered type must sit untouched for its future consumer.
        $handlers->register('something.else', function (): void {
            $this->fail('unrelated handler must not receive foreign events');
        });

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $this->assertNull($row->dispatched_at);
        $this->assertSame(0, $row->attempts, 'unhandled types burn no attempts');
        $this->assertNull($row->last_error);
    }

    public function test_drain_without_registered_handlers_is_a_no_op(): void
    {
        $tenant = $this->makeTenant();
        (new Outbox())->append((string) $tenant->getKey(), 'booking', 'bkg_n', 'booking.created', []);

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $this->assertNull($row->dispatched_at);
        $this->assertSame(0, $row->attempts);
    }
}
