import { err } from '@/application/api-envelope';
import { laravelGet, laravelPost } from '@/adapters/backend/laravel-client';
import {
  mockVendorStore,
  mockVendorApplication,
  mockPage,
  mockLimit,
  queryOf,
  uid,
  nowIso,
  vendorRoute,
} from '@/server/vendors';
import type { VendorCreateInput, VendorProfile, VendorListPayload } from '@/application/vendor';

// Vendor directory (partner surface): cursor list of the actor's vendors
// + create behind the APPROVED partner-vendor application gate (P12b).

export async function GET(req: Request) {
  return vendorRoute<VendorProfile[]>(req, {
    message: 'Vendor directory is unavailable.',
    cacheSeconds: 60,
    mock: ({ userId }) => {
      const query = queryOf(req);
      const limit = mockLimit(query);
      const rows = [...mockVendorStore.vendors.values()]
        .filter((vendor) => String(vendor.owner_user_id) === userId)
        .reverse(); // newest first, like the backend cursor order
      return { payload: mockPage(rows, limit) satisfies VendorListPayload<VendorProfile> };
    },
    call: (token) => laravelGet<VendorProfile[]>(token, '/vendor/partner/vendors', queryOf(req)),
    shape: (res) => ({ data: res.data, meta: res.meta }) satisfies VendorListPayload<VendorProfile>,
  });
}

export async function POST(req: Request) {
  let body: Partial<VendorCreateInput> = {};
  try {
    body = (await req.json()) as Partial<VendorCreateInput>;
  } catch {
    body = {};
  }

  return vendorRoute<VendorProfile>(req, {
    message: 'Vendor creation is unavailable.',
    status: 201,
    mock: async ({ userId, requestId }) => {
      const name = (body.name ?? '').trim();
      if (name.length < 2 || name.length > 255) {
        return err422('The name field must be between 2 and 255 characters.', requestId);
      }

      // Application gate (P12b edge check): approved track or 409.
      const gate = await mockVendorApplication(userId);
      if (gate.kind === 'missing') {
        return err409('Apply as a partner vendor first.', requestId);
      }
      if (!gate.approved) {
        return err409('Partner vendor application is not approved yet.', requestId);
      }
      for (const vendor of mockVendorStore.vendors.values()) {
        if (vendor.application_id === gate.id) {
          return err409('Application already linked to a vendor.', requestId);
        }
      }

      const timestamp = nowIso();
      const vendor: VendorProfile = {
        id: uid('ven'),
        object: 'vendor',
        application_id: gate.id,
        owner_user_id: userId,
        name,
        description: body.description ?? null,
        city: body.city ?? null,
        state: body.state ?? null,
        status: 'ACTIVE',
        verification_status: 'PENDING',
        created_at: timestamp,
        updated_at: timestamp,
      };
      mockVendorStore.vendors.set(vendor.id, vendor);

      return { payload: vendor };
    },
    call: (token) => laravelPost<VendorProfile>(token, '/vendor/partner/vendors', body),
  });
}

function err409(message: string, requestId: string): Response {
  return Response.json(err('CONFLICT', message, requestId), { status: 409 });
}

function err422(message: string, requestId: string): Response {
  return Response.json(err('VALIDATION_ERROR', message, requestId), { status: 422 });
}
