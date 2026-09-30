import { IdentityRepository, SessionStore, CredentialVerifier } from '@navdhya/identity/src/ports/stores';
import { Session, SessionAudience } from '@navdhya/identity/src/domain/session';
import { isIdentityUsable } from '@navdhya/identity/src/domain/identity';
import { mintActorToken, verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { RequestActor } from '@navdhya/identity/src/ports/actor-resolver';

export interface LoginDeps {
  identities: IdentityRepository;
  sessions: SessionStore;
  verifier: CredentialVerifier;
  tokenSecret: string;
}

export interface LoginInput {
  identityId: string;
  proof: unknown;
  audience: SessionAudience;
  roles: string[];
  sessionId: string;
  tokenTtlSeconds: number;
  sessionTtlSeconds: number;
  nowIso: string;
}

// Full login chain: credential → session record → bearer token. The token
// is bound to the session id so logout/rotation kills it (see
// resolveSessionToken); stateless verification alone cannot revoke.
export async function login(deps: LoginDeps, input: LoginInput): Promise<{ session: Session; token: string }> {
  const identity = await deps.identities.findById(input.identityId);
  if (!identity || !isIdentityUsable(identity)) throw new Error('IDENTITY_INACTIVE');
  const verified = await deps.verifier.verify(input.identityId, input.proof);
  if (!verified) throw new Error('CREDENTIAL_INVALID');
  const session: Session = {
    id: input.sessionId,
    identityId: input.identityId,
    audience: input.audience,
    issuedAt: input.nowIso,
    expiresAt: new Date(Date.parse(input.nowIso) + input.sessionTtlSeconds * 1000).toISOString(),
  };
  await deps.sessions.create(session);
  const token = mintActorToken(
    deps.tokenSecret,
    { userId: input.identityId, sessionId: session.id, roles: input.roles },
    input.nowIso,
    input.tokenTtlSeconds,
  );
  return { session, token };
}

export async function logout(deps: { sessions: SessionStore }, sessionId: string, nowIso: string): Promise<void> {
  await deps.sessions.revoke(sessionId, nowIso);
}

// Token check with session liveness: unknown, revoked, or expired sessions
// reject even when the token signature is valid.
export async function resolveSessionToken(
  deps: { sessions: SessionStore; tokenSecret: string },
  token: string,
  nowIso: string,
): Promise<RequestActor> {
  const actor = verifyActorToken(deps.tokenSecret, token, nowIso);
  const session = await deps.sessions.get(actor.sessionId);
  if (!session) throw new Error('SESSION_UNKNOWN');
  if (session.revokedAt) throw new Error('SESSION_REVOKED');
  if (session.expiresAt <= nowIso) throw new Error('SESSION_EXPIRED');
  return actor;
}
