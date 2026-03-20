//components/ad-network/core/types.ts
/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  types.ts
   2027-ready · MNC-grade · 150+ layouts · 50+ pricing models · Backend-scalable
   All monetary values in smallest currency unit (paise / cents).
   ═══════════════════════════════════════════════════════════════════════════════ */

// ────────────────────────────────────────────────────────────────────────────
// 1. PLACEMENT (50+)
// ────────────────────────────────────────────────────────────────────────────

export type AdPlacement =
  // Article placements
  | "article_top" | "article_mid" | "article_bottom" | "article_sticky"
  | "article_parallax" | "article_pre_headline" | "article_post_headline"
  | "article_inline_1" | "article_inline_2" | "article_inline_3"
  // Sidebar placements
  | "sidebar_top" | "sidebar_sticky" | "sidebar_mid" | "sidebar_bottom"
  | "sidebar_rail_left" | "sidebar_rail_right"
  // Feed / stream placements
  | "feed_item" | "feed_top" | "feed_mid" | "feed_bottom"
  | "feed_native_1" | "feed_native_2" | "feed_sponsored_story"
  | "feed_video_inline" | "feed_carousel"
  // Homepage placements
  | "homepage_hero" | "homepage_takeover" | "homepage_banner"
  | "homepage_below_fold" | "homepage_exit_intent"
  // Search placements
  | "search_results" | "search_top" | "search_right" | "search_sponsored"
  // Video placements
  | "pre_roll" | "mid_roll" | "post_roll" | "overlay" | "companion_banner"
  | "outstream_video" | "in_stream_pause"
  // Interstitial / fullscreen
  | "interstitial" | "rewarded" | "splash_screen" | "modal_center"
  | "modal_bottom_sheet" | "exit_intent_popup"
  // Navigation placements
  | "nav_top" | "nav_bottom" | "sticky_footer" | "sticky_header"
  | "floating_cta" | "docked_banner"
  // App placements
  | "app_open" | "app_banner_top" | "app_banner_bottom"
  | "app_interstitial" | "app_rewarded" | "app_native_grid"
  // Email placements
  | "email_header" | "email_footer" | "email_inline" | "email_sidebar"
  // CTV / OTT
  | "ctv_pre_roll" | "ctv_mid_roll" | "ctv_pause_ad" | "ctv_l_banner"
  // DOOH (Digital Out of Home)
  | "dooh_billboard" | "dooh_transit" | "dooh_retail" | "dooh_cinema";

// ────────────────────────────────────────────────────────────────────────────
// 2. LAYOUT (150+)
// ────────────────────────────────────────────────────────────────────────────

