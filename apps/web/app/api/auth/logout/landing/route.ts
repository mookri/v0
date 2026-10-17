import { timingSafeEqual } from 'node:crypto';

import { isFederated } from '@/server/federation';
import {
  clearSessionCookie,
  clearLogoutStateCookie,
  readLogoutStateCookie,
} from '@/server/session-cookie';

/**
 * GET /api/auth/logout/landing — the registered post_logout_redirect_
 * uri target (OIDC RP-Initiated Logout 1.0, A-SEC-004-6). The OP
 * returns the browser here with the `state` minted by
 * /api/auth/logout/start; on a correlation match the route clears the
 * session cookie and acknowledges the sign-out with a no-store page.
 *
 * Cleanup is IDEMPOTENT and runs on every request, matched state or
 * not: the back-channel denylist may already have killed the cookie,
 * and a stray landing visit must never leave state behind. A
 * mismatched/absent state still cleans up but NEVER acknowledges — it
 * redirects home, so a crafted link cannot narrate a sign-out the RP
 * did not start. 404 outside federation.
 */
export async function GET(req: Request): Promise<Response> {
  if (!isFederated()) {
    return new Response(null, { status: 404 });
  }

  const origin = new URL(req.url).origin;
  const expected = readLogoutStateCookie(req.headers.get('cookie'));
  const provided = new URL(req.url).searchParams.get('state') ?? '';

  const headers = new Headers({ 'Cache-Control': 'no-store' });
  for (const cookie of [...asList(clearSessionCookie()), clearLogoutStateCookie()]) {
    headers.append('Set-Cookie', cookie);
  }

  if (expected !== null && safeEqual(provided, expected)) {
    headers.set('Content-Type', 'text/html; charset=utf-8');
    return new Response(SIGNED_OUT_PAGE, { status: 200, headers });
  }

  headers.set('Location', `${origin}/`);
  return new Response(null, { status: 307, headers });
}

function asList(value: string | string[]): string[] {
  return Array.isArray(value) ? value : [value];
}

function safeEqual(a: string, b: string): boolean {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) return false;
  return timingSafeEqual(bufA, bufB);
}

const SIGNED_OUT_PAGE = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Signed out</title>
</head>
<body style="font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; background: #fff7ed; color: #2a2421; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0;">
    <main style="text-align: center; padding: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: .5rem;">You are signed out</h1>
        <p style="margin: 0; opacity: .75;">Your session on this device has ended.</p>
        <p style="margin: 1.5rem 0 0;"><a href="/" style="color: #b74717;">Return to home</a></p>
    </main>
</body>
</html>
`;
