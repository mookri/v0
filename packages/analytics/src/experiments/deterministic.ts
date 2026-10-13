// Deterministic assignment primitive (plan B8 bullet 1): the plan names
// `deterministicHash` from apps/web/lib/dlo-x/ab-testing.ts as the primitive.
// That implementation is node:crypto (forbidden in this package — Web Crypto
// only), so this file replicates the ALGORITHM byte-for-byte over Web Crypto:
//   SHA-256 → first 4 bytes as big-endian u32 → rejection-sample into [0,100].
// A unit test pins output parity with the app primitive for the same inputs,
// so both surfaces bucket identically without sharing a runtime.
//
// Key derivation adds what the plan mandates on top of the primitive:
// a per-experiment SALT and the ALLOCATION_VERSION (bump ⇒ clean
// re-randomisation instead of carry-over bias at new weights).

const MAX = 101;
const POW32 = 0x1_0000_0000;

export async function deterministicHash(input: string): Promise<number> {
  if (typeof input !== 'string' || input.length === 0) return 0;
  const digest = new Uint8Array(
    await globalThis.crypto.subtle.digest('SHA-256', new TextEncoder().encode(input)),
  );
  const view = new DataView(digest.buffer, digest.byteOffset, digest.byteLength);
  const u32 = view.getUint32(0); // big-endian, same as the app primitive
  const maxUnbiased = Math.floor(POW32 / MAX) * MAX;
  if (u32 < maxUnbiased) return u32 % MAX;
  const u32b = view.getUint32(4);
  if (u32b < maxUnbiased) return u32b % MAX;
  return u32b % MAX; // bias here is negligible (<0.0001% of inputs)
}

export interface AssignmentInput {
  subjectIdHash: string;
  experimentId: string;
  salt: string;
  allocationVersion: number;
}

/** Integer weights, cumulative-bucket selection (identical semantics to the
 *  app's weighted pickVariant). A weight of 0 excludes a variant from NEW
 *  assignment while the ledger keeps interpreting historical rows. */
export async function pickVariant(
  input: AssignmentInput,
  variants: readonly string[],
  weights: Readonly<Record<string, number>>,
): Promise<string> {
  if (variants.length === 0) throw new Error('pickVariant: variants must be non-empty');
  const key = `${input.subjectIdHash}:${input.experimentId}:${input.salt}:v${input.allocationVersion}`;
  const hash = await deterministicHash(key);
  let total = 0;
  for (const variant of variants) total += weights[variant] ?? 0;
  if (total <= 0) throw new Error('pickVariant: weights must sum to a positive integer');
  const bucket = hash % total;
  let cumulative = 0;
  for (const variant of variants) {
    cumulative += weights[variant] ?? 0;
    if (bucket < cumulative) return variant;
  }
  // Unreachable for well-formed weights; fail closed rather than guess.
  throw new Error('pickVariant: bucket outside weight space');
}