export type AdLayout =
  // ── IAB Standard Display ─────────────────────────────────────────────────
  | "leaderboard"           // 728×90
  | "billboard"             // 970×250
  | "medium_rectangle"      // 300×250
  | "half_page"             // 300×600
  | "wide_skyscraper"       // 160×600
  | "skyscraper"            // 120×600
  | "square"                // 250×250
  | "small_square"          // 200×200
  | "large_rectangle"       // 336×280
  | "banner"                // 468×60
  | "micro_bar"             // 88×31
  | "button_1"              // 120×90
  | "button_2"              // 120×60
  | "large_leaderboard"     // 970×90
  | "panorama"              // 980×120
  | "triple_widescreen"     // 50×700
  | "portrait"              // 300×1050
  | "netboard"              // 580×400
  | "top_banner"            // 930×180
  | "wallpaper"             // 1800×1000 (split)
  // ── Hero / Takeover ──────────────────────────────────────────────────────
  | "hero"                  // Full editorial bleed
  | "hero_split"            // 50/50 text + image
  | "hero_centered"         // Centered CTA overlay
  | "hero_minimal"          // Text only, no image
  | "hero_cinematic"        // Full-screen 16:9 video/image
  | "hero_mosaic"           // Multi-image grid hero
  | "hero_countdown"        // Timer countdown hero
  | "hero_parallax"         // Parallax scroll effect
  | "hero_gradient"         // Gradient background no image
  | "hero_glassmorphic"     // Frosted glass aesthetic
  // ── Card Variants ─────────────────────────────────────────────────────────
  | "card_grid"             // 2–4 col tiles
  | "card_list"             // Vertical list cards
  | "card_horizontal"       // Side-by-side image+text
  | "card_minimal"          // Title + CTA only
  | "card_rich"             // Image + rating + price
  | "card_product"          // E-commerce product card
  | "card_profile"          // Avatar + bio card
  | "card_event"            // Date + location card
  | "card_stat"             // Big number + label
  | "card_comparison"       // A vs B
  | "card_testimonial"      // Quote + avatar
  | "card_video_thumb"      // Video thumbnail + play btn
  | "card_article"          // Headline + excerpt + meta
  | "card_deal"             // Price + savings + timer
  | "card_app_install"      // App icon + rating + install
  | "card_recipe"           // Food card with macros
  | "card_job"              // Job listing card
  | "card_location"         // Map + venue card
  | "card_poll"             // Interactive poll card
  | "card_quiz"             // Quiz/trivia card
  // ── Native Variants ───────────────────────────────────────────────────────
  | "inline_text"           // Pure text, no image
  | "native_article"        // Article-styled sponsored content
  | "native_feed"           // Blends into content feed
  | "native_search"         // Search result style
  | "native_recommendation" // Content recommendation row
  | "native_product_list"   // Shoppable product list
  | "native_in_content"     // Inline within body text
  | "native_promoted_story" // Promoted story unit
  | "native_ad_choice"      // AdChoice-compliant unit
  // ── Story / Vertical ─────────────────────────────────────────────────────
  | "story_card"            // 9:16 social story
  | "story_swipeable"       // Multi-frame swipeable
  | "story_interactive"     // Poll/quiz inside story
  | "story_link_sticker"    // Story with link sticker
  | "reel_overlay"          // Short video overlay
  | "vertical_video"        // Full 9:16 video
  // ── Video ─────────────────────────────────────────────────────────────────
  | "video_pre_roll"        // 15s / 30s pre-roll
  | "video_mid_roll"        // Mid-content video
  | "video_bumper"          // 6s non-skippable
  | "video_skippable"       // Skip after 5s
  | "video_outstream"       // Outstream autoplay
  | "video_rewarded"        // Watch to earn
  | "video_companion"       // Video + banner combo
  | "video_interactive"     // Choose-your-path video
  | "video_360"             // VR/360 video unit
  | "video_live"            // Live stream sponsored
  | "video_overlay_l"       // L-shaped overlay
  | "video_thumbnail_list"  // Thumbnail playlist ad
  // ── Interactive / Rich Media ──────────────────────────────────────────────
  | "interactive_quiz"      // Multi-step quiz
  | "interactive_calculator"// ROI / savings calculator
  | "interactive_poll"      // Vote + results
  | "interactive_slider"    // Before/after slider
  | "interactive_scratch"   // Scratch card reveal
  | "interactive_spin"      // Spin wheel
  | "interactive_ar"        // AR try-on preview
  | "interactive_map"       // Embedded map unit
  | "interactive_chat"      // Conversational ad
  | "interactive_form"      // Lead gen form
  | "interactive_countdown" // Countdown to event/sale
  | "interactive_rating"    // Star rating input
  | "interactive_hotspot"   // Image with clickable hotspots
  // ── Carousel / Multi-frame ────────────────────────────────────────────────
  | "carousel_standard"     // Auto/manual image carousel
  | "carousel_product"      // Shoppable carousel
  | "carousel_story"        // Story-style carousel
  | "carousel_video"        // Video carousel
  | "carousel_mixed"        // Mix of images + video
  | "carousel_cinemagraph"  // Subtle looping motion
  // ── Expandable / Overlay ──────────────────────────────────────────────────
  | "expandable_banner"     // Collapses after expand
  | "expandable_half_page"  // Expands to fullscreen
  | "lightbox"              // Click-to-expand modal
  | "pushdown"              // Pushes content down
  | "toproll"               // Peels/rolls down from top
  | "interstitial_full"     // Full-screen between pages
  | "interstitial_partial"  // Partial overlay
  | "sticky_footer"         // Anchored bottom bar
  | "sticky_header"         // Anchored top bar
  | "floating_box"          // Draggable floating unit
  // ── Programmatic / RTB ────────────────────────────────────────────────────
  | "programmatic_display"  // Generic programmatic
  | "programmatic_native"   // Native programmatic
  | "header_bidding"        // Prebid / header bidding
  | "open_rtb"              // OpenRTB 2.x
  | "private_marketplace"   // PMP deal
  | "preferred_deal"        // Preferred (non-auction)
  | "guaranteed"            // Programmatic guaranteed
  | "contextual_display"    // Content-matched display
  // ── Sponsored Content ─────────────────────────────────────────────────────
  | "sponsored_article"     // Full native article
  | "sponsored_section"     // Branded content hub
  | "sponsored_newsletter"  // Email newsletter sponsor
  | "sponsored_podcast"     // Podcast mid-roll text
  | "sponsored_event"       // Event listing sponsor
  | "sponsored_glossary"    // Knowledge base sponsor
  // ── E-Commerce / Shopping ─────────────────────────────────────────────────
  | "product_showcase"      // Rich product display
  | "dynamic_product"       // Personalised feed-based
  | "shoppable_image"       // Tag products in image
  | "shoppable_video"       // Tag products in video
  | "price_comparison"      // Compare prices
  | "cashback_offer"        // Cashback highlight
  | "flash_sale"            // Limited-time sale
  | "bundle_offer"          // Product bundle
  // ── App Install / Engagement ──────────────────────────────────────────────
  | "app_install_banner"    // App icon + install btn
  | "app_install_interstitial" // Full-screen app install
  | "app_engagement"        // Deep-link to app section
  | "smart_app_banner"      // iOS/Android smart banner
  // ── CTV / DOOH / OOH ─────────────────────────────────────────────────────
  | "ctv_display"           // CTV companion
  | "ctv_interactive"       // QR code CTV
  | "dooh_static"           // Static DOOH
  | "dooh_dynamic"          // Dynamic DOOH (dayparted)
  | "dooh_trigger"          // Weather/event-triggered
  // ── Email ──────────────────────────────────────────────────────────────────
  | "email_masthead"        // Top-of-email banner
  | "email_mid_article"     // Mid-email native
  | "email_footer_strip"    // Footer sponsorship
  | "email_product_block"   // Inline product block
  // ── Accessibility / Fallback ─────────────────────────────────────────────
  | "text_only"             // Pure text, no imagery
  | "accessible_rich"       // WCAG 2.2 AA compliant rich
  | "low_bandwidth"         // SVG/text for slow connections
  | "fallback_placeholder"; // Loading/error placeholder

// ────────────────────────────────────────────────────────────────────────────
// 3. PRICING MODELS (50+)
// ────────────────────────────────────────────────────────────────────────────

