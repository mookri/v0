import { LayoutContext } from '@navdhya/layout-engine/src/core/LayoutContext';

export interface PolicyCondition {
  product?: string;
  routeId?: string;
  routeCategory?: string;
  experience?: string;
  viewport?: string;
  authenticated?: boolean;
  role?: string;
  experiment?: { id: string; variant: string };
}

export interface PolicyResult {
  shell: string;
  layout: string;
  regions: string[];
  variant?: string;
}

export interface Policy {
  id: string;
  when: PolicyCondition;
  result: PolicyResult;
  priority: number;
}

export function matchesPolicy(ctx: LayoutContext, policy: Policy): string[] {
  const matched: string[] = [];
  const w = policy.when;
  if (w.product !== undefined) {
    if (ctx.product.id !== w.product) return [];
    matched.push(`product=${w.product}`);
  }
  if (w.routeId !== undefined) {
    if (ctx.route.id !== w.routeId) return [];
    matched.push(`route=${w.routeId}`);
  }
  if (w.routeCategory !== undefined) {
    if (ctx.route.category !== w.routeCategory) return [];
    matched.push(`routeCategory=${w.routeCategory}`);
  }
  if (w.experience !== undefined) {
    if (ctx.experience.state !== w.experience) return [];
    matched.push(`experience=${w.experience}`);
  }
  if (w.viewport !== undefined) {
    if (ctx.viewport.mode !== w.viewport) return [];
    matched.push(`viewport=${w.viewport}`);
  }
  if (w.authenticated !== undefined) {
    if (ctx.actor.authenticated !== w.authenticated) return [];
    matched.push(`authenticated=${w.authenticated}`);
  }
  if (w.role !== undefined) {
    if (!ctx.actor.roles.includes(w.role)) return [];
    matched.push(`role=${w.role}`);
  }
  if (w.experiment !== undefined) {
    if (ctx.experiment?.assignments[w.experiment.id] !== w.experiment.variant) return [];
    matched.push(`experiment=${w.experiment.id}:${w.experiment.variant}`);
  }
  return matched;
}
