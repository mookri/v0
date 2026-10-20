// packages/analytics/src/collector/contract.ts
//
// B3a collector contract constants shared by BOTH edge adapters (Next Edge
// route + Cloudflare Worker) — the same numbers on every surface, enforced
// before any event is looked at:
//   - 64 KiB body cap        → 413, never partially accepted
//   - ≤ 25 events per batch  → 400 (GA4/collector request bound)
//   - forbidden-property stripping (data minimization): direct identifiers
//     are deleted from params BEFORE validation — an event that *required*
//     one fails validation and the batch is rejected (fail-closed).
//
// Statelessness rule (plan B3): nothing here keeps journey state, cohort
// logic or business workflows. The only state any adapter may hold is the
// per-isolate rate-limit window.

/** 64 KiB cap on the raw request body (plan B3, item 2). */
export const MAX_BATCH_BODY_BYTES = 64 * 1024;

/** ≤ 25 events per batch (GA4/collector request bound). */
export const MAX_EVENTS_PER_BATCH = 25;

/**
 * Forbidden parameter keys (data minimization). Stripped at the edge, before
 * validation, from every forwarded event — direct identifiers and secrets
 * never reach the analytics stream. Case-insensitive match. Declared registry
 * params (e.g. `search_term`) are intentionally NOT here: declared free text
 * is governed by redaction rules at external sinks, not deleted internally.
 */
export const FORBIDDEN_PARAM_KEYS: readonly string[] = [
  // contact
  'email', 'phone', 'mobile', 'telephone', 'whatsapp',
  // names
  'name', 'full_name', 'first_name', 'last_name', 'display_name',
  // precise location / identity documents
  'address', 'street', 'lat', 'lng', 'latitude', 'longitude',
  'dob', 'date_of_birth', 'pan', 'aadhaar', 'passport',
  // secrets / credentials
  'password', 'token', 'auth', 'authorization', 'cookie', 'secret',
  'api_key', 'session_token', 'id_token', 'access_token',
  // account / payment identifiers
  'uid', 'user_id', 'customer_id', 'account_number', 'card_number', 'cvv', 'ifsc',
];

const FORBIDDEN_SET: ReadonlySet<string> = new Set(FORBIDDEN_PARAM_KEYS.map((k) => k.toLowerCase()));

export function byteLength(text: string): number {
  return new TextEncoder().encode(text).length;
}

export interface StripResult {
  /** Shallow copies with forbidden params deleted; input objects untouched. */
  events: unknown[];
  /** Keys actually removed in this batch (for transparency in the response). */
  strippedKeys: string[];
}

/**
 * Remove forbidden parameter keys from every well-formed envelope in the
 * batch. Malformed entries pass through unchanged — validation rejects them
 * with the structural error, which is the more useful report.
 */
export function stripForbiddenParams(events: unknown[]): StripResult {
  const strippedKeys = new Set<string>();
  const out = events.map((entry) => {
    if (typeof entry !== 'object' || entry === null || Array.isArray(entry)) return entry;
    const envelope = entry as Record<string, unknown>;
    const params = envelope.params;
    if (typeof params !== 'object' || params === null || Array.isArray(params)) return entry;
    const copy: Record<string, unknown> = {};
    let touched = false;
    for (const [key, value] of Object.entries(params)) {
      if (FORBIDDEN_SET.has(key.toLowerCase())) {
        strippedKeys.add(key);
        touched = true;
        continue;
      }
      copy[key] = value;
    }
    return touched ? { ...envelope, params: copy } : entry;
  });
  return { events: out, strippedKeys: [...strippedKeys] };
}
