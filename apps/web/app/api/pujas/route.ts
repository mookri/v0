import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelTenant } from '@/adapters/backend/laravel-client';

interface PublicPuja {
  id: string;
  sku: string;
  name: string;
  description?: string | null;
  currency: string;
  price_minor: number;
}

/**
 * GET /api/pujas — public catalog for the marketing site's Quick Book
 * (B1). Anonymous by design: the backend route
 * `/catalog/public/items` needs no session, so the BFF forwards with no
 * bearer and only the tenant id.
 *
 * Every failure path degrades to an EMPTY list, never an error: QuickBook
 * treats "no catalog" as its supported sample state, so a backend blip
 * must not break the page. Outage visibility is /api/readyz's job.
 * The Cache-Control header carries the freshness window (segment config
 * exports are disallowed under nextConfig.cacheComponents).
 */
export async function GET() {
  const requestId = newRequestId();
  const cache = { 'Cache-Control': 'public, max-age=60' };

  if (!laravelEnabled()) {
    return Response.json(ok({ items: [] }, requestId), { headers: cache });
  }

  try {
    const { data } = await laravelGet<PublicPuja[]>(
      null,
      '/catalog/public/items',
      { tenant_id: laravelTenant() },
      {},
      { timeout: 'fast', requestId },
    );

    const items = (Array.isArray(data) ? data : []).map((p) => ({
      id: String(p.id),
      sku: String(p.sku ?? ''),
      name: String(p.name ?? ''),
      description: typeof p.description === 'string' ? p.description : null,
      currency: String(p.currency ?? 'INR'),
      price_minor: Number(p.price_minor ?? 0),
    }));

    return Response.json(ok({ items }, requestId), { headers: cache });
  } catch {
    return Response.json(ok({ items: [] }, requestId), {
      headers: { 'Cache-Control': 'public, max-age=30' },
    });
  }
}
