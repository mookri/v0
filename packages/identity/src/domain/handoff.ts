import { SessionAudience } from './session';

export interface LoginHandoff {
  id: string;
  subject: string;
  source: SessionAudience;
  audience: SessionAudience;
  codeHash: string;
  expiresAt: string;
  usedAt?: string;
  returnPath?: string;
  nonce: string;
}

export type HandoffError =
  | 'HANDOFF_EXPIRED'
  | 'HANDOFF_REPLAYED'
  | 'INVALID_AUDIENCE'
  | 'INVALID_SOURCE'
  | 'INVALID_HANDOFF';

export function validateHandoff(
  handoff: LoginHandoff | null,
  input: { codeHash: string; audience: SessionAudience; nowIso: string },
): { ok: true; handoff: LoginHandoff } | { ok: false; error: HandoffError } {
  if (!handoff || handoff.codeHash !== input.codeHash) return { ok: false, error: 'INVALID_HANDOFF' };
  if (handoff.usedAt) return { ok: false, error: 'HANDOFF_REPLAYED' };
  if (handoff.expiresAt <= input.nowIso) return { ok: false, error: 'HANDOFF_EXPIRED' };
  if (handoff.audience !== input.audience) return { ok: false, error: 'INVALID_AUDIENCE' };
  if (handoff.source === input.audience) return { ok: false, error: 'INVALID_SOURCE' };
  return { ok: true, handoff };
}
