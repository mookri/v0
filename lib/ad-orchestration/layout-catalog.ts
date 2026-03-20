import type { AdLayout, AdPlacement } from "@/components/ad-network/core/types";
import type { LayoutSpec, ReservedSize } from "./types";

export const DISPLAY_LAYOUTS = [
  "leaderboard",
  "billboard",
  "medium_rectangle",
  "half_page",
  "wide_skyscraper",
  "skyscraper",
  "square",
  "small_square",
  "large_rectangle",
  "banner",
  "micro_bar",
  "button_1",
  "button_2",
  "large_leaderboard",
  "panorama",
  "triple_widescreen",
  "portrait",
  "netboard",
  "top_banner",
  "wallpaper",
] as const satisfies readonly AdLayout[];

export const HERO_LAYOUTS = [
  "hero",
  "hero_split",
  "hero_centered",
  "hero_minimal",
  "hero_cinematic",
  "hero_mosaic",
  "hero_countdown",
  "hero_parallax",
  "hero_gradient",
  "hero_glassmorphic",
] as const satisfies readonly AdLayout[];

export const CARD_LAYOUTS = [
  "card_grid",
  "card_list",
  "card_horizontal",
  "card_minimal",
  "card_rich",
  "card_product",
  "card_profile",
  "card_event",
  "card_stat",
  "card_comparison",
  "card_testimonial",
  "card_video_thumb",
  "card_article",
  "card_deal",
  "card_app_install",
  "card_recipe",
  "card_job",
  "card_location",
  "card_poll",
  "card_quiz",
] as const satisfies readonly AdLayout[];

export const NATIVE_LAYOUTS = [
  "inline_text",
  "native_article",
  "native_feed",
  "native_search",
  "native_recommendation",
  "native_product_list",
  "native_in_content",
  "native_promoted_story",
  "native_ad_choice",
] as const satisfies readonly AdLayout[];

export const STORY_LAYOUTS = [
  "story_card",
  "story_swipeable",
  "story_interactive",
  "story_link_sticker",
  "reel_overlay",
  "vertical_video",
] as const satisfies readonly AdLayout[];

export const VIDEO_LAYOUTS = [
  "video_pre_roll",
  "video_mid_roll",
  "video_bumper",
  "video_skippable",
  "video_outstream",
  "video_rewarded",
  "video_companion",
  "video_interactive",
  "video_360",
  "video_live",
  "video_overlay_l",
  "video_thumbnail_list",
] as const satisfies readonly AdLayout[];

export const INTERACTIVE_LAYOUTS = [
  "interactive_quiz",
  "interactive_calculator",
  "interactive_poll",
  "interactive_slider",
  "interactive_scratch",
  "interactive_spin",
  "interactive_ar",
  "interactive_map",
  "interactive_chat",
  "interactive_form",
  "interactive_countdown",
  "interactive_rating",
  "interactive_hotspot",
] as const satisfies readonly AdLayout[];

export const CAROUSEL_LAYOUTS = [
  "carousel_standard",
  "carousel_product",
  "carousel_story",
  "carousel_video",
  "carousel_mixed",
  "carousel_cinemagraph",
] as const satisfies readonly AdLayout[];

export const OVERLAY_LAYOUTS = [
  "expandable_banner",
  "expandable_half_page",
  "lightbox",
  "pushdown",
  "toproll",
  "interstitial_full",
  "interstitial_partial",
  "sticky_footer",
  "sticky_header",
  "floating_box",
] as const satisfies readonly AdLayout[];

export const PROGRAMMATIC_LAYOUTS = [
  "programmatic_display",
  "programmatic_native",
  "header_bidding",
  "open_rtb",
  "private_marketplace",
  "preferred_deal",
  "guaranteed",
  "contextual_display",
] as const satisfies readonly AdLayout[];

export const SPONSORED_LAYOUTS = [
  "sponsored_article",
  "sponsored_section",
  "sponsored_newsletter",
  "sponsored_podcast",
  "sponsored_event",
  "sponsored_glossary",
] as const satisfies readonly AdLayout[];

