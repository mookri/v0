export interface CompetitorSnapshot {
  name: string;
  slug: string;
  delhiNcrFocus: string;
  networkSize: string;
  catalogSize: string;
  nationalCoverage: string;
  sameDayBooking: string;
  pricing: string;
  pricePositioning: string;
  structuredData: string;
  contentHub: string;
  cityPages: string;
  strengths: string[];
  tradeoffs: string[];
}

export const COMPETITOR_VERIFIED_AT = "May 2026";

export const COMPETITORS: Record<string, CompetitorSnapshot> = {
  navdhya: {
    name: "Navdhya",
    slug: "navdhya",
    delhiNcrFocus: "Primary",
    networkSize: "Not disclosed",
    catalogSize: "25+",
    nationalCoverage: "6 listed cities",
    sameDayBooking: "Not stated",
    pricing: "Rs. 2,500 starting for Griha Pravesh in Delhi",
    pricePositioning: "Most competitive in Delhi NCR",
    structuredData: "None live at audit time",
    contentHub: "Broken links at audit time",
    cityPages: "6 stub pages",
    strengths: [
      "Post-puja clean-up included",
      "Visible Delhi NCR starting prices",
      "Vedic University-certified pandit positioning",
      "Puja, astrology, and Vastu under one roof",
    ],
    tradeoffs: [
      "Smaller public footprint than national marketplaces",
      "Needs stronger local landing pages and schema coverage",
    ],
  },
  "99pandit": {
    name: "99Pandit",
    slug: "99pandit",
    delhiNcrFocus: "One of 70+ cities",
    networkSize: "20,000+",
    catalogSize: "400+",
    nationalCoverage: "70+ cities",
    sameDayBooking: "Yes",
    pricing: "Quote based for Delhi Griha Pravesh",
    pricePositioning: "Moderate",
    structuredData: "Rich results present",
    contentHub: "Active",
    cityPages: "70+ optimized pages",
    strengths: [
      "Largest visible network and city coverage",
      "Strong review footprint and rich results presence",
      "Good fit for families outside Delhi NCR",
    ],
    tradeoffs: [
      "Less local positioning for Delhi-only families",
      "Transparent entry pricing is weaker than Navdhya",
    ],
  },
  smartpuja: {
    name: "SmartPuja",
    slug: "smartpuja",
    delhiNcrFocus: "Partial",
    networkSize: "Unlisted",
    catalogSize: "200+",
    nationalCoverage: "Metro cities",
    sameDayBooking: "Yes",
    pricing: "Rs. 6,500+ for Delhi Griha Pravesh",
    pricePositioning: "Premium",
    structuredData: "Present",
    contentHub: "Active",
    cityPages: "Key metro pages",
    strengths: [
      "Broad catalog with metro-city presence",
      "Premium packaging and polished booking flow",
    ],
    tradeoffs: [
      "Much higher starting price in Delhi",
      "Less value-oriented for families comparing cost first",
    ],
  },
  vidhipujan: {
    name: "VidhiPujan",
    slug: "vidhipujan",
    delhiNcrFocus: "Primary",
    networkSize: "Unlisted",
    catalogSize: "300+",
    nationalCoverage: "20+ cities",
    sameDayBooking: "Partial",
    pricing: "About Rs. 3,000+ for Delhi Griha Pravesh",
    pricePositioning: "Moderate",
    structuredData: "Partial",
    contentHub: "Limited",
    cityPages: "20+ city pages",
    strengths: [
      "Large ceremony catalog and local relevance",
      "Competitive price range against many Delhi options",
    ],
    tradeoffs: [
      "Less differentiated service promise than Navdhya",
      "Weaker trust story around clean-up and bundled support",
    ],
  },
  bookmypooja: {
    name: "BookMyPooja",
    slug: "bookmypooja",
    delhiNcrFocus: "Not primary",
    networkSize: "Verified",
    catalogSize: "100+",
    nationalCoverage: "10+ cities",
    sameDayBooking: "Partial",
    pricing: "About Rs. 5,000+ for Delhi Griha Pravesh",
    pricePositioning: "Moderate",
    structuredData: "Partial",
    contentHub: "Limited",
    cityPages: "10+ cities",
    strengths: [
      "Broad service spread and simple booking",
      "Useful for general puja discovery",
    ],
    tradeoffs: [
      "Not sharply localized for Delhi NCR",
      "Less price-led than Navdhya for core home ceremonies",
    ],
  },
  harivara: {
    name: "Harivara",
    slug: "harivara",
    delhiNcrFocus: "Regional alternative",
    networkSize: "Unlisted",
    catalogSize: "Specialized",
    nationalCoverage: "Selected metros",
    sameDayBooking: "Unknown",
    pricing: "Varies by ritual and language requirement",
    pricePositioning: "Mid-range",
    structuredData: "Unknown",
    contentHub: "Limited",
    cityPages: "Limited",
    strengths: [
      "Useful option for multilingual and South Indian ritual demand",
      "Alternative when families want a regional ritual format",
    ],
    tradeoffs: [
      "Less visible local footprint in Delhi search than major players",
      "Lower volume of clear comparison signals for searchers",
    ],
  },
};
