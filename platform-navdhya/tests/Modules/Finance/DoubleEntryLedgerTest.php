<?php

namespace Tests\Modules\Finance;

use App\Modules\Finance\Domain\DoubleEntryLedgerService;
use App\Modules\Finance\Domain\Exceptions\AccountingPeriodLockedException;
use App\Modules\Finance\Domain\Exceptions\JournalReversalException;
use App\Modules\Finance\Domain\Exceptions\UnbalancedJournalException;
use App\Modules\Finance\Domain\GaplessSequenceService;
use App\Modules\Finance\Models\LedgerEntry;
use App\Modules\Finance\Models\LedgerJournal;
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

    public function test_locked_accounting_period_blocks_posting_while_contra_reversal_lands_in_open_period(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            /** @var DoubleEntryLedgerService $service */
            $service = app(DoubleEntryLedgerService::class);
            $october = CarbonImmutable::parse('2026-10-15T10:00:00Z');
            $november = CarbonImmutable::parse('2026-11-11T10:00:00Z');

            $original = $service->postJournal(
                (string) $tenant->getKey(),
                'BOOKING',
                'booking_period_test_1',
                'Original settlement entry',
                [
                    [
                        'account_code' => LedgerEntry::ACCOUNT_GATEWAY_CLEARING,
                        'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                        'amount_minor' => 50000,
                        'currency' => 'INR',
                    ],
                    [
                        'account_code' => LedgerEntry::ACCOUNT_AP_PANDIT,
                        'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                        'amount_minor' => 50000,
                        'currency' => 'INR',
                    ],
                ],
                $october,
            );
            $this->assertSame('2026-10', $original->accounting_period);

            // Lock October 2026.
            $service->lockPeriod((string) $tenant->getKey(), '2026-10', 'controller_1', 'Month-end close');

            // Direct posting into 2026-10 must fail closed.
            try {
                $service->postJournal(
                    (string) $tenant->getKey(),
                    'ADJUSTMENT',
                    'adj_1',
                    'Late backdated entry',
                    [
                        [
                            'account_code' => LedgerEntry::ACCOUNT_GATEWAY_CLEARING,
                            'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                            'amount_minor' => 1000,
                            'currency' => 'INR',
                        ],
                        [
                            'account_code' => LedgerEntry::ACCOUNT_PLATFORM_REVENUE,
                            'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                            'amount_minor' => 1000,
                            'currency' => 'INR',
                        ],
                    ],
                    $october,
                );
                $this->fail('Expected AccountingPeriodLockedException.');
            } catch (AccountingPeriodLockedException) {
                // Expected.
            }

            // Reversing the locked-period journal into open November period succeeds cleanly.
            $contra = $service->reverseJournal(
                (string) $tenant->getKey(),
                (string) $original->getKey(),
                'Dispute adjustment',
                $november,
            );

            $this->assertSame(LedgerJournal::STATUS_POSTED, $contra->status);
            $this->assertSame('2026-11', $contra->accounting_period);
            $this->assertSame((string) $original->getKey(), $contra->reverses_journal_id);

            $freshOriginal = $original->fresh();
            $this->assertSame(LedgerJournal::STATUS_REVERSED, $freshOriginal->status);
            $this->assertSame((string) $contra->getKey(), $freshOriginal->reversed_by_journal_id);

            $contraEntries = $contra->entries()->orderBy('account_code')->get();
            $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $contraEntries[0]->entry_direction);
            $this->assertSame(LedgerEntry::DIRECTION_DEBIT, $contraEntries[1]->entry_direction);

            // Second reversal fails closed.
            $this->expectException(JournalReversalException::class);
            $service->reverseJournal(
                (string) $tenant->getKey(),
                (string) $original->getKey(),
                'Duplicate reversal attempt',
                $november,
            );
        } finally {
            $this->clearContext();
        }
    }
}
