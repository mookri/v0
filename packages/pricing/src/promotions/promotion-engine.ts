import { MoneyMinor } from '@navdhya/pricing/src/domain/MoneyMinor';

export type PromotionStatus = 'DRAFT' | 'SCHEDULED' | 'ACTIVE' | 'PAUSED' | 'EXPIRED';
export type Stacking = 'STACKABLE' | 'NON_STACKABLE' | 'EXCLUSIVE';

export interface PromotionCondition {
  serviceIs?: string;
  categoryIs?: string;
  minimumSubtotalMinor?: number;
  newCustomer?: boolean;
}

export interface Promotion {
  id: string;
  name: string;
  status: PromotionStatus;
  startsAt?: string;
  endsAt?: string;
  conditions: PromotionCondition;
  reward: { kind: 'PERCENTAGE' | 'FIXED'; percent?: number; amountMinor?: number };
  stacking: Stacking;
  priority: number;
  maxDiscountMinor?: number;
}

export function isPromotionActive(p: Promotion, nowIso: string): boolean {
  if (p.status !== 'ACTIVE') return false;
  if (p.startsAt && nowIso < p.startsAt) return false;
  if (p.endsAt && nowIso >= p.endsAt) return false;
  return true;
}

export function promotionEligible(
  p: Promotion,
  ctx: { serviceId: string; category?: string; subtotalMinor: number; newCustomer?: boolean },
  nowIso: string,
): boolean {
  if (!isPromotionActive(p, nowIso)) return false;
  const c = p.conditions;
  if (c.serviceIs && c.serviceIs !== ctx.serviceId) return false;
  if (c.categoryIs && c.categoryIs !== ctx.category) return false;
  if (c.minimumSubtotalMinor !== undefined && ctx.subtotalMinor < c.minimumSubtotalMinor) return false;
  if (c.newCustomer !== undefined && c.newCustomer !== ctx.newCustomer) return false;
  return true;
}

export function promotionDiscount(p: Promotion, subtotal: MoneyMinor): MoneyMinor {
  let amountMinor = 0;
  if (p.reward.kind === 'PERCENTAGE') amountMinor = -Math.round((subtotal.amountMinor * (p.reward.percent ?? 0)) / 100);
  else amountMinor = -(p.reward.amountMinor ?? 0);
  if (p.maxDiscountMinor !== undefined) amountMinor = Math.max(amountMinor, -p.maxDiscountMinor);
  return { amountMinor, currency: subtotal.currency };
}

// Deterministic: exclusive wins by priority, else stackables apply in priority order.
export function selectPromotions(candidates: Promotion[]): Promotion[] {
  const sorted = [...candidates].sort((a, b) => b.priority - a.priority);
  const exclusive = sorted.find((p) => p.stacking === 'EXCLUSIVE');
  if (exclusive) return [exclusive];
  const nonStackable = sorted.find((p) => p.stacking === 'NON_STACKABLE');
  const stackables = sorted.filter((p) => p.stacking === 'STACKABLE');
  return [...(nonStackable ? [nonStackable] : []), ...stackables];
}
