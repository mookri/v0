import { createHash, randomUUID } from 'node:crypto';

import { TIMEOUT_MS } from '@/application/api-policy';
import {
  buildRefreshCookie,
  buildRotationPendingCookie,
  buildSessionCookie,
  clearRefreshCookie,
  clearRotationPendingCookie,
  clearSessionCookie,
  customerRefreshCookieName,
  customerTokenCookieName,
  readCustomerRefreshToken,
  readCustomerRotationPending,
  readCustomerToken,
  sessionCookieNames,
} from '@/server/backend-session';
import { dpopProof } from '@/server/dpop';
import { federationConfig, isFederated } from '@/server/federation';

// ---------------------------------------------------------------------------
// Federated session continuity (A-SEC-004-11, D4's BFF half): the BFF
// holds the issuer's refresh token in its own HttpOnly cookie (see
// backend-session.ts) and keeps the session alive across the access
// token's 900 s expiry — rotation runs HERE, server-side, on the request
// that would otherwise be the first to fail, so the browser never bounces
// through an interactive login while the family is alive.
//
// The issuer side is D4 as built (ADR-0020): rotating refresh tokens,
// atomic rotation, sid lineage on every successor (A-SEC-004-10, so a
// rotated family stays inside SessionTerminator's kill net) and
// Idempotency-Key recovery. This module is its client half:
//
//  - Trigger: lazy, at the point of use. The access token's `exp` is
//    decoded signature-free (upstream remains the sole judge of validity,
//    I18 — same posture as resolveCustomerActor); rotation fires when the
//    token is missing, unparseable or inside EXPIRY_SKEW_MS of expiry,
//    and whenever an idle browser comes back with only the refresh
//    cookie (the access cookie's Max-Age expires with the token).
//
//  - Single-flight + in-process replay: refresh tokens are ONE-TIME use —
//    two rotations of the same token is textbook reuse, and reuse
//    detection kills the user's whole lineage for this client (SEC-003
//    §3.2). Concurrent requests with the same cookie share one in-flight
//    rotation; requests arriving just AFTER a rotation replay the cached
//    outcome (same cookies re-issued) instead of re-pushing the consumed
//    token at the issuer.
//
//  - Idempotency parking: every exchange carries an Idempotency-Key. A
//    network-level failure retries ONCE with the SAME key (recovery
//    replays a committed rotation; a fresh attempt starts an
//    uncommitted one), and a failure that survives to the response parks
//    the key in a short-lived cookie so the NEXT request retries with it
//    rather than a new key over a possibly-consumed token.
//
//  - DPoP: the proof rides from the same per-instance key that bound the
//    family at the code exchange (A-SEC-004-9, RFC 9449 §5) — a
//    restarted instance whose key was lost gets invalid_dpop_proof and
//    the session re-authenticates, by design.
//
// Multi-instance caveat (production extension, same class as the
// per-instance DPoP key): the single-flight/replay state is in-process.
// Two instances rotating the same token concurrently would present
// different Idempotency-Keys — the loser is reuse and the family
// re-authenticates. Deployments need sticky routing or a shared
// rotation store; nothing security-relevant weakens either way (reuse
// detection still fires — the failure mode is a re-login).
// ---------------------------------------------------------------------------

/** Rotate before the access token expires: a request that checks in with
 *  less than this margin would race its own upstream call against the
 *  token's expiry (and against the revoke-the-old-token step of
 *  rotation). 60 s dwarfs BFF→issuer→upstream latency. */
const EXPIRY_SKEW_MS = 60_000;

/** Replay window for a just-completed rotation, keyed by the consumed
 *  token: late requests that were issued with the pre-rotation cookies
 *  (browser hasn't applied Set-Cookie yet) replay the outcome instead of
 *  starting a second rotation. */
const RECENT_REPLAY_MS = 30_000;

/** Initial attempt + one same-key retry on network failure. */
const MAX_ROTATION_ATTEMPTS = 2;

type RotationResult =
  | { status: 'rotated'; accessToken: string; refreshToken: string; expiresIn: number; key: string }
  | { status: 'fatal'; key: string }
  | { status: 'retry-later'; key: string };

