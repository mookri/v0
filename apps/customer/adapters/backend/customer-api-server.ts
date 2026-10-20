import { headers } from 'next/headers';

import { createCustomerApi, type CustomerApiFetch } from './customer-api-client';

// Server-side transport for Server Components. Relative fetch URLs are
// browser-only, so resolve the incoming request's origin and forward its
// cookie header (the BFF reads the HttpOnly session from that cookie).
// Every caller is a force-dynamic page; next/headers keeps it that way.
export async function getServerCustomerApi() {
  const requestHeaders = await headers();
  const host = requestHeaders.get('x-forwarded-host') ?? requestHeaders.get('host');
  if (!host) {
    throw new Error('Cannot resolve request host for the customer API client.');
  }
  const proto =
    requestHeaders.get('x-forwarded-proto') ??
    (process.env.NODE_ENV === 'production' ? 'https' : 'http');
  const cookie = requestHeaders.get('cookie');
  const base = `${proto}://${host}`;

  const fetchImpl: CustomerApiFetch = (path, init) => {
    const outgoing = new Headers(init?.headers);
    if (cookie) outgoing.set('cookie', cookie);
    return fetch(path.startsWith('http') ? path : `${base}${path}`, {
      ...init,
      headers: outgoing,
      cache: 'no-store',
    });
  };

  return createCustomerApi(fetchImpl);
}
