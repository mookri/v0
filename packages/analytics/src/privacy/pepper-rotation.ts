// HMAC pepper rotation (plan B7, implementation bullet 3): a versioned
// pepper ring with an OVERLAP window — identifiers minted under the old
// pepper stay resolvable while the overlap lasts and become unresolvable
// after expiry, which is the evaluated property ("continuity within
// overlap, break after expiry").
//
// Honesty notes:
//   - Web Crypto only (globalThis.crypto.subtle) — the standing rule for
//     this package (no node: imports; runs on Edge/Workers/Node).
//   - Peppers are LOADED here, never minted here: construction takes
//     already-fetched secrets (KMS/env/driver-gated fetch is outside).
//   - Identifiers are self-describing (`v{keyVersion}:{base64url digest}`),
//     so an identifier states which pepper it needs — an unknown or expired
//     version fails CLOSED rather than guessing.

const encoder = new TextEncoder();

async function hmacDigestBase64Url(pepper: string, value: string): Promise<string> {
  const key = await globalThis.crypto.subtle.importKey(
    'raw',
    encoder.encode(pepper),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const signature = await globalThis.crypto.subtle.sign('HMAC', key, encoder.encode(value));
  const bytes = new Uint8Array(signature);
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

export interface PepperEntry {
  version: number;
  pepper: string;
  /** Epoch ms after which this pepper no longer resolves identifiers
   *  (null = the ACTIVE pepper). Exactly one entry may be active. */
  expiresAtMs: number | null;
}

export interface ResolveResult {
  resolved: boolean;
  /** True when resolution relied on a previous pepper inside its overlap. */
  withinOverlap: boolean;
  keyVersion: number | null;
  reason: string;
}

const IDENTIFIER_RE = /^v(\d+):([A-Za-z0-9_-]+)$/;

export class PepperRing {
  private readonly byVersion: Map<number, PepperEntry>;
  private readonly active: PepperEntry;

  constructor(entries: readonly PepperEntry[], private readonly nowMs: () => number = Date.now) {
    if (entries.length === 0) throw new Error('PepperRing requires at least one pepper');
    const actives = entries.filter((entry) => entry.expiresAtMs === null);
    if (actives.length !== 1) {
      throw new Error(`PepperRing requires exactly one active pepper, got ${actives.length}`);
    }
    this.active = actives[0]!;
    this.byVersion = new Map(entries.map((entry) => [entry.version, entry]));
    for (const entry of entries) {
      const twin = entries.find(
        (other) => other !== entry && other.version === entry.version,
      );
      if (twin) throw new Error(`duplicate pepper version ${entry.version}`);
    }
  }

  get activeVersion(): number {
    return this.active.version;
  }

  /** Mint an identifier for a raw id under the ACTIVE pepper. */
  async hash(uid: string): Promise<string> {
    const digest = await hmacDigestBase64Url(this.active.pepper, uid);
    return `v${this.active.version}:${digest}`;
  }

  /** Can this (raw id, observed identifier) pair be resolved to one
   *  identity? Used by identity-mapping migration and erasure matching. */
  async resolve(uid: string, identifier: string): Promise<ResolveResult> {
    const match = IDENTIFIER_RE.exec(identifier);
    if (!match) {
      return { resolved: false, withinOverlap: false, keyVersion: null, reason: 'malformed identifier' };
    }
    const keyVersion = Number(match[1]);
    const entry = this.byVersion.get(keyVersion);
    if (!entry) {
      return {
        resolved: false,
        withinOverlap: false,
        keyVersion,
        reason: `unknown pepper version v${keyVersion} — identifier unresolvable`,
      };
    }

    const expected = await hmacDigestBase64Url(entry.pepper, uid);
    if (expected !== match[2]) {
      return { resolved: false, withinOverlap: false, keyVersion, reason: 'digest mismatch — different subject' };
    }

    if (entry.expiresAtMs === null) {
      return { resolved: true, withinOverlap: false, keyVersion, reason: 'current pepper' };
    }
    if (this.nowMs() > entry.expiresAtMs) {
      return {
        resolved: false,
        withinOverlap: false,
        keyVersion,
        reason: `pepper v${keyVersion} expired at ${new Date(entry.expiresAtMs).toISOString()} — identifier unresolvable by design`,
      };
    }
    return {
      resolved: true,
      withinOverlap: true,
      keyVersion,
      reason: `within overlap of pepper v${keyVersion} (expires ${new Date(entry.expiresAtMs).toISOString()})`,
    };
  }

  /** Re-mint an identifier under the ACTIVE pepper (migration path). */
  async migrate(uid: string): Promise<string> {
    return this.hash(uid);
  }
}
