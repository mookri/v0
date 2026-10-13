// Collector signing units (plan B3, decision #6). The cross-package contract
// (these signatures verify under services/functions/ingestion worker-auth)
// is proven in tests/signals/collector.test.ts; here we pin the wire format.

import { describe, expect, it } from 'vitest';
import { signCollectorForward } from '@navdhya/analytics/src/collector/auth';

describe('signCollectorForward (B3)', () => {
  it('emits the exact ingestion wire format t=,n=,v1=', async () => {
    const header = await signCollectorForward('secret', '{"events":[]}', {
      timestampSec: 1_780_000_000,
      nonce: 'n-1',
    });
    expect(header).toMatch(/^t=1780000000,n=n-1,v1=[0-9a-f]{64}$/);
  });

  it('is deterministic for identical inputs and changes with the body', async () => {
    const a = await signCollectorForward('secret', 'body', { timestampSec: 1, nonce: 'n' });
    const b = await signCollectorForward('secret', 'body', { timestampSec: 1, nonce: 'n' });
    const c = await signCollectorForward('secret', 'body!', { timestampSec: 1, nonce: 'n' });
    const d = await signCollectorForward('secret', 'body', { timestampSec: 2, nonce: 'n' });
    expect(a).toBe(b);
    expect(c).not.toBe(b);
    expect(d).not.toBe(b);
  });

  it('changes with the secret (no accidental cross-secret acceptance)', async () => {
    const a = await signCollectorForward('secret-a', 'body', { timestampSec: 1, nonce: 'n' });
    const b = await signCollectorForward('secret-b', 'body', { timestampSec: 1, nonce: 'n' });
    expect(a).not.toBe(b);
  });
});
