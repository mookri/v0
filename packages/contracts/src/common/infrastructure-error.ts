export type InfrastructureErrorCode =
  | 'unavailable'
  | 'timeout'
  | 'authentication'
  | 'rate_limited'
  | 'invalid_request'
  | 'dependency_failure';

export interface InfrastructureError {
  code: InfrastructureErrorCode;
  dependency: string;
  operation: string;
  retryable: boolean;
  cause?: unknown;
}

export function isRetryable(code: InfrastructureErrorCode): boolean {
  return code === 'timeout' || code === 'unavailable' || code === 'rate_limited' || code === 'dependency_failure';
}
