import { Recommendation } from '@navdhya/recommendations/src/domain/Recommendation';
import { RecommendationContext } from '@navdhya/recommendations/src/contracts/RecommendationContext';

export interface RecommendationSource {
  getRecommendations(ctx: RecommendationContext): Promise<Recommendation[]>;
}
