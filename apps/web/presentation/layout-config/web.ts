import { Policy } from '@navdhya/layout-engine/src/policy/Policy';
import { PRECEDENCE } from '@navdhya/layout-engine/src/policy/PolicyRule';

// Web product policies. viewport=mobile changes regions/layout — never product.
export const webPolicies: Policy[] = [
  {
    id: 'web-puja-detail',
    when: { product: 'web', routeId: 'puja-detail' },
    result: {
      shell: 'public-web',
      layout: 'standard-content',
      regions: ['header', 'hero', 'primary-content', 'recommendations', 'footer'],
    },
    priority: PRECEDENCE.explicitRoute,
  },
  {
    id: 'web-public-default',
    when: { product: 'web' },
    result: {
      shell: 'public-web',
      layout: 'standard-content',
      regions: ['header', 'primary-content', 'footer'],
    },
    priority: PRECEDENCE.product,
  },
  {
    id: 'web-mobile-structure',
    when: { product: 'web', viewport: 'mobile' },
    result: {
      shell: 'public-web',
      layout: 'standard-content',
      regions: ['header', 'primary-content', 'actions', 'footer'],
    },
    priority: PRECEDENCE.viewport,
  },
];
