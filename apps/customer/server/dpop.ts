import { createPrivateKey, generateKeyPairSync, randomUUID, sign, createHash } from 'node:crypto';
import type { KeyObject } from 'node:crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

// DPoP sender-constrained tokens (A-SEC-004-9, RFC 9449): this BFF
// holds ONE EC P-256 key per instance and proves two legs with it —
// the authorization-code exchange (which binds the issued access
// token, `cnf.dpop_jkt`, to this key), every upstream call that
// presents a bound token (proof carries `ath` over the presented
// token), and the refresh rotation (A-SEC-004-11 — the issuer
// refuses a refresh of a bound family unless the proof re-matches the
// binding). A stolen access token is worthless without this key.
//
// Per-instance store (A-SEC-004-11): Next 16 splits the proxy and
// the route handlers into SEPARATE module graphs. A module-level key
// cache would hold TWO keys — observed live in the leg 90 drill: the
// callback (route graph) bound families to key A, the proxy (its own
// graph) proved rotations with key B, and every rotation died with
// 400 invalid_dpop_proof. The keypair therefore lives in ONE file per
// instance: first use generates it under an exclusive create (O_EXCL),
// any later reader — other graph, worker thread, reloaded module —
// loads that same file. Per-graph caches are only a fast path over
// the file, never the source of truth.
//
// Key file: `$CUSTOMER_DPOP_KEY_FILE`, default
// `<cwd>/.next/dpop-key.json` (gitignored build dir). A missing or
// unparsable file regenerates — and, exactly like a process restart
// before it, every session bound to the old key must re-login. The
// file is instance secret material: filesystem permissions are its
// protection, the same posture as a TLS key at rest. If the file
// cannot be written at all the BFF falls back to an in-memory key
// (logged loudly) — fine for single-graph auth, but refresh rotation
// across graphs then fails closed. Multi-instance deployments still
// need a shared or stickily-routed key store, the documented
// production extension alongside the other per-instance state notes.
//
// Opportunistic posture: proofs ride on every request; when the
// client is not mandated (require_dpop) an unbound token simply
// ignores them. Mandated clients MUST exchange with the proof, and
// this BFF always does — so opt-in is transparent here.

interface DpopKey {
  privateKey: KeyObject;
  publicJwk: Record<string, string>;
}

/** Per-graph fast path; the file is the source of truth (see above). */
let cached: DpopKey | null = null;

function keyFilePath(): string {
  return process.env.CUSTOMER_DPOP_KEY_FILE || path.join(process.cwd(), '.next', 'dpop-key.json');
}

/** Rebuild a signing key from a stored P-256 private JWK; null when the record is not one. */
function keyFromRecord(record: Record<string, unknown>): DpopKey | null {
  if (
    record.kty !== 'EC' ||
    record.crv !== 'P-256' ||
    typeof record.x !== 'string' ||
    typeof record.y !== 'string' ||
    typeof record.d !== 'string'
  ) {
    return null;
  }
  try {
    const privateKey = createPrivateKey({ key: record, format: 'jwk' } as never);
    return {
      privateKey,
      publicJwk: { kty: 'EC', crv: 'P-256', x: record.x, y: record.y },
    };
  } catch {
    return null;
  }
}

/** Missing, unreadable or corrupt file — all treated as “no key yet”. */
function readKeyFile(file: string): DpopKey | null {
  try {
    return keyFromRecord(JSON.parse(readFileSync(file, 'utf8')) as Record<string, unknown>);
  } catch {
    return null;
  }
}

function generateRecord(): { key: DpopKey; record: Record<string, unknown> } {
  const { privateKey, publicKey } = generateKeyPairSync('ec', { namedCurve: 'P-256' });
  const priv = privateKey.export({ format: 'jwk' }) as Record<string, unknown>;
  const pub = publicKey.export({ format: 'jwk' }) as Record<string, unknown>;
  const record = { kty: pub.kty, crv: pub.crv, x: pub.x, y: pub.y, d: priv.d };
  const key: DpopKey = {
    privateKey,
    publicJwk: { kty: 'EC', crv: 'P-256', x: String(pub.x), y: String(pub.y) },
  };
  return { key, record };
}

function dpopKey(): DpopKey {
  if (cached) return cached;

  const file = keyFilePath();

  // Another graph (or a previous run of this instance) already
  // published the key — use theirs, never a fresh local one.
  const existing = readKeyFile(file);
  if (existing) {
    cached = existing;
    return cached;
  }

  const { key, record } = generateRecord();
  const publish = (exclusive: boolean): boolean => {
    try {
      mkdirSync(path.dirname(file), { recursive: true });
      writeFileSync(file, JSON.stringify(record), { flag: exclusive ? 'wx' : 'w', mode: 0o600 });
      return true;
    } catch {
      return false;
    }
  };

  // Claim the file exclusively. Losing the race means another graph
  // won between our read and this write — reload ITS key and drop the
  // local loser: that mismatch is precisely the invalid_dpop_proof
  // failure this store exists to prevent.
  if (publish(true)) {
    cached = key;
    return cached;
  }

  const winner = readKeyFile(file);
  if (winner) {
    cached = winner;
    return cached;
  }

  // The file exists but is unreadable (corrupt): replace it once so
  // the instance keeps a single key rather than per-graph memories.
  if (publish(false)) {
    console.error(`[dpop] key file was unreadable; regenerated ${file} — sessions bound to the old key must re-login`);
    cached = key;
    return cached;
  }

  // No usable file system for the store: keep the generated key in
  // memory (today's graph-local behaviour) and say so — refresh
  // rotation across Next's module graphs needs the shared file.
  console.error(`[dpop] cannot persist the instance key at ${file}; falling back to an in-memory key — refresh rotation may fail closed`);
  cached = key;
  return cached;
}

/** The BFF proof public key (RFC 7517 JSON form, inline in every proof header). */
export function dpopPublicJwk(): Record<string, string> {
  return { ...dpopKey().publicJwk };
}

function base64url(input: string | Buffer): string {
  return Buffer.from(input).toString('base64url');
}

/**
 * Mint a compact JWS DPoP proof for one request leg.
 *
 * `htu` is the exact request target (query/fragment stripped — RFC
 * 9449 §4.2: the claim MUST NOT include them). `accessToken` adds the
 * `ath` confirmation (required when a bound token is presented).
 */
export function dpopProof(htm: string, htu: string, accessToken?: string): string {
  const key = dpopKey();

  const target = new URL(htu);
  target.search = '';
  target.hash = '';

  const header = { typ: 'DPoP', alg: 'ES256', jwk: key.publicJwk };
  const payload: Record<string, string | number> = {
    htm: htm.toUpperCase(),
    htu: target.toString(),
    iat: Math.floor(Date.now() / 1000),
    jti: randomUUID(),
  };
  if (accessToken) {
    payload.ath = createHash('sha256').update(accessToken).digest('base64url');
  }

  const signingInput = `${base64url(JSON.stringify(header))}.${base64url(JSON.stringify(payload))}`;
  // JOSE ES256 is raw R||S (IEEE P1363) — dsaEncoding switches Node
  // away from its DER default.
  const signature = sign('sha256', Buffer.from(signingInput), {
    key: key.privateKey,
    dsaEncoding: 'ieee-p1363',
  });

  return `${signingInput}.${base64url(signature)}`;
}