export interface FreshSession {
  /** fresh          — nothing to do (live token, no refresh cookie, or not federated)
   *  rotated        — a new token pair replaced an expiring/absent one
   *  resurrected    — same, but the request arrived with NO access cookie
   *  unauthenticated— the family is dead; the session cookies were cleared
   *  retry-later    — the issuer could not be reached; cookies untouched, key parked */
  status: 'fresh' | 'rotated' | 'resurrected' | 'unauthenticated' | 'retry-later';
  /** New cookie header for the downstream request — null when unchanged.
   *  Handlers (and the anonymous gate) then never present a consumed token. */
  rewrittenCookieHeader: string | null;
  /** Set-Cookie values to append to this response. */
  setCookies: string[];
}

const inflight = new Map<string, Promise<RotationResult>>();
const recent = new Map<string, { result: RotationResult; until: number }>();

export async function ensureFreshSession(cookieHeader: string | null): Promise<FreshSession> {
  if (!isFederated()) {
    return { status: 'fresh', rewrittenCookieHeader: null, setCookies: [] };
  }

  const refreshToken = readCustomerRefreshToken(cookieHeader);
  const accessToken = readCustomerToken(cookieHeader);
  if (!refreshToken) {
    // Without a refresh cookie the session is exactly what it was before
    // this slice: the access token's lifetime, judged at its use.
    return { status: 'fresh', rewrittenCookieHeader: null, setCookies: [] };
  }

  const expiry = accessToken ? accessTokenExpiryMs(accessToken) : null;
  if (expiry !== null && expiry > Date.now() + EXPIRY_SKEW_MS) {
    return { status: 'fresh', rewrittenCookieHeader: null, setCookies: [] };
  }

  const result = await rotate(refreshToken, readCustomerRotationPending(cookieHeader));

  if (result.status === 'rotated') {
    const maxAge = Math.max(60, Math.floor(result.expiresIn));
    return {
      status: accessToken ? 'rotated' : 'resurrected',
      rewrittenCookieHeader: mergeSessionCookies(cookieHeader, [
        [customerTokenCookieName(), result.accessToken],
        [customerRefreshCookieName(), result.refreshToken],
      ]),
      setCookies: [
        buildSessionCookie(result.accessToken, maxAge),
        buildRefreshCookie(result.refreshToken),
        clearRotationPendingCookie(),
      ],
    };
  }

  if (result.status === 'fatal') {
    // invalid_grant (lineage revoked/consumed — e.g. the OP logged the
    // session out), invalid_dpop_proof (this instance lost its key), a
    // refused client — the BFF must stop presenting the family. Clearing
    // both cookies lands the browser on the normal re-auth path.
    return {
      status: 'unauthenticated',
      rewrittenCookieHeader: null,
      setCookies: [clearSessionCookie(), clearRefreshCookie(), clearRotationPendingCookie()],
    };
  }

  // Transient failure (network, 5xx): keep the cookies as they are and
  // park the key — the next request is the safe retry point.
  return {
    status: 'retry-later',
    rewrittenCookieHeader: null,
    setCookies: [buildRotationPendingCookie(result.key)],
  };
}

async function rotate(refreshToken: string, pendingKey: string | null): Promise<RotationResult> {
  const hash = createHash('sha256').update(refreshToken).digest('base64url');

  const replayed = recent.get(hash);
  if (replayed && replayed.until > Date.now()) return replayed.result;

  const shared = inflight.get(hash);
  if (shared) return shared;

  const run = requestRotation(refreshToken, pendingKey);
  inflight.set(hash, run);
  try {
    const result = await run;
    if (result.status !== 'retry-later') {
      recent.set(hash, { result, until: Date.now() + RECENT_REPLAY_MS });
    }
    return result;
  } finally {
    inflight.delete(hash);
  }
}

