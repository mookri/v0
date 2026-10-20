// POST /api/ae/v1/batch — interim first-party collector (plan B3a,
// decision #5: Vercel Edge Function behind the shared collector contract;
// the Cloudflare Worker at services/workers/analytics-collector implements
// the SAME pipeline, so the later swap is a non-event).
//
// Thin adapter ONLY: buffer body → env + per-isolate limiters → delegate to
// handleCollectorRequest → map to Response. The pipeline awaits the
// ingestion durable-acceptance ack before returning 200 — never
// ack-on-scheduled-background-work (no waitUntil, no queue hand-wave).

import { MAX_BATCH_BODY_BYTES } from '@navdhya/analytics/src/collector/contract';
import { handleCollectorRequest } from '@navdhya/analytics/src/collector/pipeline';
import { FixedWindowRateLimiter } from '@navdhya/analytics/src/collector/rate-limit';

// Per-isolate fixed windows: edge isolates do not share bucket state
// (documented best-effort layer; the ingestion HMAC replay window and
// per-batch caps remain the hard backstop).
const ipLimiter = new FixedWindowRateLimiter({ limit: 120, windowMs: 60_000, maxKeys: 50_000 });
const sessionLimiter = new FixedWindowRateLimiter({ limit: 60, windowMs: 60_000, maxKeys: 50_000 });

function clientIp(headers: Headers): string {
  const forwarded = headers.get('x-forwarded-for');
  const first = forwarded?.split(',')[0]?.trim();
  if (first) return first;
  return headers.get('x-real-ip') ?? 'unknown';
}

export async function POST(request: Request): Promise<Response> {
  // Content-Length fast path: refuse before buffering a huge body.
  const contentLength = request.headers.get('content-length');
  const declared = Number(contentLength ?? '');
  if (contentLength !== null && Number.isFinite(declared) && declared > MAX_BATCH_BODY_BYTES) {
    return Response.json(
      { ok: false, error: 'payload_too_large', maxBytes: MAX_BATCH_BODY_BYTES },
      { status: 413 },
    );
  }

  const bodyText = await request.text();
  const out = await handleCollectorRequest(
    { bodyText, contentLength, clientIp: clientIp(request.headers) },
    {
      nowSec: () => Math.floor(Date.now() / 1000),
      // Server-only secrets (never NEXT_PUBLIC_). Unset → pipeline fails
      // closed with 503 — no secrets means no consent evidence means no
      // collection.
      consentSecret: process.env.SIGNALS_CONSENT_TOKEN_SECRET ?? null,
      hmacSecret: process.env.SIGNALS_HMAC_SECRET ?? null,
      ingestUrl: process.env.SIGNALS_INGEST_URL ?? null,
      fetchImpl: (url, init) => fetch(url, init),
      nonce: () =>
        globalThis.crypto?.randomUUID?.() ??
        `${Date.now()}_${Math.random().toString(36).slice(2)}`,
      ipLimiter,
      sessionLimiter,
      // B3 shadow comparison: rows enter as `shadow` provenance until the
      // comparison passes (then flip to first_party — plan B5).
      defaultSource: 'shadow',
      onWarn: (message) => console.warn(message),
    },
  );

  return new Response(JSON.stringify(out.body), {
    status: out.status,
    headers: { 'content-type': 'application/json', ...out.headers },
  });
}
