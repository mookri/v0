export const PRODUCT_ID = 'customer' as const;
export type ProductId = 'web' | 'customer' | 'partner' | 'admin' | 'operations';

export const customerConfig = {
  product: PRODUCT_ID,
  domains: {
    production: 'https://app.navdhya.com',
    staging: 'https://app-staging.navdhya.com',
    local: 'http://app.navdhya.local:3001',
  },
  capabilities: {
    booking: true,
    recommendations: true,
    ads: false,
  },
} as const;

export interface CustomerFeatures {
  bookings: boolean;
  savedPujas: boolean;
  recommendations: boolean;
  notifications: boolean;
  support: boolean;
  ads: boolean;
}

export const customerFeatures: CustomerFeatures = {
  bookings: true,
  savedPujas: true,
  recommendations: true,
  notifications: true,
  support: true,
  ads: false,
};
