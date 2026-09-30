import { LayoutContext } from '@navdhya/layout-engine/src/core/LayoutContext';

export interface RawRequestInput {
  product: LayoutContext['product'];
  route: LayoutContext['route'];
  viewport: LayoutContext['viewport'];
  actor: LayoutContext['actor'];
  locale: LayoutContext['locale'];
  experience?: LayoutContext['experience'];
  experiment?: LayoutContext['experiment'];
}

// Stage A: normalize raw request facets into a structured LayoutContext.
export function resolveLayoutContext(input: RawRequestInput): LayoutContext {
  return {
    product: input.product,
    route: input.route,
    viewport: input.viewport,
    actor: input.actor,
    locale: input.locale,
    experience: input.experience ?? { state: 'default' },
    experiment: input.experiment,
  };
}