export type PricingModel =
  // ── Classic ──────────────────────────────────────────────────────────────
  | "CPM"          // Cost per 1,000 impressions
  | "CPC"          // Cost per click
  | "CPV"          // Cost per view (video)
  | "CPA"          // Cost per acquisition/conversion
  | "CPL"          // Cost per lead
  | "CPI"          // Cost per install (mobile apps)
  | "CPE"          // Cost per engagement
  | "CPD"          // Cost per day
  | "CPT"          // Cost per thousand (synonym CPM)
  | "flat_rate"    // Fixed fee regardless of performance
  // ── Viewability-based ────────────────────────────────────────────────────
  | "vCPM"         // Viewable CPM (IAB viewability standard)
  | "CPCV"         // Cost per completed video view
  | "CPF"          // Cost per fold (above-the-fold only)
  | "AVOC"         // Audible & Viewable on Complete
  // ── Outcome-based ─────────────────────────────────────────────────────────
  | "ROAS"         // Return on ad spend (billed as % of generated revenue)
  | "CPS"          // Cost per sale
  | "CPO"          // Cost per order
  | "CPR"          // Cost per registration
  | "CPTM"         // Cost per targeted thousand
  | "CPQ"          // Cost per qualified lead
  | "CPC_enhanced" // Enhanced CPC (auto-adjusted for conversions)
  | "smart_cpa"    // ML-optimised target CPA
  | "target_roas"  // Algorithmic ROAS bidding
  | "max_conversions" // Maximise conversions in budget
  // ── Auction / Programmatic ────────────────────────────────────────────────
  | "first_price_auction"   // Highest bid wins, pays their bid
  | "second_price_auction"  // Vickrey — pays next bid + ε
  | "header_bidding_cpm"    // Prebid unified auction CPM
  | "open_rtb_cpm"          // OpenRTB auction CPM
  | "private_deal_cpm"      // PMP deal floor CPM
  | "preferred_deal_cpm"    // Non-auction preferred rate
  | "programmatic_guaranteed_cpm" // PG committed CPM
  | "dynamic_floor"         // Floor changes with inventory quality
  | "yield_optimised"       // SSP yield-optimised pricing
  // ── Share-based ───────────────────────────────────────────────────────────
  | "revenue_share"         // % of publisher revenue
  | "profit_share"          // % of publisher profit
  | "gross_margin_share"    // % of gross margin
  // ── Time-based ────────────────────────────────────────────────────────────
  | "CPH"          // Cost per hour (display time)
  | "tenancy_day"  // Daily tenancy rate
  | "tenancy_week" // Weekly tenancy rate
  | "tenancy_month"// Monthly tenancy rate
  | "burst_rate"   // Surge pricing for peak traffic
  // ── Hybrid / Blended ──────────────────────────────────────────────────────
  | "hybrid_cpm_cpc"        // Base CPM + performance CPC bonus
  | "hybrid_cpa_cpv"        // Video view + CPA conversion
  | "stepped_cpm"           // CPM decreases as volume grows
  | "tiered_cpc"            // CPC varies by quality score
  | "bid_shading"           // First-price with bid shading
  // ── Subscription / SaaS Style ─────────────────────────────────────────────
  | "subscription_monthly"  // Recurring monthly placement fee
  | "subscription_annual"   // Annual contract rate
  | "credit_bundle"         // Pre-purchased impression credits
  | "prepaid_credits"       // Credit wallet, deducted per event
  // ── Performance Plus ──────────────────────────────────────────────────────
  | "pay_for_results"       // Billed only on verified outcomes
  | "cost_per_attention"    // Eye-tracking verified attention
  | "cost_per_memory"       // Post-campaign brand recall
  | "guaranteed_outcomes";  // Platform guarantees KPI or refunds

export interface PricingTier {
  minVolume: number;           // Impressions / events threshold
  unitCost: number;            // Price per unit at this tier
}

export interface PricingConfig {
  model: PricingModel;
  bidFloor: number;            // Minimum acceptable price
  bidCeiling?: number;         // Hard max bid
  totalBudget?: number;        // Campaign lifetime cap
  dailyCap?: number;           // Daily spend cap
  hourlyCap?: number;          // Hourly spend cap
  weeklyCap?: number;          // Weekly spend cap
  tiers?: PricingTier[];       // Volume discount tiers
  revenueSharePct?: number;    // For revenue_share model (0–1)
  targetKpi?: number;          // For smart_cpa / target_roas
  bidShadeAlpha?: number;      // Bid shading aggressiveness (0–1)
  floorMultiplier?: number;    // Dynamic floor = floor × quality_score
  currencyCode?: string;       // ISO 4217 (default "INR")
  taxIncluded?: boolean;
  viewabilityMinPct?: number;  // Minimum viewability for vCPM billing (0–1)
  completionMinPct?: number;   // Minimum video completion for CPCV (0–1)
}

// ────────────────────────────────────────────────────────────────────────────
// 4. SCHEDULING
// ────────────────────────────────────────────────────────────────────────────

export type DayOfWeek = "mon" | "tue" | "wed" | "thu" | "fri" | "sat" | "sun";

export interface AdSchedule {
  startDate?: string;                      // ISO 8601
  endDate?: string;                        // ISO 8601
  timezone?: string;                       // IANA timezone id
  activeDays?: DayOfWeek[];
  activeHours?: { start: number; end: number };
  blackoutDates?: string[];                // ISO dates to skip
  daypartingMatrix?: boolean[][];          // 7 rows × 24 cols (day × hour)
  flightIntervalDays?: number;             // Alternate flight cadence
  recurrenceRule?: string;                 // iCal RRULE for complex schedules
  priority?: number;                       // Higher = preferred in same slot
  paceEvenly?: boolean;                    // Budget pacing: even vs front-loaded
  pacingSpeed?: "slow" | "standard" | "accelerated";
}

// ────────────────────────────────────────────────────────────────────────────
// 5. FREQUENCY CONTROL
// ────────────────────────────────────────────────────────────────────────────

export interface FrequencyCap {
  maxImpressions: number;
  windowSeconds: number;
  scope?: "session" | "user" | "device" | "ip";  // Granularity
}

export interface FrequencyProfile {
  hourly?: FrequencyCap;
  daily?: FrequencyCap;
  weekly?: FrequencyCap;
  lifetime?: FrequencyCap;
  postClick?: FrequencyCap;           // After user clicks, cap further impressions
  recencyMinutes?: number;            // Min minutes between any 2 impressions
}

