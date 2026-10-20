import { Environment } from '@navdhya/config/src/environment';

export interface FlagContext {
  environment: Environment;
  userId?: string;
  cohort?: string;
}

// Typed feature flag policy (§§11–12). Evaluation is a pure function:
// same policy + same context = same answer, everywhere (edge included).
// No boolean literals scattered in UI code; no untyped process.env reads.
export interface FlagPolicy {
  key: string;
  // Master kill switch: false disables everywhere regardless of rollout.
  enabled: boolean;
  environments?: Environment[];
  // Deterministic percentage rollout (0–100) over userId or cohort.
  rolloutPercent?: number;
  allowedUserIds?: string[];
  allowedCohorts?: string[];
}

function stableBucket(input: string): number {
  let h = 2166136261;
  for (let i = 0; i < input.length; i++) {
    h ^= input.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return (h >>> 0) % 100;
}

export function evaluateFlag(policy: FlagPolicy, context: FlagContext): boolean {
  if (!policy.enabled) return false;
  if (policy.environments && !policy.environments.includes(context.environment)) return false;
  if (policy.allowedUserIds && context.userId && policy.allowedUserIds.includes(context.userId)) return true;
  if (policy.allowedCohorts && context.cohort && policy.allowedCohorts.includes(context.cohort)) return true;
  if (policy.rolloutPercent === undefined) return true;
  if (policy.rolloutPercent <= 0) return false;
  if (policy.rolloutPercent >= 100) return true;
  const key = context.userId ?? context.cohort;
  if (!key) return false;
  return stableBucket(`${policy.key}:${key}`) < policy.rolloutPercent;
}
