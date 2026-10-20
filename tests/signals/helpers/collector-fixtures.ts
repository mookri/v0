// Shared fixtures for the B3 collector acceptance suite (tests/signals).

import {
  FixedWindowRateLimiter,
  type RateLimiterLike,
} from '@navdhya/analytics/src/collector/rate-limit';
import type { CollectorDeps, UpstreamResponse } from '@navdhya/analytics/src/collector/pipeline';
import { issueConsentToken } from '@navdhya/analytics/src/contracts/consent';
import { newEventId, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

export const COLLECTOR_SECRET = 'collector-acceptance-secret';

export function clientEnvelope(
  overrides: Partial<SignalEnvelope> & {
    params?: Record<string, string | number | boolean>;
  } = {},
): SignalEnvelope {
  return {
    id: overrides.id ?? newEventId(),
    name: overrides.name ?? 'page_view',
    version: overrides.version ?? 1,
    occurredAt: overrides.occurredAt ?? new Date().toISOString(),
    surface: overrides.surface ?? 'web',
    appVersion: overrides.appVersion ?? '1.0.0',
    session: overrides.session ?? { id: 'sess_acceptance', seq: 1 },
    actor: overrides.actor ?? { uidHash: null, anonId: null },
    consent: overrides.consent ?? { analytics: true, ads: false },
    context: overrides.context ?? { routeTemplate: '/' },
    params: overrides.params ?? { page_type: 'home' },
    ...(overrides.breadcrumbs !== undefined ? { breadcrumbs: overrides.breadcrumbs } : {}),
  };
}

export async function consentToken(
  secret = COLLECTOR_SECRET,
  opts: { analytics?: boolean; nowSec?: number } = {},
): Promise<string> {
  const nowSec = opts.nowSec ?? Math.floor(Date.now() / 1000);
  return issueConsentToken(
    {
      v: 1,
      sub: null,
      analytics: opts.analytics ?? true,
      ads: false,
      iat: nowSec - 60,
      exp: nowSec + 300,
    },
    secret,
  );
}

export function batchBody(
  events: unknown[],
  opts: { consentToken?: string; source?: string } = {},
): string {
  const token = opts.consentToken;
  return JSON.stringify({
    events,
    source: opts.source ?? 'shadow',
    ...(token !== undefined ? { consentToken: token } : {}),
  });
}

export interface CapturedForward {
  url: string;
  headers: Record<string, string>;
  body: string;
}

export interface CollectorHarness {
  deps: CollectorDeps;
  forwards: CapturedForward[];
  controls: {
    /** Make the next forward answer with this status/body. */
    failNextWith(status: number, body?: string): void;
    /** Make forwards throw (network failure) while true. */
    throwNext: boolean;
  };
}

/** Deps with captured forwards, unique nonces, and generous default limits. */
export function collectorDeps(overrides: Partial<CollectorDeps> = {}): CollectorHarness {
  const forwards: CapturedForward[] = [];
  const controls = {
    throwNext: false,
    failNextWith(status: number, body = '{}'): void {
      nextStatus = status;
      nextBody = body;
    },
  };
  let nextStatus: number | null = null;
  let nextBody = '{}';
  let nonceCounter = 0;

  const fetchImpl: CollectorDeps['fetchImpl'] = async (url, init) => {
    forwards.push({ url, headers: init.headers, body: init.body });
    if (controls.throwNext) throw new Error('network down');
    if (nextStatus !== null) {
      const status = nextStatus;
      const body = nextBody;
      nextStatus = null;
      return { status, text: async () => body };
    }
    const persisted = JSON.parse(init.body).events.length as number;
    return {
      status: 200,
      text: async () => JSON.stringify({ ok: true, persisted }),
    };
  };

  const deps: CollectorDeps = {
    nowSec: () => Math.floor(Date.now() / 1000),
    consentSecret: COLLECTOR_SECRET,
    hmacSecret: COLLECTOR_SECRET,
    ingestUrl: 'https://ingestion.test/ingest',
    fetchImpl,
    nonce: () => `nonce_${(nonceCounter += 1)}`,
    ipLimiter: new FixedWindowRateLimiter({ limit: 10_000, windowMs: 60_000 }),
    sessionLimiter: new FixedWindowRateLimiter({ limit: 10_000, windowMs: 60_000 }),
    defaultSource: 'shadow',
    onWarn: () => {},
    ...overrides,
  };

  return { deps, forwards, controls };
}

export function unlimitedLimiter(): RateLimiterLike {
  return new FixedWindowRateLimiter({ limit: 1_000_000, windowMs: 60_000 });
}

export function stubUpstream(status: number, body: string): UpstreamResponse {
  return { status, text: async () => body };
}
