import { createRuntimeId } from '@/lib/ad-orchestration/ids';

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
  error: { code: ApiErrorCode; message: string; requestId: string };
}

export interface ApiOk<T> {
  data: T;
  requestId: string;
}

export function ok<T>(data: T, requestId: string): ApiOk<T> {
  return { data, requestId };
}

export function err(code: ApiErrorCode, message: string, requestId: string): ApiErrorEnvelope {
  return { error: { code, message, requestId } };
}

export function newRequestId(): string {
  // Contract §8: every request carries a unique id used for log
  // correlation with the backend (`X-Request-Id`).
  return createRuntimeId('req');
}
