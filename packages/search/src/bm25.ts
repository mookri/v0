// In-process BM25 ranking (shared). Ported from apps/web/lib/search/bm25.ts
// and upgraded to the v2 tokenizer (Devanagari fold + bigrams).

import { tokenize } from './tokenizer';

export interface IndexableDoc {
  id: string;
  type: string;
  title: string;
  subtitle?: string | null;
  text?: string | null;
  url: string;
  slug?: string | null;
  city?: string | null;
  image?: string | null;
  locale?: string | null;
  bookings_30d?: number;
  views_7d?: number;
  indexed_at?: string | null;
}

export interface ScoredDoc {
  doc: IndexableDoc;
  score: number;
}

const TITLE_BOOST = 3;
const SUBTITLE_BOOST = 2;
const BODY_BOOST = 1;
const MAX_TEXT_CHARS = 3_000;
const K1 = 1.2;
const B = 0.75;

export class Bm25Index {
  private readonly docs: IndexableDoc[];
  private readonly docLengths: number[] = [];
  private readonly avgDocLength: number;
  private readonly postings = new Map<string, { doc: number; tf: number }[]>();

  constructor(docs: IndexableDoc[]) {
    this.docs = docs;
    let totalLength = 0;
    docs.forEach((doc, docIndex) => {
      const titleTokens = tokenize(doc.title, doc.locale ?? 'en');
      const subtitleTokens = tokenize(doc.subtitle ?? '', doc.locale ?? 'en');
      const bodyTokens = tokenize((doc.text ?? '').slice(0, MAX_TEXT_CHARS), doc.locale ?? 'en');

      const tfMap = new Map<string, number>();
      const add = (tokens: string[], weight: number) => {
        for (const t of tokens) tfMap.set(t, (tfMap.get(t) ?? 0) + weight);
      };
      add(titleTokens, TITLE_BOOST);
      add(subtitleTokens, SUBTITLE_BOOST);
      add(bodyTokens, BODY_BOOST);

      let length = 0;
      for (const [term, tf] of tfMap) {
        length += tf;
        const entries = this.postings.get(term);
        if (entries) entries.push({ doc: docIndex, tf });
        else this.postings.set(term, [{ doc: docIndex, tf }]);
      }
      this.docLengths.push(length);
      totalLength += length;
    });
    this.avgDocLength = docs.length > 0 ? totalLength / docs.length : 0;
  }

  get size(): number {
    return this.docs.length;
  }

  search(query: string, limit = 10): ScoredDoc[] {
    const terms = [...new Set(tokenize(query, 'en'))];
    if (terms.length === 0 || this.docs.length === 0) return [];

    const scores = new Map<number, number>();
    const docCount = this.docs.length;

    for (const term of terms) {
      const entries = this.postings.get(term);
      if (!entries) continue;
      const df = entries.length;
      const idf = Math.log(1 + (docCount - df + 0.5) / (df + 0.5));
      for (const { doc: docIndex, tf } of entries) {
        const norm = K1 * (1 - B + (B * this.docLengths[docIndex]) / (this.avgDocLength || 1));
        const contrib = (idf * tf * (K1 + 1)) / (tf + norm);
        scores.set(docIndex, (scores.get(docIndex) ?? 0) + contrib);
      }
    }

    return [...scores.entries()]
      .map(([docIndex, score]) => ({ doc: this.docs[docIndex], score }))
      .sort((a, b) => b.score - a.score)
      .slice(0, limit);
  }

  suggest(query: string, limit = 8): IndexableDoc[] {
    const tokens = tokenize(query, 'en');
    const prefix = tokens[0];
    if (!prefix || this.docs.length === 0) return [];

    const docScores = new Map<number, number>();
    for (const [term, entries] of this.postings) {
      if (!term.startsWith(prefix)) continue;
      const weight = term.length === prefix.length ? 2 : 1;
      for (const { doc: docIndex, tf } of entries) {
        docScores.set(docIndex, (docScores.get(docIndex) ?? 0) + weight * Math.min(tf, 5));
      }
    }

    return [...docScores.entries()]
      .map(([docIndex, score]) => ({ doc: this.docs[docIndex], score }))
      .sort((a, b) => b.score - a.score)
      .slice(0, limit)
      .map((s) => s.doc);
  }
}

export function tokenizeExport(value: string, locale = 'en'): string[] {
  return tokenize(value, locale);
}

export { normalize } from './tokenizer';