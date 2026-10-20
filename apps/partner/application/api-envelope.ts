export type PartnerApiErrorCode =
  | 'UNAUTHENTICATED'
  | 'FORBIDDEN'
  | 'NOT_FOUND'
  | 'CONFLICT'
  | 'STALE_RESOURCE'
  | 'DEPENDENCY_UNAVAILABLE'
  | 'ASSIGNMENT_NOT_FOUND'
  | 'ASSIGNMENT_FORBIDDEN'
  | 'ASSIGNMENT_EXPIRED'
  | 'ASSIGNMENT_CONFLICT'
  | 'ONBOARDING_NOT_FOUND'
  | 'ONBOARDING_INVALID_TRANSITION'
  | 'ONBOARDING_INCOMPLETE'
  | 'RATE_LIMITED'
  | 'VALIDATION_ERROR'
  | 'INTERNAL_ERROR';

export interface PartnerApiErrorEnvelope {
  error: { code: PartnerApiErrorCode; message: string; requestId: string };
}

export interface PartnerApiOk<T> {
  data: T;
  requestId: string;
}

export function ok<T>(data: T, requestId: string): PartnerApiOk<T> {
  return { data, requestId };
}

export function err(code: PartnerApiErrorCode, message: string, requestId: string): PartnerApiErrorEnvelope {
  return { error: { code, message, requestId } };
}

export function newRequestId(): string {
  return `req_${Date.now()}_${Math.random().toString(36).slice(2)}`;
}
