// The six starter rules (plan B6 bullet 2, architecture §6.1), each one
// versioned data over the rule contract. Subject derivation stays honest to
// the envelope: browser events correlate on their session; server events
// (booking/payment truth) correlate on their server session. Cross-boundary
// correlation (browser checkout → server booking) is exactly why
// checkout_abandonment carries an authoritative `booking_exists` check at
// due time — the opportunistic cancelEvent is best-effort on top of that.
//
// Consent posture: only rules that MESSAGE the end user gate on consent
// (checkout abandonment → `ads`; zero-results reporting → `analytics`).
// Transactional follow-ups (booking workflow, payment-failure support, KYC
// reminder) and internal pages (ops alert) do not — the B7 consent API
// extends this matrix when messaging-consent classes exist.

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import type { RuleDefinition } from './contract';

const bySession = (event: SignalEnvelope): string => `session:${event.session.id}`;

/** Identity-first for lifecycle rules; falls back to session. */
const byActor = (event: SignalEnvelope): string | null => {
  if (event.actor.uidHash !== null) return `uid:${event.actor.uidHash}`;
  if (event.actor.anonId !== null) return `anon:${event.actor.anonId}`;
  return `session:${event.session.id}`;
};

export const CHECKOUT_ABANDONMENT: RuleDefinition = {
  id: 'checkout_abandonment',
  version: 1,
  description:
    'Remind a checkout that never converted. The reminder is suppressed if a booking was authoritatively confirmed in-window — analytics signals never declare the conversion themselves.',
  owner: 'commerce',
  triggerEvent: 'checkout_started',
  cancelEvent: 'booking_confirmed', // opportunistic; correlates only when the subject matches
  delayMs: 30 * 60 * 1000,
  requiresConsent: 'ads',
  cooldownMs: 24 * 60 * 60 * 1000,
  dailyCap: 1,
  authoritativeCheck: 'booking_exists',
  subjectKey: bySession,
  action: { kind: 'fcm_reminder', template: 'checkout_reminder_v1' },
};

export const BOOKING_CONFIRMATION_WORKFLOW: RuleDefinition = {
  id: 'booking_confirmation_workflow',
  version: 1,
  description:
    'Send the post-confirmation follow-up (details, next steps). Triggered by the authoritative server event itself — no analytics inference of "a booking happened".',
  owner: 'booking',
  triggerEvent: 'booking_confirmed',
  cancelEvent: null,
  delayMs: 0,
  requiresConsent: null, // transactional
  cooldownMs: 0,
  dailyCap: 3,
  authoritativeCheck: null,
  subjectKey: bySession,
  action: { kind: 'follow_up_message', template: 'booking_follow_up_v1' },
};

export const PAYMENT_FAILURE_RETRY_SUPPORT: RuleDefinition = {
  id: 'payment_failure_retry_support',
  version: 1,
  description:
    'Nudge a failed payment toward retry/support. Server trigger (authoritative), frequency-gated so a retry loop cannot spam.',
  owner: 'payments',
  triggerEvent: 'payment_failed',
  cancelEvent: null,
  delayMs: 0,
  requiresConsent: null, // transactional support
  cooldownMs: 15 * 60 * 1000,
  dailyCap: 3,
  authoritativeCheck: null,
  subjectKey: bySession,
  action: { kind: 'support_nudge', template: 'payment_retry_v1' },
};

export const ZERO_RESULTS_CATALOG_GAP: RuleDefinition = {
  id: 'zero_results_catalog_gap',
  version: 1,
  description:
    'Route zero-result searches to the internal catalog-gap report (not user messaging). Analytics consent gates eligibility.',
  owner: 'growth',
  triggerEvent: 'zero_results_search',
  cancelEvent: null,
  delayMs: 0,
  requiresConsent: 'analytics',
  cooldownMs: 0,
  dailyCap: 5,
  authoritativeCheck: null,
  subjectKey: bySession,
  action: { kind: 'catalog_gap_report', template: 'catalog_gap_v1' },
};

export const KYC_PENDING_REMINDER: RuleDefinition = {
  id: 'kyc_pending_reminder',
  version: 1,
  description:
    'Remind a partner whose KYC is still pending a day after submission. Due-time authoritative check suppresses the reminder if KYC was approved meanwhile.',
  owner: 'partner_ops',
  triggerEvent: 'kyc_submitted',
  cancelEvent: null,
  delayMs: 24 * 60 * 60 * 1000,
  requiresConsent: null, // transactional lifecycle
  cooldownMs: 24 * 60 * 60 * 1000,
  dailyCap: 1,
  authoritativeCheck: 'kyc_pending',
  subjectKey: byActor,
  action: { kind: 'kyc_reminder', template: 'kyc_pending_v1' },
};

export const ASSIGNMENT_DELAY_OPS_ALERT: RuleDefinition = {
  id: 'assignment_delay_ops_alert',
  version: 1,
  description:
    'Page ops when a confirmed booking still has no driver assignment after the SLA window. Due-time authoritative check suppresses the page once an assignment exists.',
  owner: 'booking',
  triggerEvent: 'booking_confirmed',
  cancelEvent: null,
  delayMs: 15 * 60 * 1000,
  requiresConsent: null, // internal ops page
  cooldownMs: 0,
  dailyCap: 5,
  authoritativeCheck: 'assignment_missing',
  subjectKey: bySession,
  action: { kind: 'ops_page', template: 'assignment_delay_v1' },
};

export const STARTER_RULES: readonly RuleDefinition[] = [
  CHECKOUT_ABANDONMENT,
  BOOKING_CONFIRMATION_WORKFLOW,
  PAYMENT_FAILURE_RETRY_SUPPORT,
  ZERO_RESULTS_CATALOG_GAP,
  KYC_PENDING_REMINDER,
  ASSIGNMENT_DELAY_OPS_ALERT,
];
