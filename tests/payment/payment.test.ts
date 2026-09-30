import { test, expect } from 'vitest';
import { canTransition, assertTransition } from '@navdhya/payment/src/domain/payment-rules';
import { initiatePayment, confirmCapture } from '@navdhya/payment/src/application/collect-payment';
import { refundPayment, refundBlocker } from '@navdhya/payment/src/application/refund-payment';
import { handleWebhook } from '@navdhya/payment/src/application/handle-webhook';
import { Payment, PaymentId } from '@navdhya/payment/src/domain/Payment';
import { PaymentProvider, PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';

const NOW = '2026-10-01T00:00:00.000Z';

function memoryRepo(seed: Payment[] = []): PaymentRepository {
  const byId = new Map<PaymentId, Payment>(seed.map((p) => [p.id, p]));
  const byKey = new Map<string, Payment>(seed.map((p) => [p.idempotencyKey, p]));
  return {
    findById: async (id) => byId.get(id) ?? null,
    findByIdempotencyKey: async (k) => byKey.get(k) ?? null,
    findByProviderEventId: async (e) => [...byId.values()].find((p) => p.providerEventIds.includes(e)) ?? null,
    save: async (p) => {
      byId.set(p.id, p);
      byKey.set(p.idempotencyKey, p);
    },
    update: async (p) => {
      byId.set(p.id, p);
      byKey.set(p.idempotencyKey, p);
    },
  };
}

function fakeProvider(over: Partial<PaymentProvider> = {}): PaymentProvider {
  return {
    createPaymentIntent: async (r) => ({ id: `pi_${r.bookingId}`, status: 'pending' }),
    verifyPayment: async (r) => ({ paymentId: r.paymentId, verified: true, status: 'captured' }),
    refund: async (r) => ({ refundId: `re_${r.paymentId}`, status: 'succeeded' }),
    ...over,
  };
}

const req = (over: Record<string, unknown> = {}) => ({
  paymentId: 'pay_1',
  bookingId: 'b_1',
  amountMinor: 450000,
  currency: 'INR',
  idempotencyKey: 'idem-1',
  nowIso: NOW,
  ...over,
});

test('rules: capture path legal, terminal states closed', () => {
  expect(canTransition('PENDING', 'AUTHORIZED')).toBe(true);
  expect(canTransition('PENDING', 'CAPTURED')).toBe(true);
  expect(canTransition('AUTHORIZED', 'CAPTURED')).toBe(true);
  expect(canTransition('CAPTURED', 'PARTIALLY_REFUNDED')).toBe(true);
  expect(canTransition('PARTIALLY_REFUNDED', 'REFUNDED')).toBe(true);
  expect(canTransition('CAPTURED', 'CANCELLED')).toBe(false);
  expect(canTransition('REFUNDED', 'REFUNDED')).toBe(false);
  expect(() => assertTransition('PENDING', 'REFUNDED')).toThrow('ILLEGAL_PAYMENT_TRANSITION');
});

test('initiate: creates pending intent; idempotent on key; rejects bad amount', async () => {
  const repo = memoryRepo();
  const s1 = await initiatePayment(repo, fakeProvider(), req());
  expect(s1.status).toBe('PENDING');
  const s2 = await initiatePayment(repo, fakeProvider(), req());
  expect(s2).toEqual(s1);
  await expect(initiatePayment(repo, fakeProvider(), req({ paymentId: 'pay_2', idempotencyKey: 'idem-2', amountMinor: 0 }))).rejects.toThrow('INVALID_AMOUNT');
  const failing = fakeProvider({ createPaymentIntent: async () => ({ id: 'pi_x', status: 'failed' as const }) });
  await expect(initiatePayment(repo, failing, req({ paymentId: 'pay_3', idempotencyKey: 'idem-3' }))).rejects.toThrow('PROVIDER_INTENT_FAILED');
});

test('confirmCapture: captured on verified; failed on provider failure', async () => {
  const repo = memoryRepo();
  await initiatePayment(repo, fakeProvider(), req());
  const captured = await confirmCapture(repo, fakeProvider(), 'pay_1', 'pay_provider_1', NOW);
  expect(captured.status).toBe('CAPTURED');
  const repo2 = memoryRepo();
  await initiatePayment(repo2, fakeProvider(), req({ paymentId: 'pay_9', idempotencyKey: 'idem-9' }));
  const failedProvider = fakeProvider({
    verifyPayment: async (r) => ({ paymentId: r.paymentId, verified: false, status: 'failed' as const }),
  });
  await expect(confirmCapture(repo2, failedProvider, 'pay_9', 'pp_9', NOW)).rejects.toThrow('PAYMENT_NOT_CAPTURED');
  expect((await repo2.findById('pay_9'))?.status).toBe('FAILED');
});

test('refund: partial then full; overrun and non-captured blocked', async () => {
  const repo = memoryRepo();
  await initiatePayment(repo, fakeProvider(), req());
  await confirmCapture(repo, fakeProvider(), 'pay_1', 'pp_1', NOW);
  await expect(
    refundPayment(repo, fakeProvider(), { paymentId: 'pay_1', amountMinor: 450001, reasonCode: 'DUP', idempotencyKey: 'r-0', nowIso: NOW }),
  ).rejects.toThrow('REFUND_OVERRUN');
  const partial = await refundPayment(repo, fakeProvider(), { paymentId: 'pay_1', amountMinor: 50000, reasonCode: 'PARTIAL', idempotencyKey: 'r-1', nowIso: NOW });
  expect(partial.status).toBe('PARTIALLY_REFUNDED');
  expect(partial.refundedMinor).toBe(50000);
  const full = await refundPayment(repo, fakeProvider(), { paymentId: 'pay_1', reasonCode: 'FINAL', idempotencyKey: 'r-2', nowIso: NOW });
  expect(full.status).toBe('REFUNDED');
  expect(full.refundedMinor).toBe(450000);
  expect(refundBlocker((await repo.findById('pay_1'))!, 1)).toBe('NOT_CAPTURED');
});

test('webhook: capture applies once; replay returns current summary', async () => {
  const repo = memoryRepo();
  await initiatePayment(repo, fakeProvider(), req());
  const evt = { providerEventId: 'evt_1', type: 'payment.captured' as const, providerPaymentId: 'pp_1', paymentId: 'pay_1', nowIso: NOW };
  const first = await handleWebhook(repo, evt);
  expect(first.status).toBe('CAPTURED');
  const replay = await handleWebhook(repo, evt);
  expect(replay).toEqual(first);
});
