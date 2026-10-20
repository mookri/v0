export type IdentityStatus = 'ACTIVE' | 'SUSPENDED' | 'DEACTIVATED' | 'DELETED';

export interface Identity {
  id: string;
  status: IdentityStatus;
  primaryEmail?: string;
  primaryPhone?: string;
  emailVerified: boolean;
  phoneVerified: boolean;
  createdAt: string;
  updatedAt: string;
}

export function isIdentityUsable(identity: Identity): boolean {
  return identity.status === 'ACTIVE';
}
