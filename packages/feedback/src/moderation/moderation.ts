import { Review, ReviewStatus } from '../domain/review';

export type ModerationReason =
  | 'SPAM' | 'ABUSE' | 'PERSONAL_DATA' | 'OFF_TOPIC' | 'MANIPULATION' | 'DUPLICATE' | 'POLICY_VIOLATION';

export interface ModerationDecision {
  reviewId: string;
  decision: 'APPROVE' | 'RESTRICT' | 'REMOVE';
  reasonCode: ModerationReason;
  actorType: 'SYSTEM' | 'MODERATOR' | 'REPORTER';
  actorId?: string;
  createdAt: string;
}

const DECISION_STATUS: Record<ModerationDecision['decision'], ReviewStatus> = {
  APPROVE: 'PUBLISHED',
  RESTRICT: 'RESTRICTED',
  REMOVE: 'REMOVED',
};

export function applyModeration(review: Review, decision: ModerationDecision): Review {
  return { ...review, status: DECISION_STATUS[decision.decision], version: review.version + 1, updatedAt: decision.createdAt };
}

export function restoreReview(review: Review, nowIso: string): Review | null {
  if (review.status !== 'RESTRICTED' && review.status !== 'REMOVED') return null;
  return { ...review, status: 'PUBLISHED', version: review.version + 1, updatedAt: nowIso };
}
