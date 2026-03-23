/* ═══════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  adTokens.ts
   THE SINGLE SOURCE OF TRUTH FOR ALL VISUAL CONSTANTS.

   ARCHITECTURE:
   ┌─────────────────────────────────────────────────────────────────────┐
   │  V (CSS var name registry)  ← TypeScript constants, not strings    │
   │  css(V.x)  → 'var(--ad-x)'  ← use in inline style props           │
   │  AdTheme   → token value map ← change here = all ads update        │
   │  themes.*  → preset themes  ← drop-in brand switching              │
   └─────────────────────────────────────────────────────────────────────┘

   COMPONENT RULE — the lock pattern:
     ✅  style={{ background: css(V.bgFallback) }}   ← token only
     ✅  className="absolute inset-0 overflow-hidden" ← structure only
     ❌  style={{ background: '#0e1a12' }}            ← hardcoded, never
     ❌  className="bg-amber-400 rounded-lg"          ← theme value, never

   WHY IT CAN'T BREAK:
   - Components contain zero visual values
   - Swap theme at <AdProvider theme={purpleTheme}> = instant rebrand
   - Tree-shaking safe — unused themes are not bundled
   ═══════════════════════════════════════════════════════════════════════════ */

// ─────────────────────────────────────────────────────────────────────────────
// V  — CSS variable name registry
// Import this everywhere. Never type '--ad-...' manually.
// ─────────────────────────────────────────────────────────────────────────────

export const V = {
  // ── Accent (primary brand color) ──────────────────────────────────────────
  accent: '--ad-accent',          // CTA bg, badge bg, focus ring
  accentHover: '--ad-accent-hover',    // CTA hover state
  accentFg: '--ad-accent-fg',       // text on accent background

  // ── Backgrounds ───────────────────────────────────────────────────────────
  bgFallback: '--ad-bg-fallback',     // ad bg when no image
  bgCard: '--ad-bg-card',         // card surface
  bgCardDark: '--ad-bg-card-dark',    // card surface in dark mode
  bgBorder: '--ad-bg-border',       // card border / divider
  bgSponsorTag: '--ad-bg-sponsor-tag',  // "Sponsored" chip background

  // ── Text on dark / image overlays (white-on-dark scheme) ─────────────────
  onDark1: '--ad-on-dark-1',       // 100% white — headlines
  onDark2: '--ad-on-dark-2',       // 78% — body copy
  onDark3: '--ad-on-dark-3',       // 55% — secondary text
  onDarkLabel: '--ad-on-dark-label',   // 40% — "SPONSORED" label
  onDarkCta: '--ad-on-dark-cta-bg',  // CTA surface on dark (white card)
  onDarkCtaFg: '--ad-on-dark-cta-fg',  // CTA text on dark (near-black)

  // ── Text on light / bright-image overlays (dark-on-light scheme) ──────────
  onLight1: '--ad-on-light-1',      // primary text
  onLight2: '--ad-on-light-2',      // secondary text
  onLight3: '--ad-on-light-3',      // muted / metadata
  onLightLabel: '--ad-on-light-label',  // "SPONSORED" on light image
  onLightCta: '--ad-on-light-cta-bg', // CTA surface on light image (dark)
  onLightCtaFg: '--ad-on-light-cta-fg', // CTA text on light image (white)

  // ── Success / Verified ────────────────────────────────────────────────────
  success: '--ad-success',         // verified badge, success states

  // ── Overlays (applied over images) ────────────────────────────────────────
  overlayDark: '--ad-overlay-dark',    // bottom-heavy vignette
  overlayDarkHeavy: '--ad-overlay-dark-h', // heavy vignette (story)
  overlaySide: '--ad-overlay-side',    // left-heavy (leaderboard)
  overlayHero: '--ad-overlay-hero',    // dual (hero)
  overlayTop: '--ad-overlay-top',     // top-to-bottom (skyscraper)
  overlayStory: '--ad-overlay-story',   // BUG-08 ADD: triple-layer story gradient (9:16)

  // ── Border radius ─────────────────────────────────────────────────────────
  rSm: '--ad-r-sm',            // 4px  — badges, buttons
  rMd: '--ad-r-md',            // 8px  — cards, images
  rLg: '--ad-r-lg',            // 12px — story, floating
  rXl: '--ad-r-xl',            // 16px — story card outer
  rPill: '--ad-r-pill',          // 9999px — pill chips
  rIcon: '--ad-r-icon',          // 14px — app icons

  // ── Shadows ───────────────────────────────────────────────────────────────
  shadowCard: '--ad-s-card',          // product / grid card
  shadowFloat: '--ad-s-float',         // floating box / sticky
  shadowStory: '--ad-s-story',         // story card
  shadowCta: '--ad-s-cta',           // CTA button lift

  // ── Motion ────────────────────────────────────────────────────────────────
  easeFast: '--ad-ease-fast',       // 150ms ease
  easeBase: '--ad-ease-base',       // 200ms ease
  easeSlow: '--ad-ease-slow',       // 500ms ease
  easeImage: '--ad-ease-image',      // 700ms cubic-bezier image reveal
  easeHover: '--ad-ease-hover',      // hover scale transition

  // ── Typography ────────────────────────────────────────────────────────────
  fontDisplay: '--ad-font-display',    // headline/editorial serif
  fontBody: '--ad-font-body',       // body sans-serif

  // ── Label text (SPONSORED, AD) ────────────────────────────────────────────
  labelSpacing: '--ad-label-spacing',   // letter-spacing for label text
} as const;

