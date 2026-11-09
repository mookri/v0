<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Domain\Exceptions\UnbalancedJournalException;
use App\Modules\Finance\Models\LedgerEntry;
use App\Modules\Finance\Models\LedgerJournal;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;

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
        CarbonImmutable $postedAt
    ): LedgerJournal {
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
            'status' => LedgerJournal::STATUS_POSTED,
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
}
