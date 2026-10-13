// packages/analytics/src/adapters/ga4-measurement-protocol.ts
//
// GA4 Measurement Protocol adapter for SERVER-owned events (plan B4, item 3).
// The MP usage rules are ENCODED here, not documented as hopes:
//   - ≤25 events/request, wire name ≤40 chars, ≤25 params per event
//     (Google's documented request bounds);
//   - `2xx` from MP is a RECEIPT ONLY — the bytes arrived. "Processed" is
//     never claimable from MP (§0.3 rule 1); GA4-side ingestion lag and
//     BigQuery-export lag stay separate honesty questions;
//   - only events whose registry def allows the `server_ga4` sink may go
//     through MP (gtm_ga4 events belong to GTM, collector events to the
//     collector — no event reaches one destination by two paths);
//   - consent.analytics must be true on the envelope: the private warehouse
//     keeps backend truth regardless, but GA4 is an EXTERNAL reporting
//     destination and stays fail-closed without consent;
//   - valid client/session context is REQUIRED and never invented: the
//     sending boundary supplies the pseudonymous client_id (its identity
//     mapping), the adapter refuses batches it cannot anchor.
//
// Wire-name mapping: `payment_succeeded` reports to GA4 as `purchase` (the
// GA4 ecommerce canonical). The registry name differs ON PURPOSE — browser
// `purchase_viewed` and server `purchase` are different events that must
// never collapse into one another (§0.3). Any new server_ga4 event must be
// consciously added to MP_WIRE_NAME (coverage is pinned by a test).

import { validateEnvelope, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { EVENT_DEFS } from '@navdhya/analytics/src/registry/generated/event-types';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';

export const MP_LIMITS = {
  maxEventsPerRequest: 25,
  maxNameLength: 40,
  maxParamsPerEvent: 25,
} as const;

/** Registry name → GA4 wire name. Only server_ga4-eligible events appear. */
export const MP_WIRE_NAME: Readonly<Record<string, string>> = {
  payment_succeeded: 'purchase',
  payment_failed: 'payment_failed',
  booking_confirmed: 'booking_confirmed',
};

export interface MpEvent {
  name: string;
  params: Record<string, string | number | boolean>;
}

export interface MpBatch {
  client_id: string;
  events: MpEvent[];
}

export interface MpBuildOptions {
  /**
   * The sending boundary's identity mapping — the ONLY place a GA4
   * client_id may come from. Return null when the envelope cannot be
   * anchored (the batch is then rejected, fail-closed).
   */
  resolveClientId(envelope: SignalEnvelope): string | null;
  engagementTimeMsec?: number;
}

/**
 * Default resolver: use the envelope's pseudonymous actor anchors when a
 * future emitter populates them. Server envelopes today are actor-less, so
 * real senders pass their own resolver.
 */
export function defaultMpClientIdResolver(envelope: SignalEnvelope): string | null {
  return envelope.actor.uidHash ?? envelope.actor.anonId;
}

/** Build a GA4 MP batch, fail-closed: ANY violation rejects the WHOLE batch. */
export function buildMpBatch(envelopes: SignalEnvelope[], options: MpBuildOptions): MpBatch {
  if (envelopes.length === 0) throw new Error('mp: empty batch');
  if (envelopes.length > MP_LIMITS.maxEventsPerRequest) {
    throw new Error(`mp: ${envelopes.length} events exceed the ${MP_LIMITS.maxEventsPerRequest}/request bound`);
  }

  const events: MpEvent[] = [];
  let clientId: string | null = null;

  for (const envelope of envelopes) {
    const structural = validateEnvelope(envelope);
    if (structural.length > 0) throw new Error(`mp: invalid envelope — ${structural.join('; ')}`);

    const def = EVENT_DEFS[envelope.name as keyof typeof EVENT_DEFS];
    if (!def || !def.allowedSinks.includes('server_ga4')) {
      throw new Error(
        `mp: event "${envelope.name}" is not server_ga4-eligible (allowedSinks: ${def ? def.allowedSinks.join(', ') : 'unregistered'})`,
      );
    }
    if (envelope.consent.analytics !== true) {
      // GA4 is an external destination; no consent evidence → no MP.
      throw new Error(`mp: event "${envelope.name}" lacks analytics consent — GA4 delivery refused`);
    }

    const schemaErrors = validateEventParams(envelope.name, envelope.params as Record<string, unknown>);
    if (schemaErrors.length > 0) throw new Error(`mp: "${envelope.name}" — ${schemaErrors.join('; ')}`);

    const wireName = MP_WIRE_NAME[envelope.name];
    if (wireName === undefined) {
      throw new Error(`mp: server_ga4 event "${envelope.name}" has no governed GA4 wire name — add one to MP_WIRE_NAME`);
    }
    if (wireName.length > MP_LIMITS.maxNameLength) {
      throw new Error(`mp: wire name "${wireName}" exceeds ${MP_LIMITS.maxNameLength} chars`);
    }

    const resolved = options.resolveClientId(envelope);
    if (resolved !== null && clientId !== null && resolved !== clientId) {
      throw new Error('mp: batch mixes multiple client identities — one client_id per request');
    }
    if (resolved === null) {
      throw new Error(`mp: no client context for "${envelope.name}" — refusing (client_id is never invented)`);
    }
    clientId = resolved;

    const params: Record<string, string | number | boolean> = {
      ...envelope.params,
      // Sessionization plumbing GA4 requires server-side:
      engagement_time_msec: String(options.engagementTimeMsec ?? 1),
      session_id: envelope.session.id,
    };
    if (Object.keys(params).length > MP_LIMITS.maxParamsPerEvent) {
      throw new Error(`mp: "${wireName}" exceeds ${MP_LIMITS.maxParamsPerEvent} params`);
    }

    events.push({ name: wireName, params });
  }

  return { client_id: clientId as string, events };
}

/** 2xx is a receipt — never "processed" (honesty rule encoded as a type). */
export type MpOutcome = 'receipt' | 'retryable' | 'rejected';

export function mpResponseOutcome(status: number): MpOutcome {
  if (status >= 200 && status < 300) return 'receipt';
  if (status === 429 || status >= 500) return 'retryable';
  return 'rejected';
}

export const MP_COLLECT_URL = 'https://www.google-analytics.com/mp/collect';
export const MP_DEBUG_URL = 'https://www.google-analytics.com/debug/mp/collect';

export interface MpSendDeps {
  measurementId: string;
  apiSecret: string;
  fetchImpl?: typeof fetch;
  /** Override for tests / regional endpoints. */
  endpoint?: string;
}

export interface MpSendResult {
  status: number;
  outcome: MpOutcome;
  /** True ONLY on a 2xx receipt. Never means "GA4 processed this". */
  receipt: boolean;
}

export async function sendMpBatch(batch: MpBatch, deps: MpSendDeps): Promise<MpSendResult> {
  const precheck = validateMpPayload(batch);
  if (precheck.length > 0) throw new Error(`mp: payload invalid — ${precheck.join('; ')}`);
  const fetchFn = deps.fetchImpl ?? fetch;
  const url =
    `${deps.endpoint ?? MP_COLLECT_URL}?measurement_id=${encodeURIComponent(deps.measurementId)}` +
    `&api_secret=${encodeURIComponent(deps.apiSecret)}`;
  const response = await fetchFn(url, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(batch),
  });
  const outcome = mpResponseOutcome(response.status);
  return { status: response.status, outcome, receipt: outcome === 'receipt' };
}

