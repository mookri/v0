import { laravelPost } from '@/adapters/backend/laravel-client';
import { mockError, mockVendorStore, nowIso, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorOrder } from '@/application/vendor';

// Order cancel (RPC verb): only NEW/PROCESSING leave the cancellable
// window — later states conflict (P12b state machine).

type CancelContext = { params: Promise<{ vendorId: string; orderId: string }> };

const CANCELLABLE = ['NEW', 'PROCESSING'];

export async function POST(req: Request, { params }: CancelContext) {
  const { vendorId, orderId } = await params;

  return vendorRoute<VendorOrder>(req, {
    message: 'Order cancellation is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const order = (mockVendorStore.orders.get(vendorId) ?? []).find((row) => row.id === orderId);
      if (!order) return mockError('NOT_FOUND', 'Order not found.', requestId, 404);

      if (!CANCELLABLE.includes(order.status)) {
        return mockError('CONFLICT', 'Only new or processing orders can be cancelled.', requestId, 409);
      }

      order.status = 'CANCELLED';
      order.updated_at = nowIso();

      return { payload: order };
    },
    call: (token) =>
      laravelPost<VendorOrder>(
        token,
        `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/orders/${encodeURIComponent(orderId)}:cancel`,
        {},
      ),
  });
}
