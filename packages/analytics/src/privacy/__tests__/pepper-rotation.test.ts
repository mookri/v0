// Pepper rotation (plan B7 acceptance): identity continuity across the
// overlap; old-pepper-only identifiers unresolvable after expiry; malformed
// or unknown versions fail closed.

import { describe, expect, it } from 'vitest';
import { PepperRing, type PepperEntry } from '@navdhya/analytics/src/privacy/pepper-rotation';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const HOUR = 60 * 60 * 1000;

function rotatedRing(nowMs: () => number = () => NOW) {
  const entries: PepperEntry[] = [
    { version: 1, pepper: 'pepper-one', expiresAtMs: NOW + 24 * HOUR },
    { version: 2, pepper: 'pepper-two', expiresAtMs: null },
  ];
  return { ring: new PepperRing(entries, nowMs), entries };
}

describe('pepper rotation (B7)', () => {
  it('mints self-describing identifiers under the ACTIVE pepper', async () => {
    const { ring } = rotatedRing();
    expect(ring.activeVersion).toBe(2);
    const identifier = await ring.hash('user_42');
    expect(identifier).toMatch(/^v2:[A-Za-z0-9_-]+$/);
    expect(await ring.hash('user_42')).toBe(identifier); // deterministic
    expect(await ring.hash('user_43')).not.toBe(identifier);
  });

  it('ROTATION: continuity within the overlap — old identifier still resolves', async () => {
    const preRotation = new PepperRing([{ version: 1, pepper: 'pepper-one', expiresAtMs: null }]);
    const legacy = await preRotation.hash('user_42');
    expect(legacy).toMatch(/^v1:/);

    const { ring } = rotatedRing();
    const resolved = await ring.resolve('user_42', legacy);
    expect(resolved).toMatchObject({ resolved: true, withinOverlap: true, keyVersion: 1 });

    // Migration re-mints under the active pepper.
    const migrated = await ring.migrate('user_42');
    expect(migrated).toMatch(/^v2:/);
    expect((await ring.resolve('user_42', migrated)).withinOverlap).toBe(false);
  });

  it('BREAK: after the overlap expires, old-pepper-only identifiers are unresolvable', async () => {
    let nowMs = NOW;
    const { ring } = rotatedRing(() => nowMs);
    const legacy = 'v1:' + (await new PepperRing([{ version: 1, pepper: 'pepper-one', expiresAtMs: null }]).hash('user_42').then((s) => s.split(':')[1]!));

    expect((await ring.resolve('user_42', legacy)).resolved).toBe(true); // inside overlap

    nowMs = NOW + 25 * HOUR; // past expiry
    const after = await ring.resolve('user_42', legacy);
    expect(after.resolved).toBe(false);
    expect(after.reason).toContain('expired');

    // The current pepper's identifiers keep resolving.
    expect((await ring.resolve('user_42', await ring.hash('user_42'))).resolved).toBe(true);
  });

  it('fails closed: digest mismatch, malformed, and unknown versions', async () => {
    const { ring } = rotatedRing();
    const other = await ring.hash('user_43');
    expect((await ring.resolve('user_42', other)).reason).toContain('different subject');
    expect((await ring.resolve('user_42', 'garbage')).reason).toContain('malformed');
    expect((await ring.resolve('user_42', 'v9:abc')).resolved).toBe(false);
    expect((await ring.resolve('user_42', 'v9:abc')).reason).toContain('unknown pepper version');
  });

  it('constructor requires exactly one active pepper and unique versions', () => {
    expect(() => new PepperRing([])).toThrow(/at least one/);
    expect(
      () =>
        new PepperRing([
          { version: 1, pepper: 'a', expiresAtMs: NOW },
          { version: 2, pepper: 'b', expiresAtMs: NOW + HOUR },
        ]),
    ).toThrow(/exactly one active/);
    expect(
      () =>
        new PepperRing([
          { version: 1, pepper: 'a', expiresAtMs: null },
          { version: 1, pepper: 'b', expiresAtMs: null },
        ]),
    ).toThrow(/exactly one active|duplicate/);
  });
});
