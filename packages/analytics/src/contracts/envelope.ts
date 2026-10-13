// packages/analytics/src/contracts/envelope.ts
//
// Canonical transport contract for the first-party Signals pipeline.
// This is NOT a GA4 payload: each sink has an adapter that maps approved
// fields into its own schema. The trusted ingestion service adds
// `receivedAt` and processing metadata server-side.
//
// Honesty constraints (plan §0.3):
//   - `occurredAt` is client time — untrusted for business truth.
//   - `session.seq` is REQUIRED: it is the denominator for the
//     client-delivery metric (acknowledged ÷ eligible-attempted).
//   - `uidHash` is HMAC-pseudonymous, NOT anonymous. Keep it out of GA4.

export const SURFACES = ['web', 'customer', 'partner', 'dashboard'] as const;
export type Surface = (typeof SURFACES)[number];

/** Primitive param values only. Structured data has dedicated envelope fields. */
export type ParamValue = string | number | boolean;

export interface EnvelopeSession {
  id: string;
  /** Monotonic per session, starting at 1. Required (see header). */
  seq: number;
}

export interface EnvelopeActor {
  /** HMAC-hashed stable id. Internal analytical stream only; never GA4. */
  uidHash: string | null;
  anonId: string | null;
}

export interface EnvelopeConsent {
  analytics: boolean;
  ads: boolean;
}

export interface EnvelopeContext {
  /** Route *template* (e.g. "/pujas/[slug]") — never a raw URL with query string. */
  routeTemplate: string;
  utm?: Record<string, string>;
  experimentExposureId?: string;
}

/** Bounded local ring-buffer entry, attached only to `app_error` events. */
export interface Breadcrumb {
  /** Epoch ms. */
  at: number;
  category: string;
  message: string;
  route?: string;
}

export interface SignalEnvelope {
  /** ULID, generated once by the producing client — preserved end to end. */
  id: string;
  /** Registry-approved event name. */
  name: string;
  /** Payload schema version (registry-owned). */
  version: number;
  /** Client/producer clock. Untrusted for business truth. */
  occurredAt: string;
  surface: Surface;
  appVersion: string;
  session: EnvelopeSession;
  actor: EnvelopeActor;
  consent: EnvelopeConsent;
  context: EnvelopeContext;
  params: Record<string, ParamValue>;
  /** Only populated for events the registry allows it on (app_error). */
  breadcrumbs?: Breadcrumb[];
}

export const MAX_PARAMS = 25; // GA4 event-parameter limit
export const MAX_BREADCRUMBS = 50;

const EVENT_NAME_RE = /^[a-z][a-z0-9_]*$/;
const SURFACE_SET: ReadonlySet<string> = new Set(SURFACES);

/**
 * Structural envelope validation (registry-agnostic).
 * Returns an empty array when valid; one message per violation otherwise.
 */
