import { describe, expect, it } from 'vitest';
import { devaToIast, foldToLatin, generateKeySet, TRANSLITERATION_SCHEMA } from '../transliteration';
import vectors from '../../../../platform-navdhya/tests/Fixtures/Search/translit-vectors.json';

describe('Transliteration Parity Suite (TypeScript)', () => {
  it('loads the generated schema artifact correctly', () => {
    expect(TRANSLITERATION_SCHEMA.schema_version).toBe(1);
    expect(TRANSLITERATION_SCHEMA.independent_vowels).toBeDefined();
    expect(TRANSLITERATION_SCHEMA.consonants).toBeDefined();
    expect(TRANSLITERATION_SCHEMA.latin_fold).toBeDefined();
  });

  it('matches all shared fixture vectors with PHP parity', () => {
    for (const v of vectors) {
      const actualIast = devaToIast(v.deva);
      expect(actualIast.toLowerCase()).toBe(v.iast.toLowerCase());

      const actualFold = foldToLatin(v.deva);
      expect(actualFold).toBe(v.fold);

      const keySet = generateKeySet(v.deva);
      expect(keySet.display).toBe(v.deva);
      expect(keySet.key_dev).toBe(v.deva);
      expect(keySet.key_lat).toBe(actualIast);
      expect(keySet.key_fold).toBe(v.fold);
      expect(keySet.key_tr).toContain(v.fold);
    }
  });

  it('preserves IAST scholarly Sanskrit without diacritic loss', () => {
    const keySet = generateKeySet('rudrābhiṣeka');
    expect(keySet.display).toBe('rudrābhiṣeka');
    expect(keySet.key_lat).toBe('rudrābhiṣeka');
    expect(keySet.key_fold).toBe('rudrabhisheka');
    expect(keySet.key_tr).toContain('rudrabhishek');
  });
});

