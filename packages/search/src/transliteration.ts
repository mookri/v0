// packages/search/src/transliteration.ts
//
// Single source of truth transliteration engine for TypeScript.
// Driven by generated artifact packages/search/gen/transliteration.v1.json (closes D-13).

import schema from '../gen/transliteration.v1.json';

export const TRANSLITERATION_SCHEMA = schema;

const INDEPENDENT_VOWELS = schema.independent_vowels as Record<string, string>;
const VOWEL_MATRAS = schema.vowel_matras as Record<string, string>;
const CONSONANTS = schema.consonants as Record<string, string>;
const SPECIAL_SIGNS = schema.special_signs as Record<string, string>;
const DIGITS = schema.digits as Record<string, string>;
const LATIN_FOLD = schema.latin_fold as Record<string, string>;

/**
 * Convert Devanagari text to exact IAST scientific transliteration.
 */
export function devaToIast(text: string): string {
  const chars = Array.from(text);
  let out = '';
  const n = chars.length;

  for (let i = 0; i < n; i++) {
    const c = chars[i];

    if (DIGITS[c]) {
      out += DIGITS[c];
      continue;
    }

    if (INDEPENDENT_VOWELS[c]) {
      out += INDEPENDENT_VOWELS[c];
      continue;
    }

    if (CONSONANTS[c]) {
      out += CONSONANTS[c];
      const next = chars[i + 1];
      if (next === '्') {
        // virama suppresses inherent vowel
        i++;
      } else if (next && VOWEL_MATRAS[next]) {
        out += VOWEL_MATRAS[next];
        i++;
      } else {
        out += 'a';
      }
      continue;
    }

    if (VOWEL_MATRAS[c]) {
      out += VOWEL_MATRAS[c];
      continue;
    }

    if (SPECIAL_SIGNS[c]) {
      out += SPECIAL_SIGNS[c];
      continue;
    }

    out += c;
  }

  return out;
}

/**
 * Fold IAST / Devanagari to plain ASCII lowercase (no diacritics).
 */
export function foldToLatin(text: string): string {
  const hasDevanagari = /[\u0900-\u097F]/.test(text);
  const iast = hasDevanagari ? devaToIast(text) : text;
  let lower = iast.toLowerCase();

  for (const [k, v] of Object.entries(LATIN_FOLD)) {
    lower = lower.replaceAll(k, v);
  }

  return lower;
}

/**
 * Backwards-compatible Devanagari folding function.
 */
export function foldDevanagari(value: string): string {
  return foldToLatin(value);
}

/**
 * Phonetic representation for fuzzy search indexing and recall.
 */
export function toPhoneticKey(text: string): string {
  let clean = foldToLatin(text).replace(/[^a-z0-9]+/g, '');

  const phoneticMap: Record<string, string> = {
    sh: 's',
    ee: 'i',
    oo: 'u',
    aa: 'a',
    kh: 'k',
    gh: 'g',
    chh: 'c',
    ch: 'c',
    jh: 'j',
    th: 't',
    dh: 'd',
    ph: 'p',
    bh: 'b',
    w: 'v',
    m: 'n',
  };

  for (const [pattern, replacement] of Object.entries(phoneticMap)) {
    clean = clean.replaceAll(pattern, replacement);
  }

  // Collapse consecutive duplicates (e.g. ss -> s)
  return clean.replace(/(.)\1+/g, '$1');
}

export interface KeySet {
  display: string;
  key_dev: string;
  key_lat: string;
  key_fold: string;
  key_phon: string;
  key_tr: string[];
}

export function generateKeySet(text: string): KeySet {
  const display = text.trim();
  if (!display) {
    return {
      display: '',
      key_dev: '',
      key_lat: '',
      key_fold: '',
      key_phon: '',
      key_tr: [],
    };
  }

  const isDevanagari = /[\u0900-\u097F]/.test(display);
  const keyDev = isDevanagari ? display : '';
  const keyLat = isDevanagari ? devaToIast(display) : display;
  const keyFold = foldToLatin(display);
  const keyPhon = toPhoneticKey(display);

  const variants = new Set<string>();
  variants.add(keyFold);

  if (keyFold.endsWith('a') && keyFold.length > 3) {
    variants.add(keyFold.slice(0, -1));
  }
  if (keyFold.includes('i')) {
    variants.add(keyFold.replaceAll('i', 'ee'));
  }
  if (keyFold.includes('u')) {
    variants.add(keyFold.replaceAll('u', 'oo'));
  }
  if (keyFold.includes('v')) {
    variants.add(keyFold.replaceAll('v', 'w'));
  }

  return {
    display,
    key_dev: keyDev,
    key_lat: keyLat,
    key_fold: keyFold,
    key_phon: keyPhon,
    key_tr: Array.from(variants),
  };
}
