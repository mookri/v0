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
    public function test_duplicate_consumer_registration_fails_without_replacing_the_original_or_reordering_consumers(): void
    {
        $handlers = new OutboxHandlers();
        $event = new OutboxEvent(['event_type' => 'booking.created']);
        $seen = [];
        $handlers->register('booking.created', 'test.z-first', function (OutboxEvent $received) use (&$seen, $event): void {
            $this->assertSame($event, $received);
            $seen[] = 'first';
        });
        $handlers->register('booking.created', 'test.a-second', function (OutboxEvent $received) use (&$seen, $event): void {
            $this->assertSame($event, $received);
            $seen[] = 'second';
        });

        try {
            $handlers->register('booking.created', 'test.z-first', function (): void {
                $this->fail('duplicate consumer must not replace the original');
            });
            $this->fail('duplicate consumer registration must fail');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('booking.created', $exception->getMessage());
            $this->assertStringContainsString('test.z-first', $exception->getMessage());
        }

        $handlers->dispatch($event);
        $handlers->dispatch($event);
        $this->assertSame(['first', 'second', 'first', 'second'], $seen);
    }

    public function test_registry_has_and_types_describe_events_not_consumers(): void
    {
        $handlers = new OutboxHandlers();
        $this->assertSame([], $handlers->types());
        $this->assertFalse($handlers->has('booking.created'));
        $seen = [];
        $record = function (OutboxEvent $event) use (&$seen): void {
            $seen[] = $event->event_type;
        };

        $handlers->register('booking.created', 'test.shared-consumer', $record);
        $handlers->register('booking.cancelled', 'test.shared-consumer', $record);
        $handlers->register('booking.created', 'test.other-consumer', $record);

        $this->assertSame(['booking.created', 'booking.cancelled'], $handlers->types());
        $this->assertTrue($handlers->has('booking.created'));
        $this->assertTrue($handlers->has('booking.cancelled'));
        $this->assertFalse($handlers->has('test.shared-consumer'));
        $this->assertFalse($handlers->has('booking.unknown'));
        $handlers->dispatch(new OutboxEvent(['event_type' => 'booking.cancelled']));
        $this->assertSame(['booking.cancelled'], $seen, 'consumer IDs are scoped to their event type');
    }

    public function test_direct_dispatch_of_an_unknown_event_fails(): void
    {
        $handlers = new OutboxHandlers();
        $handlers->register('booking.created', 'test.unrelated', function (): void {
            $this->fail('unrelated consumer must not receive an unknown event');
        });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No outbox consumers registered for event type 'booking.unknown'.");
        $handlers->dispatch(new OutboxEvent(['event_type' => 'booking.unknown']));
    }

    public function test_consumer_failure_propagates_and_rolls_back_all_writes_in_the_enclosing_transaction(): void
    {
        $tenant = $this->makeTenant();
        $event = (new Outbox())->append((string) $tenant->getKey(), 'booking', 'bkg_rollback', 'booking.created', []);
        $handlers = new OutboxHandlers();
        $failure = new \RuntimeException('second consumer failed');
        $seen = [];
        $handlers->register('booking.created', 'test.first-write', function (OutboxEvent $event) use (&$seen): void {
            $event->forceFill(['payload' => ['first_consumer' => true]])->save();
            $seen[] = 'first';
        });
        $handlers->register('booking.created', 'test.failing-write', function (OutboxEvent $event) use (&$seen, $failure): void {
            $event->forceFill(['aggregate_id' => 'changed-by-second'])->save();
            $seen[] = 'second';
            throw $failure;
        });
        $handlers->register('booking.created', 'test.after-failure', function (): void {
            $this->fail('consumers after the failure must not run');
        });

        try {
            DB::connection('tenant_shared')->transaction(function () use ($handlers, $event): void {
                $handlers->dispatch($event);
                $event->forceFill(['dispatched_at' => now()])->save();
            });
            $this->fail('the consumer failure must escape dispatch');
        } catch (\RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $event->refresh();
        $this->assertSame(['first', 'second'], $seen);
        $this->assertSame([], $event->payload, 'the earlier consumer write must roll back');
        $this->assertSame('bkg_rollback', $event->aggregate_id, 'the failing consumer write must roll back');
        $this->assertNull($event->dispatched_at);
    }

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
        $observed = [];
        $handlers->register('booking.created', 'test.record-dispatch', function (OutboxEvent $event) use (&$seen): void {
            $seen[] = (string) $event->id;
        });
        $handlers->register('booking.created', 'test.observe-dispatch', function (OutboxEvent $event) use (&$seen, &$observed): void {
            $observed[] = (string) $event->id;
            $this->assertSame($seen, $observed, 'the first consumer must run before the second for each event');
        });

        $outbox->append((string) $tenantA->getKey(), 'booking', 'a1', 'booking.created', []);
        $outbox->append((string) $tenantA->getKey(), 'booking', 'a2', 'booking.created', []);
        $outbox->append((string) $tenantB->getKey(), 'booking', 'b1', 'booking.created', []);

        $this->assertSame(0, Artisan::call('outbox:drain'));
        $this->assertCount(3, $seen, 'every pending event dispatches exactly once');
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->whereNull('dispatched_at')->count());

        $this->assertSame(0, Artisan::call('outbox:drain'));
        $this->assertCount(3, $seen, 'a second drain must not re-dispatch already-dispatched events');
        $this->assertSame($seen, $observed, 'both consumers must receive every event exactly once');
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
        $handlers->register('booking.created', 'test.retry', function () use (&$shouldFail, &$calls): void {
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
        $handlers->register('booking.created', 'test.exhausted', function () use (&$calls): void {
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
        $handlers->register('something.else', 'test.unrelated', function (): void {
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
        $this->app->instance(OutboxHandlers::class, new OutboxHandlers());
        $tenant = $this->makeTenant();
        (new Outbox())->append((string) $tenant->getKey(), 'booking', 'bkg_n', 'booking.created', []);

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $row = OutboxEvent::withoutGlobalScopes()->first();
        $this->assertNotNull($row);
        $this->assertNull($row->dispatched_at);
        $this->assertSame(0, $row->attempts);
    }

    public function test_drain_fans_out_to_multiple_consumers_such_as_finance_and_notifications_without_overwriting_each_other(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $outbox = new Outbox();
        $outbox->append($tenantId, 'booking', 'bkg_fanout', 'booking.reservation.confirmed', ['amount' => 50000]);

        /** @var OutboxHandlers $handlers */
        $handlers = app(OutboxHandlers::class);
        $processed = [];

        $handlers->register('booking.reservation.confirmed', 'finance.subledger', function (OutboxEvent $event) use (&$processed): void {
            $processed['finance'] = (int) $event->payload['amount'];
        });

        $handlers->register('booking.reservation.confirmed', 'customer.notifications', function (OutboxEvent $event) use (&$processed): void {
            $processed['notifications'] = (int) $event->payload['amount'];
        });

        $this->assertSame(0, Artisan::call('outbox:drain'));

        $this->assertSame(50000, $processed['finance'] ?? null);
        $this->assertSame(50000, $processed['notifications'] ?? null);

        $row = OutboxEvent::withoutGlobalScopes()->where('aggregate_id', 'bkg_fanout')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->dispatched_at);
        $this->assertSame(1, $row->attempts);
    }
}
