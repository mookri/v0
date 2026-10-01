import { MemoryReviewAuditStore } from '@navdhya/partner/src/adapters/memory-review-audit';

// Review audit composition (single-instance staging; shared store later).
export const reviewAudit = new MemoryReviewAuditStore();

export function isReviewer(roles: string[]): boolean {
  return roles.includes('reviewer') || roles.includes('admin');
}
