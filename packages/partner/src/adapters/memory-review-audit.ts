import { ReviewRecord } from '@navdhya/partner/src/application/review';

// Append-only audit trail for review decisions (admin-ops requirement:
// actor, action, target, reason, timestamp — no silent state changes).
export interface ReviewAuditStore {
  append(record: ReviewRecord): Promise<void>;
  listByApplication(applicationId: string): Promise<ReviewRecord[]>;
}

export class MemoryReviewAuditStore implements ReviewAuditStore {
  private readonly records: ReviewRecord[] = [];

  async append(record: ReviewRecord): Promise<void> {
    this.records.push({ ...record });
  }

  async listByApplication(applicationId: string): Promise<ReviewRecord[]> {
    return this.records.filter((r) => r.applicationId === applicationId).map((r) => ({ ...r }));
  }
}
