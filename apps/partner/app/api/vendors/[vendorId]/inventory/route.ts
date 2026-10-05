import { laravelGet } from '@/adapters/backend/laravel-client';
import { mockLimit, mockPage, mockVendorStore, queryOf, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorInventoryRow, VendorListPayload } from '@/application/vendor';

// Vendor inventory: cursor list of stock rows (P12b partner verb).

type ListContext = { params: Promise<{ vendorId: string }> };

export async function GET(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  return vendorRoute<VendorInventoryRow[]>(req, {
    message: 'Vendor inventory is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;
      const query = queryOf(req);
      const rows = [...(mockVendorStore.inventory.get(vendorId) ?? [])].reverse();
      return { payload: mockPage(rows, mockLimit(query)) satisfies VendorListPayload<VendorInventoryRow> };
    },
    call: (token) =>
      laravelGet<VendorInventoryRow[]>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/inventory`, queryOf(req)),
    shape: (res) => ({ data: res.data, meta: res.meta }) satisfies VendorListPayload<VendorInventoryRow>,
  });
}
