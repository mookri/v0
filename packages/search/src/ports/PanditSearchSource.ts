import { SearchQuery } from '@navdhya/search/src/domain/SearchQuery';
import { SearchResult } from '@navdhya/search/src/domain/SearchResult';

export interface PanditSearchSource {
  search(query: SearchQuery): Promise<SearchResult[]>;
}
