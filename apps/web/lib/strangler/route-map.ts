// Strangler map: which web paths stay local vs redirect to a cut-over app.
// Edge-safe by construction: pure string ops, no identity/store imports
// (see docs/architecture/edge-session.md).
// Source of truth for classes: docs/architecture/route-ownership.yaml.

export type StranglerAction =
  | { action: 'serve-local' }
  | { action: 'redirect'; target: string };

export interface StranglerFlags {
  bookLive: boolean;
  userLive: boolean;
  settingsLive: boolean;
  customerOrigin: string;
}

export function stranglerTarget(pathname: string, flags: StranglerFlags): StranglerAction {
  const customerClass =
    pathname === '/book' || pathname.startsWith('/book/')
      ? flags.bookLive
      : pathname === '/user' || pathname.startsWith('/user/')
        ? flags.userLive
        : pathname === '/settings' || pathname.startsWith('/settings/')
          ? flags.settingsLive
          : false;
  if (customerClass) {
    return { action: 'redirect', target: `${flags.customerOrigin}${pathname}` };
  }
  return { action: 'serve-local' };
}

function asBool(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined) return fallback;
  return value === '1';
}

export function stranglerFlagsFromEnv(env: Record<string, string | undefined>): StranglerFlags {
  const master = env['STRANGLER_CUSTOMER_LIVE'] === '1';
  return {
    bookLive: asBool(env['STRANGLER_CUSTOMER_BOOK'], master),
    userLive: asBool(env['STRANGLER_CUSTOMER_USER'], master),
    settingsLive: asBool(env['STRANGLER_CUSTOMER_SETTINGS'], master),
    customerOrigin: env['NEXT_PUBLIC_CUSTOMER_ORIGIN'] ?? 'https://app.navdhya.com',
  };
}
