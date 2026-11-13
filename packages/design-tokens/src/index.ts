/**
 * Navdhya Sacred Ember Locked Colour System (AGENTS.md)
 * - No green anywhere in the core identity.
 * - Orange (#F16A24) is a signature motif, never body text on Warm Ivory (#FFF7ED).
 */
export const SACRED_EMBER_PALETTE = {
  ivory: '#FFF7ED',
  aubergine: '#2C2033',
  flame: '#F16A24',
  sand: '#EFE3D3',
  deepEmber: '#B74717',
  deepText: '#2A2421',
} as const;

export type SacredEmberToken = keyof typeof SACRED_EMBER_PALETTE;

export const SACRED_EMBER_SEMANTIC = {
  canvas: SACRED_EMBER_PALETTE.ivory,
  surfaceMuted: SACRED_EMBER_PALETTE.sand,
  surfaceInverse: SACRED_EMBER_PALETTE.aubergine,
  textPrimary: SACRED_EMBER_PALETTE.deepText,
  textHeading: SACRED_EMBER_PALETTE.aubergine,
  textInverse: SACRED_EMBER_PALETTE.ivory,
  accentPrimary: SACRED_EMBER_PALETTE.flame,
  accentHighContrast: SACRED_EMBER_PALETTE.deepEmber,
  borderSubtle: SACRED_EMBER_PALETTE.sand,
  borderFocus: SACRED_EMBER_PALETTE.flame,
} as const;

export const DISALLOWED_GREEN_PATTERN =
  /\b(?:bg|text|border|ring|from|to|via)-(?:green|emerald|teal|lime)-\d{2,3}\b|#(?:10b981|22c55e|16a34a|15803d|059669|047857|4ade80|86efac|00ff00|008000)\b/i;

export function containsDisallowedGreen(source: string): boolean {
  return DISALLOWED_GREEN_PATTERN.test(source);
}

function parseHexRgb(hex: string): [number, number, number] {
  const clean = hex.trim().replace(/^#/, '');
  if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
    throw new Error(`Invalid 6-digit hex color: ${hex}`);
  }
  return [
    Number.parseInt(clean.slice(0, 2), 16),
    Number.parseInt(clean.slice(2, 4), 16),
    Number.parseInt(clean.slice(4, 6), 16),
  ];
}

function srgbToLinear(channel255: number): number {
  const c = channel255 / 255;
  return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

export function relativeLuminance(hex: string): number {
  const [r, g, b] = parseHexRgb(hex);
  return 0.2126 * srgbToLinear(r) + 0.7152 * srgbToLinear(g) + 0.0722 * srgbToLinear(b);
}

export function contrastRatio(foregroundHex: string, backgroundHex: string): number {
  const l1 = relativeLuminance(foregroundHex);
  const l2 = relativeLuminance(backgroundHex);
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

export function isWcagAaTextContrast(foregroundHex: string, backgroundHex: string, largeText = false): boolean {
  const threshold = largeText ? 3.0 : 4.5;
  return contrastRatio(foregroundHex, backgroundHex) >= threshold;
}

export function assertValidSacredEmberPairing(foregroundHex: string, backgroundHex: string, largeText = false): void {
  const fg = foregroundHex.toUpperCase();
  const bg = backgroundHex.toUpperCase();
  if (fg === SACRED_EMBER_PALETTE.flame && bg === SACRED_EMBER_PALETTE.ivory && !largeText) {
    throw new Error(
      'Disallowed Sacred Ember pairing: Navdhya Flame (#F16A24) body text on Warm Ivory (#FFF7ED) fails WCAG AA contrast; use Deep Ember (#B74717), Aubergine Ink (#2C2033), or Deep Text (#2A2421).',
    );
  }
  if (!isWcagAaTextContrast(fg, bg, largeText)) {
    throw new Error(
      `Insufficient WCAG AA contrast (${contrastRatio(fg, bg).toFixed(2)}:1) for ${fg} on ${bg}.`,
    );
  }
}

export function sacredEmberCssVariables(): string {
  return [
    ':root {',
    `  --navdhya-ivory: ${SACRED_EMBER_PALETTE.ivory};`,
    `  --navdhya-aubergine: ${SACRED_EMBER_PALETTE.aubergine};`,
    `  --navdhya-flame: ${SACRED_EMBER_PALETTE.flame};`,
    `  --navdhya-sand: ${SACRED_EMBER_PALETTE.sand};`,
    `  --navdhya-deep-ember: ${SACRED_EMBER_PALETTE.deepEmber};`,
    `  --navdhya-deep-text: ${SACRED_EMBER_PALETTE.deepText};`,
    '}',
  ].join('\n');
}

export const sacredEmberTailwindColors = {
  'sacred-ivory': SACRED_EMBER_PALETTE.ivory,
  'sacred-aubergine': SACRED_EMBER_PALETTE.aubergine,
  'sacred-flame': SACRED_EMBER_PALETTE.flame,
  'sacred-sand': SACRED_EMBER_PALETTE.sand,
  'sacred-ember': SACRED_EMBER_PALETTE.deepEmber,
  'sacred-ink': SACRED_EMBER_PALETTE.deepText,
} as const;

