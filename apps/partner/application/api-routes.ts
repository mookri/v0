// Registry of partner BFF endpoints that translate to a Laravel backend
// source of truth (parity-vendor/temple/onboarding contract tables and
// tests/api/partner-contract.test.ts consume this). Memory-only routes
// (assignments, health, version) are intentionally not listed.

export interface PartnerApiRouteMeta {
  routeId: string;
  method: string;
  path: string;
  access: 'authenticated';
  capability: string;
  cache: 'private-short' | 'none';
}

export const apiRoutes: PartnerApiRouteMeta[] = [
  // Temple directory + dashboard (P11)
  { routeId: 'partner.temples', method: 'GET', path: '/api/temples', access: 'authenticated', capability: 'temple:read', cache: 'private-short' },
  { routeId: 'partner.temple.detail', method: 'GET', path: '/api/temples/:templeId', access: 'authenticated', capability: 'temple:read', cache: 'private-short' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/overview', access: 'authenticated', capability: 'temple:partner', cache: 'none' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/pujas', access: 'authenticated', capability: 'temple:partner', cache: 'none' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/bookings', access: 'authenticated', capability: 'temple:partner', cache: 'none' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/calendar', access: 'authenticated', capability: 'temple:partner', cache: 'none' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/analytics', access: 'authenticated', capability: 'temple:partner', cache: 'none' },
  { routeId: 'partner.temple.dashboard', method: 'GET', path: '/api/temples/:templeId/dashboard/settings', access: 'authenticated', capability: 'temple:partner', cache: 'none' },

  // Partner onboarding (P10)
  { routeId: 'partner.onboarding.start', method: 'POST', path: '/api/onboarding/start', access: 'authenticated', capability: 'onboarding', cache: 'none' },
  { routeId: 'partner.onboarding.detail', method: 'GET', path: '/api/onboarding/:id', access: 'authenticated', capability: 'onboarding', cache: 'none' },
  { routeId: 'partner.onboarding.status', method: 'GET', path: '/api/onboarding/:id/status', access: 'authenticated', capability: 'onboarding', cache: 'private-short' },
  { routeId: 'partner.onboarding.step', method: 'PUT', path: '/api/onboarding/:id/step/:step', access: 'authenticated', capability: 'onboarding', cache: 'none' },
  { routeId: 'partner.onboarding.submit', method: 'POST', path: '/api/onboarding/:id/submit', access: 'authenticated', capability: 'onboarding', cache: 'none' },
  { routeId: 'partner.onboarding.submit.confirm', method: 'POST', path: '/api/onboarding/:id/submit/confirm', access: 'authenticated', capability: 'onboarding', cache: 'none' },
  { routeId: 'partner.onboarding.documents', method: 'POST', path: '/api/onboarding/:id/documents', access: 'authenticated', capability: 'onboarding', cache: 'none' },

  // Review (P10)
  { routeId: 'partner.review.queue', method: 'GET', path: '/api/review/queue', access: 'authenticated', capability: 'onboarding:review:view', cache: 'none' },
  { routeId: 'partner.review.decision', method: 'POST', path: '/api/review/:id/decision', access: 'authenticated', capability: 'onboarding:review:approve', cache: 'none' },
];
