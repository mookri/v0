import { laravelCall, laravelGet } from '@/adapters/backend/laravel-client';
import { mockError, nowIso, resolveOwnedVendor, vendorRoute } from '@/server/vendors';
import type { VendorProfile } from '@/application/vendor';

// Vendor detail + profile update (ownership-gated; P12b partner verbs).

type DetailContext = { params: Promise<{ vendorId: string }> };

export async function GET(req: Request, { params }: DetailContext) {
  const { vendorId } = await params;

  return vendorRoute<VendorProfile>(req, {
    message: 'Vendor details are unavailable.',
    cacheSeconds: 60,
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;
      return { payload: resolved.vendor };
    },
    call: (token) => laravelGet<VendorProfile>(token, `/vendor/partner/vendors/${encodeURIComponent(vendorId)}`),
  });
}

export async function PATCH(req: Request, { params }: DetailContext) {
  const { vendorId } = await params;

  let body: Record<string, unknown> = {};
  try {
    body = (await req.json()) as Record<string, unknown>;
  } catch {
    body = {};
  }

  return vendorRoute<VendorProfile>(req, {
    message: 'Vendor update is unavailable.',
    mock: ({ userId, requestId }) => {
      const resolved = resolveOwnedVendor(vendorId, userId, requestId);
      if ('error' in resolved) return resolved.error;

      if (typeof body.name === 'string' && (body.name.trim().length < 2 || body.name.length > 255)) {
        return mockError('VALIDATION_ERROR', 'The name field must be between 2 and 255 characters.', requestId, 422);
      }

      const vendor = resolved.vendor;
      for (const field of ['name', 'description', 'city', 'state'] as const) {
        if (field in body) {
          vendor[field] = body[field] as never;
        }
      }
      vendor.updated_at = nowIso();

      return { payload: vendor };
    },
    call: (token) =>
      laravelCall<VendorProfile>(token, 'PATCH', `/vendor/partner/vendors/${encodeURIComponent(vendorId)}`, {}, body),
  });
}
