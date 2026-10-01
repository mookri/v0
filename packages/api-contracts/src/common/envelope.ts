export interface ApiMeta {
  requestId: string;
  pagination?: { hasMore: boolean; nextCursor: string | null };
}

export interface ApiOk<T> {
  data: T;
  meta: ApiMeta;
}

export type ApiErrorCode =
  | 'UNAUTHENTICATED'
  | 'FORBIDDEN'
  | 'NOT_FOUND'
  | 'VALIDATION_ERROR'
  | 'CONFLICT'
  | 'RATE_LIMITED'
  | 'DEPENDENCY_UNAVAILABLE'
  | 'INTERNAL_ERROR'
  | 'BOOKING_NOT_FOUND'
  | 'BOOKING_CANNOT_CANCEL'
  | 'BOOKING_SLOT_CONFLICT'
  | 'BOOKING_RESCHEDULE_UNAVAILABLE'
  | 'STALE_RESOURCE'
  | 'REVIEW_NOT_ALLOWED'
  | 'REVIEW_EXISTS';

export interface ApiErrorEnvelope {
  error: { code: ApiErrorCode; message: string; requestId: string; fieldErrors?: Record<string, string>; retryAfter?: number };
}

export const HTTP_STATUS: Record<string, number> = {
  UNAUTHENTICATED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  BOOKING_NOT_FOUND: 404,
  // 422 (not 400): aligned with the backend kernel registry
  // (validation_failed) per the customer parity contract.
  VALIDATION_ERROR: 422,
  CONFLICT: 409,
  BOOKING_CANNOT_CANCEL: 409,
  BOOKING_SLOT_CONFLICT: 409,
  STALE_RESOURCE: 409,
  BOOKING_RESCHEDULE_UNAVAILABLE: 409,
  REVIEW_NOT_ALLOWED: 409,
  REVIEW_EXISTS: 409,
  RATE_LIMITED: 429,
  DEPENDENCY_UNAVAILABLE: 503,
  INTERNAL_ERROR: 500,
};

export function statusFor(code: ApiErrorCode): number {
  return HTTP_STATUS[code] ?? 500;
}
