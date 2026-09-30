import { EventCategory, PiiClass } from './envelope';

export interface EventDefinition {
  type: string;
  version: number;
  owner: string;
  description: string;
  category: EventCategory;
  classification: PiiClass;
  piiFields: string[];
  retention: string;
  consumers: string[];
}

export const EVENT_REGISTRY: EventDefinition[] = [
  { type: 'IdentityCreated', version: 1, owner: 'identity', description: 'Canonical identity record created.', category: 'domain', classification: 'INTERNAL', piiFields: [], retention: 'business', consumers: ['analytics'] },
  { type: 'AuthenticationSucceeded', version: 1, owner: 'identity', description: 'Credential verified for an identity.', category: 'security', classification: 'SENSITIVE', piiFields: [], retention: 'security', consumers: ['analytics', 'operations'] },
  { type: 'SessionCreated', version: 1, owner: 'identity', description: 'Product session issued.', category: 'security', classification: 'INTERNAL', piiFields: [], retention: 'security', consumers: ['analytics'] },
  { type: 'SessionRevoked', version: 1, owner: 'identity', description: 'Product session revoked.', category: 'security', classification: 'INTERNAL', piiFields: [], retention: 'security', consumers: ['analytics'] },
  { type: 'PujaUpdated', version: 1, owner: 'puja', description: 'Canonical puja entity changed.', category: 'domain', classification: 'PUBLIC', piiFields: [], retention: 'business', consumers: ['search', 'analytics'] },
  { type: 'PujaDeactivated', version: 1, owner: 'puja', description: 'Puja withdrawn from discovery.', category: 'domain', classification: 'PUBLIC', piiFields: [], retention: 'business', consumers: ['search', 'analytics'] },
  { type: 'BookingCreated', version: 1, owner: 'booking', description: 'Booking entered the lifecycle.', category: 'domain', classification: 'INTERNAL', piiFields: [], retention: 'business', consumers: ['communication', 'analytics', 'operations'] },
  { type: 'BookingConfirmed', version: 1, owner: 'booking', description: 'Booking entered confirmed state.', category: 'domain', classification: 'INTERNAL', piiFields: [], retention: 'business', consumers: ['communication', 'analytics', 'recommendations', 'operations'] },
  { type: 'BookingCancelled', version: 1, owner: 'booking', description: 'Booking cancelled.', category: 'domain', classification: 'INTERNAL', piiFields: [], retention: 'business', consumers: ['communication', 'analytics'] },
  { type: 'BookingRescheduled', version: 1, owner: 'booking', description: 'Booking schedule changed.', category: 'domain', classification: 'INTERNAL', piiFields: [], retention: 'business', consumers: ['communication', 'analytics'] },
  { type: 'PaymentSucceeded', version: 1, owner: 'payment', description: 'Payment captured.', category: 'domain', classification: 'RESTRICTED', piiFields: [], retention: 'finance', consumers: ['booking', 'analytics'] },
  { type: 'PaymentFailed', version: 1, owner: 'payment', description: 'Payment attempt failed.', category: 'domain', classification: 'RESTRICTED', piiFields: [], retention: 'finance', consumers: ['analytics', 'operations'] },
  { type: 'SearchPerformed', version: 1, owner: 'search', description: 'Discovery query executed.', category: 'product', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics', 'ranking-signals'] },
  { type: 'SearchResultClicked', version: 1, owner: 'search', description: 'Discovery result selected.', category: 'product', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics', 'ranking-signals'] },
  { type: 'ZeroResultReturned', version: 1, owner: 'search', description: 'Query produced no results.', category: 'product', classification: 'PUBLIC', piiFields: [], retention: 'product', consumers: ['analytics'] },
  { type: 'NotificationCreated', version: 1, owner: 'notifications', description: 'Notification intent materialized.', category: 'communication', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['communication', 'analytics'] },
  { type: 'MessageDelivered', version: 1, owner: 'communication', description: 'Provider confirmed delivery.', category: 'communication', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics'] },
  { type: 'MessageFailed', version: 1, owner: 'communication', description: 'Delivery exhausted retries.', category: 'communication', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics', 'operations'] },
  { type: 'ExperimentAssigned', version: 1, owner: 'experimentation', description: 'Subject assigned to a variant.', category: 'analytics', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics'] },
  { type: 'ExperimentExposed', version: 1, owner: 'experimentation', description: 'Assigned experience actually rendered.', category: 'analytics', classification: 'INTERNAL', piiFields: [], retention: 'product', consumers: ['analytics'] },
];

export function lookupEvent(type: string, version: number): EventDefinition | null {
  return EVENT_REGISTRY.find((e) => e.type === type && e.version === version) ?? null;
}
