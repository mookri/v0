import "server-only";
import { unstable_cacheLife as cacheLife, unstable_cacheTag as cacheTag } from "next/cache";

// Define the exact shape needed by the UI, nothing more (Rule #25 - DTO Discipline)
export type PujaCardDTO = {
    id: string;
    slug: string;
    title: string;
    image: string;
    price: number; 
    durationMinutes: number;
    category?: string;
    description?: string;
    rating?: number;
    reviews?: number;
    duration?: string;
    location?: string;
    badge?: string;
    badgeType?: string;
};

import { getFeaturedPujas as getMockPujas } from "@/lib/data/pujas";

/**
 * Fetches featured pujas for a specific city.
 * Long-lived cached content: freshness requirement = 1-6 hours (Rule #05)
 */
export async function getFeaturedPujas(city: string = "delhi"): Promise<PujaCardDTO[]> {
    "use cache";
    cacheLife("hours"); 
    cacheTag(`featured-pujas:v2:${city}`);

    // Fetch from our "database" (currently the hardcoded TS file)
    const dbRows = getMockPujas();

    // Map to strict DTO
    return dbRows.map(row => ({
        id: row.id,
        slug: row.slug,
        title: row.title,
        image: row.image,
        price: parseInt(row.price.replace(/,/g, ''), 10) || 0,
        durationMinutes: 120, // default if not calculable
        category: row.category,
        description: row.description,
        rating: row.rating ? parseFloat(row.rating) : 4.5,
        reviews: row.reviews ? parseInt(row.reviews.replace(/[^0-9]/g, ''), 10) : 0,
        duration: row.duration,
        location: row.location,
        badge: row.badge,
        badgeType: row.badgeType
    }));
}

/**
 * Fetches trending rituals.
 * Short-lived cached content: freshness requirement = 1-5 minutes (Rule #05)
 */
export async function getTrendingPujas(city: string = "delhi"): Promise<PujaCardDTO[]> {
    "use cache";
    cacheLife("minutes");
    cacheTag(`trending-pujas:${city}`);

    // Mocking response for now
    return [];
}
