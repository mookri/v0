import { SearchDocument } from '@navdhya/search/src/domain/SearchDocument';
import { SearchQuery } from '@navdhya/search/src/domain/CanonicalQuery';

export interface EngineResult {
  documents: SearchDocument[];
  total: number;
}

export interface SearchEngine {
  search(query: SearchQuery): Promise<EngineResult>;
  suggest(prefix: string, limit: number): Promise<string[]>;
  bulkIndex(documents: SearchDocument[]): Promise<void>;
  delete(entityType: string, entityId: string): Promise<void>;
  health(): Promise<{ ok: boolean; indexVersion: string }>;
}

// Idempotent event-driven indexing: entity version wins, stale events rejected.
export function shouldApplyIndexEvent(currentVersion: number | undefined, incomingVersion: number): boolean {
  if (currentVersion === undefined) return true;
  return incomingVersion > currentVersion;
}
