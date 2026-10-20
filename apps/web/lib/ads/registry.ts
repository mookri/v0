//lib/ads/registry.ts
import { Ad } from "./types";

export const ADS: Ad[] = [
    {
        id: "ad_1",
        title: "Book Verified Pandits Instantly",
        description: "Trusted rituals with transparent pricing.",
        imageUrl: "https://picsum.photos/1200/600",
        ctaText: "Book Now",
        ctaUrl: "#",
        sponsorName: "Navdhya",
        verified: true,
        placements: ["article_mid"],
        weight: 2,
    },
    {
        id: "ad_2",
        title: "Get Personalized Ritual Guidance",
        description: "Talk to experts before booking.",
        imageUrl: "https://picsum.photos/1200/601",
        ctaText: "Consult Now",
        ctaUrl: "#",
        sponsorName: "Navdhya",
        placements: ["article_mid"],
        weight: 1,
    },
];