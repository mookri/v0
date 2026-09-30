import { DomainEvent } from '../ports/EventPublisher';
import { BookingId } from '../domain/BookingId';

export type BookingConfirmed = DomainEvent<{ bookingId: BookingId }>;
