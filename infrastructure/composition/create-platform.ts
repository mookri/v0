import type { Cache } from '@navdhya/contracts/src/common/cache';
import type { TransactionManager } from '@navdhya/contracts/src/common/transaction';
import type { PaymentGateway } from '@navdhya/contracts/src/payment/gateway';
import type { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import { createNoopDbClient } from '@infra/database/postgres/client';
import { PostgresTransactionManager } from '@infra/database/postgres/transaction';
import { PostgresBookingRepository } from '@infra/database/postgres/repositories/PostgresBookingRepository';
import { PostgresPujaRepository } from '@infra/database/postgres/repositories/PostgresPujaRepository';
import { MemoryCache } from '@infra/cache/memory/MemoryCache';
import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import { createPaymentGateway } from '@infra/payments/providers/providers';

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
