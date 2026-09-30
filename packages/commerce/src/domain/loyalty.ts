export type LoyaltyEntryType = 'EARN' | 'REDEEM' | 'EXPIRE' | 'REVERSE' | 'ADJUST';

export interface LoyaltyPointsEntry {
  id: string;
  accountId: string;
  type: LoyaltyEntryType;
  points: number;
  sourceType: string;
  sourceId: string;
  policyVersion: string;
  createdAt: string;
}

export interface LoyaltyAccount {
  id: string;
  customerId: string;
  programId: string;
  programVersion: string;
  status: 'ACTIVE' | 'SUSPENDED' | 'CLOSED';
}

export function rebuildPointsBalance(entries: LoyaltyPointsEntry[]): number {
  let balance = 0;
  for (const e of entries) {
    switch (e.type) {
      case 'EARN':
      case 'ADJUST':
        balance += e.points;
        break;
      case 'REDEEM':
      case 'EXPIRE':
      case 'REVERSE':
        balance -= e.points;
        break;
    }
  }
  return balance;
}

export type ReferralStatus = 'CREATED' | 'CLAIMED' | 'QUALIFIED' | 'REWARDED' | 'EXPIRED' | 'CANCELLED';

export interface Referral {
  id: string;
  referrerId: string;
  refereeId?: string;
  code: string;
  status: ReferralStatus;
  createdAt: string;
}

export function canQualifyReferral(status: ReferralStatus): boolean {
  return status === 'CLAIMED';
}
