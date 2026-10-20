// Erasure-aware access (plan B7, implementation bullet 4): queries over
// erased hashes return nothing; the erasure-token table is authoritative.

import { describe, expect, it } from 'vitest';
import {
  MemoryErasureTokenStore,
  excludeErased,
} from '@navdhya/analytics/src/privacy/erasure-tokens';
import {
  MemoryConsentLedger,
  assertFanoutAllowed,
} from '@navdhya/analytics/src/privacy/consent-ledger';

const AT = Date.parse('2026-10-13T12:00:00.000Z');

describe('erasure-aware access (B7)', () => {
  it('filterErased flags exactly the erased hashes', async () => {
    const store = new MemoryErasureTokenStore();
    await store.record({ subjectHash: 'uh_gone', scope: 'full', erasedAtMs: AT, requestId: 'del_1' });
    const erased = await store.filterErased(['uh_live', 'uh_gone', 'uh_other']);
    expect(erased).toEqual(new Set(['uh_gone']));
    expect(await store.get('uh_live')).toBeNull();
  });

  it('queries over erased hashes return nothing', async () => {
    const store = new MemoryErasureTokenStore();
    await store.record({ subjectHash: 'uh_gone', scope: 'full', erasedAtMs: AT, requestId: 'del_1' });
    const rows = [
      { id: 'e1', uidHash: 'uh_live' },
      { id: 'e2', uidHash: 'uh_gone' },
      { id: 'e3', uidHash: 'uh_gone' },
    ];
    const erased = await store.filterErased(rows.map((row) => row.uidHash));
    const visible = excludeErased(rows, (row) => row.uidHash, erased);
    expect(visible.map((row) => row.id)).toEqual(['e1']);
    // A query for ONLY erased subjects returns nothing at all:
    expect(excludeErased(rows.filter((r) => r.uidHash === 'uh_gone'), (r) => r.uidHash, erased)).toEqual([]);
  });

  it('consent withdrawal blocks fan-out; the ledger is first-withdrawal-wins', async () => {
    const ledger = new MemoryConsentLedger();
    expect((await assertFanoutAllowed(ledger, 'uh_1', 'ads')).ok).toBe(true);

    await ledger.withdraw({ subjectHash: 'uh_1', purpose: 'ads', withdrawnAtMs: AT, requestId: 'wd_1' });
    const blocked = await assertFanoutAllowed(ledger, 'uh_1', 'ads');
    expect(blocked.ok).toBe(false);
    expect(blocked.reason).toContain('fan-out blocked');
    // Purpose-scoped: analytics is unaffected by the ads withdrawal.
    expect((await assertFanoutAllowed(ledger, 'uh_1', 'analytics')).ok).toBe(true);

    // A later repeat never rewrites the original withdrawal time.
    await ledger.withdraw({ subjectHash: 'uh_1', purpose: 'ads', withdrawnAtMs: AT + 1000, requestId: 'wd_2' });
    expect((await ledger.get('uh_1', 'ads'))?.requestId).toBe('wd_1');
  });
});
