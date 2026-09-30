import type { Cache } from '../../packages/contracts/src/common/cache';
import type { TransactionManager } from '../../packages/contracts/src/common/transaction';
import type { PaymentGateway } from '../../packages/contracts/src/payment/gateway';
import type { BookingRepository } from '../../packages/booking/src/ports/BookingRepository';
import { createNoopDbClient } from '../database/postgres/client';
import { PostgresTransactionManager } from '../database/postgres/transaction';
import { PostgresBookingRepository } from '../database/postgres/repositories/PostgresBookingRepository';
import { PostgresPujaRepository } from '../database/postgres/repositories/PostgresPujaRepository';
import { MemoryCache } from '../cache/memory/MemoryCache';
import { MemoryOutboxStore } from '../messaging/outbox/MemoryOutboxStore';
import { createPaymentGateway } from '../payments/providers/providers';

export interface Platform {
  bookingRepository: BookingRepository;
  pujaRepository: PostgresPujaRepository;
  transactions: TransactionManager;
  cache: Cache;
  outbox: MemoryOutboxStore;
  payments: PaymentGateway;
}

// Composition root: the ONLY place that instantiates infrastructure.
// Capabilities receive these via explicit injection — never `new` drivers.
export function createPlatform(): Platform {
  const db = createNoopDbClient();
  return {
    bookingRepository: new PostgresBookingRepository(db),
    pujaRepository: new PostgresPujaRepository(db),
    transactions: new PostgresTransactionManager(db),
    cache: new MemoryCache(),
    outbox: new MemoryOutboxStore(),
    payments: createPaymentGateway({ provider: 'noop' }),
  };
}
