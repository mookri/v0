// Crash hygiene units (plan B8): strict redaction, bounded redacted
// breadcrumbs, adaptive sampling bounds.

import { describe, expect, it } from 'vitest';
import {
  REDACTED,
  redactBreadcrumb,
  redactText,
} from '@navdhya/analytics/src/crash/redact';
import { BreadcrumbRing } from '@navdhya/analytics/src/crash/breadcrumbs';
import { ErrorSampler, adaptiveSampleRate } from '@navdhya/analytics/src/crash/sampling';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');

describe('crash redaction (B8)', () => {
  it('strips PII patterns from free text', () => {
    // Synthetic fixture, assembled from parts so no secret scanner mistakes it
    // for a live credential. This is the canonical jwt.io example token; the
    // email, phone, key and card number are equally fake.
    const jwt = [
      'eyJhbGciOiJIUzI1NiJ9',
      'eyJzdWIiOiIxMjM0NTY3ODkwIn0',
      'SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
    ].join('.');
    const text = `failed for rahul@example.com, call +91 98765 43210, token ${jwt}, key sk-live-abcdef1234567890, card 4111 1111 1111 1111`;
    const redacted = redactText(text);
    expect(redacted).not.toContain('rahul@example.com');
    expect(redacted).not.toContain('98765 43210');
    expect(redacted).not.toContain('SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV');
    expect(redacted).not.toContain('sk-live-abcdef1234567890');
    expect(redacted).not.toContain('4111 1111 1111 1111');
    expect(redacted).toContain(REDACTED);
  });

  it('keeps sensitive query PARAM names but strips their values', () => {
    const redacted = redactText('GET /api/book?token=supersecret123&page=2');
    expect(redacted).not.toContain('supersecret123');
    expect(redacted).toContain('token=[redacted]');
    expect(redacted).toContain('page=2');
  });

  it('leaves Luhn-failing digit runs (ids, timestamps) alone — redaction stays debuggable', () => {
    const orderId = '9780306406157'; // 13 digits, fails Luhn
    const redacted = redactText(`order ${orderId} at 1760356800000`);
    expect(redacted).toContain(orderId);
    expect(redacted).toContain('1760356800000');
  });

  it('redacts breadcrumb message AND data at capture time', () => {
    const crumb = redactBreadcrumb({
      type: 'navigation',
      message: 'opened checkout for user@example.com',
      atMs: NOW,
      data: { url: '/cart?session=abc123secret', attempt: '2' },
    });
    expect(crumb.message).not.toContain('user@example.com');
    expect(crumb.data!.url).not.toContain('abc123secret');
    expect(crumb.data!.attempt).toBe('2');
  });
});

describe('breadcrumb ring (B8)', () => {
  it('is bounded, evicts oldest, and drains once per error', () => {
    const ring = new BreadcrumbRing(3);
    for (let index = 0; index < 5; index += 1) {
      ring.add({ type: 'click', message: `step-${index}`, atMs: NOW + index });
    }
    expect(ring.size).toBe(3);
    expect(ring.peek().map((crumb) => crumb.message)).toEqual(['step-2', 'step-3', 'step-4']);
    const drained = ring.drain();
    expect(drained).toHaveLength(3);
    expect(ring.size).toBe(0); // attached once, then cleared
    expect(() => new BreadcrumbRing(0)).toThrow(/positive integer/);
  });
});

describe('adaptive sampling (B8)', () => {
  it('pure rate function clamps to [floor, 1] and targets the configured rate', () => {
    expect(adaptiveSampleRate(0, 60)).toBe(1);
    expect(adaptiveSampleRate(30, 60)).toBe(1); // under target → ship everything
    expect(adaptiveSampleRate(600, 60)).toBeCloseTo(0.1, 6);
    expect(adaptiveSampleRate(60000, 60)).toBe(0.01); // floor
  });

  it('sampler adapts under a burst and recovers, using the injected clock/random', () => {
    let nowMs = NOW;
    const sampler = new ErrorSampler({
      targetPerMinute: 60,
      windowMs: 60_000,
      nowMs: () => nowMs,
      random: () => 0.5, // deterministic decision point
    });
    expect(sampler.shouldSample()).toBe(true); // first error, rate ~0 → probability 1
    // Burst: 600 errors ~10ms apart — each one observed by the sampler.
    let shipped = 0;
    for (let index = 0; index < 600; index += 1) {
      nowMs += 10;
      if (sampler.shouldSample()) shipped += 1;
    }
    // ~601 errors/min observed vs target 60: probability drops from 1 to
    // ~0.1 mid-burst, so only the head of the burst ships.
    expect(sampler.observedRate()).toBeGreaterThan(600);
    expect(shipped).toBeLessThan(200);
    expect(sampler.lastProbability()).toBeLessThan(0.2);
    // Window slides → rate returns to zero → ship again.
    nowMs += 120_000;
    expect(sampler.observedRate()).toBe(0);
    expect(sampler.shouldSample()).toBe(true);
  });
});
