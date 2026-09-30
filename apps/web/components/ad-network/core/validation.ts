/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  validation.ts
   1000x strict schema validation · constraint satisfaction · quality scoring
   ═══════════════════════════════════════════════════════════════════════════════ */

import {
  Ad, AdLayout, AdPlacement, PricingModel, AdStatus, AdType, AdEventType,
  ConstraintGroup, AdConstraint, ConstraintOperator, ValidationResult,
  ValidationError, ValidationWarning, AdFeatureFlags, PricingConfig,
  AdSchedule, FrequencyProfile, AdTargeting, AdExperiment, AdCreative,
} from "./types";

// ─── Constants ────────────────────────────────────────────────────────────────

const VALID_PLACEMENTS = new Set<AdPlacement>([
  "article_top","article_mid","article_bottom","article_sticky","article_parallax",
  "article_pre_headline","article_post_headline","article_inline_1","article_inline_2",
  "article_inline_3","sidebar_top","sidebar_sticky","sidebar_mid","sidebar_bottom",
  "sidebar_rail_left","sidebar_rail_right","feed_item","feed_top","feed_mid",
  "feed_bottom","feed_native_1","feed_native_2","feed_sponsored_story",
  "feed_video_inline","feed_carousel","homepage_hero","homepage_takeover",
  "homepage_banner","homepage_below_fold","homepage_exit_intent","search_results",
  "search_top","search_right","search_sponsored","pre_roll","mid_roll","post_roll",
  "overlay","companion_banner","outstream_video","in_stream_pause","interstitial",
  "rewarded","splash_screen","modal_center","modal_bottom_sheet","exit_intent_popup",
  "nav_top","nav_bottom","sticky_footer","sticky_header","floating_cta","docked_banner",
  "app_open","app_banner_top","app_banner_bottom","app_interstitial","app_rewarded",
  "app_native_grid","email_header","email_footer","email_inline","email_sidebar",
  "ctv_pre_roll","ctv_mid_roll","ctv_pause_ad","ctv_l_banner","dooh_billboard",
  "dooh_transit","dooh_retail","dooh_cinema",
]);

const VALID_LAYOUTS = new Set<AdLayout>([
  "leaderboard","billboard","medium_rectangle","half_page","wide_skyscraper",
  "skyscraper","square","small_square","large_rectangle","banner","micro_bar",
  "button_1","button_2","large_leaderboard","panorama","triple_widescreen","portrait",
  "netboard","top_banner","wallpaper","hero","hero_split","hero_centered","hero_minimal",
  "hero_cinematic","hero_mosaic","hero_countdown","hero_parallax","hero_gradient",
  "hero_glassmorphic","card_grid","card_list","card_horizontal","card_minimal",
  "card_rich","card_product","card_profile","card_event","card_stat","card_comparison",
  "card_testimonial","card_video_thumb","card_article","card_deal","card_app_install",
  "card_recipe","card_job","card_location","card_poll","card_quiz","inline_text",
  "native_article","native_feed","native_search","native_recommendation",
  "native_product_list","native_in_content","native_promoted_story","native_ad_choice",
  "story_card","story_swipeable","story_interactive","story_link_sticker",
  "reel_overlay","vertical_video","video_pre_roll","video_mid_roll","video_bumper",
  "video_skippable","video_outstream","video_rewarded","video_companion",
  "video_interactive","video_360","video_live","video_overlay_l","video_thumbnail_list",
  "interactive_quiz","interactive_calculator","interactive_poll","interactive_slider",
  "interactive_scratch","interactive_spin","interactive_ar","interactive_map",
  "interactive_chat","interactive_form","interactive_countdown","interactive_rating",
  "interactive_hotspot","carousel_standard","carousel_product","carousel_story",
  "carousel_video","carousel_mixed","carousel_cinemagraph","expandable_banner",
  "expandable_half_page","lightbox","pushdown","toproll","interstitial_full",
  "interstitial_partial","sticky_footer","sticky_header","floating_box",
  "programmatic_display","programmatic_native","header_bidding","open_rtb",
  "private_marketplace","preferred_deal","guaranteed","contextual_display",
  "sponsored_article","sponsored_section","sponsored_newsletter","sponsored_podcast",
  "sponsored_event","sponsored_glossary","product_showcase","dynamic_product",
  "shoppable_image","shoppable_video","price_comparison","cashback_offer",
  "flash_sale","bundle_offer","app_install_banner","app_install_interstitial",
  "app_engagement","smart_app_banner","ctv_display","ctv_interactive","dooh_static",
  "dooh_dynamic","dooh_trigger","email_masthead","email_mid_article","email_footer_strip",
  "email_product_block","text_only","accessible_rich","low_bandwidth","fallback_placeholder",
]);

