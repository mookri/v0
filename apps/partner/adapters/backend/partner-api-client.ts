// Page-facing partner API client: server components call the partner BFF
// routes through here (same shape as the customer customer-api-client).
// Backend translation happens inside the BFF route handlers — pages never
// talk to api.navdhya.com directly.

import type {
  VendorCreateInput,
  VendorInventoryRow,
  VendorListPayload,
  VendorOrder,
  VendorOrderInput,
  VendorPayout,
  VendorProduct,
  VendorProductsPayload,
  VendorProfile,
} from '@/application/vendor';

export interface PartnerApiErrorShape {
  code: string;
  status: number;
  requestId: string;
}

export class PartnerPageApiError extends Error {
  code: string;
  status: number;
  requestId: string;

  constructor(shape: PartnerApiErrorShape) {
    super(shape.code);
    this.code = shape.code;
    this.status = shape.status;
    this.requestId = shape.requestId;
  }
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(path, {
    ...init,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', ...(init?.headers ?? {}) },
  });
  const body = await res.json().catch(() => null);
  if (!res.ok) {
    throw new PartnerPageApiError({
      code: body?.error?.code ?? 'INTERNAL_ERROR',
      status: res.status,
      requestId: body?.error?.requestId ?? 'unknown',
    });
  }
  return body?.data as T;
}

