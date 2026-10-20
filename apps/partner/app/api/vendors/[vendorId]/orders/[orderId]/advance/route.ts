import { laravelPost } from '@/adapters/backend/laravel-client';
import { mockError, mockVendorStore, nowIso, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorOrder } from '@/application/vendor';

// Order lifecycle advance (RPC verb): NEW → PROCESSING → DISPATCHED →
// DELIVERED; terminal/cancelled orders conflict (P12b state machine).

type AdvanceContext = { params: Promise<{ vendorId: string; orderId: string }> };

const ORDER_NEXT: Record<string, string> = {
  NEW: 'PROCESSING',
  PROCESSING: 'DISPATCHED',
  DISPATCHED: 'DELIVERED',
};

export async function POST(req: Request, { params }: AdvanceContext) {
  const { vendorId, orderId } = await params;

  return vendorRoute<VendorOrder>(req, {
    message: 'Order advance is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const order = (mockVendorStore.orders.get(vendorId) ?? []).find((row) => row.id === orderId);
      if (!order) return mockError('NOT_FOUND', 'Order not found.', requestId, 404);

      const next = ORDER_NEXT[order.status];
      if (!next) {
        return order.status === 'CANCELLED'
          ? mockError('CONFLICT', 'Cancelled orders cannot advance.', requestId, 409)
          : mockError('CONFLICT', 'Order is already delivered.', requestId, 409);
      }

      order.status = next;
      order.updated_at = nowIso();

      return { payload: order };
    },
    call: (token) =>
      laravelPost<VendorOrder>(
        token,
        `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}:advance`,
        {},
      ),
  });
}
