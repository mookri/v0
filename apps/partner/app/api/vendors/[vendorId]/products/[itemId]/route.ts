import { laravelCall } from '@/adapters/backend/laravel-client';
import { mockError, mockVendorStore, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorProduct } from '@/application/vendor';

// Vendor product update: partial content/price edit — SKU is immutable
// and archived items are immutable (P12a Catalog rules).

type ItemContext = { params: Promise<{ vendorId: string; itemId: string }> };

export async function PATCH(req: Request, { params }: ItemContext) {
  const { vendorId, itemId } = await params;

  let body: Partial<VendorProduct> = {};
  try {
    body = (await req.json()) as Partial<VendorProduct>;
  } catch {
    body = {};
  }

  return vendorRoute<VendorProduct>(req, {
    message: 'Vendor product update is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const rows = mockVendorStore.products.get(vendorId) ?? [];
      const item = rows.find((row) => row.id === itemId);
      if (!item) return mockError('NOT_FOUND', 'Product not found.', requestId, 404);
      if (item.status === 'ARCHIVED') {
        return mockError('CONFLICT', 'Archived items are immutable.', requestId, 409);
      }

      if (typeof body.name === 'string' && (body.name.trim().length < 2 || body.name.length > 255)) {
        return mockError('VALIDATION_ERROR', 'The name field must be between 2 and 255 characters.', requestId, 422);
      }
      if ('price_minor' in body && body.price_minor != null) {
        if (!Number.isInteger(body.price_minor) || body.price_minor < 0) {
          return mockError('VALIDATION_ERROR', 'The price_minor field must be a non-negative integer.', requestId, 422);
        }
      }

      for (const field of ['name', 'description', 'currency', 'price_minor', 'attributes'] as const) {
        if (field in body) {
          item[field] = body[field] as never;
        }
      }

      return { payload: item };
    },
    call: (token) =>
      laravelCall<VendorProduct>(
        token,
        'PATCH',
        `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/products/${encodeURIComponent(itemId)}`,
        {},
        body,
      ),
  });
}
