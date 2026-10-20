import { laravelGet, laravelPost } from '@/adapters/backend/laravel-client';
import { mockError, mockLimit, mockVendorStore, queryOf, resolveOwnedVendor, uid, vendorRoute } from '@/server/vendors';
import type { VendorProduct, VendorProductsPayload } from '@/application/vendor';

// Vendor products: catalog items with vendor_id set (P12a ports) — the
// list is limit-based on the backend (no cursor), payload is { data }.

type ListContext = { params: Promise<{ vendorId: string }> };

export async function GET(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  return vendorRoute<VendorProduct[]>(req, {
    message: 'Vendor products are unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;
      const limit = mockLimit(queryOf(req));
      const rows = [...(mockVendorStore.products.get(vendorId) ?? [])].reverse();
      return { payload: { data: rows.slice(0, limit) } satisfies VendorProductsPayload };
    },
    call: (token) =>
      laravelGet<VendorProduct[]>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/products`, queryOf(req)),
    shape: (res) => ({ data: res.data }) satisfies VendorProductsPayload,
  });
}

export async function POST(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  let body: Partial<VendorProduct> = {};
  try {
    body = (await req.json()) as Partial<VendorProduct>;
  } catch {
    body = {};
  }

  return vendorRoute<VendorProduct>(req, {
    message: 'Vendor product creation is unavailable.',
    status: 201,
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const sku = body.sku ?? '';
      const name = (body.name ?? '').trim();
      if (sku.length < 1 || sku.length > 64) {
        return mockError('VALIDATION_ERROR', 'The sku field must be between 1 and 64 characters.', requestId, 422);
      }
      if (name.length < 2 || name.length > 255) {
        return mockError('VALIDATION_ERROR', 'The name field must be between 2 and 255 characters.', requestId, 422);
      }
      for (const rows of mockVendorStore.products.values()) {
        if (rows.some((row) => row.sku === sku)) {
          return mockError('CONFLICT', 'SKU already exists in this tenant.', requestId, 409);
        }
      }

      const product: VendorProduct = {
        id: uid('prd'),
        sku,
        name,
        description: body.description ?? null,
        status: 'DRAFT',
        currency: body.currency ?? 'INR',
        price_minor: body.price_minor ?? 0,
        attributes: body.attributes ?? null,
        vendor_id: vendorId,
      };
      const rows = mockVendorStore.products.get(vendorId) ?? [];
      rows.push(product);
      mockVendorStore.products.set(vendorId, rows);

      return { payload: product };
    },
    call: (token) =>
      laravelPost<VendorProduct>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/products`, body),
  });
}
