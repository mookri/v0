import { createHmac, timingSafeEqual } from 'node:crypto';

// Provider-webhook authentication. Signatures are HMAC-SHA256 over the raw
// request body with the provider webhook secret. Comparison is timing-safe;
// all failures collapse to one code so callers leak nothing about why.
export interface WebhookSignature {
  rawBody: string;
  signature: string;
  secret: string;
}

export function verifyWebhookSignature({ rawBody, signature, secret }: WebhookSignature): boolean {
  if (!rawBody || !signature || !secret) return false;
  const expected = createHmac('sha256', secret).update(rawBody, 'utf8').digest('hex');
  const a = Buffer.from(expected, 'utf8');
  const b = Buffer.from(signature, 'utf8');
  if (a.length !== b.length) return false;
  return timingSafeEqual(a, b);
}

export function assertWebhookSignature(sig: WebhookSignature): void {
  if (!verifyWebhookSignature(sig)) throw new Error('WEBHOOK_UNVERIFIED');
}

const ISO_CURRENCY = /^[A-Z]{3}$/;

export function assertChargeable(amountMinor: number, currency: string): void {
  if (!Number.isInteger(amountMinor) || amountMinor <= 0) throw new Error('INVALID_AMOUNT');
  if (!ISO_CURRENCY.test(currency)) throw new Error('INVALID_CURRENCY');
}
