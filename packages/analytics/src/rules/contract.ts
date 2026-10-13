// Rule engine contract (plan B6, implementation bullet 1): typed conditions,
// versioned rules, window semantics, consent requirements, stable action
// idempotency keys.
//
// Honesty notes:
//   - Rules that determine OUTCOMES consult authoritative business state
//     (the `authoritativeCheck` label resolved by an injected port at
//     evaluation time). Analytics signals may gate ELIGIBILITY to message
//     (trigger + consent + frequency) but never declare that a
//     booking/payment/refund happened.
//   - The action request ID is a deterministic composite of
//     (rule, version, subject, sorted triggering event IDs): replaying the
//     same triggering events yields the SAME ID, so the store detects the
//     replay as a duplicate instead of double-sending.
//   - Messaging consent beyond the envelope's {analytics, ads} pair lands
//     with the B7 consent API; rules gate on what exists today and record
//     which consent they used.

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

/** The consent field a rule requires before messaging a subject, if any. */
export type ConsentRequirement = 'analytics' | 'ads' | null;

/** Six starter rules (plan B6 bullet 2) declare one of these authoritative
 *  checks; the engine resolves the label through an injected port so no
 *  business system is hard-wired into the package. */
export type AuthoritativeCheckLabel =
  | 'booking_exists' // checkout already converted → suppress reminder
  | 'kyc_pending' // KYC approved meanwhile → suppress reminder
  | 'assignment_missing' // driver already assigned → suppress ops page
  | null;

export interface RuleDefinition {
  id: string;
  /** Bumped whenever conditions/actions change — persisted on every action. */
  version: number;
  description: string;
  owner: string;
  /** Registry event name that starts the rule. */
  triggerEvent: string;
  /** Optional opportunistic cancel: same subject seen with this event
   *  before the window elapses suppresses the action. The authoritative
   *  check at due time remains the robust cancel path. */
  cancelEvent: string | null;
  /** Delayed-action window: 0 = evaluate immediately, >0 = scheduled. */
  delayMs: number;
  /** Consent required on the TRIGGERING event before any messaging. */
  requiresConsent: ConsentRequirement;
  /** No second FIRE for the same subject inside this window. */
  cooldownMs: number;
  /** Max fires per subject per UTC day. */
  dailyCap: number;
  /** Authoritative check evaluated at due time for windowed rules
   *  (or immediately for instant rules that still need business state). */
  authoritativeCheck: AuthoritativeCheckLabel;
  /** Stable subject derivation; null = rule does not apply to the event. */
  subjectKey: (event: SignalEnvelope) => string | null;
  /** What the fired action asks the delivery boundary to send. */
  action: { kind: 'fcm_reminder' | 'follow_up_message' | 'support_nudge' | 'catalog_gap_report' | 'kyc_reminder' | 'ops_page'; template: string };
}

/** Everything persisted per action (plan B6 bullet 1) — a fired action must
 *  be explainable from this record alone, a year later. */
export type RuleDecision =
  | 'pending_window' // scheduled; not yet evaluated
  | 'fired' // action ready to dispatch
  | 'duplicate_detected' // replay of the same triggering event IDs
  | 'suppressed_cancel' // cancel event matched the subject in-window
  | 'suppressed_authoritative' // authoritative state says outcome already happened
  | 'suppressed_consent' // required consent missing on the triggering event
  | 'suppressed_cooldown' // fired recently for this subject
  | 'suppressed_daily_cap'; // daily cap reached for this subject

export type DeliveryStatus =
  | 'scheduled' // windowed, awaiting its due time
  | 'pending' // fired, awaiting dispatch
  | 'delivered' // effect reached the FCM/outbox boundary once
  | 'failed' // delivery attempt errored
  | 'none'; // suppressed — nothing to deliver

export interface RuleActionRecord {
  /** Deterministic idempotency key (see header). */
  actionRequestId: string;
  ruleId: string;
  ruleVersion: number;
  subjectKey: string;
  /** The exact events behind this decision — the replay/dedup identity. */
  triggeringEventIds: string[];
  decision: RuleDecision;
  /** Human-readable WHY (consent state, matched cancel event, check result). */
  evidence: string;
  /** When the trigger was processed. */
  requestedAtMs: number;
  /** Due time for windowed rules; null when evaluated immediately. */
  evaluateAtMs: number | null;
  /** When the final (non-pending) decision was made; null while pending. */
  decidedAtMs: number | null;
  deliveryStatus: DeliveryStatus;
  deliveredAtMs: number | null;
  /** Delivery attempts at the FCM/outbox boundary (replays do not count). */
  attempts: number;
}

/** Stable idempotency key: same rule + version + subject + triggering event
 *  IDs → same key, always. Sorted event IDs make order irrelevant. */
export function buildActionRequestId(
  rule: Pick<RuleDefinition, 'id' | 'version'>,
  subjectKey: string,
  triggeringEventIds: string[],
): string {
  const ids = [...triggeringEventIds].sort().join('+');
  return `${rule.id}@v${rule.version}:${subjectKey}:${ids}`;
}
