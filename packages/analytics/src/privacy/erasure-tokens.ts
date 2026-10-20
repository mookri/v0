// Erasure tokens (plan B7, implementation bullet 4): the AUTHORITATIVE
// suppression table. "Erasure-aware access: queries over erased hashes
// return nothing; the erasure-token table is authoritative" — every read
// path that could surface a subject consults this table (filterErased), and
// the table is never pruned (retention matrix: erasure_tokens = indefinite;
// deleting a token would resurrect the subject on the next rebuild).
//
// Honesty notes:
//   - Tokens are keyed by PSEUDONYMOUS hash (uidHash/anonId) — the token
//     table never holds raw identifiers.
//   - Scope 'full' = account deletion (all data); 'fanout_only' =
//     consent withdrawal (stop sends; raw data ages out by retention).

export type ErasureScope = 'full' | 'fanout_only';

export interface ErasureToken {
  subjectHash: string;
  scope: ErasureScope;
  erasedAtMs: number;
  requestId: string;
}

export interface ErasureTokenStore {
  record(token: ErasureToken): Promise<void>;
  /** The token for a hash, or null when the subject was never erased. */
  get(subjectHash: string): Promise<ErasureToken | null>;
  /** Erasure-aware access: which of these hashes are erased? */
  filterErased(subjectHashes: readonly string[]): Promise<Set<string>>;
}

export class MemoryErasureTokenStore implements ErasureTokenStore {
  private readonly rows = new Map<string, ErasureToken>();

  async record(token: ErasureToken): Promise<void> {
    // First token wins: a fanout-only withdrawal followed by a full
    // deletion UPGRADES the scope (never downgrades).
    const existing = this.rows.get(token.subjectHash);
    if (!existing) {
      this.rows.set(token.subjectHash, { ...token });
      return;
    }
    if (existing.scope === 'fanout_only' && token.scope === 'full') {
      this.rows.set(token.subjectHash, { ...token });
    }
  }

  async get(subjectHash: string): Promise<ErasureToken | null> {
    return this.rows.get(subjectHash) ?? null;
  }

  async filterErased(subjectHashes: readonly string[]): Promise<Set<string>> {
    return new Set(subjectHashes.filter((hash) => this.rows.has(hash)));
  }
}

/** Apply filterErased to rows keyed by a subject hash: erased subjects
 *  vanish from the result — queries over erased hashes return nothing. */
export function excludeErased<T>(
  rows: readonly T[],
  subjectHashOf: (row: T) => string,
  erased: ReadonlySet<string>,
): T[] {
  return rows.filter((row) => !erased.has(subjectHashOf(row)));
}
