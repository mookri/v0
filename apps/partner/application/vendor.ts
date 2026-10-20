// Vendor wire shapes (P12b backend serializes these; partner BFF routes
// and pages consume them — parity rows in parity-partners.md document the
// same fields). Snake case matches the kernel envelope as-is.

export interface VendorProfile {
  id: string;
  object: 'vendor';
  application_id: string;
  /** Platform user id (Laravel: numeric; dev mock: raw actor id). */
  owner_user_id: string | number;
  name: string;
  description: string | null;
  city: string | null;
  state: string | null;
  status: 'ACTIVE' | 'SUSPENDED' | string;
  verification_status: 'PENDING' | 'VERIFIED' | string;
  created_at: string;
  updated_at: string;
}

/** Catalog item scoped to a vendor (P12a Catalog listForVendor shape). */
export interface VendorProduct {
  id: string;
  sku: string;
  name: string;
  description: string | null;
  status: string;
  currency: string;
  price_minor: number;
  attributes: Record<string, unknown> | null;
  vendor_id: string | null;
}

export interface VendorInventoryRow {
  id: string;
  object: 'vendor_inventory';
  vendor_id: string;
  sku: string;
  on_hand: number;
  created_at: string;
  updated_at: string;
}

export interface VendorOrderLine {
  id: string;
  sku: string;
  name: string;
  quantity: number;
  unit_minor: number;
  line_minor: number;
}

export interface VendorOrder {
  id: string;
  object: 'vendor_order';
  vendor_id: string;
  reference: string;
  customer_name: string | null;
  status: 'NEW' | 'PROCESSING' | 'DISPATCHED' | 'DELIVERED' | 'CANCELLED' | string;
  currency: string;
  total_minor: number;
  created_at: string;
  updated_at: string;
  /** Inlined on detail/mutation responses; absent from list rows. */
  items?: VendorOrderLine[];
}

export interface VendorPayout {
  id: string;
  object: 'vendor_payout';
  vendor_id: string;
  period: string;
  status: 'PENDING' | 'PAID' | string;
  amount_minor: number;
  currency: string;
  reference: string | null;
  created_at: string;
  updated_at: string;
}

export interface PageMeta {
  limit?: number;
  has_more?: boolean;
  next_cursor?: string | null;
}

/** Cursor-paginated vendor list payload (vendors, inventory, orders, payouts). */
export interface VendorListPayload<T> {
  data: T[];
  meta: { page?: PageMeta; request_id?: string };
}

/** Products are limit-based on the backend (no cursor) — page payload. */
export interface VendorProductsPayload {
  data: VendorProduct[];
}

/** Request body for POST /api/vendors. */
export interface VendorCreateInput {
  name: string;
  description?: string | null;
  city?: string | null;
  state?: string | null;
}

/** Request body for POST /api/vendors/:vendorId/orders. */
export interface VendorOrderInput {
  reference: string;
  customer_name?: string | null;
  currency?: string;
  items: Array<{ sku: string; name: string; quantity: number; unit_minor: number }>;
}
