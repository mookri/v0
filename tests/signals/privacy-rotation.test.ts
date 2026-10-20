// B7 acceptance — HMAC pepper rotation (plan B7):
//   identity continuity across the overlap; old-pepper-only identifiers
//   unresolvable after expiry. The scenario mirrors the operational story:
//   identifiers minted under pepper v1 are live in an identity-mapping
//   store, a rotation happens, and the overlap window is the migration
//   budget — the test proves continuity inside it and the hard break after.
import { describe, expect, it } from 'vitest';
import {
  PepperRing,
  type PepperEntry,
} from '@navdhya/analytics/src/privacy/pepper-rotation';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const HOUR = 60 * 60 * 1000;
const OVERLAP_MS = 24 * HOUR;

const ALICE = 'user_alice_1';
const BOB = 'user_bob_2';

function ringV1(): PepperRing {
  return new PepperRing([{ version: 1, pepper: 'kms-pepper-v1', expiresAtMs: null }]);
}

function rotatedRing(nowMs: () => number): PepperRing {
  const entries: PepperEntry[] = [
    { version: 1, pepper: 'kms-pepper-v1', expiresAtMs: NOW + OVERLAP_MS },
    { version: 2, pepper: 'kms-pepper-v2', expiresAtMs: null },
  ];
  return new PepperRing(entries, nowMs);
}

describe('B7 acceptance — pepper rotation', () => {
  it('continuity within overlap, hard break after expiry', async () => {
    // 1. Pre-rotation: identifiers minted under v1 live in a mapping store.
    let nowMs = NOW;
    const preRotation = ringV1();
    const mapping = new Map<string, string>([
      [await preRotation.hash(ALICE), ALICE],
      [await preRotation.hash(BOB), BOB],
    ]);
    for (const identifier of mapping.keys()) expect(identifier).toMatch(/^v1:/);

    // 2. Rotation: v2 becomes active, v1 enters a 24h overlap.
    const ring = rotatedRing(() => nowMs);
    expect(ring.activeVersion).toBe(2);

    // 3. Within the overlap: v1-minted identifiers still RESOLVE to their
    //    subjects (continuity), and new hashes mint under v2.
    for (const [identifier, uid] of mapping) {
      const resolved = await ring.resolve(uid, identifier);
      expect(resolved).toMatchObject({ resolved: true, withinOverlap: true, keyVersion: 1 });
    }
    const aliceV2 = await ring.hash(ALICE);
    expect(aliceV2).toMatch(/^v2:/);
    expect(aliceV2).not.toBe([...mapping.keys()][0]);

    // A v1 identifier for the WRONG subject fails closed (digest mismatch).
    const wrong = await ring.resolve(ALICE, [...mapping.keys()][1]!);
    expect(wrong.resolved).toBe(false);
    expect(wrong.reason).toContain('different subject');

    // 4. The overlap is the MIGRATION budget: re-mint every mapping entry
    //    under the active pepper before expiry.
    const migrated = new Map<string, string>();
    for (const [identifier, uid] of mapping) {
      migrated.set(await ring.migrate(uid), uid);
      expect(identifier.startsWith('v1:')).toBe(true);
    }
    expect([...migrated.keys()].every((key) => key.startsWith('v2:'))).toBe(true);

    // 5. After the overlap expires: v1-only identifiers are unresolvable
    //    BY DESIGN (identity broken for anything not migrated in time),
    //    while the v2 identifiers and the migrated mapping keep working.
    nowMs = NOW + OVERLAP_MS + HOUR;
    for (const [identifier, uid] of mapping) {
      const broken = await ring.resolve(uid, identifier);
      expect(broken.resolved).toBe(false);
      expect(broken.reason).toContain('expired');
    }
    for (const [identifier, uid] of migrated) {
      expect((await ring.resolve(uid, identifier)).resolved).toBe(true);
    }

    // Unknown key versions fail closed too — an identifier states which
    // pepper it needs, and an unknown one is never guessed at.
    expect((await ring.resolve(ALICE, 'v9:whatever')).reason).toContain('unknown pepper version');
  });
});
