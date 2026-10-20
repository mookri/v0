export type ProductId = 'web' | 'customer' | 'partner' | 'admin' | 'operations';

export interface ActorContext {
  userId: string;
  product: ProductId;
  sessionId: string;
  roles: string[];
  organizationId?: string;
  partnerId?: string;
}

const ROLE_PERMISSIONS: Record<string, string[]> = {
  customer: ['booking.read', 'booking.create', 'booking.cancel', 'booking.reschedule', 'profile.read', 'profile.update', 'notification.read'],
  pandit: ['booking.read', 'schedule.manage', 'profile.manage'],
  support_admin: ['customer.read', 'booking.read', 'support.case.manage'],
};

export function permissionsForRoles(roles: string[]): string[] {
  const out = new Set<string>();
  for (const r of roles) for (const p of ROLE_PERMISSIONS[r] ?? []) out.add(p);
  return [...out];
}

export interface AuthorizeInput {
  product: ProductId;
  actor: ActorContext;
  action: string;
  resource?: { ownerId?: string };
}

export function authorize(input: AuthorizeInput): { allowed: true } | { allowed: false; reason: string } {
  if (input.actor.product !== input.product) return { allowed: false, reason: 'PRODUCT_MISMATCH' };
  const permissions = permissionsForRoles(input.actor.roles);
  if (!permissions.includes(input.action)) return { allowed: false, reason: 'FORBIDDEN' };
  if (input.resource?.ownerId !== undefined && input.resource.ownerId !== input.actor.userId) {
    return { allowed: false, reason: 'NOT_OWNED' };
  }
  return { allowed: true };
}
