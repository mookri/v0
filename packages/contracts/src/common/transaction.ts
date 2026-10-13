import type { OutboxStore } from '@navdhya/contracts/src/messaging/outbox';

export interface TransactionContext {
  readonly id: string;
  /**
   * Tx-bound outbox (plan B2): records appended through this handle commit
   * and roll back with the surrounding business write — P0 emission is
   * structurally transactional, not best-effort. Absent on legacy paths.
   */
  readonly outbox?: OutboxStore;
}

export interface TransactionManager {
  run<T>(operation: (tx: TransactionContext) => Promise<T>): Promise<T>;
}
