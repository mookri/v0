// packages/analytics/src/adapters/ga4-data-layer.ts
//
// Governed dataLayer for the GTM Web container (plan B4, items 1–2). This
// is the ONLY sanctioned way for browser code to hand events to GTM → GA4.
// It enforces the same delivery policy the collector enforces from the
// other side:
//   - registry event must exist — `purchase` does NOT (unknown event):
//     confirmation pages cannot re-fire the server's purchase through GTM;
//     `purchase_viewed` is their own, browser-owned event;
//   - allowedSinks must include `gtm_ga4` (server_ga4 and collector-only
//     events never reach GA4 through GTM);
//   - producer must be `client` — a page cannot push server truth;
//   - the GTM container is the WEB surface only;
//   - params are schema-validated and forbidden keys stripped — no
//     undeclared or free-text payload ever reaches a marketing tag.
//
// Consent (Consent Mode v2, "initialization before all tags"):
//   - the container loads with everything DENIED until the real consent
//     record drives an update — UNKNOWN counts as denied (fail-closed);
//   - grant/deny produces deterministic, testable dataLayer shapes.

import { stripForbiddenParams } from '@navdhya/analytics/src/collector/contract';
import type { ConsentStatus } from '@navdhya/analytics/src/contracts/consent';
import { EVENT_DEFS } from '@navdhya/analytics/src/registry/generated/event-types';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';

export type DataLayerPush = Record<string, unknown>;

export interface GtmPushOutcome {
  pushed: boolean;
  reason?: string;
}

/** Consent Mode v2 defaults — pushed before the container loads. */
export const CONSENT_MODE_DEFAULTS = {
  event: 'consent_default',
  analytics_storage: 'denied',
  ad_storage: 'denied',
  wait_for_update: 500,
} as const;

/** Deterministic grant/deny mapping; UNKNOWN → denied (fail-closed). */
export function consentModeUpdate(status: ConsentStatus): DataLayerPush {
  return {
    event: 'consent_update',
    analytics_storage: status.analytics === 'GRANTED' ? 'granted' : 'denied',
    ad_storage: status.ads === 'GRANTED' ? 'granted' : 'denied',
  };
}

/**
 * Push a registry event onto the dataLayer (GTM convention:
 * `{ event: <name>, ...params }` triggers tags bound to that event name).
 * Never throws — analytics must not break the user journey; rejections are
 * returned with the reason and surfaced through onWarn by callers.
 */
export function pushGtmEvent(
  dataLayer: DataLayerPush[],
  name: string,
  params: Record<string, string | number | boolean> = {},
): GtmPushOutcome {
  const def = EVENT_DEFS[name as keyof typeof EVENT_DEFS];
  if (!def) {
    return { pushed: false, reason: `gtm: unknown event "${name}" (registry-gated; purchase has no browser twin)` };
  }
  if (!def.allowedSinks.includes('gtm_ga4')) {
    return {
      pushed: false,
      reason: `gtm: event "${name}" is not GTM-eligible (allowedSinks: ${def.allowedSinks.join(', ')})`,
    };
  }
  if (def.producer !== 'client') {
    return { pushed: false, reason: `gtm: event "${name}" is server-produced — GA4 receives it via MP, not GTM` };
  }
  if (!def.surfaces.includes('web')) {
    return { pushed: false, reason: `gtm: event "${name}" is not permitted on the web surface` };
  }

  const stripped = stripForbiddenParams([{ params }]).events[0] as { params: Record<string, unknown> };
  const errors = validateEventParams(name, stripped.params);
  if (errors.length > 0) {
    return { pushed: false, reason: `gtm: "${name}" — ${errors.join('; ')}` };
  }

  dataLayer.push({ event: name, ...stripped.params });
  return { pushed: true };
}

const CONTAINER_ID = /^GTM-[A-Z0-9]+$/;

export interface GtmContainerUrls {
  /** External loader (CSP-friendly: no inline bootstrap script needed). */
  loaderUrl: string;
  /** No-JS fallback frame. */
  noscriptUrl: string;
}

export function gtmContainer(containerId: string): GtmContainerUrls {
  if (!CONTAINER_ID.test(containerId)) {
    throw new Error(`gtm: invalid container id "${containerId}" (expected GTM-XXXXXXX)`);
  }
  return {
    loaderUrl: `https://www.googletagmanager.com/gtm.js?id=${containerId}`,
    noscriptUrl: `https://www.googletagmanager.com/ns.html?id=${containerId}`,
  };
}
