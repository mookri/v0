<?php

namespace Tests\Platform\Outbox;

use App\Platform\Outbox\DeadLetterStore;
use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxDeadLetter;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;
use Tests\TenantIntegrationTestCase;

/**
 * Validates Outbox DLQ quarantine, per-consumer cursors, and replay capability (T-JYAUT-00e / D-16).
 */
final class DeadLetterAndPerConsumerCursorTest extends TenantIntegrationTestCase
{
    public function test_poisoned_consumer_quarantines_to_dlq_while_healthy_consumer_advances(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $handlers = app(OutboxHandlers::class);
        $healthyRan = 0;

        // Register healthy consumer
        $handlers->register('test.dlq.event', 'healthy_consumer', function (OutboxEvent $event) use (&$healthyRan): void {
            $healthyRan++;
        });

        // Register poison consumer that fails
        $handlers->register('test.dlq.event', 'poison_consumer', function (OutboxEvent $event): void {
            throw new \RuntimeException('Simulated poison consumer failure');
        });

        $outbox = app(Outbox::class);
        $event = $outbox->append($tenantId, 'Order', '101', 'test.dlq.event', ['test' => true]);

        $this->clearContext();

        // Drain 5 times until MAX_ATTEMPTS reached
        for ($i = 1; $i <= OutboxEvent::MAX_ATTEMPTS; $i++) {
            $this->artisan('outbox:drain', ['--tenant' => $tenantId])->assertSuccessful();
        }

        $this->resolveContext($tenant);

        // Healthy consumer must run only ONCE due to per-consumer cursor (_completed_consumers)
        $this->assertSame(1, $healthyRan, 'Healthy consumer should have completed exactly once and not re-run 5 times.');

        // Dead letter queue must contain the quarantined event
        $deadLetter = OutboxDeadLetter::query()
            ->where('tenant_id', $tenantId)
            ->where('original_event_id', (string) $event->getKey())
            ->first();

        $this->assertNotNull($deadLetter, 'Exhausted event must be quarantined in outbox_dead_letters');
        $this->assertStringContainsString('Simulated poison consumer failure', (string) $deadLetter->last_error);

        // Replay the dead letter
        $this->clearContext();
        $this->artisan('outbox:replay', [
            '--id' => (string) $deadLetter->getKey(),
            '--tenant' => $tenantId,
        ])->assertSuccessful();

        $this->resolveContext($tenant);
        $deadLetter->refresh();
        $this->assertNotNull($deadLetter->replayed_at);

        $event->refresh();
        $this->assertSame(0, $event->attempts, 'Replaying dead letter should reset original event attempts to 0');
    }
}

