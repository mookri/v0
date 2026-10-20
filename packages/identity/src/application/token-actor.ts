import { createHmac, timingSafeEqual } from 'node:crypto';
import { ActorResolver, RequestActor } from '@navdhya/identity/src/ports/actor-resolver';

// Dev-only fallback secret. Production MUST set SESSION_SIGNING_KEY;
// anything minted with this value is untrusted by definition.
export const INSECURE_DEV_SECRET = 'dev-only-insecure-actor-secret';

// Named error code for the production guard below (house style: stable
// codes the operator can grep for, not prose).
export const SESSION_SIGNING_KEY_MISSING = 'SESSION_SIGNING_KEY_MISSING';

/**
 * Resolve the HMAC key that signs BFF actor tokens.
 *
 * An actor token is a bearer credential: possession of the key lets you
 * mint a valid token for ANY (userId, sessionId, roles) tuple, because
 * mintActorToken does no server-side state check. Falling back to the
 * committed INSECURE_DEV_SECRET in production therefore hands blanket
 * impersonation to anyone who has read this repository — it is a total
 * authentication bypass, not a weak default.
 *
 * Mirrors packages/security/src/csrf.ts, which refuses to issue CSRF
 * tokens when CSRF_SECRET is unset in production, so the two secrets in
 * this codebase fail the same way.
 *
 * Note this is deliberately NOT evaluated at module load: Next.js imports
 * route modules during `next build`, where NODE_ENV is already
 * 'production' but no runtime secrets exist. Resolving lazily keeps the
 * guard on the runtime path (where it matters) instead of failing the
 * build — and avoids ever baking a build-time placeholder into a bundle.
 */
export function resolveActorTokenSecret(
  env: Record<string, string | undefined> = process.env,
): string {
  const explicit = env['SESSION_SIGNING_KEY'];
  if (explicit) return explicit;

  if (env['NODE_ENV'] === 'production') {
    throw new Error(
      `${SESSION_SIGNING_KEY_MISSING}: SESSION_SIGNING_KEY is not set in production; ` +
        'refusing to mint or verify BFF actor tokens with the dev fallback.',
    );
  }

  return INSECURE_DEV_SECRET;
}

export interface ActorTokenClaims {
  userId: string;
  sessionId: string;
  roles: string[];
  market?: string;
  orgId?: string;
  expiresAtIso: string;
}

function b64urlEncode(json: string): string {
  return Buffer.from(json, 'utf8').toString('base64url');
}

function sign(secret: string, payload: string): string {
  return createHmac('sha256', secret).update(payload, 'utf8').digest('base64url');
}

// Opaque bearer token: base64url(payload).base64url(hmac). Payload fields
// must not contain dots; roles are comma-separated within one segment.
export function mintActorToken(
  secret: string,
  actor: Omit<RequestActor, 'sessionId'> & { sessionId: string },
  nowIso: string,
  ttlSeconds: number,
): string {
  for (const field of [actor.userId, actor.sessionId, ...(actor.market ? [actor.market] : []), ...(actor.orgId ? [actor.orgId] : []), ...actor.roles]) {
    if (!field || field.includes('.') || field.includes(',')) throw new Error('ACTOR_FIELD_INVALID');
  }
  const expiresAtIso = new Date(Date.parse(nowIso) + ttlSeconds * 1000).toISOString();
  const payload = b64urlEncode(
    JSON.stringify({
      v: 1,
      u: actor.userId,
      s: actor.sessionId,
      r: actor.roles.join(','),
      m: actor.market ?? null,
      o: actor.orgId ?? null,
      e: expiresAtIso,
    }),
  );
  return `${payload}.${sign(secret, payload)}`;
}

export function verifyActorToken(secret: string, token: string, nowIso: string): RequestActor {
  const [payload, signature] = token.split('.');
  if (!payload || !signature) throw new Error('ACTOR_TOKEN_MALFORMED');
  const expected = sign(secret, payload);
  const a = Buffer.from(expected, 'utf8');
  const b = Buffer.from(signature, 'utf8');
  if (a.length !== b.length || !timingSafeEqual(a, b)) throw new Error('ACTOR_TOKEN_INVALID');
  let claims: { v: number; u: string; s: string; r: string; m: string | null; o: string | null; e: string };
  try {
    claims = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as typeof claims;
  } catch {
    throw new Error('ACTOR_TOKEN_MALFORMED');
  }
  if (claims.v !== 1 || !claims.u || !claims.s || !claims.e) throw new Error('ACTOR_TOKEN_MALFORMED');
  if (claims.e <= nowIso) throw new Error('ACTOR_TOKEN_EXPIRED');
  return {
    userId: claims.u,
    sessionId: claims.s,
    roles: claims.r ? claims.r.split(',') : [],
    ...(claims.m ? { market: claims.m } : {}),
    ...(claims.o ? { orgId: claims.o } : {}),
  };
}

export interface TokenResolverConfig {
  /**
   * HMAC key, or a getter resolved on every `resolve()` call.
   *
   * The getter form exists so callers can defer environment validation to
   * request time: several partner BFF modules construct their resolver at
   * module scope, which Next.js evaluates during `next build`. Resolving
   * the secret per call also means an operator can rotate SESSION_SIGNING_KEY
   * without a redeploy of the module graph.
   */
  secret: string | (() => string);
  now?: () => string;
}

// Production-grade ActorResolver: Authorization: Bearer <actor token>.
// No session-store lookup (stateless); revocation needs short TTLs or a
// blocklist — tracked when rotation lands server-side.
export class SignedTokenActorResolver implements ActorResolver {
  private readonly now: () => string;

  constructor(private readonly config: TokenResolverConfig) {
    if (!config.secret) throw new Error('ACTOR_SECRET_MISSING');
    this.now = config.now ?? (() => new Date().toISOString());
  }

  /** Resolved outside the try/catch below: a missing production key must
   *  surface as a loud 500, not be swallowed into a silent 401. */
  private secret(): string {
    return typeof this.config.secret === 'function' ? this.config.secret() : this.config.secret;
  }

  async resolve(headers: Record<string, string | undefined>): Promise<RequestActor | null> {
    const header = headers['authorization'];
    if (!header || !header.startsWith('Bearer ')) return null;
    const secret = this.secret();
    try {
      return verifyActorToken(secret, header.slice('Bearer '.length).trim(), this.now());
    } catch {
      return null;
    }
  }
}
