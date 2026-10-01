import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import type { SessionAdapter, CustomerSession } from '@/adapters/session';

// Backend-cookie session (F2): the login route stores the backend-minted
// HMAC bearer in an HttpOnly cookie; handlers resolve the actor by
// verifying it locally with the shared secret (SESSION_SIGNING_KEY must
// equal the backend IDENTITY_HMAC_SECRET in wired deployments).
// Mocks remain the default — this adapter is only constructed when
// CUSTOMER_BACKEND=laravel.
export const CUSTOMER_TOKEN_COOKIE = '__Host-nv_customer_token';

export function customerTokenSecret(): string {
  return process.env.SESSION_SIGNING_KEY ?? '';
}

export function readCustomerToken(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const eq = part.indexOf('=');
    if (eq === -1) continue;
    if (part.slice(0, eq).trim() === CUSTOMER_TOKEN_COOKIE) {
      return decodeURIComponent(part.slice(eq + 1).trim());
    }
  }
  return null;
}

export function resolveCustomerActor(
  cookieHeader: string | null,
  secret: string,
): { userId: string; sessionId: string } | null {
  const token = readCustomerToken(cookieHeader);
  if (!token || !secret) return null;
  try {
    const actor = verifyActorToken(secret, token, new Date().toISOString());
    return { userId: actor.userId, sessionId: actor.sessionId };
  } catch {
    return null;
  }
}

export function buildSessionCookie(token: string, maxAgeSeconds: number): string {
  const secure = process.env.NODE_ENV === 'production' ? '; Secure' : '';
  return `${CUSTOMER_TOKEN_COOKIE}=${encodeURIComponent(token)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSeconds}${secure}`;
}

export function clearSessionCookie(): string {
  return `${CUSTOMER_TOKEN_COOKIE}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0`;
}

export class BackendSessionAdapter implements SessionAdapter {
  constructor(
    private readonly cookieHeader: string | null,
    private readonly secret: string = customerTokenSecret(),
  ) {}

  async getSession(): Promise<CustomerSession> {
    const actor = resolveCustomerActor(this.cookieHeader, this.secret);
    if (!actor) return { authenticated: false };
    return { authenticated: true, actor: { userId: actor.userId } };
  }
}
