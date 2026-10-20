export interface CompletionRecord {
  fulfillmentId: string;
  completedAt: string;
  completedBy: { actorId: string };
  evidenceRefs?: string[];
  customerAcknowledgedAt?: string;
}

export interface ExecutionStep {
  id: string;
  status: 'PENDING' | 'IN_PROGRESS' | 'COMPLETED' | 'SKIPPED' | 'FAILED';
}
