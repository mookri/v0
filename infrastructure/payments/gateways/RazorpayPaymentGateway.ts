import type {
  CreatePaymentIntentRequest,
  PaymentGateway,
  PaymentIntent,
  PaymentVerification,
  RefundRequest,
  RefundResult,
  VerifyPaymentRequest,
} from '@navdhya/contracts/src/payment/gateway';

export interface RazorpayConfig {
  keyId: string;
  keySecret: string;
  fetchFn?: typeof fetch;
}

interface RazorpayOrder {
  id: string;
  status: 'created' | 'attempted' | 'paid';
}

interface RazorpayPayment {
  id: string;
  status: 'created' | 'authorized' | 'captured' | 'failed';
}

interface RazorpayRefund {
  id: string;
  status: 'pending' | 'processed' | 'failed';
}

// Razorpay adapter behind the PaymentGateway port. Credentials come from
// server env only (never NEXT_PUBLIC_*); fetch is injectable for tests.
export class RazorpayPaymentGateway implements PaymentGateway {
  private readonly auth: string;
  private readonly fetchFn: typeof fetch;

  constructor(config: RazorpayConfig) {
    if (!config.keyId || !config.keySecret) throw new Error('RAZORPAY_CREDENTIALS_MISSING');
    this.auth = `Basic ${Buffer.from(`${config.keyId}:${config.keySecret}`).toString('base64')}`;
    this.fetchFn = config.fetchFn ?? fetch;
  }

  private async call<T>(path: string, body?: unknown): Promise<T> {
    const res = await this.fetchFn(`https://api.razorpay.com/v1${path}`, {
      method: 'POST',
      headers: { Authorization: this.auth, 'Content-Type': 'application/json' },
      body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) throw new Error(`RAZORPAY_REQUEST_FAILED:${res.status}`);
    return (await res.json()) as T;
  }

  private async get<T>(path: string): Promise<T> {
    const res = await this.fetchFn(`https://api.razorpay.com/v1${path}`, {
      headers: { Authorization: this.auth },
    });
    if (!res.ok) throw new Error(`RAZORPAY_REQUEST_FAILED:${res.status}`);
    return (await res.json()) as T;
  }

  async createPaymentIntent(req: CreatePaymentIntentRequest): Promise<PaymentIntent> {
    const order = await this.call<RazorpayOrder>('/orders', {
      amount: req.amount,
      currency: req.currency,
      receipt: req.bookingId,
    });
    return { id: order.id, status: order.status === 'paid' ? 'authorized' : 'pending' };
  }

  async verifyPayment(req: VerifyPaymentRequest): Promise<PaymentVerification> {
    const payment = await this.get<RazorpayPayment>(`/payments/${req.paymentId}`);
    if (payment.status === 'captured') return { paymentId: payment.id, verified: true, status: 'captured' };
    if (payment.status === 'failed') return { paymentId: payment.id, verified: false, status: 'failed' };
    return { paymentId: payment.id, verified: false, status: 'pending' };
  }

  async refund(req: RefundRequest): Promise<RefundResult> {
    const refund = await this.call<RazorpayRefund>(
      `/payments/${req.paymentId}/refund`,
      req.amount !== undefined ? { amount: req.amount } : {},
    );
    if (refund.status === 'processed') return { refundId: refund.id, status: 'succeeded' };
    if (refund.status === 'failed') return { refundId: refund.id, status: 'failed' };
    return { refundId: refund.id, status: 'pending' };
  }
}
