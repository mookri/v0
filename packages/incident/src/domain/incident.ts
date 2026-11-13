export type IncidentType =
  | 'SERVICE_DEGRADATION' | 'SERVICE_OUTAGE' | 'DATA_INTEGRITY' | 'SECURITY'
  | 'PAYMENT' | 'FULFILLMENT' | 'COMMUNICATION' | 'THIRD_PARTY' | 'INFRASTRUCTURE' | 'UNKNOWN'
  | 'PANDIT_NO_SHOW' | 'LATE_ARRIVAL' | 'EMERGENCY_UNAVAILABILITY' | 'RITUAL_MISCONDUCT'
  | 'SAMAGRI_SHORTFALL' | 'TEMPLE_ACCESS_DENIAL' | 'STREAMING_DISRUPTION'
  | 'PRASAD_FULFILLMENT_FAILURE' | 'DEVOTEE_SAFETY_INCIDENT' | 'PRIVACY_BREACH';

export type IncidentSeverity = 'SEV_0' | 'SEV_1' | 'SEV_2' | 'SEV_3' | 'SEV_4';

export type AttributedCause =
  | 'PROVIDER'
  | 'VENUE'
  | 'WEATHER_FORCE_MAJEURE'
  | 'PLATFORM'
  | 'DEVOTEE'
  | 'UNKNOWN';

export type IncidentStatus =
  | 'DETECTED' | 'AWAITING_VERIFICATION' | 'TRIAGED' | 'DECLARED'
  | 'CONTAINED' | 'RECOVERY_IN_PROGRESS' | 'MITIGATING' | 'MONITORING'
  | 'POSTMORTEM_PENDING' | 'RESOLVED' | 'CLOSED' | 'CANCELLED' | 'MERGED';

export interface Incident {
  id: string;
  reference: string;
  title: string;
  type: IncidentType;
  severity: IncidentSeverity;
  status: IncidentStatus;
  attributedCause?: AttributedCause;
  services: string[];
  startedAt: string;
  detectedAt: string;
  declaredAt?: string;
  resolvedAt?: string;
  closedAt?: string;
  commanderId?: string;
  safetyRestricted?: boolean;
  version: number;
}

const TRANSITIONS: Record<IncidentStatus, IncidentStatus[]> = {
  DETECTED: ['AWAITING_VERIFICATION', 'TRIAGED', 'CANCELLED', 'MERGED'],
  AWAITING_VERIFICATION: ['TRIAGED', 'DECLARED', 'CANCELLED', 'MERGED'],
  TRIAGED: ['DECLARED', 'CONTAINED', 'CANCELLED', 'MERGED'],
  DECLARED: ['CONTAINED', 'RECOVERY_IN_PROGRESS', 'MITIGATING', 'CANCELLED', 'MERGED'],
  CONTAINED: ['RECOVERY_IN_PROGRESS', 'MITIGATING', 'MONITORING'],
  RECOVERY_IN_PROGRESS: ['MITIGATING', 'MONITORING', 'RESOLVED'],
  MITIGATING: ['RECOVERY_IN_PROGRESS', 'MONITORING', 'DECLARED', 'CANCELLED', 'MERGED'],
  MONITORING: ['POSTMORTEM_PENDING', 'RESOLVED', 'MITIGATING'],
  POSTMORTEM_PENDING: ['RESOLVED', 'MONITORING'],
  RESOLVED: ['POSTMORTEM_PENDING', 'CLOSED', 'MITIGATING'],
  CLOSED: [],
  CANCELLED: [],
  MERGED: [],
};

export interface IncidentClosureChecklist {
  allAffectedBookingsHaveOutcome: boolean;
  attributedCauseRecorded: boolean;
  attributedCause: AttributedCause;
  postmortemCompleted: boolean;
  severity: IncidentSeverity;
  type: IncidentType;
  safetyLeadClosed: boolean;
}

export function canCloseIncident(checklist: IncidentClosureChecklist): { ok: boolean; missing: string[] } {
  const missing: string[] = [];
  if (!checklist.allAffectedBookingsHaveOutcome) missing.push('affected_bookings_outcome');
  if (!checklist.attributedCauseRecorded || checklist.attributedCause === 'UNKNOWN') missing.push('attributed_cause');
  if (checklist.severity === 'SEV_0' || checklist.severity === 'SEV_1') {
    if (!checklist.postmortemCompleted) missing.push('postmortem');
  }
  if (checklist.severity === 'SEV_0' || checklist.type === 'DEVOTEE_SAFETY_INCIDENT') {
    if (!checklist.safetyLeadClosed) missing.push('safety_lead_signoff');
  }
  return { ok: missing.length === 0, missing };
}

export function canTransitionIncident(from: IncidentStatus, to: IncidentStatus): boolean {
  return TRANSITIONS[from]?.includes(to) ?? false;
}