// ─────────────────────────────────────────────────────────────────────────────
// css()  — convert a V.x name to a CSS var() reference
// ─────────────────────────────────────────────────────────────────────────────

/** Use in style props: `style={{ color: css(V.onDark1) }}` */
export const css = (varName: string): string => `var(${varName})`;

// ─────────────────────────────────────────────────────────────────────────────
// AdTheme  — the shape of a complete theme
// ─────────────────────────────────────────────────────────────────────────────

export type AdTheme = Record<string, string>;

// ─────────────────────────────────────────────────────────────────────────────
// themes.*  — built-in preset themes
// ─────────────────────────────────────────────────────────────────────────────

/** Dark editorial — default, amber accents, serif headlines */
export const darkTheme: AdTheme = {
  [V.accent]: '#fbbf24',
  [V.accentHover]: '#d97706',
  [V.accentFg]: '#000000',

  [V.bgFallback]: '#0e1a12',
  [V.bgCard]: '#ffffff',
  [V.bgCardDark]: '#111827',
  [V.bgBorder]: '#e5e7eb',
  [V.bgSponsorTag]: 'rgba(0,0,0,0.40)',

  [V.onDark1]: 'rgba(255,255,255,1.0)',
  [V.onDark2]: 'rgba(255,255,255,0.78)',
  [V.onDark3]: 'rgba(255,255,255,0.55)',
  [V.onDarkLabel]: 'rgba(255,255,255,0.40)',
  [V.onDarkCta]: 'rgba(255,255,255,0.95)',
  [V.onDarkCtaFg]: '#0f172a',

  [V.onLight1]: '#111827',
  [V.onLight2]: '#374151',
  [V.onLight3]: '#6b7280',
  [V.onLightLabel]: 'rgba(0,0,0,0.45)',
  [V.onLightCta]: '#0f172a',
  [V.onLightCtaFg]: '#ffffff',

  [V.success]: '#10b981',

  [V.overlayDark]: 'linear-gradient(to top,rgba(0,0,0,.85) 0%,rgba(0,0,0,.10) 60%,transparent 100%)',
  [V.overlayDarkHeavy]: 'linear-gradient(to top,rgba(0,0,0,.92) 0%,rgba(0,0,0,.25) 50%,transparent 100%)',
  [V.overlaySide]: 'linear-gradient(to right,rgba(0,0,0,.70) 0%,rgba(0,0,0,.28) 50%,transparent 100%)',
  [V.overlayHero]: 'linear-gradient(to top,rgba(0,0,0,.78) 0%,rgba(0,0,0,.18) 55%,transparent 100%)',
  [V.overlayTop]: 'linear-gradient(to bottom,transparent 0%,rgba(0,0,0,.28) 55%,rgba(0,0,0,.95) 100%)',
  // BUG-08 FIX: overlayStory was referenced in VerticalAds.tsx but missing from tokens
  [V.overlayStory]: 'linear-gradient(to top,rgba(0,0,0,.92) 0%,rgba(0,0,0,.30) 50%,rgba(0,0,0,.10) 100%)',

  [V.rSm]: '4px',
  [V.rMd]: '8px',
  [V.rLg]: '12px',
  [V.rXl]: '16px',
  [V.rPill]: '9999px',
  [V.rIcon]: '14px',

  [V.shadowCard]: '0 2px 12px rgba(0,0,0,.08)',
  [V.shadowFloat]: '0 8px 32px rgba(0,0,0,.22)',
  [V.shadowStory]: '0 20px 60px rgba(0,0,0,.42)',
  [V.shadowCta]: '0 2px 8px rgba(0,0,0,.16)',

  [V.easeFast]: '150ms ease',
  [V.easeBase]: '200ms ease',
  [V.easeSlow]: '500ms ease',
  [V.easeImage]: '700ms cubic-bezier(0.16,1,0.3,1)',
  [V.easeHover]: '300ms ease',

  [V.fontDisplay]: "ui-serif, Georgia, 'Times New Roman', serif",
  [V.fontBody]: "ui-sans-serif, system-ui, -apple-system, sans-serif",
  [V.labelSpacing]: '0.25em',
};

