import { laravelPost } from '@/adapters/backend/laravel-client';
import { mockError, mockVendorStore, nowIso, resolveOwnedVendor, uid, vendorRoute } from '@/server/vendors';
import type { VendorInventoryRow } from '@/application/vendor';

// Inventory adjust (RPC verb): signed stock delta — row is created on
// first adjustment, stock never goes negative (P12b service rule).

type AdjustContext = { params: Promise<{ vendorId: string }> };

export async function POST(req: Request, { params }: AdjustContext) {
  const { vendorId } = await params;

  let body: { sku?: string; delta?: number } = {};
  try {
    body = (await req.json()) as { sku?: string; delta?: number };
  } catch {
    body = {};
  }

  return vendorRoute<VendorInventoryRow>(req, {
    message: 'Inventory adjustment is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const sku = body.sku ?? '';
      const delta = body.delta;
      if (sku.length < 1 || sku.length > 64) {
        return mockError('VALIDATION_ERROR', 'The sku field must be between 1 and 64 characters.', requestId, 422);
      }
      if (!Number.isInteger(delta) || (delta as number) < -1000000 || (delta as number) > 1000000) {
        return mockError('VALIDATION_ERROR', 'The delta field must be an integer.', requestId, 422);
      }

      const rows = mockVendorStore.inventory.get(vendorId) ?? [];
      const row = rows.find((entry) => entry.sku === sku);

      if (!row) {
        if ((delta as number) < 0) {
          return mockError('CONFLICT', 'Stock cannot go negative.', requestId, 409);
        }
        const timestamp = nowIso();
        const created: VendorInventoryRow = {
          id: uid('inv'),
          object: 'vendor_inventory',
          vendor_id: vendorId,
          sku,
          on_hand: delta as number,
          created_at: timestamp,
          updated_at: timestamp,
        };
        rows.push(created);
        mockVendorStore.inventory.set(vendorId, rows);
        return { payload: created };
      }

      const next = row.on_hand + (delta as number);
      if (next < 0) {
        return mockError('CONFLICT', 'Stock cannot go negative.', requestId, 409);
      }
      row.on_hand = next;
      row.updated_at = nowIso();

      return { payload: row };
    },
    call: (token) =>
      laravelPost<VendorInventoryRow>(
        token,
        `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/inventory:adjust`,
        body,
      ),
  });
}
