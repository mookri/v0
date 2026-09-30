import { RecommendationSource } from '../ports/RecommendationSource';
import { RecommendationResponse } from '../contracts/RecommendationResponse';

export async function getHomeRecommendations(source: RecommendationSource): Promise<RecommendationResponse> {
  const recommendations = await source.getRecommendations({ limit: 10 });
  return { recommendations };
}
