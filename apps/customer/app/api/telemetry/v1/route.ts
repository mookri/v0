import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

/**
 * Identified Telemetry BFF Endpoint (E5 / E7).
 * Batched events submitted by authenticated devotees.
 * Enforces ANALYTICS consent check upstream; drops/records faithfully.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    // Mock / dev fallback
    return Response.json(ok({ status: 'accepted', stored: 1, dropped: 0 }, requestId), { status: 202 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  let body: unknown = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid JSON payload.', requestId), { status: 422 });
  }

  try {
    const { data } = await laravelPost<unknown>(actor.token, '/customers/events', body);
    return Response.json(ok(data, requestId), { status: 202 });
  } catch (e) {
    return laravelErrorResponse(e, 'Telemetry ingestion failed.', requestId);
  }
}

