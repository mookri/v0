<?php

namespace Tests\Modules\Finance;

use App\Modules\Finance\Domain\DoubleEntryLedgerService;
use App\Modules\Finance\Domain\Exceptions\UnbalancedJournalException;
use App\Modules\Finance\Domain\GaplessSequenceService;
use App\Modules\Finance\Models\LedgerEntry;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class DoubleEntryLedgerTest extends TenantIntegrationTestCase
{
    public function test_balanced_journal_posts_with_gapless_number_and_records_entries(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var DoubleEntryLedgerService $service */
            $service = app(DoubleEntryLedgerService::class);
            $now = CarbonImmutable::now('UTC');

            $entries = [
                [
                    'account_code' => LedgerEntry::ACCOUNT_GATEWAY_CLEARING,
                    'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                    'amount_minor' => 100000, // ₹1,000.00
                    'currency' => 'INR',
                ],
                [
                    'account_code' => LedgerEntry::ACCOUNT_AP_PANDIT,
                    'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                    'amount_minor' => 80000,  // ₹800.00
                    'currency' => 'INR',
                ],
                [
                    'account_code' => LedgerEntry::ACCOUNT_PLATFORM_REVENUE,
                    'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                    'amount_minor' => 15000,  // ₹150.00
                    'currency' => 'INR',
                ],
                [
                    'account_code' => LedgerEntry::ACCOUNT_TAX_PAYABLE,
                    'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                    'amount_minor' => 5000,   // ₹50.00
                    'currency' => 'INR',
                ],
            ];

            $journal = $service->postJournal(
                (string) $tenant->getKey(),
                'BOOKING',
                'booking_test_ledger_1',
                'Customer booking payment and settlement split',
                $entries,
                $now
            );

            $this->assertStringStartsWith('JRN-', $journal->journal_number);
            $this->assertSame('POSTED', $journal->status);

            // Verify entries count
            $this->assertSame(4, $journal->entries()->count());

            // Verify Outbox Event
            $this->assertDatabaseHas('outbox_events', [
                'tenant_id' => (string) $tenant->getKey(),
                'event_type' => 'finance.journal.posted',
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }

    public function test_unbalanced_journal_throws_exception_and_writes_nothing(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var DoubleEntryLedgerService $service */
            $service = app(DoubleEntryLedgerService::class);
            $now = CarbonImmutable::now('UTC');

            $unbalancedEntries = [
                [
                    'account_code' => LedgerEntry::ACCOUNT_GATEWAY_CLEARING,
                    'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                    'amount_minor' => 100000, // ₹1,000.00
                    'currency' => 'INR',
                ],
                [
                    'account_code' => LedgerEntry::ACCOUNT_AP_PANDIT,
                    'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                    'amount_minor' => 90000,  // ₹900.00 (Unbalanced by ₹100.00!)
                    'currency' => 'INR',
                ],
            ];

            $this->expectException(UnbalancedJournalException::class);

            $service->postJournal(
                (string) $tenant->getKey(),
                'BOOKING',
                'booking_unbalanced_1',
                'Unbalanced split',
                $unbalancedEntries,
                $now
            );
        } finally {
            $this->clearContext();
        }
    }

    public function test_gapless_sequence_mints_monotonic_numbers(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var GaplessSequenceService $service */
            $service = app(GaplessSequenceService::class);
            $year = 2026;

            $num1 = $service->nextNumber((string) $tenant->getKey(), 'INV', $year);
            $num2 = $service->nextNumber((string) $tenant->getKey(), 'INV', $year);
            $num3 = $service->nextNumber((string) $tenant->getKey(), 'INV', $year);

            $this->assertSame('INV-2026-000001', $num1);
            $this->assertSame('INV-2026-000002', $num2);
            $this->assertSame('INV-2026-000003', $num3);
        } finally {
            $this->clearContext();
        }
    }
}

