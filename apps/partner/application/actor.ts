import { SignedTokenActorResolver, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { SESSION_COOKIE } from '@/server/auth';

// Actor resolution for partner BFF routes via signed bearer tokens.
// Secret comes from server env; the insecure fallback is dev-only
// (production must set SESSION_SIGNING_KEY — see packages/config).
// Replaces the former staging header seam; no client ids are trusted.
export function partnerActorResolver(now?: () => string): SignedTokenActorResolver {
  return new SignedTokenActorResolver({
    secret: process.env['SESSION_SIGNING_KEY'] ?? INSECURE_DEV_SECRET,
    now,
  });
}

// Browser sessions: the login route issues SESSION_COOKIE (httpOnly);
// routes accept it as a fallback when no Authorization header is present.
// Cookie theft mitigations (rotation, binding) land with the session
// hardening pass — the token inside carries its own expiry meanwhile.
export function readSessionCookie(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const [name, ...rest] = part.trim().split('=');
    if (name === SESSION_COOKIE && rest.length > 0) return rest.join('=');
  }
  return null;
}

export function bearerOrSessionCookie(headers: Headers): string | null {
  const header = headers.get('authorization');
  if (header?.startsWith('Bearer ')) return header.slice('Bearer '.length).trim();
  return readSessionCookie(headers.get('cookie'));
}

export function headersToRecord(headers: Headers): Record<string, string | undefined> {
  const out: Record<string, string | undefined> = {};
  headers.forEach((value, key) => {
    out[key.toLowerCase()] = value;
  });
  return out;
}

// Browser fallback: inject Authorization from the session cookie when the
// header is absent, so cookie-authed pages and forms reach the same
// bearer-only resolvers as API clients.
export function withCookieFallback(req: Request): Record<string, string | undefined> {
  const record = headersToRecord(req.headers);
  if (!record['authorization']) {
    const token = readSessionCookie(req.headers.get('cookie'));
    if (token) record['authorization'] = `Bearer ${token}`;
  }
  return record;
}
