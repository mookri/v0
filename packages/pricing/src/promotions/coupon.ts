export type CouponStatus = 'ACTIVE' | 'PAUSED' | 'EXPIRED';
export type RedemptionStatus = 'HELD' | 'REDEEMED' | 'RELEASED' | 'EXPIRED';

export interface Coupon {
  id: string;
  code: string;
  promotionId: string;
  status: CouponStatus;
  startsAt?: string;
  endsAt?: string;
  usageLimit?: number;
  perCustomerLimit?: number;
}

export function isCouponActive(c: Coupon, nowIso: string): boolean {
  if (c.status !== 'ACTIVE') return false;
  if (c.startsAt && nowIso < c.startsAt) return false;
  if (c.endsAt && nowIso >= c.endsAt) return false;
  return true;
}

export interface RedemptionLedger {
  countTotal(couponId: string): Promise<number>;
  countByCustomer(couponId: string, customerId: string): Promise<number>;
  redeem(couponId: string, customerId: string, quoteId: string): Promise<boolean>;
}

// Exactly-once under concurrency: check-then-redeem must be atomic in the adapter.
export async function redeemCoupon(
  ledger: RedemptionLedger,
  coupon: Coupon,
  customerId: string,
  quoteId: string,
  nowIso: string,
): Promise<{ ok: true } | { ok: false; reason: 'COUPON_INVALID' | 'COUPON_EXPIRED' | 'COUPON_LIMIT_REACHED' }> {
  if (!isCouponActive(coupon, nowIso)) {
    return { ok: false, reason: coupon.status === 'EXPIRED' || (coupon.endsAt && nowIso >= coupon.endsAt) ? 'COUPON_EXPIRED' : 'COUPON_INVALID' };
  }
  if (coupon.usageLimit !== undefined && (await ledger.countTotal(coupon.id)) >= coupon.usageLimit) {
    return { ok: false, reason: 'COUPON_LIMIT_REACHED' };
  }
  if (coupon.perCustomerLimit !== undefined && (await ledger.countByCustomer(coupon.id, customerId)) >= coupon.perCustomerLimit) {
    return { ok: false, reason: 'COUPON_LIMIT_REACHED' };
  }
  const ok = await ledger.redeem(coupon.id, customerId, quoteId);
  return ok ? { ok: true } : { ok: false, reason: 'COUPON_LIMIT_REACHED' };
}
