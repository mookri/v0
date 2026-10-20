export interface CreatePaymentIntentRequest {
  amount: number;
  currency: string;
  bookingId: string;
  idempotencyKey: string;
}

export interface PaymentIntent {
  id: string;
  status: 'requires_action' | 'pending' | 'authorized' | 'failed';
}

export interface VerifyPaymentRequest {
  paymentId: string;
}

export interface PaymentVerification {
  paymentId: string;
  verified: boolean;
  status: 'captured' | 'failed' | 'pending';
}

export interface RefundRequest {
  paymentId: string;
  amount?: number;
  idempotencyKey: string;
}

export interface RefundResult {
  refundId: string;
  status: 'succeeded' | 'failed' | 'pending';
}

export interface PaymentGateway {
  createPaymentIntent(request: CreatePaymentIntentRequest): Promise<PaymentIntent>;
  verifyPayment(request: VerifyPaymentRequest): Promise<PaymentVerification>;
  refund(request: RefundRequest): Promise<RefundResult>;
}
