import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';
import { statusFor, type ApiErrorCode } from '@navdhya/api-contracts/src/common/envelope';
import type { PartnerApiErrorCode } from '@apps/partner/application/api-envelope';
import { apiRoutes } from '@apps/partner/application/api-routes';

// Contract parity for the partner BFF: HTTP statuses are the cross-surface
// contract (parity-onboarding.md code correspondence + the temple rows in
// parity-customers.md). Codes differ by registry — clients branch on
// per-surface codes, never on string equality across surfaces.

// Codes present in BOTH the partner BFF registry and the platform contract
// registry — statuses must agree with statusFor().
const SHARED_CODES = [
  'UNAUTHENTICATED',
  'FORBIDDEN',
  'NOT_FOUND',
  'VALIDATION_ERROR',
  'CONFLICT',
  'STALE_RESOURCE',
  'RATE_LIMITED',
  'DEPENDENCY_UNAVAILABLE',
  'INTERNAL_ERROR',
] as const satisfies readonly PartnerApiErrorCode[];

const SHARED_STATUS: Record<(typeof SHARED_CODES)[number], number> = {
  UNAUTHENTICATED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  VALIDATION_ERROR: 422,
  CONFLICT: 409,
  STALE_RESOURCE: 409,
  RATE_LIMITED: 429,
  DEPENDENCY_UNAVAILABLE: 503,
  INTERNAL_ERROR: 500,
};

// Partner-only domain codes: statuses as used by the BFF handlers and the
// parity-onboarding.md correspondence table.
const PARTNER_ONLY_STATUS: Record<string, number> = {
  ASSIGNMENT_NOT_FOUND: 404,
  ASSIGNMENT_FORBIDDEN: 403,
  ASSIGNMENT_EXPIRED: 409,
  ASSIGNMENT_CONFLICT: 409,
  ONBOARDING_NOT_FOUND: 404,
  ONBOARDING_INVALID_TRANSITION: 409,
  ONBOARDING_INCOMPLETE: 422,
};

// Mirror of the PartnerApiErrorCode union — update together with
// application/api-envelope.ts (the compiler rejects invalid entries).
const BFF_CODES: PartnerApiErrorCode[] = [
  'UNAUTHENTICATED',
  'FORBIDDEN',
  'NOT_FOUND',
  'CONFLICT',
  'STALE_RESOURCE',
  'DEPENDENCY_UNAVAILABLE',
  'ASSIGNMENT_NOT_FOUND',
  'ASSIGNMENT_FORBIDDEN',
  'ASSIGNMENT_EXPIRED',
  'ASSIGNMENT_CONFLICT',
  'ONBOARDING_NOT_FOUND',
  'ONBOARDING_INVALID_TRANSITION',
  'ONBOARDING_INCOMPLETE',
  'RATE_LIMITED',
  'VALIDATION_ERROR',
  'INTERNAL_ERROR',
];

test('every shared BFF code resolves to its parity status', () => {
  for (const code of SHARED_CODES) {
    // Narrows PartnerApiErrorCode to the contract union: a BFF-only code
    // in this list fails types.
    const contractCode: ApiErrorCode = code;
    expect(statusFor(contractCode)).toBe(SHARED_STATUS[code]);
  }
});

test('every BFF code is documented with exactly one status', () => {
  const shared = new Set<string>(SHARED_CODES);
  const partnerOnly = new Set(Object.keys(PARTNER_ONLY_STATUS));

  // No overlap between the two registries
  for (const code of shared) expect(partnerOnly.has(code)).toBe(false);

  // The mirror covers exactly shared ∪ partner-only — adding a code to
  // the envelope union without documenting its status fails here.
  expect(new Set(BFF_CODES)).toEqual(new Set([...shared, ...partnerOnly]));
  expect(BFF_CODES.length).toBe(shared.size + partnerOnly.size);

  // Shared statuses agree with the platform contract table
  for (const [code, status] of Object.entries(SHARED_STATUS)) {
    expect(status).toBe(statusFor(code as ApiErrorCode));
  }
});

interface BackendMapping {
  bff: string;
  backend: string[];
  inRegistry: boolean;
}

