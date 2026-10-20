import { SearchQuery } from '@navdhya/search/src/domain/SearchQuery';
import { SearchResult } from '@navdhya/search/src/domain/SearchResult';

export interface ContentSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
