export interface MoneyView {
  amount: number;
  currency: string;
  formatted?: string;
}

export interface BookingRef {
  id: string;
  status: string;
  schedule: { startsAt: string; displayDate: string; displayTime: string };
  puja: { id: string; name: string };
  pandit?: { id: string; name: string };
  price?: MoneyView;
}

export interface GetBookingResponse {
  booking: BookingRef;
}