const VALID_PRICING_MODELS = new Set<PricingModel>([
  "CPM","CPC","CPV","CPA","CPL","CPI","CPE","CPD","CPT","flat_rate",
  "vCPM","CPCV","CPF","AVOC","ROAS","CPS","CPO","CPR","CPTM","CPQ",
  "CPC_enhanced","smart_cpa","target_roas","max_conversions",
  "first_price_auction","second_price_auction","header_bidding_cpm",
  "open_rtb_cpm","private_deal_cpm","preferred_deal_cpm",
  "programmatic_guaranteed_cpm","dynamic_floor","yield_optimised",
  "revenue_share","profit_share","gross_margin_share",
  "CPH","tenancy_day","tenancy_week","tenancy_month","burst_rate",
  "hybrid_cpm_cpc","hybrid_cpa_cpv","stepped_cpm","tiered_cpc","bid_shading",
  "subscription_monthly","subscription_annual","credit_bundle","prepaid_credits",
  "pay_for_results","cost_per_attention","cost_per_memory","guaranteed_outcomes",
]);

const VALID_STATUSES = new Set<AdStatus>(["active","paused","scheduled","ended","draft","under_review","rejected","archived"]);
const VALID_TYPES = new Set<AdType>(["display","native","video","audio","interactive","programmatic","direct","sponsored","house","psa"]);

// ─── URL Validation ──────────────────────────────────────────────────────────

function isValidUrl(url: string): boolean {
  try {
    const u = new URL(url);
    return u.protocol === "https:" || u.protocol === "http:";
  } catch { return false; }
}

function isValidHttpsUrl(url: string): boolean {
  try {
    return new URL(url).protocol === "https:";
  } catch { return false; }
}

function isValidHex(color: string): boolean {
  return /^#([0-9A-Fa-f]{3,4}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/.test(color);
}

function isValidIso8601(date: string): boolean {
  return !isNaN(Date.parse(date));
}

function isValidIso3166(code: string): boolean {
  return /^[A-Z]{2}$/.test(code);
}

// ─── Field validators ────────────────────────────────────────────────────────

function validateBudget(budget: PricingConfig, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (!VALID_PRICING_MODELS.has(budget.model)) {
    errors.push({ field: `${path}.model`, code: "INVALID_PRICING_MODEL", message: `Unknown pricing model: "${budget.model}"`, value: budget.model, severity: "critical" });
  }

  if (typeof budget.bidFloor !== "number" || budget.bidFloor < 0) {
    errors.push({ field: `${path}.bidFloor`, code: "INVALID_BID_FLOOR", message: "bidFloor must be a non-negative number", value: budget.bidFloor, severity: "error" });
  }

  if (budget.bidCeiling !== undefined && budget.bidCeiling < budget.bidFloor) {
    errors.push({ field: `${path}.bidCeiling`, code: "BID_CEILING_BELOW_FLOOR", message: "bidCeiling cannot be less than bidFloor", severity: "error" });
  }

  if (budget.totalBudget !== undefined && budget.dailyCap !== undefined && budget.dailyCap > budget.totalBudget) {
    errors.push({ field: `${path}.dailyCap`, code: "DAILY_CAP_EXCEEDS_TOTAL", message: "dailyCap cannot exceed totalBudget", severity: "error" });
  }

  if (budget.revenueSharePct !== undefined && (budget.revenueSharePct < 0 || budget.revenueSharePct > 1)) {
    errors.push({ field: `${path}.revenueSharePct`, code: "INVALID_REVENUE_SHARE", message: "revenueSharePct must be between 0 and 1", severity: "error" });
  }

  if (budget.tiers?.length) {
    const sorted = [...budget.tiers].sort((a, b) => a.minVolume - b.minVolume);
    sorted.forEach((tier, i) => {
      if (i > 0 && tier.minVolume <= sorted[i - 1].minVolume) {
        errors.push({ field: `${path}.tiers[${i}]`, code: "DUPLICATE_TIER_VOLUME", message: "Tier minVolume values must be strictly ascending", severity: "error" });
      }
      if (tier.unitCost < 0) {
        errors.push({ field: `${path}.tiers[${i}].unitCost`, code: "NEGATIVE_TIER_COST", message: "unitCost must be non-negative", severity: "error" });
      }
    });
  }

  if (budget.bidFloor > 0 && budget.bidFloor < 10) {
    warnings.push({ field: `${path}.bidFloor`, code: "LOW_BID_FLOOR", message: "Bid floor under 10 paise — may cause low fill rates", recommendation: "Consider raising bidFloor to at least 100 (₹1)" });
  }

  return { errors, warnings };
}

