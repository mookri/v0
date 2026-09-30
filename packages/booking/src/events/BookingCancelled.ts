import { DomainEvent } from '../ports/EventPublisher';
import { BookingId } from '../domain/BookingId';

export type BookingCancelled = DomainEvent<{ bookingId: BookingId; reason?: string }>;
