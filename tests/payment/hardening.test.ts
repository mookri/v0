import { test, expect } from 'vitest';
import { createHmac } from 'node:crypto';
import {
  verifyWebhookSignature,
  assertWebhookSignature,
  assertChargeable,
} from '@navdhya/payment/src/domain/webhook-auth';
import { handleWebhook } from '@navdhya/payment/src/application/handle-webhook';
import { initiatePayment } from '@navdhya/payment/src/application/collect-payment';
import { Payment, PaymentId } from '@navdhya/payment/src/domain/Payment';
import { PaymentProvider, PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';
import { RazorpayPaymentGateway } from '@infra/payments/gateways/RazorpayPaymentGateway';
import { createPaymentGateway } from '@infra/payments/providers/providers';

const NOW = '2026-10-01T00:00:00.000Z';
const SECRET = 'whsec_test';
const BODY = '{"event":"payment.captured"}';
const sign = (body: string, secret: string) => createHmac('sha256', secret).update(body, 'utf8').digest('hex');

test('webhook signature accepts exact HMAC, rejects tampering', () => {
  const good = { rawBody: BODY, signature: sign(BODY, SECRET), secret: SECRET };
  expect(verifyWebhookSignature(good)).toBe(true);
  expect(() => assertWebhookSignature(good)).not.toThrow();
  expect(verifyWebhookSignature({ ...good, rawBody: BODY + '!' })).toBe(false);
  expect(verifyWebhookSignature({ ...good, signature: sign(BODY, 'other') })).toBe(false);
  expect(verifyWebhookSignature({ ...good, secret: '' })).toBe(false);
  expect(() => assertWebhookSignature({ ...good, signature: 'deadbeef' })).toThrow('WEBHOOK_UNVERIFIED');
});

test('charge validation enforces minor units and currency shape', () => {
  expect(() => assertChargeable(450000, 'INR')).not.toThrow();
  expect(() => assertChargeable(0, 'INR')).toThrow('INVALID_AMOUNT');
  expect(() => assertChargeable(10.5, 'INR')).toThrow('INVALID_AMOUNT');
  expect(() => assertChargeable(100, 'inr')).toThrow('INVALID_CURRENCY');
  expect(() => assertChargeable(100, 'INRXX')).toThrow('INVALID_CURRENCY');
});

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

const provider: PaymentProvider = {
  createPaymentIntent: async (r) => ({ id: `pi_${r.bookingId}`, status: 'pending' }),
  verifyPayment: async (r) => ({ paymentId: r.paymentId, verified: true, status: 'captured' }),
  refund: async (r) => ({ refundId: `re_${r.paymentId}`, status: 'succeeded' }),
};

test('unverified webhooks never mutate payment state', async () => {
  const repo = memoryRepo();
  await initiatePayment(repo, provider, {
    paymentId: 'pay_h',
    bookingId: 'b_h',
    amountMinor: 1000,
    currency: 'INR',
    idempotencyKey: 'idem-h',
    nowIso: NOW,
  });
  await expect(
    handleWebhook(repo, {
      providerEventId: 'evt_h',
      type: 'payment.captured',
      providerPaymentId: 'pp_h',
      paymentId: 'pay_h',
      verified: false,
      nowIso: NOW,
    }),
  ).rejects.toThrow('WEBHOOK_UNVERIFIED');
  expect((await repo.findById('pay_h'))?.status).toBe('PENDING');
});

function fakeFetch(routes: Record<string, { status: number; body: unknown }>): typeof fetch {
  return (async (url: string | URL | Request) => {
    const u = String(url);
    const key = Object.keys(routes).find((k) => u.endsWith(k));
    const route = key ? routes[key]! : { status: 404, body: {} };
    return { ok: route.status >= 200 && route.status < 300, status: route.status, json: async () => route.body };
  }) as unknown as typeof fetch;
}

const creds = { keyId: 'rzp_test', keySecret: 'secret' };

test('razorpay adapter maps order/payment/refund lifecycles', async () => {
  const gw = new RazorpayPaymentGateway({
    ...creds,
    fetchFn: fakeFetch({
      '/orders': { status: 200, body: { id: 'order_1', status: 'created' } },
      '/payments/pay_1': { status: 200, body: { id: 'pay_1', status: 'captured' } },
      '/payments/pay_1/refund': { status: 200, body: { id: 're_1', status: 'processed' } },
    }),
  });
  const intent = await gw.createPaymentIntent({ amount: 1000, currency: 'INR', bookingId: 'b1', idempotencyKey: 'k1' });
  expect(intent).toEqual({ id: 'order_1', status: 'pending' });
  expect(await gw.verifyPayment({ paymentId: 'pay_1' })).toEqual({ paymentId: 'pay_1', verified: true, status: 'captured' });
  expect(await gw.refund({ paymentId: 'pay_1', idempotencyKey: 'k2' })).toEqual({ refundId: 're_1', status: 'succeeded' });
});

test('razorpay adapter surfaces provider failures and guards credentials', async () => {
  const failing = new RazorpayPaymentGateway({ ...creds, fetchFn: fakeFetch({ '/orders': { status: 401, body: {} } }) });
  await expect(failing.createPaymentIntent({ amount: 1, currency: 'INR', bookingId: 'b', idempotencyKey: 'k' })).rejects.toThrow(
    'RAZORPAY_REQUEST_FAILED:401',
  );
  expect(() => new RazorpayPaymentGateway({ keyId: '', keySecret: '' })).toThrow('RAZORPAY_CREDENTIALS_MISSING');
  expect(() => createPaymentGateway({ provider: 'razorpay' })).toThrow('missing credentials');
  expect(createPaymentGateway({ provider: 'razorpay', keyId: 'k', keySecret: 's' })).toBeInstanceOf(RazorpayPaymentGateway);
});
