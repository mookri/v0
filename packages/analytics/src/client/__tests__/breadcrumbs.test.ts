// packages/analytics/src/client/__tests__/breadcrumbs.test.ts
// B1 acceptance: bounded ring buffer with field truncation.

import { describe, expect, it } from 'vitest';
import { Breadcrumbs } from '@navdhya/analytics/src/client/breadcrumbs';

describe('Breadcrumbs ring buffer', () => {
  it('evicts oldest beyond capacity (default 50)', () => {
    const bc = new Breadcrumbs();
    for (let i = 0; i < 60; i++) bc.record({ category: 'nav', message: `m${i}` });
    expect(bc.size()).toBe(50);
    const snap = bc.snapshot();
    expect(snap[0]!.message).toBe('m10'); // first 10 evicted
    expect(snap[49]!.message).toBe('m59');
  });

  it('drain empties the buffer; snapshot does not mutate internals', () => {
    const bc = new Breadcrumbs(5);
    bc.record({ category: 'ui', message: 'a' });
    bc.record({ category: 'ui', message: 'b' });

    const snap = bc.snapshot();
    snap.pop();
    expect(bc.size()).toBe(2); // snapshot is a copy

    const drained = bc.drain();
    expect(drained).toHaveLength(2);
    expect(bc.size()).toBe(0);
  });

  it('truncates fields to bounded lengths', () => {
    const bc = new Breadcrumbs();
    bc.record({
      category: 'x'.repeat(100),
      message: 'y'.repeat(500),
      route: '/'.repeat(500),
    });
    const [b] = bc.snapshot();
    expect(b!.category).toHaveLength(64);
    expect(b!.message).toHaveLength(256);
    expect(b!.route).toHaveLength(256);
  });

  it('rejects invalid capacities', () => {
    expect(() => new Breadcrumbs(0)).toThrow();
    expect(() => new Breadcrumbs(-1)).toThrow();
    expect(() => new Breadcrumbs(1.5)).toThrow();
  });
});
