import { Payment } from '@navdhya/payment/src/domain/Payment';
import { assertTransition } from '@navdhya/payment/src/domain/payment-rules';
import { toPaymentSummary, PaymentSummary } from '@navdhya/payment/src/contracts/PaymentContracts';
import { PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';

export interface ProviderWebhookEvent {
  providerEventId: string;
  type: 'payment.captured' | 'payment.failed';
  providerPaymentId: string;
  paymentId: string;
  nowIso: string;
}

// Idempotent: replayed provider events return the current summary unchanged.
export async function handleWebhook(
  repo: PaymentRepository,
  event: ProviderWebhookEvent,
): Promise<PaymentSummary> {
  const seen = await repo.findByProviderEventId(event.providerEventId);
  if (seen) return toPaymentSummary(seen);
  const payment = await repo.findById(event.paymentId);
  if (!payment) throw new Error('PAYMENT_NOT_FOUND');
  if (payment.providerEventIds.includes(event.providerEventId)) return toPaymentSummary(payment);
  const next: Payment =
    event.type === 'payment.captured'
      ? { ...payment, status: 'CAPTURED', providerPaymentId: event.providerPaymentId, updatedAt: event.nowIso, providerEventIds: [...payment.providerEventIds, event.providerEventId] }
      : { ...payment, status: 'FAILED', providerPaymentId: event.providerPaymentId, updatedAt: event.nowIso, providerEventIds: [...payment.providerEventIds, event.providerEventId] };
  assertTransition(payment.status, next.status);
  await repo.update(next);
  return toPaymentSummary(next);
}
