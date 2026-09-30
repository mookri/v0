import type { Ad, AdLayout, AdPlacement } from "@/components/ad-network/core/types";
import { layoutRegistry } from "./LayoutRegistry";
import { AuctionEngine } from "./AuctionEngine";
import { ExperimentEngine } from "./ExperimentEngine";
import { FrequencyPacingService } from "./FrequencyPacingService";
import { LayoutResolver } from "./LayoutResolver";
import { RankingEngine } from "./RankingEngine";
import { createRuntimeId } from "./ids";
import type {
  CampaignRepository,
  DecisionBatchResponse,
  DecisionRequest,
  DecisionResponse,
  LayoutDiversityContext,
  PlacementOpportunity,
} from "./types";

function isScheduleActive(ad: Ad, now = new Date()): boolean {
  if (!ad.schedule) return true;
  if (ad.schedule.startDate && new Date(ad.schedule.startDate) > now) return false;
  if (ad.schedule.endDate && new Date(ad.schedule.endDate) < now) return false;

  const dayMap = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"] as const;
  if (ad.schedule.activeDays?.length) {
    const currentDay = dayMap[now.getDay()];
    if (!ad.schedule.activeDays.includes(currentDay)) return false;
  }

  if (ad.schedule.activeHours) {
    const hour = now.getHours();
    const { start, end } = ad.schedule.activeHours;
    if (start <= end && (hour < start || hour > end)) return false;
    if (start > end && hour < start && hour > end) return false;
  }

  return true;
}

function matchesTargeting(ad: Ad, request: DecisionRequest): boolean {
  const targeting = ad.targeting;
  if (!targeting) return true;

  if (targeting.geo?.countries?.length && request.user.country) {
    if (!targeting.geo.countries.includes(request.user.country)) return false;
  }

  if (targeting.geo?.excludeCountries?.length && request.user.country) {
    if (targeting.geo.excludeCountries.includes(request.user.country)) return false;
  }

  if (targeting.tech?.devices?.length && request.user.deviceType) {
    if (!targeting.tech.devices.includes(request.user.deviceType)) return false;
  }

  if (targeting.tech?.connectionTypes?.length && request.user.connectionType) {
    if (!targeting.tech.connectionTypes.includes(request.user.connectionType)) return false;
  }

  if (targeting.language?.length && request.page.language) {
    if (!targeting.language.some((language) => request.page.language?.startsWith(language))) return false;
  }

  const contextualCategories = targeting.audience?.contextualCategories;
  if (contextualCategories?.length && request.page.categories?.length) {
    const hasContextMatch = contextualCategories.some((category) => request.page.categories?.includes(category));
    if (!hasContextMatch) return false;
  }

  return true;
}

function placementForOpportunity(opportunity: PlacementOpportunity, ad: Ad): AdPlacement {
  return opportunity.placementHints.find((placement) => ad.placements.includes(placement)) ?? opportunity.placementHints[0];
}

function fallbackLayoutForOpportunity(opportunity: PlacementOpportunity): AdLayout {
  if (opportunity.designIntent === "immersive") return "native_feed";
  if (opportunity.designIntent === "compact") return "inline_text";
  return opportunity.surface === "sidebar" ? "medium_rectangle" : "inline_text";
}

function buildDecisionResponse(
  request: DecisionRequest,
  opportunity: PlacementOpportunity,
  ad: Ad | null,
  layout: AdLayout,
  rankScore: number,
  clearingPrice = 0,
  floorApplied = 0,
  experimentAssignments: Record<string, string> = {},
): DecisionResponse {
  const decisionId = `${opportunity.slotId}:${createRuntimeId("dec")}`;
  const auctionId = `${request.requestId}:${opportunity.slotId}`;
  const placement = ad ? placementForOpportunity(opportunity, ad) : opportunity.placementHints[0];
  const creativeId = ad?.creative?.id;

  return {
    slotId: opportunity.slotId,
    requestId: request.requestId,
    decisionId,
    placement,
    layout,
    reservedSize: opportunity.reservedSize,
    rankScore,
    clearingPrice,
    floorApplied,
    tracking: {
      requestId: request.requestId,
      decisionId,
      auctionId,
      creativeId,
      endpoint: "/api/ads/track",
      beaconEndpoint: "/api/ads/track-beacon",
      experimentAssignments,
      impressionKey: `${decisionId}:impression`,
      viewableKey: `${decisionId}:viewable`,
      clickKey: `${decisionId}:click`,
      renderKey: `${decisionId}:render`,
    },
    experimentAssignments,
    ad,
    reason: ad ? "filled" : "no_eligible_ad",
  };
}

