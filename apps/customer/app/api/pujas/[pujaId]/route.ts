import { newRequestId, ok, err } from '@/application/api-envelope';
import { findMockCatalogItem } from '@/application/pujas-data';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface CatalogItem {
  id: string;
  name: string;
  sku: string;
  currency: string;
  price_minor: number;
  description?: string;
  status?: string;
}

interface RouteParams {
  params: Promise<{ pujaId: string }>;
}

export async function GET(req: Request, { params }: RouteParams) {
  const requestId = newRequestId();
  const { pujaId } = await params;

  if (!laravelEnabled()) {
    const item = findMockCatalogItem(pujaId);
    if (!item) {
      return Response.json(err('NOT_FOUND', 'Puja not found.', requestId), { status: 404 });
    }
    return Response.json(ok(item, requestId), {
      headers: { 'Cache-Control': 'private, max-age=60' },
    });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data: item } = await laravelGet<CatalogItem>(
      actor.token,
      `/catalog/items/${encodeURIComponent(pujaId)}`
    );

    // Public detail is ACTIVE-only, matching the list endpoint. Drafts and
    // archived items must not be readable by guessing an id.
    if (item.status !== undefined && item.status !== 'ACTIVE') {
      return Response.json(err('NOT_FOUND', 'Puja not found.', requestId), { status: 404 });
    }

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