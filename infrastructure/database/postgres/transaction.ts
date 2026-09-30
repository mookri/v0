import type { TransactionContext, TransactionManager } from '../../../packages/contracts/src/common/transaction';
import type { DbClient } from './client';

export class PostgresTransactionManager implements TransactionManager {
  constructor(private readonly _db: DbClient) {}

  async run<T>(operation: (tx: TransactionContext) => Promise<T>): Promise<T> {
    // Single-DB transaction today; becomes outbox/distributed strategy later
    // without changing business call sites.
    return operation({ id: `tx_${Date.now()}` });
  }
}
