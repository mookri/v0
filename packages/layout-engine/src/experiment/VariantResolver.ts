import { LayoutContext } from '../core/LayoutContext';

// Engine consumes assignments; it never runs hashing/experiment platform logic.
export function resolveVariant(ctx: LayoutContext, experimentId: string): string | undefined {
  return ctx.experiment?.assignments[experimentId];
}
