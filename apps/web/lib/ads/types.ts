//lib/ads/types.ts
export type AdPlacement = "article_top" | "article_mid" | "article_bottom";

export interface Ad {
    id: string;

    title: string;
    description?: string;
    imageUrl?: string;

    ctaText: string;
    ctaUrl: string;

    sponsorName?: string;
    verified?: boolean;

    weight?: number; // for rotation
    placements: AdPlacement[];

    experiment?: {
        key: string;
        variant: "A" | "B";
    };
}