export const COMMERCE_LAYOUTS = [
  "product_showcase",
  "dynamic_product",
  "shoppable_image",
  "shoppable_video",
  "price_comparison",
  "cashback_offer",
  "flash_sale",
  "bundle_offer",
] as const satisfies readonly AdLayout[];

export const APP_LAYOUTS = [
  "app_install_banner",
  "app_install_interstitial",
  "app_engagement",
  "smart_app_banner",
] as const satisfies readonly AdLayout[];

export const SCREEN_LAYOUTS = [
  "ctv_display",
  "ctv_interactive",
  "dooh_static",
  "dooh_dynamic",
  "dooh_trigger",
] as const satisfies readonly AdLayout[];

export const EMAIL_LAYOUTS = [
  "email_masthead",
  "email_mid_article",
  "email_footer_strip",
  "email_product_block",
] as const satisfies readonly AdLayout[];

export const ACCESSIBILITY_LAYOUTS = [
  "text_only",
  "accessible_rich",
  "low_bandwidth",
  "fallback_placeholder",
] as const satisfies readonly AdLayout[];

export const ALL_LAYOUTS = [
  ...DISPLAY_LAYOUTS,
  ...HERO_LAYOUTS,
  ...CARD_LAYOUTS,
  ...NATIVE_LAYOUTS,
  ...STORY_LAYOUTS,
  ...VIDEO_LAYOUTS,
  ...INTERACTIVE_LAYOUTS,
  ...CAROUSEL_LAYOUTS,
  ...OVERLAY_LAYOUTS,
  ...PROGRAMMATIC_LAYOUTS,
  ...SPONSORED_LAYOUTS,
  ...COMMERCE_LAYOUTS,
  ...APP_LAYOUTS,
  ...SCREEN_LAYOUTS,
  ...EMAIL_LAYOUTS,
  ...ACCESSIBILITY_LAYOUTS,
] as const satisfies readonly AdLayout[];

export const ALL_PLACEMENTS = [
  "article_top",
  "article_mid",
  "article_bottom",
  "article_sticky",
  "article_parallax",
  "article_pre_headline",
  "article_post_headline",
  "article_inline_1",
  "article_inline_2",
  "article_inline_3",
  "sidebar_top",
  "sidebar_sticky",
  "sidebar_mid",
  "sidebar_bottom",
  "sidebar_rail_left",
  "sidebar_rail_right",
  "feed_item",
  "feed_top",
  "feed_mid",
  "feed_bottom",
  "feed_native_1",
  "feed_native_2",
  "feed_sponsored_story",
  "feed_video_inline",
  "feed_carousel",
  "homepage_hero",
  "homepage_takeover",
  "homepage_banner",
  "homepage_below_fold",
  "homepage_exit_intent",
  "search_results",
  "search_top",
  "search_right",
  "search_sponsored",
  "pre_roll",
  "mid_roll",
  "post_roll",
  "overlay",
  "companion_banner",
  "outstream_video",
  "in_stream_pause",
  "interstitial",
  "rewarded",
  "splash_screen",
  "modal_center",
  "modal_bottom_sheet",
  "exit_intent_popup",
  "nav_top",
  "nav_bottom",
  "sticky_footer",
  "sticky_header",
  "floating_cta",
  "docked_banner",
  "app_open",
  "app_banner_top",
  "app_banner_bottom",
  "app_interstitial",
  "app_rewarded",
  "app_native_grid",
  "email_header",
  "email_footer",
  "email_inline",
  "email_sidebar",
  "ctv_pre_roll",
  "ctv_mid_roll",
  "ctv_pause_ad",
  "ctv_l_banner",
  "dooh_billboard",
  "dooh_transit",
  "dooh_retail",
  "dooh_cinema",
] as const satisfies readonly AdPlacement[];