/** Light minimal — blue accents, clean white cards */
export const lightTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#2563eb',
  [V.accentHover]: '#1d4ed8',
  [V.accentFg]: '#ffffff',
  [V.bgFallback]: '#1e3a5f',
  [V.rSm]: '6px',
  [V.rMd]: '10px',
  [V.rLg]: '14px',
  [V.rXl]: '18px',
  [V.shadowCard]: '0 4px 16px rgba(0,0,0,.10)',
  [V.shadowFloat]: '0 12px 40px rgba(0,0,0,.18)',
};

/** Premium finance — zero radius, muted gold, serif-first */
export const premiumTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#b45309',
  [V.accentHover]: '#92400e',
  [V.accentFg]: '#ffffff',
  [V.bgFallback]: '#1c1917',
  [V.rSm]: '0px',
  [V.rMd]: '0px',
  [V.rLg]: '0px',
  [V.rXl]: '0px',
  [V.rPill]: '0px',
  [V.rIcon]: '0px',
  [V.shadowCard]: '0 1px 3px rgba(0,0,0,.12)',
  [V.shadowFloat]: '0 4px 20px rgba(0,0,0,.30)',
  [V.fontDisplay]: "'Cormorant Garamond', 'Playfair Display', Georgia, serif",
};

/** Vibrant e-commerce — green, generous radius */
export const ecomTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#16a34a',
  [V.accentHover]: '#15803d',
  [V.accentFg]: '#ffffff',
  [V.bgFallback]: '#14532d',
  [V.rSm]: '6px',
  [V.rMd]: '12px',
  [V.rLg]: '16px',
  [V.rXl]: '20px',
  [V.rPill]: '9999px',
};

/** Brand purple — violet, glassmorphic feel */
export const purpleTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#7c3aed',
  [V.accentHover]: '#6d28d9',
  [V.accentFg]: '#ffffff',
  [V.bgFallback]: '#1e1b4b',
  [V.rMd]: '10px',
  [V.rLg]: '14px',
  [V.rXl]: '18px',
  [V.shadowCard]: '0 4px 24px rgba(124,58,237,.15)',
  [V.shadowFloat]: '0 8px 40px rgba(124,58,237,.25)',
};

