export type ControlType =
  | 'FEATURE_DISABLE' | 'TRAFFIC_SHIFT' | 'QUEUE_PAUSE' | 'PROVIDER_DISABLE' | 'TRANSACTION_PAUSE';

export interface OperationalControl {
  id: string;
  type: ControlType;
  scope: string;
  enabled: boolean;
  activatedBy: string;
  reason: string;
  incidentId: string;
  activatedAt: string;
  expiresAt?: string;
  deactivatedAt?: string;
}

export function isControlLive(control: OperationalControl, nowIso: string): boolean {
  if (!control.enabled || control.deactivatedAt) return false;
  if (control.expiresAt && control.expiresAt <= nowIso) return false;
  return true;
}

export type CommandRole = 'COMMANDER' | 'TECHNICAL_LEAD' | 'COMMUNICATIONS_LEAD' | 'RECORDER';

export interface RoleAssignment {
  incidentId: string;
  role: CommandRole;
  actorId: string;
  assignedAt: string;
}
