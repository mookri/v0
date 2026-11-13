import { describe, expect, it } from 'vitest';
import {
  SACRED_EMBER_PALETTE,
  assertValidSacredEmberPairing,
  containsDisallowedGreen,
  contrastRatio,
  isWcagAaTextContrast,
  sacredEmberCssVariables,
} from './index';

describe('@navdhya/design-tokens Sacred Ember system (T-AP06-01)', () => {
  it('locks the 6 canonical Sacred Ember hex tokens', () => {
    expect(SACRED_EMBER_PALETTE).toEqual({
      ivory: '#FFF7ED',
      aubergine: '#2C2033',
      flame: '#F16A24',
      sand: '#EFE3D3',
      deepEmber: '#B74717',
      deepText: '#2A2421',
    });
  });

  it('meets WCAG AA contrast for approved text pairings and rejects #F16A24 body text on #FFF7ED', () => {
    expect(isWcagAaTextContrast(SACRED_EMBER_PALETTE.aubergine, SACRED_EMBER_PALETTE.ivory)).toBe(true);
    expect(isWcagAaTextContrast(SACRED_EMBER_PALETTE.deepText, SACRED_EMBER_PALETTE.ivory)).toBe(true);
    expect(isWcagAaTextContrast(SACRED_EMBER_PALETTE.deepEmber, SACRED_EMBER_PALETTE.ivory)).toBe(true);
    expect(isWcagAaTextContrast(SACRED_EMBER_PALETTE.ivory, SACRED_EMBER_PALETTE.aubergine)).toBe(true);

    expect(contrastRatio(SACRED_EMBER_PALETTE.flame, SACRED_EMBER_PALETTE.ivory)).toBeLessThan(4.5);
    expect(() =>
      assertValidSacredEmberPairing(SACRED_EMBER_PALETTE.flame, SACRED_EMBER_PALETTE.ivory),
    ).toThrow(/Disallowed Sacred Ember pairing/);

    expect(() =>
      assertValidSacredEmberPairing(SACRED_EMBER_PALETTE.deepEmber, SACRED_EMBER_PALETTE.ivory),
    ).not.toThrow();
  });

  it('detects disallowed green Tailwind classes and green hex values', () => {
    expect(containsDisallowedGreen('className="text-green-600 bg-white"')).toBe(true);
    expect(containsDisallowedGreen('style={{ color: "#10b981" }}')).toBe(true);
    expect(containsDisallowedGreen('className="bg-[#FFF7ED] text-[#2C2033] border-[#EFE3D3]"')).toBe(false);
  });

  it('generates CSS custom properties for root styles', () => {
    const css = sacredEmberCssVariables();
    expect(css).toContain('--navdhya-ivory: #FFF7ED;');
    expect(css).toContain('--navdhya-aubergine: #2C2033;');
  });

  it('asserts apps/web/styles/globals.css contains zero disallowed green tokens', async () => {
    const fs = await import('node:fs');
    const path = await import('node:path');
    const globalsPath = path.resolve(__dirname, '../../../apps/web/styles/globals.css');
    if (fs.existsSync(globalsPath)) {
      const content = fs.readFileSync(globalsPath, 'utf-8');
      expect(containsDisallowedGreen(content)).toBe(false);
      expect(content).not.toContain('.ambient-glow-emerald');
      expect(content).not.toMatch(/--primary:\s*oklch\([^)]*158\.4\)/);
    }
  });
});

