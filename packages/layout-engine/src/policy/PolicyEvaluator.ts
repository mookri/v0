import { LayoutContext } from '../core/LayoutContext';
import { Policy, matchesPolicy } from './Policy';

export interface PolicyMatch {
  policy: Policy;
  matched: string[];
}

export function evaluatePolicies(ctx: LayoutContext, policies: Policy[]): PolicyMatch[] {
  const matches: PolicyMatch[] = [];
  for (const policy of policies) {
    const matched = matchesPolicy(ctx, policy);
    if (matched.length > 0 || Object.keys(policy.when).length === 0) {
      matches.push({ policy, matched });
    }
  }
  matches.sort((a, b) => b.policy.priority - a.policy.priority);
  return matches;
}