function validateSchedule(schedule: AdSchedule, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (schedule.startDate && !isValidIso8601(schedule.startDate)) {
    errors.push({ field: `${path}.startDate`, code: "INVALID_DATE", message: "startDate must be a valid ISO 8601 date string", severity: "error" });
  }
  if (schedule.endDate && !isValidIso8601(schedule.endDate)) {
    errors.push({ field: `${path}.endDate`, code: "INVALID_DATE", message: "endDate must be a valid ISO 8601 date string", severity: "error" });
  }
  if (schedule.startDate && schedule.endDate) {
    if (new Date(schedule.startDate) >= new Date(schedule.endDate)) {
      errors.push({ field: `${path}.endDate`, code: "END_BEFORE_START", message: "endDate must be after startDate", severity: "critical" });
    }
  }
  if (schedule.endDate && new Date(schedule.endDate) < new Date()) {
    warnings.push({ field: `${path}.endDate`, code: "CAMPAIGN_ENDED", message: "endDate is in the past — ad will never serve", recommendation: "Update endDate or set status to 'ended'" });
  }
  if (schedule.activeHours) {
    const { start, end } = schedule.activeHours;
    if (start < 0 || start > 23 || end < 0 || end > 23) {
      errors.push({ field: `${path}.activeHours`, code: "INVALID_HOUR", message: "activeHours.start and .end must be 0–23", severity: "error" });
    }
    if (start === end) {
      warnings.push({ field: `${path}.activeHours`, code: "SINGLE_HOUR_WINDOW", message: "activeHours window is only 1 hour — very restrictive", recommendation: "Widen the hour window for better fill" });
    }
  }
  if (schedule.activeDays?.length === 0) {
    errors.push({ field: `${path}.activeDays`, code: "EMPTY_ACTIVE_DAYS", message: "activeDays cannot be empty array; omit to run all days", severity: "error" });
  }
  if (schedule.daypartingMatrix) {
    const mat = schedule.daypartingMatrix;
    if (mat.length !== 7) {
      errors.push({ field: `${path}.daypartingMatrix`, code: "INVALID_MATRIX_ROWS", message: "daypartingMatrix must have exactly 7 rows (Mon–Sun)", severity: "error" });
    }
    mat.forEach((row, r) => {
      if (row.length !== 24) {
        errors.push({ field: `${path}.daypartingMatrix[${r}]`, code: "INVALID_MATRIX_COLS", message: `Row ${r} must have exactly 24 columns (hours)`, severity: "error" });
      }
    });
    const anyActive = mat.some(row => row.some(Boolean));
    if (!anyActive) {
      warnings.push({ field: `${path}.daypartingMatrix`, code: "ALL_HOURS_BLOCKED", message: "daypartingMatrix has no active slots — ad will never serve" });
    }
  }
  return { errors, warnings };
}

