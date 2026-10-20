// packages/analytics/src/registry/lint.ts
//
// Registry lint gates (plan B1). Each gate has a negative-test fixture in
// __tests__/lint.test.ts proving it fires. Runs as a vitest test (the turbo
// `test` task) — this repo has no CI workflow files; tests are the CI home.

import { SINKS, type Sink } from '@navdhya/analytics/src/contracts/delivery-policy';
import { SURFACES, validateEnvelope, MAX_PARAMS } from '@navdhya/analytics/src/contracts/envelope';
import type { EventDef, ParamType } from './types';

export type LintGate =
  | 'structure'
  | 'delivery-policy'
  | 'pii-names'
  | 'high-cardinality'
  | 'volume'
  | 'compat'
  | 'fixtures';

export interface LintDiagnostic {
  gate: LintGate;
  event?: string;
  param?: string;
  message: string;
}

export interface BaselineDoc {
  events: Record<string, { version: number; params: Record<string, string> }>;
}

export interface LintOptions {
  baseline?: BaselineDoc;
  fixtures?: unknown[];
}

const EVENT_NAME_RE = /^[a-z][a-z0-9_]*$/;
const PARAM_NAME_RE = /^[a-z][a-z0-9_]*$/;
const SINK_SET: ReadonlySet<string> = new Set(SINKS);
const SURFACE_SET: ReadonlySet<string> = new Set(SURFACES);
const PARAM_TYPES: ReadonlySet<string> = new Set(['string', 'number', 'boolean']);
const PRIORITIES: ReadonlySet<string> = new Set(['P0', 'P1', 'P2', 'P3']);
const PRIVACY: ReadonlySet<string> = new Set(['public', 'pseudonymous']);
const RETENTION: ReadonlySet<string> = new Set(['raw_30d', 'raw_90d', 'audit_long']);
const SAMPLING_MODES: ReadonlySet<string> = new Set(['none', 'fixed', 'adaptive']);

/**
 * Param names that are direct-identifier shapes. Raw entity ids and contact
 * data are forbidden as generic analytics properties (bin or hash instead).
 */
const PII_NAME_RE =
  /(^|_)(email|e_mail|phone|mobile|tel|firstname|first_name|lastname|last_name|fullname|full_name|name|dob|birthday|birth_date|address|password|passwd|secret|token|otp|ssn|pan|aadhaar|passport|driver_licen[sc]e|user_id|booking_id|order_id|payment_id)($|_)/;

/** Id-shaped or URL-shaped param names require explicit HC review. */
const HC_NAME_RE = /(^|_)id$|_ids$|(^|_)url$|_url$|(^|_)ref$|_ref$/;