const DISPLAY_SIZES: Partial<Record<AdLayout, ReservedSize>> = {
  leaderboard: { width: 728, height: 90 },
  large_leaderboard: { width: 970, height: 90 },
  billboard: { width: 970, height: 250 },
  medium_rectangle: { width: 300, height: 250 },
  large_rectangle: { width: 336, height: 280 },
  half_page: { width: 300, height: 600 },
  wide_skyscraper: { width: 160, height: 600 },
  skyscraper: { width: 120, height: 600 },
  square: { width: 250, height: 250 },
  small_square: { width: 200, height: 200 },
  banner: { width: 468, height: 60 },
  micro_bar: { width: 88, height: 31 },
  button_1: { width: 120, height: 90 },
  button_2: { width: 120, height: 60 },
  panorama: { width: 980, height: 120 },
  triple_widescreen: { width: 700, height: 50 },
  portrait: { width: 300, height: 1050 },
  netboard: { width: 580, height: 400 },
  top_banner: { width: 930, height: 180 },
};

const MIN_VIEWPORTS: Partial<Record<AdLayout, number>> = {
  half_page: 1024,
  wide_skyscraper: 1100,
  skyscraper: 1024,
  portrait: 1200,
  wallpaper: 1280,
};

const ARTICLE_INLINE_DEFAULTS: AdLayout[] = [
  "native_in_content",
  "native_article",
  "native_promoted_story",
  "card_horizontal",
  "card_article",
  "card_rich",
  "card_grid",
  "carousel_standard",
  "story_card",
  "inline_text",
  "low_bandwidth",
  "text_only",
];

const ARTICLE_INLINE_EDITORIAL_DEFAULTS: AdLayout[] = [
  "native_in_content",
  "native_article",
  "card_horizontal",
  "card_article",
  "native_promoted_story",
  "sponsored_article",
  "inline_text",
  "low_bandwidth",
  "text_only",
];

const ARTICLE_INLINE_COMPACT_DEFAULTS: AdLayout[] = [
  "leaderboard",
  "top_banner",
  "inline_text",
  "native_promoted_story",
  "card_horizontal",
  "native_recommendation",
  "low_bandwidth",
  "text_only",
];

const ARTICLE_INLINE_IMMERSIVE_DEFAULTS: AdLayout[] = [
  "story_card",
  "carousel_standard",
  "carousel_story",
  "card_grid",
  "native_feed",
  "interactive_form",
  "card_rich",
  "native_promoted_story",
  "low_bandwidth",
  "text_only",
];

const ARTICLE_CHROME_DEFAULTS: AdLayout[] = [
  "hero_split",
  "hero_centered",
  "billboard",
  "panorama",
  "sponsored_section",
  "native_feed",
  "leaderboard",
  "top_banner",
];

const SIDEBAR_DEFAULTS: AdLayout[] = [
  "medium_rectangle",
  "large_rectangle",
  "square",
  "half_page",
  "wide_skyscraper",
  "portrait",
  "card_profile",
  "card_rich",
  "card_product",
  "sponsored_section",
  "accessible_rich",
  "low_bandwidth",
  "text_only",
];

const FEED_DEFAULTS: AdLayout[] = [
  "native_feed",
  "native_recommendation",
  "card_grid",
  "card_list",
  "card_horizontal",
  "carousel_standard",
  "carousel_product",
  "story_card",
  "native_product_list",
  "video_thumbnail_list",
  "low_bandwidth",
];

const SEARCH_DEFAULTS: AdLayout[] = [
  "native_search",
  "inline_text",
  "native_recommendation",
  "card_article",
  "text_only",
];

const VIDEO_PLACEMENT_DEFAULTS: AdLayout[] = [
  "video_pre_roll",
  "video_mid_roll",
  "video_outstream",
  "video_companion",
  "video_thumbnail_list",
  "reel_overlay",
  "story_card",
  "low_bandwidth",
];

const INTERSTITIAL_DEFAULTS: AdLayout[] = [
  "interstitial_full",
  "interstitial_partial",
  "lightbox",
  "pushdown",
  "floating_box",
  "hero_centered",
];

