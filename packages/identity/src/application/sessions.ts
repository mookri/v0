import { Identity } from '../domain/identity';
import { Session, SessionAudience } from '../domain/session';
import { IdentityRepository, SessionStore } from '../ports/stores';

export function hashCode(code: string): string {
  let h = 0;
  for (let i = 0; i < code.length; i++) h = (h * 31 + code.charCodeAt(i)) >>> 0;
  return `h_${h.toString(36)}`;
}

export async function createSession(
  deps: { identities: IdentityRepository; sessions: SessionStore },
  input: { identityId: string; audience: SessionAudience; ttlSeconds: number; nowIso: string; sessionId: string },
): Promise<Session> {
  const identity = await deps.identities.findById(input.identityId);
  if (!identity || identity.status !== 'ACTIVE') throw new Error('IDENTITY_INACTIVE');
  const session: Session = {
    id: input.sessionId,
    identityId: input.identityId,
    audience: input.audience,
    issuedAt: input.nowIso,
    expiresAt: new Date(Date.parse(input.nowIso) + input.ttlSeconds * 1000).toISOString(),
  };
  await deps.sessions.create(session);
  return session;
}

export async function rotateSession(
  deps: { sessions: SessionStore },
  input: { oldId: string; nextId: string; nowIso: string; ttlSeconds: number },
): Promise<Session> {
  const current = await deps.sessions.get(input.oldId);
  if (!current) throw new Error('SESSION_NOT_FOUND');
  const next: Session = {
    ...current,
    id: input.nextId,
    issuedAt: input.nowIso,
    expiresAt: new Date(Date.parse(input.nowIso) + input.ttlSeconds * 1000).toISOString(),
    rotatedFrom: current.id,
  };
  await deps.sessions.rotate(input.oldId, next);
  return next;
}
