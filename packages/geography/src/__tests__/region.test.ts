import { describe, expect, it } from 'vitest';
import { resolveCanonicalRegion, CANONICAL_REGIONS } from '../domain/region';

describe('Canonical Region Resolution (@navdhya/geography)', () => {
  it('resolves direct English and Devanagari names', () => {
    const varanasi = resolveCanonicalRegion('Varanasi');
    expect(varanasi?.id).toBe('reg_varanasi');
    expect(varanasi?.name).toBe('Varanasi');

    const kashiDev = resolveCanonicalRegion('वाराणसी');
    expect(kashiDev?.id).toBe('reg_varanasi');
  });

  it('resolves vernacular aliases and colonial spellings', () => {
    expect(resolveCanonicalRegion('Banaras')?.id).toBe('reg_varanasi');
    expect(resolveCanonicalRegion('Benares')?.id).toBe('reg_varanasi');
    expect(resolveCanonicalRegion('Kashi')?.id).toBe('reg_varanasi');
    expect(resolveCanonicalRegion('काशी')?.id).toBe('reg_varanasi');
    expect(resolveCanonicalRegion('बनारस')?.id).toBe('reg_varanasi');
    expect(resolveCanonicalRegion('Bangalore')?.id).toBe('reg_bengaluru');
    expect(resolveCanonicalRegion('Bombay')?.id).toBe('reg_mumbai');
    expect(resolveCanonicalRegion('New Delhi')?.id).toBe('reg_delhi_ncr');
  });

  it('returns null for unknown regions', () => {
    expect(resolveCanonicalRegion('Atlantis')).toBeNull();
  });
});

