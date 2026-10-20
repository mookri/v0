/**
 * Centralized runtime ID generator for the Navdhya ad system.
 *
 * Replaces the previous hardcoded-timestamp placeholder pattern,
 * which was both a parser error (numeric literal followed by .toString)
 * and architecturally wrong (every request generated the same ID).
 *
 * Semantics:
 * - requestId     → every incoming decision request
 * - auctionId     → one auction execution
 * - decisionId    → one ad decision (slot-scoped)
 * - impressionId / eventId / batchId / sessionId → corresponding concepts
 *
 * Uses `crypto.randomUUID()` in production (Node 20+ and modern browsers).
 * Falls back to `Date.now()` + `Math.random()` outside secure contexts.
 * Accepts an optional injected `id` for deterministic tests.
 */
export function createRuntimeId(prefix: string, id?: string): string {
  if (id) {
    return `${prefix}_${id}`;
  }

  try {
    const uuid = globalThis.crypto?.randomUUID?.();
    if (uuid) {
      return `${prefix}_${uuid}`;
    }
  } catch {
    // fall through to Math.random fallback below
  }

  const time = Date.now().toString(36);
  const rand = Math.random().toString(36).slice(2, 10);
  return `${prefix}_${time}${rand}`;
}
