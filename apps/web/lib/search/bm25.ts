// /lib/search/bm25.ts
// In-process BM25 ranking for the BFF's federated search (SEO track S37).
//
// The BFF keeps a small in-memory index of the content corpus (Laravel's
// /search/index-docs projection when the backend is enabled, the disk SEO
// corpus otherwise) so type-ahead and result fusion never depend on a
// per-keystroke FULLTEXT round trip. Laravel's FULLTEXT query remains the
// relevance authority — BM25 results are fused in, not substituted for it.

export interface IndexableDoc {
  /** Stable unique key — `type:slug`. */
  id: string;
  /** entity_type — seo_page | temple | pandit | puja_service | … */
  type: string;
  title: string;
  subtitle?: string | null;
  /** Plain text body (HTML already stripped by the caller). */
  text?: string | null;
  /** Site-relative URL, e.g. /ceremonies/hindu-wedding-ceremony-guide. */
  url: string;
  slug?: string | null;
  city?: string | null;
  image?: string | null;
}

export interface ScoredDoc {
  doc: IndexableDoc;
  score: number;
}

const STOPWORDS = new Set([
  "a", "an", "and", "are", "as", "at", "be", "but", "by", "for", "from",
  "in", "is", "it", "of", "on", "or", "that", "the", "this", "to", "was",
  "with", "you", "your",
]);

/**
 * Lowercase tokenizer — splits on non-alphanumerics, drops stopwords and
 * single characters. Returns an empty array for queries that are entirely
 * stopwords (callers treat that as "no results", not an error).
 */
export function tokenize(value: string): string[] {
  if (!value) return [];
  return value
    .toLowerCase()
    .split(/[^a-z0-9]+/)
    .filter((token) => token.length >= 2 && !STOPWORDS.has(token));
}

interface PostingsEntry {
  doc: number;
  tf: number;
}

/**
 * BM25 (k1=1.2, b=0.75) over an in-memory corpus.
 *
 * Title tokens are indexed with a 3x term-frequency boost so title matches
 * outrank incidental body mentions without a separate field-weight scheme.
 */
export class Bm25Index {
  private readonly docs: IndexableDoc[];
  private readonly docLengths: number[] = [];
  private readonly avgDocLength: number;
  private readonly postings = new Map<string, PostingsEntry[]>();

  private static readonly K1 = 1.2;
  private static readonly B = 0.75;
  private static readonly TITLE_BOOST = 3;
  /** Cap on indexed body text per doc — keeps the index bounded. */
  private static readonly MAX_TEXT_CHARS = 3_000;

  constructor(docs: IndexableDoc[]) {
    this.docs = docs;
    let totalLength = 0;

    docs.forEach((doc, docIndex) => {
      const titleTokens = tokenize(doc.title);
      const subtitleTokens = tokenize(doc.subtitle ?? "");
      const bodyTokens = tokenize(
        (doc.text ?? "").slice(0, Bm25Index.MAX_TEXT_CHARS),
      );

      const termFrequencies = new Map<string, number>();
      const add = (tokens: string[], weight: number) => {
        for (const token of tokens) {
          termFrequencies.set(token, (termFrequencies.get(token) ?? 0) + weight);
        }
      };

      add(titleTokens, Bm25Index.TITLE_BOOST);
      add(subtitleTokens, Bm25Index.TITLE_BOOST);
      add(bodyTokens, 1);

      let length = 0;
      for (const [term, tf] of termFrequencies) {
        length += tf;
        const entries = this.postings.get(term);
        if (entries) {
          entries.push({ doc: docIndex, tf });
        } else {
          this.postings.set(term, [{ doc: docIndex, tf }]);
        }
      }

      this.docLengths.push(length);
      totalLength += length;
    });

    this.avgDocLength = docs.length > 0 ? totalLength / docs.length : 0;
  }

  get size(): number {
    return this.docs.length;
  }

  /**
   * Rank the corpus against a free-text query. Returns at most `limit`
   * docs, best first; empty for blank/stopword-only queries.
   */
  search(query: string, limit = 10): ScoredDoc[] {
    const terms = [...new Set(tokenize(query))];
    if (terms.length === 0 || this.docs.length === 0) return [];

    const scores = new Map<number, number>();
    const docCount = this.docs.length;

    for (const term of terms) {
      const entries = this.postings.get(term);
      if (!entries) continue;

      const df = entries.length;
      // Standard BM25 IDF, smoothed so idf stays strictly positive.
      const idf = Math.log(1 + (docCount - df + 0.5) / (df + 0.5));

      for (const { doc: docIndex, tf } of entries) {
        const norm =
          Bm25Index.K1 *
          (1 -
            Bm25Index.B +
            (Bm25Index.B * this.docLengths[docIndex]) /
              (this.avgDocLength || 1));
        const contribution = (idf * tf * (Bm25Index.K1 + 1)) / (tf + norm);
        scores.set(docIndex, (scores.get(docIndex) ?? 0) + contribution);
      }
    }

    return [...scores.entries()]
      .map(([docIndex, score]) => ({ doc: this.docs[docIndex], score }))
      .sort((a, b) => b.score - a.score)
      .slice(0, limit);
  }

  /**
   * Prefix type-ahead over the indexed vocabulary — every doc containing a
   * term starting with `query`'s first token, deduplicated by doc, best
   * first. Cheap enough to run per keystroke (single pass over the term
   * dictionary).
   */
  suggest(query: string, limit = 8): IndexableDoc[] {
    const prefix = tokenize(query)[0];
    if (!prefix || this.docs.length === 0) return [];

    const docScores = new Map<number, number>();
    for (const [term, entries] of this.postings) {
      if (!term.startsWith(prefix)) continue;
      // Longer vocabulary matches are more specific — favour them.
      const weight = term.length === prefix.length ? 2 : 1;
      for (const { doc: docIndex, tf } of entries) {
        docScores.set(docIndex, (docScores.get(docIndex) ?? 0) + weight * Math.min(tf, 5));
      }
    }

    return [...docScores.entries()]
      .map(([docIndex, score]) => ({ doc: this.docs[docIndex], score }))
      .sort((a, b) => b.score - a.score)
      .slice(0, limit)
      .map((scored) => scored.doc);
  }
}
