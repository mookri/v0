import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface CatalogItem {
  id: string;
  name: string;
  sku: string;
  currency: string;
  price_minor: number;
  description?: string;
}

interface RouteParams {
  params: Promise<{ pujaId: string }>;
}

export async function GET(req: Request, { params }: RouteParams) {
  const requestId = newRequestId();
  const { pujaId } = await params;

  if (!laravelEnabled()) {
    const ctx = anonymousContext('customer.puja.detail', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('NOT_FOUND', 'Puja not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data: item } = await laravelGet<CatalogItem>(
      actor.token,
      `/catalog/items/${encodeURIComponent(pujaId)}`
    );

    const puja = {
      id: item.id,
      title: item.name,
      kind: 'puja' as const,
      sku: item.sku,
      currency: item.currency,
      price_minor: item.price_minor,
      description: item.description,
    };

    return Response.json(ok(puja, requestId), {
      headers: { 'Cache-Control': 'private, max-age=60' },
    });
  } catch (e: any) {
    if (e.status === 404) {
      return Response.json(err('NOT_FOUND', 'Puja not found.', requestId), { status: 404 });
    }
    return laravelErrorResponse(e, 'Puja details are unavailable.', requestId);
  }
}