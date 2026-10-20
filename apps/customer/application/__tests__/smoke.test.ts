import { describe, expect, it } from 'vitest';
import { PRODUCT_ID, customerConfig, customerFeatures } from '@/config/application';
import { customerRoutes, routeById } from '@/application/routes';

describe('customer application identity', () => {
  it('declares an explicit product id', () => {
    expect(PRODUCT_ID).toBe('customer');
    expect(customerConfig.product).toBe('customer');
  });

  it('exposes the expected feature flags', () => {
    expect(customerFeatures.bookings).toBe(true);
    expect(customerFeatures.support).toBe(true);
    expect(customerFeatures.ads).toBe(false);
  });
});

describe('customer route registry', () => {
  it('namespaces every route id under customer.', () => {
    for (const route of Object.values(customerRoutes)) {
      expect(route.id).toMatch(/^customer\./);
      expect(route.product).toBe('customer');
    }
  });

  it('resolves routes by id', () => {
    expect(routeById('customer.home')?.path).toBe('/');
    expect(routeById('customer.bookings')?.path).toBe('/bookings');
    expect(routeById('customer.settings')?.access).toBe('authenticated');
    expect(routeById('nope')).toBeNull();
  });

  it('marks protected routes as authenticated', () => {
    for (const route of Object.values(customerRoutes)) {
      expect(route.access).toBe('authenticated');
    }
  });
});
