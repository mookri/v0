import { DomainEvent } from '../ports/EventPublisher';
import { Booking } from '../domain/Booking';

export type BookingCreated = DomainEvent<Pick<Booking, 'id' | 'customer' | 'items' | 'schedule'>>;
