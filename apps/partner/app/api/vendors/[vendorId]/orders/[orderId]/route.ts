import { laravelGet } from '@/adapters/backend/laravel-client';
import { mockError, mockVendorStore, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorOrder } from '@/application/vendor';

// Vendor order detail: line snapshots inlined (list rows omit items).

type OrderContext = { params: Promise<{ vendorId: string; orderId: string }> };

export async function GET(req: Request, { params }: OrderContext) {
  const { vendorId, orderId } = await params;

  return vendorRoute<VendorOrder>(req, {
    message: 'Order details are unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const order = (mockVendorStore.orders.get(vendorId) ?? []).find((row) => row.id === orderId);
      if (!order) return mockError('NOT_FOUND', 'Order not found.', requestId, 404);

      return { payload: order };
    },
    call: (token) =>
      laravelGet<VendorOrder>(
        token,
        `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}`,
      ),
  });
}
