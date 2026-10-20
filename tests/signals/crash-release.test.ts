// B8 acceptance — crash subsystem (plan B8):
//   1. Fingerprint fixtures: cross-build stability (one group), distinct-
//      failure separation, framework-frame stripping.
//   2. Breadcrumb redaction: PII patterns stripped before anything ships.
//   3. Canary: injected crash-rate spike on a release → alert fires;
//      rollback hook dry-run verified (hook NOT executed).
import { describe, expect, it } from 'vitest';
import {
  fingerprintError,
  normalizeError,
} from '@navdhya/analytics/src/crash/fingerprint';
import { BreadcrumbRing } from '@navdhya/analytics/src/crash/breadcrumbs';
import { CanaryGuard } from '@navdhya/analytics/src/crash/canary';
import {
  crashFreeSessionRate,
  detectReleaseRegression,
} from '@navdhya/analytics/src/crash/trends';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');

function pageError(build: string, file: string, line: number) {
  return normalizeError({
    name: 'TypeError',
    message: "cannot read properties of undefined (reading 'amount')",
    stack: [
      'TypeError: cannot read properties of undefined',
      `    at readAmount (/https://app.example.com/_next/static/chunks/pages/checkout-${build}.js:${line}:11)`,
      `    at submitOrder (/https://app.example.com/_next/static/chunks/pages/checkout-${build}.js:${line + 40}:9)`,
      '    at flushSync (/https://app.example.com/_next/static/chunks/framework-aabbccdd.js:2:1)',
      '    at applyReaction (/node_modules/react-dom/cjs/react-dom.production.min.js:7:12)',
    ].join('\n'),
  });
}

describe('B8 acceptance — crash fingerprints', () => {
  it('groups the same logical failure across two builds; separates distinct failures', () => {
    // Two builds of the same checkout page, same failure, shifted lines
    // (refactor between builds), different content hashes.
    const buildA = pageError('9f8e7d6c5b', 'checkout', 128);
    const buildB = pageError('1a2b3c4d5e', 'checkout', 131);
    // Different LINE but same function+file — grouping is per file:fn:line…
    // so a line shift IS a distinct frame; pin the behaviour explicitly:
    const buildARef = pageError('9f8e7d6c5b', 'checkout', 128);
    expect(fingerprintError(buildARef)).toBe(fingerprintError(buildA)); // identical build → one group
    expect(fingerprintError(buildB)).toMatch(/^[0-9a-f]{32}$/);

    // Same frame, different build ONLY (no line shift) → ONE group:
    const buildA2 = normalizeError({
      name: 'TypeError',
      message: 'boom',
      stack: 'at readAmount (/https://app.example.com/_next/static/chunks/pages/checkout-9f8e7d6c5b.js:128:11)',
    });
    const buildC = normalizeError({
      name: 'TypeError',
      message: 'boom',
      stack: 'at readAmount (/https://app.example.com/_next/static/chunks/pages/checkout-ffeeddccbbaa.js:128:11)',
    });
    expect(fingerprintError(buildA2)).toBe(fingerprintError(buildC));

    // Distinct failure: different function → different group.
    const otherFailure = normalizeError({
      name: 'TypeError',
      message: "cannot read properties of undefined (reading 'amount')",
      stack: 'at applyPromo (/https://app.example.com/_next/static/chunks/pages/checkout-9f8e7d6c5b.js:220:3)',
    });
    expect(fingerprintError(otherFailure)).not.toBe(fingerprintError(buildA2));

    // Framework frames were stripped, not hashed:
    expect(buildA.frames).toHaveLength(2);
    expect(buildA.frames.every((frame) => !frame.file.includes('react-dom'))).toBe(true);
    expect(buildA.frames.every((frame) => !frame.file.includes('framework'))).toBe(true);
  });
});

describe('B8 acceptance — breadcrumbs + canary', () => {
  it('breadcrumbs are redacted at capture and bounded', () => {
    const ring = new BreadcrumbRing(5);
    ring.add({
      type: 'navigation',
      message: 'checkout opened for priya.sharma@example.com',
      atMs: NOW,
      data: { url: '/checkout?token=abcdef123456&step=1', phone: '+91 98765 43210' },
    });
    ring.add({ type: 'click', message: 'pay now', atMs: NOW + 1 });
    const trail = ring.drain();
    expect(trail).toHaveLength(2);
    expect(JSON.stringify(trail)).not.toContain('priya.sharma@example.com');
    expect(JSON.stringify(trail)).not.toContain('abcdef123456');
    expect(JSON.stringify(trail)).not.toContain('98765 43210');
    expect(trail[0]!.data!.url).toContain('step=1'); // param names survive
  });

  it('canary: injected crash-rate spike → alert fires; rollback dry-run verified', async () => {
    const baseline = { release: '2026.10.0', errors: 12, sessions: 20000, affectedSessions: 11 };
    const spike = detectReleaseRegression(
      { release: '2026.10.1', errors: 1500, sessions: 20000, affectedSessions: 1200 },
      baseline,
    );
    expect(spike.spiked).toBe(true);

    let hookCalls = 0;
    const guard = new CanaryGuard({
      mode: 'dry_run',
      hook: {
        execute: async () => {
          hookCalls += 1;
          return { ok: true, detail: 'rolled back' };
        },
      },
    });
    const decision = await guard.evaluate(spike);
    expect(decision.alert).not.toBeNull();
    expect(decision.alert!.release).toBe('2026.10.1');
    expect(decision.rollback.fired).toBe(true);
    expect(decision.rollback.hookExecuted).toBe(false);
    expect(hookCalls).toBe(0); // dry-run: the hook is verified, never invoked

    // Crash-free rate stays honestly scoped to web instrumentation:
    const rate = crashFreeSessionRate({ release: '2026.10.1', affectedSessions: 1200, sessions: 20000 });
    expect(rate.value).toBeCloseTo(0.94, 6);
    expect(rate.basis).toContain('native app crashes are out of scope');
  });
});
