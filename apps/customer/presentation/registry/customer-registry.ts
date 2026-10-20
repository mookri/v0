export const customerShells = ['customer-app', 'customer-minimal'] as const;
export const customerLayouts = ['dashboard', 'booking', 'profile'] as const;

export interface CustomerPresentationConfig {
  product: 'customer';
  shells: readonly string[];
  layouts: readonly string[];
}

export const customerPresentation: CustomerPresentationConfig = {
  product: 'customer',
  shells: customerShells,
  layouts: customerLayouts,
};
