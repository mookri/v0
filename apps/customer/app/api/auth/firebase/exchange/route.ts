import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { buildSessionCookie } from '@/server/backend-session';

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

/**
 * POST /api/auth/firebase/exchange — verify the Firebase ID token and
 * mint the session (S50 kit; backend customers/auth/firebase/exchange).
 *
 * The backend verifies the token, binds phone ↔ intent, enforces
 * eligibility and MFA, and stays authoritative; every
 * credential-shaped failure arrives here as the one collapsed 401 (no
 * oracle on account/MFA state).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { intentId?: string; idToken?: string; totpCode?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const intentId = typeof body.intentId === 'string' ? body.intentId.trim() : '';
  const idToken = typeof body.idToken === 'string' ? body.idToken.trim() : '';
  const totpCode = typeof body.totpCode === 'string' ? body.totpCode.trim() : '';

  // Laravel mints lowercase ULIDs in this kit — Crockford base32, both cases.
  if (!/^[0-9A-HJKMNP-TV-Z]{26}$/i.test(intentId)) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  if (idToken.length < 20 || idToken.length > 8192) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  if (totpCode !== '' && !/^\d{6,8}$/.test(totpCode)) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  try {
    const payload: Record<string, string> = {
      tenant_id: laravelTenant(),
      intent_id: intentId,
      id_token: idToken,
    };
    if (totpCode !== '') payload.totp_code = totpCode;

    const { data: session } = await laravelPost<LaravelSession>(
      null,
      '/customers/auth/firebase/exchange',
      payload,
    );

    // Raw backend bearer in an HttpOnly cookie; the body carries no
    // token (same contract as /api/auth/login).
    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId),
      { headers: { 'Set-Cookie': buildSessionCookie(session.token, maxAge) } },
    );
  } catch (e) {
    // The typed 503 (Firebase admin creds absent/outage) must not read
    // as a credentials problem — the status already says "later".
    const message =
      e instanceof CustomerApiError && e.code === 'DEPENDENCY_UNAVAILABLE'
        ? 'Sign-in is temporarily unavailable. Please try again shortly.'
        : 'Invalid credentials.';
    return laravelErrorResponse(e, message, requestId);
  }
}
