export type TaskStatus = 'TODO' | 'IN_PROGRESS' | 'COMPLETED' | 'BLOCKED' | 'CANCELLED';

export interface SupportTask {
  id: string;
  caseId: string;
  title: string;
  status: TaskStatus;
  ownerId?: string;
  dueAt?: string;
}

export interface SlaPolicy {
  id: string;
  version: string;
  firstResponseMinutes: number;
  resolutionMinutes: number;
}

export interface SlaState {
  startedAt: string;
  pausedAt?: string;
  dueAt: string;
  breached: boolean;
}

export function computeSlaDue(startedAt: string, minutes: number): string {
  return new Date(Date.parse(startedAt) + minutes * 60_000).toISOString();
}

export function isSlaBreached(state: SlaState, nowIso: string): boolean {
  if (state.pausedAt) return state.breached;
  return nowIso >= state.dueAt || state.breached;
}
