export type PaymentId = string;

export type PaymentStatus =
  | 'REQUIRES_INTENT'
  | 'PENDING'
  | 'AUTHORIZED'
  | 'CAPTURED'
  | 'FAILED'
  | 'CANCELLED'
  | 'REFUNDED'
  | 'PARTIALLY_REFUNDED';

export interface Payment {
  id: PaymentId;
  bookingId: string;
  amountMinor: number;
  currency: string;
  status: PaymentStatus;
  providerIntentId?: string;
  providerPaymentId?: string;
  idempotencyKey: string;
  refundedMinor: number;
  providerEventIds: string[];
  createdAt: string;
  updatedAt: string;
}
