import { laravelGet, laravelPost } from '@/adapters/backend/laravel-client';
import {
  mockError,
  mockLimit,
  mockPage,
  mockVendorStore,
  nowIso,
  queryOf,
  resolveOwnedVendor,
  uid,
  vendorRoute,
} from '@/server/vendors';
import type { VendorListPayload, VendorOrder, VendorOrderInput } from '@/application/vendor';

// Vendor orders: cursor list (rows without line snapshots) + placement
// (NEW, lines snapshotted, total derived — P12b service rules).

type ListContext = { params: Promise<{ vendorId: string }> };

type OrderListRow = Omit<VendorOrder, 'items'>;

export async function GET(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  return vendorRoute<OrderListRow[]>(req, {
    message: 'Vendor orders are unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;
      const query = queryOf(req);
      const status = query['status'];
      const rows = (mockVendorStore.orders.get(vendorId) ?? [])
        .filter((order) => !status || order.status === status)
        .map(({ items: _items, ...row }) => row)
        .reverse();
      return { payload: mockPage(rows, mockLimit(query)) satisfies VendorListPayload<OrderListRow> };
    },
    call: (token) =>
      laravelGet<OrderListRow[]>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/orders`, queryOf(req)),
    shape: (res) => ({ data: res.data, meta: res.meta }) satisfies VendorListPayload<OrderListRow>,
  });
}

export async function POST(req: Request, { params }: ListContext) {
  const { vendorId } = await params;

  let body: Partial<VendorOrderInput> = {};
  try {
    body = (await req.json()) as Partial<VendorOrderInput>;
  } catch {
    body = {};
  }

  return vendorRoute<VendorOrder>(req, {
    message: 'Order placement is unavailable.',
    status: 201,
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      const reference = body.reference ?? '';
      if (reference.length < 1 || reference.length > 64) {
        return mockError('VALIDATION_ERROR', 'The reference field must be between 1 and 64 characters.', requestId, 422);
      }
      const lines = body.items ?? [];
      if (lines.length < 1 || lines.length > 100) {
        return mockError('VALIDATION_ERROR', 'The items field must have between 1 and 100 entries.', requestId, 422);
      }
      for (const line of lines) {
        const valid =
          typeof line.sku === 'string' &&
          line.sku.length >= 1 &&
          line.sku.length <= 64 &&
          typeof line.name === 'string' &&
          line.name.length >= 1 &&
          line.name.length <= 255 &&
          Number.isInteger(line.quantity) &&
          (line.quantity as number) >= 1 &&
          Number.isInteger(line.unit_minor) &&
          (line.unit_minor as number) >= 0;
        if (!valid) {
          return mockError('VALIDATION_ERROR', 'Each item needs a sku, name, quantity and unit_minor.', requestId, 422);
        }
      }
      if ((mockVendorStore.orders.get(vendorId) ?? []).some((order) => order.reference === reference)) {
        return mockError('CONFLICT', 'Order reference already exists for this vendor.', requestId, 409);
      }

      const timestamp = nowIso();
      let total = 0;
      const items = lines.map((line) => {
        const lineMinor = (line.quantity as number) * (line.unit_minor as number);
        total += lineMinor;
        return {
          id: uid('oit'),
          sku: line.sku,
          name: line.name,
          quantity: line.quantity as number,
          unit_minor: line.unit_minor as number,
          line_minor: lineMinor,
        };
      });

      const order: VendorOrder = {
        id: uid('vor'),
        object: 'vendor_order',
        vendor_id: vendorId,
        reference,
        customer_name: body.customer_name ?? null,
        status: 'NEW',
        currency: body.currency ?? 'INR',
        total_minor: total,
        created_at: timestamp,
        updated_at: timestamp,
        items,
      };
      const rows = mockVendorStore.orders.get(vendorId) ?? [];
      rows.push(order);
      mockVendorStore.orders.set(vendorId, rows);

      return { payload: order };
    },
    call: (token) => laravelPost<VendorOrder>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}/orders`, body),
  });
}
