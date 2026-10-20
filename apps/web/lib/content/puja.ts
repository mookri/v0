import type { SEOPage, ServiceDetails, ServicePackage } from "@/lib/content/types";

export type PujaPageViewModel = {
  title: string;
  description: string;
  summary: string;
  priceRange: string;
  heroImage: string;
  galleryImages: string[];
  highlights: string[];
  benefits: string[];
  process: ServiceDetails["process"];
  includes: string[];
  faqs: NonNullable<SEOPage["faqs"]>;
  packages: ServicePackage[];
  serviceProof: string[];
  bookingLeadTime: string;
  serviceAreas: string[];
};

const DEFAULT_GALLERY = [
  "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
  "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
];

const DEFAULT_BOOKING_LEAD_TIME = "2 to 5 days is ideal, but urgent Delhi NCR requests can still be reviewed.";
const DEFAULT_SERVICE_AREAS = ["Delhi", "Gurgaon", "Noida", "Ghaziabad"];

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function extractParagraphs(content: string): string[] {
  return Array.from(
    content.matchAll(/<p>(.*?)<\/p>/gi),
    (match) => stripHtml(match[1] ?? ""),
  ).filter(Boolean);
}

function buildFallbackPackages(priceRange: string, title: string): ServicePackage[] {
  const parts = priceRange
    .split(/\s*-\s*/)
    .map((part) => part.trim())
    .filter(Boolean);

  const basicPrice = parts[0] ?? "Rs. 2,500";
  const premiumPrice = parts[1] ?? parts[0] ?? "Rs. 4,500";

  return [
    {
      name: "Guided Ceremony",
      description: `A practical ${title.toLowerCase()} format with pandit guidance, mantra flow, and clear preparation support.`,
      price: basicPrice,
      duration: "60 to 120 minutes",
    },
    {
      name: "Full Family Package",
      description: `A fuller ${title.toLowerCase()} experience with wider family coordination, samagri clarity, and ceremony pacing support.`,
      price: premiumPrice,
      duration: "90 to 180 minutes",
    },
  ];
}

export function getPujaPageViewModel(page: SEOPage): PujaPageViewModel {
  const serviceDetails = page.serviceDetails ?? {};
  const paragraphs = extractParagraphs(page.content);
  const summary = serviceDetails.summary ?? paragraphs.slice(0, 2).join(" ");
  const priceRange = serviceDetails.priceRange ?? page.pricingNote ?? "Rs. 2,500 - Rs. 5,500";
  const heroImage = serviceDetails.image ?? page.heroImage ?? DEFAULT_GALLERY[0];
  const galleryImages = Array.from(
    new Set([heroImage, ...(serviceDetails.galleryImages ?? []), ...DEFAULT_GALLERY]),
  ).slice(0, 4);

  return {
    title: page.title,
    description: page.metaDescription,
    summary,
    priceRange,
    heroImage,
    galleryImages,
    highlights: serviceDetails.highlights ?? page.secondaryKeywords.slice(0, 4),
    benefits: serviceDetails.benefits ?? [],
    process: serviceDetails.process ?? [],
    includes: serviceDetails.includes ?? [],
    faqs: page.faqs ?? [],
    packages: serviceDetails.packages ?? buildFallbackPackages(priceRange, page.title),
    serviceProof: serviceDetails.serviceProof ?? page.proofPoints ?? [],
    bookingLeadTime: serviceDetails.bookingLeadTime ?? DEFAULT_BOOKING_LEAD_TIME,
    serviceAreas: serviceDetails.serviceAreas ?? page.targetCities ?? DEFAULT_SERVICE_AREAS,
  };
}

export function extractStartingPrice(priceRange?: string): string | undefined {
  if (!priceRange) return undefined;
  const match = priceRange.match(/(\d[\d,]*)/);
  return match ? match[1].replace(/,/g, "") : undefined;
}

