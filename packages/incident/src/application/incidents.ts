import { Incident, canTransitionIncident } from '@navdhya/incident/src/domain/incident';

export interface IncidentRepository {
  getById(id: string): Promise<Incident | null>;
  save(incident: Incident): Promise<void>;
}

export type IncidentResult =
  | { ok: true; incident: Incident }
  | { ok: false; reason: 'NOT_FOUND' | 'VERSION_CONFLICT' | 'INVALID_TRANSITION' };

export function transitionIncident(incident: Incident, to: Incident['status'], expectedVersion: number): IncidentResult {
  if (incident.version !== expectedVersion) return { ok: false, reason: 'VERSION_CONFLICT' };
  if (!canTransitionIncident(incident.status, to)) return { ok: false, reason: 'INVALID_TRANSITION' };
  const now = new Date().toISOString();
  return {
    ok: true,
    incident: {
      ...incident,
      status: to,
      version: incident.version + 1,
      ...(to === 'DECLARED' ? { declaredAt: now } : {}),
      ...(to === 'RESOLVED' ? { resolvedAt: now } : {}),
      ...(to === 'CLOSED' ? { closedAt: now } : {}),
    },
  };
}
