// Segments v1 unit tests (plan B6 bullet 4): explainable memberships,
// authoritative fields for lifecycle segments, approved cohorts only.

import { describe, expect, it } from 'vitest';
import {
  APPROVED_ACQUISITION_COHORTS,
  APPROVED_CITY_COHORTS,
  SEGMENT_DEFINITIONS,
  evaluateSegments,
  type SegmentSubjectInput,
} from '@navdhya/analytics/src/segments/segments-v1';

function subject(overrides: Partial<SegmentSubjectInput> = {}): SegmentSubjectInput {
  return {
    subjectKey: 'uid:uh_1',
    windowEvents: [],
    lifetimeOrderCount: 0,
    lastSeenDaysAgo: 0,
    zeroResultSearches: 0,
    city: null,
    acquisitionSource: null,
    ...overrides,
  };
}

function membershipFor(input: SegmentSubjectInput, segment: string) {
  return evaluateSegments(input).find((m) => m.segment === segment)!;
}

describe('segments v1 (B6)', () => {
  it('high_intent: two high-intent events, no conversion', () => {
    const matched = membershipFor(
      subject({ windowEvents: [{ name: 'view_item', count: 2 }, { name: 'checkout_started', count: 1 }] }),
      'high_intent',
    );
    expect(matched.matched).toBe(true);
    expect(matched.evidence).toContain('3 high-intent');

    const converted = membershipFor(
      subject({
        windowEvents: [
          { name: 'view_item', count: 2 },
          { name: 'checkout_started', count: 1 },
          { name: 'purchase_viewed', count: 1 },
        ],
      }),
      'high_intent',
    );
    expect(converted.matched).toBe(false);
  });

  it('checkout_abandonment: started in-window, no in-window conversion (lifetime irrelevant)', () => {
    expect(
      membershipFor(subject({ windowEvents: [{ name: 'checkout_started', count: 1 }], lifetimeOrderCount: 3 }), 'checkout_abandonment').matched,
    ).toBe(true);
    expect(
      membershipFor(
        subject({ windowEvents: [{ name: 'checkout_started', count: 1 }, { name: 'booking_confirmed', count: 1 }] }),
        'checkout_abandonment',
      ).matched,
    ).toBe(false);
  });

  it('first_time vs repeat split on authoritative order count', () => {
    expect(membershipFor(subject({ lifetimeOrderCount: 0 }), 'first_time').matched).toBe(true);
    expect(membershipFor(subject({ lifetimeOrderCount: 0 }), 'repeat').matched).toBe(false);
    expect(membershipFor(subject({ lifetimeOrderCount: 2 }), 'repeat').matched).toBe(true);
    expect(membershipFor(subject({ lifetimeOrderCount: 2 }), 'first_time').matched).toBe(false);
  });

  it('at_risk: repeat customer idle beyond threshold; newcomers never at-risk', () => {
    expect(
      membershipFor(subject({ lifetimeOrderCount: 2, lastSeenDaysAgo: 20 }), 'at_risk').matched,
    ).toBe(true);
    expect(
      membershipFor(subject({ lifetimeOrderCount: 0, lastSeenDaysAgo: 20 }), 'at_risk').matched,
    ).toBe(false);
    expect(
      membershipFor(subject({ lifetimeOrderCount: 2, lastSeenDaysAgo: 3 }), 'at_risk').matched,
    ).toBe(false);
  });

  it('catalog_gap_searcher: two+ zero-result searches', () => {
    expect(membershipFor(subject({ zeroResultSearches: 2 }), 'catalog_gap_searcher').matched).toBe(true);
    expect(membershipFor(subject({ zeroResultSearches: 1 }), 'catalog_gap_searcher').matched).toBe(false);
  });

  it('city/acquisition cohorts: only approved values match', () => {
    expect(membershipFor(subject({ city: 'Pune' }), 'city_cohort').matched).toBe(true);
    expect(membershipFor(subject({ city: 'Ranchi' }), 'city_cohort').matched).toBe(false);
    expect(membershipFor(subject({ acquisitionSource: 'partner' }), 'acquisition_cohort').matched).toBe(true);
    expect(membershipFor(subject({ acquisitionSource: 'cold_email' }), 'acquisition_cohort').matched).toBe(false);
    expect(APPROVED_CITY_COHORTS).toContain('Mumbai');
    expect(APPROVED_ACQUISITION_COHORTS).toContain('organic');
  });

  it('every subject gets one evidence-carrying membership per segment definition', () => {
    const memberships = evaluateSegments(subject({ city: 'Pune', lifetimeOrderCount: 1 }));
    expect(memberships).toHaveLength(SEGMENT_DEFINITIONS.length);
    for (const membership of memberships) {
      expect(membership.evidence.length, membership.segment).toBeGreaterThan(0);
    }
  });
});