/** Playful news/media — red, punchy */
export const mediaTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#dc2626',
  [V.accentHover]: '#b91c1c',
  [V.accentFg]: '#ffffff',
  [V.bgFallback]: '#1a0a0a',
  [V.rSm]: '2px',
  [V.rMd]: '4px',
  [V.rLg]: '6px',
  [V.rXl]: '8px',
};

/** Executive modern — sleek enterprise surfaces, cleaner typography, restrained accent */
export const executiveTheme: AdTheme = {
  ...darkTheme,
  [V.accent]: '#0f8b6d',
  [V.accentHover]: '#0c755c',
  [V.accentFg]: '#ffffff',

  [V.bgFallback]: '#10161d',
  [V.bgCard]: '#f8fafc',
  [V.bgCardDark]: '#121a23',
  [V.bgBorder]: 'rgba(15,23,42,0.10)',
  [V.bgSponsorTag]: 'rgba(15,23,42,0.56)',

  [V.onDark1]: 'rgba(255,255,255,0.98)',
  [V.onDark2]: 'rgba(255,255,255,0.82)',
  [V.onDark3]: 'rgba(255,255,255,0.62)',
  [V.onDarkLabel]: 'rgba(255,255,255,0.52)',
  [V.onDarkCta]: '#f8fafc',
  [V.onDarkCtaFg]: '#0f172a',

  [V.onLight1]: '#0f172a',
  [V.onLight2]: '#334155',
  [V.onLight3]: '#64748b',
  [V.onLightLabel]: 'rgba(15,23,42,0.48)',
  [V.onLightCta]: '#0f172a',
  [V.onLightCtaFg]: '#ffffff',

  [V.success]: '#10b981',

  [V.overlayDark]: 'linear-gradient(to top,rgba(8,15,23,.82) 0%,rgba(8,15,23,.18) 58%,transparent 100%)',
  [V.overlayDarkHeavy]: 'linear-gradient(to top,rgba(7,12,18,.92) 0%,rgba(7,12,18,.28) 48%,transparent 100%)',
  [V.overlaySide]: 'linear-gradient(to right,rgba(8,15,23,.78) 0%,rgba(8,15,23,.32) 46%,transparent 100%)',
  [V.overlayHero]: 'linear-gradient(to top,rgba(8,15,23,.76) 0%,rgba(8,15,23,.14) 56%,transparent 100%)',
  [V.overlayTop]: 'linear-gradient(to bottom,transparent 0%,rgba(8,15,23,.24) 50%,rgba(8,15,23,.90) 100%)',
  [V.overlayStory]: 'linear-gradient(to top,rgba(8,15,23,.92) 0%,rgba(8,15,23,.34) 50%,rgba(8,15,23,.08) 100%)',

  [V.rSm]: '3px',
  [V.rMd]: '6px',
  [V.rLg]: '8px',
  [V.rXl]: '10px',
  [V.rPill]: '10px',
  [V.rIcon]: '8px',

  [V.shadowCard]: '0 10px 34px rgba(15,23,42,.08)',
  [V.shadowFloat]: '0 18px 54px rgba(15,23,42,.16)',
  [V.shadowStory]: '0 28px 84px rgba(2,6,23,.34)',
  [V.shadowCta]: '0 8px 24px rgba(15,139,109,.18)',

  [V.easeFast]: '140ms cubic-bezier(0.2,0.8,0.2,1)',
  [V.easeBase]: '220ms cubic-bezier(0.16,1,0.3,1)',
  [V.easeSlow]: '520ms cubic-bezier(0.16,1,0.3,1)',
  [V.easeImage]: '780ms cubic-bezier(0.16,1,0.3,1)',
  [V.easeHover]: '280ms cubic-bezier(0.16,1,0.3,1)',

  [V.fontDisplay]: "var(--font-albert), ui-sans-serif, system-ui, sans-serif",
  [V.fontBody]: "var(--font-albert), ui-sans-serif, system-ui, sans-serif",
  [V.labelSpacing]: '0.18em',
};

