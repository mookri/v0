export type ExceptionSeverity = 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
export type ExceptionStatus = 'OPEN' | 'ACKNOWLEDGED' | 'RESOLVED' | 'CANCELLED';

export interface FulfillmentException {
  id: string;
  fulfillmentId: string;
  type: string;
  severity: ExceptionSeverity;
  status: ExceptionStatus;
  openedAt: string;
  resolvedAt?: string;
}

export function stableExceptionKey(fulfillmentId: string, type: string, windowId: string): string {
  return `exc:${fulfillmentId}:${type}:${windowId}`;
}
