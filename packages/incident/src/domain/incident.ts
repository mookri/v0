export type IncidentType =
  | 'SERVICE_DEGRADATION' | 'SERVICE_OUTAGE' | 'DATA_INTEGRITY' | 'SECURITY'
  | 'PAYMENT' | 'FULFILLMENT' | 'COMMUNICATION' | 'THIRD_PARTY' | 'INFRASTRUCTURE' | 'UNKNOWN';

export type IncidentSeverity = 'SEV_1' | 'SEV_2' | 'SEV_3' | 'SEV_4';

export type IncidentStatus =
  | 'DETECTED' | 'TRIAGED' | 'DECLARED' | 'MITIGATING' | 'MONITORING'
  | 'RESOLVED' | 'CLOSED' | 'CANCELLED' | 'MERGED';

export interface Incident {
  id: string;
  reference: string;
  title: string;
  type: IncidentType;
  severity: IncidentSeverity;
  status: IncidentStatus;
  services: string[];
  startedAt: string;
  detectedAt: string;
  declaredAt?: string;
  resolvedAt?: string;
  closedAt?: string;
  commanderId?: string;
  version: number;
}

const TRANSITIONS: Record<IncidentStatus, IncidentStatus[]> = {
  DETECTED: ['TRIAGED', 'CANCELLED', 'MERGED'],
  TRIAGED: ['DECLARED', 'CANCELLED', 'MERGED'],
  DECLARED: ['MITIGATING', 'CANCELLED', 'MERGED'],
  MITIGATING: ['MONITORING', 'DECLARED', 'CANCELLED', 'MERGED'],
  MONITORING: ['RESOLVED', 'MITIGATING'],
  RESOLVED: ['CLOSED', 'MITIGATING'],
  CLOSED: [],
  CANCELLED: [],
  MERGED: [],
};

export function canTransitionIncident(from: IncidentStatus, to: IncidentStatus): boolean {
  return TRANSITIONS[from]?.includes(to) ?? false;
}