export const partnerApi = {
  // Temple directory (partner surface)
  getTemples: (params?: {
    city?: string;
    state?: string;
    amenities?: string[];
    lat?: number;
    lng?: number;
    radius?: number;
    q?: string;
    sort?: string;
    limit?: number;
  }) => {
    const qs = new URLSearchParams();
    if (params?.city) qs.set('city', params.city);
    if (params?.state) qs.set('state', params.state);
    if (params?.amenities) qs.set('amenities', params.amenities.join(','));
    if (params?.lat !== undefined) qs.set('lat', String(params.lat));
    if (params?.lng !== undefined) qs.set('lng', String(params.lng));
    if (params?.radius !== undefined) qs.set('radius', String(params.radius));
    if (params?.q) qs.set('q', params.q);
    if (params?.sort) qs.set('sort', params.sort);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples${suffix}`);
  },
  getTemple: (id: string) => request(`/api/temples/${encodeURIComponent(id)}`),

  // Temple dashboard
  getTempleDashboardOverview: (templeId: string) =>
    request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/overview`),
  getTempleDashboardPujas: (templeId: string, params?: { status?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/pujas${suffix}`);
  },
  getTempleDashboardBookings: (templeId: string, params?: { status?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/bookings${suffix}`);
  },
  getTempleDashboardCalendar: (templeId: string, year?: number, month?: number) => {
    const qs = new URLSearchParams();
    if (year !== undefined) qs.set('year', String(year));
    if (month !== undefined) qs.set('month', String(month));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/calendar${suffix}`);
  },
  getTempleDashboardAnalytics: (templeId: string, months?: number) => {
    const qs = new URLSearchParams();
    if (months !== undefined) qs.set('months', String(months));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/analytics${suffix}`);
  },
  getTempleDashboardSettings: (templeId: string) =>
    request(`/api/temples/${encodeURIComponent(templeId)}/dashboard/settings`),

  // Temple managers (partner self-serve)
  getTempleManagers: (templeId: string) =>
    request(`/api/temples/${encodeURIComponent(templeId)}/managers`),

  // Pandit self-serve (W1f)
  getPanditProfile: () => request('/api/pandit/profile'),
  getPanditAssignments: (params?: { status?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/pandit/assignments${suffix}`);
  },
  getPanditEarnings: () => request('/api/pandit/earnings'),
  getPanditReviews: () => request('/api/pandit/reviews'),
  updatePanditProfile: (body: Record<string, unknown>) =>
    request('/api/pandit/profile', { method: 'PATCH', body: JSON.stringify(body) }),
  getPanditAvailability: () => request('/api/pandit/availability'),
  replacePanditAvailability: (body: unknown) =>
    request('/api/pandit/availability', { method: 'PUT', body: JSON.stringify(body) }),
  confirmPanditAssignment: (id: string) =>
    request(`/api/pandit/assignments/${encodeURIComponent(id)}/confirm`, { method: 'POST' }),
  declinePanditAssignment: (id: string, reason?: string) =>
    request(`/api/pandit/assignments/${encodeURIComponent(id)}/decline`, { method: 'POST', body: JSON.stringify({ reason: reason ?? '' }) }),
  completePanditAssignment: (id: string) =>
    request(`/api/pandit/assignments/${encodeURIComponent(id)}/complete`, { method: 'POST' }),

  // Vendor surface (P12)
  getVendors: (params?: { limit?: number; cursor?: string }) => {
    const qs = new URLSearchParams();
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    if (params?.cursor) qs.set('cursor', params.cursor);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request<VendorListPayload<VendorProfile>>(`/api/vendors${suffix}`);
  },
  createVendor: (body: VendorCreateInput) =>
    request<VendorProfile>('/api/vendors', { method: 'POST', body: JSON.stringify(body) }),
  getVendor: (vendorId: string) => request<VendorProfile>(`/api/vendors/${encodeURIComponent(vendorId)}`),
  updateVendor: (
    vendorId: string,
    body: { name?: string; description?: string | null; city?: string | null; state?: string | null },
  ) => request<VendorProfile>(`/api/vendors/${encodeURIComponent(vendorId)}`, { method: 'PATCH', body: JSON.stringify(body) }),

  getVendorProducts: (vendorId: string, params?: { limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request<VendorProductsPayload>(`/api/vendors/${encodeURIComponent(vendorId)}/products${suffix}`);
  },
  createVendorProduct: (
    vendorId: string,
    body: {
      sku: string;
      name: string;
      description?: string | null;
      currency?: string;
      price_minor?: number;
      attributes?: Record<string, unknown> | null;
    },
  ) => request<VendorProduct>(`/api/vendors/${encodeURIComponent(vendorId)}/products`, { method: 'POST', body: JSON.stringify(body) }),
  updateVendorProduct: (
    vendorId: string,
    itemId: string,
    body: {
      name?: string;
      description?: string | null;
      currency?: string;
      price_minor?: number;
      attributes?: Record<string, unknown> | null;
    },
  ) =>
    request<VendorProduct>(`/api/vendors/${encodeURIComponent(vendorId)}/products/${encodeURIComponent(itemId)}`, {
      method: 'PATCH',
      body: JSON.stringify(body),
    }),

  getVendorInventory: (vendorId: string, params?: { limit?: number; cursor?: string }) => {
    const qs = new URLSearchParams();
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    if (params?.cursor) qs.set('cursor', params.cursor);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request<VendorListPayload<VendorInventoryRow>>(`/api/vendors/${encodeURIComponent(vendorId)}/inventory${suffix}`);
  },
  adjustVendorInventory: (vendorId: string, body: { sku: string; delta: number }) =>
    request<VendorInventoryRow>(`/api/vendors/${encodeURIComponent(vendorId)}/inventory/adjust`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  getVendorOrders: (vendorId: string, params?: { status?: string; limit?: number; cursor?: string }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    if (params?.cursor) qs.set('cursor', params.cursor);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request<VendorListPayload<Omit<VendorOrder, 'items'>>>(`/api/vendors/${encodeURIComponent(vendorId)}/orders${suffix}`);
  },
  placeVendorOrder: (vendorId: string, body: VendorOrderInput) =>
    request<VendorOrder>(`/api/vendors/${encodeURIComponent(vendorId)}/orders`, { method: 'POST', body: JSON.stringify(body) }),
  getVendorOrder: (vendorId: string, orderId: string) =>
    request<VendorOrder>(`/api/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}`),
  advanceVendorOrder: (vendorId: string, orderId: string) =>
    request<VendorOrder>(`/api/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}/advance`, {
      method: 'POST',
      body: '{}',
    }),
  cancelVendorOrder: (vendorId: string, orderId: string) =>
    request<VendorOrder>(`/api/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}/cancel`, {
      method: 'POST',
      body: '{}',
    }),

  getVendorPayouts: (vendorId: string, params?: { status?: string; limit?: number; cursor?: string }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    if (params?.cursor) qs.set('cursor', params.cursor);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request<VendorListPayload<VendorPayout>>(`/api/vendors/${encodeURIComponent(vendorId)}/payouts${suffix}`);
  },
};