// ─────────────────────────────────────────────────────────────────────────────
// Keyframe CSS  — injected once globally, never changes
// ─────────────────────────────────────────────────────────────────────────────

export const AD_KEYFRAMES = `
@keyframes ad-shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
@keyframes ad-fade-in {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes ad-slide-up {
  from { transform: translateY(100%); }
  to   { transform: translateY(0); }
}
@keyframes ad-progress-drain {
  from { width: 100%; }
  to   { width: 0%;   }
}
`.trim();

// ─────────────────────────────────────────────────────────────────────────────
// TextScheme  — "dark" = white text on image | "light" = dark text on image
//
// Resolution priority (highest first):
//   1. ad.creative.textColor explicitly set  →  parse luminance
//   2. ad.creative.bgColor set               →  parse luminance  
//   3. Neither set                           →  "dark" (safe default)
//
// Used by every layout component that renders text over an image.
// ─────────────────────────────────────────────────────────────────────────────

export type TextScheme = "dark" | "light";

/**
 * Parse a hex color to 0-255 luminance (simple average, not perceptual).
 * Returns null if the string isn't a valid hex color.
 */
function hexLuminance(hex: string): number | null {
  const clean = hex.replace("#", "").trim();
  if (clean.length !== 3 && clean.length !== 6) return null;
  const full = clean.length === 3
    ? clean.split("").map(c => c + c).join("")
    : clean;
  const r = parseInt(full.slice(0, 2), 16);
  const g = parseInt(full.slice(2, 4), 16);
  const b = parseInt(full.slice(4, 6), 16);
  if (isNaN(r) || isNaN(g) || isNaN(b)) return null;
  // Perceptual luminance weights (WCAG)
  return 0.299 * r + 0.587 * g + 0.114 * b;
}

/**
 * Derive the correct TextScheme for an ad.
 *
 * @param textColor  ad.creative?.textColor  — explicit override
 * @param bgColor    ad.creative?.bgColor    — fallback bg hint
 *
 * @example
 * const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
 * // Then in JSX:
 * color: scheme === "dark" ? css(V.onDark1) : css(V.onLight1)
 */
export function resolveTextScheme(
  textColor?: string,
  bgColor?: string
): TextScheme {
  // If textColor is explicitly set, use its luminance to decide
  if (textColor) {
    const lum = hexLuminance(textColor);
    if (lum !== null) return lum > 127 ? "dark" : "light";
  }
  // Fall back to bgColor (the fallback background behind the image)
  if (bgColor) {
    const lum = hexLuminance(bgColor);
    if (lum !== null) {
      // A light bgColor = probably a light image = needs dark text
      return lum > 160 ? "light" : "dark";
    }
  }
  // No hint → assume image is dark, use white text (the safe default)
  return "dark";
}

/**
 * Convenience: resolve a full set of text/CTA colours from a TextScheme.
 *
 * @example
 * const T = schemeTokens(resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor));
 * <h3 style={{ color: T.heading }}>...</h3>
 * <span style={{ color: T.label }}>Sponsored</span>
 * <a style={{ background: T.ctaBg, color: T.ctaFg }}>CTA</a>
 */
export function schemeTokens(scheme: TextScheme) {
  const isDark = scheme === "dark";
  return {
    heading: isDark ? css(V.onDark1) : css(V.onLight1),
    body: isDark ? css(V.onDark2) : css(V.onLight2),
    muted: isDark ? css(V.onDark3) : css(V.onLight3),
    label: isDark ? css(V.onDarkLabel) : css(V.onLightLabel),
    ctaBg: isDark ? css(V.onDarkCta) : css(V.onLightCta),
    ctaFg: isDark ? css(V.onDarkCtaFg) : css(V.onLightCtaFg),
  } as const;
}
