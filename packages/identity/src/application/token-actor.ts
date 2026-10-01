import { createHmac, timingSafeEqual } from 'node:crypto';
import { ActorResolver, RequestActor } from '@navdhya/identity/src/ports/actor-resolver';

// Dev-only fallback secret. Production MUST set SESSION_SIGNING_KEY;
// anything minted with this value is untrusted by definition.
export const INSECURE_DEV_SECRET = 'dev-only-insecure-actor-secret';

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
  secret: string;
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

  async resolve(headers: Record<string, string | undefined>): Promise<RequestActor | null> {
    const header = headers['authorization'];
    if (!header || !header.startsWith('Bearer ')) return null;
    try {
      return verifyActorToken(this.config.secret, header.slice('Bearer '.length).trim(), this.now());
    } catch {
      return null;
    }
  }
}
