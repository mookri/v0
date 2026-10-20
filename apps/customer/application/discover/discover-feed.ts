export interface DiscoverCard {
  id: string;
  title: string;
  kind: 'puja' | 'content';
}

export interface DiscoverFeed {
  recommended: DiscoverCard[];
  popular: DiscoverCard[];
  recent: DiscoverCard[];
}

export function getDiscoverFeed(input: {
  recommended?: DiscoverCard[];
  popular?: DiscoverCard[];
  recent?: DiscoverCard[];
}): DiscoverFeed {
  return {
    recommended: input.recommended ?? [],
    popular: input.popular ?? [],
    recent: input.recent ?? [],
  };
}
