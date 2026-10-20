// Signal sink adapters (plan B2): bridge capability signal ports to the
// analytics emitters bound to an outbox store. This is the composition seam —
// capabilities declare PaymentSignalSink / BookingSignalSink ports and must
// never import the analytics package themselves (architecture tests enforce
// that); the wiring layer (infrastructure / app routes / tests) lives above
// both and may import either.
//
// Transactional guarantee: pass a store bound to the current transaction
// (`ctx.outbox` from PostgresTransactionManager). The capability invokes the
// sink BEFORE control leaves the business operation, so the append shares
// the transaction — rollback leaves no signal, commit leaves exactly one.

import type { OutboxStore } from '@navdhya/contracts/src/messaging/outbox';
import type { PaymentSignalSink } from '@navdhya/payment/src/ports/PaymentPorts';
import type { BookingSignalSink } from '@navdhya/booking/src/ports/BookingSignalSink';
import {
  emitBookingConfirmed,
  emitPaymentFailed,
  emitPaymentSucceeded,
} from '@navdhya/analytics/src/server/emitters';

export function paymentSignalSink(outbox: OutboxStore): PaymentSignalSink {
  return {
    paymentSucceeded: async (signal) => {
      await emitPaymentSucceeded(
        outbox,
        { aggregateId: signal.aggregateId },
        { value: signal.value, currency: signal.currency, transaction_ref: signal.transactionRef },
      );
    },
    paymentFailed: async (signal) => {
      await emitPaymentFailed(
        outbox,
        { aggregateId: signal.aggregateId },
        {
          value: signal.value,
          currency: signal.currency,
          transaction_ref: signal.transactionRef,
          ...(signal.failureCode !== undefined ? { failure_code: signal.failureCode } : {}),
        },
      );
    },
  };
}

export function bookingSignalSink(outbox: OutboxStore): BookingSignalSink {
  return {
    bookingConfirmed: async (signal) => {
      await emitBookingConfirmed(
        outbox,
        { aggregateId: signal.aggregateId },
        {
          value: signal.value,
          currency: signal.currency,
          service_category: signal.serviceCategory,
          ...(signal.city !== undefined ? { city: signal.city } : {}),
        },
      );
    },
  };
}
