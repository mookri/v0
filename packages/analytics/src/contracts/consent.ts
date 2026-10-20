// packages/analytics/src/contracts/consent.ts
//
// Consent architecture (plan §0.2 decision #7, architecture §5.2):
//   - Server-side consent record in Postgres is the single source of truth
//     (auditable, survives cookie clearing).
//   - The SDK attaches an HMAC consent token per batch; the edge verifies it.
//   - GTM and the first-party collector must read the SAME record.
//   - Purposes are separate: advertising consent is never inferred from
//     analytics consent.
//   - UNKNOWN is fail-closed: no analytics payload flows until explicit
//     GRANTED (strict-denial posture — recorded in CHANGELOG at B0).

export type ConsentPurpose = 'analytics' | 'ads';
export type ConsentState = 'UNKNOWN' | 'DENIED' | 'GRANTED';

export interface ConsentStatus {
  analytics: ConsentState;
  ads: ConsentState;
}

export type ConsentAction = 'grant' | 'deny' | 'withdraw' | 'reset';

/** Fail-closed initial state. */
export const UNKNOWN_CONSENT: ConsentStatus = Object.freeze({
  analytics: 'UNKNOWN',
  ads: 'UNKNOWN',
});

/**
 * Consent state machine: UNKNOWN → DENIED | GRANTED.
 * - grant: any state → GRANTED (explicit opt-in)
 * - deny: any state → DENIED (explicit opt-out)
 * - withdraw: GRANTED → DENIED (revocation is a recorded decision); others unchanged
 * - reset: any state → UNKNOWN (e.g. identity change)
 */
export function transitionConsent(current: ConsentState, action: ConsentAction): ConsentState {
  switch (action) {
    case 'grant':
      return 'GRANTED';
    case 'deny':
    case 'withdraw':
      return 'DENIED';
    case 'reset':
      return 'UNKNOWN';
  }
}

export function applyConsentAction(status: ConsentStatus, purpose: ConsentPurpose, action: ConsentAction): ConsentStatus {
  return { ...status, [purpose]: transitionConsent(status[purpose], action) };
}

/** Only analytics=GRANTED permits first-party analytics payloads. */
export function analyticsAllowed(status: ConsentStatus): boolean {
  return status.analytics === 'GRANTED';
}

// ─── Per-batch consent token (HMAC-SHA256, Web Crypto) ───────────────────────

export interface ConsentTokenPayload {
  v: 1;
  /** Subject: uidHash or null (anonymous session). */
  sub: string | null;
  analytics: boolean;
  ads: boolean;
  /** Issued-at, epoch seconds. */
  iat: number;
  /** Expiry, epoch seconds. */
  exp: number;
}

function base64UrlEncode(bytes: Uint8Array): string {
  let bin = '';
  for (const b of bytes) bin += String.fromCharCode(b);
  return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlDecode(s: string): Uint8Array {
  const b64 = s.replace(/-/g, '+').replace(/_/g, '/');
  const bin = atob(b64);
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return bytes;
}

async function hmacSha256(secret: string, data: string): Promise<Uint8Array> {
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(data));
  return new Uint8Array(sig);
}

/** Issue a short-lived consent token for the SDK to attach per batch. */
export async function issueConsentToken(
  payload: ConsentTokenPayload,
  secret: string,
): Promise<string> {
  if (payload.v !== 1) throw new Error('unsupported consent token version');
  if (!Number.isFinite(payload.iat) || !Number.isFinite(payload.exp) || payload.exp <= payload.iat) {
    throw new Error('invalid consent token timestamps');
  }
  const body = base64UrlEncode(new TextEncoder().encode(JSON.stringify(payload)));
  const sig = base64UrlEncode(await hmacSha256(secret, body));
  return `${body}.${sig}`;
}

/**
 * Verify a consent token. Returns the payload, or null for: malformed token,
 * bad signature, wrong secret, expired token, or unsupported version.
 * Fail-closed: null means "no consent evidence" → do not collect.
 */
export async function verifyConsentToken(
  token: string,
  secret: string,
  nowSec: number = Math.floor(Date.now() / 1000),
): Promise<ConsentTokenPayload | null> {
  const parts = token.split('.');
  if (parts.length !== 2) return null;
  const [body, sig] = parts as [string, string];
  try {
    const expected = base64UrlEncode(await hmacSha256(secret, body));
    if (sig.length !== expected.length) return null;
    let mismatch = 0;
    for (let i = 0; i < sig.length; i++) mismatch |= sig.charCodeAt(i) ^ expected.charCodeAt(i);
    if (mismatch !== 0) return null;
    const payload = JSON.parse(new TextDecoder().decode(base64UrlDecode(body))) as ConsentTokenPayload;
    if (payload.v !== 1 || typeof payload.analytics !== 'boolean' || typeof payload.ads !== 'boolean') return null;
    if (nowSec >= payload.exp) return null;
    return payload;
  } catch {
    return null;
  }
}
