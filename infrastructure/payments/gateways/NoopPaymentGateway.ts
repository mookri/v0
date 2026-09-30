import type {
  CreatePaymentIntentRequest,
  PaymentGateway,
  PaymentIntent,
  PaymentVerification,
  RefundRequest,
  RefundResult,
  VerifyPaymentRequest,
} from '../../../packages/contracts/src/payment/gateway';

// Deterministic stand-in satisfying the PaymentGateway contract in tests and
// local dev. Provider adapters (Razorpay/…) implement the same interface.
export class NoopPaymentGateway implements PaymentGateway {
  async createPaymentIntent(req: CreatePaymentIntentRequest): Promise<PaymentIntent> {
    return { id: `pi_${req.bookingId}`, status: 'pending' };
  }

  async verifyPayment(req: VerifyPaymentRequest): Promise<PaymentVerification> {
    return { paymentId: req.paymentId, verified: true, status: 'captured' };
  }

  async refund(req: RefundRequest): Promise<RefundResult> {
    return { refundId: `re_${req.paymentId}`, status: 'succeeded' };
  }
}
