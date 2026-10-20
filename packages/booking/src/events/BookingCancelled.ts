import { DomainEvent } from '@navdhya/booking/src/ports/EventPublisher';
import { BookingId } from '@navdhya/booking/src/domain/BookingId';

export type BookingCancelled = DomainEvent<{ bookingId: BookingId; reason?: string }>;
