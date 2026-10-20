import { DomainEvent } from '@navdhya/booking/src/ports/EventPublisher';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';
import { BookingSchedule } from '@navdhya/booking/src/domain/BookingSchedule';

export type BookingRescheduled = DomainEvent<{ bookingId: BookingId; schedule: BookingSchedule }>;
