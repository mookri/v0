export interface CustomerApiErrorShape {
  code: string;
  status: number;
  requestId: string;
  /** Upstream Retry-After header (seconds as string), when present. */
  retryAfter?: string;
}

export class CustomerApiError extends Error {
  code: string;
  status: number;
  requestId: string;
  retryAfter?: string;

  constructor(shape: CustomerApiErrorShape) {
    super(shape.code);
    this.code = shape.code;
    this.status = shape.status;
    this.requestId = shape.requestId;
    this.retryAfter = shape.retryAfter;
  }
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(path, {
    ...init,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', ...(init?.headers ?? {}) },
  });
  const body = await res.json();
  if (!res.ok) {
    throw new CustomerApiError({
      code: body?.error?.code ?? 'INTERNAL_ERROR',
      status: res.status,
      requestId: body?.error?.requestId ?? 'unknown',
    });
  }
  return body.data as T;
}

export const customerApi = {
  getHome: () => request('/api/home'),
  getBookings: () => request('/api/bookings'),
  getBooking: (id: string) => request(`/api/bookings/${id}`),
  getBookingHistory: (id: string) => request(`/api/bookings/${id}/history`),
  cancelBooking: (id: string, idempotencyKey: string) =>
    request(`/api/bookings/${id}/cancel`, {
      method: 'POST',
      headers: { 'Idempotency-Key': idempotencyKey },
      body: JSON.stringify({}),
    }),
  rescheduleBooking: (id: string, newStartTime: string, idempotencyKey: string) =>
    request(`/api/bookings/${id}/reschedule`, {
      method: 'POST',
      headers: { 'Idempotency-Key': idempotencyKey },
      body: JSON.stringify({ newStartTime }),
    }),
  getProfile: () => request('/api/profile'),
  getVerifications: () => request('/api/verifications'),
  addVerification: (type: 'phone' | 'email', contact: string) =>
    request('/api/verifications', {
      method: 'POST',
      body: JSON.stringify({ type, contact }),
    }),
  confirmVerification: (id: string, code: string) =>
    request('/api/verifications/confirm', {
      method: 'POST',
      body: JSON.stringify({ id, code }),
    }),
  getSaved: () => request('/api/saved'),
  addSaved: (refId: string, title?: string) =>
    request('/api/saved', {
      method: 'POST',
      body: JSON.stringify({ ref_id: refId, title }),
    }),
  deleteSaved: (id: string) =>
    request(`/api/saved/${id}`, { method: 'DELETE' }),
  getNotifications: () => request('/api/notifications'),
  getUnreadCount: () => request<{ count: number }>('/api/notifications/unread-count'),
  markNotificationRead: (id: string) =>
    request(`/api/notifications/${id}/read`, { method: 'POST' }),
  markAllNotificationsRead: () =>
    request('/api/notifications/read-all', { method: 'POST' }),
  getSettings: () => request('/api/settings'),

  // Puja / Catalog
  getPujas: () => request('/api/pujas'),
  getPuja: (id: string) => request(`/api/pujas/${id}`),
  quotePuja: (id: string, payload: {
    currency: string;
    market?: string;
    lines: Array<{ sku: string; quantity: number; pandit_id?: number; market?: string }>;
  }) =>
    request(`/api/pujas/${id}/quote`, {
      method: 'POST',
      body: JSON.stringify(payload),
    }),

  // Booking creation
  createBooking: (payload: {
    service_sku: string;
    scheduled_at: string;
    duration_minutes?: number;
    address_id?: string;
    address_snapshot?: Record<string, unknown>;
  }, idempotencyKey: string) =>
    request('/api/bookings', {
      method: 'POST',
      headers: { 'Idempotency-Key': idempotencyKey },
      body: JSON.stringify(payload),
    }),

  // Pandit directory
  getPandits: (params?: { specialization?: string; market?: string; min_rating?: number; limit?: number; cursor?: string }) => {
    const qs = new URLSearchParams();
    if (params?.specialization) qs.set('specialization', params.specialization);
    if (params?.market) qs.set('market', params.market);
    if (params?.min_rating !== undefined) qs.set('min_rating', String(params.min_rating));
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    if (params?.cursor) qs.set('cursor', params.cursor);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/pandits${suffix}`);
  },
  getPandit: (id: string) => request(`/api/pandits/${id}`),
  getPanditAvailability: (id: string, params?: { start_date?: string; end_date?: string }) => {
    const qs = new URLSearchParams();
    if (params?.start_date) qs.set('start_date', params.start_date);
    if (params?.end_date) qs.set('end_date', params.end_date);
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/pandits/${id}/availability${suffix}`);
  },

  // Instant-book search (B2): date + slot (+ optional puja) → assignable
  // pandits + open temple slots (backend-gated; never invented here).
  instantSearch: (params: { date: string; start: string; end: string; puja_id?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    qs.set('date', params.date);
    qs.set('start', params.start);
    qs.set('end', params.end);
    if (params.puja_id) qs.set('puja_id', params.puja_id);
    if (params.limit !== undefined) qs.set('limit', String(params.limit));
    return request(`/api/instant-search?${qs.toString()}`);
  },

  // Temple directory
  getTemples: (params?: { city?: string; state?: string; amenities?: string[]; lat?: number; lng?: number; radius?: number; q?: string; sort?: string; limit?: number }) => {
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
  getTemple: (id: string) => request(`/api/temples/${id}`),

  // Profile
  deleteProfile: (challengeId: string, code: string) =>
    request('/api/profile:delete', {
      method: 'POST',
      body: JSON.stringify({ challengeId, code }),
    }),
};