import { Payment } from '@navdhya/payment/src/domain/Payment';

export interface PaymentCaptured {
  kind: 'PaymentCaptured';
  paymentId: string;
  bookingId: string;
  amountMinor: number;
  currency: string;
  occurredAt: string;
}

export interface PaymentFailed {
  kind: 'PaymentFailed';
  paymentId: string;
  bookingId: string;
  reason: string;
  occurredAt: string;
}

export interface PaymentRefunded {
  kind: 'PaymentRefunded';
  paymentId: string;
  bookingId: string;
  refundedMinor: number;
  fullyRefunded: boolean;
  occurredAt: string;
}

export function paymentCaptured(p: Payment, nowIso: string): PaymentCaptured {
  return {
    kind: 'PaymentCaptured',
    paymentId: p.id,
    bookingId: p.bookingId,
    amountMinor: p.amountMinor,
    currency: p.currency,
    occurredAt: nowIso,
  };
}
