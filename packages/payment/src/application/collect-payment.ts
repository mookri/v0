import { Payment } from '@navdhya/payment/src/domain/Payment';
import { assertTransition } from '@navdhya/payment/src/domain/payment-rules';
import { InitiatePayment, toPaymentSummary, PaymentSummary } from '@navdhya/payment/src/contracts/PaymentContracts';
import { PaymentProvider, PaymentRepository, PaymentSignalSink } from '@navdhya/payment/src/ports/PaymentPorts';

export async function initiatePayment(
  repo: PaymentRepository,
  provider: PaymentProvider,
  req: InitiatePayment,
): Promise<PaymentSummary> {
  if (!Number.isInteger(req.amountMinor) || req.amountMinor <= 0) throw new Error('INVALID_AMOUNT');
  const existing = await repo.findByIdempotencyKey(req.idempotencyKey);
  if (existing) return toPaymentSummary(existing);
  const intent = await provider.createPaymentIntent({
    amount: req.amountMinor,
    currency: req.currency,
    bookingId: req.bookingId,
    idempotencyKey: req.idempotencyKey,
  });
  if (intent.status === 'failed') throw new Error('PROVIDER_INTENT_FAILED');
  const payment: Payment = {
    id: req.paymentId,
    bookingId: req.bookingId,
    amountMinor: req.amountMinor,
    currency: req.currency,
    status: intent.status === 'authorized' ? 'AUTHORIZED' : 'PENDING',
    providerIntentId: intent.id,
    idempotencyKey: req.idempotencyKey,
    refundedMinor: 0,
    providerEventIds: [],
    createdAt: req.nowIso,
    updatedAt: req.nowIso,
  };
  await repo.save(payment);
  return toPaymentSummary(payment);
}

/**
 * Gateway-result boundary (plan B2): captured → `payment_succeeded`,
 * failed → `payment_failed` — emitted through the caller-injected signal sink
 * BEFORE control leaves this function, so emission runs inside whatever
 * transaction surrounds the call (the sink closes over `tx.outbox`). The
 * capability declares the port; composition/tests wire the analytics
 * emitter adapter (architecture rule: capabilities never import analytics).
 * Optional to preserve legacy call sites.
 */
export async function confirmCapture(
  repo: PaymentRepository,
  provider: PaymentProvider,
  paymentId: string,
  providerPaymentId: string,
  nowIso: string,
  signals?: PaymentSignalSink,
): Promise<PaymentSummary> {
  const payment = await repo.findById(paymentId);
  if (!payment) throw new Error('PAYMENT_NOT_FOUND');
  const verification = await provider.verifyPayment({ paymentId: providerPaymentId });
  if (!verification.verified || verification.status !== 'captured') {
    if (verification.status === 'failed') {
      assertTransition(payment.status, 'FAILED');
      await repo.update({ ...payment, status: 'FAILED', updatedAt: nowIso });
      // Registry `value` is INR major units; gateway carries minor units.
      await signals?.paymentFailed({
        aggregateId: payment.id,
        value: payment.amountMinor / 100,
        currency: payment.currency,
        transactionRef: providerPaymentId,
      });
    }
    throw new Error('PAYMENT_NOT_CAPTURED');
  }
  assertTransition(payment.status, 'CAPTURED');
  const next: Payment = {
    ...payment,
    status: 'CAPTURED',
    providerPaymentId: verification.paymentId,
    updatedAt: nowIso,
  };
  await repo.update(next);
  await signals?.paymentSucceeded({
    aggregateId: next.id,
    value: next.amountMinor / 100,
    currency: next.currency,
    transactionRef: verification.paymentId,
  });
  return toPaymentSummary(next);
}
