import { LayoutContext } from '@navdhya/layout-engine/src/core/LayoutContext';
import { LayoutDecision } from '@navdhya/layout-engine/src/core/LayoutDecision';
import { LAYOUT_POLICY_VERSION } from '@navdhya/layout-engine/src/core/LayoutMode';
import { Policy } from '@navdhya/layout-engine/src/policy/Policy';
import { evaluatePolicies } from '@navdhya/layout-engine/src/policy/PolicyEvaluator';

export interface DecisionExplanation {
  decision: LayoutDecision;
  policy: string;
  matched: string[];
}

// Stage B: deterministic policy evaluation → serializable decision.
// Returns identifiers only — never React components.
export function resolveLayout(ctx: LayoutContext, policies: Policy[]): DecisionExplanation {
  const matches = evaluatePolicies(ctx, policies);
  const top = matches[0];
  if (!top) {
    const decision: LayoutDecision = {
      shell: 'minimal',
      layout: 'minimal',
      regions: [{ id: 'content' }],
      policyId: 'minimal-fallback',
      policyVersion: LAYOUT_POLICY_VERSION,
    };
    return { decision, policy: 'minimal-fallback', matched: [] };
  }
  const decision: LayoutDecision = {
    shell: top.policy.result.shell,
    layout: top.policy.result.layout,
    regions: top.policy.result.regions.map((id) => ({ id })),
    variant: top.policy.result.variant ?? ctx.experiment?.assignments['layout'] ,
    policyId: top.policy.id,
    policyVersion: LAYOUT_POLICY_VERSION,
  };
  return { decision, policy: top.policy.id, matched: top.matched };
}
