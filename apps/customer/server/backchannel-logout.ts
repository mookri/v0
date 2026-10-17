/**
 * RP-side back-channel logout state (A-SEC-004-6) — customer BFF.
 *
 * The federated session here is the issuer access token in an
 * HttpOnly cookie: there is no server-side session record to delete
 * when the OP's logout_token arrives. What the BFF CAN do — and what
 * this module does — is remember the terminated `sid` and refuse to
 * resolve an actor behind it: every actor decode checks the
 * denylist, so the cookie dies at its next use instead of lingering
 * until the access token's expiry. The issuer side remains the
 * primary kill (the same logout revokes the token lineage, so every
 * proxied call already 401s); this closes the BFF-local, decode-only
 * gap immediately.
 *
 * State is per-process and bounded (a Map with a TTL and a hard cap).
 * A multi-instance deployment wants a shared store (Redis) behind the
 * same two functions; single-instance dev/staging and the fail-closed
 * issuer half do not need it. The denylist only ever ADDS
 * rejections: a forged cookie's `sid` claim can at most deny
 * itself, never grant a session.
 */

const SID_TTL_MS = 24 * 60 * 60 * 1000;
const JTI_TTL_MS = 10 * 60 * 1000;
const MAX_ENTRIES = 10000;

const terminatedSids = new Map<string, number>();
const seenLogoutJtis = new Map<string, number>();

function prune(store: Map<string, number>): void {
  const now = Date.now();
  for (const [key, expiry] of store) {
    if (expiry <= now) store.delete(key);
  }
  while (store.size > MAX_ENTRIES) {
    const oldest = store.keys().next();
    if (oldest.done) break;
    store.delete(oldest.value);
  }
}

/**
 * Record a verified logout_token's jti. Returns false when the jti
 * was already seen — a replayed logout_token is rejected.
 */
export function rememberLogoutJti(jti: string): boolean {
  prune(seenLogoutJtis);
  if (seenLogoutJtis.has(jti)) return false;
  seenLogoutJtis.set(jti, Date.now() + JTI_TTL_MS);
  return true;
}

/** Record the OP session a verified logout_token ended. */
export function markSidTerminated(sid: string): void {
  prune(terminatedSids);
  terminatedSids.set(sid, Date.now() + SID_TTL_MS);
}

/** True when this OP session was ended by a back-channel logout. */
export function isSidTerminated(sid: string): boolean {
  const expiry = terminatedSids.get(sid);
  if (expiry === undefined) return false;
  if (expiry <= Date.now()) {
    terminatedSids.delete(sid);
    return false;
  }
  return true;
}

/**
 * Termination check over a DECODED, untrusted token payload — the
 * shape customer's decodeFederatedActor already holds. Pure local
 * lookup: the payload is never trusted, only interrogated, and a
 * bogus sid matches nothing (the check can only ever REJECT).
 */
export function payloadSidTerminated(payload: Record<string, unknown>): boolean {
  const sid = payload['sid'];
  return typeof sid === 'string' && sid !== '' && isSidTerminated(sid);
}

/** Test hook: forget every recorded termination and replay key. */
export function clearBackchannelLogoutState(): void {
  terminatedSids.clear();
  seenLogoutJtis.clear();
}
