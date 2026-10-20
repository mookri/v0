export type SessionAudience = 'web' | 'customer' | 'partner' | 'admin' | 'operations';

export interface Session {
  id: string;
  identityId: string;
  audience: SessionAudience;
  issuedAt: string;
  expiresAt: string;
  revokedAt?: string;
  rotatedFrom?: string;
}

export function isSessionLive(session: Session, nowIso: string): boolean {
  if (session.revokedAt) return false;
  return session.expiresAt > nowIso;
}