const NAV_DEFAULTS: AdLayout[] = [
  "sticky_header",
  "sticky_footer",
  "floating_box",
  "banner",
  "leaderboard",
  "inline_text",
];

const APP_DEFAULTS: AdLayout[] = [
  "app_install_banner",
  "app_install_interstitial",
  "app_engagement",
  "smart_app_banner",
  "card_app_install",
  "native_feed",
];

const EMAIL_DEFAULTS: AdLayout[] = [
  "email_masthead",
  "email_mid_article",
  "email_footer_strip",
  "email_product_block",
  "sponsored_newsletter",
];

const SCREEN_DEFAULTS: AdLayout[] = [
  "ctv_display",
  "ctv_interactive",
  "dooh_static",
  "dooh_dynamic",
  "dooh_trigger",
  "billboard",
];

function uniq<T>(values: readonly T[]): T[] {
  return Array.from(new Set(values));
}

function isOneOf(layout: AdLayout, collection: readonly AdLayout[]): boolean {
  return collection.includes(layout);
}

export function getPlacementDefaults(placement: AdPlacement): AdLayout[] {
  if (placement === "article_inline_1") return ARTICLE_INLINE_EDITORIAL_DEFAULTS;
  if (placement === "article_inline_2") return ARTICLE_INLINE_COMPACT_DEFAULTS;
  if (placement === "article_inline_3") return ARTICLE_INLINE_IMMERSIVE_DEFAULTS;
  if (placement.startsWith("article_inline")) return ARTICLE_INLINE_DEFAULTS;
  if (placement.startsWith("article_")) return placement === "article_top" ? ARTICLE_CHROME_DEFAULTS : uniq([...ARTICLE_INLINE_DEFAULTS, ...ARTICLE_CHROME_DEFAULTS]);
  if (placement.startsWith("sidebar")) return SIDEBAR_DEFAULTS;
  if (placement.startsWith("feed")) return FEED_DEFAULTS;
  if (placement.startsWith("homepage")) return ARTICLE_CHROME_DEFAULTS;
  if (placement.startsWith("search")) return SEARCH_DEFAULTS;
  if (placement === "pre_roll" || placement === "mid_roll" || placement === "post_roll" || placement === "overlay" || placement === "companion_banner" || placement === "outstream_video" || placement === "in_stream_pause") {
    return VIDEO_PLACEMENT_DEFAULTS;
  }
  if (placement === "interstitial" || placement === "rewarded" || placement === "splash_screen" || placement === "modal_center" || placement === "modal_bottom_sheet" || placement === "exit_intent_popup") {
    return INTERSTITIAL_DEFAULTS;
  }
  if (placement === "nav_top" || placement === "nav_bottom" || placement === "sticky_footer" || placement === "sticky_header" || placement === "floating_cta" || placement === "docked_banner") {
    return NAV_DEFAULTS;
  }
  if (placement.startsWith("app_")) return APP_DEFAULTS;
  if (placement.startsWith("email_")) return EMAIL_DEFAULTS;
  if (placement.startsWith("ctv_") || placement.startsWith("dooh_")) return SCREEN_DEFAULTS;
  return ["native_feed", "inline_text", "low_bandwidth", "text_only"];
}