function validateFrequency(fp: FrequencyProfile, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];
  const caps = [fp.hourly, fp.daily, fp.weekly, fp.lifetime].filter(Boolean);
  caps.forEach((cap, i) => {
    const label = ["hourly","daily","weekly","lifetime"][i];
    if (!cap) return;
    if (cap.maxImpressions <= 0) {
      errors.push({ field: `${path}.${label}.maxImpressions`, code: "INVALID_FREQ_CAP", message: "maxImpressions must be > 0", severity: "error" });
    }
    if (cap.windowSeconds <= 0) {
      errors.push({ field: `${path}.${label}.windowSeconds`, code: "INVALID_WINDOW", message: "windowSeconds must be > 0", severity: "error" });
    }
  });
  if (fp.hourly && fp.daily && fp.hourly.maxImpressions > fp.daily.maxImpressions) {
    warnings.push({ field: `${path}`, code: "FREQ_INCONSISTENCY", message: "hourly.maxImpressions > daily.maxImpressions — daily cap may never be reached", recommendation: "Ensure hourly × hours_in_day ≤ daily cap" });
  }
  return { errors, warnings };
}

function validateCreative(creative: AdCreative, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (creative.imageUrl && !isValidUrl(creative.imageUrl)) {
    errors.push({ field: `${path}.imageUrl`, code: "INVALID_IMAGE_URL", message: "imageUrl must be a valid HTTP(S) URL", severity: "error" });
  }
  if (creative.imageUrl && !isValidHttpsUrl(creative.imageUrl)) {
    warnings.push({ field: `${path}.imageUrl`, code: "HTTP_IMAGE_URL", message: "imageUrl is HTTP not HTTPS — may be blocked by mixed content policy", recommendation: "Use HTTPS URLs for all creative assets" });
  }
  if (creative.videoUrl && !isValidUrl(creative.videoUrl)) {
    errors.push({ field: `${path}.videoUrl`, code: "INVALID_VIDEO_URL", message: "videoUrl must be a valid HTTP(S) URL", severity: "error" });
  }
  if (creative.bgColor && !isValidHex(creative.bgColor)) {
    errors.push({ field: `${path}.bgColor`, code: "INVALID_HEX_COLOR", message: `bgColor "${creative.bgColor}" is not a valid hex color`, severity: "error" });
  }
  if (creative.accentColor && !isValidHex(creative.accentColor)) {
    errors.push({ field: `${path}.accentColor`, code: "INVALID_HEX_COLOR", message: `accentColor "${creative.accentColor}" is not a valid hex color`, severity: "error" });
  }
  if (!creative.alt && creative.imageUrl) {
    warnings.push({ field: `${path}.alt`, code: "MISSING_ALT_TEXT", message: "alt text missing on image creative — accessibility and SEO impact", recommendation: "Add descriptive alt text to every image creative" });
  }
  if (creative.alt && creative.alt.length > 255) {
    errors.push({ field: `${path}.alt`, code: "ALT_TOO_LONG", message: "alt text exceeds 255 characters", severity: "error" });
  }
  if (creative.fileSize && creative.fileSize > 2_000_000) {
    warnings.push({ field: `${path}.fileSize`, code: "LARGE_CREATIVE", message: "Creative file size > 2MB — may cause slow loads", recommendation: "Compress assets to under 200KB for display, 1MB for rich media" });
  }
  const validAspectRatios = ["16:9","4:3","1:1","9:16","3:2","2:1","21:9"];
  if (creative.aspectRatio && !validAspectRatios.includes(creative.aspectRatio)) {
    warnings.push({ field: `${path}.aspectRatio`, code: "UNUSUAL_ASPECT_RATIO", message: `aspectRatio "${creative.aspectRatio}" is non-standard`, recommendation: `Use standard ratios: ${validAspectRatios.join(", ")}` });
  }
  if (creative.videoDurationMs !== undefined && creative.videoDurationMs > 120_000) {
    warnings.push({ field: `${path}.videoDurationMs`, code: "LONG_VIDEO", message: "Video duration > 2 minutes — completion rates will be very low", recommendation: "Keep video ads under 30 seconds for best performance" });
  }
  return { errors, warnings };
}

