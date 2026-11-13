import {
  BOOKING_STATUS_SECTION_MAP,
  type BookingSection,
  type BookingStatus,
} from '@navdhya/contracts/src/generated/booking-lifecycle';

export type { BookingSection };

export function bookingSection(status: BookingStatus): BookingSection {
  return BOOKING_STATUS_SECTION_MAP[status];
}

export function statusLabel(status: BookingStatus): string {
  return status.toLowerCase().replace(/_/g, ' ');
}

export function formatBookingDate(startTime: Date): { date: string; time: string } {
  const d = new Date(startTime);
  return { date: d.toISOString().slice(0, 10), time: d.toISOString().slice(11, 16) };
}
