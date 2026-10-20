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
