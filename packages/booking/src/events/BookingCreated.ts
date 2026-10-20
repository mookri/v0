import { DomainEvent } from '@navdhya/booking/src/ports/EventPublisher';
import { Booking } from '@navdhya/booking/src/domain/Booking';

export type BookingCreated = DomainEvent<Pick<Booking, 'id' | 'customer' | 'items' | 'schedule'>>;