// ────────────────────────────────────────────────────────────────────────────
// 6. TARGETING
// ────────────────────────────────────────────────────────────────────────────

export type DeviceType = "mobile" | "tablet" | "desktop" | "ctv" | "dooh" | "wearable";
export type ConnectionType = "wifi" | "4g" | "5g" | "3g" | "2g" | "ethernet" | "offline";
export type OperatingSystem = "ios" | "android" | "windows" | "macos" | "linux" | "chromeos" | "tizen" | "webos";
export type Browser = "chrome" | "safari" | "firefox" | "edge" | "opera" | "samsung" | "other";
export type AdEnvironment = "web" | "app" | "amp" | "email" | "ctv" | "dooh" | "audio";

export interface GeoTarget {
  countries?: string[];                  // ISO 3166-1 alpha-2
  regions?: string[];                    // State / province codes
  cities?: string[];                     // City names or IDs
  postalCodes?: string[];
  dmaRegions?: string[];                 // Nielsen DMA codes
  radiusKm?: { lat: number; lng: number; km: number }[];
  excludeCountries?: string[];
  excludeRegions?: string[];
  excludeCities?: string[];
}

export interface AudienceTarget {
  segments?: string[];                   // First-party segment IDs
  thirdPartySegments?: string[];         // Third-party DMP segments
  lookalike?: string[];                  // Seed audience IDs for lookalike
  retargeting?: { pixelId: string; windowDays: number };
  contextualKeywords?: string[];
  contextualCategories?: string[];       // IAB content taxonomy
  intentKeywords?: string[];
  brandSafetyCategories?: string[];      // Block list categories
  emotionalTone?: ("positive" | "neutral")[];  // Brand safety sentiment
  excludeSegments?: string[];
}

export interface TechTarget {
  devices?: DeviceType[];
  operatingSystems?: OperatingSystem[];
  browsers?: Browser[];
  connectionTypes?: ConnectionType[];
  environments?: AdEnvironment[];
  minOsVersion?: string;
  minBrowserVersion?: string;
  requireJavascript?: boolean;
  requireCookies?: boolean;
  requireLocalStorage?: boolean;
}

export interface AdTargeting {
  geo?: GeoTarget;
  audience?: AudienceTarget;
  tech?: TechTarget;
  /** @deprecated Use geo.countries */
  geoCountries?: string[];
  /** @deprecated Use tech.devices */
  devices?: DeviceType[];
  /** @deprecated Use audience.segments */
  segments?: string[];
  keywords?: string[];
  language?: string[];                   // ISO 639-1 codes
  timeOfDay?: { start: number; end: number };  // Peak hours
  incomeLevel?: ("low" | "mid" | "high" | "premium")[];
  intentScore?: { min: number; max: number };  // 0–100
  customTargeting?: Record<string, string | string[]>;  // KV pairs
}

// ────────────────────────────────────────────────────────────────────────────
// 7. EXPERIMENT / A/B/N TESTING
// ────────────────────────────────────────────────────────────────────────────

export type ExperimentVariant = string;    // Any label: "A" | "B" | "control" | "challenger_1"

export type ExperimentType =
  | "ab"             // Classic two-variant
  | "abn"            // Multi-variant (3+)
  | "multivariate"   // Multiple element combinations
  | "bandit"         // Multi-armed bandit (auto-optimise)
  | "bayesian"       // Bayesian A/B (continuous updating)
  | "sequential"     // Sequential probability ratio test
  | "holdout"        // Global holdout group
  | "causal"         // Causal inference / diff-in-diff
  | "interleaving";  // Team draft interleaving for ranking

export interface BanditConfig {
  algorithm: "epsilon_greedy" | "ucb1" | "thompson_sampling" | "exp3";
  epsilon?: number;             // For epsilon-greedy (0–1)
  warmupImpressions?: number;   // Explore for N impressions before exploiting
  decayFactor?: number;         // Reward decay over time
  rewardSignal: "ctr" | "cvr" | "revenue" | "engagement";
}

export interface AdExperiment {
  key: string;
  type: ExperimentType;
  variant: ExperimentVariant;
  trafficAllocation: number;    // 0–1
  bandit?: BanditConfig;
  layerKey?: string;            // Experiment layering namespace
  mutuallyExclusive?: string[]; // Keys of experiments that cannot co-run
  holdbackPct?: number;         // % of traffic held back for baseline
  minDetectableEffect?: number; // Minimum detectable lift to reach significance
  confidenceLevel?: number;     // Default 0.95
  onesided?: boolean;           // One vs two-tailed test
  guardrailMetrics?: string[];  // Secondary metrics to watch
}

// ────────────────────────────────────────────────────────────────────────────
// 8. CREATIVE
// ────────────────────────────────────────────────────────────────────────────

export type CreativeFormat =
  | "image" | "video" | "html5" | "amp_html" | "native" | "text"
  | "audio" | "svg" | "css_animation" | "webgl" | "vpaid" | "vast";

export interface AdCreative {
  id?: string;
  format?: CreativeFormat;
  imageUrl?: string;
  imageUrlHiDpi?: string;           // 2x retina
  imageUrlWebp?: string;            // Modern format
  videoUrl?: string;
  videoUrlHls?: string;             // Adaptive streaming
  videoPosterUrl?: string;
  videoSkippableAfterMs?: number;
  videoDurationMs?: number;
  audioUrl?: string;
  html5BundleUrl?: string;          // ZIP of HTML5 creative
  ampHtmlUrl?: string;
  logoUrl?: string;
  iconUrl?: string;                 // 1:1 brand icon
  bgColor?: string;                 // Hex fallback
  bgGradient?: string;              // CSS gradient string
  accentColor?: string;             // For CTA button
  textColor?: string;
  alt?: string;
  caption?: string;
  thumbnails?: string[];            // Multiple carousel frames
  aspectRatio?: string;             // "16:9" | "4:3" | "1:1" | "9:16"
  mimeType?: string;
  fileSize?: number;                // Bytes
  pixelDensity?: number;            // For DPI-aware serving
  safeArea?: { top: number; bottom: number; left: number; right: number };
  richMediaUrl?: string;            // VPAID / HTML5 rich media
  trackingScripts?: string[];       // Third-party pixel scripts (as URLs)
}

