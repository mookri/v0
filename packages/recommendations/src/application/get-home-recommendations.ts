import { RecommendationSource } from '@navdhya/recommendations/src/ports/RecommendationSource';
import { RecommendationResponse } from '@navdhya/recommendations/src/contracts/RecommendationResponse';

export async function getHomeRecommendations(source: RecommendationSource): Promise<RecommendationResponse> {
  const recommendations = await source.getRecommendations({ limit: 10 });
  return { recommendations };
}
