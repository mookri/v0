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

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const ctx = anonymousContext('customer.pujas', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(
      ok({ items: [] as Array<{ id: string; title: string; kind: 'puja'; sku: string; currency: string; price_minor: number; description?: string }> }, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } }
    );
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