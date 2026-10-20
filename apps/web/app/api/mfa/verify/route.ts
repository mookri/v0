import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaVerifySchema } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';

/**
 * POST /api/mfa/verify — step-up primitive.
 *
 * Contract §5: **AAL1** required (cookie session); a successful verify
 * *produces* the step-up. §6: MFA completion is a session-rotation
 * trigger — rotation itself is authoritative in Laravel (M5/I15): the
 * backend revokes the presented session and returns its replacement in
 * the login envelope shape. The BFF adopts that rotated token into the
 * session cookie (the predecessor is already dead server-side, so the
 * swap must happen with this response). A response without a rotated
 * token is forwarded untouched.
 *
 * Payload to Laravel is explicit (`code` only): `{...body}` spreads are
 * forbidden — client-supplied keys must never reach the authority (§5).
 */
interface RotatedSession {
  token?: string;
  expires_at?: string;
}

export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaVerifySchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost<RotatedSession>(
      ctx.actor.token,
      '/identity/mfa/verify',
      { tenant_id: laravelTenant(), code: ctx.data.code },
      {},
      { timeout: 'normal', requestId },
    );

    const headers: Record<string, string> = {};
    if (typeof data?.token === 'string' && data.token !== '') {
      const maxAge =
        typeof data.expires_at === 'string'
          ? Math.max(60, Math.floor((Date.parse(data.expires_at) - Date.now()) / 1000))
          : 60;
      headers['Set-Cookie'] = setSessionCookie(data.token, maxAge);
    }

    return Response.json(ok(data, requestId), { headers });
  } catch (e) {
    return laravelErrorResponse(e, 'MFA verify unavailable.', requestId);
  }
}
