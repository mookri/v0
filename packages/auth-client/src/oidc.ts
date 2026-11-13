import {
  createPublicKey,
  createVerify,
  timingSafeEqual,
  type JsonWebKey,
  type KeyObject,
} from 'node:crypto';

export interface IdTokenClaims {
  sub: string;
  iss: string;
  aud: string | string[];
  exp: number;
  iat: number;
  nonce?: string;
  auth_time?: number;
  amr?: string[];
  acr?: string;
  sid?: string;
}

export interface AccessTokenClaims {
  sub: string;
  aud?: string | string[];
  exp: number;
  iat: number;
  jti?: string;
  scope?: string;
  sid?: string;
}

export interface LogoutTokenClaims {
  iss: string;
  aud: string | string[];
  sub: string;
  sid: string;
  iat: number;
  jti: string;
  events: Record<string, unknown>;
}

const SKEW_SECONDS = 60;
const JWKS_TTL_MS = 5 * 60 * 1000;
const LOGOUT_IAT_MAX_AGE_SECONDS = 300;
const LOGOUT_IAT_FUTURE_SKEW_SECONDS = 60;
const BACKCHANNEL_LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

interface JwkKey {
  kty?: string;
  kid?: string;
  use?: string;
  alg?: string;
  n?: string;
  e?: string;
}

interface CachedJwks {
  fetchedAt: number;
  keys: Map<string, KeyObject>;
}

let jwksCache: CachedJwks | null = null;

export function clearOidcJwksCache(): void {
  jwksCache = null;
}

async function fetchJwks(issuer: string): Promise<CachedJwks> {
  const response = await fetch(`${issuer}/.well-known/jwks.json`, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) {
    throw new Error('JWKS unavailable');
  }
  const body = (await response.json()) as { keys?: JwkKey[] };
  const keys = new Map<string, KeyObject>();
  for (const jwk of body.keys ?? []) {
    if (
      jwk.kty !== 'RSA' ||
      typeof jwk.kid !== 'string' ||
      typeof jwk.n !== 'string' ||
      typeof jwk.e !== 'string'
    ) {
      continue;
    }
    if (jwk.use && jwk.use !== 'sig') continue;
    try {
      keys.set(jwk.kid, createPublicKey({ key: jwk as unknown as JsonWebKey, format: 'jwk' }));
    } catch {
      // Skip malformed individual keys while retaining valid siblings.
    }
  }
  return { fetchedAt: Date.now(), keys };
}

async function freshJwks(issuer: string): Promise<CachedJwks> {
  if (!jwksCache || Date.now() - jwksCache.fetchedAt > JWKS_TTL_MS) {
    jwksCache = await fetchJwks(issuer);
  }
  return jwksCache;
}

async function resolveKey(issuer: string, kid: string): Promise<KeyObject | null> {
  const cached = await freshJwks(issuer);
  const hit = cached.keys.get(kid);
  if (hit) return hit;

  jwksCache = await fetchJwks(issuer);
  return jwksCache.keys.get(kid) ?? null;
}

async function resolveKeySet(issuer: string): Promise<KeyObject[]> {
  return [...(await freshJwks(issuer)).keys.values()];
}

function safeEqual(a: string, b: string): boolean {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) return false;
  return timingSafeEqual(bufA, bufB);
}

function decodeSegment(segment: string): unknown {
  return JSON.parse(Buffer.from(segment, 'base64url').toString('utf8'));
}

function verifySignature(segments: string[], keys: KeyObject[]): boolean {
  let signature: Buffer;
  try {
    signature = Buffer.from(segments[2], 'base64url');
  } catch {
    return false;
  }
  return keys.some((key) => {
    const verifier = createVerify('RSA-SHA256');
    verifier.update(`${segments[0]}.${segments[1]}`);
    verifier.end();
    return verifier.verify(key, signature);
  });
}

export function assertAudienceMatches(aud: string | string[] | undefined, expectedClientId: string): boolean {
  if (aud === undefined) return false;
  const audiences = Array.isArray(aud) ? aud : [aud];
  return audiences.some((item) => typeof item === 'string' && safeEqual(item, expectedClientId));
}

