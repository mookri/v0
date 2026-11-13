const SID_TTL_MS = 24 * 60 * 60 * 1000;
const JTI_TTL_MS = 10 * 60 * 1000;
const MAX_ENTRIES = 10000;

export function createBackchannelLogoutStore() {
  const terminatedSids = new Map<string, number>();
  const seenLogoutJtis = new Map<string, number>();

  const prune = (store: Map<string, number>): void => {
    const now = Date.now();
    for (const [key, expiry] of store) {
      if (expiry <= now) store.delete(key);
    }
    while (store.size > MAX_ENTRIES) {
      const oldest = store.keys().next();
      if (oldest.done) break;
      store.delete(oldest.value);
    }
  };

  return {
    rememberLogoutJti(jti: string): boolean {
      prune(seenLogoutJtis);
      if (seenLogoutJtis.has(jti)) return false;
      seenLogoutJtis.set(jti, Date.now() + JTI_TTL_MS);
      return true;
    },
    markSidTerminated(sid: string): void {
      prune(terminatedSids);
      terminatedSids.set(sid, Date.now() + SID_TTL_MS);
    },
    isSidTerminated(sid: string): boolean {
      const expiry = terminatedSids.get(sid);
      if (expiry === undefined) return false;
      if (expiry <= Date.now()) {
        terminatedSids.delete(sid);
        return false;
      }
      return true;
    },
    payloadSidTerminated(payload: Record<string, unknown>): boolean {
      const sid = payload['sid'];
      const expiry = typeof sid === 'string' && sid !== '' ? terminatedSids.get(sid) : undefined;
      if (expiry === undefined) return false;
      if (expiry <= Date.now()) {
        terminatedSids.delete(sid as string);
        return false;
      }
      return true;
    },
    clear(): void {
      terminatedSids.clear();
      seenLogoutJtis.clear();
    },
  };
}

