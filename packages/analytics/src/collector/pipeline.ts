// packages/analytics/src/collector/pipeline.ts
//
// The collector contract (plan B3, decisions #5/#6/#7) — ONE implementation
// behind BOTH edge adapters: the Next/Vercel Edge route
// (apps/web/app/api/ae/v1/batch/route.ts) and the Cloudflare Worker
// (services/workers/analytics-collector). Swapping adapters is a non-event.
//
// Stateless by rule: no journey state, no cohort logic, no business
// workflows, no in-memory "delivery". The only mutable state is whatever
// adapter-injected rate limiters hold (per-isolate, documented).
//
// Status map — the honesty rules, encoded:
//   413  body over 64 KiB                     → never partially read
//   400  batch/schema rejection naming the offender; ALL-OR-NOTHING —
//        valid events in a rejected batch are never silently accepted
//   403  no consent evidence (missing/invalid/expired token, or the token
//        says analytics=denied) → event NEVER forwarded
//   429  per-IP / per-session rate limit (Retry-After set)
//   200  ONLY after the ingestion service returned its durable-acceptance
//        ack (2xx from a real sink put) — never ack-on-scheduled-work
//   503  no durable ack (upstream 5xx, network error, ingestion 401 auth
//        misconfig, or missing local secrets) → retrying is safe: replay
//        is absorbed by insertId dedup at the sink.
//
// Delivery-policy enforcement (§0.3 rule 2): only events whose registry def
// is producer=client AND collector-eligible (allowedSinks includes
// collector_bigquery) AND permitted on the envelope's surface may enter
// through here. Server P0 events travel the outbox path; ad beacons are
// internal_only; gtm_ga4-only events belong to GTM (B4).

