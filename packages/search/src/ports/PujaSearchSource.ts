import { SearchQuery } from '@navdhya/search/src/domain/SearchQuery';
import { SearchResult } from '@navdhya/search/src/domain/SearchResult';

export interface PujaSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
