import { LayoutContext } from '@navdhya/layout-engine/src/core/LayoutContext';
import { Policy } from '@navdhya/layout-engine/src/policy/Policy';
import { resolveLayoutContext, RawRequestInput } from './ContextResolver';
import { resolveLayout, DecisionExplanation } from './LayoutResolver';

export function resolveDecision(input: RawRequestInput, policies: Policy[]): DecisionExplanation {
  const ctx: LayoutContext = resolveLayoutContext(input);
  return resolveLayout(ctx, policies);
}
