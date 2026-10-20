// Segments v1 (plan B6 bullet 4): basic, EXPLAINABLE segments over injected
// evidence. Every membership decision carries the counts/fields that made
// it — a segment answer must be auditable, not a black-box score.
//
// Architecture posture (recorded, not incidental):
//   - slow-moving cohorts (city/acquisition) are materialized in BigQuery
//     (models/mart/segments.sql); there is NO segment→Remote-Config mass
//     sync anywhere in this package — client config never carries segment
//     membership, and nothing here exports to Remote Config.
//   - Redis is not used: v1 evaluates over batched evidence; a live Redis
//     join earns its complexity only when a latency-bound consumer exists.

export const APPROVED_CITY_COHORTS: readonly string[] = ['Pune', 'Mumbai', 'Bengaluru'];
export const APPROVED_ACQUISITION_COHORTS: readonly string[] = [
  'organic',
  'paid_search',
  'referral',
  'partner',
];

export const HIGH_INTENT_EVENTS: readonly string[] = [
  'view_item',
  'checkout_started',
  'begin_checkout',
];
export const CONVERSION_EVENTS: readonly string[] = ['purchase_viewed', 'booking_confirmed'];

/** The window used by the windowed segments (7 days). */
export const SEGMENT_WINDOW_DAYS = 7;
/** A repeat customer with no activity for this long is at-risk. */
export const AT_RISK_IDLE_DAYS = 14;

export interface SegmentSubjectInput {
  subjectKey: string;
  /** Event names seen inside the segment window (counts matter). */
  windowEvents: Array<{ name: string; count: number }>;
  /** Lifetime completed orders from authoritative business state. */
  lifetimeOrderCount: number;
  /** Days since any activity, for at-risk evaluation. */
  lastSeenDaysAgo: number;
  /** Zero-result searches inside the window. */
  zeroResultSearches: number;
  city: string | null;
  acquisitionSource: string | null;
}

export interface SegmentMembership {
  segment: string;
  matched: boolean;
  evidence: string;
}

export interface SegmentDefinition {
  id: string;
  description: string;
  evaluate: (input: SegmentSubjectInput) => { matched: boolean; evidence: string };
}

function countOf(input: SegmentSubjectInput, name: string): number {
  return input.windowEvents.find((event) => event.name === name)?.count ?? 0;
}

export const SEGMENT_DEFINITIONS: readonly SegmentDefinition[] = [
  {
    id: 'high_intent',
    description: 'Two or more high-intent events in-window with no conversion yet.',
    evaluate: (input) => {
      const highIntent = HIGH_INTENT_EVENTS.reduce((sum, name) => sum + countOf(input, name), 0);
      const conversions = CONVERSION_EVENTS.reduce((sum, name) => sum + countOf(input, name), 0);
      const matched = highIntent >= 2 && conversions === 0;
      return {
        matched,
        evidence: `${highIntent} high-intent event(s), ${conversions} conversion event(s) in ${SEGMENT_WINDOW_DAYS}d`,
      };
    },
  },
  {
    id: 'checkout_abandonment',
    description: 'Started checkout in-window, never converted (session-level twin of the rule).',
    evaluate: (input) => {
      const started = countOf(input, 'checkout_started');
      // Only IN-WINDOW conversions cancel the segment; lifetime orders do
      // not (a repeat customer can still abandon this checkout).
      const converted = countOf(input, 'purchase_viewed') + countOf(input, 'booking_confirmed');
      const matched = started > 0 && converted === 0;
      return { matched, evidence: `${started} checkout_started, ${converted} in-window conversion event(s)` };
    },
  },
  {
    id: 'first_time',
    description: 'No completed orders in authoritative business state.',
    evaluate: (input) => ({
      matched: input.lifetimeOrderCount === 0,
      evidence: `lifetimeOrderCount=${input.lifetimeOrderCount} (authoritative)`,
    }),
  },
  {
    id: 'repeat',
    description: 'At least one completed order in authoritative business state.',
    evaluate: (input) => ({
      matched: input.lifetimeOrderCount >= 1,
      evidence: `lifetimeOrderCount=${input.lifetimeOrderCount} (authoritative)`,
    }),
  },
  {
    id: 'at_risk',
    description: 'Repeat customer idle beyond the at-risk threshold.',
    evaluate: (input) => {
      const matched = input.lifetimeOrderCount >= 1 && input.lastSeenDaysAgo > AT_RISK_IDLE_DAYS;
      return {
        matched,
        evidence: `lifetimeOrderCount=${input.lifetimeOrderCount}, lastSeenDaysAgo=${input.lastSeenDaysAgo} (threshold ${AT_RISK_IDLE_DAYS})`,
      };
    },
  },
  {
    id: 'catalog_gap_searcher',
    description: 'Two or more zero-result searches in-window — catalog coverage signal.',
    evaluate: (input) => ({
      matched: input.zeroResultSearches >= 2,
      evidence: `${input.zeroResultSearches} zero-result search(es) in ${SEGMENT_WINDOW_DAYS}d`,
    }),
  },
  {
    id: 'city_cohort',
    description: 'Approved city cohort (slow-moving — BigQuery-materialized).',
    evaluate: (input) => ({
      matched: input.city !== null && APPROVED_CITY_COHORTS.includes(input.city),
      evidence: `city=${input.city ?? 'none'} in approved [${APPROVED_CITY_COHORTS.join(', ')}]`,
    }),
  },
  {
    id: 'acquisition_cohort',
    description: 'Approved acquisition cohort (slow-moving — BigQuery-materialized).',
    evaluate: (input) => ({
      matched:
        input.acquisitionSource !== null &&
        APPROVED_ACQUISITION_COHORTS.includes(input.acquisitionSource),
      evidence: `acquisitionSource=${input.acquisitionSource ?? 'none'} in approved [${APPROVED_ACQUISITION_COHORTS.join(', ')}]`,
    }),
  },
];

/** Evaluate every segment for one subject — one membership per definition,
 *  matched or not, each with its evidence. */
export function evaluateSegments(input: SegmentSubjectInput): SegmentMembership[] {
  return SEGMENT_DEFINITIONS.map((definition) => {
    const { matched, evidence } = definition.evaluate(input);
    return { segment: definition.id, matched, evidence };
  });
}