/**
 * Local spec validation (CI gate). The live counterpart is Google's debug
 * endpoint (MP_DEBUG_URL) — driven by tooling/scripts/validate-mp-endpoint.ts
 * when GA4 credentials are available (env-gated, like every other driver).
 */
export function validateMpPayload(batch: MpBatch): string[] {
  const errors: string[] = [];
  if (typeof batch.client_id !== 'string' || batch.client_id.length === 0) {
    errors.push('client_id required');
  }
  if (!Array.isArray(batch.events) || batch.events.length === 0) {
    errors.push('events required');
    return errors;
  }
  if (batch.events.length > MP_LIMITS.maxEventsPerRequest) {
    errors.push(`events exceed ${MP_LIMITS.maxEventsPerRequest}/request`);
  }
  for (const event of batch.events) {
    if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(event.name) || event.name.length > MP_LIMITS.maxNameLength) {
      errors.push(`invalid event name "${event.name}"`);
    }
    const params = event.params ?? {};
    if (Object.keys(params).length > MP_LIMITS.maxParamsPerEvent) {
      errors.push(`"${event.name}" exceeds ${MP_LIMITS.maxParamsPerEvent} params`);
    }
    if (typeof params['session_id'] !== 'string' && typeof params['session_id'] !== 'number') {
      errors.push(`"${event.name}" missing session_id`);
    }
    if (typeof params['engagement_time_msec'] !== 'string') {
      errors.push(`"${event.name}" missing engagement_time_msec`);
    }
    for (const [key, value] of Object.entries(params)) {
      if (!['string', 'number', 'boolean'].includes(typeof value)) {
        errors.push(`"${event.name}" param "${key}" is not a primitive`);
      }
    }
  }
  return errors;
}
