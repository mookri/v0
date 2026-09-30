import { SearchQuery } from '../domain/SearchQuery';
import { SearchResult } from '../domain/SearchResult';

export interface PanditSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
