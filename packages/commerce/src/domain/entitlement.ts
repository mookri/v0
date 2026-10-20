export type EntitlementStatus = 'AVAILABLE' | 'RESERVED' | 'CONSUMED' | 'EXPIRED' | 'REVOKED';
export type EntitlementSourceType = 'MEMBERSHIP' | 'LOYALTY' | 'PROMOTION' | 'REFERRAL' | 'ADMIN' | 'PURCHASE';

export interface Entitlement {
  id: string;
  customerId: string;
  type: string;
  sourceType: EntitlementSourceType;
  sourceId: string;
  status: EntitlementStatus;
  quantityTotal?: number;
  quantityConsumed?: number;
  startsAt: string;
  expiresAt?: string;
}

export interface EntitlementReservation {
  id: string;
  entitlementId: string;
  referenceType: string;
  referenceId: string;
  expiresAt: string;
  status: 'HELD' | 'CONSUMED' | 'RELEASED';
}

export function remainingQuantity(e: Entitlement): number | null {
  if (e.quantityTotal === undefined) return null;
  return e.quantityTotal - (e.quantityConsumed ?? 0);
}

export function isEntitlementUsable(e: Entitlement, nowIso: string): boolean {
  if (e.status !== 'AVAILABLE') return false;
  if (e.expiresAt && e.expiresAt <= nowIso) return false;
  const remaining = remainingQuantity(e);
  if (remaining !== null && remaining <= 0) return false;
  return true;
}
