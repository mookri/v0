/**
 * Ad Engine v2 — Master Export
 * Single import point for the entire MNC-grade advertising engine.
 *
 * FIXES:
 *   - AdSlot path: was './components/AdSlot' → './AdSlot'
 *   - RoutingStrategy: was from './core/types' → correctly from './core/engine'
 *   - ValidationResult/Error/Warning: single source (./core/validation re-exports from ./core/types)
 *   - FlowStep: single canonical source from ./core/types (not re-exported from flows)
 *   - AdEngine, createAdEngine now exist in ./core/engine
 *   - EngineConfig, FrequencyFunctions now exported from ./core/engine
 *   - scoreAd now exported (was private function)
 *   - AdEngineProvider, AdEngineProviderProps added
 *
 * Usage:
 *   import { bootstrapAdEngine, AdEngineProvider, AdSlot } from 'ad-engine-v2';
 */

// ─────────────────────────────────────────────────────────────────────────────
// Core Types (re-export everything)
// ─────────────────────────────────────────────────────────────────────────────

export type {
  Ad,
  AdStatus,
  AdType,
  AdPlacement,
  AdLayout,
  PricingModel,
  PricingConfig,
  PricingTier,
  AdSchedule,
  DayOfWeek,
  FrequencyProfile,
  FrequencyCap,
  AdTargeting,
  GeoTarget,
  AudienceTarget,
  TechTarget,
  DeviceType,
  ConnectionType,
  OperatingSystem,
  Browser,
  AdEnvironment,
  AdCreative,
  CreativeFormat,
  AdExperiment,
  ExperimentVariant,
  ExperimentType,
  BanditConfig,
  ConstraintGroup,
  AdConstraint,
  ConstraintOperator,
  AdRequest,
  AdResolution,
  AdEvent,
  AdEventType,
  AdErrorCode,
  AdFeatureFlags,
  AdLabel,
  AdapterType,
  AdapterConfig,
  PrebuiltFlowType,
  PrebuiltFlow,
  FlowStep,
  FlowTrigger,
  FlowExitCondition,
  PipelineStage,
  PipelineContext,
  PipelineMiddleware,
  PipelineError,
  ClientHints,
  PageContext,
  UserProfile,
  AuctionContext,
  AdEngineConfig,
  TrackingBatch,
  ValidationResult,
  ValidationError,
  ValidationWarning,
} from './core/types';

// ─────────────────────────────────────────────────────────────────────────────
// Engine
// ─────────────────────────────────────────────────────────────────────────────

export {
  // FIX: AdEngine and createAdEngine now exist (were missing before)
  AdEngine,
  createAdEngine,
  // Pipeline utilities
  runPipeline,
  resolveAd,
  resolveAdAdvanced,
  getRotationPool,
  resolveGridAds,
  // Registration helpers
  registerAds,
  registerFrequencyFns,
  registerVariantFn,
  registerBudgetFn,
  // Routing
  addRoutingRule,
  getRoutingStrategy,
  // Legacy
  getAdForPlacement,
} from './core/engine';

export type {
  // FIX: RoutingStrategy is defined in engine.ts, not types.ts
  RoutingStrategy,
  // FIX: EngineConfig and FrequencyFunctions now exported from engine.ts
  EngineConfig,
  FrequencyFunctions,
  PipelineResult,
  RoutingRule,
} from './core/engine';

// ─────────────────────────────────────────────────────────────────────────────
// Pricing
// ─────────────────────────────────────────────────────────────────────────────

export {
  calculateRevenue,
  recordSpend,
  isWithinBudget,
  getPacingMultiplier,
  calculateECPM,
  calculateViewableCPM,
  formatCTR,
  formatCVR,
  formatROAS,
  formatPrice,
  pricingModelLabel,
  pricingModelCategory,
  applyTierPricing,
  BILLING_EVENTS,
} from './core/pricing';
export type { PacingState } from './core/pricing';

// ─────────────────────────────────────────────────────────────────────────────
// Tracking
// ─────────────────────────────────────────────────────────────────────────────

export {
  track,
  trackImpression,
  trackClick,
  trackViewable,
  trackConversion,
  trackVideoEvent,
  trackEngagement,
  observeViewability,
  recordTouchpoint,
  getAttributionCredits,
  createVideoTracker,
  configureTracking,
  flushQueue,
} from './core/tracking';
export type { AttributionModel, TrackingConfig } from './core/tracking';

// ─────────────────────────────────────────────────────────────────────────────
// Experiments
// ─────────────────────────────────────────────────────────────────────────────

export {
  registerExperiment,
  getExperiment,
  getVariant,
  forceVariant,
  clearVariant,
  getAllVariants,
  updateBanditReward,
  calculateSignificance,
  bayesianAB,
  bayesianABAsync,  // BUG-13 FIX: async version that doesn't block the main thread
} from './core/experiment';
export type { BanditArm, SignificanceResult, BayesianResult } from './core/experiment';

// ─────────────────────────────────────────────────────────────────────────────
// Frequency
// ─────────────────────────────────────────────────────────────────────────────

export {
  getFrequencyCount,
  recordImpression as recordFrequencyImpression,
  checkFrequencyProfile,
  clearFrequency,
  clearAllFrequency,
  isRecencyGated,
  getPlacementImpressionCount,
  recordPlacementImpression,
} from './core/frequency';
export type { FrequencyCheckResult } from './core/frequency';

// ─────────────────────────────────────────────────────────────────────────────
// Validation
// FIX: ValidationResult/Error/Warning exported from ONE source (types.ts via validation.ts)
// FIX: scoreAd now exported (was a private function)
// ─────────────────────────────────────────────────────────────────────────────

