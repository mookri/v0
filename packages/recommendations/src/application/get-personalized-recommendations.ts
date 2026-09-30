import { RecommendationSource } from '../ports/RecommendationSource';
import { RecommendationResponse } from '../contracts/RecommendationResponse';

export async function getPersonalizedRecommendations(
  source: RecommendationSource,
  userId: string,
): Promise<RecommendationResponse> {
  const recommendations = await source.getRecommendations({ userId, limit: 10 });
  return { recommendations };
}
