import { foldDevanagari } from './transliteration';

const LATIN_STOPWORDS = new Set([
  'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for', 'from',
  'in', 'is', 'it', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was',
  'with', 'you', 'your',
]);

const HINDI_STOPWORDS = new Set([
  'का', 'के', 'की', 'है', 'और', 'में', 'एक', 'यह', 'तो', 'भी',
]);

export function tokenize(value: string, locale = 'en'): string[] {
  if (!value) return [];
  const tokens: string[] = [];

  // Devanagari akshara bigrams (raw) for ngram recall.
  if (/\p{Script=Devanagari}/u.test(value)) {
    const chars = Array.from(value).filter((c) => /\p{Script=Devanagari}/u.test(c));
    for (let i = 0; i + 1 < chars.length; i++) {
      tokens.push((chars[i] + chars[i + 1]).toLowerCase());
    }
  }

  // Fold + split.
  const folded = foldDevanagari(value).toLowerCase();
  const parts = folded.split(/[^a-z0-9]+/).filter(Boolean);
  for (const part of parts) {
    if (part.length < 2) continue;
    if (LATIN_STOPWORDS.has(part) || HINDI_STOPWORDS.has(part)) continue;
    tokens.push(part);
  }

  return [...new Set(tokens)];
}

export function normalize(value: string): string {
  return foldDevanagari(value).toLowerCase().replace(/\s+/g, ' ').trim();
}

export function expandSynonyms(tokens: string[], synonyms: Record<string, string[]>): string[] {
  const out: string[] = [];
  let added = 0;
  const cap = 3;
  for (const t of tokens) {
    if (added >= cap) break;
    const syns = synonyms[t] ?? [];
    for (const s of syns) {
      if (added >= cap) break;
      const ls = s.toLowerCase().trim();
      if (ls && !tokens.includes(ls) && !out.includes(ls)) {
        out.push(ls);
        added++;
      }
    }
  }
  return out;
}