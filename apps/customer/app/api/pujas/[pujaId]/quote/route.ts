import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface RouteParams {
  params: Promise<{ pujaId: string }>;
}

interface QuoteLine {
  sku: string;
  quantity: number;
  pandit_id?: number;
  market?: string;
}

interface QuoteRequest {
  currency: string;
  market?: string;
  lines: QuoteLine[];
}

export async function POST(req: Request, { params }: RouteParams) {
  const requestId = newRequestId();
  const { pujaId } = await params;

  if (!laravelEnabled()) {
    const ctx = anonymousContext('customer.puja.quote', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(
      err('VALIDATION_ERROR', 'Quote service unavailable in mock mode.', requestId),
      { status: 422 }
    );
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  let body: QuoteRequest;
  try {
    body = await req.json();
  } catch {
    return Response.json(
      err('VALIDATION_ERROR', 'Invalid JSON body.', requestId),
      { status: 422 }
    );
  }

  if (!body?.currency || !Array.isArray(body.lines) || body.lines.length === 0) {
    return Response.json(
      err('VALIDATION_ERROR', 'currency and lines[] are required.', requestId),
      { status: 422 }
    );
  }

  // Validate each line has required fields
  for (const line of body.lines) {
    if (!line.sku || typeof line.quantity !== 'number' || line.quantity <= 0) {
      return Response.json(
        err('VALIDATION_ERROR', 'Each line requires sku (string) and quantity (positive integer).', requestId),
        { status: 422 }
      );
    }
  }

  try {
    const { data: quote } = await laravelPost<{ data: Record<string, unknown> }>(
      actor.token,
      '/catalog/quote',
      body
    );
    return Response.json(ok({ ...quote, object: 'price_quote' }, requestId));
  } catch (e: any) {
    if (e.status === 422) {
      return Response.json(
        err('VALIDATION_ERROR', e.message ?? 'Quote validation failed.', requestId),
        { status: 422 }
      );
    }
    if (e.status === 404) {
      return Response.json(err('NOT_FOUND', 'Referenced SKU not found.', requestId), { status: 404 });
    }
    return laravelErrorResponse(e, 'Quote service is unavailable.', requestId);
  }
}