import type { SearchIndex, SearchIndexDoc } from '../../../packages/contracts/src/search/search-index';

// Postgres-shaped today (LIKE semantics), swappable for OpenSearch/Meilisearch
// later without changing @navdhya/search.
export class PostgresSearchIndex implements SearchIndex {
  private readonly docs = new Map<string, SearchIndexDoc>();

  async index(doc: SearchIndexDoc): Promise<void> {
    this.docs.set(doc.id, doc);
  }

  async remove(id: string): Promise<void> {
    this.docs.delete(id);
  }

  async search(query: string, limit = 20): Promise<SearchIndexDoc[]> {
    const q = query.toLowerCase();
    return [...this.docs.values()]
      .filter((d) => `${d.title} ${d.text}`.toLowerCase().includes(q))
      .slice(0, limit);
  }
}
