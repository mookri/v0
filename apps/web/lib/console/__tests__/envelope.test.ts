import { readConsole, type ConsoleEnvelope } from '@/lib/console/envelope';

/**
 * The console panels must never present an unconfigured backend or a failed
 * call as an empty table ("no roles exist"). `readConsole` is the seam that
 * keeps those three outcomes apart, so it is unit-tested on its own.
 */
describe('readConsole', () => {
  it('returns data when the BFF carries a real payload', () => {
    const body: ConsoleEnvelope<string[]> = { data: { data: ['admin'], meta: { request_id: 'req_1' } } };
    expect(readConsole(body)).toEqual({ kind: 'data', value: ['admin'] });
  });

  it('flags the BFF fallback envelope instead of returning empty data', () => {
    const body: ConsoleEnvelope<string[]> = { data: { data: [], meta: { fallback: true } } };
    expect(readConsole(body)).toEqual({ kind: 'fallback' });
  });

  it('surfaces a mapped error envelope with its code', () => {
    const body: ConsoleEnvelope<string[]> = {
      error: { code: 'FORBIDDEN', message: 'Capability required.', requestId: 'req_2' },
    };
    expect(readConsole(body)).toEqual({ kind: 'error', code: 'FORBIDDEN', message: 'Capability required.' });
  });

  it('reports a payload-free success rather than inventing rows', () => {
    const body: ConsoleEnvelope<string[]> = { data: { meta: {} } };
    expect(readConsole(body)).toEqual({ kind: 'data', value: null });
  });

  it('defaults a codeless error to INTERNAL_ERROR', () => {
    expect(readConsole({ error: { code: '', message: '' } })).toEqual({
      kind: 'error',
      code: 'INTERNAL_ERROR',
      message: 'The request failed.',
    });
  });

  it('treats a missing body as an error, never as data', () => {
    expect(readConsole(null)).toEqual({
      kind: 'error',
      code: 'INTERNAL_ERROR',
      message: 'The console returned no response.',
    });
  });
});
