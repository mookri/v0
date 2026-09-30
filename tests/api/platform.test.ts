import { test, expect } from 'vitest';
import { statusFor } from '@navdhya/api-contracts/src/common/envelope';
import { scopedIdempotencyKey, samePayloadHash } from '@apps/customer/application/idempotency';
import { isRetryableStatus } from '@apps/customer/application/api-policy';

test('status map is consistent', () => {
  expect(statusFor('BOOKING_NOT_FOUND')).toBe(404);
  expect(statusFor('BOOKING_CANNOT_CANCEL')).toBe(409);
  expect(statusFor('RATE_LIMITED')).toBe(429);
  expect(statusFor('INTERNAL_ERROR')).toBe(500);
});

test('idempotency keys are actor/operation/resource scoped', () => {
  const a = scopedIdempotencyKey({ actorId: 'u1', operation: 'cancel-booking', resourceId: 'b1', key: 'k' });
  const b = scopedIdempotencyKey({ actorId: 'u2', operation: 'cancel-booking', resourceId: 'b1', key: 'k' });
  expect(a).not.toBe(b);
  expect(samePayloadHash({ a: 1 }, { a: 1 })).toBe(true);
  expect(samePayloadHash({ a: 1 }, { a: 2 })).toBe(false);
});

test('retry policy retries transport failures, never business conflicts', () => {
  expect(isRetryableStatus(503)).toBe(true);
  expect(isRetryableStatus(409)).toBe(false);
  expect(isRetryableStatus(400)).toBe(false);
});
