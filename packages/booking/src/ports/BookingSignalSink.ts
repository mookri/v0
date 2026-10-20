/**
 * Signals port (plan B2): booking emits SEMANTIC FACTS through this port;
 * the composition layer adapts them to SignalEnvelopes via the analytics
 * package's server emitters bound to a tx outbox. Capability packages must
 * never import the analytics package (architecture tests), while emission
 * still runs inside the business transaction.
 */
export interface BookingConfirmedSignal {
  /** booking.id — the aggregate this signal describes. */
  aggregateId: string;
  /** INR major units (registry `value` semantics). */
  value: number;
  currency: string;
  /** Catalog category of the confirmed booking (registry `service_category`). */
  serviceCategory: string;
  city?: string;
}

export interface BookingSignalSink {
  bookingConfirmed(signal: BookingConfirmedSignal): Promise<void>;
}
