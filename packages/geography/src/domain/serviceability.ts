export type ServiceabilityRule =
  | { kind: 'REGION_ALLOW'; regionId: string; ruleId: string }
  | { kind: 'REGION_DENY'; regionId: string; ruleId: string }
  | { kind: 'POSTAL_ALLOW'; postalCode: string; ruleId: string }
  | { kind: 'POSTAL_DENY'; postalCode: string; ruleId: string };

export interface ServiceabilityPolicy {
  id: string;
  serviceId: string;
  ruleVersion: string;
  rules: ServiceabilityRule[];
  effectiveFrom?: string;
  effectiveUntil?: string;
}

export type ServiceabilityResultCode = 'SERVICEABLE' | 'NOT_SERVICEABLE' | 'CONDITIONAL' | 'UNKNOWN';

export interface ServiceabilityReason {
  code: string;
  ruleId?: string;
}

export interface ServiceabilityDecision {
  decisionId: string;
  serviceId: string;
  locationId: string;
  result: ServiceabilityResultCode;
  reasons: ServiceabilityReason[];
  policyVersion: string;
  evaluatedAt: string;
}

function policyActive(policy: ServiceabilityPolicy, nowIso: string): boolean {
  if (policy.effectiveFrom && nowIso < policy.effectiveFrom) return false;
  if (policy.effectiveUntil && nowIso >= policy.effectiveUntil) return false;
  return true;
}

// Precedence: explicit exclusion > specific inclusion > broad inclusion.
export function evaluateServiceability(input: {
  decisionId: string;
  serviceId: string;
  locationId: string;
  regionIds: string[];
  postalCode?: string;
  policy: ServiceabilityPolicy;
  nowIso: string;
}): ServiceabilityDecision {
  const base = {
    decisionId: input.decisionId,
    serviceId: input.serviceId,
    locationId: input.locationId,
    policyVersion: input.policy.ruleVersion,
    evaluatedAt: input.nowIso,
  };
  if (!policyActive(input.policy, input.nowIso)) {
    return { ...base, result: 'UNKNOWN', reasons: [{ code: 'POLICY_INACTIVE' }] };
  }
  const denied = input.policy.rules.find((r) => {
    if (r.kind === 'REGION_DENY') return input.regionIds.includes(r.regionId);
    if (r.kind === 'POSTAL_DENY') return !!input.postalCode && r.postalCode === input.postalCode;
    return false;
  });
  if (denied) return { ...base, result: 'NOT_SERVICEABLE', reasons: [{ code: 'EXCLUDED', ruleId: denied.ruleId }] };
  const allowed = input.policy.rules.find((r) => {
    if (r.kind === 'REGION_ALLOW') return input.regionIds.includes(r.regionId);
    if (r.kind === 'POSTAL_ALLOW') return !!input.postalCode && r.postalCode === input.postalCode;
    return false;
  });
  if (allowed) return { ...base, result: 'SERVICEABLE', reasons: [{ code: 'INCLUDED', ruleId: allowed.ruleId }] };
  return { ...base, result: 'NOT_SERVICEABLE', reasons: [{ code: 'NO_COVERAGE' }] };
}

export function batchEvaluate(
  requests: Omit<Parameters<typeof evaluateServiceability>[0], 'decisionId'>[],
  decisionPrefix: string,
): ServiceabilityDecision[] {
  return requests.map((r, i) => evaluateServiceability({ ...r, decisionId: `${decisionPrefix}_${i + 1}` }));
}
