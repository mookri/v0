import { test, expect } from 'vitest';
import { resolveLayout } from '../../packages/layout-engine/src/resolver/LayoutResolver';
import { resolveLayoutContext } from '../../packages/layout-engine/src/resolver/ContextResolver';
import { webPolicies } from '../../apps/web/presentation/layout-config/web';
import { customerPolicies } from '../../apps/web/presentation/layout-config/customer';

const baseRoute = { id: 'home', category: 'content', intent: 'view' };
const locale = { language: 'en' };

test('web public desktop resolves public-web', () => {
  const ctx = resolveLayoutContext({
    product: { id: 'web' },
    route: baseRoute,
    viewport: { mode: 'desktop' },
    actor: { authenticated: false, roles: [] },
    locale,
  });
  const { decision } = resolveLayout(ctx, webPolicies);
  expect(decision.shell).toBe('public-web');
  expect(decision.policyId).toBe('web-public-default');
});

test('customer with role on web still gets web (role != product)', () => {
  const ctx = resolveLayoutContext({
    product: { id: 'web' },
    route: { id: 'puja-detail', category: 'puja', intent: 'view' },
    viewport: { mode: 'desktop' },
    actor: { authenticated: true, roles: ['customer'] },
    locale,
  });
  const { decision } = resolveLayout(ctx, webPolicies);
  expect(decision.shell).toBe('public-web');
  expect(decision.policyId).toBe('web-puja-detail');
});

test('product=customer resolves customer-app independently', () => {
  const ctx = resolveLayoutContext({
    product: { id: 'customer' },
    route: { id: 'bookings', category: 'booking', intent: 'view' },
    viewport: { mode: 'mobile' },
    actor: { authenticated: true, roles: ['customer'] },
    locale,
  });
  const { decision } = resolveLayout(ctx, customerPolicies);
  expect(decision.shell).toBe('customer-app');
  expect(decision.layout).toBe('dashboard');
});

test('explicit route beats viewport default (precedence)', () => {
  const ctx = resolveLayoutContext({
    product: { id: 'web' },
    route: { id: 'puja-detail', category: 'puja', intent: 'view' },
    viewport: { mode: 'mobile' },
    actor: { authenticated: false, roles: [] },
    locale,
  });
  const { decision, policy } = resolveLayout(ctx, webPolicies);
  expect(policy).toBe('web-puja-detail');
  expect(decision.shell).toBe('public-web');
});

test('same context always yields same decision (determinism)', () => {
  const input = {
    product: { id: 'web' as const },
    route: baseRoute,
    viewport: { mode: 'desktop' as const },
    actor: { authenticated: false, roles: [] as string[] },
    locale,
  };
  const a = resolveLayout(resolveLayoutContext(input), webPolicies);
  const b = resolveLayout(resolveLayoutContext(input), webPolicies);
  expect(a.decision).toEqual(b.decision);
});

test('mobile never changes product shell (viewport < product precedence)', () => {
  const mk = (mode: 'mobile' | 'desktop') =>
    resolveLayout(
      resolveLayoutContext({
        product: { id: 'web' },
        route: baseRoute,
        viewport: { mode },
        actor: { authenticated: false, roles: [] },
        locale,
      }),
      webPolicies,
    );
  expect(mk('mobile').decision.shell).toBe('public-web');
  expect(mk('desktop').decision.shell).toBe('public-web');
  // product (800) outranks viewport (400), so home keeps product default regions
  expect(mk('mobile').policy).toBe('web-public-default');
});
