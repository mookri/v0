import { JournalEntry, JournalReversal } from '@navdhya/finance/src/domain/ledger';

export interface LedgerRepository {
  save(entry: JournalEntry): Promise<void>;
  findById(id: string): Promise<JournalEntry | null>;
  findBySource(sourceType: string, sourceId: string): Promise<JournalEntry[]>;
  saveReversal(reversal: JournalReversal): Promise<void>;
}

export interface FinanceGateway {
  getCustomerFinancialSummary(customerId: string): Promise<unknown>;
  getPartnerFinancialSummary(partnerId: string): Promise<unknown>;
}