function validateTargeting(targeting: AdTargeting, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (targeting.geo?.countries?.length) {
    targeting.geo.countries.forEach((code, i) => {
      if (!isValidIso3166(code)) {
        errors.push({ field: `${path}.geo.countries[${i}]`, code: "INVALID_COUNTRY_CODE", message: `"${code}" is not a valid ISO 3166-1 alpha-2 country code`, severity: "error" });
      }
    });
  }
  if (targeting.audience?.segments?.length === 0) {
    warnings.push({ field: `${path}.audience.segments`, code: "EMPTY_SEGMENTS", message: "segments array is empty — no segment targeting applied", recommendation: "Remove empty segments array or add segment IDs" });
  }
  if (targeting.audience?.retargeting && targeting.audience.retargeting.windowDays < 1) {
    errors.push({ field: `${path}.audience.retargeting.windowDays`, code: "INVALID_RETARGETING_WINDOW", message: "Retargeting window must be at least 1 day", severity: "error" });
  }
  if (targeting.audience?.retargeting && targeting.audience.retargeting.windowDays > 540) {
    warnings.push({ field: `${path}.audience.retargeting.windowDays`, code: "LONG_RETARGETING_WINDOW", message: "Retargeting window > 540 days may not be supported by all platforms", recommendation: "Most platforms support max 180–540 day windows" });
  }
  if (targeting.intentScore) {
    const { min, max } = targeting.intentScore;
    if (min < 0 || max > 100 || min > max) {
      errors.push({ field: `${path}.intentScore`, code: "INVALID_INTENT_RANGE", message: "intentScore.min/max must be 0–100 with min ≤ max", severity: "error" });
    }
  }
  return { errors, warnings };
}

function validateExperiment(exp: AdExperiment, path: string): { errors: ValidationError[]; warnings: ValidationWarning[] } {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (!exp.key || exp.key.trim().length === 0) {
    errors.push({ field: `${path}.key`, code: "MISSING_EXPERIMENT_KEY", message: "experiment.key is required", severity: "critical" });
  }
  if (!/^[a-z0-9_-]+$/.test(exp.key)) {
    errors.push({ field: `${path}.key`, code: "INVALID_EXPERIMENT_KEY", message: `experiment.key "${exp.key}" must match [a-z0-9_-]`, severity: "error" });
  }
  if (exp.trafficAllocation < 0 || exp.trafficAllocation > 1) {
    errors.push({ field: `${path}.trafficAllocation`, code: "INVALID_TRAFFIC_ALLOCATION", message: "trafficAllocation must be between 0 and 1", severity: "error" });
  }
  if (exp.confidenceLevel !== undefined && (exp.confidenceLevel <= 0 || exp.confidenceLevel >= 1)) {
    errors.push({ field: `${path}.confidenceLevel`, code: "INVALID_CONFIDENCE_LEVEL", message: "confidenceLevel must be between 0 and 1 exclusive", severity: "error" });
  }
  if (exp.holdbackPct !== undefined && (exp.holdbackPct < 0 || exp.holdbackPct > 0.5)) {
    warnings.push({ field: `${path}.holdbackPct`, code: "HIGH_HOLDBACK_PCT", message: "holdbackPct > 50% means more traffic excluded than tested", recommendation: "Typical holdback is 5–10%" });
  }
  if (exp.type === "bandit" && !exp.bandit) {
    warnings.push({ field: `${path}.bandit`, code: "MISSING_BANDIT_CONFIG", message: "type is 'bandit' but bandit config is missing — will use defaults", recommendation: "Provide bandit config for best performance" });
  }
  return { errors, warnings };
}

// ─── Constraint Satisfaction ─────────────────────────────────────────────────

