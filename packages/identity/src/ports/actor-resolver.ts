export interface RequestActor {
  userId: string;
  sessionId: string;
  roles: string[];
  // Tenant scope (multi-tenant trajectory): market is required going
  // forward, orgId for organization-bound actors. Absent = legacy/dev
  // tokens, treated as the default market at enforcement points.
  market?: string;
  orgId?: string;
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

export const DEFAULT_MARKET = 'in';

// Tenant enforcement: resources carry their market (default market when
// unset); cross-market access throws. Org binding narrows further when both
// sides declare it. Enforcement points call this — never inline compares.
export function assertSameTenant(actor: RequestActor, resource: { market?: string; orgId?: string }): void {
  const actorMarket = actor.market ?? DEFAULT_MARKET;
  const resourceMarket = resource.market ?? DEFAULT_MARKET;
  if (actorMarket !== resourceMarket) throw new Error(`TENANT_MISMATCH:${actorMarket}!=${resourceMarket}`);
  if (actor.orgId && resource.orgId && actor.orgId !== resource.orgId) {
    throw new Error('TENANT_ORG_MISMATCH');
  }
}
