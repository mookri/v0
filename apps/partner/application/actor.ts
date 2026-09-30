// Actor resolution seam for partner BFF routes.
// STAGING ONLY: resolves the actor from the x-partner-id header so the
// assignment flow is testable end-to-end behind mocks.
// TODO(session-adapter): replace with the session adapter (same as customer);
// client-supplied actor ids must never be trusted in production.
export interface PartnerActor {
  partnerId: string;
}

export function resolvePartnerActor(headers: Headers): PartnerActor | null {
  const partnerId = headers.get('x-partner-id');
  if (!partnerId) return null;
  return { partnerId };
}
