import { SearchQuery } from '../domain/SearchQuery';
import { SearchResult } from '../domain/SearchResult';

export interface PujaSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
