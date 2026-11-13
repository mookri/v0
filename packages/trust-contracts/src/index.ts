/**
 * @navdhya/trust-contracts
 * Shared identifiers, ownership boundaries, party visibility scopes,
 * confidence bands, and error codes shared across Feedback, Support, Incident,
 * and apps/web surfaces (T-TS01-00).
 */

export type TrustSubjectType = 'pandit' | 'temple' | 'vendor' | 'service_instance';

export interface SubjectRef {
  type: TrustSubjectType;
  id: string;
  derivedFrom: 'server_assignment' | 'booking_revision' | 'institutional_entity';
}

export type PartyRole = 'devotee' | 'provider' | 'institution' | 'platform' | 'representative';

export type VisibilityScope =
  | 'PUBLIC'
  | 'PARTICIPANT'
  | 'PROVIDER_REDACTED'
  | 'REPORTER_PRIVATE'
  | 'INTERNAL_STAFF'
  | 'RESTRICTED_SAFETY';

export interface AuditEntry {
  actorId: string;
  actorType: string;
  action: string;
  previousState: string;
  newState: string;
  reason: string;
  policyVersion: string;
  correlationId: string;
  occurredAt: string;
}

export interface EvidenceEnvelope {
  reference: string;
  description: string;
  purpose: string;
  provenance: 'ORIGINAL_ARTEFACT' | 'DERIVED_INTERPRETATION' | 'EXTERNAL_RECORD';
  sensitivityClass: 'STANDARD' | 'FINANCIAL' | 'SAFETY_RESTRICTED' | 'DEVOTIONAL_PRIVATE';
  retentionClass: string;
}

export type ConfidenceTier =
  | 'NO_REVIEWS'
  | 'NEW_PROVIDER'
  | 'EMERGING_CONFIDENCE'
  | 'ESTABLISHED_SAMPLE';

export interface ConfidenceBand {
  tier: ConfidenceTier;
  sampleCount: number;
  label: string;
  lowerBound?: number | null;
  upperBound?: number | null;
}

export interface CursorPageMeta {
  limit: number;
  has_more: boolean;
  next_cursor: string | null;
}

export const TRUST_ERROR_CODES = {
  RESOLUTION_REQUIRED: 'resolution_required',
  SUBJECT_MISMATCH: 'subject_mismatch',
  FORCED_CREDIT_FORBIDDEN: 'forced_credit_forbidden',
  NO_MEDIATION_IN_SAFETY: 'no_mediation_in_safety_cases',
  INDEPENDENCE_VIOLATION: 'independence_violation',
  CLOSURE_PRECONDITION_FAILED: 'closure_precondition_failed',
  ARRIVAL_ALREADY_RECORDED: 'arrival_already_recorded',
  UNCOMPLETED_BOOKING: 'uncompleted_booking',
} as const;

