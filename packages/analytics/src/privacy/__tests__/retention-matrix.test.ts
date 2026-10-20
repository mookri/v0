// Retention matrix (plan B7): every domain the plan lists is covered, every
// registry retentionClass resolves, proof domains outlive the data.

import { describe, expect, it } from 'vitest';
import {
  REQUIRED_DOMAINS,
  RETENTION_MATRIX,
  RETENTION_MATRIX_VERSION,
  retentionDaysForClass,
} from '@navdhya/analytics/src/privacy/retention-matrix';
import { EVENT_DEFS } from '@navdhya/analytics/src/registry/generated/event-types';

describe('retention matrix (B7)', () => {
  it('covers every domain plan B7 bullet 1 requires', () => {
    const domains = new Set(RETENTION_MATRIX.map((row) => row.domain));
    for (const required of REQUIRED_DOMAINS) {
      expect(domains.has(required), required).toBe(true);
    }
  });

  it('every registry retentionClass has a days mapping', () => {
    for (const def of Object.values(EVENT_DEFS)) {
      expect(() => retentionDaysForClass(def.retentionClass), def.retentionClass).not.toThrow();
    }
    expect(retentionDaysForClass('raw_30d')).toBe(30);
    expect(retentionDaysForClass('raw_90d')).toBe(90);
    expect(retentionDaysForClass('audit_long')).toBe(400);
  });

  it('an unknown retention class throws — new classes cannot skip retention', () => {
    expect(() => retentionDaysForClass('raw_forever')).toThrow(/no matrix entry/);
  });

  it('erasure tokens are indefinite; proof domains outlive behavioural data', () => {
    const erasure = RETENTION_MATRIX.find((row) => row.domain === 'erasure_tokens')!;
    expect(erasure.retentionDays).toBe('indefinite');
    const consent = RETENTION_MATRIX.find((row) => row.domain === 'consent_records')!;
    const ga4 = RETENTION_MATRIX.find((row) => row.domain === 'ga4_data')!;
    expect(Number(consent.retentionDays)).toBeGreaterThan(Number(ga4.retentionDays));
  });

  it('is versioned', () => {
    expect(RETENTION_MATRIX_VERSION).toMatch(/^\d+\.\d+\.\d+$/);
  });
});
