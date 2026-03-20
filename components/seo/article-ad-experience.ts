import type { ClusterMeta, SEOPage } from "@/lib/content/types";
import { getAdOrchestrationRuntime } from "@/lib/ad-orchestration";
import type { DecisionRequest, DecisionResponse, PlacementPlan } from "@/lib/ad-orchestration";

export type ArticleAdBaseRequest = Omit<DecisionRequest, "requestId" | "opportunities">;

export interface ArticleAdExperience {
  contentPlan: PlacementPlan;
  initialDecisions: DecisionResponse[];
  baseRequest: ArticleAdBaseRequest;
  prefetchedSlotIds: string[];
}

export function buildArticleAdBaseRequest(page: SEOPage, clusterMeta: ClusterMeta): ArticleAdBaseRequest {
  return {
    page: {
      title: page.title,
      keywords: [page.primaryKeyword, ...page.secondaryKeywords].filter(Boolean),
      categories: [page.cluster],
      language: "en-IN",
      url: page.canonicalUrl ?? `${clusterMeta.basePath}/${page.slug}`,
      scrollDepth: 0,
    },
    user: {
      anonymousId: `seo:${page.slug}`,
      sessionId: `article-${page.id}`,
    },
    client: {
      viewportWidth: 1280,
      viewportHeight: 900,
      connection: "wifi",
      language: "en-IN",
      timezone: "Asia/Kolkata",
    },
    session: {
      id: `article-${page.id}`,
      engagementScore: 0.35,
      scrollDepth: 0,
    },
  };
}

export async function buildArticleAdExperience(page: SEOPage, clusterMeta: ClusterMeta): Promise<ArticleAdExperience> {
  const runtime = getAdOrchestrationRuntime();
  const contentPlan = runtime.placementEngine.buildArticlePlan(page.content);
  const baseRequest = buildArticleAdBaseRequest(page, clusterMeta);

  const initialOpportunities = contentPlan.opportunities.filter(
    (opportunity) => !opportunity.lazy || opportunity.depthBucket === "above_fold",
  );

  const initialDecisions = initialOpportunities.length > 0
    ? (await runtime.decisionEngine.decideMany({
      requestId: `ssr_${page.id}`,
      opportunities: initialOpportunities,
      ...baseRequest,
    })).decisions
    : [];

  return {
    contentPlan,
    initialDecisions,
    baseRequest,
    prefetchedSlotIds: initialDecisions.map((decision) => decision.slotId),
  };
}
