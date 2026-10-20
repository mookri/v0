import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';
import { statusFor, type ApiErrorCode } from '@navdhya/api-contracts/src/common/envelope';
import { apiRoutes } from '@apps/partner/application/api-routes';
import { mapBackendCode } from '@apps/partner/adapters/backend/laravel-client';

// Vendor BFF ↔ P12b backend contract (P12c): HTTP statuses are the
// cross-surface contract. Every envelope code the vendor backend can
// emit must resolve through the shared Laravel code map to a BFF code
// whose platform status matches — a missing mapping would surface as
// INTERNAL_ERROR/500 instead of the parity status.

const vendorRoutes = apiRoutes.filter((route) => route.path.startsWith('/api/vendors'));

test('vendor registry entries are authenticated behind the partner scope', () => {
  expect(vendorRoutes.length).toBeGreaterThan(0);
  for (const route of vendorRoutes) {
    expect(route.access).toBe('authenticated');
    expect(route.capability).toBe('vendor:partner');
  }
});

// Backend envelope code → expected BFF code + platform status on this
// surface (P12b ErrorEnvelope registry + ambient platform codes). An
// unmapped code falls back to INTERNAL_ERROR/500 — asserted against the
// exact expected code, so both wrong fallbacks and wrong remaps fail.
const VENDOR_BACKEND_PARITY: Record<string, { bff: string; status: number }> = {
  unauthenticated: { bff: 'UNAUTHENTICATED', status: 401 },
  membership_inactive: { bff: 'UNAUTHENTICATED', status: 401 },
  tenant_suspended: { bff: 'FORBIDDEN', status: 403 },
  permission_missing: { bff: 'FORBIDDEN', status: 403 },
  not_found: { bff: 'NOT_FOUND', status: 404 },
  state_forbidden: { bff: 'CONFLICT', status: 409 },
  conflict: { bff: 'CONFLICT', status: 409 },
  validation_failed: { bff: 'VALIDATION_ERROR', status: 422 },
  rate_limited: { bff: 'RATE_LIMITED', status: 429 },
  service_unavailable: { bff: 'DEPENDENCY_UNAVAILABLE', status: 503 },
  internal_error: { bff: 'INTERNAL_ERROR', status: 500 },
};

test('every vendor backend code maps to a BFF code with the parity status', () => {
  for (const [backendCode, expected] of Object.entries(VENDOR_BACKEND_PARITY)) {
    const bffCode = mapBackendCode(backendCode);
    expect(bffCode, `code mapping mismatch for ${backendCode}`).toBe(expected.bff);
    expect(statusFor(bffCode as ApiErrorCode), `status mismatch for ${backendCode}`).toBe(expected.status);
  }
});

interface VendorMapping {
  bff: string;
  backend: string[];
}