export function evaluateConstraint(constraint: AdConstraint, record: Record<string, unknown>): boolean {
  const value = record[constraint.field];
  const target = constraint.value;

  switch (constraint.operator as ConstraintOperator) {
    case "eq":             return value === target;
    case "neq":            return value !== target;
    case "gt":             return typeof value === "number" && value > (target as number);
    case "gte":            return typeof value === "number" && value >= (target as number);
    case "lt":             return typeof value === "number" && value < (target as number);
    case "lte":            return typeof value === "number" && value <= (target as number);
    case "in":             return Array.isArray(target) && target.includes(value);
    case "not_in":         return Array.isArray(target) && !target.includes(value);
    case "contains":       return Array.isArray(value) && value.includes(target);
    case "not_contains":   return Array.isArray(value) && !value.includes(target);
    case "matches_regex":  return typeof value === "string" && new RegExp(target as string).test(value);
    case "between": {
      const [lo, hi] = target as [number, number];
      return typeof value === "number" && value >= lo && value <= hi;
    }
    case "is_null":        return value === null || value === undefined;
    case "is_not_null":    return value !== null && value !== undefined;
    default:               return false;
  }
}

export function evaluateConstraintGroup(group: ConstraintGroup, record: Record<string, unknown>): boolean {
  const { logic, constraints } = group;
  const results = constraints.map(c => {
    if ("logic" in c) return evaluateConstraintGroup(c as ConstraintGroup, record);
    return evaluateConstraint(c as AdConstraint, record);
  });

  if (logic === "AND") return results.every(Boolean);
  if (logic === "OR")  return results.some(Boolean);
  if (logic === "NOT") return !results[0];
  return false;
}

// ─── Quality Scorer ──────────────────────────────────────────────────────────

export function scoreAd(ad: Ad, errors: ValidationError[], warnings: ValidationWarning[]): number {
  let score = 100;

  // Deduct for errors
  score -= errors.filter(e => e.severity === "critical").length * 25;
  score -= errors.filter(e => e.severity === "error").length * 10;
  score -= warnings.length * 2;

  // Bonus for completeness
  if (ad.creative?.imageUrl) score += 5;
  if (ad.description) score += 3;
  if (ad.creative?.alt) score += 2;
  if (ad.eyebrow) score += 1;
  if (ad.budget) score += 5;
  if (ad.targeting) score += 3;
  if (ad.creative?.imageUrl && isValidHttpsUrl(ad.creative.imageUrl)) score += 2;
  if (ad.frequencyProfile || ad.frequencyCap) score += 2;
  if (ad.schedule) score += 2;

  // Deduct for missing recommended fields
  if (!ad.sponsorName) score -= 2;
  if (!ad.creative?.alt) score -= 3;

  return Math.max(0, Math.min(100, score));
}

// ─── Main Validator ──────────────────────────────────────────────────────────

