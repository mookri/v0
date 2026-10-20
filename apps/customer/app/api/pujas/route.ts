import { newRequestId, ok } from '@/application/api-envelope';
import { getMockCatalog } from '@/application/pujas-data';
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

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ items: getMockCatalog() }, requestId), {
      headers: { 'Cache-Control': 'private, max-age=60' },
    });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    // Catalog items endpoint returns kernel envelope; unwrap and filter ACTIVE
    const { data: items } = await laravelGet<CatalogItem[]>(
      actor.token,
      '/catalog/items',
      { status: 'ACTIVE' }
    );

    const pujas = (items ?? []).map((item) => ({
      id: item.id,
      title: item.name,
      kind: 'puja' as const,
      sku: item.sku,
      currency: item.currency,
      price_minor: item.price_minor,
      description: item.description,
    }));

    return Response.json(
      ok({ items: pujas }, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } }
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Puja catalogue is unavailable.', requestId);
  }
}