export interface RequestActor {
  userId: string;
  sessionId: string;
  roles: string[];
}

// Resolves the request actor from transport headers. Production
// implementations validate a session (see SessionStore); staging/test
// adapters resolve from explicit headers and are never trusted in prod.
export interface ActorResolver {
  resolve(headers: Record<string, string | undefined>): Promise<RequestActor | null>;
}

export class StaticActorResolver implements ActorResolver {
  constructor(private readonly actor: RequestActor | null) {}

  async resolve(_headers: Record<string, string | undefined>): Promise<RequestActor | null> {
    return this.actor;
  }
}
