import { SupportCase, canTransitionCase } from '@navdhya/support/src/domain/case';

export interface CaseRepository {
  getById(id: string): Promise<SupportCase | null>;
  create(kase: SupportCase): Promise<void>;
  save(kase: SupportCase): Promise<void>;
}

export type CaseResult =
  | { ok: true; kase: SupportCase }
  | { ok: false; reason: 'NOT_FOUND' | 'NOT_ACCESSIBLE' | 'VERSION_CONFLICT' | 'INVALID_TRANSITION' | 'ALREADY_CLOSED' };

export function openCase(kase: SupportCase): SupportCase {
  return kase;
}

export function transitionCase(kase: SupportCase, to: SupportCase['status'], expectedVersion: number): CaseResult {
  if (kase.version !== expectedVersion) return { ok: false, reason: 'VERSION_CONFLICT' };
  if (!canTransitionCase(kase.status, to)) return { ok: false, reason: 'INVALID_TRANSITION' };
  return { ok: true, kase: { ...kase, status: to, version: kase.version + 1 } };
}

export function customerOwnsCase(kase: SupportCase, customerId: string): boolean {
  return kase.reporterUserId === customerId;
}