async function requestRotation(refreshToken: string, pendingKey: string | null): Promise<RotationResult> {
  const config = federationConfig();
  const tokenUri = `${config.issuer}/oauth/token`;
  // A parked key from an earlier lost-response attempt is REUSED: the
  // issuer then replays a rotation it already committed, instead of
  // seeing the same consumed token arrive under a fresh key (reuse ->
  // lineage kill). The key is not a credential — recovery still demands
  // client authentication, this same refresh token and an identical
  // fingerprint (RecoverableRefreshTokenGrant).
  const key = pendingKey ?? randomUUID();

  let res: Response | null = null;
  for (let attempt = 0; attempt < MAX_ROTATION_ATTEMPTS && res === null; attempt++) {
    try {
      res = await fetch(tokenUri, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json',
          // Same proof key that bound the family at the code exchange
          // (A-SEC-004-9): a bound family refuses a refresh without it.
          DPoP: dpopProof('POST', tokenUri),
          'Idempotency-Key': key,
        },
        // No `scope` parameter on purpose: league defaults to the stored
        // scope, and the issuer fingerprints client_id + refresh_token +
        // scope — a stable body keeps every retry of this exchange
        // fingerprint-identical to its original.
        body: new URLSearchParams({
          grant_type: 'refresh_token',
          refresh_token: refreshToken,
          client_id: config.clientId,
          client_secret: config.clientSecret,
        }).toString(),
        signal: AbortSignal.timeout(TIMEOUT_MS.normal),
      });
    } catch {
      // Network-level failure: the issuer may or may not have committed
      // the rotation. Retry ONCE with the same key — recovery replays a
      // committed rotation, a fresh attempt starts an uncommitted one.
      res = null;
    }
  }
  if (res === null) {
    console.error('[session-refresh] rotation deferred: transport failure (both attempts)');
    return { status: 'retry-later', key };
  }

  if (res.status === 200) {
    const body = (await res.json().catch(() => null)) as
      | { access_token?: unknown; refresh_token?: unknown; expires_in?: unknown }
      | null;
    if (
      typeof body?.access_token === 'string' &&
      body.access_token !== '' &&
      typeof body?.refresh_token === 'string' &&
      body.refresh_token !== ''
    ) {
      return {
        status: 'rotated',
        accessToken: body.access_token,
        refreshToken: body.refresh_token,
        expiresIn: typeof body.expires_in === 'number' ? body.expires_in : 900,
        key,
      };
    }
    // A 200 without a full rotated pair is not a rotation this BFF can
    // hold: the presented token was consumed either way, so keeping any
    // part of this response would replay a dead credential next time.
    return { status: 'fatal', key };
  }

  if (res.status >= 400 && res.status < 500) {
    // invalid_grant (lineage revoked/consumed — e.g. the OP logged the
    // session out), invalid_dpop_proof (this instance's key no longer
    // matches the family's binding), a refused client — the BFF must
    // stop presenting the family. Clearing both cookies lands the
    // browser on the normal re-auth path. Code-only observability: WHY
    // families die is operationally essential and carries no token
    // material — tokens, keys and proofs are never logged.
    const refused = (await res.json().catch(() => null)) as { error?: unknown } | null;
    console.error(
      `[session-refresh] rotation refused: ${res.status} ${
        typeof refused?.error === 'string' ? refused.error : 'unknown_error'
      }`,
    );
    return { status: 'fatal', key };
  }

  // 5xx: the issuer answered but cannot promise what happened to the
  // token. Leave the cookies alone; the parked key makes the next
  // request the safe retry point.
  console.error(`[session-refresh] rotation deferred: issuer status ${res.status}`);
  return { status: 'retry-later', key };
}

/** Signature-free `exp` decode (base64url payload, seconds) — routing
 *  context only, exactly like resolveCustomerActor's federated branch:
 *  validity is judged upstream on every proxied call (I18). */
function accessTokenExpiryMs(token: string): number | null {
  const parts = token.split('.');
  if (parts.length !== 3) return null;
  try {
    const payload = JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8')) as {
      exp?: unknown;
    };
    return typeof payload.exp === 'number' ? payload.exp * 1000 : null;
  } catch {
    return null;
  }
}

/**
 * Rebuild a request cookie header with `replacements` installed and every
 * session-family cookie (token/refresh/pending, dev + prod names) stripped
 * — the downstream request then carries exactly the pair the rotation just
 * minted, with foreign cookies (XSRF, flow leftovers) preserved.
 */
export function mergeSessionCookies(
  originalHeader: string | null,
  replacements: Array<[string, string]>,
): string {
  const owned = new Set(sessionCookieNames());
  const kept: string[] = [];
  if (originalHeader) {
    for (const part of originalHeader.split(';')) {
      const eq = part.indexOf('=');
      if (eq === -1) continue;
      const name = part.slice(0, eq).trim();
      if (owned.has(name)) continue;
      kept.push(part.trim());
    }
  }
  for (const [name, value] of replacements) {
    kept.push(`${name}=${encodeURIComponent(value)}`);
  }
  return kept.join('; ');
}
