import { laravelGet } from '@/adapters/backend/laravel-client';
import { mockLimit, mockPage, mockVendorStore, queryOf, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorListPayload, VendorPayout } from '@/application/vendor';

// Vendor payouts: cursor list (read-only stub — rows are seeded backend-
// side; the dev mock stays empty like the temple directory).

type ListContext = { params: Promise<{ vendorId: string }> };

export async function GET(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  return vendorRoute<VendorPayout[]>(req, {
    message: 'Vendor payouts are unavailable.',
    cacheSeconds: 60,
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;
      const query = queryOf(req);
      const status = query['status'];
      const rows = (mockVendorStore.payouts.get(vendorId) ?? [])
        .filter((payout) => !status || payout.status === status)
        .reverse();
      return { payload: mockPage(rows, mockLimit(query)) satisfies VendorListPayload<VendorPayout> };
    },
    call: (token) =>
      laravelGet<VendorPayout[]>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/payouts`, queryOf(req)),
    shape: (res) => ({ data: res.data, meta: res.meta }) satisfies VendorListPayload<VendorPayout>,
  });
}
