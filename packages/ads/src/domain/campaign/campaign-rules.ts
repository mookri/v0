import { Campaign } from './Campaign';

export function isCampaignActive(c: Campaign, nowIso: string): boolean {
  if (c.status !== 'active') return false;
  if (c.startAt && c.startAt > nowIso) return false;
  if (c.endAt && c.endAt < nowIso) return false;
  if (c.budgetTotal !== undefined && (c.budgetSpent ?? 0) >= c.budgetTotal) return false;
  return true;
}
