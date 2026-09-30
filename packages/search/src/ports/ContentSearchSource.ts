import { SearchQuery } from '../domain/SearchQuery';
import { SearchResult } from '../domain/SearchResult';

export interface ContentSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
