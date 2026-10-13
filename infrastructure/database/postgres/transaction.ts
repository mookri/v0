import type { TransactionContext, TransactionManager } from '@navdhya/contracts/src/common/transaction';
import type { DbClient } from './client';
import { PostgresOutboxStore } from '@infra/messaging/outbox/PostgresOutboxStore';

// Real BEGIN/COMMIT/ROLLBACK on the underlying client (plan B2). The outbox
// handed to the operation is bound to the SAME client, so signal appends
// commit and roll back with the business write — a thrown operation leaves
// NO outbox record (acceptance: transactional guarantee).
//
// Driver caveat (unchanged from B0): with a pooled driver, BEGIN must run on
// a dedicated connection; createPostgresClient wires that when DATABASE_URL
// is provisioned. The statement sequence and tx-bound outbox seam are what
// this class commits to today and what the fake-client tests assert.
export class PostgresTransactionManager implements TransactionManager {
  private seq = 0;

  constructor(private readonly db: DbClient) {}

  async run<T>(operation: (tx: TransactionContext) => Promise<T>): Promise<T> {
    await this.db.query('BEGIN');
    const tx: TransactionContext = {
      id: `tx_${Date.now()}_${++this.seq}`,
      outbox: new PostgresOutboxStore(this.db),
    };
    try {
      const result = await operation(tx);
      await this.db.query('COMMIT');
      return result;
    } catch (err) {
      await this.db.query('ROLLBACK');
      throw err;
    }
  }
}
