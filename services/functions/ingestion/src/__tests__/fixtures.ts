// Shared fixtures for ingestion tests — synthetic, PII-free.

import type { SignalEnvelope, Surface } from '@navdhya/analytics/src/contracts/envelope';

export function validEnvelope(overrides: Partial<SignalEnvelope> = {}): SignalEnvelope {
  return {
    id: 'FIXTURE000000000000000000A1',
    name: 'page_view',
    version: 1,
    occurredAt: '2026-10-12T10:00:00.000Z',
    surface: 'web' as Surface,
    appVersion: '1.0.0',
    session: { id: 'sess_ing_001', seq: 1 },
    actor: { uidHash: null, anonId: 'anon_ing_001' },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/pujas' },
    params: { page_type: 'catalog' },
    ...overrides,
  };
}
