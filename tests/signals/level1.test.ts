// B2 item 6: Level-1 reconciliation — gap detection must be exhaustive:
// committed→published gaps, published→persisted gaps, dedup violations.

import { describe, expect, it } from 'vitest';
import { reconcileLevel1 } from '@navdhya/analytics/src/server/reconciliation/level1';

describe('reconcileLevel1', () => {
  it('clean chain → ok, zero gaps', () => {
    const report = reconcileLevel1({
      committedIds: ['a', 'b', 'c'],
      publishedIds: ['a', 'b', 'c'],
      persistedIds: ['a', 'b', 'c'],
    });
    expect(report.ok).toBe(true);
    expect(report).toMatchObject({
      counts: { committed: 3, published: 3, persisted: 3 },
      missingPublished: [],
      missingPersisted: [],
      duplicatedPersisted: [],
    });
  });

  it('detects an INJECTED gap at the publisher (committed ∖ published)', () => {
    const report = reconcileLevel1({
      committedIds: ['a', 'b', 'c'],
      publishedIds: ['a', 'c'], // b never acked
      persistedIds: ['a', 'c'],
    });
    expect(report.ok).toBe(false);
    expect(report.missingPublished).toEqual(['b']);
    expect(report.missingPersisted).toEqual(['b']); // b is missing downstream too
  });

  it('detects an INJECTED gap at ingestion (published but not persisted)', () => {
    const report = reconcileLevel1({
      committedIds: ['a', 'b'],
      publishedIds: ['a', 'b'],
      persistedIds: ['a'], // b lost between Pub/Sub and the sink
    });
    expect(report.ok).toBe(false);
    expect(report.missingPublished).toEqual([]);
    expect(report.missingPersisted).toEqual(['b']);
  });

  it('detects duplicated persistence (insertId dedup violated)', () => {
    const report = reconcileLevel1({
      committedIds: ['a', 'b'],
      publishedIds: ['a', 'b'],
      persistedIds: ['a', 'b', 'a'],
    });
    expect(report.ok).toBe(false);
    expect(report.duplicatedPersisted).toEqual(['a']);
    expect(report.missingPersisted).toEqual([]);
  });

  it('publisher-never-acked but warehouse-persisted (redelivery covered it) is NOT a persisted gap', () => {
    // The ack was lost, not the event: persisted is ground truth downstream.
    const report = reconcileLevel1({
      committedIds: ['a'],
      publishedIds: [], // ack lost / crash pre-ack
      persistedIds: ['a'], // but the replay landed
    });
    expect(report.missingPersisted).toEqual([]);
    expect(report.missingPublished).toEqual(['a']); // still reported for the publisher SLO
    expect(report.ok).toBe(false); // any gap is a reportable condition
  });

  it('empty inputs are clean', () => {
    expect(reconcileLevel1({ committedIds: [], publishedIds: [], persistedIds: [] }).ok).toBe(true);
  });
});
