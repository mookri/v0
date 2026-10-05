// Page-facing partner API client: server components call the partner BFF
// routes through here (same shape as the customer customer-api-client).
// Backend translation happens inside the BFF route handlers — pages never
// talk to api.navdhya.com directly.

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
};
