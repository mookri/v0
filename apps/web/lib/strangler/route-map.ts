// Strangler map: which web paths stay local vs redirect to a cut-over app.
// Edge-safe by construction: pure string ops, no identity/store imports
// (see docs/architecture/edge-session.md).
// Source of truth for classes: docs/architecture/route-ownership.yaml.

export type StranglerAction =
  | { action: 'serve-local' }
  | { action: 'redirect'; target: string };

export interface StranglerFlags {
  customerLive: boolean;
  customerOrigin: string;
}

const CUSTOMER_PATHS = ['/book', '/user', '/settings'];

export function stranglerTarget(pathname: string, flags: StranglerFlags): StranglerAction {
  const isCustomerPath = CUSTOMER_PATHS.some((p) => pathname === p || pathname.startsWith(p + '/'));
  if (isCustomerPath && flags.customerLive) {
    return { action: 'redirect', target: `${flags.customerOrigin}${pathname}` };
  }
  return { action: 'serve-local' };
}

export function stranglerFlagsFromEnv(env: Record<string, string | undefined>): StranglerFlags {
  return {
    customerLive: env['STRANGLER_CUSTOMER_LIVE'] === '1',
    customerOrigin: env['NEXT_PUBLIC_CUSTOMER_ORIGIN'] ?? 'https://app.navdhya.com',
  };
}