export {
  validateAd,
  validateAdRegistry,
  scoreAd,
  evaluateConstraint,
  evaluateConstraintGroup,
} from './core/validation';

// ─────────────────────────────────────────────────────────────────────────────
// Registry
// ─────────────────────────────────────────────────────────────────────────────

export { AdRegistry, defaultRegistry, sampleAds } from './core/registry';

// ─────────────────────────────────────────────────────────────────────────────
// Loader
// ─────────────────────────────────────────────────────────────────────────────

export { AdLoader, defaultLoader } from './core/loader';
export type {
  LoaderConfig, LoadRequest, LoadResult, LoadPriority, LoadStrategy,
} from './core/loader';

// ─────────────────────────────────────────────────────────────────────────────
// Flows
// FIX: FlowStep type comes from ./core/types (canonical), not re-exported from flows.ts
// ─────────────────────────────────────────────────────────────────────────────

export {
  startFlow,
  advanceFlow,
  exitFlow,
  getCurrentFlowStep,
  getEligibleAdIdsForFlow,
  PREBUILT_FLOWS,
} from './flows/flows';
export type { FlowState, FlowDefinition } from './flows/flows';

// ─────────────────────────────────────────────────────────────────────────────
// Adapters
// ─────────────────────────────────────────────────────────────────────────────

export {
  AdapterRegistry,
  defaultAdapterRegistry,
  bootstrapAdapters,
  BaseAdapter,
  GoogleAdManagerAdapter,
  MetaAudienceNetworkAdapter,
  AmazonAPSAdapter,
  PrebidAdapter,
  OpenRTBAdapter,
  TheTradeDeskAdapter,
  XandrAdapter,
  DV360Adapter,
  VASTVPAIDAdapter,
  AMPAdapter,
  SegmentAdapter,
  mParticleAdapter,
  RudderstackAdapter,
  ShopifyAdapter,
  WordPressAdapter,
  ReactNativeAdapter,
  NextJSAdapter,
  NuxtAdapter,
  SvelteKitAdapter,
} from './adapters/adapters';
export type {
  AdapterBidRequest,
  AdapterBidResponse,
  AdapterEventPayload,
  AuctionConfig,
  AuctionStrategy,
  PrebidBidderConfig,
} from './adapters/adapters';

// ─────────────────────────────────────────────────────────────────────────────
// Automation
// ─────────────────────────────────────────────────────────────────────────────

export {
  AutomationEngine,
  CSPSolver,
  ConstraintSolver,
  BudgetAutomator,
  CreativeRotator,
  TemplateLibrary,
} from './automation/automation';
export type {
  AutomationRule,
  AutomationTrigger,
  AutomationTriggerType,
  AutomationAction,
  AutomationActionType,
  AutomationCondition,
  AutomationContext,
  AutomationActionExecutor,
  CSPVariable,
  CSPConstraintDef,
  CSPSolution,
  BudgetAutomationConfig,
  CreativeVariant,
  RotationStrategy,
  AdTemplate,
} from './automation/automation';

// ─────────────────────────────────────────────────────────────────────────────
// React Components
// FIX: path was './components/AdSlot' → './AdSlot'
// FIX: added AdEngineProvider, AdEngineProviderProps
// ─────────────────────────────────────────────────────────────────────────────

export {
  AdSlot,
  AdGrid,
  AdStrip,
  AdInterstitial,
  AdEngineProvider,
  useAdEngine,
} from './AdSlot';
export type {
  AdSlotProps,
  AdSlotState,
  AdGridProps,
  AdStripProps,
  AdInterstitialProps,
  AdEngineProviderProps,
} from './AdSlot';

// ─────────────────────────────────────────────────────────────────────────────
// Layout Components (pre-built, design-system-ready)
// ─────────────────────────────────────────────────────────────────────────────

export { default as HeroAd } from './HeroAd';
export { InlineTextAd, CardGridAd, NativeFeedAd as NativeAdCard } from './NativeAds';
export { LeaderboardAd, BillboardAd, MediumRectangleAd } from './RectangularAds';
export { SquareAd, HalfPageAd, WideSkyscraperAd, StoryCardAd } from './VerticalAds';

// ─────────────────────────────────────────────────────────────────────────────
// Convenience factory — bootstrapAdEngine()
// ─────────────────────────────────────────────────────────────────────────────

import { AdEngine, createAdEngine, type EngineConfig } from './core/engine';
import { defaultRegistry } from './core/registry';
import { defaultLoader } from './core/loader';
import { AutomationEngine } from './automation/automation';
import { AdapterRegistry } from './adapters/adapters';

export interface AdEngineBundle {
  engine: AdEngine;
  registry: typeof defaultRegistry;
  loader: typeof defaultLoader;
  automation: AutomationEngine;
  adapters: AdapterRegistry;
}

/**
 * One-call bootstrap — returns a fully configured engine bundle.
 *
 * @example
 * // In your app providers (once):
 * const { engine } = bootstrapAdEngine({ minQualityScore: 40 });
 *
 * // Wrap your tree:
 * <AdEngineProvider engine={engine}>
 *   <App />
 * </AdEngineProvider>
 *
 * // Then anywhere:
 * <AdSlot placement="article_mid" />
 */
export function bootstrapAdEngine(config?: Partial<EngineConfig>): AdEngineBundle {
  const registry = defaultRegistry;
  const engine = createAdEngine(registry.getAll(), config);
  const loader = defaultLoader;
  const automation = new AutomationEngine();
  AutomationEngine.buildStandardRules().forEach(r => automation.addRule(r));
  const adapters = new AdapterRegistry();
  return { engine, registry, loader, automation, adapters };
}
