import { RecommendationSource } from '../ports/RecommendationSource';
import { RecommendationResponse } from '../contracts/RecommendationResponse';

export async function getPujaRecommendations(
  source: RecommendationSource,
  pujaId: string,
): Promise<RecommendationResponse> {
  const recommendations = await source.getRecommendations({ pujaId, limit: 10 });
  return { recommendations };
}
