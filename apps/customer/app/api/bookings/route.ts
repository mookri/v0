import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ items: [] as Array<Record<string, unknown>> }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const response = await laravelGet<Array<Record<string, unknown>>>(
      actor.token,
      '/booking/reservations'
    );

    const items = (response.data ?? []).map((b) => ({
      id: String(b.id ?? ''),
      status: String(b.status ?? ''),
      date: b.scheduled_at ? new Date(String(b.scheduled_at)).toISOString().split('T')[0] : '',
      itemCount: 1,
    }));

    return Response.json(ok({ items }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Bookings list is unavailable.', requestId);
  }
}

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(
      err('VALIDATION_ERROR', 'Booking creation unavailable in mock mode.', requestId),
      { status: 422 }
    );
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  let body: {
    service_sku: string;
    scheduled_at: string;
    duration_minutes?: number;
    address_id?: string;
    address_snapshot?: Record<string, unknown>;
  };
  try {
    body = await req.json();
  } catch {
    return Response.json(
      err('VALIDATION_ERROR', 'Invalid JSON body.', requestId),
      { status: 422 }
    );
  }

  if (!body?.service_sku || !body?.scheduled_at) {
    return Response.json(
      err('VALIDATION_ERROR', 'service_sku and scheduled_at are required.', requestId),
      { status: 422 }
    );
  }

  // Forward Idempotency-Key header
  const idempotencyKey = req.headers.get('Idempotency-Key');
  const extraHeaders: Record<string, string> = {};
  if (idempotencyKey) {
    extraHeaders['Idempotency-Key'] = idempotencyKey;
  }

  try {
    const { data: booking, etag } = await laravelPost<Record<string, unknown>>(
      actor.token,
      '/booking/reservations',
      body,
      {}, // codeMap
      extraHeaders,
      'v2',
    );
    const headers: Record<string, string> = {};
    if (etag) headers['ETag'] = etag;
    return Response.json(ok(booking, requestId), { status: 201, headers });
  } catch (e: any) {
    if (e.status === 422) {
      return Response.json(
        err('VALIDATION_ERROR', e.message ?? 'Booking validation failed.', requestId, { fields: e.fields }),
        { status: 422 }
      );
    }
    if (e.status === 409) {
      const code = e.code === 'CONFLICT' ? 'BOOKING_SLOT_CONFLICT' : 'CONFLICT';
      return Response.json(err(code, e.message ?? 'Slot conflict.', requestId), { status: 409 });
    }
    if (e.status === 404) {
      return Response.json(err('BOOKING_NOT_FOUND', 'Service not found.', requestId), { status: 404 });
    }
    if (e.status === 403) {
      return Response.json(err('FORBIDDEN', 'Cannot create booking.', requestId), { status: 403 });
    }
    return laravelErrorResponse(e, 'Booking creation is unavailable.', requestId);
  }
}