function preferredPlacementsForLayout(layout: AdLayout): AdPlacement[] {
  if (isOneOf(layout, HERO_LAYOUTS)) {
    return ["homepage_hero", "homepage_takeover", "article_top", "article_pre_headline", "homepage_banner"];
  }
  if (layout === "inline_text" || layout === "text_only" || layout === "low_bandwidth") {
    return ["article_inline_1", "article_inline_2", "article_inline_3", "search_sponsored", "sticky_footer", "email_footer"];
  }
  if (isOneOf(layout, NATIVE_LAYOUTS)) {
    return ["article_inline_1", "article_inline_2", "article_inline_3", "feed_item", "feed_native_1", "feed_native_2", "search_results"];
  }
  if (isOneOf(layout, CARD_LAYOUTS) || isOneOf(layout, CAROUSEL_LAYOUTS)) {
    return ["feed_item", "feed_top", "feed_mid", "article_inline_2", "article_inline_3", "app_native_grid"];
  }
  if (layout === "medium_rectangle" || layout === "large_rectangle" || layout === "half_page" || layout === "wide_skyscraper" || layout === "portrait" || layout === "square") {
    return ["sidebar_top", "sidebar_mid", "sidebar_sticky", "sidebar_bottom"];
  }
  if (isOneOf(layout, STORY_LAYOUTS)) {
    return ["feed_sponsored_story", "feed_item", "article_inline_3", "sidebar_rail_right"];
  }
  if (isOneOf(layout, VIDEO_LAYOUTS)) {
    return ["feed_video_inline", "pre_roll", "mid_roll", "post_roll", "outstream_video", "ctv_pre_roll", "ctv_mid_roll"];
  }
  if (isOneOf(layout, INTERACTIVE_LAYOUTS)) {
    return ["article_inline_2", "feed_item", "homepage_below_fold", "modal_center"];
  }
  if (isOneOf(layout, SPONSORED_LAYOUTS)) {
    return ["article_top", "article_mid", "feed_sponsored_story", "homepage_banner", "email_inline"];
  }
  if (isOneOf(layout, COMMERCE_LAYOUTS) || isOneOf(layout, APP_LAYOUTS)) {
    return ["feed_item", "feed_carousel", "app_native_grid", "article_inline_2", "sidebar_mid"];
  }
  if (isOneOf(layout, EMAIL_LAYOUTS)) {
    return ["email_header", "email_inline", "email_footer", "email_sidebar"];
  }
  if (isOneOf(layout, SCREEN_LAYOUTS)) {
    return ["ctv_pre_roll", "ctv_mid_roll", "ctv_pause_ad", "dooh_billboard", "dooh_retail", "dooh_cinema"];
  }
  return ["article_mid", "feed_item", "sidebar_top"];
}

function inferCategory(layout: AdLayout): LayoutSpec["category"] {
  if (isOneOf(layout, HERO_LAYOUTS)) return "hero";
  if (isOneOf(layout, NATIVE_LAYOUTS) || layout === "accessible_rich" || layout === "low_bandwidth" || layout === "text_only") return "native";
  if (isOneOf(layout, CARD_LAYOUTS) || isOneOf(layout, CAROUSEL_LAYOUTS) || isOneOf(layout, COMMERCE_LAYOUTS) || isOneOf(layout, APP_LAYOUTS)) return "card";
  if (isOneOf(layout, VIDEO_LAYOUTS) || layout === "vertical_video") return "video";
  if (isOneOf(layout, INTERACTIVE_LAYOUTS)) return "interactive";
  if (layout === "fallback_placeholder") return "fallback";
  return "display";
}

function inferFamily(layout: AdLayout): LayoutSpec["family"] {
  if (isOneOf(layout, HERO_LAYOUTS)) return "hero";
  if (isOneOf(layout, NATIVE_LAYOUTS)) return "native";
  if (layout === "accessible_rich" || layout === "low_bandwidth" || layout === "text_only" || layout === "fallback_placeholder") return "fallback";
  if (layout === "medium_rectangle" || layout === "large_rectangle" || layout === "half_page" || layout === "wide_skyscraper" || layout === "portrait" || layout === "square" || layout === "small_square" || layout === "skyscraper") return "sidebar";
  if (isOneOf(layout, STORY_LAYOUTS)) return "story";
  if (isOneOf(layout, VIDEO_LAYOUTS)) return "video";
  if (isOneOf(layout, INTERACTIVE_LAYOUTS)) return "interactive";
  if (isOneOf(layout, CAROUSEL_LAYOUTS)) return "carousel";
  if (isOneOf(layout, SPONSORED_LAYOUTS)) return "sponsored";
  if (isOneOf(layout, COMMERCE_LAYOUTS) || isOneOf(layout, APP_LAYOUTS)) return "commerce";
  if (isOneOf(layout, EMAIL_LAYOUTS)) return "email";
  return "display";
}