import { validateEnvelope, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { verifyConsentToken } from '@navdhya/analytics/src/contracts/consent';
import { isSignalSource, type SignalSource } from '@navdhya/analytics/src/contracts/signal-source';
import { EVENT_DEFS } from '@navdhya/analytics/src/registry/generated/event-types';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';
import { signCollectorForward } from './auth';
import {
  MAX_BATCH_BODY_BYTES,
  MAX_EVENTS_PER_BATCH,
  byteLength,
  stripForbiddenParams,
} from './contract';
import type { RateLimiterLike } from './rate-limit';

export interface CollectorRequest {
  /** Raw request body as text (adapters decide how they buffer it). */
  bodyText: string;
  /** Raw Content-Length header, checked before the body is read. */
  contentLength?: string | null;
  clientIp: string;
}

export interface CollectorResponse {
  status: number;
  body: Record<string, unknown>;
  headers?: Record<string, string>;
}

export interface UpstreamResponse {
  status: number;
  text(): Promise<string>;
}

export interface CollectorDeps {
  /** Unix seconds — drives HMAC timestamps and consent expiry. */
  nowSec(): number;
  /** Server-only secret; null (unset env) → fail-closed 503. */
  consentSecret: string | null;
  /** Server-only secret; null (unset env) → fail-closed 503. */
  hmacSecret: string | null;
  /** Ingestion endpoint; null (unset env) → fail-closed 503. */
  ingestUrl: string | null;
  fetchImpl(url: string, init: { method: string; headers: Record<string, string>; body: string }): Promise<UpstreamResponse>;
  /** Fresh nonce per forward (replay protection at ingestion). */
  nonce(): string;
  ipLimiter: RateLimiterLike;
  sessionLimiter: RateLimiterLike;
  /** Provenance stamped on forwarded rows; B3 default is `shadow`. */
  defaultSource?: SignalSource;
  onWarn?: (message: string) => void;
}

interface RejectedEntry {
  index: number;
  name?: unknown;
  errors: string[];
}

function json(body: Record<string, unknown>, status: number, headers?: Record<string, string>): CollectorResponse {
  return headers ? { status, body, headers } : { status, body };
}

function rateLimited(resetAtMs: number, nowMs: number, retryAfterSec: number): CollectorResponse {
  const wait = retryAfterSec > 0 ? retryAfterSec : Math.max(1, Math.ceil((resetAtMs - nowMs) / 1000));
  return json({ ok: false, error: 'rate_limited' }, 429, { 'Retry-After': String(wait) });
}

/** Registry-level eligibility for entering via the browser collector. */
function eligibilityErrors(name: string, envelope: SignalEnvelope): string[] {
  const def = (EVENT_DEFS as Record<string, (typeof EVENT_DEFS)[keyof typeof EVENT_DEFS] | undefined>)[name];
  if (!def) return [`registry: unknown event "${name}"`];
  const errors: string[] = [];
  if (def.producer !== 'client') {
    // P0 is a backend reliability category (§0.3 rule 4): a browser batch
    // may never carry server-produced truth.
    errors.push(`registry: event "${name}" is server-produced and may not enter via the collector`);
  }
  if (!def.allowedSinks.includes('collector_bigquery')) {
    errors.push(`registry: event "${name}" is not collector-eligible (allowedSinks: ${def.allowedSinks.join(', ')})`);
  }
  if (!def.surfaces.includes(envelope.surface)) {
    errors.push(`registry: event "${name}" not permitted on surface "${envelope.surface}"`);
  }
  return errors;
}

export async function handleCollectorRequest(
  input: CollectorRequest,
  deps: CollectorDeps,
): Promise<CollectorResponse> {
  // ── Configuration gate: no secrets → no consent evidence → no collection.
  if (!deps.consentSecret || !deps.hmacSecret || !deps.ingestUrl) {
    deps.onWarn?.('collector: missing SIGNALS_* configuration; refusing all batches');
    return json({ ok: false, error: 'collector_misconfigured' }, 503);
  }

  // ── 64 KiB cap (header fast-path, then actual bytes — never partial).
  const declared = Number(input.contentLength ?? '');
  if (Number.isFinite(declared) && declared > MAX_BATCH_BODY_BYTES) {
    return json({ ok: false, error: 'payload_too_large', maxBytes: MAX_BATCH_BODY_BYTES }, 413);
  }
  if (byteLength(input.bodyText) > MAX_BATCH_BODY_BYTES) {
    return json({ ok: false, error: 'payload_too_large', maxBytes: MAX_BATCH_BODY_BYTES }, 413);
  }

  // ── Per-IP limit before JSON parse (cheapest first).
  const nowMs = deps.nowSec() * 1000;
  const ipDecision = deps.ipLimiter.check(input.clientIp || 'unknown');
  if (!ipDecision.ok) return rateLimited(ipDecision.resetAtMs, nowMs, ipDecision.retryAfterSec);

  // ── Parse + shape.
  let parsed: unknown;
  try {
    parsed = JSON.parse(input.bodyText);
  } catch {
    return json({ ok: false, error: 'invalid_json' }, 400);
  }
  const batch = parsed as { events?: unknown; consentToken?: unknown; source?: unknown } | null;
  if (typeof batch !== 'object' || batch === null || !Array.isArray(batch.events)) {
    return json({ ok: false, error: 'invalid_batch', expected: '{ events: [...], consentToken, source }' }, 400);
  }
  const events = batch.events;
  if (events.length === 0) {
    return json({ ok: false, error: 'invalid_batch', reason: 'empty batch' }, 400);
  }
  if (events.length > MAX_EVENTS_PER_BATCH) {
    return json(
      { ok: false, error: 'batch_too_large', count: events.length, max: MAX_EVENTS_PER_BATCH },
      400,
    );
  }

  // ── Per-session limit (session id of the first envelope; malformed
  // sessions are rejected by validation below and cost at most one bucket).
  const firstSession = (events[0] as { session?: { id?: unknown } } | null)?.session;
  const sessionId = typeof firstSession?.id === 'string' && firstSession.id.length > 0 ? firstSession.id : null;
  if (sessionId !== null) {
    const sessionDecision = deps.sessionLimiter.check(sessionId);
    if (!sessionDecision.ok) return rateLimited(sessionDecision.resetAtMs, nowMs, sessionDecision.retryAfterSec);
  }

  // ── Consent (decision #7): the token IS the evidence; fail-closed 403.
  if (typeof batch.consentToken !== 'string' || batch.consentToken.length === 0) {
    return json({ ok: false, error: 'consent_required' }, 403);
  }
  const consent = await verifyConsentToken(batch.consentToken, deps.consentSecret, deps.nowSec());
  if (consent === null) {
    return json({ ok: false, error: 'consent_rejected' }, 403);
  }
  if (consent.analytics !== true) {
    return json({ ok: false, error: 'consent_denied' }, 403);
  }

  // ── Source provenance (B3: shadow while comparing).
  let source: SignalSource = deps.defaultSource ?? 'shadow';
  if (batch.source !== undefined && batch.source !== null) {
    if (!isSignalSource(batch.source)) {
      return json({ ok: false, error: 'invalid_source', allowed: ['first_party', 'server', 'shadow', 'ga4_export'] }, 400);
    }
    source = batch.source;
  }

  // ── Data minimization: strip forbidden params BEFORE validation.
  const strip = stripForbiddenParams(events);

  // ── Per-event validation: ALL-OR-NOTHING with the offender named.
  const rejected: RejectedEntry[] = [];
  const valid: SignalEnvelope[] = [];
  strip.events.forEach((candidate, index) => {
    const structural = validateEnvelope(candidate);
    if (structural.length > 0) {
      rejected.push({ index, name: (candidate as { name?: unknown } | null)?.name, errors: structural });
      return;
    }
    const envelope = candidate as SignalEnvelope;
    const errors = [
      ...eligibilityErrors(envelope.name, envelope),
      ...validateEventParams(envelope.name, envelope.params),
    ];
    if (errors.length > 0) {
      rejected.push({ index, name: envelope.name, errors });
      return;
    }
    valid.push(envelope);
  });
  if (rejected.length > 0) {
    // Valid batchmates are NOT forwarded (documented all-or-nothing): the
    // sender learns exactly what to drop and retries the rest as a new batch.
    return json({ ok: false, error: 'invalid_events', rejected, deferred: valid.length }, 400);
  }

  // ── Forward: signed, awaited, honest about the ack.
  const forwardBody = JSON.stringify({ events: valid });
  const authHeader = await signCollectorForward(deps.hmacSecret, forwardBody, {
    timestampSec: deps.nowSec(),
    nonce: deps.nonce(),
  });

  let upstream: UpstreamResponse;
  try {
    upstream = await deps.fetchImpl(deps.ingestUrl, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'x-signals-auth': authHeader,
        'x-signals-source': source,
      },
      body: forwardBody,
    });
  } catch (err) {
    deps.onWarn?.(`collector: forward failed (${String(err)}) → 503, client may retry`);
    return json({ ok: false, error: 'upstream_unavailable' }, 503);
  }

  let upstreamText = '';
  try {
    upstreamText = await upstream.text();
  } catch {
    upstreamText = '';
  }

  if (upstream.status === 200) {
    let persisted: number | undefined;
    try {
      const parsedUpstream = JSON.parse(upstreamText) as { persisted?: unknown };
      if (typeof parsedUpstream.persisted === 'number') persisted = parsedUpstream.persisted;
    } catch { /* body shape is advisory — the 200 itself is the durable ack */ }
    return json({ ok: true, persisted, source, stripped: strip.strippedKeys.length }, 200);
  }
  if (upstream.status === 400) {
    // Schema rejection at ingestion (belt and braces — the collector already
    // validated). Pass the offender through so the client can drop it.
    try {
      const parsedUpstream = JSON.parse(upstreamText) as Record<string, unknown>;
      return json({ ok: false, error: 'invalid_events', upstream: parsedUpstream }, 400);
    } catch {
      return json({ ok: false, error: 'upstream_rejected' }, 400);
    }
  }
  if (upstream.status === 401) {
    // Our signing secret disagrees with ingestion's — deployment problem,
    // never a client problem. Never ack.
    deps.onWarn?.('collector: ingestion rejected auth (SIGNALS_HMAC_SECRET mismatch?) → 503');
    return json({ ok: false, error: 'upstream_auth_failed' }, 503);
  }
  deps.onWarn?.(`collector: upstream ${upstream.status} → 503, no durable ack`);
  return json({ ok: false, error: 'upstream_unavailable' }, 503);
}
