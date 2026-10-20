// packages/analytics/src/registry/types.ts
//
// Event registry definition types (architecture §5.1). Every event definition
// carries identity, ownership, delivery policy, privacy classification,
// retention, sampling and priority. Types, JSON schemas, the edge validator
// and BigQuery docs are GENERATED from these definitions — never hand-edited.

import type { Surface } from '@navdhya/analytics/src/contracts/envelope';
import type { Sink } from '@navdhya/analytics/src/contracts/delivery-policy';

export type EventPriority = 'P0' | 'P1' | 'P2' | 'P3';
export type PrivacyClass = 'public' | 'pseudonymous';
export type RetentionClass = 'raw_30d' | 'raw_90d' | 'audit_long';
export type ParamType = 'string' | 'number' | 'boolean';
export type SamplingMode = 'none' | 'fixed' | 'adaptive';
export type EventProducer = 'client' | 'server';

export interface ParamDef {
  name: string;
  type: ParamType;
  required: boolean;
  description: string;
  /**
   * High-cardinality values must be explicitly reviewed (hcReviewed: true)
   * or the lint gate fails. Raw entity ids (booking_id, user_id, …) are
   * forbidden outright by the pii-names gate — bin them instead.
   */
  highCardinality?: boolean;
  hcReviewed?: boolean;
}

export interface SamplingPolicy {
  mode: SamplingMode;
  /** Required when mode = 'fixed'; 0 < rate ≤ 1. */
  rate?: number;
}

export interface EventDef {
  /** snake_case, unique, ≤ 500 registered names (governance ceiling, not a target). */
  name: string;
  version: number;
  /** Responsible team. */
  owner: string;
  /** Where the authoritative emission happens. P0 events are always 'server'. */
  producer: EventProducer;
  surfaces: Surface[];
  allowedSinks: Sink[];
  params: ParamDef[];
  privacyClass: PrivacyClass;
  retentionClass: RetentionClass;
  sampling: SamplingPolicy;
  priority: EventPriority;
  description: string;
  /** Registry allows breadcrumbs on this event (app_error only). */
  allowsBreadcrumbs?: boolean;
}
