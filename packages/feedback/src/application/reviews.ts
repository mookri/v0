import { Review } from '../domain/review';
import { isValidRating } from '../domain/rating';
import { evaluateEligibility } from '../eligibility/eligibility';

export interface ReviewRepository {
  findById(id: string): Promise<Review | null>;
  findByBookingAuthorTarget(bookingId: string, authorId: string, targetId: string): Promise<Review | null>;
  save(review: Review): Promise<void>;
}

export type SubmitResult =
  | { ok: true; review: Review }
  | { ok: false; reason: 'NOT_ELIGIBLE' | 'INVALID_RATING' | 'DUPLICATE' | 'TARGET_MISMATCH'; code?: string };

export async function submitReview(
  repo: ReviewRepository,
  input: {
    id: string;
    authorId: string;
    target: Review['target'];
    expectedTargetId: string;
    sourceBookingId: string;
    rating?: Review['rating'];
    title?: string;
    body?: string;
    nowIso: string;
    windowEndsAt?: string;
    ownsBooking: boolean;
    fulfillmentCompleted: boolean;
  },
): Promise<SubmitResult> {
  if (input.target.id !== input.expectedTargetId) return { ok: false, reason: 'TARGET_MISMATCH' };
  if (input.rating && !isValidRating(input.rating)) return { ok: false, reason: 'INVALID_RATING' };
  const existing = await repo.findByBookingAuthorTarget(input.sourceBookingId, input.authorId, input.target.id);
  const eligibility = evaluateEligibility({
    ownsBooking: input.ownsBooking,
    fulfillmentCompleted: input.fulfillmentCompleted,
    nowIso: input.nowIso,
    windowEndsAt: input.windowEndsAt,
    alreadySubmitted: existing !== null,
  });
  if (!eligibility.eligible) {
    if (existing) return { ok: false, reason: 'DUPLICATE' };
    return { ok: false, reason: 'NOT_ELIGIBLE', code: eligibility.reasonCode };
  }
  const review: Review = {
    id: input.id,
    authorId: input.authorId,
    target: input.target,
    sourceBookingId: input.sourceBookingId,
    source: 'POST_SERVICE',
    status: 'SUBMITTED',
    rating: input.rating,
    title: input.title,
    body: input.body,
    version: 1,
    createdAt: input.nowIso,
  };
  await repo.save(review);
  return { ok: true, review };
}
