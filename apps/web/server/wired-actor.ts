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
  source: 'cookie' | 'header';
}

export type WiredActorResult = WiredActor | Response;

/**
 * Resolve the calling actor — `auth/p0-contract.md` §7.
 *
 * | Cookie | Header      | Result                                       |
 * |--------|-------------|----------------------------------------------|
 * | valid  | any         | cookie wins (header logged as legacy anomaly) |
 * | present but invalid | any | 401, NO fallback (blocks downgrade)     |
 * | absent | valid       | 401 unless LEGACY_HEADER_AUTH=1              |
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
    // Precedence row 1: cookie wins. A simultaneous header token is
    // recorded so legacy clients can be flagged for migration; it must
    // never influence authentication.
    if (extraction.legacyHeaderWithCookie) {
      console.warn(
        `[auth] legacy header token present alongside cookie — ignored (requestId=${rid}, cookie=${sessionCookieName()})`,
      );
    }
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

  if (extraction.source === 'header') {
    // Precedence row 3: only reachable when LEGACY_HEADER_AUTH=1.
    return {
      id: 'unknown',
      type: 'customer',
      token: extraction.token!,
      tenantId,
      scopes: ['customer'],
      requestId: rid,
      source: 'header',
    };
  }

  // Rows 2/4: no usable credential. Row 2 (cookie present but its token
  // will fail validation) and row 4 (nothing) both terminate at 401
  // here; header-only without the flag is row 2's sibling — reject.
  return Response.json(
    err('UNAUTHENTICATED', 'Sign in to continue.', rid),
    { status: 401 },
  );
}

export function extractTenantId(req: Request): string | null {
  return req.headers.get('x-tenant-id');
}