export async function verifyIdToken(
  idToken: string,
  issuer: string,
  clientId: string,
  expectedNonce: string,
): Promise<IdTokenClaims | null> {
  const segments = idToken.split('.');
  if (segments.length !== 3) return null;

  let header: { alg?: string; kid?: string };
  let claims: IdTokenClaims;
  try {
    header = decodeSegment(segments[0]) as { alg?: string; kid?: string };
    claims = decodeSegment(segments[1]) as IdTokenClaims;
  } catch {
    return null;
  }

  if (header.alg !== 'RS256' || typeof header.kid !== 'string' || header.kid === '') return null;

  const key = await resolveKey(issuer, header.kid);
  if (!key || !verifySignature(segments, [key])) return null;

  if (typeof claims.iss !== 'string' || !safeEqual(claims.iss, issuer)) return null;
  if (!assertAudienceMatches(claims.aud, clientId)) return null;
  if (typeof claims.exp !== 'number' || typeof claims.iat !== 'number') return null;

  const now = Math.floor(Date.now() / 1000);
  if (claims.exp < now - SKEW_SECONDS) return null;
  if (claims.iat > now + SKEW_SECONDS) return null;
  if (typeof claims.nonce !== 'string' || !safeEqual(claims.nonce, expectedNonce)) return null;

  return claims;
}

export async function verifyAccessToken(
  accessToken: string,
  issuer: string,
  clientId: string,
): Promise<AccessTokenClaims | null> {
  const segments = accessToken.split('.');
  if (segments.length !== 3) return null;

  let header: { alg?: string; kid?: string };
  let claims: AccessTokenClaims;
  try {
    header = decodeSegment(segments[0]) as { alg?: string; kid?: string };
    claims = decodeSegment(segments[1]) as AccessTokenClaims;
  } catch {
    return null;
  }

  if (header.alg !== 'RS256') return null;
  const keys =
    typeof header.kid === 'string' && header.kid !== ''
      ? [await resolveKey(issuer, header.kid)].filter((k): k is KeyObject => k !== null)
      : await resolveKeySet(issuer);
  if (keys.length === 0 || !verifySignature(segments, keys)) return null;

  if (typeof claims.sub !== 'string' || claims.sub === '') return null;
  if (!assertAudienceMatches(claims.aud, clientId)) return null;
  if (typeof claims.exp !== 'number' || typeof claims.iat !== 'number') return null;

  const now = Math.floor(Date.now() / 1000);
  if (claims.exp < now - SKEW_SECONDS) return null;
  if (claims.iat > now + SKEW_SECONDS) return null;

  return claims;
}

export async function verifyLogoutToken(
  logoutToken: string,
  issuer: string,
  clientId: string,
): Promise<LogoutTokenClaims | null> {
  const segments = logoutToken.split('.');
  if (segments.length !== 3) return null;

  let header: { alg?: string; kid?: string; typ?: string };
  let claims: LogoutTokenClaims;
  try {
    header = decodeSegment(segments[0]) as { alg?: string; kid?: string; typ?: string };
    claims = decodeSegment(segments[1]) as LogoutTokenClaims;
  } catch {
    return null;
  }

  if (header.alg !== 'RS256' || typeof header.kid !== 'string' || header.kid === '') return null;
  if (header.typ !== 'logout+jwt' && header.typ !== 'logout') return null;

  const key = await resolveKey(issuer, header.kid);
  if (!key || !verifySignature(segments, [key])) return null;

  if (typeof claims.iss !== 'string' || !safeEqual(claims.iss, issuer)) return null;
  if (!assertAudienceMatches(claims.aud, clientId)) return null;

  if (
    typeof claims.events !== 'object' ||
    claims.events === null ||
    !(BACKCHANNEL_LOGOUT_EVENT in claims.events)
  ) {
    return null;
  }

  if (typeof claims.iat !== 'number') return null;
  const now = Math.floor(Date.now() / 1000);
  if (claims.iat > now + LOGOUT_IAT_FUTURE_SKEW_SECONDS) return null;
  if (claims.iat < now - LOGOUT_IAT_MAX_AGE_SECONDS) return null;

  if (typeof claims.sid !== 'string' || claims.sid === '') return null;
  if (typeof claims.sub !== 'string' || claims.sub === '') return null;
  if (typeof claims.jti !== 'string' || claims.jti === '') return null;

  return claims;
}
