import { test, expect } from 'vitest';
import { isValidRating } from '@navdhya/feedback/src/domain/rating';
import { sanitizeBody, isPublicReview } from '@navdhya/feedback/src/domain/review';
import { evaluateEligibility } from '@navdhya/feedback/src/eligibility/eligibility';
import { applyModeration, restoreReview } from '@navdhya/feedback/src/moderation/moderation';
import { emptyAggregate, addRating, averageRating, isBadgeActive } from '@navdhya/feedback/src/quality/quality';
import { submitReview, ReviewRepository } from '@navdhya/feedback/src/application/reviews';

const NOW = '2026-09-30T00:00:00.000Z';

test('ratings enforce the canonical 1-5 integer scale', () => {
  expect(isValidRating({ overall: 5 })).toBe(true);
  expect(isValidRating({ overall: 0 })).toBe(false);
  expect(isValidRating({ overall: 4.5 })).toBe(false);
  expect(isValidRating({ dimensions: { punctuality: 6 } })).toBe(false);
  expect(isValidRating({ dimensions: { punctuality: 4 } })).toBe(true);
});

test('bodies are sanitized and PII-stripped at submission boundary', () => {
  expect(sanitizeBody('<script>x</script>Great, call +91 98765 43210')).toBe('Great, call [redacted]');
  expect(isPublicReview({ id: 'r', authorId: 'u', target: { type: 'partner', id: 'p' }, source: 'POST_SERVICE', status: 'PUBLISHED', version: 1, createdAt: NOW })).toBe(true);
});

test('eligibility requires ownership, completion, window, uniqueness', () => {
  const base = { ownsBooking: true, fulfillmentCompleted: true, nowIso: NOW, alreadySubmitted: false };
  expect(evaluateEligibility(base).eligible).toBe(true);
  expect(evaluateEligibility({ ...base, ownsBooking: false }).reasonCode).toBe('NOT_OWNER');
  expect(evaluateEligibility({ ...base, fulfillmentCompleted: false }).reasonCode).toBe('NOT_COMPLETED');
  expect(evaluateEligibility({ ...base, alreadySubmitted: true }).reasonCode).toBe('ALREADY_SUBMITTED');
  expect(evaluateEligibility({ ...base, windowEndsAt: '2020-01-01T00:00:00.000Z' }).reasonCode).toBe('WINDOW_CLOSED');
});

test('submit guards duplicates, targets, and rating ranges', async () => {
  const store = new Map();
  const repo: ReviewRepository = {
    findById: async (id) => store.get(id) ?? null,
    findByBookingAuthorTarget: async (b, a, t) => [...store.values()].find((r: { sourceBookingId: string; authorId: string; target: { id: string } }) => r.sourceBookingId === b && r.authorId === a && r.target.id === t) ?? null,
    save: async (r) => { store.set(r.id, r); },
  };
  const input = { id: 'r1', authorId: 'u', target: { type: 'partner' as const, id: 'p' }, expectedTargetId: 'p', sourceBookingId: 'b', rating: { overall: 5 }, nowIso: NOW, ownsBooking: true, fulfillmentCompleted: true };
  expect((await submitReview(repo, input)).ok).toBe(true);
  expect(await submitReview(repo, { ...input, id: 'r2' })).toEqual({ ok: false, reason: 'DUPLICATE' });
  expect(await submitReview(repo, { ...input, id: 'r3', target: { type: 'partner', id: 'other' } })).toEqual({ ok: false, reason: 'TARGET_MISMATCH' });
  expect(await submitReview(repo, { ...input, id: 'r4', rating: { overall: 9 } })).toEqual({ ok: false, reason: 'INVALID_RATING' });
});

test('moderation restricts without deleting; restore is a transition', () => {
  const review = { id: 'r', authorId: 'u', target: { type: 'partner' as const, id: 'p' }, source: 'POST_SERVICE' as const, status: 'PUBLISHED' as const, version: 1, createdAt: NOW };
  const restricted = applyModeration(review, { reviewId: 'r', decision: 'RESTRICT', reasonCode: 'PERSONAL_DATA', actorType: 'MODERATOR', createdAt: NOW });
  expect(restricted.status).toBe('RESTRICTED');
  expect(restricted.version).toBe(2);
  expect(restoreReview(restricted, NOW)?.status).toBe('PUBLISHED');
  expect(restoreReview(review, NOW)).toBeNull();
});

test('aggregates keep precision; display rounds only', () => {
  let agg = emptyAggregate();
  agg = addRating(agg, 5);
  agg = addRating(agg, 4);
  expect(agg.count).toBe(2);
  expect(agg.distribution.five).toBe(1);
  expect(averageRating(agg)).toBe(4.5);
  expect(averageRating(emptyAggregate())).toBeUndefined();
});

test('badges expire by policy, never silently', () => {
  const badge = { id: 'b', type: 'VERIFIED', subjectId: 'p', issuedAt: NOW, policyVersion: 'v1', status: 'ACTIVE' as const };
  expect(isBadgeActive(badge, NOW)).toBe(true);
  expect(isBadgeActive({ ...badge, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(isBadgeActive({ ...badge, status: 'REVOKED' as const }, NOW)).toBe(false);
});
