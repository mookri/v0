export type TimeoutClass = 'fast' | 'normal' | 'long';

export const TIMEOUT_MS: Record<TimeoutClass, number> = {
  fast: 2000,
  normal: 8000,
  long: 25000,
};

export const RETRYABLE_STATUS = new Set([408, 429, 502, 503, 504]);

export function isRetryableStatus(status: number): boolean {
  return RETRYABLE_STATUS.has(status);
}