// Mirror of the parity-doc flow tables (BFF endpoint → backend source).
// Backend paths must live under the module prefixes; BFF paths flagged
// inRegistry must exist in the apiRoutes registry.
const MAPPINGS: BackendMapping[] = [
  // Temple directory + dashboard (P11)
  { bff: 'GET /api/temples', backend: ['GET /api/v1/temple/profiles'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId', backend: ['GET /api/v1/temple/profiles/{id}'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/overview', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/overview'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/pujas', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/pujas'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/bookings', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/bookings'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/calendar', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/calendar'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/analytics', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/analytics'], inRegistry: true },
  { bff: 'GET /api/temples/:templeId/dashboard/settings', backend: ['GET /api/v1/temple/partner/temples/{id}/dashboard/settings'], inRegistry: true },

  // Partner onboarding (P10)
  { bff: 'POST /api/onboarding/start', backend: ['POST /api/v1/onboarding/applications'], inRegistry: true },
  { bff: 'GET /api/onboarding/:id', backend: ['GET /api/v1/onboarding/applications/{id}'], inRegistry: true },
  { bff: 'GET /api/onboarding/:id/status', backend: ['GET /api/v1/onboarding/applications/{id}/status'], inRegistry: true },
  { bff: 'PUT /api/onboarding/:id/step/:step', backend: ['PUT /api/v1/onboarding/applications/{id}/step/{step}'], inRegistry: true },
  { bff: 'POST /api/onboarding/:id/submit', backend: ['POST /api/v1/onboarding/applications/{id}/submit'], inRegistry: true },
  { bff: 'POST /api/onboarding/:id/submit/confirm', backend: ['POST /api/v1/onboarding/applications/{id}/submit/confirm'], inRegistry: true },
  { bff: 'POST /api/onboarding/:id/documents', backend: ['POST /api/v1/onboarding/applications/{id}/documents'], inRegistry: true },

  // Review (P10): one BFF decision verb fans out to three colon verbs
  { bff: 'GET /api/review/queue', backend: ['GET /api/v1/onboarding/review/queue'], inRegistry: true },
  {
    bff: 'POST /api/review/:id/decision',
    backend: [
      'POST /api/v1/onboarding/review/{id}:approve',
      'POST /api/v1/onboarding/review/{id}:reject',
      'POST /api/v1/onboarding/review/{id}:request-info',
    ],
    inRegistry: true,
  },
];

test('backend mappings use module prefixes and registries agree', () => {
  const registry = new Set(apiRoutes.map((r) => `${r.method} ${r.path}`));
  for (const mapping of MAPPINGS) {
    expect(mapping.backend.length).toBeGreaterThan(0);
    for (const path of mapping.backend) {
      expect(path).toMatch(/^(GET|POST|PATCH|PUT|DELETE) \/api\/v1\/(temple|onboarding)\//);
    }
    if (mapping.inRegistry) {
      expect(registry.has(mapping.bff)).toBe(true);
    }
  }
});

// Registry entries must correspond to real route files with the declared
// verb exported — catches registry drift (renamed/moved/deleted handlers).
interface RouteFile {
  urlPath: string;
  methods: string[];
}

function collectRouteFiles(dir: string, segments: string[] = []): RouteFile[] {
  const out: RouteFile[] = [];
  if (!fs.existsSync(dir)) return out;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      const segment = entry.name.startsWith('[') && entry.name.endsWith(']')
        ? `:${entry.name.slice(1, -1)}`
        : entry.name;
      out.push(...collectRouteFiles(path.join(dir, entry.name), [...segments, segment]));
    } else if (entry.name === 'route.ts') {
      const source = fs.readFileSync(path.join(dir, entry.name), 'utf8');
      const methods = [...source.matchAll(/export (?:async )?function (GET|POST|PUT|PATCH|DELETE)\b/g)]
        .map((m) => m[1] as string);
      out.push({ urlPath: `/api/${segments.join('/')}`, methods });
    }
  }
  return out;
}

test('every registry entry maps to a real route file and verb', () => {
  const apiRoot = path.join(process.cwd(), 'apps', 'partner', 'app', 'api');
  const files = collectRouteFiles(apiRoot);
  expect(files.length).toBeGreaterThan(0);

  for (const route of apiRoutes) {
    const file = files.find((f) => f.urlPath === route.path);
    expect(file, `route file missing for ${route.method} ${route.path}`).toBeTruthy();
    expect(file!.methods, `verb ${route.method} not exported by ${route.path}`).toContain(route.method);
  }
});