function inferOrientation(layout: AdLayout): LayoutSpec["orientation"] {
  if (
    layout === "half_page" ||
    layout === "wide_skyscraper" ||
    layout === "skyscraper" ||
    layout === "portrait" ||
    isOneOf(layout, STORY_LAYOUTS)
  ) {
    return "vertical";
  }
  if (layout === "square" || layout === "small_square") return "square";
  if (
    layout === "leaderboard" ||
    layout === "large_leaderboard" ||
    layout === "billboard" ||
    layout === "banner" ||
    layout === "panorama" ||
    layout === "top_banner" ||
    layout === "micro_bar" ||
    layout === "button_1" ||
    layout === "button_2" ||
    layout === "triple_widescreen" ||
    layout === "inline_text" ||
    layout === "email_footer_strip"
  ) {
    return "horizontal";
  }
  return "fluid";
}

function inferTemplate(layout: AdLayout): LayoutSpec["template"] {
  if (isOneOf(layout, HERO_LAYOUTS) || layout === "interstitial_full" || layout === "interstitial_partial" || layout === "lightbox" || layout === "wallpaper") {
    return "hero";
  }
  if (layout === "inline_text" || layout === "text_only" || layout === "low_bandwidth" || layout === "email_footer_strip" || layout === "micro_bar") {
    return "ticker";
  }
  if (
    layout === "leaderboard" ||
    layout === "large_leaderboard" ||
    layout === "billboard" ||
    layout === "banner" ||
    layout === "panorama" ||
    layout === "top_banner" ||
    layout === "sticky_footer" ||
    layout === "sticky_header" ||
    layout === "expandable_banner" ||
    layout === "toproll" ||
    layout === "programmatic_display"
  ) {
    return "ribbon";
  }
  if (isOneOf(layout, STORY_LAYOUTS) || layout === "half_page" || layout === "wide_skyscraper" || layout === "skyscraper" || layout === "portrait" || layout === "expandable_half_page") {
    return "story";
  }
  if (isOneOf(layout, VIDEO_LAYOUTS)) return "video";
  if (isOneOf(layout, CAROUSEL_LAYOUTS)) return "carousel";
  if (isOneOf(layout, INTERACTIVE_LAYOUTS)) return "interactive";
  if (
    layout === "native_article" ||
    layout === "native_feed" ||
    layout === "native_search" ||
    layout === "native_recommendation" ||
    layout === "native_in_content" ||
    layout === "native_promoted_story" ||
    layout === "native_ad_choice" ||
    layout === "card_horizontal" ||
    layout === "card_list" ||
    layout === "sponsored_article" ||
    layout === "sponsored_section" ||
    layout === "sponsored_newsletter" ||
    layout === "sponsored_event" ||
    layout === "sponsored_glossary" ||
    layout === "email_mid_article" ||
    layout === "contextual_display"
  ) {
    return "split";
  }
  return "poster";
}

function inferDensity(layout: AdLayout): LayoutSpec["density"] {
  const template = inferTemplate(layout);
  if (template === "ticker" || template === "ribbon") return "compact";
  if (template === "hero" || template === "story" || template === "video") return "immersive";
  return "standard";
}

