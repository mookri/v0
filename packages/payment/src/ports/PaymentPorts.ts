import { Payment, PaymentId } from '@navdhya/payment/src/domain/Payment';

// Provider-facing port. Shape-compatible with the shared gateway contract in
// @navdhya/contracts so existing adapters (e.g. NoopPaymentGateway) satisfy
// this port structurally without modification.
export interface PaymentProvider {
  createPaymentIntent(request: {
    amount: number;
    currency: string;
    bookingId: string;
    idempotencyKey: string;
  }): Promise<{ id: string; status: 'requires_action' | 'pending' | 'authorized' | 'failed' }>;
  verifyPayment(request: { paymentId: string }): Promise<{
    paymentId: string;
    verified: boolean;
    status: 'captured' | 'failed' | 'pending';
  }>;
  refund(request: { paymentId: string; amount?: number; idempotencyKey: string }): Promise<{
    refundId: string;
    status: 'succeeded' | 'failed' | 'pending';
  }>;
}

export interface PaymentRepository {
  findById(id: PaymentId): Promise<Payment | null>;
  findByIdempotencyKey(key: string): Promise<Payment | null>;
  findByProviderEventId(eventId: string): Promise<Payment | null>;
  save(payment: Payment): Promise<void>;
  update(payment: Payment): Promise<void>;
}

/**
 * Signals port (plan B2): the capability emits SEMANTIC FACTS here; the
 * composition layer adapts them to SignalEnvelopes (via the analytics
 * package's server emitters bound to a tx outbox). Keeps capability →
 * analytics imports forbidden (architecture tests) while emission still
 * runs inside the business transaction.
 */
export interface PaymentSignal {
  /** payment.id — the aggregate this signal describes. */
  aggregateId: string;
  /** INR major units (registry `value` semantics). */
  value: number;
  currency: string;
  /** Gateway payment/order reference (registry `transaction_ref`). */
  transactionRef: string;
  failureCode?: string;
}

export interface PaymentSignalSink {
  paymentSucceeded(signal: PaymentSignal): Promise<void>;
  paymentFailed(signal: PaymentSignal): Promise<void>;
}
