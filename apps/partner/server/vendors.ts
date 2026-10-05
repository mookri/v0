import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelErrorResponse, type LaravelResponse } from '@/adapters/backend/laravel-client';
import type {
  PageMeta,
  VendorInventoryRow,
  VendorOrder,
  VendorPayout,
  VendorProduct,
  VendorProfile,
} from '@/application/vendor';

// Vendor BFF shell (P12c): actor resolution, the shared route handler
// (mock default, laravel behind PARTNER_BACKEND=laravel), and a dev-only
// in-memory store mirroring the P12b partner verbs — pages and tests
// exercise full flows (create, adjust, advance…) with no backend.

const vendorActors = partnerActorResolver();

/** Resolved partner actor for the request, or null when unauthenticated. */
export async function vendorActor(req: Request) {
  return vendorActors.resolve(headersToRecord(req.headers));
}

/** Raw bearer token forwarded to the Laravel backend, or null. */
export function vendorToken(req: Request): string | null {
  return bearerOrSessionCookie(req.headers);
}

/** Query params as a record (backend calls + mock reads). */
export function queryOf(req: Request): Record<string, string> {
  const out: Record<string, string> = {};
  for (const [key, value] of new URL(req.url).searchParams.entries()) out[key] = value;
  return out;
}

export function mockLimit(query: Record<string, string>, fallback = 20): number {
  const raw = Number(query['limit']);
  return Number.isFinite(raw) && raw > 0 ? Math.min(100, Math.trunc(raw)) : fallback;
}

/** Mock cursor page (the dev store has no cursors — always terminal). */
export function mockPage<T>(rows: T[], limit: number): { data: T[]; meta: { page: PageMeta } } {
  const data = rows.slice(0, limit);
  return { data, meta: { page: { limit, has_more: rows.length > data.length, next_cursor: null } } };
}

/** Mock error envelope (matches the backend envelope codes 1:1). */
export function mockError(code: 'NOT_FOUND' | 'FORBIDDEN' | 'CONFLICT' | 'VALIDATION_ERROR', message: string, requestId: string, status: number): Response {
  return Response.json(err(code, message, requestId), { status });
}

// ── Shared route shell ───────────────────────────────────────────────────────────

export interface VendorRouteSpec<T> {
  /** Envelope message when the backend call fails. */
  message: string;
  /** Mock-mode handler: an error Response, or the page payload. */
  mock: (ctx: {
    userId: string;
    requestId: string;
  }) => Response | { payload: unknown; init?: ResponseInit } | Promise<Response | { payload: unknown; init?: ResponseInit }>;
  /** Backend call (laravel mode). */
  call: (token: string | null) => Promise<LaravelResponse<T>>;
  /** Success payload from the backend response (default: unwrapped data). */
  shape?: (res: LaravelResponse<T>) => unknown;
  /** Success status (default 200). */
  status?: number;
  /** private Cache-Control max-age seconds for successful responses. */
  cacheSeconds?: number;
}

export async function vendorRoute<T>(req: Request, spec: VendorRouteSpec<T>): Promise<Response> {
  const requestId = newRequestId();
  const actor = await vendorActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  const init: ResponseInit | undefined = spec.cacheSeconds
    ? { headers: { 'Cache-Control': `private, max-age=${spec.cacheSeconds}` } }
    : undefined;

  if (!laravelEnabled()) {
    const result = await spec.mock({ userId: actor.userId, requestId });
    if (result instanceof Response) return result;
    // Success status mirrors the laravel branch (creates answer 201).
    return Response.json(ok(result.payload, requestId), { status: spec.status ?? 200, ...init, ...result.init });
  }

  try {
    const res = await spec.call(vendorToken(req));
    return Response.json(ok(spec.shape ? spec.shape(res) : res.data, requestId), {
      status: spec.status ?? 200,
      ...init,
    });
  } catch (e) {
    return laravelErrorResponse(e, spec.message, requestId);
  }
}

// ── Dev-only in-memory mock store ─────────────────────────────────────────────────
// Process-local (resets on restart) like the onboarding memory repository.
// Synthetic ids/timestamps; owner_user_id keeps the raw actor id.

const mockVendors = new Map<string, VendorProfile>();
const mockProducts = new Map<string, VendorProduct[]>();
const mockInventory = new Map<string, VendorInventoryRow[]>();
const mockOrders = new Map<string, VendorOrder[]>();
const mockPayouts = new Map<string, VendorPayout[]>();

export const mockVendorStore = {
  vendors: mockVendors,
  products: mockProducts,
  inventory: mockInventory,
  orders: mockOrders,
  payouts: mockPayouts,
};

export function __vendorStoreForTests() {
  return {
    vendors: mockVendors,
    products: mockProducts,
    inventory: mockInventory,
    orders: mockOrders,
    payouts: mockPayouts,
    reset(): void {
      mockVendors.clear();
      mockProducts.clear();
      mockInventory.clear();
      mockOrders.clear();
      mockPayouts.clear();
    },
  };
}

export function uid(prefix: string): string {
  return `${prefix}_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`;
}

export function nowIso(): string {
  return new Date().toISOString();
}

/** Ownership resolution mirroring the P12b trait: 404 absent, 403 foreign. */
export function resolveOwnedVendor(
  vendorId: string,
  userId: string,
  requestId: string,
): { vendor: VendorProfile } | { error: Response } {
  const vendor = mockVendors.get(vendorId);
  if (!vendor) return { error: mockError('NOT_FOUND', 'Vendor not found.', requestId, 404) };
  if (String(vendor.owner_user_id) !== userId) {
    return { error: mockError('FORBIDDEN', 'You do not own this vendor.', requestId, 403) };
  }
  return { vendor };
}

export type MockApplicationGate =
  | { kind: 'missing' }
  | { kind: 'application'; id: string; approved: boolean };

/**
 * APPROVED partner-vendor application gate on the actor identity — the
 * mock reads the onboarding memory repository (latest partner-vendor
 * application wins, mirroring ManagesPartnerApplications::latestForIdentity).
 */
export async function mockVendorApplication(userId: string): Promise<MockApplicationGate> {
  const { onboardingApps } = await import('@/server/onboarding');
  const mine = (await onboardingApps.findByIdentity(userId)).filter((app) => app.track === 'partner-vendor');
  const latest = mine[mine.length - 1];
  if (!latest) return { kind: 'missing' };
  return { kind: 'application', id: latest.id, approved: latest.status === 'APPROVED' };
}
