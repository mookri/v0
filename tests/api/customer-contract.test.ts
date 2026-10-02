import { test, expect } from 'vitest';
import { statusFor, type ApiErrorCode } from '@navdhya/api-contracts/src/common/envelope';
import type { ApiErrorCode as BffCode } from '@apps/customer/application/api-envelope';
import { apiRoutes } from '@apps/customer/application/api-routes';

// Contract parity (parity-customers.md code correspondence table, backend
// mirror at platform-navdhya/docs/api/parity-customers.md): the BFF code
// registry is a subset of the contract registry with identical statuses,
// and every BFF endpoint maps to its backend source of truth.

// Mirror of the BFF ApiErrorCode union — update together (the compiler
// checks each entry is a valid BffCode; the subset test below checks the
// contract side).
const BFF_CODES: BffCode[] = [
  'UNAUTHENTICATED',
  'FORBIDDEN',
  'NOT_FOUND',
  'VALIDATION_ERROR',
  'CONFLICT',
  'RATE_LIMITED',
  'DEPENDENCY_UNAVAILABLE',
  'INTERNAL_ERROR',
  'BOOKING_NOT_FOUND',
  'BOOKING_CANNOT_CANCEL',
  'BOOKING_SLOT_CONFLICT',
  'BOOKING_RESCHEDULE_UNAVAILABLE',
  'STALE_RESOURCE',
  'REVIEW_NOT_ALLOWED',
  'REVIEW_EXISTS',
];

const EXPECTED_STATUS: Record<BffCode, number> = {
  UNAUTHENTICATED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  BOOKING_NOT_FOUND: 404,
  VALIDATION_ERROR: 422,
  CONFLICT: 409,
  BOOKING_CANNOT_CANCEL: 409,
  BOOKING_SLOT_CONFLICT: 409,
  BOOKING_RESCHEDULE_UNAVAILABLE: 409,
  STALE_RESOURCE: 409,
  REVIEW_NOT_ALLOWED: 409,
  REVIEW_EXISTS: 409,
  RATE_LIMITED: 429,
  DEPENDENCY_UNAVAILABLE: 503,
  INTERNAL_ERROR: 500,
};

test('every BFF code resolves to its parity status', () => {
  for (const code of BFF_CODES) {
    // Narrows BffCode to the contract union: a BFF-only code fails types.
    const contractCode: ApiErrorCode = code;
    expect(statusFor(contractCode)).toBe(EXPECTED_STATUS[code]);
  }
});

interface BackendMapping {
  bff: string;
  backend: string[];
  inRegistry: boolean;
}

// Mirror of the parity-doc flow table (BFF endpoint → backend source).
// Backend paths must live under the module prefixes; BFF paths flagged
// inRegistry must exist in the apiRoutes registry.
const MAPPINGS: BackendMapping[] = [
  { bff: 'GET /api/home', backend: ['GET /api/v1/booking/reservations', 'GET /api/v1/customers/profile'], inRegistry: true },
  { bff: 'GET /api/bookings', backend: ['GET /api/v1/booking/reservations'], inRegistry: true },
  { bff: 'GET /api/bookings/:bookingId', backend: ['GET /api/v1/booking/reservations/{id}'], inRegistry: true },
  { bff: 'POST /api/bookings/:bookingId/cancel', backend: ['POST /api/v1/booking/reservations/{id}:cancel'], inRegistry: true },
  { bff: 'POST /api/bookings/:bookingId/reschedule', backend: ['POST /api/v1/booking/reservations/{id}:reschedule'], inRegistry: true },
  { bff: 'GET /api/profile', backend: ['GET /api/v1/customers/profile'], inRegistry: true },
  { bff: 'GET /api/saved', backend: ['GET /api/v1/customers/saved'], inRegistry: true },
  { bff: 'GET /api/notifications', backend: ['GET /api/v1/customers/notifications'], inRegistry: true },
  { bff: 'GET /api/discover', backend: ['GET /api/v1/customers/discover'], inRegistry: true },
  { bff: 'GET /api/settings', backend: ['GET /api/v1/customers/settings'], inRegistry: true },
  { bff: 'POST /api/auth/login', backend: ['POST /api/v1/customers/auth/otp', 'POST /api/v1/customers/auth/login'], inRegistry: false },
  { bff: 'POST /api/auth/logout', backend: ['POST /api/v1/customers/auth/logout'], inRegistry: false },
  { bff: 'GET /api/addresses', backend: ['GET /api/v1/customers/addresses'], inRegistry: true },
  { bff: 'POST /api/addresses', backend: ['POST /api/v1/customers/addresses'], inRegistry: true },
  { bff: 'PATCH /api/addresses/:id', backend: ['PATCH /api/v1/customers/addresses/{id}'], inRegistry: true },
  { bff: 'DELETE /api/addresses/:id', backend: ['DELETE /api/v1/customers/addresses/{id}'], inRegistry: true },
  { bff: 'POST /api/addresses/:id/default', backend: ['POST /api/v1/customers/addresses/{id}:default'], inRegistry: true },
  { bff: 'GET /api/activity', backend: ['GET /api/v1/customers/activity'], inRegistry: true },
  { bff: 'GET /api/support/tickets', backend: ['GET /api/v1/customers/support/tickets'], inRegistry: true },
  { bff: 'POST /api/support/tickets', backend: ['POST /api/v1/customers/support/tickets'], inRegistry: true },
  { bff: 'GET /api/support/tickets/:id', backend: ['GET /api/v1/customers/support/tickets/{id}'], inRegistry: true },
  { bff: 'POST /api/support/tickets/:id/messages', backend: ['POST /api/v1/customers/support/tickets/{id}/messages'], inRegistry: true },
  { bff: 'POST /api/support/tickets/:id/close', backend: ['POST /api/v1/customers/support/tickets/{id}:close'], inRegistry: true },
  { bff: 'GET /api/referrals', backend: ['GET /api/v1/customers/referrals'], inRegistry: true },
  { bff: 'GET /api/reviews', backend: ['GET /api/v1/booking/reviews'], inRegistry: true },
  { bff: 'POST /api/bookings/:bookingId/reviews', backend: ['POST /api/v1/booking/reservations/{id}/reviews'], inRegistry: true },
  { bff: 'DELETE /api/reviews/:id', backend: ['DELETE /api/v1/booking/reviews/{id}'], inRegistry: true },
];

test('backend mappings use module prefixes and registries agree', () => {
  const registry = new Set(apiRoutes.map((r) => `${r.method} ${r.path}`));
  for (const mapping of MAPPINGS) {
    expect(mapping.backend.length).toBeGreaterThan(0);
    for (const path of mapping.backend) {
      expect(path).toMatch(/^(GET|POST|PATCH|PUT|DELETE) \/api\/v1\/(customers|booking)\//);
    }
    if (mapping.inRegistry) {
      expect(registry.has(mapping.bff)).toBe(true);
    }
  }
});
