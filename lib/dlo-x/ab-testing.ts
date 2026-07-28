// lib/dlo-x/ab-testing.ts

import { createHash } from "node:crypto";

/**
 * Deterministic A/B hash. Returns an integer in [0, 100].
 *
 * The old DJB2-like implementation had two problems:
 *   1. Modulo bias — `Math.abs(hash) % 101` is biased when the underlying
 *      hash distribution is non-uniform; small input changes produced
 *      visible clustering in A/B buckets at our traffic levels.
 *   2. Weak mixing — neighbouring strings (e.g. `user-1234` and
 *      `user-1235`) often collided into the same bucket.
 *
 * New implementation:
 *   - SHA-256 of the input (well-distributed, secure)
 *   - Reads the first 4 bytes as an unsigned 32-bit integer
 *   - Rejection-samples into [0, 100] to eliminate modulo bias
 *
 * Performance: SHA-256 is ~5µs for short strings on Node 20, which is
 * well under our per-request budget. The hash is computed at most a
 * handful of times per request (A/B variant, layout variant, telemetry
 * seed derivative).
 */
const MAX = 101;
const POW32 = 0x1_0000_0000;

export function deterministicHash(input: string): number {
  if (typeof input !== "string" || input.length === 0) return 0;
  const digest = createHash("sha256").update(input, "utf8").digest();
  // Take the first 4 bytes as a big-endian u32
  const u32 = digest.readUInt32BE(0);
  // Rejection sampling: largest multiple of MAX that fits in u32
  const maxUnbiased = Math.floor(POW32 / MAX) * MAX;
  if (u32 < maxUnbiased) {
    return u32 % MAX;
  }
  // Rare re-roll on the next 4 bytes
  const u32b = digest.readUInt32BE(4);
  if (u32b < maxUnbiased) {
    return u32b % MAX;
  }
  // Fallback: modulo. Bias here is negligible (<0.0001% of inputs)
  return u32b % MAX;
}

/**
 * Pick one of N variants deterministically. Caller passes an array of
 * variant ids and the user-key; returns the chosen id.
 *
 * `weights` lets you bias allocation. Defaults to equal weight.
 * Weights are integers; the function computes the bucket based on
 * `deterministicHash(key + ':' + experimentId) % totalWeight`.
 */
export function pickVariant<T extends string>(
  key: string,
  experimentId: string,
  variants: readonly T[],
  weights?: Record<T, number>,
): T {
  if (variants.length === 0) {
    throw new Error("pickVariant: variants must be non-empty");
  }
  if (!weights) {
    const idx = deterministicHash(`${key}:${experimentId}`) % variants.length;
    return variants[idx]!;
  }
  let total = 0;
  for (const v of variants) total += weights[v] ?? 0;
  if (total <= 0) {
    throw new Error(`pickVariant: weights for ${experimentId} sum to ${total}`);
  }
  const r = deterministicHash(`${key}:${experimentId}`) % total;
  let cum = 0;
  for (const v of variants) {
    cum += weights[v] ?? 0;
    if (r < cum) return v;
  }
  return variants[variants.length - 1]!;
}