// ────────────────────────────────────────────────────────────────────────────
// 9. CONSTRAINT SYSTEM
// ────────────────────────────────────────────────────────────────────────────

export type ConstraintOperator =
  | "eq" | "neq" | "gt" | "gte" | "lt" | "lte"
  | "in" | "not_in" | "contains" | "not_contains"
  | "matches_regex" | "between" | "is_null" | "is_not_null";

export interface AdConstraint {
  field: string;
  operator: ConstraintOperator;
  value: unknown;
  weight?: number;              // Soft constraint weight (0 = hard)
  errorCode?: string;
  errorMessage?: string;
}

export interface ConstraintGroup {
  id: string;
  logic: "AND" | "OR" | "NOT";
  constraints: (AdConstraint | ConstraintGroup)[];
  required?: boolean;           // Hard vs soft
}

// ────────────────────────────────────────────────────────────────────────────
// 10. PIPELINE STAGE HOOKS
// ────────────────────────────────────────────────────────────────────────────

export type PipelineStage =
  | "auth"              // Stage 1: Authentication / API key validation
  | "rate_limit"        // Stage 2: Rate limiting & throttle
  | "validation"        // Stage 3: Schema & constraint validation
  | "placement_filter"  // Stage 4: Match placement + layout
  | "status_filter"     // Stage 5: Active/paused/scheduled check
  | "schedule_filter"   // Stage 6: Dayparting / date range
  | "targeting_filter"  // Stage 7: Geo / device / segment targeting
  | "budget_gate"       // Stage 8: Budget pacing & cap enforcement
  | "frequency_gate"    // Stage 9: Frequency cap check
  | "experiment_resolve"// Stage 10: A/B/bandit variant assignment
  | "constraint_satisfy"// Stage 11: CSP constraint satisfaction
  | "quality_score"     // Stage 12: Relevance + quality scoring
  | "auction"           // Stage 13: Auction / bid resolution
  | "weighted_select"   // Stage 14: Weighted random pick
  | "post_select";      // Stage 15: Post-selection hooks (tracking, logging)

export interface PipelineContext {
  stage: PipelineStage;
  requestId: string;
  startedAt: number;
  metadata: Record<string, unknown>;
}

export type PipelineMiddleware = (
  ads: Ad[],
  req: AdRequest,
  ctx: PipelineContext,
  next: (ads: Ad[]) => Promise<AdResolution>
) => Promise<AdResolution>;

// ────────────────────────────────────────────────────────────────────────────
// 11. AD REQUEST / RESOLUTION
// ────────────────────────────────────────────────────────────────────────────

export interface AdRequest {
  requestId?: string;
  placement: AdPlacement;
  layout?: AdLayout;  // optional — engine selects best layout when omitted
  columns?: number;                   // For grid layouts
  config?: AdEngineConfig;
  constraints?: ConstraintGroup[];
  clientHints?: ClientHints;
  pageContext?: PageContext;
  userProfile?: UserProfile;
  auctionContext?: AuctionContext;
  timestamp?: number;
  timeout?: number;                   // Max resolve time in ms
}

export interface ClientHints {
  ua?: string;
  uaArch?: string;
  uaBitness?: string;
  uaModel?: string;
  uaPlatform?: string;
  uaPlatformVersion?: string;
  viewportWidth?: number;
  viewportHeight?: number;
  dpr?: number;
  connection?: ConnectionType;
  saveData?: boolean;
  prefersReducedMotion?: boolean;
  prefersColorScheme?: "light" | "dark";
  language?: string;
  timezone?: string;
}

export interface PageContext {
  url?: string;
  title?: string;
  keywords?: string[];
  categories?: string[];             // IAB content taxonomy
  sentiment?: number;                // -1 to 1
  language?: string;
  scrollDepth?: number;              // 0–1
  timeOnPage?: number;               // Seconds
  referrer?: string;
  utmSource?: string;
  utmMedium?: string;
  utmCampaign?: string;
}

export interface UserProfile {
  userId?: string;
  sessionId?: string;
  anonymousId?: string;
  segments?: string[];
  country?: string;
  region?: string;
  city?: string;
  language?: string;
  deviceType?: DeviceType;
  os?: OperatingSystem;
  browser?: Browser;
  connectionType?: ConnectionType;
  isReturning?: boolean;
  visitCount?: number;
  ltv?: number;                      // Lifetime value estimate
  riskScore?: number;                // Fraud / bot risk 0–100
  consentLevel?: 0 | 1 | 2 | 3;    // GDPR/CCPA consent level
}

export interface AuctionContext {
  floorPrice?: number;               // Publisher floor
  timeout?: number;                  // Bid timeout ms
  seatIds?: string[];                // Allowed buyer seats
  dealIds?: string[];                // Available PMP deals
  supplyChain?: string;              // OpenRTB 2.5 schain
}

export interface AdResolution {
  ad: Ad | null;
  adId?: string;                     // ID of the winning ad (convenience)
  ads?: Ad[];                        // For grid/multi layouts
  requestId: string;
  resolvedAt: number;
  latencyMs: number;
  stagesRun: PipelineStage[];
  eligibleCount: number;
  filteredOut: { stage: PipelineStage; count: number }[];
  winningBid?: number;
  qualityScore?: number;
  pipelineErrors?: PipelineError[];
  fallback?: boolean;
  cached?: boolean;
  debugInfo?: Record<string, unknown>;
}

