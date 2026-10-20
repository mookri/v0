// Cloudflare Worker adapter (plan B3b): the SAME collector contract and
// validator module as the Vercel Edge route — swapped in by configuration
// (decision #5). Everything interesting lives in
// packages/analytics/src/collector/pipeline; this file only adapts
// Cloudflare's fetch handler onto it.
//
// `waitUntil()` is deliberately NOT used to smuggle the forward past the
// response: it is not a durable queue, and the pipeline already awaits the
// ingestion durable-acceptance ack before returning 200.

import { isSignalSource, type SignalSource } from '../../../../packages/analytics/src/contracts/signal-source';
import { MAX_BATCH_BODY_BYTES } from '../../../../packages/analytics/src/collector/contract';
import { handleCollectorRequest, type CollectorDeps } from '../../../../packages/analytics/src/collector/pipeline';
import { FixedWindowRateLimiter } from '../../../../packages/analytics/src/collector/rate-limit';

/** Cloudflare-injected bindings (wrangler secret / var per wrangler.toml). */
export interface AnalyticsCollectorEnv {
  SIGNALS_HMAC_SECRET?: string;
  SIGNALS_CONSENT_TOKEN_SECRET?: string;
  SIGNALS_INGEST_URL?: string;
  /** Provenance override; invalid or unset → `shadow` while comparing (B3). */
  SIGNALS_SOURCE_DEFAULT?: string;
}

/** Test seam: clock / nonce / transport injected, never stubbed globals. */
export interface WorkerOverrides {
  fetchImpl?: CollectorDeps['fetchImpl'];
  nowSec?: () => number;
  nonce?: () => string;
}

// Per-isolate fixed windows (same numbers as the Edge route — same contract).
const ipLimiter = new FixedWindowRateLimiter({ limit: 120, windowMs: 60_000, maxKeys: 50_000 });
const sessionLimiter = new FixedWindowRateLimiter({ limit: 60, windowMs: 60_000, maxKeys: 50_000 });

function clientIp(headers: Headers): string {
  // Cloudflare sets CF-Connecting-IP; x-forwarded-for is the local-dev path.
  const forwardedFirst = headers.get('x-forwarded-for')?.split(',')[0]?.trim();
  return headers.get('cf-connecting-ip') ?? (forwardedFirst && forwardedFirst.length > 0 ? forwardedFirst : 'unknown');
}

export async function handleCollectorWorker(
  request: Request,
  env: AnalyticsCollectorEnv,
  overrides: WorkerOverrides = {},
): Promise<Response> {
  if (request.method !== 'POST') {
    return new Response(JSON.stringify({ ok: false, error: 'method_not_allowed' }), {
      status: 405,
      headers: { 'content-type': 'application/json', allow: 'POST' },
    });
  }

  const contentLength = request.headers.get('content-length');
  const declared = Number(contentLength ?? '');
  if (contentLength !== null && Number.isFinite(declared) && declared > MAX_BATCH_BODY_BYTES) {
    return new Response(
      JSON.stringify({ ok: false, error: 'payload_too_large', maxBytes: MAX_BATCH_BODY_BYTES }),
      { status: 413, headers: { 'content-type': 'application/json' } },
    );
  }

  const bodyText = await request.text();
  const configuredSource = env.SIGNALS_SOURCE_DEFAULT;
  const defaultSource: SignalSource = isSignalSource(configuredSource) ? configuredSource : 'shadow';

  const out = await handleCollectorRequest(
    { bodyText, contentLength, clientIp: clientIp(request.headers) },
    {
      nowSec: overrides.nowSec ?? (() => Math.floor(Date.now() / 1000)),
      consentSecret: env.SIGNALS_CONSENT_TOKEN_SECRET ?? null,
      hmacSecret: env.SIGNALS_HMAC_SECRET ?? null,
      ingestUrl: env.SIGNALS_INGEST_URL ?? null,
      fetchImpl: overrides.fetchImpl ?? ((url, init) => fetch(url, init)),
      nonce:
        overrides.nonce ??
        (() => globalThis.crypto?.randomUUID?.() ?? `${Date.now()}_${Math.random().toString(36).slice(2)}`),
      ipLimiter,
      sessionLimiter,
      defaultSource,
      onWarn: (message) => console.warn(message),
    },
  );

  return new Response(JSON.stringify(out.body), {
    status: out.status,
    headers: { 'content-type': 'application/json', ...out.headers },
  });
}

export default {
  fetch(request: Request, env: AnalyticsCollectorEnv): Promise<Response> {
    return handleCollectorWorker(request, env);
  },
};
