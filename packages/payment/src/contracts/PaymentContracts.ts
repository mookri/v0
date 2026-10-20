import { Payment, PaymentId } from '@navdhya/payment/src/domain/Payment';

export interface InitiatePayment {
  paymentId: PaymentId;
  bookingId: string;
  amountMinor: number;
  currency: string;
  idempotencyKey: string;
  nowIso: string;
}

export interface PaymentSummary {
  id: PaymentId;
  bookingId: string;
  amountMinor: number;
  currency: string;
  status: Payment['status'];
  refundedMinor: number;
}

export function toPaymentSummary(p: Payment): PaymentSummary {
  return {
    id: p.id,
    bookingId: p.bookingId,
    amountMinor: p.amountMinor,
    currency: p.currency,
    status: p.status,
    refundedMinor: p.refundedMinor,
  };
}

export interface RefundPayment {
  paymentId: PaymentId;
  amountMinor?: number;
  reasonCode: string;
  idempotencyKey: string;
  nowIso: string;
}
