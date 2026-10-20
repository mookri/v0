export type EventCategory = 'domain' | 'product' | 'operational' | 'security' | 'communication' | 'analytics';
export type EventEnvironment = 'development' | 'staging' | 'production';
export type PiiClass = 'PUBLIC' | 'INTERNAL' | 'SENSITIVE' | 'RESTRICTED';

export interface EventEnvelope<T> {
  eventId: string;
  eventType: string;
  eventVersion: number;
  occurredAt: string;
  producer: string;
  environment: EventEnvironment;
  actor?: { userId?: string; product?: string; sessionId?: string };
  correlationId?: string;
  causationId?: string;
  data: T;
}

export interface EventBus {
  publish<T>(event: EventEnvelope<T>): Promise<void>;
}

export interface EventConsumer {
  handle(event: EventEnvelope<unknown>): Promise<void>;
}

export function validateEnvelope<T>(event: EventEnvelope<T>): string[] {
  const errors: string[] = [];
  if (!event.eventId) errors.push('eventId required');
  if (!event.eventType) errors.push('eventType required');
  if (!event.eventVersion) errors.push('eventVersion required');
  if (!event.occurredAt) errors.push('occurredAt required');
  if (!event.producer) errors.push('producer required');
  return errors;
}

export function dedupKey(event: { eventId: string; producer: string }): string {
  return `${event.producer}:${event.eventId}`;
}
