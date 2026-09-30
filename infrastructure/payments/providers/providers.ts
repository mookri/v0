import type { PaymentGateway } from '@navdhya/contracts/src/payment/gateway';
import { NoopPaymentGateway } from '@infra/payments/gateways/NoopPaymentGateway';
import { RazorpayPaymentGateway } from '@infra/payments/gateways/RazorpayPaymentGateway';

export interface PaymentProviderConfig {
  provider: 'noop' | 'razorpay';
  keyId?: string;
  keySecret?: string;
}

// Provider selection lives here — business code only sees PaymentGateway.
// Razorpay credentials are server-only env (never NEXT_PUBLIC_*).
export function createPaymentGateway(config: PaymentProviderConfig): PaymentGateway {
  switch (config.provider) {
    case 'razorpay':
      if (!config.keyId || !config.keySecret) {
        throw new Error('Razorpay adapter not configured: missing credentials');
      }
      return new RazorpayPaymentGateway({ keyId: config.keyId, keySecret: config.keySecret });
    case 'noop':
    default:
      return new NoopPaymentGateway();
  }
}