function inferReservedSize(layout: AdLayout): ReservedSize {
  if (DISPLAY_SIZES[layout]) return DISPLAY_SIZES[layout] as ReservedSize;

  const family = inferFamily(layout);
  const template = inferTemplate(layout);
  const orientation = inferOrientation(layout);

  if (family === "hero") return { width: "fluid", height: 420, aspectRatio: "16:9" };
  if (template === "ticker") return { width: "fluid", height: 84, aspectRatio: "12:1" };
  if (template === "ribbon") return { width: "fluid", height: 180, aspectRatio: "16:4" };
  if (template === "story") return orientation === "vertical" ? { width: "fluid", height: 520, aspectRatio: "9:16" } : { width: "fluid", height: 420, aspectRatio: "4:5" };
  if (template === "video") return { width: "fluid", height: 360, aspectRatio: "16:9" };
  if (template === "carousel") return { width: "fluid", height: 260, aspectRatio: "16:9" };
  if (template === "interactive") return { width: "fluid", height: 320, aspectRatio: "16:9" };
  if (family === "email") return { width: "fluid", height: 160, aspectRatio: "3:1" };
  if (family === "commerce") return { width: "fluid", height: 320, aspectRatio: "4:5" };
  if (orientation === "square") return { width: "fluid", height: 250, aspectRatio: "1:1" };
  if (template === "split") return { width: "fluid", height: 180, aspectRatio: "16:6" };
  return { width: "fluid", height: 260, aspectRatio: "4:3" };
}

function inferFallbackOrder(layout: AdLayout): AdLayout[] {
  const family = inferFamily(layout);
  if (family === "hero") return ["hero_split", "native_in_content", "inline_text", "low_bandwidth", "text_only"];
  if (family === "video") return ["video_outstream", "story_card", "native_feed", "inline_text", "low_bandwidth"];
  if (family === "carousel") return ["carousel_standard", "card_grid", "native_feed", "low_bandwidth"];
  if (family === "sidebar") return [layout, "medium_rectangle", "card_profile", "inline_text", "low_bandwidth"];
  if (family === "commerce") return [layout, "card_product", "native_product_list", "inline_text", "low_bandwidth"];
  if (family === "sponsored") return [layout, "native_article", "native_feed", "inline_text", "low_bandwidth"];
  if (family === "interactive") return [layout, "native_feed", "card_grid", "inline_text", "low_bandwidth"];
  if (family === "email") return [layout, "native_article", "inline_text", "text_only"];
  if (family === "fallback") return [layout, "low_bandwidth", "text_only", "inline_text"];
  return [layout, "native_feed", "inline_text", "low_bandwidth", "text_only"];
}

function isLowBandwidthSafe(layout: AdLayout): boolean {
  return (
    layout === "inline_text" ||
    layout === "text_only" ||
    layout === "low_bandwidth" ||
    layout === "accessible_rich" ||
    layout === "email_footer_strip" ||
    layout === "email_mid_article" ||
    layout === "sponsored_podcast" ||
    layout === "native_article" ||
    layout === "native_in_content"
  );
}

function requiresImage(layout: AdLayout): boolean {
  return (
    layout === "shoppable_image" ||
    layout === "wallpaper" ||
    layout === "hero_cinematic" ||
    layout === "hero_mosaic" ||
    layout === "card_video_thumb"
  );
}

function requiresVideo(layout: AdLayout): boolean {
  return isOneOf(layout, VIDEO_LAYOUTS) || layout === "vertical_video" || layout === "shoppable_video";
}

export function getLayoutSpec(layout: AdLayout, placementHint?: AdPlacement): LayoutSpec {
  const placementDefaults = placementHint ? getPlacementDefaults(placementHint) : [];
  return {
    layout,
    category: inferCategory(layout),
    family: inferFamily(layout),
    orientation: inferOrientation(layout),
    template: inferTemplate(layout),
    density: inferDensity(layout),
    reservedSize: inferReservedSize(layout),
    preferredPlacements: uniq([...preferredPlacementsForLayout(layout), ...placementDefaults]),
    lowBandwidthSafe: isLowBandwidthSafe(layout),
    requiresImage: requiresImage(layout),
    requiresVideo: requiresVideo(layout),
    minViewportWidth: MIN_VIEWPORTS[layout],
    fallbackOrder: inferFallbackOrder(layout),
  };
}

export const LAYOUT_CATALOG: Record<AdLayout, LayoutSpec> = Object.fromEntries(
  ALL_LAYOUTS.map((layout) => [layout, getLayoutSpec(layout)]),
) as Record<AdLayout, LayoutSpec>;
