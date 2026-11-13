export type PublicStatusState = 'INVESTIGATING' | 'IDENTIFIED' | 'MONITORING' | 'RESOLVED';

export interface StatusPublication {
  id: string;
  incidentId: string;
  title: string;
  status: PublicStatusState;
  customerMessage: string;
  publishedAt: string;
  updatedAt: string;
  version: number;
}

export function toPublicStatus(input: {
  id: string;
  incidentId: string;
  title: string;
  status: PublicStatusState;
  customerMessage: string;
  nowIso: string;
}): StatusPublication {
  return { ...input, publishedAt: input.nowIso, updatedAt: input.nowIso, version: 1 };
}

export function canPublishPublicStatus(input: {
  safetyClassified: boolean;
  severity: 'SEV_0' | 'SEV_1' | 'SEV_2' | 'SEV_3' | 'SEV_4';
  approverId?: string | null;
}): { allowed: boolean; reason?: string } {
  if (input.safetyClassified || input.severity === 'SEV_0') {
    return { allowed: false, reason: 'safety_incident_never_public' };
  }
  if (!input.approverId || input.approverId.trim() === '') {
    return { allowed: false, reason: 'explicit_human_approver_required' };
  }
  return { allowed: true };
}

export interface Postmortem {
  incidentId: string;
  summary: string;
  impact: string;
  rootCause?: { category: string; description: string; confidence: 'LOW' | 'MEDIUM' | 'HIGH' };
  correctiveActionIds: string[];
  completedAt?: string;
}

export interface CorrectiveAction {
  id: string;
  incidentId: string;
  title: string;
  type: 'CODE' | 'INFRASTRUCTURE' | 'PROCESS' | 'MONITORING' | 'DOCUMENTATION' | 'SECURITY' | 'CAPACITY';
  ownerId: string;
  dueAt?: string;
  status: 'OPEN' | 'IN_PROGRESS' | 'DONE' | 'CANCELLED';
}
