import type { PaymentGateway } from '../../../packages/contracts/src/payment/gateway';
import { NoopPaymentGateway } from '../gateways/NoopPaymentGateway';

export interface PaymentProviderConfig {
  provider: 'noop' | 'razorpay';
  keyId?: string;
}

// Provider selection lives here — business code only sees PaymentGateway.
export function createPaymentGateway(config: PaymentProviderConfig): PaymentGateway {
  switch (config.provider) {
    case 'razorpay':
      throw new Error('Razorpay adapter not configured: missing credentials');
    case 'noop':
    default:
      return new NoopPaymentGateway();
  }
}
