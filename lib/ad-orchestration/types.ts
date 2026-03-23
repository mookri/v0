import type {
  Ad,
  AdLayout,
  AdPlacement,
  AdEventType,
  PageContext,
  UserProfile,
  ClientHints,
  PricingModel,
} from "@/components/ad-network/core/types";

export type OpportunitySurface = "article" | "sidebar" | "feed" | "hero";
export type DepthBucket = "above_fold" | "mid" | "deep";
export type ContentBlockType = "heading" | "paragraph" | "media" | "list" | "cta";
export type OpportunityDesignIntent = "editorial" | "trust" | "compact" | "immersive";

export interface ReservedSize {
  width: number | "fluid";
  height: number;
  aspectRatio?: string;
}

export interface StructuredContentBlock {
  id: string;
  type: ContentBlockType;
  html: string;
  text: string;
  wordCount: number;
  paragraphIndex: number;
  headingLevel?: 2 | 3;
  isAfterHeading: boolean;
}

export interface TocItem {
  level: number;
  text: string;
  id: string;
}

export interface PlacementOpportunity {
  slotId: string;
  surface: OpportunitySurface;
  placementHints: AdPlacement[];
  anchorId: string;
  anchorIndex: number;
  reservedSize: ReservedSize;
  depthBucket: DepthBucket;
  lazy: boolean;
  designIntent?: OpportunityDesignIntent;
  preferredFamilies?: LayoutSpec["family"][];
  preferredTemplates?: LayoutSpec["template"][];
  score?: number;
  paragraphIndex?: number;
}

export interface PlacementPlan {
  blocks: StructuredContentBlock[];
  toc: TocItem[];
  opportunities: PlacementOpportunity[];
  wordCount: number;
}

export interface DecisionSession {
  id: string;
  engagementScore: number;
  scrollDepth: number;
}

export interface DecisionRequest {
  requestId: string;
  opportunities: PlacementOpportunity[];
  page: PageContext;
  user: UserProfile;
  client: ClientHints;
  session: DecisionSession;
}

export interface TrackingDescriptor {
  requestId: string;
  decisionId: string;
  auctionId: string;
  creativeId?: string;
  endpoint: string;
  beaconEndpoint: string;
  experimentAssignments: Record<string, string>;
  impressionKey: string;
  viewableKey: string;
  clickKey: string;
  renderKey: string;
}

export interface DecisionResponse {
  slotId: string;
  requestId: string;
  decisionId: string;
  placement: AdPlacement;
  layout: AdLayout;
  reservedSize: ReservedSize;
  tracking: TrackingDescriptor;
  experimentAssignments: Record<string, string>;
  ad: Ad | null;
  reason?: string;
  rankScore?: number;
  clearingPrice?: number;
  floorApplied?: number;
}

export interface DecisionBatchResponse {
  requestId: string;
  decisions: DecisionResponse[];
  generatedAt: number;
}

export interface RankedCandidate {
  ad: Ad;
  predictedCtr: number;
  predictedCvr: number;
  relevance: number;
  qualityScore: number;
  revenueEcpm: number;
  uxPenalty: number;
  totalScore: number;
}

export interface LayoutSpec {
  layout: AdLayout;
  category:
    | "hero"
    | "native"
    | "card"
    | "display"
    | "video"
    | "interactive"
    | "fallback";
  family:
    | "hero"
    | "native"
    | "display"
    | "sidebar"
    | "story"
    | "video"
    | "interactive"
    | "carousel"
    | "sponsored"
    | "commerce"
    | "email"
    | "fallback";
  orientation: "horizontal" | "vertical" | "square" | "fluid";
  template:
    | "hero"
    | "poster"
    | "split"
    | "ribbon"
    | "story"
    | "video"
    | "carousel"
    | "interactive"
    | "ticker";
  density: "compact" | "standard" | "immersive";
  reservedSize: ReservedSize;
  preferredPlacements: AdPlacement[];
  lowBandwidthSafe: boolean;
  requiresImage: boolean;
  requiresVideo: boolean;
  minViewportWidth?: number;
  fallbackOrder: AdLayout[];
}

export interface LayoutSelection {
  layout: AdLayout;
  spec: LayoutSpec;
  reason: string;
}

export interface AuctionCandidate {
  ranked: RankedCandidate;
  pricingModel: PricingModel | undefined;
  effectiveBid: number;
  shadedBid: number;
  floor: number;
  priorityTier: "guaranteed" | "preferred" | "auction";
}

export interface AuctionOutcome {
  winner: RankedCandidate | null;
  auctionId: string;
  clearingPrice: number;
  floorApplied: number;
  auctionType: "first_price" | "second_price" | "hybrid" | "preferred" | "guaranteed" | "none";
  debug: {
    candidates: Array<{
      adId: string;
      effectiveBid: number;
      shadedBid: number;
      totalScore: number;
      pricingModel?: PricingModel;
      priorityTier: AuctionCandidate["priorityTier"];
    }>;
  };
}

export interface RankingWeights {
  revenue: number;
  ctr: number;
  relevance: number;
  quality: number;
  uxPenalty: number;
}

export interface RankingContext {
  opportunity: PlacementOpportunity;
  page: PageContext;
  user: UserProfile;
  client: ClientHints;
  session: DecisionSession;
  priorSponsorCounts: Record<string, number>;
  priorLayoutCounts: Partial<Record<AdLayout, number>>;
  priorFamilyCounts: Partial<Record<LayoutSpec["family"], number>>;
  priorTemplateCounts: Partial<Record<LayoutSpec["template"], number>>;
}

export interface LayoutDiversityContext {
  priorLayoutCounts?: Partial<Record<AdLayout, number>>;
  priorFamilyCounts?: Partial<Record<LayoutSpec["family"], number>>;
  priorTemplateCounts?: Partial<Record<LayoutSpec["template"], number>>;
}

export interface TrackingEventPayload {
  type: AdEventType | "render";
  requestId: string;
  decisionId: string;
  auctionId: string;
  adId: string;
  creativeId?: string;
  placement: AdPlacement;
  layout: AdLayout;
  timestamp: number;
  batchId?: string;
  idempotencyKey: string;
  experimentAssignments: Record<string, string>;
  scrollDepth?: number;
  viewabilityRatio?: number;
  viewabilityMs?: number;
  connectionType?: string;
  customData?: Record<string, unknown>;
}

export interface TrackingBatchPayload {
  batchId: string;
  events: TrackingEventPayload[];
  sentAt: number;
  source: "queue" | "beacon" | "fetch";
}

export interface CampaignRepository {
  listCandidates(request: DecisionRequest, opportunity: PlacementOpportunity): Promise<Ad[]>;
  getById(adId: string): Promise<Ad | null>;
  getAll(): Promise<Ad[]>;
}

export interface FrequencyStore {
  getCount(key: string, windowMs: number): Promise<number>;
  increment(key: string, ttlMs: number): Promise<void>;
  getLastSeen(key: string): Promise<number | null>;
  rememberIdempotencyKey(key: string, ttlMs: number): Promise<boolean>;
}

export interface BudgetLedger {
  getSpent(adId: string, scope: "lifetime" | "daily" | "hourly"): Promise<number>;
  addSpend(adId: string, amount: number): Promise<void>;
}

export interface EventSink {
  record(batch: TrackingBatchPayload): Promise<{ accepted: number; duplicates: number }>;
}
