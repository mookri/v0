import type { ActorResolver, RequestActor } from '@navdhya/identity/src/ports/actor-resolver';

// STAGING ONLY: resolves the actor from the x-user-id header so authed
// flows are testable end-to-end behind mocks. Implements the identity
// ActorResolver port so the session-backed replacement is a swap, not a
// rewrite. Client-supplied actor ids must never be trusted in production.
export class StagingHeaderActorResolver implements ActorResolver {
  async resolve(headers: Record<string, string | undefined>): Promise<RequestActor | null> {
    const userId = headers['x-user-id'];
    if (!userId) return null;
    return { userId, sessionId: `staging_${userId}`, roles: ['customer'] };
  }
}

export function headersToRecord(headers: Headers): Record<string, string | undefined> {
  const out: Record<string, string | undefined> = {};
  headers.forEach((value, key) => {
    out[key.toLowerCase()] = value;
  });
  return out;
}