export class AdDecisionEngine {
  constructor(
    private readonly repository: CampaignRepository,
    private readonly experimentEngine: ExperimentEngine,
    private readonly frequencyPacingService: FrequencyPacingService,
    private readonly rankingEngine: RankingEngine,
    private readonly auctionEngine: AuctionEngine,
    private readonly layoutResolver: LayoutResolver,
  ) {}

  async decideOne(
    request: DecisionRequest,
    opportunity: PlacementOpportunity,
    history: LayoutDiversityContext & { priorSponsorCounts: Record<string, number> },
  ): Promise<DecisionResponse> {
    const listed = await this.repository.listCandidates(request, opportunity);
    const filtered = [];

    for (const candidate of listed) {
      if (!isScheduleActive(candidate)) continue;
      if (!matchesTargeting(candidate, request)) continue;
      if (!(await this.frequencyPacingService.isEligible(candidate, request, opportunity))) continue;
      filtered.push(candidate);
    }

    const identity = request.user.userId ?? request.user.anonymousId ?? request.user.sessionId ?? request.session.id;
    const { ads: experimentEligibleAds, assignments } = this.experimentEngine.assign(filtered, identity);
    const ranked = this.rankingEngine.rank(experimentEligibleAds, {
      opportunity,
      page: request.page,
      user: request.user,
      client: request.client,
      session: request.session,
      priorSponsorCounts: history.priorSponsorCounts,
      priorLayoutCounts: history.priorLayoutCounts ?? {},
      priorFamilyCounts: history.priorFamilyCounts ?? {},
      priorTemplateCounts: history.priorTemplateCounts ?? {},
    });

    const auction = this.auctionEngine.run(ranked, opportunity);
    const winner = auction.winner;

    if (!winner) {
      return buildDecisionResponse(request, opportunity, null, fallbackLayoutForOpportunity(opportunity), 0, 0, auction.floorApplied, assignments);
    }

    const layoutSelection = this.layoutResolver.resolve(opportunity, winner.ad, request.client, undefined, history);
    return buildDecisionResponse(
      request,
      opportunity,
      winner.ad,
      layoutSelection.layout,
      winner.totalScore,
      auction.clearingPrice,
      auction.floorApplied,
      assignments,
    );
  }

  async decideMany(request: DecisionRequest): Promise<DecisionBatchResponse> {
    const history: LayoutDiversityContext & { priorSponsorCounts: Record<string, number> } = {
      priorSponsorCounts: {},
      priorLayoutCounts: {},
      priorFamilyCounts: {},
      priorTemplateCounts: {},
    };
    const decisions: DecisionResponse[] = [];

    for (const opportunity of request.opportunities) {
      const decision = await this.decideOne(request, opportunity, history);
      decisions.push(decision);
      if (decision.ad?.sponsorName) {
        history.priorSponsorCounts[decision.ad.sponsorName] = (history.priorSponsorCounts[decision.ad.sponsorName] ?? 0) + 1;
      }

      if (decision.ad) {
        const spec = layoutRegistry.getSpec(decision.layout);
        history.priorLayoutCounts = history.priorLayoutCounts ?? {};
        history.priorFamilyCounts = history.priorFamilyCounts ?? {};
        history.priorTemplateCounts = history.priorTemplateCounts ?? {};
        history.priorLayoutCounts[decision.layout] = (history.priorLayoutCounts[decision.layout] ?? 0) + 1;
        history.priorFamilyCounts[spec.family] = (history.priorFamilyCounts[spec.family] ?? 0) + 1;
        history.priorTemplateCounts[spec.template] = (history.priorTemplateCounts[spec.template] ?? 0) + 1;
      }
    }

    return {
      requestId: request.requestId,
      decisions,
      generatedAt: 1700000000000,
    };
  }
}
