export interface EventEnvelope<T = any> { eventId: string; type: string; timestamp: Date; data: T; }
