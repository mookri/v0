// Worker auth (decision #6): HMAC + timestamp window + nonce replay.

import { describe, expect, it } from 'vitest';
import {
  MemoryNonceStore,
  signWorkerRequest,
  verifyWorkerAuth,
  type VerifyDeps,
} from '../worker-auth';

const SECRET = 'worker-shared-secret';
const BODY = JSON.stringify({ events: [{ name: 'page_view' }] });
const NOW = 1_780_000_000; // fixed clock

function deps(overrides: Partial<VerifyDeps> = {}): VerifyDeps {
  return { secret: SECRET, nonceStore: new MemoryNonceStore(), nowSec: NOW, ...overrides };
}

async function validHeader(nonce = 'nonce_1'): Promise<string> {
  return signWorkerRequest(SECRET, BODY, { timestampSec: NOW, nonce });
}

describe('worker auth', () => {
  it('accepts a correctly signed request inside the window', async () => {
    const result = await verifyWorkerAuth(await validHeader(), BODY, deps());
    expect(result).toEqual({ ok: true, timestampSec: NOW });
  });

  it('rejects missing and malformed headers', async () => {
    expect(await verifyWorkerAuth(null, BODY, deps())).toEqual({ ok: false, reason: 'missing_auth' });
    expect(await verifyWorkerAuth('t=1,n=x', BODY, deps())).toEqual({
      ok: false,
      reason: 'malformed_auth_header',
    });
    expect(await verifyWorkerAuth('t=abc,n=x,v1=deadbeef', BODY, deps())).toEqual({
      ok: false,
      reason: 'malformed_auth_header',
    });
  });

  it('rejects timestamps outside the replay window (old and future)', async () => {
    const header = await signWorkerRequest(SECRET, BODY, { timestampSec: NOW - 301, nonce: 'n_old' });
    expect(await verifyWorkerAuth(header, BODY, deps())).toEqual({
      ok: false,
      reason: 'timestamp_outside_window',
    });

    const future = await signWorkerRequest(SECRET, BODY, { timestampSec: NOW + 301, nonce: 'n_future' });
    expect(await verifyWorkerAuth(future, BODY, deps())).toEqual({
      ok: false,
      reason: 'timestamp_outside_window',
    });

    // Boundary: exactly within tolerance is fine.
    const edge = await signWorkerRequest(SECRET, BODY, { timestampSec: NOW - 300, nonce: 'n_edge' });
    expect((await verifyWorkerAuth(edge, BODY, deps())).ok).toBe(true);
  });

  it('rejects tampered bodies and wrong secrets', async () => {
    const header = await validHeader();
    expect(await verifyWorkerAuth(header, `${BODY} `, deps())).toEqual({
      ok: false,
      reason: 'bad_signature',
    });
    expect(await verifyWorkerAuth(header, BODY, deps({ secret: 'rotated' }))).toEqual({
      ok: false,
      reason: 'bad_signature',
    });
  });

  it('rejects nonce replay within the window', async () => {
    const d = deps(); // ONE store across both attempts — replay is store-scoped
    const header = await validHeader('nonce_r');
    expect((await verifyWorkerAuth(header, BODY, d)).ok).toBe(true);
    expect(await verifyWorkerAuth(header, BODY, d)).toEqual({ ok: false, reason: 'nonce_replay' });
  });

  it('different nonces with the same body are both fine', async () => {
    const d = deps();
    expect((await verifyWorkerAuth(await validHeader('n_a'), BODY, d)).ok).toBe(true);
    expect((await verifyWorkerAuth(await validHeader('n_b'), BODY, d)).ok).toBe(true);
  });
});