// ────────────────────────────────────────────────────────────────────────────
// 12. ERROR TYPES
// ────────────────────────────────────────────────────────────────────────────

export type AdErrorCode =
  | "NO_AD_FOUND"
  | "INVALID_PLACEMENT"
  | "INVALID_LAYOUT"
  | "BUDGET_EXHAUSTED"
  | "FREQUENCY_CAPPED"
  | "TARGETING_MISMATCH"
  | "SCHEDULE_INACTIVE"
  | "AUTH_FAILED"
  | "RATE_LIMITED"
  | "VALIDATION_FAILED"
  | "CONSTRAINT_VIOLATION"
  | "AUCTION_TIMEOUT"
  | "PIPELINE_TIMEOUT"
  | "CREATIVE_LOAD_ERROR"
  | "TRACKING_FAILED"
  | "EXPERIMENT_COLLISION"
  | "BUDGET_PACING_STALLED"
  | "ADAPTER_ERROR"
  | "NETWORK_ERROR"
  | "UNKNOWN";

export interface PipelineError {
  code: AdErrorCode;
  stage: PipelineStage;
  message: string;
  details?: unknown;
  retryable?: boolean;
  timestamp?: number;
}

// ────────────────────────────────────────────────────────────────────────────
// 13. CORE AD
// ────────────────────────────────────────────────────────────────────────────

export type AdStatus = "active" | "paused" | "scheduled" | "ended" | "draft" | "under_review" | "rejected" | "archived";

export type AdType =
  | "display"       // Standard display
  | "native"        // Native in-feed
  | "video"         // Video ad
  | "audio"         // Audio ad
  | "interactive"   // Rich interactive
  | "programmatic"  // RTB/programmatic
  | "direct"        // Direct-sold
  | "sponsored"     // Branded content
  | "house"         // Internal/house ad
  | "psa";          // Public service

export interface AdLabel {
  text: string;                      // e.g. "Sponsored", "Ad", "Promoted"
  position?: "top-left" | "top-right" | "bottom-left" | "bottom-right";
  style?: "pill" | "badge" | "text" | "icon";
}

export interface Ad {
  id: string;
  type?: AdType;

  // Content
  title: string;
  description?: string;
  eyebrow?: string;
  badge?: string;
  subline?: string;                  // Second line under description
  disclaimer?: string;               // Legal/compliance text
  ctaText: string;
  ctaUrl: string;
  ctaSecondaryText?: string;         // Second CTA
  ctaSecondaryUrl?: string;

  // Identity
  sponsorName?: string;
  sponsorLogoUrl?: string;
  sponsorUrl?: string;
  verified?: boolean;
  adLabel?: AdLabel;                 // Custom "Sponsored" label config

  // Creative
  creative?: AdCreative;
  creativeVariants?: Record<string, AdCreative>; // Keyed by layout or breakpoint
  /** @deprecated use creative.imageUrl */
  imageUrl?: string;

  // Classification
  layouts: AdLayout[];
  placements: AdPlacement[];
  categories?: string[];             // IAB taxonomy
  tags?: string[];                   // Custom metadata tags
  language?: string;                 // ISO 639-1

  // Rotation
  weight?: number;
  priority?: number;                 // Override weight for certain slots

  // Lifecycle
  status?: AdStatus;
  version?: number;
  createdAt?: string;
  updatedAt?: string;
  reviewedAt?: string;
  reviewNotes?: string;

  // Pricing
  budget?: PricingConfig;

  // Scheduling
  schedule?: AdSchedule;

  // Frequency
  frequencyProfile?: FrequencyProfile;
  /** @deprecated use frequencyProfile.daily */
  frequencyCap?: FrequencyCap;

  // Targeting
  targeting?: AdTargeting;

  // Experiment
  experiment?: AdExperiment;

  // Constraints
  constraints?: ConstraintGroup[];

  // Tracking
  impressionPixel?: string;
  clickPixel?: string;
  viewabilityPixel?: string;
  conversionPixel?: string;
  thirdPartyPixels?: { event: AdEventType; url: string }[];

  // Feature flags
  features?: AdFeatureFlags;

  // Backend / API control
  externalId?: string;               // ID in external ad server
  campaignId?: string;
  lineItemId?: string;
  orderId?: string;
  advertiserDomain?: string;
  iabContentCategory?: string;
  adChoicesUrl?: string;
  clickThroughUrl?: string;

  // Quality
  qualityScore?: number;             // 0–10, set by engine
  relevanceScore?: number;           // Contextual relevance 0–1
}

// ────────────────────────────────────────────────────────────────────────────
// 14. FEATURE FLAGS (100+ controls)
// ────────────────────────────────────────────────────────────────────────────

export interface AdFeatureFlags {
  // Rendering
  lazyLoad?: boolean;
  lazyLoadOffset?: number;           // rootMargin px
  preload?: boolean;
  prefetchNextAd?: boolean;
  renderOnVisible?: boolean;
  intersectionThreshold?: number;    // 0–1
  fadeInAnimation?: boolean;
  slideInAnimation?: boolean;
  animationDurationMs?: number;
  progressiveImage?: boolean;        // LQIP → full quality
  webpPreferred?: boolean;
  avifPreferred?: boolean;
  responsiveImages?: boolean;
  retina?: boolean;                  // Serve 2x on HiDPI
  svgFallback?: boolean;
  lowBandwidthMode?: boolean;        // Serve text-only on slow connections
  offlineGraceful?: boolean;         // Hide vs show placeholder offline
  skeletonLoader?: boolean;
  shimmerEffect?: boolean;

