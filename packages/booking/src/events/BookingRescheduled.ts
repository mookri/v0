import { DomainEvent } from '../ports/EventPublisher';
import { BookingId } from '../domain/BookingId';
import { BookingSchedule } from '../domain/BookingSchedule';

export type BookingRescheduled = DomainEvent<{ bookingId: BookingId; schedule: BookingSchedule }>;