export function validateAd(ad: unknown): ValidationResult {
  const errors: ValidationError[] = [];
  const warnings: ValidationWarning[] = [];

  if (!ad || typeof ad !== "object") {
    return { valid: false, errors: [{ field: "root", code: "INVALID_INPUT", message: "Input must be a non-null object", severity: "critical" }], warnings: [], score: 0 };
  }

  const a = ad as Record<string, unknown>;

  // id
  if (!a.id || typeof a.id !== "string" || a.id.trim().length === 0) {
    errors.push({ field: "id", code: "MISSING_ID", message: "Ad id is required and must be a non-empty string", severity: "critical" });
  } else if (!/^[a-zA-Z0-9_-]+$/.test(a.id as string)) {
    errors.push({ field: "id", code: "INVALID_ID_FORMAT", message: `id "${a.id}" must match [a-zA-Z0-9_-]`, severity: "error" });
  } else if ((a.id as string).length > 128) {
    errors.push({ field: "id", code: "ID_TOO_LONG", message: "id must be ≤ 128 characters", severity: "error" });
  }

  // title
  if (!a.title || typeof a.title !== "string" || a.title.trim().length === 0) {
    errors.push({ field: "title", code: "MISSING_TITLE", message: "title is required", severity: "critical" });
  } else if ((a.title as string).length > 150) {
    errors.push({ field: "title", code: "TITLE_TOO_LONG", message: "title exceeds 150 characters", value: (a.title as string).length, expected: 150, severity: "error" });
  } else if ((a.title as string).length < 3) {
    warnings.push({ field: "title", code: "TITLE_TOO_SHORT", message: "title under 3 characters — unlikely to engage users", recommendation: "Use a descriptive, benefit-oriented title" });
  }

  // ctaText
  if (!a.ctaText || typeof a.ctaText !== "string") {
    errors.push({ field: "ctaText", code: "MISSING_CTA_TEXT", message: "ctaText is required", severity: "critical" });
  } else if ((a.ctaText as string).length > 30) {
    warnings.push({ field: "ctaText", code: "CTA_TOO_LONG", message: "ctaText over 30 characters may be truncated on small layouts", recommendation: "Keep CTAs under 20 characters" });
  }

  // ctaUrl
  if (!a.ctaUrl || typeof a.ctaUrl !== "string") {
    errors.push({ field: "ctaUrl", code: "MISSING_CTA_URL", message: "ctaUrl is required", severity: "critical" });
  } else if (!isValidUrl(a.ctaUrl as string)) {
    errors.push({ field: "ctaUrl", code: "INVALID_CTA_URL", message: `ctaUrl "${a.ctaUrl}" is not a valid URL`, severity: "critical" });
  } else if (!isValidHttpsUrl(a.ctaUrl as string)) {
    warnings.push({ field: "ctaUrl", code: "HTTP_CTA_URL", message: "ctaUrl is HTTP — recommend HTTPS for security and SEO", recommendation: "Use HTTPS for all destination URLs" });
  }

  // layouts
  if (!Array.isArray(a.layouts) || (a.layouts as unknown[]).length === 0) {
    errors.push({ field: "layouts", code: "MISSING_LAYOUTS", message: "layouts must be a non-empty array", severity: "critical" });
  } else {
    (a.layouts as string[]).forEach((layout, i) => {
      if (!VALID_LAYOUTS.has(layout as AdLayout)) {
        errors.push({ field: `layouts[${i}]`, code: "INVALID_LAYOUT", message: `Unknown layout: "${layout}"`, value: layout, severity: "error" });
      }
    });
    if ((a.layouts as string[]).length > 20) {
      warnings.push({ field: "layouts", code: "MANY_LAYOUTS", message: "More than 20 layouts defined — consider splitting into separate ads", recommendation: "Group related layouts per ad for cleaner analytics" });
    }
  }

  // placements
  if (!Array.isArray(a.placements) || (a.placements as unknown[]).length === 0) {
    errors.push({ field: "placements", code: "MISSING_PLACEMENTS", message: "placements must be a non-empty array", severity: "critical" });
  } else {
    (a.placements as string[]).forEach((placement, i) => {
      if (!VALID_PLACEMENTS.has(placement as AdPlacement)) {
        errors.push({ field: `placements[${i}]`, code: "INVALID_PLACEMENT", message: `Unknown placement: "${placement}"`, value: placement, severity: "error" });
      }
    });
  }

  // status
  if (a.status && !VALID_STATUSES.has(a.status as AdStatus)) {
    errors.push({ field: "status", code: "INVALID_STATUS", message: `Unknown status: "${a.status}"`, severity: "error" });
  }

  // type
  if (a.type && !VALID_TYPES.has(a.type as AdType)) {
    errors.push({ field: "type", code: "INVALID_AD_TYPE", message: `Unknown ad type: "${a.type}"`, severity: "error" });
  }

  // weight
  if (a.weight !== undefined && (typeof a.weight !== "number" || a.weight <= 0)) {
    errors.push({ field: "weight", code: "INVALID_WEIGHT", message: "weight must be a positive number", severity: "error" });
  }

  // budget
  if (a.budget) {
    const r = validateBudget(a.budget as PricingConfig, "budget");
    errors.push(...r.errors); warnings.push(...r.warnings);
  } else {
    warnings.push({ field: "budget", code: "MISSING_BUDGET", message: "No pricing config — ad will not generate revenue tracking", recommendation: "Add a budget config with bidFloor and model" });
  }

  // schedule
  if (a.schedule) {
    const r = validateSchedule(a.schedule as AdSchedule, "schedule");
    errors.push(...r.errors); warnings.push(...r.warnings);
  }

  // frequencyProfile
  if (a.frequencyProfile) {
    const r = validateFrequency(a.frequencyProfile as FrequencyProfile, "frequencyProfile");
    errors.push(...r.errors); warnings.push(...r.warnings);
  }

  // creative
  if (a.creative) {
    const r = validateCreative(a.creative as AdCreative, "creative");
    errors.push(...r.errors); warnings.push(...r.warnings);
  } else if (!a.imageUrl) {
    warnings.push({ field: "creative", code: "MISSING_CREATIVE", message: "No creative assets — text-only rendering will be used", recommendation: "Add creative.imageUrl for higher CTR" });
  }

  // targeting
  if (a.targeting) {
    const r = validateTargeting(a.targeting as AdTargeting, "targeting");
    errors.push(...r.errors); warnings.push(...r.warnings);
  }

  // experiment
  if (a.experiment) {
    const r = validateExperiment(a.experiment as AdExperiment, "experiment");
    errors.push(...r.errors); warnings.push(...r.warnings);
  }

  // impressionPixel / clickPixel
  if (a.impressionPixel && !isValidUrl(a.impressionPixel as string)) {
    errors.push({ field: "impressionPixel", code: "INVALID_PIXEL_URL", message: "impressionPixel must be a valid URL", severity: "error" });
  }
  if (a.clickPixel && !isValidUrl(a.clickPixel as string)) {
    errors.push({ field: "clickPixel", code: "INVALID_PIXEL_URL", message: "clickPixel must be a valid URL", severity: "error" });
  }

  // description length
  if (a.description && typeof a.description === "string" && (a.description as string).length > 500) {
    warnings.push({ field: "description", code: "LONG_DESCRIPTION", message: "description over 500 chars — most layouts will truncate", recommendation: "Keep description under 150 characters" });
  }

  // disclaimer
  if (a.disclaimer && typeof a.disclaimer === "string" && (a.disclaimer as string).length > 200) {
    warnings.push({ field: "disclaimer", code: "LONG_DISCLAIMER", message: "disclaimer over 200 chars may overflow small layouts" });
  }

  const typedAd = a as unknown as Ad;
  const score = scoreAd(typedAd, errors, warnings);
  const valid = errors.filter(e => e.severity === "critical").length === 0;

  return { valid, errors, warnings, score };
}

