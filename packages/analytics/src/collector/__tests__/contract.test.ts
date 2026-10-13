// Collector contract units (plan B3, item 2): byte cap, batch bound,
// forbidden-property stripping (data minimization).

import { describe, expect, it } from 'vitest';
import {
  FORBIDDEN_PARAM_KEYS,
  MAX_BATCH_BODY_BYTES,
  MAX_EVENTS_PER_BATCH,
  byteLength,
  stripForbiddenParams,
} from '@navdhya/analytics/src/collector/contract';

describe('collector contract (B3a)', () => {
  it('pins the documented bounds', () => {
    expect(MAX_BATCH_BODY_BYTES).toBe(64 * 1024);
    expect(MAX_EVENTS_PER_BATCH).toBe(25);
    expect(FORBIDDEN_PARAM_KEYS).toContain('email');
    expect(FORBIDDEN_PARAM_KEYS).toContain('card_number');
  });

  it('measures byte length, not UTF-16 length', () => {
    // '₹' is 1 UTF-16 unit (surrogate pair → 2) but 3 UTF-8 bytes.
    const text = '₹'.repeat(10);
    expect(byteLength(text)).toBe(30);
  });

  it('strips forbidden keys case-insensitively, leaving the input untouched', () => {
    const original = {
      id: 'EVT_1',
      name: 'page_view',
      params: { page_type: 'home', Email: 'a@b.c', USER_ID: 'u-1', note: 'ok' },
    };
    const { events, strippedKeys } = stripForbiddenParams([original]);
    const stripped = events[0] as { params: Record<string, unknown> };
    expect(stripped.params).toEqual({ page_type: 'home', note: 'ok' });
    expect(strippedKeys.sort()).toEqual(['Email', 'USER_ID']);
    // original untouched (defensive copy):
    expect((original.params as Record<string, unknown>)['Email']).toBe('a@b.c');
  });

  it('leaves declared registry params alone (redaction is a downstream-sink rule)', () => {
    const declared = { id: 'EVT_2', name: 'search', params: { query: 'red saree' } };
    const { events, strippedKeys } = stripForbiddenParams([declared]);
    expect(events[0]).toBe(declared); // same reference — nothing touched
    expect(strippedKeys).toEqual([]);
  });

  it('passes malformed entries through so validation reports the structural error', () => {
    const { events } = stripForbiddenParams([null, 42, 'x', { params: 'not-an-object' }]);
    expect(events).toEqual([null, 42, 'x', { params: 'not-an-object' }]);
  });
});
