/**
 * Console BFF envelope reader — W1a,
 * `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 *
 * Every console BFF route answers with the app envelope `{ data, requestId }`,
 * where `data` is the proxied payload `{ data, meta }`. Three outcomes matter
 * to a panel, and conflating them is how the consoles used to lie:
 *
 *   - `data`     — real rows from Laravel;
 *   - `fallback` — the BFF had no backend configured (`meta.fallback: true`).
 *                  An empty table here reads as "there are no roles", which is
 *                  a fabricated fact, so panels must render a config notice;
 *   - `error`    — a mapped backend/BFF failure (`error.code`).
 *
 * Pure and dependency-free: no `next/*`, no fetch, so it unit-tests without a
 * request scope.
 */

export interface ConsoleEnvelopeError {
  code: string;
  message: string;
  requestId?: string;
}

export interface ConsolePayload<T> {
  data?: T;
  meta?: { fallback?: boolean } & Record<string, unknown>;
}

export interface ConsoleEnvelope<T> {
  data?: ConsolePayload<T>;
  error?: ConsoleEnvelopeError;
  requestId?: string;
}

export type ConsoleResult<T> =
  | { kind: 'data'; value: T | null }
  | { kind: 'fallback' }
  | { kind: 'error'; code: string; message: string };

export function readConsole<T>(body: ConsoleEnvelope<T> | null | undefined): ConsoleResult<T> {
  if (body === null || body === undefined) {
    return { kind: 'error', code: 'INTERNAL_ERROR', message: 'The console returned no response.' };
  }

  if (body.error) {
    return {
      kind: 'error',
      code: body.error.code || 'INTERNAL_ERROR',
      message: body.error.message || 'The request failed.',
    };
  }

  const payload = body.data;
  // `fallback: true` is the BFF's "backend not configured" signal. It must be
  // checked before the payload so an unconfigured backend never renders as an
  // empty-but-successful result.
  if (payload?.meta?.fallback === true) {
    return { kind: 'fallback' };
  }

  return { kind: 'data', value: (payload?.data ?? null) as T | null };
}
