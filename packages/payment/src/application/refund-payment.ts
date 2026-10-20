import { Payment } from '@navdhya/payment/src/domain/Payment';
import { assertTransition } from '@navdhya/payment/src/domain/payment-rules';
import { RefundPayment, toPaymentSummary, PaymentSummary } from '@navdhya/payment/src/contracts/PaymentContracts';
import { PaymentProvider, PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';

export function refundBlocker(payment: Payment, amountMinor: number): string | null {
  if (payment.status !== 'CAPTURED' && payment.status !== 'PARTIALLY_REFUNDED') return 'NOT_CAPTURED';
  if (!Number.isInteger(amountMinor) || amountMinor <= 0) return 'INVALID_AMOUNT';
  if (payment.refundedMinor + amountMinor > payment.amountMinor) return 'REFUND_OVERRUN';
  return null;
}

export async function refundPayment(
  repo: PaymentRepository,
  provider: PaymentProvider,
  req: RefundPayment,
): Promise<PaymentSummary> {
  const payment = await repo.findById(req.paymentId);
  if (!payment) throw new Error('PAYMENT_NOT_FOUND');
  const amountMinor = req.amountMinor ?? payment.amountMinor - payment.refundedMinor;
  const blocked = refundBlocker(payment, amountMinor);
  if (blocked) throw new Error(blocked);
  if (!payment.providerPaymentId) throw new Error('NO_PROVIDER_PAYMENT');
  const result = await provider.refund({
    paymentId: payment.providerPaymentId,
    amount: amountMinor,
    idempotencyKey: req.idempotencyKey,
  });
  if (result.status !== 'succeeded') throw new Error('PROVIDER_REFUND_FAILED');
  const refundedMinor = payment.refundedMinor + amountMinor;
  const status = refundedMinor === payment.amountMinor ? 'REFUNDED' : 'PARTIALLY_REFUNDED';
  assertTransition(payment.status, status);
  const next: Payment = { ...payment, status, refundedMinor, updatedAt: req.nowIso };
  await repo.update(next);
  return toPaymentSummary(next);
}
