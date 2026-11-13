<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Models\NotificationIntent;
use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Planning\NotificationOutboxProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM03-01:
 * Proves that notification_outbox operates transactionally,
 * duplicate event_ids are rejected, and the processor transitions rows cleanly.
 */
final class TransactionalOutboxTest extends TenantIntegrationTestCase
{
    public function test_rolled_back_transaction_leaves_zero_outbox_rows(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        try {
            DB::connection('tenant_shared')->transaction(function () use ($tenantId): void {
                NotificationOutbox::query()->create([
                    'tenant_id' => $tenantId,
                    'event_id' => 'evt_rollback_test_001',
                    'aggregate_type' => 'booking',
                    'aggregate_id' => 'bkg_111',
                    'event_type' => 'booking.confirmed',
                    'payload_json' => ['recipient' => '+919876543210'],
                    'status' => 'pending',
                ]);

                // Simulate abortive business error
                throw new \RuntimeException('Business logic failure; rollback');
            });
        } catch (\RuntimeException) {
            // caught expected exception
        }

        $this->assertSame(0, NotificationOutbox::query()->where('event_id', 'evt_rollback_test_001')->count());
    }

    public function test_duplicate_event_id_is_rejected_by_unique_constraint(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => 'evt_dup_constraint_002',
            'aggregate_type' => 'booking',
            'aggregate_id' => 'bkg_222',
            'event_type' => 'booking.confirmed',
            'payload_json' => ['recipient' => '+919876543210'],
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);

        NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => 'evt_dup_constraint_002', // DUPLICATE
            'aggregate_type' => 'booking',
            'aggregate_id' => 'bkg_222',
            'event_type' => 'booking.confirmed',
            'payload_json' => ['recipient' => '+919876543210'],
            'status' => 'pending',
        ]);
    }

    public function test_outbox_processor_claims_and_dispatches_intents(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $outbox = NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => 'evt_process_test_003',
            'aggregate_type' => 'booking',
            'aggregate_id' => 'bkg_333',
            'event_type' => 'booking.confirmed',
            'payload_json' => [
                'recipient_ref' => '+919876543210',
                'category' => 'transactional',
                'priority' => 'normal',
            ],
            'status' => 'pending',
        ]);

        $processor = app(NotificationOutboxProcessor::class);
        $dispatched = $processor->process(10);

        $this->assertSame(1, $dispatched);

        $outbox->refresh();
        $this->assertSame(NotificationOutbox::STATUS_DISPATCHED, $outbox->status);

        // Verify intent was created
        $this->assertSame(1, NotificationIntent::query()->where('subject_id', 'bkg_333')->count());
    }
}

