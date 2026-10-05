import { test, expect, beforeEach } from 'vitest';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { POST as createVendor, GET as listVendors } from '@apps/partner/app/api/vendors/route';
import { GET as showVendor, PATCH as patchVendor } from '@apps/partner/app/api/vendors/[vendorId]/route';
import { GET as listProducts, POST as createProduct } from '@apps/partner/app/api/vendors/[vendorId]/products/route';
import { PATCH as patchProduct } from '@apps/partner/app/api/vendors/[vendorId]/products/[itemId]/route';
import { GET as listInventory } from '@apps/partner/app/api/vendors/[vendorId]/inventory/route';
import { POST as adjustInventory } from '@apps/partner/app/api/vendors/[vendorId]/inventory/adjust/route';
import { GET as listOrders, POST as placeOrder } from '@apps/partner/app/api/vendors/[vendorId]/orders/route';
import { GET as showOrder } from '@apps/partner/app/api/vendors/[vendorId]/orders/[orderId]/route';
import { POST as advanceOrder } from '@apps/partner/app/api/vendors/[vendorId]/orders/[orderId]/advance/route';
import { POST as cancelOrder } from '@apps/partner/app/api/vendors/[vendorId]/orders/[orderId]/cancel/route';
import { GET as listPayouts } from '@apps/partner/app/api/vendors/[vendorId]/payouts/route';
import { __vendorStoreForTests } from '@apps/partner/server/vendors';
import type { ApplicationStatus } from '@navdhya/partner/src/domain/partner-application';

// Vendor BFF behavior (P12c): mock-default routes behind vendorRoute —
// envelope codes and HTTP statuses mirror the P12b partner surface
// (401 unauth, 409 application gate/conflicts, 404/403 ownership, 201
// creates). The laravel branch shares the same shape mapping.

const bearer = (userId: string) =>
  `Bearer ${mintActorToken(
    INSECURE_DEV_SECRET,
    { userId, sessionId: `staging_${userId}`, roles: ['partner'] },
    new Date().toISOString(),
    3600,
  )}`;

let seq = 0;
const freshUser = () => `ven_bff_${Date.now().toString(36)}_${seq++}`;

