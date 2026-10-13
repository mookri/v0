// packages/analytics/src/client/analytics-client.ts
//
// Signals SDK (plan B1, completed in B3). Interface per architecture §4.2:
//   track(name, params) / flush() — no-op-safe when disabled (the pattern the
//   old PostHog stub got right), fail-closed without consent, registry-validated
//   (unknown events and unknown params are rejected — allowlists only).
//
// Delivery paths (plan B3):
//   - PRIMARY: fetch with positive-ack. Events leave the outbox ONLY after
//     the collector answers 200 (which itself is only returned after the
//     ingestion durable-acceptance ack). Failures keep events queued.
//   - UNLOAD: navigator.sendBeacon, best-effort ONLY. sendBeacon cannot
//     acknowledge, so it never removes events and its sends are counted in
//     `beaconBestEffort` — never in the client-delivery SLO denominator
//     (eligible-attempted = primary-path attempts, §0.3 rule 6).
//
// Honesty rules enforced here:
//   - P0 / server-produced events are NEVER accepted from the browser — the
//     outbox queue only ever holds P1..P3. Revenue truth is a backend category.
//   - Every batch carries the per-batch consent token (decision #7) and the
//     provenance `source` (shadow while comparing, B3).
//   - The delivery metric is acknowledged ÷ eligible-attempted — measured
//     over what the SDK actually attempted, never "all events ever created".

import {
  newEventId,
  type Breadcrumb,
  type EnvelopeActor,
  type SignalEnvelope,
  type Surface,
} from '@navdhya/analytics/src/contracts/envelope';
import { analyticsAllowed, UNKNOWN_CONSENT, type ConsentStatus } from '@navdhya/analytics/src/contracts/consent';
import type { ClientSource } from '@navdhya/analytics/src/contracts/signal-source';
import { EVENT_DEFS, type EventName } from '@navdhya/analytics/src/registry/generated/event-types';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';
import { Breadcrumbs } from './breadcrumbs';
import { createDefaultOutbox, type Outbox } from './outbox';

export interface FlushResult {
  sent: number;
  failed: number;
}

/**
 * Client-delivery metrics (§0.3 rule 6): the SLO denominator is
 * eligible-attempted events — the primary path's real attempts — never
 * "all events ever generated in the browser". Beacon sends are recorded
 * separately and excluded from the denominator (no ack is possible).
 */
export interface DeliveryMetrics {
  eligibleAttempted: number;
  acknowledged: number;
  failed: number;
  /** Unload best-effort sends — measured separately, never in the SLO. */
  beaconBestEffort: number;
  /** Events shed by the queue-bound eviction ladder (P3 → P2 → P1). */
  shed: number;
}

export interface AnalyticsClientConfig {
  /** Global kill switch. Default false: no keys/flag → silent no-op. */
  enabled?: boolean;
  surface: Surface;
  appVersion: string;
  /** First-party collector endpoint (B3a: /api/ae/v1/batch). */
  collectorUrl?: string;
  outbox?: Outbox;
  send?: (batch: SignalEnvelope[]) => Promise<void>;
  /** Fail-closed default: UNKNOWN consent blocks everything. */
  consent?: () => ConsentStatus;
  /** Route template provider; query strings are stripped defensively. */
  routeTemplate?: () => string;
  /** Actor provider — uidHash only if the app has one; never raw ids. */
  actor?: () => EnvelopeActor;
  maxBatch?: number;
  maxQueue?: number;
  /** Provenance stamped on batches. B3 default: `shadow` (comparison mode);
   *  flip to `first_party` when shadow validation passes (plan B5). */
  source?: ClientSource;
  /** Server-issued per-batch consent token provider (decision #7). Without a
   *  token the collector 403s and batches stay queued — fail-closed. */
  getConsentToken?: () => string | null | Promise<string | null>;
  /** Unload transport override; defaults to navigator.sendBeacon. */
  beacon?: (url: string, body: string) => boolean;
  /** Auto-register the pagehide flush when a window exists. Default true. */
  autoBeacon?: boolean;
  now?: () => number;
  newId?: () => string;
  newSessionId?: () => string;
  onWarn?: (message: string) => void;
}