// ─── Batch Validator ─────────────────────────────────────────────────────────

export interface BatchValidationResult {
  total: number;
  valid: number;
  invalid: number;
  results: Array<{ id: string; result: ValidationResult }>;
  globalWarnings: string[];
}

export function validateAdRegistry(ads: unknown[]): BatchValidationResult {
  const results = ads.map((ad, i) => {
    const id = (typeof ad === "object" && ad !== null && "id" in ad) ? String((ad as Record<string,unknown>).id) : `unknown_${i}`;
    return { id, result: validateAd(ad) };
  });

  const globalWarnings: string[] = [];

  // Check for duplicate IDs
  const ids = results.map(r => r.id);
  const seen = new Set<string>();
  ids.forEach(id => {
    if (seen.has(id)) globalWarnings.push(`Duplicate ad ID detected: "${id}"`);
    seen.add(id);
  });

  // Check for experiment traffic allocation sum
  const experimentGroups: Record<string, number[]> = {};
  (ads as Ad[]).forEach(ad => {
    if (ad.experiment) {
      if (!experimentGroups[ad.experiment.key]) experimentGroups[ad.experiment.key] = [];
      experimentGroups[ad.experiment.key].push(ad.experiment.trafficAllocation);
    }
  });
  Object.entries(experimentGroups).forEach(([key, allocs]) => {
    const sum = allocs.reduce((a, b) => a + b, 0);
    if (Math.abs(sum - 1.0) > 0.01) {
      globalWarnings.push(`Experiment "${key}" traffic allocations sum to ${sum.toFixed(3)} — should equal 1.0`);
    }
  });

  return {
    total: results.length,
    valid: results.filter(r => r.result.valid).length,
    invalid: results.filter(r => !r.result.valid).length,
    results,
    globalWarnings,
  };
}
