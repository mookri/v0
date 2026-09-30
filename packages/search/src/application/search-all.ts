import { SearchRequest } from '../contracts/SearchRequest';
import { SearchResponse } from '../contracts/SearchResponse';
import { PujaSearchSource } from '../ports/PujaSearchSource';
import { PanditSearchSource } from '../ports/PanditSearchSource';
import { ContentSearchSource } from '../ports/ContentSearchSource';

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