const EMAIL_VALUE_RE = /[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/;
const PHONE10_RE = /(?<!\d)\d{10}(?!\d)/;
const PHONE_DASH_RE = /\b\d{3}-\d{3}-\d{4}\b/;

export function lintRegistry(events: EventDef[], opts: LintOptions = {}): LintDiagnostic[] {
  const d: LintDiagnostic[] = [];
  const seen = new Set<string>();

  // ── volume: governance ceilings ──────────────────────────────────────────
  if (events.length > 500) {
    d.push({ gate: 'volume', message: `${events.length} registered events exceeds the 500-name governance ceiling` });
  }

  for (const e of events) {
    // ── structure ─────────────────────────────────────────────────────────
    if (seen.has(e.name)) d.push({ gate: 'structure', event: e.name, message: 'duplicate event name' });
    seen.add(e.name);
    if (!EVENT_NAME_RE.test(e.name)) d.push({ gate: 'structure', event: e.name, message: 'name must be snake_case' });
    if (!Number.isInteger(e.version) || e.version < 1) d.push({ gate: 'structure', event: e.name, message: 'version must be an integer ≥ 1' });
    if (!e.owner || e.owner.length === 0) d.push({ gate: 'structure', event: e.name, message: 'owner is required' });
    if (!e.description || e.description.length === 0) d.push({ gate: 'structure', event: e.name, message: 'description is required' });
    if (e.producer !== 'client' && e.producer !== 'server') d.push({ gate: 'structure', event: e.name, message: 'producer must be client | server' });
    if (!PRIORITIES.has(e.priority)) d.push({ gate: 'structure', event: e.name, message: `invalid priority "${e.priority}"` });
    if (!PRIVACY.has(e.privacyClass)) d.push({ gate: 'structure', event: e.name, message: `invalid privacyClass "${e.privacyClass}"` });
    if (!RETENTION.has(e.retentionClass)) d.push({ gate: 'structure', event: e.name, message: `invalid retentionClass "${e.retentionClass}"` });
    if (e.surfaces.length === 0) d.push({ gate: 'structure', event: e.name, message: 'at least one surface required' });
    for (const s of e.surfaces) {
      if (!SURFACE_SET.has(s)) d.push({ gate: 'structure', event: e.name, message: `invalid surface "${s}"` });
    }
    if (!SAMPLING_MODES.has(e.sampling.mode)) {
      d.push({ gate: 'structure', event: e.name, message: `invalid sampling mode "${e.sampling.mode}"` });
    } else if (e.sampling.mode === 'fixed') {
      const r = e.sampling.rate;
      if (typeof r !== 'number' || !(r > 0 && r <= 1)) {
        d.push({ gate: 'structure', event: e.name, message: 'fixed sampling requires 0 < rate ≤ 1' });
      }
    }
    if (e.params.length > MAX_PARAMS) {
      d.push({ gate: 'volume', event: e.name, message: `${e.params.length} params exceeds GA4 limit of ${MAX_PARAMS}` });
    }

    const paramNames = new Set<string>();
    for (const p of e.params) {
      if (paramNames.has(p.name)) d.push({ gate: 'structure', event: e.name, param: p.name, message: 'duplicate param name' });
      paramNames.add(p.name);
      if (!PARAM_NAME_RE.test(p.name)) d.push({ gate: 'structure', event: e.name, param: p.name, message: 'param name must be snake_case' });
      // G3: forbidden param types (only string | number | boolean allowed).
      if (!PARAM_TYPES.has(p.type as string)) {
        d.push({ gate: 'structure', event: e.name, param: p.name, message: `forbidden param type "${String(p.type)}" — only string | number | boolean` });
      }
      if (!p.description || p.description.length === 0) {
        d.push({ gate: 'structure', event: e.name, param: p.name, message: 'param description is required' });
      }
      // G5: PII-shaped param names.
      if (PII_NAME_RE.test(p.name)) {
        d.push({ gate: 'pii-names', event: e.name, param: p.name, message: 'PII/direct-identifier param name is forbidden — bin, hash, or drop it' });
      }
      // G4: high-cardinality without review, or id/url/ref-shaped names without review.
      const idShaped = HC_NAME_RE.test(p.name);
      if (p.highCardinality === true && p.hcReviewed !== true) {
        d.push({ gate: 'high-cardinality', event: e.name, param: p.name, message: 'highCardinality param requires hcReviewed: true' });
      }
      if (idShaped && p.hcReviewed !== true) {
        d.push({ gate: 'high-cardinality', event: e.name, param: p.name, message: 'id/url/ref-shaped param requires explicit hcReviewed: true (or rename to a binned form)' });
      }
    }

    // ── delivery policy ───────────────────────────────────────────────────
    if (e.allowedSinks.length === 0) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'allowedSinks must be non-empty' });
    }
    const sinks = new Set<string>(e.allowedSinks);
    if (sinks.size !== e.allowedSinks.length) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'duplicate sinks in allowedSinks' });
    }
    for (const s of e.allowedSinks) {
      if (!SINK_SET.has(s)) d.push({ gate: 'delivery-policy', event: e.name, message: `unknown sink "${String(s)}"` });
    }
    if (sinks.has('internal_only') && e.allowedSinks.length > 1) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'internal_only cannot be combined with reporting sinks' });
    }
    if (e.priority === 'P0' && sinks.has('gtm_ga4')) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'P0 (domain truth) can never route through gtm_ga4 — browser tags do not own business events' });
    }
    if (e.producer === 'server' && sinks.has('gtm_ga4')) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'server-produced events can never route through gtm_ga4 (double-count vector)' });
    }
    if (e.allowsBreadcrumbs && sinks.has('gtm_ga4')) {
      d.push({ gate: 'delivery-policy', event: e.name, message: 'breadcrumbs never flow to GA4 — private stream only' });
    }

    // ── compat (G2): breaking changes require a version bump ──────────────
    const baseline = opts.baseline;
    if (baseline) {
      const base = baseline.events[e.name];
      if (base) {
        if (e.version < base.version) {
          d.push({ gate: 'compat', event: e.name, message: `version regressed from baseline v${base.version}` });
        } else {
          const removed = Object.keys(base.params).filter((p) => !paramNames.has(p));
          const retyped = Object.entries(base.params).filter(
            ([p, t]) => paramNames.has(p) && (e.params.find((q) => q.name === p)?.type ?? '') !== t,
          );
          if ((removed.length > 0 || retyped.length > 0) && e.version <= base.version) {
            d.push({
              gate: 'compat',
              event: e.name,
              message: `breaking param change (removed: [${removed.join(', ')}]; retyped: [${retyped.map(([p]) => p).join(', ')}]) requires version > baseline v${base.version}`,
            });
          }
        }
      }
    }
  }

  // Baseline events deleted outright must be removed from the baseline explicitly.
  if (opts.baseline) {
    for (const name of Object.keys(opts.baseline.events)) {
      if (!seen.has(name)) {
        d.push({ gate: 'compat', event: name, message: 'event exists in baseline but not in registry — delete baseline entry explicitly if intentional' });
      }
    }
  }

  // ── fixtures ────────────────────────────────────────────────────────────
  if (opts.fixtures) {
    opts.fixtures.forEach((fx, i) => {
      const where = `fixtures[${i}]`;
      const envErrors = validateEnvelope(fx);
      if (envErrors.length > 0) {
        d.push({ gate: 'fixtures', message: `${where}: invalid envelope — ${envErrors.join('; ')}` });
        return;
      }
      const env = fx as { name: string; params: Record<string, unknown>; breadcrumbs?: Array<{ message: string }> };
      const def = events.find((e) => e.name === env.name);
      if (!def) {
        d.push({ gate: 'fixtures', message: `${where}: unknown event "${env.name}" (not in registry)` });
        return;
      }
      for (const p of def.params) {
        if (p.required && !(p.name in env.params)) {
          d.push({ gate: 'fixtures', event: def.name, param: p.name, message: `${where}: missing required param` });
        }
      }
      for (const [k, v] of Object.entries(env.params)) {
        const pd = def.params.find((q) => q.name === k);
        if (!pd) {
          d.push({ gate: 'fixtures', event: def.name, param: k, message: `${where}: unknown param (not in registry)` });
          continue;
        }
        if (typeof v !== pd.type) {
          d.push({ gate: 'fixtures', event: def.name, param: k, message: `${where}: type mismatch — expected ${pd.type}` });
        }
        if (typeof v === 'string' && (EMAIL_VALUE_RE.test(v) || PHONE10_RE.test(v) || PHONE_DASH_RE.test(v))) {
          d.push({ gate: 'fixtures', event: def.name, param: k, message: `${where}: PII-shaped value in fixture (email/phone pattern)` });
        }
      }
      for (const b of env.breadcrumbs ?? []) {
        if (EMAIL_VALUE_RE.test(b.message) || PHONE10_RE.test(b.message)) {
          d.push({ gate: 'fixtures', event: def.name, message: `${where}: PII-shaped value in breadcrumb message` });
        }
      }
    });
  }

  return d;
}
