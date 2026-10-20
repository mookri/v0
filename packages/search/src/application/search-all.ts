import { SearchRequest } from '@navdhya/search/src/contracts/SearchRequest';
import { SearchResponse } from '@navdhya/search/src/contracts/SearchResponse';
import { PujaSearchSource } from '@navdhya/search/src/ports/PujaSearchSource';
import { PanditSearchSource } from '@navdhya/search/src/ports/PanditSearchSource';
import { ContentSearchSource } from '@navdhya/search/src/ports/ContentSearchSource';

export interface SearchSources {
  puja: PujaSearchSource;
  pandit: PanditSearchSource;
  content: ContentSearchSource;
}

export async function searchAll(sources: SearchSources, req: SearchRequest): Promise<SearchResponse> {
  const query = { text: req.query, limit: req.limit };
  const [pujas, pandits, content] = await Promise.all([
    sources.puja.search(query),
    sources.pandit.search(query),
    sources.content.search(query),
  ]);
  const results = [...pujas, ...pandits, ...content]
    .sort((a, b) => b.score - a.score)
    .slice(0, req.limit ?? 20);
  return { results };
}
