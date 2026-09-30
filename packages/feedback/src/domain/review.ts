import { Rating } from './rating';

export type ReviewStatus = 'DRAFT' | 'SUBMITTED' | 'UNDER_REVIEW' | 'PUBLISHED' | 'RESTRICTED' | 'REMOVED';
export type ReviewTargetType = 'partner' | 'service' | 'booking';
export type ReviewSource = 'POST_SERVICE' | 'CUSTOMER_INITIATED' | 'SUPPORT_FOLLOWUP' | 'OPERATIONS_FOLLOWUP';

export interface Review {
  id: string;
  authorId: string;
  target: { type: ReviewTargetType; id: string };
  sourceBookingId?: string;
  source: ReviewSource;
  status: ReviewStatus;
  rating?: Rating;
  title?: string;
  body?: string;
  version: number;
  createdAt: string;
  updatedAt?: string;
}

export function isPublicReview(review: Review): boolean {
  return review.status === 'PUBLISHED';
}

export function sanitizeBody(body: string): string {
  return body
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/<[^>]*>/g, '')
    .replace(/\+?\d[\d\s-]{7,}\d/g, '[redacted]')
    .slice(0, 5000);
}
