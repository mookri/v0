import { SignedTokenActorResolver, resolveActorTokenSecret } from '@navdhya/identity/src/application/token-actor';
import type { ActorResolver, RequestActor } from '@navdhya/identity/src/ports/actor-resolver';
import { SESSION_COOKIE } from '@/server/auth';
import { isFederated } from '@/server/federation';
import { federatedActorFromToken } from '@/server/session';

// Actor resolution for partner BFF routes via signed bearer tokens.
// Secret comes from server env via a LAZY getter: it is validated at
// request time (throwing SESSION_SIGNING_KEY_MISSING in production when
// unset) rather than at module load — several partner modules construct
// their resolver at module scope, which Next.js evaluates during build.
// Replaces the former staging header seam; no client ids are trusted.
//
// AUTH_MODE cutover (ADR-0020): the resolver is MODE-AWARE and reads
// the mode per resolve() — direct keeps today's HMAC actor tokens;
// federated verifies the issuer's access token locally against the
// issuer JWKS (server/oidc), because partner's routes act on the app's
// own stores with no upstream to re-judge the token (customer/web hand
// that judgment to Laravel on each proxied call). The two token
// families never cross: a 2-segment opaque actor token is not an
// issuer JWT, and an issuer JWT fails the HMAC verification — either
// way the result is null, never a partial actor.
class ModeAwareActorResolver implements ActorResolver {
  private readonly direct: SignedTokenActorResolver;

  constructor(now?: () => string) {
    this.direct = new SignedTokenActorResolver({
      secret: () => resolveActorTokenSecret(),
      now,
    });
  }

  async resolve(headers: Record<string, string | undefined>): Promise<RequestActor | null> {
    if (isFederated()) {
      const header = headers['authorization'];
      if (!header?.startsWith('Bearer ')) return null;
      const token = header.slice('Bearer '.length).trim();
      // JWTs are exactly three dot-separated segments; anything else
      // cannot be an issuer access token, so it fails closed here
      // instead of reaching signature verification.
      if (token.split('.').length !== 3) return null;
      return federatedActorFromToken(token);
    }
    return this.direct.resolve(headers);
  }
}

export function partnerActorResolver(now?: () => string): ActorResolver {
  return new ModeAwareActorResolver(now);
}

// Pre-configured instance for onboarding routes
export const onboardingActors = partnerActorResolver();

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
