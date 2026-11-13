<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Domain\Exceptions\AccountingPeriodLockedException;
use App\Modules\Finance\Domain\Exceptions\JournalReversalException;
use App\Modules\Finance\Domain\Exceptions\UnbalancedJournalException;
use App\Modules\Finance\Models\AccountingPeriodLock;
use App\Modules\Finance\Models\LedgerEntry;
use App\Modules\Finance\Models\LedgerJournal;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DoubleEntryLedgerService
{
    public function __construct(
        private readonly GaplessSequenceService $sequenceService,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * Posts a balanced double-entry subledger journal.
     *
     * @param list<array{
     *     account_code: string,
     *     entry_direction: string, // DEBIT | CREDIT
     *     amount_minor: int,
     *     currency: string
     * }> $entries
     */
    public function postJournal(
        string $tenantId,
        string $referenceType,
        string $referenceId,
        string $description,
        array $entries,
        CarbonImmutable $postedAt,
        ?string $reversesJournalId = null,
    ): LedgerJournal {
        $period = $postedAt->setTimezone('UTC')->format('Y-m');
        $this->assertPeriodOpen($tenantId, $period);

        // Enforce non-empty entries
        if ($entries === []) {
            throw new UnbalancedJournalException('Journal must contain at least two balanced entries.');
        }

        // Enforce strict balanced debits == credits invariant
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $entry) {
            $amount = (int) $entry['amount_minor'];
            if ($amount <= 0) {
                throw new UnbalancedJournalException('Entry amount must be positive.');
            }

            $direction = strtoupper(trim((string) $entry['entry_direction']));
            if ($direction === LedgerEntry::DIRECTION_DEBIT) {
                $totalDebit += $amount;
            } elseif ($direction === LedgerEntry::DIRECTION_CREDIT) {
                $totalCredit += $amount;
            } else {
                throw new UnbalancedJournalException("Invalid entry direction: {$direction}");
            }
        }

        if ($totalDebit !== $totalCredit) {
            throw new UnbalancedJournalException("Unbalanced journal: Debits ({$totalDebit}) != Credits ({$totalCredit})");
        }

        // Mint gapless sequential journal number
        $journalNumber = $this->sequenceService->nextNumber($tenantId, 'JRN', (int) $postedAt->format('Y'));

        $journal = new LedgerJournal();
        $journal->forceFill([
            'tenant_id' => $tenantId,
            'journal_number' => $journalNumber,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'posted_at' => $postedAt,
            'accounting_period' => $period,
            'status' => LedgerJournal::STATUS_POSTED,
            'reverses_journal_id' => $reversesJournalId,
        ]);
        $journal->save();

        foreach ($entries as $entry) {
            $entryRow = new LedgerEntry();
            $entryRow->forceFill([
                'tenant_id' => $tenantId,
                'journal_id' => (string) $journal->getKey(),
                'account_code' => (string) $entry['account_code'],
                'entry_direction' => strtoupper(trim((string) $entry['entry_direction'])),
                'amount_minor' => (int) $entry['amount_minor'],
                'currency' => (string) $entry['currency'],
            ]);
            $entryRow->save();
        }

        $this->outbox->append(
            $tenantId,
            'ledger_journal',
            (string) $journal->getKey(),
            'finance.journal.posted',
            [
                'journal_id' => (string) $journal->getKey(),
                'journal_number' => $journalNumber,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'total_amount_minor' => $totalDebit,
            ]
        );

        $this->audit->record(
            'finance.journal.posted',
            ['module' => 'finance', 'resource_type' => 'ledger_journal', 'resource_id' => (string) $journal->getKey()],
            null,
            ['journal_number' => $journalNumber, 'total_amount_minor' => $totalDebit]
        );

        return $journal;
    }

    /**
     * Posts a balanced contra reversal journal (inverting DEBIT <-> CREDIT) for an
     * immutable posted journal, recording the reversal link in the open `$reversedAt` period.
     */
    public function reverseJournal(
        string $tenantId,
        string $journalId,
        string $reason,
        CarbonImmutable $reversedAt,
    ): LedgerJournal {
        $connection = (new LedgerJournal())->getConnectionName() ?: 'tenant_shared';

        return DB::connection($connection)->transaction(function () use ($tenantId, $journalId, $reason, $reversedAt): LedgerJournal {
            /** @var LedgerJournal|null $original */
            $original = LedgerJournal::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $journalId)
                ->lockForUpdate()
                ->first();

            if ($original === null) {
                throw new JournalReversalException('Ledger journal not found.');
            }

            if ($original->status !== LedgerJournal::STATUS_POSTED) {
                throw new JournalReversalException("Only POSTED journals can be reversed (current state: {$original->status}).");
            }

            if ($original->reversed_by_journal_id !== null) {
                throw new JournalReversalException('Ledger journal has already been reversed.');
            }

            $entries = LedgerEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('journal_id', (string) $original->getKey())
                ->get();

            $contraEntries = $entries->map(static fn (LedgerEntry $entry): array => [
                'account_code' => $entry->account_code,
                'entry_direction' => $entry->entry_direction === LedgerEntry::DIRECTION_DEBIT
                    ? LedgerEntry::DIRECTION_CREDIT
                    : LedgerEntry::DIRECTION_DEBIT,
                'amount_minor' => (int) $entry->amount_minor,
                'currency' => $entry->currency,
            ])->all();

            $contra = $this->postJournal(
                tenantId: $tenantId,
                referenceType: 'REVERSAL',
                referenceId: (string) $original->getKey(),
                description: sprintf('Reversal of %s: %s', $original->journal_number, $reason),
                entries: $contraEntries,
                postedAt: $reversedAt,
                reversesJournalId: (string) $original->getKey(),
            );

            $original->status = LedgerJournal::STATUS_REVERSED;
            $original->reversed_by_journal_id = (string) $contra->getKey();
            $original->reversed_at = $reversedAt;
            $original->save();

            $this->outbox->append(
                $tenantId,
                'ledger_journal',
                (string) $original->getKey(),
                'finance.journal.reversed',
                [
                    'original_journal_id' => (string) $original->getKey(),
                    'contra_journal_id' => (string) $contra->getKey(),
                    'reason' => $reason,
                ],
            );

            $this->audit->record(
                'finance.journal.reversed',
                ['module' => 'finance', 'resource_type' => 'ledger_journal', 'resource_id' => (string) $original->getKey()],
                ['status' => LedgerJournal::STATUS_POSTED],
                ['status' => LedgerJournal::STATUS_REVERSED, 'contra_journal_id' => (string) $contra->getKey()],
            );

            return $contra;
        });
    }

    public function lockPeriod(
        string $tenantId,
        string $accountingPeriod,
        string $lockedBy,
        ?string $reason = null,
    ): AccountingPeriodLock {
        $lock = AccountingPeriodLock::query()->firstOrNew([
            'tenant_id' => $tenantId,
            'accounting_period' => $accountingPeriod,
        ]);

        $lock->forceFill([
            'status' => AccountingPeriodLock::STATUS_LOCKED,
            'locked_by' => $lockedBy,
            'locked_at' => CarbonImmutable::now('UTC'),
            'lock_reason' => $reason,
        ]);
        $lock->save();

        return $lock;
    }

    private function assertPeriodOpen(string $tenantId, string $accountingPeriod): void
    {
        $locked = AccountingPeriodLock::query()
            ->where('tenant_id', $tenantId)
            ->where('accounting_period', $accountingPeriod)
            ->where('status', AccountingPeriodLock::STATUS_LOCKED)
            ->exists();

        if ($locked) {
            throw new AccountingPeriodLockedException(
                "Accounting period [{$accountingPeriod}] is locked; post adjustments or reversals into an open period.",
            );
        }
    }
}
