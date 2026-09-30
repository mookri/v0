import { Policy } from '../../../../packages/layout-engine/src/policy/Policy';
import { PRECEDENCE } from '../../../../packages/layout-engine/src/policy/PolicyRule';

// Proves product=customer resolves independently without building apps/customer yet.
// A customer on navdhya.com still gets web (see decision tests); only product=customer gets customer-app.
export const customerPolicies: Policy[] = [
  {
    id: 'customer-bookings',
    when: { product: 'customer', routeId: 'bookings', authenticated: true },
    result: {
      shell: 'customer-app',
      layout: 'dashboard',
      regions: ['topbar', 'summary', 'upcoming', 'bottom-navigation'],
    },
    priority: PRECEDENCE.explicitRoute,
  },
  {
    id: 'customer-authenticated-default',
    when: { product: 'customer', authenticated: true },
    result: {
      shell: 'customer-app',
      layout: 'dashboard',
      regions: ['topbar', 'summary', 'actions', 'bottom-navigation'],
    },
    priority: PRECEDENCE.product,
  },
];