export function validateEnvelope(x: unknown): string[] {
  const errors: string[] = [];
  if (typeof x !== 'object' || x === null || Array.isArray(x)) {
    return ['envelope: must be an object'];
  }
  const e = x as Record<string, unknown>;

  if (typeof e.id !== 'string' || e.id.length === 0 || e.id.length > 64) {
    errors.push('id: must be a non-empty string ≤ 64 chars');
  }
  if (typeof e.name !== 'string' || !EVENT_NAME_RE.test(e.name)) {
    errors.push('name: must be snake_case event name');
  }
  if (typeof e.version !== 'number' || !Number.isInteger(e.version) || e.version < 1) {
    errors.push('version: must be an integer ≥ 1');
  }
  if (typeof e.occurredAt !== 'string' || Number.isNaN(Date.parse(e.occurredAt))) {
    errors.push('occurredAt: must be a parseable ISO timestamp');
  }
  if (typeof e.surface !== 'string' || !SURFACE_SET.has(e.surface)) {
    errors.push(`surface: must be one of ${SURFACES.join(', ')}`);
  }
  if (typeof e.appVersion !== 'string' || e.appVersion.length === 0) {
    errors.push('appVersion: must be a non-empty string');
  }

  const session = e.session as Record<string, unknown> | undefined;
  if (typeof session !== 'object' || session === null) {
    errors.push('session: required');
  } else {
    if (typeof session.id !== 'string' || session.id.length === 0) {
      errors.push('session.id: must be a non-empty string');
    }
    if (typeof session.seq !== 'number' || !Number.isInteger(session.seq) || session.seq < 1) {
      errors.push('session.seq: required integer ≥ 1 (client-delivery metric denominator)');
    }
  }

  const actor = e.actor as Record<string, unknown> | undefined;
  if (typeof actor !== 'object' || actor === null) {
    errors.push('actor: required');
  } else {
    for (const k of ['uidHash', 'anonId'] as const) {
      const v = actor[k];
      if (v !== null && typeof v !== 'string') errors.push(`actor.${k}: must be string or null`);
    }
  }

  const consent = e.consent as Record<string, unknown> | undefined;
  if (typeof consent !== 'object' || consent === null) {
    errors.push('consent: required');
  } else {
    for (const k of ['analytics', 'ads'] as const) {
      if (typeof consent[k] !== 'boolean') errors.push(`consent.${k}: must be boolean`);
    }
  }

  const context = e.context as Record<string, unknown> | undefined;
  if (typeof context !== 'object' || context === null) {
    errors.push('context: required');
  } else if (typeof context.routeTemplate !== 'string' || !context.routeTemplate.startsWith('/')) {
    errors.push('context.routeTemplate: must be a path starting with "/"');
  } else if (context.routeTemplate.includes('?')) {
    errors.push('context.routeTemplate: query strings are forbidden (PII vector)');
  }

  const params = e.params as Record<string, unknown> | undefined;
  if (typeof params !== 'object' || params === null || Array.isArray(params)) {
    errors.push('params: required object');
  } else {
    const keys = Object.keys(params);
    if (keys.length > MAX_PARAMS) {
      errors.push(`params: ${keys.length} exceeds limit of ${MAX_PARAMS}`);
    }
    for (const k of keys) {
      const v = params[k];
      if (typeof v !== 'string' && typeof v !== 'number' && typeof v !== 'boolean') {
        errors.push(`params.${k}: must be string | number | boolean`);
      }
    }
  }

  if (e.breadcrumbs !== undefined) {
    if (!Array.isArray(e.breadcrumbs)) {
      errors.push('breadcrumbs: must be an array when present');
    } else if (e.breadcrumbs.length > MAX_BREADCRUMBS) {
      errors.push(`breadcrumbs: ${e.breadcrumbs.length} exceeds limit of ${MAX_BREADCRUMBS}`);
    } else {
      e.breadcrumbs.forEach((b, i) => {
        const bc = b as Record<string, unknown>;
        if (typeof bc !== 'object' || bc === null) {
          errors.push(`breadcrumbs[${i}]: must be an object`);
          return;
        }
        if (typeof bc.at !== 'number' || typeof bc.category !== 'string' || typeof bc.message !== 'string') {
          errors.push(`breadcrumbs[${i}]: requires numeric at, string category and message`);
        }
      });
    }
  }

  return errors;
}

/** Crockford base32 ULID: 10 chars time + 16 chars randomness. */
const CROCKFORD = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

function encodeTime(ms: number, len: number): string {
  let out = '';
  let t = ms;
  for (let i = len - 1; i >= 0; i--) {
    out = CROCKFORD[t % 32] + out;
    t = Math.floor(t / 32);
  }
  return out;
}

function randomChars(len: number): string {
  const bytes = new Uint8Array(len);
  if (typeof globalThis.crypto?.getRandomValues === 'function') {
    globalThis.crypto.getRandomValues(bytes);
  } else {
    for (let i = 0; i < len; i++) bytes[i] = Math.floor(Math.random() * 256);
  }
  let out = '';
  for (let i = 0; i < len; i++) out += CROCKFORD[bytes[i]! % 32];
  return out;
}

/** Time-sortable, unique event id. Generated once; never regenerated downstream. */
export function newEventId(nowMs: number = Date.now()): string {
  return encodeTime(nowMs, 10) + randomChars(16);
}