function call(method: string, url: string, userId?: string, body?: unknown): Request {
  return new Request(`https://partner.navdhya.com${url}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...(userId ? { authorization: bearer(userId) } : {}),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
}

const params = (obj: Record<string, string>) => ({ params: Promise.resolve(obj) });

const json = async (res: Response) => (await res.json()) as Record<string, any>;

async function seedApplication(userId: string, status: ApplicationStatus): Promise<string> {
  const { onboardingApps } = await import('@apps/partner/server/onboarding');
  const id = `app_${userId}`;
  await onboardingApps.save({
    id,
    identityId: userId,
    type: 'VENDOR',
    track: 'partner-vendor',
    trackVersion: 'partner-vendor-v1',
    status,
  });
  return id;
}

/** Approved application + create → the vendor id (per-test user). */
async function vendorOf(userId: string): Promise<string> {
  await seedApplication(userId, 'APPROVED');
  const res = await createVendor(call('POST', '/api/vendors', userId, { name: 'Gita Bhandar' }));
  expect(res.status).toBe(201);
  return (await json(res)).data.id as string;
}

beforeEach(() => {
  __vendorStoreForTests().reset();
});

test('create without an actor is unauthenticated', async () => {
  const res = await createVendor(call('POST', '/api/vendors'));
  expect(res.status).toBe(401);
  expect((await json(res)).error.code).toBe('UNAUTHENTICATED');
});

test('create without a partner-vendor application conflicts', async () => {
  const res = await createVendor(call('POST', '/api/vendors', freshUser(), { name: 'Gita Bhandar' }));
  expect(res.status).toBe(409);
  const body = await json(res);
  expect(body.error.code).toBe('CONFLICT');
  expect(body.error.message).toBe('Apply as a partner vendor first.');
});

test('create with an unapproved application conflicts', async () => {
  const userId = freshUser();
  await seedApplication(userId, 'DRAFT');
  const res = await createVendor(call('POST', '/api/vendors', userId, { name: 'Gita Bhandar' }));
  expect(res.status).toBe(409);
  expect((await json(res)).error.message).toBe('Partner vendor application is not approved yet.');
});

test('create → list → detail flow with ownership scoping', async () => {
  const ownerId = freshUser();
  const otherId = freshUser();
  const applicationId = await seedApplication(ownerId, 'APPROVED');

  const created = await createVendor(
    call('POST', '/api/vendors', ownerId, { name: 'Gita Bhandar', city: 'Varanasi', state: 'UP' }),
  );
  expect(created.status).toBe(201);
  const vendor = (await json(created)).data;
  expect(vendor).toMatchObject({
    object: 'vendor',
    application_id: applicationId,
    name: 'Gita Bhandar',
    city: 'Varanasi',
    status: 'ACTIVE',
    verification_status: 'PENDING',
  });

  // List payloads are { data, meta } inside the ok envelope.
  const ownerList = await json(await listVendors(call('GET', '/api/vendors', ownerId)));
  expect(ownerList.data.data.map((row: { id: string }) => row.id)).toContain(vendor.id);

  const otherList = await json(await listVendors(call('GET', '/api/vendors', otherId)));
  expect(otherList.data.data).toHaveLength(0);

  const detail = await showVendor(call('GET', `/api/vendors/${vendor.id}`, ownerId), params({ vendorId: vendor.id }));
  expect(detail.status).toBe(200);
  expect((await json(detail)).data.name).toBe('Gita Bhandar');

  const foreign = await showVendor(call('GET', `/api/vendors/${vendor.id}`, otherId), params({ vendorId: vendor.id }));
  expect(foreign.status).toBe(403);
  expect((await json(foreign)).error.code).toBe('FORBIDDEN');

  const missing = await showVendor(
    call('GET', '/api/vendors/ven_does_not_exist', ownerId),
    params({ vendorId: 'ven_does_not_exist' }),
  );
  expect(missing.status).toBe(404);
  expect((await json(missing)).error.code).toBe('NOT_FOUND');
});

test('a vendor application can back only one vendor', async () => {
  const userId = freshUser();
  await seedApplication(userId, 'APPROVED');
  const first = await createVendor(call('POST', '/api/vendors', userId, { name: 'Gita Bhandar' }));
  expect(first.status).toBe(201);
  const second = await createVendor(call('POST', '/api/vendors', userId, { name: 'Gita Bhandar 2' }));
  expect(second.status).toBe(409);
  expect((await json(second)).error.message).toBe('Application already linked to a vendor.');
});

test('profile patch updates fields and validates the name', async () => {
  const userId = freshUser();
  const vendorId = await vendorOf(userId);

  const patched = await patchVendor(
    call('PATCH', `/api/vendors/${vendorId}`, userId, { name: 'Gita Bhandar Premium' }),
    params({ vendorId }),
  );
  expect(patched.status).toBe(200);
  expect((await json(patched)).data.name).toBe('Gita Bhandar Premium');

  const invalid = await patchVendor(call('PATCH', `/api/vendors/${vendorId}`, userId, { name: 'x' }), params({ vendorId }));
  expect(invalid.status).toBe(422);
  expect((await json(invalid)).error.code).toBe('VALIDATION_ERROR');
});

test('product create/list/update with conflicts', async () => {
  const userId = freshUser();
  const vendorId = await vendorOf(userId);

  const created = await createProduct(
    call('POST', `/api/vendors/${vendorId}/products`, userId, { sku: 'SKU-1', name: 'Brass Diya', price_minor: 9900 }),
    params({ vendorId }),
  );
  expect(created.status).toBe(201);
  const product = (await json(created)).data;
  expect(product).toMatchObject({ sku: 'SKU-1', status: 'DRAFT', vendor_id: vendorId });

  const duplicate = await createProduct(
    call('POST', `/api/vendors/${vendorId}/products`, userId, { sku: 'SKU-1', name: 'Other' }),
    params({ vendorId }),
  );
  expect(duplicate.status).toBe(409);
  expect((await json(duplicate)).error.message).toBe('SKU already exists in this tenant.');

  const invalid = await createProduct(
    call('POST', `/api/vendors/${vendorId}/products`, userId, { sku: 'SKU-2' }),
    params({ vendorId }),
  );
  expect(invalid.status).toBe(422);

  const list = await json(await listProducts(call('GET', `/api/vendors/${vendorId}/products`, userId), params({ vendorId })));
  expect(list.data.data).toHaveLength(1);
  expect(list.data.meta).toBeUndefined(); // limit-based list carries no cursor page

  const patched = await patchProduct(
    call('PATCH', `/api/vendors/${vendorId}/products/${product.id}`, userId, { price_minor: 12900 }),
    params({ vendorId, itemId: product.id }),
  );
  expect(patched.status).toBe(200);
  expect((await json(patched)).data).toMatchObject({ price_minor: 12900, sku: 'SKU-1' });

  const missing = await patchProduct(
    call('PATCH', `/api/vendors/${vendorId}/products/prd_x`, userId, { price_minor: 1 }),
    params({ vendorId, itemId: 'prd_x' }),
  );
  expect(missing.status).toBe(404);
});

test('inventory adjust tracks stock and never goes negative', async () => {
  const userId = freshUser();
  const vendorId = await vendorOf(userId);

  const up = await adjustInventory(
    call('POST', `/api/vendors/${vendorId}/inventory/adjust`, userId, { sku: 'SKU-1', delta: 5 }),
    params({ vendorId }),
  );
  expect(up.status).toBe(200);
  expect((await json(up)).data).toMatchObject({ sku: 'SKU-1', on_hand: 5, object: 'vendor_inventory' });

  const down = await adjustInventory(
    call('POST', `/api/vendors/${vendorId}/inventory/adjust`, userId, { sku: 'SKU-1', delta: -10 }),
    params({ vendorId }),
  );
  expect(down.status).toBe(409);
  expect((await json(down)).error.message).toBe('Stock cannot go negative.');

  const unknownSku = await adjustInventory(
    call('POST', `/api/vendors/${vendorId}/inventory/adjust`, userId, { sku: 'SKU-NEW', delta: -1 }),
    params({ vendorId }),
  );
  expect(unknownSku.status).toBe(409);

  const list = await json(await listInventory(call('GET', `/api/vendors/${vendorId}/inventory`, userId), params({ vendorId })));
  expect(list.data.data).toHaveLength(1);
  expect(list.data.meta.page).toMatchObject({ has_more: false });
});

test('order placement snapshots lines and the list omits them', async () => {
  const userId = freshUser();
  const vendorId = await vendorOf(userId);

  const placed = await placeOrder(
    call('POST', `/api/vendors/${vendorId}/orders`, userId, {
      reference: 'PO-1001',
      customer_name: 'Gita',
      items: [{ sku: 'SKU-1', name: 'Brass Diya', quantity: 2, unit_minor: 50000 }],
    }),
    params({ vendorId }),
  );
  expect(placed.status).toBe(201);
  const order = (await json(placed)).data;
  expect(order).toMatchObject({ object: 'vendor_order', status: 'NEW', total_minor: 100000 });
  expect(order.items).toHaveLength(1);
  expect(order.items[0]).toMatchObject({ line_minor: 100000 });

  const duplicate = await placeOrder(
    call('POST', `/api/vendors/${vendorId}/orders`, userId, {
      reference: 'PO-1001',
      items: [{ sku: 'SKU-1', name: 'Brass Diya', quantity: 1, unit_minor: 50000 }],
    }),
    params({ vendorId }),
  );
  expect(duplicate.status).toBe(409);
  expect((await json(duplicate)).error.message).toBe('Order reference already exists for this vendor.');

  const invalid = await placeOrder(
    call('POST', `/api/vendors/${vendorId}/orders`, userId, { reference: 'PO-1002', items: [] }),
    params({ vendorId }),
  );
  expect(invalid.status).toBe(422);

  const list = await json(await listOrders(call('GET', `/api/vendors/${vendorId}/orders`, userId), params({ vendorId })));
  expect(list.data.data).toHaveLength(1);
  expect('items' in list.data.data[0]).toBe(false);

  const detail = await showOrder(
    call('GET', `/api/vendors/${vendorId}/orders/${order.id}`, userId),
    params({ vendorId, orderId: order.id }),
  );
  expect(detail.status).toBe(200);
  expect((await json(detail)).data.items).toHaveLength(1);
});

test('order lifecycle advances to delivered and blocks terminal moves', async () => {
  const userId = freshUser();
  const vendorId = await vendorOf(userId);

  const placed = await placeOrder(
    call('POST', `/api/vendors/${vendorId}/orders`, userId, {
      reference: 'PO-2001',
      items: [{ sku: 'SKU-1', name: 'Brass Diya', quantity: 1, unit_minor: 10000 }],
    }),
    params({ vendorId }),
  );
  const orderId = (await json(placed)).data.id as string;
  const ctx = params({ vendorId, orderId });

  const first = await advanceOrder(call('POST', `/api/vendors/${vendorId}/orders/${orderId}/advance`, userId), ctx);
  expect(first.status).toBe(200);
  expect((await json(first)).data.status).toBe('PROCESSING');

  const second = await advanceOrder(call('POST', `/api/vendors/${vendorId}/orders/${orderId}/advance`, userId), ctx);
  expect((await json(second)).data.status).toBe('DISPATCHED');

  const third = await advanceOrder(call('POST', `/api/vendors/${vendorId}/orders/${orderId}/advance`, userId), ctx);
  expect((await json(third)).data.status).toBe('DELIVERED');

  const delivered = await advanceOrder(call('POST', `/api/vendors/${vendorId}/orders/${orderId}/advance`, userId), ctx);
  expect(delivered.status).toBe(409);
  expect((await json(delivered)).error.message).toBe('Order is already delivered.');

  const secondOrder = await placeOrder(
    call('POST', `/api/vendors/${vendorId}/orders`, userId, {
      reference: 'PO-2002',
      items: [{ sku: 'SKU-1', name: 'Brass Diya', quantity: 1, unit_minor: 10000 }],
    }),
    params({ vendorId }),
  );
  const cancelId = (await json(secondOrder)).data.id as string;

  const cancelled = await cancelOrder(
    call('POST', `/api/vendors/${vendorId}/orders/${cancelId}/cancel`, userId),
    params({ vendorId, orderId: cancelId }),
  );
  expect(cancelled.status).toBe(200);
  expect((await json(cancelled)).data.status).toBe('CANCELLED');

  const advanceCancelled = await advanceOrder(
    call('POST', `/api/vendors/${vendorId}/orders/${cancelId}/advance`, userId),
    params({ vendorId, orderId: cancelId }),
  );
  expect(advanceCancelled.status).toBe(409);
  expect((await json(advanceCancelled)).error.message).toBe('Cancelled orders cannot advance.');

  const cancelAgain = await cancelOrder(
    call('POST', `/api/vendors/${vendorId}/orders/${cancelId}/cancel`, userId),
    params({ vendorId, orderId: cancelId }),
  );
  expect(cancelAgain.status).toBe(409);
  expect((await json(cancelAgain)).error.message).toBe('Only new or processing orders can be cancelled.');
});

test('payouts list an empty stub page for the owner', async () => {
  const ownerId = freshUser();
  const otherId = freshUser();
  const vendorId = await vendorOf(ownerId);

  const page = await json(await listPayouts(call('GET', `/api/vendors/${vendorId}/payouts`, ownerId), params({ vendorId })));
  expect(page.data.data).toHaveLength(0);
  expect(page.data.meta.page).toMatchObject({ has_more: false, next_cursor: null });

  const foreign = await listPayouts(call('GET', `/api/vendors/${vendorId}/payouts`, otherId), params({ vendorId }));
  expect(foreign.status).toBe(403);
});
