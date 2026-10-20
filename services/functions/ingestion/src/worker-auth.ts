// Worker → ingestion authentication (plan decision #6): HMAC-SHA256 with
// timestamp + nonce replay window, verified HERE at the ingestion service.
// GCP Workload Identity Federation is unreachable from Cloudflare Workers —
// this is the chosen, tested branch, not a fallback.
//
// Header format: `x-signals-auth: t=<unix sec>,n=<nonce>,v1=<hex hmac>`
// where v1 = HMAC(secret, `${t}.${n}.${sha256hex(body)}`).
// Checks in order: parse → timestamp window → signature (constant-time) →
// nonce freshness (signature first: never let unauthenticated input burn
// nonces). Secrets are injected; rotation = verify against a set (B3 adds
// the rotated set — a single secret today).

import { webcrypto } from 'node:crypto';

const DEFAULT_TOLERANCE_SEC = 300;

export interface NonceStore {
  /** True when fresh (stored for the window); false on replay. */
  checkAndStore(nonce: string, expiresAtMs: number, nowMs: number): Promise<boolean>;
}

export class MemoryNonceStore implements NonceStore {
  private readonly seen = new Map<string, number>();

  async checkAndStore(nonce: string, expiresAtMs: number, nowMs: number): Promise<boolean> {
    for (const [seenNonce, expiry] of this.seen) {
      if (expiry <= nowMs) this.seen.delete(seenNonce); // prune expired window
    }
    if (this.seen.has(nonce)) return false;
    this.seen.set(nonce, expiresAtMs);
    return true;
  }
}

async function sha256Hex(data: string): Promise<string> {
  const digest = await webcrypto.subtle.digest('SHA-256', new TextEncoder().encode(data));
  return [...new Uint8Array(digest)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

async function hmacSha256Hex(secret: string, data: string): Promise<string> {
  const key = await webcrypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const sig = await webcrypto.subtle.sign('HMAC', key, new TextEncoder().encode(data));
  return [...new Uint8Array(sig)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

function constantTimeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  let mismatch = 0;
  for (let i = 0; i < a.length; i++) mismatch |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return mismatch === 0;
}

export async function signWorkerRequest(
  secret: string,
  body: string,
  opts: { timestampSec: number; nonce: string },
): Promise<string> {
  const bodyHash = await sha256Hex(body);
  const signature = await hmacSha256Hex(secret, `${opts.timestampSec}.${opts.nonce}.${bodyHash}`);
  return `t=${opts.timestampSec},n=${opts.nonce},v1=${signature}`;
}

export type AuthResult = { ok: true; timestampSec: number } | { ok: false; reason: string };

export interface VerifyDeps {
  secret: string;
  nonceStore: NonceStore;
  toleranceSec?: number;
  nowSec?: number;
}

export async function verifyWorkerAuth(
  header: string | null | undefined,
  body: string,
  deps: VerifyDeps,
): Promise<AuthResult> {
  if (!header) return { ok: false, reason: 'missing_auth' };
  const parts = new Map<string, string>();
  for (const pair of header.split(',')) {
    const eq = pair.indexOf('=');
    if (eq > 0) parts.set(pair.slice(0, eq).trim(), pair.slice(eq + 1).trim());
  }
  const t = parts.get('t');
  const n = parts.get('n');
  const v1 = parts.get('v1');
  if (!t || !n || !v1) return { ok: false, reason: 'malformed_auth_header' };

  const timestampSec = Number(t);
  const toleranceSec = deps.toleranceSec ?? DEFAULT_TOLERANCE_SEC;
  const nowSec = deps.nowSec ?? Math.floor(Date.now() / 1000);
  if (!Number.isFinite(timestampSec)) return { ok: false, reason: 'malformed_auth_header' };
  if (Math.abs(nowSec - timestampSec) > toleranceSec) {
    return { ok: false, reason: 'timestamp_outside_window' };
  }

  const expected = await hmacSha256Hex(deps.secret, `${t}.${n}.${await sha256Hex(body)}`);
  if (!constantTimeEqual(v1, expected)) return { ok: false, reason: 'bad_signature' };

  // Signature valid → now consume the nonce within its replay window.
  const fresh = await deps.nonceStore.checkAndStore(n, (timestampSec + toleranceSec) * 1000, nowSec * 1000);
  if (!fresh) return { ok: false, reason: 'nonce_replay' };

  return { ok: true, timestampSec };
}
