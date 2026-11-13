export interface PujaSummaryView {
  id: string;
  name: string;
  summary?: string;
  category?: string;
}

export interface PanditSummaryView {
  id: string;
  name: string;
}

export interface UserSummaryView {
  id: string;
  name: string;
}

export interface AvailabilityQueryView {
  pujaId: string;
  from: string;
  to: string;
}

export interface PriceQuoteView {
  quoteId: string;
  total: { amount: number; currency: string };
  expiresAt: string;
}

