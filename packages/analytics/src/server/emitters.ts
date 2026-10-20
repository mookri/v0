// Server-side P0 emitters (plan B2) — the ONLY sanctioned way for backend
// code to create Signals outbox records. Deep-import path
// (`@navdhya/analytics/src/server/emitters`); NOT re-exported from the package
// index so browser bundles never pull server emission code.
//
// Contract:
//   - Emits ONLY registry events whose producer === 'server' (P0). Browser
//     emission of these events is rejected by the SDK; this module is the
//     counterpart gate on the server side.
//   - Payloads are fully-formed SignalEnvelopes, self-validated against the
//     GENERATED validator before append — a bad payload throws (program
//     error), it never reaches the outbox.
//   - Idempotency: envelope ID = outbox record ID (ULID), so stores dedup
//     re-appends and the whole chain (outbox → Pub/Sub → ingestion →
//     signals_raw) keys on one stable ID.
//   - Consent is RECORDED, not enforced: server truth flows to the internal
//     stream regardless; GA4 sink adapters (B4) apply consent at the tag
//     layer. Default is fail-closed unknown (false/false).
//   - Browser death cannot lose these: the caller appends through the
//     tx-bound store (TransactionContext.outbox), never directly.

import type { OutboxAppend, OutboxStore } from '@navdhya/contracts/src/messaging/outbox';
import { newEventId, type SignalEnvelope, type Surface } from '@navdhya/analytics/src/contracts/envelope';
import { EVENT_DEFS, type EventName, type EventParams } from '@navdhya/analytics/src/registry/generated/event-types';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';

export interface ServerEmitContext {
  /** Business aggregate this signal belongs to (payment.id / booking.id). */
  aggregateId: string;
  /** Originating surface; unknown at server boundaries → defaults to 'web'. */
  surface?: Surface;
  appVersion?: string;
  nowMs?: number;
  /** Observed consent state; default UNKNOWN = fail-closed false/false. */
  consent?: { analytics: boolean; ads: boolean };
}

export type PaymentSucceededParams = EventParams<'payment_succeeded'>;
export type PaymentFailedParams = EventParams<'payment_failed'>;
export type BookingConfirmedParams = EventParams<'booking_confirmed'>;

function buildServerEnvelope<E extends EventName>(
  name: E,
  ctx: ServerEmitContext,
  params: EventParams<E>,
): SignalEnvelope {
  const def = EVENT_DEFS[name];
  if (def.producer !== 'server') {
    throw new Error(`signals emit: "${name}" is not server-produced — client events belong in the SDK`);
  }
  const errors = validateEventParams(name, params as Record<string, unknown>);
  if (errors.length > 0) {
    throw new Error(`signals emit: "${name}" rejected — ${errors.join('; ')}`);
  }
  const consent = ctx.consent ?? { analytics: false, ads: false };
  return {
    id: newEventId(),
    name,
    version: def.version,
    occurredAt: new Date(ctx.nowMs ?? Date.now()).toISOString(),
    surface: ctx.surface ?? 'web',
    appVersion: ctx.appVersion ?? 'server',
    // Deterministic synthetic session: server events are not user sessions,
    // but the envelope contract requires a session; unique per (type,
    // aggregate) so (session.id, seq) never collides across records.
    session: { id: `srv_${name}_${ctx.aggregateId}`, seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: consent.analytics, ads: consent.ads },
    // No browser route exists server-side; contract requires a path, '/' is
    // the honest value (never a query string).
    context: { routeTemplate: '/' },
    params: { ...params },
  };
}

/** Wrap a signal envelope as the outbox append payload (stable ID = ULID). */
export function signalOutboxRecord(envelope: SignalEnvelope, aggregateId: string): OutboxAppend {
  return {
    id: envelope.id,
    aggregateId,
    type: envelope.name,
    payload: envelope,
    occurredAt: envelope.occurredAt,
  };
}

export async function emitPaymentSucceeded(
  outbox: OutboxStore,
  ctx: ServerEmitContext,
  params: PaymentSucceededParams,
): Promise<SignalEnvelope> {
  const envelope = buildServerEnvelope('payment_succeeded', ctx, params);
  await outbox.append(signalOutboxRecord(envelope, ctx.aggregateId));
  return envelope;
}

export async function emitPaymentFailed(
  outbox: OutboxStore,
  ctx: ServerEmitContext,
  params: PaymentFailedParams,
): Promise<SignalEnvelope> {
  const envelope = buildServerEnvelope('payment_failed', ctx, params);
  await outbox.append(signalOutboxRecord(envelope, ctx.aggregateId));
  return envelope;
}

export async function emitBookingConfirmed(
  outbox: OutboxStore,
  ctx: ServerEmitContext,
  params: BookingConfirmedParams,
): Promise<SignalEnvelope> {
  const envelope = buildServerEnvelope('booking_confirmed', ctx, params);
  await outbox.append(signalOutboxRecord(envelope, ctx.aggregateId));
  return envelope;
}