  // Rotation
  autoRotate?: boolean;
  rotationIntervalMs?: number;
  rotationOnVisible?: boolean;       // Only rotate when in viewport
  pauseRotationOnHover?: boolean;
  rotationTransition?: "fade" | "slide" | "flip" | "none";
  randomiseRotationOrder?: boolean;
  weightedRotation?: boolean;
  maxRotations?: number;             // Stop after N rotations

  // Tracking
  trackImpressions?: boolean;
  trackClicks?: boolean;
  trackViewability?: boolean;
  trackConversions?: boolean;
  trackEngagement?: boolean;
  trackScrollDepth?: boolean;
  trackHover?: boolean;
  trackVideoQuartiles?: boolean;     // 25/50/75/100% video
  trackAudioCompletion?: boolean;
  viewabilityStandard?: "iab_mrc" | "groupm" | "custom";
  viewabilityMinRatio?: number;      // Default 0.5 (IAB MRC)
  viewabilityMinMs?: number;         // Default 1000ms (IAB MRC)
  heatmapTracking?: boolean;
  sessionRecordingSync?: boolean;    // Tag with session recording IDs
  thirdPartyVerification?: boolean;
  brandSafetyCheck?: boolean;

  // Frequency
  respectGlobalFrequencyCap?: boolean;
  shareFrequencyAcrossPlacements?: boolean;
  resetFrequencyOnConversion?: boolean;
  softFrequencyCap?: boolean;        // Reduce weight vs hard block

  // Experiment
  enableExperiment?: boolean;
  stickyVariant?: boolean;
  crossDeviceVariant?: boolean;
  experimentDebugMode?: boolean;

  // Layout
  stretchToFit?: boolean;            // Fill container vs fixed size
  maxWidth?: number;
  minWidth?: number;
  aspectRatioLocked?: boolean;
  borderRadius?: number;
  borderEnabled?: boolean;
  shadowEnabled?: boolean;
  darkModeAdapt?: boolean;           // Auto-adjust for dark mode
  rtlSupport?: boolean;              // Right-to-left text support
  accessibilityMode?: boolean;       // Enhanced WCAG compliance
  focusIndicator?: boolean;          // Visible focus ring
  reducedMotion?: boolean;           // Respect prefers-reduced-motion
  highContrast?: boolean;

  // Click behaviour
  openInNewTab?: boolean;
  clickProtection?: boolean;         // Validate click before redirect
  doubleClickProtection?: boolean;
  misclickProtection?: boolean;      // Ignore edge-clicks
  clickDebounceMs?: number;

  // Interstitial
  closeButton?: boolean;
  closeButtonDelayMs?: number;
  closeButtonPosition?: "top-left" | "top-right";
  closeOnOverlayClick?: boolean;
  closeOnEscape?: boolean;
  preventScrollLock?: boolean;

  // Video
  videoAutoplay?: boolean;
  videoMuted?: boolean;
  videoControls?: boolean;
  videoLoop?: boolean;
  videoClickToPlay?: boolean;
  videoProgressBar?: boolean;
  videoSkippable?: boolean;
  videoSkipDelayMs?: number;
  videoEndCard?: boolean;
  videoCaptions?: boolean;
  videoAdChoices?: boolean;

  // Privacy / Consent
  requireConsent?: boolean;
  consentLevel?: 0 | 1 | 2 | 3;    // Min required consent level
  cookielessMode?: boolean;          // Use fingerprint-free targeting
  gdprCompliant?: boolean;
  ccpaCompliant?: boolean;
  coppaCompliant?: boolean;
  contextualOnly?: boolean;          // No behavioural targeting

  // Budget / Pacing
  enableBudgetPacing?: boolean;
  pacingAlgorithm?: "even" | "frontloaded" | "backloaded" | "goal_based";
  overbookingBuffer?: number;        // % over-delivery allowed (e.g. 0.1 = 10%)

  // Fallback
  fallbackAdId?: string;             // Show this ad if primary fails
  houseAdFallback?: boolean;
  blankFallback?: boolean;
  placeholderFallback?: boolean;

  // Debug
  debugOverlay?: boolean;            // Show debug info overlay on ad
  logLevel?: "off" | "error" | "warn" | "info" | "debug" | "verbose";
}

// ────────────────────────────────────────────────────────────────────────────
// 15. ANALYTICS / EVENTS
// ────────────────────────────────────────────────────────────────────────────

export type AdEventType =
  | "impression" | "click" | "viewable"
  | "conversion" | "skip" | "close"
  | "hover" | "expand" | "collapse"
  | "video_start" | "video_q1" | "video_q2"
  | "video_q3" | "video_complete" | "video_mute"
  | "video_unmute" | "video_pause" | "video_resume"
  | "audio_start" | "audio_complete"
  | "form_submit" | "form_start" | "form_abandon"
  | "swipe_left" | "swipe_right" | "swipe_up"
  | "scroll_to" | "timer_complete" | "reward_earned"
  | "ar_activated" | "chat_open" | "chat_message"
  | "share" | "save" | "rate"
  | "creative_error" | "tracking_error" | "load"
  | "render" | "first_byte" | "dom_ready";

export interface AdEvent {
  type: AdEventType;
  adId: string;
  placement: AdPlacement;
  layout?: AdLayout;  // optional — engine selects best layout when omitted
  variant?: string;
  experimentKey?: string;
  sessionId: string;
  userId?: string;
  anonymousId?: string;
  timestamp: number;
  serverTimestamp?: number;          // Set by backend on receipt
  sequenceNumber?: number;           // Per-session monotonic counter
  viewabilityRatio?: number;         // 0–1
  viewabilityMs?: number;            // Duration visible
  revenueCredit?: number;
  currencyCode?: string;
  winningBid?: number;
  qualityScore?: number;
  scrollDepth?: number;
  videoProgress?: number;            // 0–1
  interactionType?: string;          // Detailed interaction
  elementId?: string;                // DOM element clicked
  xCoord?: number;                   // Click X in ad space (0–1)
  yCoord?: number;                   // Click Y in ad space (0–1)
  pageUrl?: string;
  pageTitle?: string;
  referrer?: string;
  userAgent?: string;
  country?: string;
  region?: string;
  city?: string;
  deviceType?: DeviceType;
  os?: OperatingSystem;
  browser?: Browser;
  connectionType?: ConnectionType;
  requestId?: string;
  batchId?: string;
  sdkVersion?: string;
  customData?: Record<string, unknown>;
}

