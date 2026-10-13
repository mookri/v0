// Fingerprint units (plan B8 acceptance item 3): cross-build stability,
// distinct-failure separation, framework-frame stripping.

import { describe, expect, it } from 'vitest';
import {
  MAX_PARSED_FRAMES,
  fingerprintError,
  isFrameworkFrame,
  normalizeError,
  parseStack,
  stripBuildArtifacts,
} from '@navdhya/analytics/src/crash/fingerprint';

function stackFor(file: string, fn: string, line: number): string {
  return `TypeError: cannot read property 'x'\n    at ${fn} (${file}:${line}:17)\n    at render (https://example.com/_next/static/chunks/main-9f8e7d6a.js:1:2)`;
}

describe('crash fingerprinting (B8)', () => {
  it('GROUPS the same logical failure across two builds into one group', () => {
    const buildA = normalizeError({
      name: 'TypeError',
      message: "cannot read property 'x'",
      stack: stackFor('https://example.com/_next/static/chunks/pages/checkout-a1b2c3d4e5.js', 'submitForm', 128),
    });
    const buildB = normalizeError({
      name: 'TypeError',
      message: "cannot read property 'x' (slightly different wording after refactor)",
      stack: stackFor('https://example.com/_next/static/chunks/pages/checkout-77aa88bb99cc.js', 'submitForm', 128),
    });
    expect(fingerprintError(buildA)).toBe(fingerprintError(buildB)); // one group
    expect(stripBuildArtifacts('checkout-a1b2c3d4e5.js')).toBe('checkout-<hash>.js');
    expect(stripBuildArtifacts('main.9f8e7d6a.js')).toBe('main.<hash>.js');
  });

  it('SEPARATES two distinct failures', () => {
    const failureA = normalizeError({
      name: 'TypeError',
      message: 'boom',
      stack: 'at submitForm (/app/src/checkout.ts:128:17)',
    });
    const failureB = normalizeError({
      name: 'TypeError',
      message: 'boom',
      stack: 'at applyDiscount (/app/src/discounts.ts:42:9)',
    });
    // Same message, different frame → different group.
    expect(fingerprintError(failureA)).not.toBe(fingerprintError(failureB));
    // Different error class, same frame → different group.
    const failureC = normalizeError({
      name: 'RangeError',
      message: 'boom',
      stack: 'at submitForm (/app/src/checkout.ts:128:17)',
    });
    expect(fingerprintError(failureC)).not.toBe(fingerprintError(failureA));
  });

  it('strips framework frames before hashing', () => {
    expect(isFrameworkFrame('/node_modules/react-dom/cjs/react-dom.production.min.js')).toBe(true);
    expect(isFrameworkFrame('https://x/_next/static/chunks/framework-abc123.js')).toBe(true);
    expect(isFrameworkFrame('/app/src/checkout.ts')).toBe(false);

    const normalized = normalizeError({
      name: 'TypeError',
      message: 'boom',
      stack: [
        'at flushQueue (/node_modules/react-dom/cjs/react-dom.production.min.js:1:2)',
        'at submitForm (/app/src/checkout.ts:128:17)',
      ].join('\n'),
    });
    expect(normalized.frames).toHaveLength(1);
    expect(normalized.frames[0]!.file).toBe('/app/src/checkout.ts');
  });

  it('parses V8 stacks (both styles) and bounds payload size', () => {
    const frames = parseStack(
      [
        'Error: x',
        '    at fn (file.ts:10:5)',
        '    at https://example.com/chunk.js:3:1',
        '    at not a frame',
      ].join('\n'),
    );
    expect(frames).toEqual([
      { fn: 'fn', file: 'file.ts', line: 10 },
      { fn: '(anonymous)', file: 'https://example.com/chunk.js', line: 3 },
    ]);
    const huge = Array.from({ length: 500 }, (_, i) => `    at f${i} (/app/f${i}.ts:${i}:1)`).join('\n');
    expect(parseStack(huge).length).toBeLessThanOrEqual(MAX_PARSED_FRAMES);
  });

  it('falls back to the error class when only framework frames exist', () => {
    const normalized = normalizeError({
      name: 'ReactError',
      message: 'minified react error #310',
      stack: 'at X (/node_modules/react-dom/index.js:1:1)',
    });
    expect(normalized.frames).toEqual([]);
    expect(fingerprintError(normalized)).toMatch(/^[0-9a-f]{32}$/);
  });
});
