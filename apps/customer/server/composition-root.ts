import { PRODUCT_ID } from '@/config/application';
import { ProductionSessionAdapter } from '@/adapters/session';

export interface CustomerPlatform {
  product: typeof PRODUCT_ID;
  requestId: string;
  routeId?: string;
}

export function createCustomerApplication(routeId?: string): CustomerPlatform {
  return {
    product: PRODUCT_ID,
    requestId: `creq_${Date.now()}`,
    routeId,
  };
}

export function createSessionAdapter() {
  return new ProductionSessionAdapter();
}
