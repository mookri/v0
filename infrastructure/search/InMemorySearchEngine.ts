import { SearchDocument } from '../../packages/search/src/domain/SearchDocument';
import { SearchQuery, normalizeQueryText } from '../../packages/search/src/domain/CanonicalQuery';
import { SearchEngine } from '../../packages/search/src/ports/SearchEngine';

// Lexical in-memory engine for local/dev/test. Production providers implement
// the same SearchEngine port (OpenSearch/Typesense/Meilisearch later).
export class InMemorySearchEngine implements SearchEngine {
  private readonly docs = new Map<string, SearchDocument>();

  async bulkIndex(documents: SearchDocument[]): Promise<void> {
    for (const d of documents) {
      const current = this.docs.get(d.id);
      if (current && current.sourceVersion >= d.sourceVersion) continue;
      this.docs.set(d.id, d);
    }
  }

  async delete(_entityType: string, entityId: string): Promise<void> {
    this.docs.delete(entityId);
  }

  async search(query: SearchQuery): Promise<{ documents: SearchDocument[]; total: number }> {
    const needle = normalizeQueryText(query.text ?? '');
    const matched = [...this.docs.values()].filter((d) => {
      if (d.status !== 'ACTIVE') return false;
      if (query.entityTypes && !query.entityTypes.includes(d.entityType)) return false;
      if (!needle) return true;
      const hay = [...d.searchableText, d.title].join(' ').toLowerCase();
      return needle.split(' ').every((tok) => hay.includes(tok));
    });
    const total = matched.length;
    return { documents: matched.slice(0, query.limit), total };
  }

  async suggest(prefix: string, limit: number): Promise<string[]> {
    const p = normalizeQueryText(prefix);
    const titles = new Set<string>();
    for (const d of this.docs.values()) {
      if (d.title.toLowerCase().startsWith(p)) titles.add(d.title);
      if (titles.size >= limit) break;
    }
    return [...titles].slice(0, limit);
  }

  async health(): Promise<{ ok: boolean; indexVersion: string }> {
    return { ok: true, indexVersion: 'local-v1' };
  }
}
