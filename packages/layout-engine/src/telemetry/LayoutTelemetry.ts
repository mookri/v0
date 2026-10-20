import { LayoutDecision } from '@navdhya/layout-engine/src/core/LayoutDecision';
import { LayoutContext } from '@navdhya/layout-engine/src/core/LayoutContext';

export interface LayoutTelemetryEvent {
  event: 'layout_decision';
  requestId: string;
  product: string;
  route: string;
  viewport: string;
  experience: string;
  shell: string;
  layout: string;
  variant?: string;
  policyId: string;
  policyVersion: string;
}

export function toTelemetryEvent(
  requestId: string,
  ctx: LayoutContext,
  decision: LayoutDecision,
): LayoutTelemetryEvent {
  return {
    event: 'layout_decision',
    requestId,
    product: ctx.product.id,
    route: ctx.route.id,
    viewport: ctx.viewport.mode,
    experience: ctx.experience.state,
    shell: decision.shell,
    layout: decision.layout,
    variant: decision.variant,
    policyId: decision.policyId,
    policyVersion: decision.policyVersion,
  };
}
