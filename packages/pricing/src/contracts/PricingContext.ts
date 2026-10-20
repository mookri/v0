export interface PricingContext {
  pujaId: string;
  panditId?: string;
  bookingDate: Date;
  discountCodes: string[];
}
