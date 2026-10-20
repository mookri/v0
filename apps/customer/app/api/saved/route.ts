import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired saved list (CUSTOMER_BACKEND=laravel): GET /api/saved
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data: backendData } = await laravelGet<{ items?: unknown[] } | unknown[]>(actor.token, '/customers/saved');

    // Handle both array and object-with-items responses
    const rawItems = Array.isArray(backendData) ? backendData : (backendData?.items ?? []);

    // Enrich saved items for BFF consumption (interim title=ref mapping)
    const enrichedItems = rawItems.map((item: any) => {
      const refId = item.ref_id ?? item.refId ?? '';
      const refType = item.ref_type ?? item.refType ?? 'puja';
      const href =
        refType === 'puja' ? `/pujas/${encodeURIComponent(refId)}`
        : refType === 'service' ? '/book/start'
        : '/discover';
      return {
        id: item.id,
        type: refType,
        title: refId,
        href,
      };
    });

    return Response.json(ok({ items: enrichedItems }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Saved items unavailable.', requestId);
  }
}

// Wired saved add (CUSTOMER_BACKEND=laravel): POST /api/saved
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { ref_type?: string; ref_id?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const refTypes = ['puja', 'content', 'service'] as const;
  if (!body.ref_type || !body.ref_id) {
    return Response.json(err('VALIDATION_ERROR', 'ref_type and ref_id are required.', requestId), { status: 422 });
  }
  if (!refTypes.includes(body.ref_type as (typeof refTypes)[number])) {
    return Response.json(err('VALIDATION_ERROR', 'ref_type must be one of puja, content, service.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      '/customers/saved',
      { ref_type: body.ref_type, ref_id: body.ref_id }
    );
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to add saved item.', requestId);
  }
}