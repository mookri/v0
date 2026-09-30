export interface AnalyticsEvent<T = Record<string, unknown>> {
  eventType: string;
  eventVersion: number;
  occurredAt: string;
  product: string;
  surfaceId?: string;
  actor?: { userId?: string; anonymousId?: string };
  data: T;
}

export interface AnalyticsSink {
  publish(event: AnalyticsEvent): Promise<void>;
}

export interface MetricDefinition {
  id: string;
  name: string;
  description: string;
  owner: string;
  sourceEvents: string[];
  calculation: string;
  grain: string;
  version: number;
}

export const CORE_METRICS: MetricDefinition[] = [
  { id: 'confirmed-bookings', name: 'Confirmed bookings', description: 'Bookings entering confirmed state per day.', owner: 'booking', sourceEvents: ['BookingConfirmed'], calculation: 'count distinct bookingId', grain: 'day', version: 1 },
  { id: 'payment-success-rate', name: 'Payment success rate', description: 'Succeeded over initiated payments.', owner: 'payment', sourceEvents: ['PaymentSucceeded', 'PaymentFailed'], calculation: 'succeeded / initiated', grain: 'day', version: 1 },
  { id: 'message-delivery-rate', name: 'Message delivery rate', description: 'Delivered over dispatched messages.', owner: 'communication', sourceEvents: ['MessageDelivered', 'MessageFailed'], calculation: 'delivered / dispatched', grain: 'day', version: 1 },
  { id: 'search-conversion', name: 'Search conversion', description: 'Bookings attributed to search sessions.', owner: 'search', sourceEvents: ['SearchPerformed', 'BookingConfirmed'], calculation: 'attributed bookings / searches', grain: 'day', version: 1 },
];

// Fire-and-forget client: buffers locally, never rejects the business path.
export class AnalyticsClient {
  private readonly buffer: AnalyticsEvent[] = [];
  constructor(private readonly sink?: AnalyticsSink) {}

  track(event: AnalyticsEvent): void {
    this.buffer.push(event);
    if (this.sink) {
      this.sink.publish(event).catch(() => {});
    }
  }

  flush(): AnalyticsEvent[] {
    return this.buffer.splice(0);
  }
}
