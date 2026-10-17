import { createPublicKey, createVerify, timingSafeEqual, type JsonWebKey, type KeyObject } from 'node:crypto';

/**
 * OIDC id_token verification (9a, ADR-0021 D3) — customer BFF.
 *
 * The token endpoint is the only source of an id_token (the issuer
 * never redirects one), and this module is the only consumer: the BFF
 * verifies it before the session is established, so a forged, replayed,
 * cross-audience or issuer-mismatched token can never become a login.
 *
 * Verification fails closed on every axis, mirroring the Laravel
 * client_assertion posture:
 *
 *  - **Signature:** RS256 only, pinned (`alg` from the header is
 *    checked, never trusted); keys resolve from the issuer's JWKS by
 *    `kid`, fetched server-side (the JWKS URL never reaches the
 *    browser). A `kid` miss triggers exactly one refetch (rotation
 *    overlap), then fails.
 *  - **Claims:** `iss` equals the configured issuer byte for byte
 *    (timing-safe), `aud` contains our client id, `exp`/`iat` are
 *    bounded (60s clock-skew leeway), and `nonce` equals the value
 *    minted into the flow cookie — the replay binding (OIDC Core).
 *  - **No library:** plain node:crypto — the apps keep their dependency
 *    surface small, and RS256 verification is one createVerify call.
 *
 * (Intentionally parallel to the web app's server/oidc.ts: the apps share
 * an env contract but no server modules.)
 */

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

/** Clock-skew leeway in seconds, mirroring the Laravel default. */
const SKEW_SECONDS = 60;

/** JWKS cache window: long enough to absorb rotation, short enough to retire. */
const JWKS_TTL_MS = 5 * 60 * 1000;

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

/** Test hook: drop the cached JWKS so the next verify refetches. */
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
    // Only RSA signing keys are accepted; the kid is the RFC 7638
    // thumbprint the issuer publishes, and the sole lookup key.
    if (jwk.kty !== 'RSA' || typeof jwk.kid !== 'string' || typeof jwk.n !== 'string' || typeof jwk.e !== 'string') {
      continue;
    }
    if (jwk.use && jwk.use !== 'sig') continue;
    try {
      keys.set(jwk.kid, createPublicKey({ key: jwk as unknown as JsonWebKey, format: 'jwk' }));
    } catch {
      // An unreadable key is skipped, never fatal for the others.
    }
  }
  return { fetchedAt: Date.now(), keys };
}

async function resolveKey(issuer: string, kid: string): Promise<KeyObject | null> {
  if (!jwksCache || Date.now() - jwksCache.fetchedAt > JWKS_TTL_MS) {
    jwksCache = await fetchJwks(issuer);
  }
  const cached = jwksCache.keys.get(kid);
  if (cached) return cached;
  // kid miss: the issuer may have rotated since our last fetch — one
  // refetch, then fail (bounded: an attacker cannot force a fetch
  // storm through a garbage kid).
  jwksCache = await fetchJwks(issuer);
  return jwksCache.keys.get(kid) ?? null;
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

/**
 * Verify an id_token from the token endpoint. Returns the claims on
 * success, null on ANY verification failure — callers treat null as a
 * failed sign-in, never a partial login.
 */
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

  // RS256 pinned: the algorithm is checked, never taken on faith
  // (alg=none and HMAC confusions die here).
  if (header.alg !== 'RS256' || typeof header.kid !== 'string' || header.kid === '') return null;

  const key = await resolveKey(issuer, header.kid);
  if (!key) return null;

  const verifier = createVerify('RSA-SHA256');
  verifier.update(`${segments[0]}.${segments[1]}`);
  verifier.end();
  let signature: Buffer;
  try {
    signature = Buffer.from(segments[2], 'base64url');
  } catch {
    return null;
  }
  if (!verifier.verify(key, signature)) return null;

  // Claims: byte-exact issuer, our client as audience, bounded times,
  // exact nonce binding.
  if (typeof claims.iss !== 'string' || !safeEqual(claims.iss, issuer)) return null;

  const audiences = Array.isArray(claims.aud) ? claims.aud : [claims.aud];
  if (!audiences.some((aud) => typeof aud === 'string' && safeEqual(aud, clientId))) return null;

  if (typeof claims.exp !== 'number' || typeof claims.iat !== 'number') return null;
  const now = Math.floor(Date.now() / 1000);
  if (claims.exp < now - SKEW_SECONDS) return null;
  if (claims.iat > now + SKEW_SECONDS) return null;

  if (typeof claims.nonce !== 'string' || !safeEqual(claims.nonce, expectedNonce)) return null;

  return claims;
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

/** Rejection windows for a logout_token's iat — each delivery attempt re-mints. */
const LOGOUT_IAT_MAX_AGE_SECONDS = 300;
const LOGOUT_IAT_FUTURE_SKEW_SECONDS = 60;

const BACKCHANNEL_LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

/**
 * Verify an OIDC back-channel logout_token (A-SEC-004-6). Same
 * fail-closed posture as verifyIdToken, with the logout-token axes:
 *
 *  - RS256 pinned, kid-resolved against the issuer JWKS (the issuer's
 *    logout mint always carries the RFC 7638 kid);
 *  - `iss` byte-exact, `aud` contains our client id;
 *  - header `typ` is `logout+jwt` (the issuer's spelling; `logout`
 *    also accepted — the spec's earlier spelling — signature and the
 *    claims below still bind either way);
 *  - `events` carries the back-channel-logout event: an id_token or
 *    access token can never satisfy this shape, so a replayed token
 *    of another kind dies here even before the typ check disagrees;
 *  - `iat` is fresh (5 minutes) and not meaningfully future — every
 *    delivery attempt re-mints, so a delayed retry never widens this;
 *  - `sid`, `sub` and `jti` are non-empty strings (the jti is the
 *    receiver's replay key).
 *
 * Returns the claims on success, null on ANY verification failure.
 */
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
  if (!key) return null;

  const verifier = createVerify('RSA-SHA256');
  verifier.update(`${segments[0]}.${segments[1]}`);
  verifier.end();
  let signature: Buffer;
  try {
    signature = Buffer.from(segments[2], 'base64url');
  } catch {
    return null;
  }
  if (!verifier.verify(key, signature)) return null;

  if (typeof claims.iss !== 'string' || !safeEqual(claims.iss, issuer)) return null;

  const audiences = Array.isArray(claims.aud) ? claims.aud : [claims.aud];
  if (!audiences.some((aud) => typeof aud === 'string' && safeEqual(aud, clientId))) return null;

  if (typeof claims.events !== 'object' || claims.events === null || !(BACKCHANNEL_LOGOUT_EVENT in claims.events)) {
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
