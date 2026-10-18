// Stopgap data-access layer for the customer app's featured-rituals surface.
// The full implementation is pending (user WIP); this keeps the build green
// and returns an empty list until the real catalog wiring lands.
export interface FeaturedPuja {
  id: string;
  slug: string;
  title: string;
  image: string;
  rating: number;
  reviews: number;
  description: string;
  price: number;
  category?: string;
  badge?: string;
  badgeType?: 'green' | 'blue' | 'mint' | 'orange';
}

export async function getFeaturedPujas(): Promise<FeaturedPuja[]> {
  return [];
}