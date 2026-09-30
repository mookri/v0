import { CustomerSession } from '../session';
import type { CustomerHomeViewModel } from '../../application/home/get-customer-home';
import type { CustomerBookingCard } from '../../application/bookings/list-customer-bookings';
import type { CustomerBookingDetails } from '../../application/bookings/get-customer-booking-details';
import type { CustomerProfileViewModel } from '../../application/profile/customer-profile';

// BFF-ready seam (§12.87): UI depends on this contract, not on transport.
// Today backed by local orchestration; Phase 13 swaps in the BFF client.
export interface CustomerBackend {
  getSession(): Promise<CustomerSession>;
  getHome(): Promise<CustomerHomeViewModel>;
  getBookings(): Promise<CustomerBookingCard[]>;
  getBooking(id: string): Promise<CustomerBookingDetails | null>;
  getProfile(): Promise<CustomerProfileViewModel | null>;
}
