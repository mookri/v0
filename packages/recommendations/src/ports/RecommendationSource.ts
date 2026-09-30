import { Recommendation } from '../domain/Recommendation';
import { RecommendationContext } from '../contracts/RecommendationContext';

export interface RecommendationSource {
  getRecommendations(ctx: RecommendationContext): Promise<Recommendation[]>;
}