export interface SignalsClient {
  /** Returns true when the event was accepted into the outbox. */
  track(name: EventName | (string & {}), params?: Record<string, string | number | boolean>): boolean;
  /** Peek → send → ack. Events that fail to send REMAIN queued. */
  flush(): Promise<FlushResult>;
  /**
   * Unload-only best-effort delivery (plan B3). Cannot be acknowledged, so
   * events stay queued and the send counts ONLY in beaconBestEffort.
   * Returns the number of events offered to the beacon transport.
   */
  flushBeacon(): Promise<number>;
  /** Delivery counters (copy). */
  metrics(): DeliveryMetrics;
  /** acknowledged ÷ eligible-attempted; null before the first attempt. */
  deliveryRatio(): number | null;
  recordBreadcrumb(category: string, message: string, route?: string): void;
  snapshotBreadcrumbs(): Breadcrumb[];
  readonly sessionId: string;
  readonly enabled: boolean;
}

const MAX_DEFAULT_BATCH = 25;
const MAX_DEFAULT_QUEUE = 500;

export function createAnalyticsClient(config: AnalyticsClientConfig): SignalsClient {
  const enabled = config.enabled ?? false;
  const outbox = config.outbox ?? createDefaultOutbox();
  const now = config.now ?? Date.now;
  const newId = config.newId ?? (() => newEventId());
  const maxBatch = config.maxBatch ?? MAX_DEFAULT_BATCH;
  const maxQueue = config.maxQueue ?? MAX_DEFAULT_QUEUE;
  const sessionId = config.newSessionId?.() ?? `sess_${newEventId()}`;
  const breadcrumbs = new Breadcrumbs(50, now);
  const warn = config.onWarn ?? (() => {});
  const consent = config.consent ?? (() => UNKNOWN_CONSENT);
  const routeTemplate = config.routeTemplate ?? (() => '/');
  const actor = config.actor ?? ((): EnvelopeActor => ({ uidHash: null, anonId: null }));
  const source: ClientSource = config.source ?? 'shadow';
  const metrics: DeliveryMetrics = {
    eligibleAttempted: 0,
    acknowledged: 0,
    failed: 0,
    beaconBestEffort: 0,
    shed: 0,
  };

  let seq = 0;
  let consecutiveFailures = 0;

  /** Batch wire format: events + provenance + per-batch consent token. */
  async function batchBody(batch: SignalEnvelope[]): Promise<string> {
    const consentToken = (await config.getConsentToken?.()) ?? null;
    return JSON.stringify({
      events: batch,
      source,
      ...(consentToken !== null ? { consentToken } : {}),
    });
  }

  function defaultBeacon(url: string, body: string): boolean {
    if (typeof navigator === 'undefined' || typeof navigator.sendBeacon !== 'function') return false;
    try {
      return navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
    } catch {
      return false;
    }
  }

  async function defaultSend(batch: SignalEnvelope[]): Promise<void> {
    if (!config.collectorUrl) {
      return Promise.reject(new Error('signals: collectorUrl not configured'));
    }
    const res = await fetch(config.collectorUrl, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: await batchBody(batch),
    });
    if (!res.ok) throw new Error(`signals: collector responded ${res.status}`);
  }
  const send = config.send ?? defaultSend;

  function sanitizeRoute(): string {
    const r = routeTemplate();
    const cut = r.indexOf('?');
    return (cut >= 0 ? r.slice(0, cut) : r) || '/';
  }

  async function settleInFlight(): Promise<FlushResult> {
    const entries = await outbox.peek(maxBatch);
    if (entries.length === 0) return { sent: 0, failed: 0 };
    const ids = entries.map((e) => e.envelope.id);
    metrics.eligibleAttempted += ids.length; // SLO denominator: real attempts
    try {
      await send(entries.map((e) => e.envelope));
      await outbox.ack(ids);
      metrics.acknowledged += ids.length;
      consecutiveFailures = 0;
      return { sent: ids.length, failed: 0 };
    } catch (err) {
      consecutiveFailures += 1;
      metrics.failed += ids.length;
      warn(`signals: flush failed (${String(err)}); ${ids.length} event(s) remain queued`);
      return { sent: 0, failed: ids.length };
    }
  }

  const client: SignalsClient = {
    sessionId,
    enabled,

    track(name, params = {}): boolean {
      if (!enabled) return false;

      const def = (EVENT_DEFS as Record<string, (typeof EVENT_DEFS)[EventName] | undefined>)[name];
      if (!def) {
        warn(`signals: unknown event "${name}" rejected (registry allowlist)`);
        return false;
      }
      if (def.producer !== 'client') {
        // P0 / server-owned events are a backend reliability category.
        warn(`signals: "${name}" is server-produced; browser emission rejected`);
        return false;
      }
      if (!def.surfaces.includes(config.surface)) {
        warn(`signals: "${name}" not permitted on surface "${config.surface}"`);
        return false;
      }
      if (!analyticsAllowed(consent())) {
        return false; // fail-closed: no consent evidence → no payload
      }

      const paramErrors = validateEventParams(name, params);
      if (paramErrors.length > 0) {
        warn(`signals: "${name}" rejected — ${paramErrors.join('; ')}`);
        return false;
      }

      seq += 1;
      const envelope: SignalEnvelope = {
        id: newId(),
        name,
        version: def.version,
        occurredAt: new Date(now()).toISOString(),
        surface: config.surface,
        appVersion: config.appVersion,
        session: { id: sessionId, seq },
        actor: actor(),
        consent: { analytics: consent().analytics === 'GRANTED', ads: consent().ads === 'GRANTED' },
        context: { routeTemplate: sanitizeRoute() },
        params: { ...params },
      };
      if (def.allowsBreadcrumbs === true) {
        envelope.breadcrumbs = breadcrumbs.drain();
      }

      void outbox
        .enqueue({ envelope, priority: def.priority, attempts: 0, enqueuedAt: now() })
        .then(() => outbox.shedTo(maxQueue))
        .then((removed) => {
          if (removed > 0) {
            metrics.shed += removed;
            warn(`signals: queue over ${maxQueue}; shed ${removed} lower-priority event(s)`);
          }
        })
        .catch((err) => warn(`signals: enqueue failed: ${String(err)}`));

      return true;
    },

    async flush(): Promise<FlushResult> {
      if (!enabled) return { sent: 0, failed: 0 };
      return settleInFlight();
    },

    /**
     * Unload-only best-effort (plan B3): sendBeacon cannot acknowledge, so
     * NOTHING is acked or attempted here — events stay queued for the next
     * page (duplicates absorbed by insertId dedup at the sink) and the send
     * is counted only in beaconBestEffort, outside the delivery-SLO
     * denominator.
     */
    async flushBeacon(): Promise<number> {
      if (!enabled || !config.collectorUrl) return 0;
      const entries = await outbox.peek(maxBatch);
      if (entries.length === 0) return 0;
      const beacon = config.beacon ?? defaultBeacon;
      let body: string;
      try {
        body = await batchBody(entries.map((e) => e.envelope));
      } catch (err) {
        warn(`signals: beacon body failed: ${String(err)}`);
        return 0;
      }
      let accepted = false;
      try {
        accepted = beacon(config.collectorUrl, body);
      } catch (err) {
        warn(`signals: beacon failed: ${String(err)}`);
        accepted = false;
      }
      if (accepted) metrics.beaconBestEffort += entries.length;
      return accepted ? entries.length : 0;
    },

    metrics(): DeliveryMetrics {
      return { ...metrics };
    },

    deliveryRatio(): number | null {
      if (metrics.eligibleAttempted === 0) return null; // no division by hope
      return metrics.acknowledged / metrics.eligibleAttempted;
    },

    recordBreadcrumb(category, message, route): void {
      if (!enabled) return;
      breadcrumbs.record(route !== undefined ? { category, message, route } : { category, message });
    },

    snapshotBreadcrumbs(): Breadcrumb[] {
      return breadcrumbs.snapshot();
    },
  };

  // Unload wiring: `pagehide` fires reliably on mobile navigation and tab
  // eviction; guarded so SSR/tests without a window never see it. The flush
  // is best-effort by construction (see flushBeacon).
  if ((config.autoBeacon ?? true) && typeof window !== 'undefined' && typeof window.addEventListener === 'function') {
    window.addEventListener('pagehide', () => {
      void client.flushBeacon();
    });
  }

  return client;
}
