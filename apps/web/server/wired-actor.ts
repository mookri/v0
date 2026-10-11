import { newRequestId, err } from '@/application/api-envelope';
import { extractSessionToken, sessionCookieName } from '@/server/session-cookie';

export interface WiredActor {
  id: string;
  type: 'customer' | 'pandit' | 'admin';
  token: string;
  tenantId?: string;
  scopes: string[];
  requestId: string;
  /** Where the credential came from — contract §7 precedence matrix. */
  source: 'cookie';
}

export type WiredActorResult = WiredActor | Response;

/**
 * Resolve the calling actor — `auth/p0-contract.md` §7.
 *
 * | Cookie | Header      | Result                                       |
 * |--------|-------------|----------------------------------------------|
 * | valid  | any         | cookie wins (header logged as legacy anomaly) |
 * | present but invalid | any | 401, NO fallback (blocks downgrade)     |
 * | absent | valid       | 401                                          |
 * | absent | absent      | 401                                          |
 *
 * "valid" is decided by the backend on every proxied call — the BFF
 * cannot authoritatively judge a token (I18). Here we resolve
 * *provenance* and enforce the fallback policy. A cookie token the
 * backend rejects surfaces as a 401 from `laravelErrorResponse`,
 * never as a silent header retry.
 */
export function wiredActor(req: Request, requestId?: string): WiredActorResult {
  const rid = requestId ?? newRequestId();
  const extraction = extractSessionToken(req);

  const tenantId = req.headers.get('x-tenant-id') || undefined;

  if (extraction.source === 'cookie') {
    return {
      id: 'unknown',
      type: 'customer',
      token: extraction.token!,
      tenantId,
      scopes: ['customer'],
      requestId: rid,
      source: 'cookie',
    };
  }

  // No usable credential
  return Response.json(
    err('UNAUTHENTICATED', 'Sign in to continue.', rid),
    { status: 401 },
  );
}

export function extractTenantId(req: Request): string | null {
  return req.headers.get('x-tenant-id');
}
