import "server-only";
import { unstable_cacheLife as cacheLife, unstable_cacheTag as cacheTag } from "next/cache";

// DTO for Presentation Availability (Rule #18)
export type AvailabilityPreviewDTO = {
    pujaId: string;
    status: "available" | "limited" | "sold_out";
    nextAvailableSlot?: Date;
    availablePanditsCount: number;
};

/**
 * Fetches presentation availability (NOT transactional booking availability).
 * Short-lived cached content: freshness requirement = seconds / event-driven (Rule #05)
 */
export async function getPresentationAvailability(pujaId: string): Promise<AvailabilityPreviewDTO> {
    "use cache";
    cacheLife("seconds"); // Can tolerate short-lived staleness
    cacheTag(`availability:preview:${pujaId}`);

    // TODO: Connect to Redis Projection (Rule #17)
    return {
        pujaId,
        status: "available",
        availablePanditsCount: 5,
        nextAvailableSlot: new Date("2099-01-01") // Fixed date to avoid SSG errors
    };
}
