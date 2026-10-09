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
  { bff: 'GET /api/bookings/:bookingId/history', backend: ['GET /api/v1/booking/reservations/{id}/history'], inRegistry: true },
  { bff: 'GET /api/profile', backend: ['GET /api/v1/customers/profile'], inRegistry: true },
  { bff: 'PATCH /api/profile', backend: ['PATCH /api/v1/customers/profile'], inRegistry: true },
  { bff: 'POST /api/profile:deactivate', backend: ['POST /api/v1/customers/profile:deactivate'], inRegistry: true },
  { bff: 'POST /api/profile:delete', backend: ['POST /api/v1/customers/profile:delete'], inRegistry: true },
  { bff: 'POST /api/profile:reactivate', backend: ['POST /api/v1/customers/profile:reactivate'], inRegistry: false },
  { bff: 'GET /api/saved', backend: ['GET /api/v1/customers/saved'], inRegistry: true },
  { bff: 'POST /api/saved', backend: ['POST /api/v1/customers/saved'], inRegistry: true },
  { bff: 'DELETE /api/saved/:id', backend: ['DELETE /api/v1/customers/saved/{id}'], inRegistry: true },
  { bff: 'GET /api/notifications', backend: ['GET /api/v1/customers/notifications'], inRegistry: true },
  { bff: 'POST /api/notifications/:id/read', backend: ['POST /api/v1/customers/notifications/{id}:read'], inRegistry: true },
  { bff: 'POST /api/notifications/read-all', backend: ['POST /api/v1/customers/notifications:read-all'], inRegistry: true },
  { bff: 'GET /api/discover', backend: ['GET /api/v1/customers/discover'], inRegistry: true },
  { bff: 'GET /api/settings', backend: ['GET /api/v1/customers/settings'], inRegistry: true },
  { bff: 'PATCH /api/settings', backend: ['PATCH /api/v1/customers/settings'], inRegistry: true },
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
  { bff: 'GET /api/verifications', backend: ['GET /api/v1/customers/verifications'], inRegistry: true },
  { bff: 'POST /api/verifications', backend: ['POST /api/v1/customers/verifications'], inRegistry: true },
  { bff: 'POST /api/verifications/confirm', backend: ['POST /api/v1/customers/verifications/{id}:confirm'], inRegistry: true },

  // Puja / Catalog (P7)
  { bff: 'GET /api/pujas', backend: ['GET /api/v1/catalog/items?status=ACTIVE'], inRegistry: true },
  { bff: 'GET /api/pujas/:pujaId', backend: ['GET /api/v1/catalog/items/{id}'], inRegistry: true },
  { bff: 'POST /api/pujas/:pujaId/quote', backend: ['POST /api/v1/catalog/quote'], inRegistry: true },

  // Booking creation (P8)
  { bff: 'POST /api/bookings', backend: ['POST /api/v1/booking/reservations'], inRegistry: true },

  // Pandit directory (P9)
  { bff: 'GET /api/pandits', backend: ['GET /api/v1/pandit/profiles'], inRegistry: true },
  { bff: 'GET /api/pandits/:panditId', backend: ['GET /api/v1/pandit/profiles/{id}'], inRegistry: true },
  { bff: 'GET /api/pandits/:panditId/availability', backend: ['GET /api/v1/pandit/profiles/{id}/availability'], inRegistry: true },

  // Temple directory (P11)
  { bff: 'GET /api/temples', backend: ['GET /api/v1/temple/profiles'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId', backend: ['GET /api/v1/temple/profiles/{id}'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/pujas', backend: ['GET /api/v1/temple/profiles/{id}/pujas'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/availability', backend: ['GET /api/v1/temple/profiles/{id}/availability'], inRegistry: true },

  // Instant-book search (B2)
  { bff: 'GET /api/instant-search', backend: ['GET /api/v1/booking/instant-search'], inRegistry: true },
];

test('backend mappings use module prefixes and registries agree', () => {
  const registry = new Set(apiRoutes.map((r) => `${r.method} ${r.path}`));
  for (const mapping of MAPPINGS) {
    expect(mapping.backend.length).toBeGreaterThan(0);
    for (const path of mapping.backend) {
      expect(path).toMatch(/^(GET|POST|PATCH|PUT|DELETE) \/api\/v1\/(customers|booking|catalog|pandit|temple)\//);
    }
    if (mapping.inRegistry) {
      expect(registry.has(mapping.bff)).toBe(true);
    }
  }
});