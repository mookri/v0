// Fixed-window limiter units (plan B3): per-session + per-IP limits with an
// injectable clock — tests never sleep.

import { describe, expect, it } from 'vitest';
import { FixedWindowRateLimiter } from '@navdhya/analytics/src/collector/rate-limit';

describe('FixedWindowRateLimiter (B3)', () => {
  it('allows up to the limit, then denies until the window resets', () => {
    let now = 1_000_000;
    const limiter = new FixedWindowRateLimiter({ limit: 3, windowMs: 60_000 }, () => now);
    expect(limiter.check('ip:1').ok).toBe(true);
    expect(limiter.check('ip:1').ok).toBe(true);
    expect(limiter.check('ip:1').ok).toBe(true);
    const denied = limiter.check('ip:1');
    expect(denied.ok).toBe(false);
    expect(denied.retryAfterSec).toBeGreaterThan(0);
    expect(denied.retryAfterSec).toBeLessThanOrEqual(60);

    now += 60_001;
    expect(limiter.check('ip:1').ok).toBe(true);
  });

  it('keeps independent buckets per key', () => {
    const limiter = new FixedWindowRateLimiter({ limit: 1, windowMs: 60_000 });
    expect(limiter.check('session:a').ok).toBe(true);
    expect(limiter.check('session:a').ok).toBe(false);
    expect(limiter.check('session:b').ok).toBe(true);
    expect(limiter.check('ip:9.9.9.9').ok).toBe(true);
  });

  it('charges cost > 1 per check', () => {
    const limiter = new FixedWindowRateLimiter({ limit: 10, windowMs: 60_000 });
    expect(limiter.check('k', 8).ok).toBe(true);
    expect(limiter.check('k', 2).ok).toBe(true);
    expect(limiter.check('k', 1).ok).toBe(false);
  });

  it('stays memory-bounded under key flooding (prune expired, then evict oldest)', () => {
    let now = 0;
    const limiter = new FixedWindowRateLimiter({ limit: 5, windowMs: 1_000, maxKeys: 100 }, () => now);
    for (let i = 0; i < 500; i += 1) limiter.check(`flood:${i}`);
    expect(limiter.size()).toBeLessThanOrEqual(101);
    // After the window passes, live-checking keys re-establish buckets freely.
    now = 5_000;
    limiter.check('fresh');
    expect(limiter.check('fresh').ok).toBe(true);
  });

  it('rejects nonsensical options at construction', () => {
    expect(() => new FixedWindowRateLimiter({ limit: 0, windowMs: 1000 })).toThrow(/invalid options/);
    expect(() => new FixedWindowRateLimiter({ limit: 1, windowMs: 0 })).toThrow(/invalid options/);
  });
});