// Mirror of the parity flow table (BFF endpoint → P12b backend source).
const MAPPINGS: VendorMapping[] = [
  { bff: 'GET /api/vendors', backend: ['GET /api/v1/vendor/partner/vendors'] },
  { bff: 'POST /api/vendors', backend: ['POST /api/v1/vendor/partner/vendors'] },
  { bff: 'GET /api/vendors/:vendorId', backend: ['GET /api/v1/vendor/partner/vendors/{id}'] },
  { bff: 'PATCH /api/vendors/:vendorId', backend: ['PATCH /api/v1/vendor/partner/vendors/{id}'] },
  { bff: 'GET /api/vendors/:vendorId/products', backend: ['GET /api/v1/vendor/partner/vendors/{id}/products'] },
  { bff: 'POST /api/vendors/:vendorId/products', backend: ['POST /api/v1/vendor/partner/vendors/{id}/products'] },
  {
    bff: 'PATCH /api/vendors/:vendorId/products/:itemId',
    backend: ['PATCH /api/v1/vendor/partner/vendors/{id}/products/{itemId}'],
  },
  { bff: 'GET /api/vendors/:vendorId/inventory', backend: ['GET /api/v1/vendor/partner/vendors/{id}/inventory'] },
  {
    bff: 'POST /api/vendors/:vendorId/inventory/adjust',
    backend: ['POST /api/v1/vendor/partner/vendors/{id}/inventory:adjust'],
  },
  { bff: 'GET /api/vendors/:vendorId/orders', backend: ['GET /api/v1/vendor/partner/vendors/{id}/orders'] },
  { bff: 'POST /api/vendors/:vendorId/orders', backend: ['POST /api/v1/vendor/partner/vendors/{id}/orders'] },
  {
    bff: 'GET /api/vendors/:vendorId/orders/:orderId',
    backend: ['GET /api/v1/vendor/partner/vendors/{id}/orders/{orderId}'],
  },
  {
    bff: 'POST /api/vendors/:vendorId/orders/:orderId/advance',
    backend: ['POST /api/v1/vendor/partner/vendors/{id}/orders/{orderId}:advance'],
  },
  {
    bff: 'POST /api/vendors/:vendorId/orders/:orderId/cancel',
    backend: ['POST /api/v1/vendor/partner/vendors/{id}/orders/{orderId}:cancel'],
  },
  { bff: 'GET /api/vendors/:vendorId/payouts', backend: ['GET /api/v1/vendor/partner/vendors/{id}/payouts'] },
];

test('vendor mappings use the vendor module prefix and cover the registry exactly', () => {
  const registry = new Set(apiRoutes.map((route) => `${route.method} ${route.path}`));
  const mapped = new Set(MAPPINGS.map((mapping) => mapping.bff));

  for (const mapping of MAPPINGS) {
    expect(mapping.backend.length).toBeGreaterThan(0);
    for (const backendPath of mapping.backend) {
      expect(backendPath).toMatch(/^(GET|POST|PATCH|PUT|DELETE) \/api\/v1\/vendor\//);
    }
    expect(registry.has(mapping.bff), `mapping not in registry: ${mapping.bff}`).toBe(true);
  }

  // Every vendor registry entry is documented, and nothing stale remains.
  expect(mapped).toEqual(new Set(vendorRoutes.map((route) => `${route.method} ${route.path}`)));
});

// Registry ↔ route-file drift: vendor files must export the declared
// verbs, and every verb a file exports must be registered (both ways).
function collectVendorRouteFiles(dir: string, segments: string[] = []): { urlPath: string; methods: string[] }[] {
  const out: { urlPath: string; methods: string[] }[] = [];
  if (!fs.existsSync(dir)) return out;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      const segment = entry.name.startsWith('[') && entry.name.endsWith(']')
        ? `:${entry.name.slice(1, -1)}`
        : entry.name;
      out.push(...collectVendorRouteFiles(path.join(dir, entry.name), [...segments, segment]));
    } else if (entry.name === 'route.ts') {
      const source = fs.readFileSync(path.join(dir, entry.name), 'utf8');
      const methods = [...source.matchAll(/export (?:async )?function (GET|POST|PUT|PATCH|DELETE)\b/g)].map(
        (match) => match[1] as string,
      );
      out.push({ urlPath: `/api/${segments.join('/')}`, methods });
    }
  }
  return out;
}

test('vendor route files and registry entries match in both directions', () => {
  const files = collectVendorRouteFiles(path.join(process.cwd(), 'apps', 'partner', 'app', 'api', 'vendors'), [
    'vendors',
  ]);
  expect(files.length).toBeGreaterThan(0);

  const registry = new Set(apiRoutes.map((route) => `${route.method} ${route.path}`));

  for (const route of vendorRoutes) {
    const file = files.find((candidate) => candidate.urlPath === route.path);
    expect(file, `route file missing for ${route.method} ${route.path}`).toBeTruthy();
    expect(file!.methods, `verb ${route.method} not exported by ${route.path}`).toContain(route.method);
  }

  for (const file of files) {
    for (const method of file.methods) {
      expect(registry.has(`${method} ${file.urlPath}`), `unregistered verb ${method} ${file.urlPath}`).toBe(true);
    }
  }
});
