export interface CustomerApiErrorShape {
  code: string;
  status: number;
  requestId: string;
}

export class CustomerApiError extends Error {
  code: string;
  status: number;
  requestId: string;

  constructor(shape: CustomerApiErrorShape) {
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
};