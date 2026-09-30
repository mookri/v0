import { Payment } from '@navdhya/payment/src/domain/Payment';
import { assertTransition } from '@navdhya/payment/src/domain/payment-rules';
import { InitiatePayment, toPaymentSummary, PaymentSummary } from '@navdhya/payment/src/contracts/PaymentContracts';
import { PaymentProvider, PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';

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

export async function confirmCapture(
  repo: PaymentRepository,
  provider: PaymentProvider,
  paymentId: string,
  providerPaymentId: string,
  nowIso: string,
): Promise<PaymentSummary> {
  const payment = await repo.findById(paymentId);
  if (!payment) throw new Error('PAYMENT_NOT_FOUND');
  const verification = await provider.verifyPayment({ paymentId: providerPaymentId });
  if (!verification.verified || verification.status !== 'captured') {
    if (verification.status === 'failed') {
      assertTransition(payment.status, 'FAILED');
      await repo.update({ ...payment, status: 'FAILED', updatedAt: nowIso });
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
  return toPaymentSummary(next);
}
