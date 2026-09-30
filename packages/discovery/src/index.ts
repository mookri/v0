export type DiscoverySurfaceType = 'home' | 'category' | 'festival' | 'location' | 'collection' | 'search';
export type DiscoverySectionType = 'featured' | 'popular' | 'trending' | 'recommended' | 'recent' | 'category' | 'collection' | 'search-results';
export type DiscoverySource = 'search' | 'recommendation' | 'editorial' | 'recent';
export type DiscoveryCriticality = 'critical' | 'important' | 'optional';

export interface DiscoveryItem {
  id: string;
  title: string;
  source: DiscoverySource;
}

export interface DiscoverySection {
  id: string;
  type: DiscoverySectionType;
  title?: string;
  items: DiscoveryItem[];
  source: DiscoverySource;
  criticality: DiscoveryCriticality;
}

export interface DiscoverySurface {
  id: string;
  type: DiscoverySurfaceType;
  sections: DiscoverySection[];
}

export interface DiscoverySectionProvider {
  getSection(): Promise<DiscoverySection | null>;
}

// Graceful degradation: critical sections fail the surface, optional ones drop.
export async function composeSurface(
  surfaceId: string,
  type: DiscoverySurface['type'],
  providers: DiscoverySectionProvider[],
): Promise<DiscoverySurface> {
  const sections: DiscoverySection[] = [];
  for (const provider of providers) {
    try {
      const section = await provider.getSection();
      if (section) sections.push(section);
    } catch {
      continue;
    }
  }
  return { id: surfaceId, type, sections };
}