export interface TrackingBatch {
  batchId: string;
  events: AdEvent[];
  sentAt: number;
  retryCount?: number;
  source?: "queue" | "beacon" | "fetch" | "xhr";
}

// ────────────────────────────────────────────────────────────────────────────
// 16. ENGINE CONFIG
// ────────────────────────────────────────────────────────────────────────────

export interface AdEngineConfig {
  // Request context
  userSegments?: string[];
  userCountry?: string;
  userRegion?: string;
  userCity?: string;
  deviceType?: DeviceType;
  os?: OperatingSystem;
  browser?: Browser;
  connectionType?: ConnectionType;
  language?: string;
  timezone?: string;
  consentLevel?: 0 | 1 | 2 | 3;
  userId?: string;
  sessionId?: string;
  anonymousId?: string;

  // Engine behaviour
  rotationIntervalMs?: number;
  defaultFrequencyCap?: FrequencyCap;
  defaultFrequencyProfile?: FrequencyProfile;
  timeoutMs?: number;                // Pipeline timeout
  enableCache?: boolean;
  cacheTtlMs?: number;
  enableDebug?: boolean;
  logLevel?: AdFeatureFlags["logLevel"];
  strictMode?: boolean;              // Fail hard on any error vs graceful

  // Feature flags overrides (global defaults)
  featureOverrides?: Partial<AdFeatureFlags>;

  // Pipeline middleware
  middlewares?: PipelineMiddleware[];

  // Adapters
  enabledAdapters?: string[];

  // Backend API
  apiEndpoint?: string;              // For server-side resolution
  apiKey?: string;
  publisherId?: string;
  siteId?: string;
  appId?: string;

  // Quality
  minQualityScore?: number;          // Filter ads below score
  brandSafetyThreshold?: number;
}

// ────────────────────────────────────────────────────────────────────────────
// 17. PREBUILT FLOWS
// ────────────────────────────────────────────────────────────────────────────

export type PrebuiltFlowType =
  | "new_user_onboarding"
  | "retargeting_lapsed"
  | "cart_abandonment"
  | "post_conversion"
  | "lookalike_acquisition"
  | "seasonal_campaign"
  | "brand_awareness"
  | "app_install"
  | "lead_generation"
  | "re_engagement"
  | "upsell_crosssell"
  | "loyalty_reward"
  | "event_promotion"
  | "flash_sale"
  | "content_recommendation"
  | "product_launch"
  | "competitive_conquest"
  | "geo_local"
  | "contextual_relevance"
  | "daypart_optimised"
  | "custom";

export interface PrebuiltFlow {
  id: string;
  type: PrebuiltFlowType;
  name: string;
  description: string;
  steps: FlowStep[];
  triggers: FlowTrigger[];
  exitConditions: FlowExitCondition[];
  kpis: string[];
}

export interface FlowStep {
  id: string;
  order: number;
  adIds: string[];
  waitAfterEventType?: AdEventType;
  waitMs?: number;
  maxAttempts?: number;
}

export interface FlowTrigger {
  event: string;                     // e.g. "page_view", "cart_add"
  conditions?: AdConstraint[];
}

export interface FlowExitCondition {
  event: AdEventType;
  or?: FlowExitCondition[];
}

// ────────────────────────────────────────────────────────────────────────────
// 18. ADAPTERS
// ────────────────────────────────────────────────────────────────────────────

export type AdapterType =
  | "google_ad_manager" | "meta_audience_network"
  | "amazon_aps" | "amazon_dsp" | "dv360"
  | "the_trade_desk" | "trade_desk" | "xandr" | "xandr_appnexus"
  | "pubmatic" | "magnite"
  | "openx" | "rubicon"
  | "criteo" | "taboola"
  | "outbrain" | "mopub"
  | "applovin" | "ironsource"
  | "unity_ads" | "chartboost"
  | "verizon_media" | "smart_adserver"
  | "adform" | "mediamind"
  | "sizmek" | "flashtalking"
  | "doubleclick" | "openrtb_custom" | "open_rtb"
  | "prebid" | "sharethrough"
  | "triplelift" | "nativo"
  | "vast_vpaid" | "amp"
  | "segment" | "mparticle" | "rudderstack"
  | "shopify" | "wordpress"
  | "react_native" | "nextjs" | "nuxt" | "sveltekit"
  | "internal";

export interface AdapterConfig {
  type: AdapterType;
  enabled: boolean;
  priority?: number;
  endpoint?: string;
  apiKey?: string;
  publisherId?: string;
  networkId?: string;
  timeout?: number;
  retryAttempts?: number;
  fallbackToNext?: boolean;
  customParams?: Record<string, unknown>;
}

// ────────────────────────────────────────────────────────────────────────────
// 19. VALIDATION SCHEMAS
// ────────────────────────────────────────────────────────────────────────────

export interface ValidationResult {
  valid: boolean;
  errors: ValidationError[];
  warnings: ValidationWarning[];
  score: number;                     // 0–100 quality score
}

export interface ValidationError {
  field: string;
  code: string;
  message: string;
  value?: unknown;
  expected?: unknown;
  severity: "error" | "critical";
}

export interface ValidationWarning {
  field: string;
  code: string;
  message: string;
  recommendation?: string;
}
