export function scopedIdempotencyKey(input: {
  actorId: string;
  operation: string;
  resourceId: string;
  key: string;
}): string {
  return `${input.actorId}:${input.operation}:${input.resourceId}:${input.key}`;
}

export function samePayloadHash(a: unknown, b: unknown): boolean {
  return JSON.stringify(a) === JSON.stringify(b);
}

// Client-side idempotency key generation + sessionStorage persistence
const IDEMPOTENCY_PREFIX = 'navdhya_idempotency_';

export function generateIdempotencyKey(): string {
  return `idem_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
}

export function storeIdempotencyKey(resourceId: string, key: string): void {
  if (typeof window !== 'undefined') {
    sessionStorage.setItem(`${IDEMPOTENCY_PREFIX}${resourceId}`, key);
  }
}

export function getStoredIdempotencyKey(resourceId: string): string | null {
  if (typeof window !== 'undefined') {
    return sessionStorage.getItem(`${IDEMPOTENCY_PREFIX}${resourceId}`);
  }
  return null;
}

export function clearIdempotencyKey(resourceId: string): void {
  if (typeof window !== 'undefined') {
    sessionStorage.removeItem(`${IDEMPOTENCY_PREFIX}${resourceId}`);
  }
}