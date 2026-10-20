import { pujasData, type Puja } from '@/application/pujas-data';

// Featured-rituals data-access layer. The catalogue source is the shared
// mock dataset until the backend catalogue is wired into this surface;
// returning [] here silently hid every featured record.

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

function numeric(value: string | undefined): number {
  const match = (value ?? '').replace(/,/g, '').match(/\d+(?:\.\d+)?/);
  return match ? Number(match[0]) : 0;
}

function toFeaturedPuja(puja: Puja): FeaturedPuja {
  return {
    id: puja.slug || puja.id,
    slug: puja.slug || puja.id,
    title: puja.title,
    image: puja.image,
    rating: numeric(puja.rating),
    reviews: numeric(puja.reviews),
    description: puja.description,
    price: numeric(puja.price),
    category: puja.category,
    badge: puja.badge,
    badgeType: puja.badgeType,
  };
}

export async function getFeaturedPujas(): Promise<FeaturedPuja[]> {
  const featured = pujasData.filter((puja) => puja.isFeatured);
  return (featured.length > 0 ? featured : pujasData).map(toFeaturedPuja);
}