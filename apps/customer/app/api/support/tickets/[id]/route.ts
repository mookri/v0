import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const { data: ticket } = await laravelGet<unknown>(
      actor.token,
      `/customers/support/tickets/${encodeURIComponent((await params).id)}`,
    );
    return Response.json(ok(ticket as Record<string, unknown>, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Ticket is unavailable.', requestId);
  }
}

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  const id = encodeURIComponent((await params).id);
  let body: Record<string, unknown> = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  try {
    const { data: ticket } = await laravelPost<unknown>(actor.token, `/customers/support/tickets/${id}/messages`, body);
    return Response.json(ok(ticket as Record<string, unknown>, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Ticket is unavailable.', requestId);
  }
}
