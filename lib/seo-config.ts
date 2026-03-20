// SEO Configuration and Schema Markup for Navdhya
import { generateBreadcrumbSchema, generateFAQSchema, generateGenericSchema } from "@/lib/schema-utils"

export const SEO_CONFIG = {
  siteName: "Navdhya",
  siteUrl: "https://www.navdhya.com",
  defaultTitle: "Navdhya | Verified Pandit Booking & Vedic Puja Services — Home & Online",
  defaultDescription: "Book verified pandits for authentic home pujas, online rituals, astrology consultations, and Hindu ceremonies with Navdhya. Trusted by families across Delhi-NCR and expanding pan-India.",
  defaultImage: "/favicon/android-chrome-512x512.png",
  locale: "en_IN",
  type: "website",
  twitter: {
    site: "@navdhya",
    cardType: "summary_large_image"
  },
  organization: {
    name: "Navdhya",
    url: "https://www.navdhya.com",
    logo: "/favicon/android-chrome-512x512.png"
  }
};

// Schema Markup for the main website
export const WEBSITE_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "https://www.navdhya.com#website",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  inLanguage: "en-IN",
  description: "Navdhya is a Vedic ritual services platform offering verified pandit booking, home and online puja services, astrology consultations, and spiritual guidance across India.",
  publisher: {
    "@type": "Organization",
    "@id": "https://www.navdhya.com#organization",
    name: "Navdhya",
    logo: {
      "@type": "ImageObject",
      url: "https://www.navdhya.com/favicon/android-chrome-512x512.png"
    }
  }
};

// Organization Schema Markup
export const ORGANIZATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://www.navdhya.com#organization",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  logo: "https://www.navdhya.com/favicon/android-chrome-512x512.png",
  description: "Navdhya is a Vedic ritual services platform offering verified pandit booking, home and online puja services, astrology consultations, and spiritual guidance across India.",
  foundingDate: "2023",
  knowsAbout: [
    "Vedic ritual services",
    "Home puja booking",
    "Online puja services",
    "Pandit verification",
    "Vedic astrology",
    "Hindu ceremonies"
  ],
  areaServed: [
    { "@type": "Place", name: "Delhi-NCR" },
    { "@type": "Country", name: "India" }
  ],
  contactPoint: {
    "@type": "ContactPoint",
    contactType: "Customer Service",
    availableLanguage: ["English", "Hindi"]
  },
  sameAs: []
};

// Local Business Schema (if applicable)
export const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  name: "Navdhya",
  image: "https://www.navdhya.com/favicon/android-chrome-512x512.png",
  "@id": "https://www.navdhya.com",
  url: "https://www.navdhya.com",
  telephone: "+91-7289000377",
  address: {
    "@type": "PostalAddress",
    streetAddress: "",
    addressLocality: "Delhi",
    addressRegion: "Delhi-NCR",
    postalCode: "",
    addressCountry: "IN"
  },
  description: "Verified pandit booking and authentic Vedic puja services across Delhi-NCR. Home pujas, online rituals, astrology consultations, and Hindu ceremonies by Navdhya.",
  priceRange: "$$"
};

// Breadcrumb Schema for internal pages
export const BREADCRUMB_SCHEMA = (items: Array<{ name: string; url: string }>) => 
  generateBreadcrumbSchema(items);

// FAQ Schema for FAQ pages
export const FAQ_SCHEMA = (faqItems: Array<{ question: string; answer: string }>) => 
  generateFAQSchema(faqItems);

// Service Schema for puja services
export const SERVICE_SCHEMA = (service: {
  name: string;
  description: string;
  url: string;
  image?: string;
  price?: string;
}) => generateGenericSchema({
  type: "Service",
  name: service.name,
  description: service.description,
  path: service.url.replace("https://www.navdhya.com", ""),
  image: service.image,
  price: service.price
});

// Article Schema for blog posts
export const ARTICLE_SCHEMA = (article: {
  headline: string;
  description: string;
  datePublished: string;
  dateModified: string;
  author: string;
  image?: string;
}) => generateGenericSchema({
  type: "Article",
  name: article.headline,
  description: article.description,
  path: `/blog/${article.headline.toLowerCase().replace(/\s+/g, '-')}`,
  image: article.image
});

// Legal Page Schema Markup
export const LEGAL_PAGE_SCHEMA = (page: {
  name: string;
  url: string;
  description: string;
}) => generateGenericSchema({
  type: "WebPage",
  name: page.name,
  description: page.description,
  path: page.url.replace("https://www.navdhya.com", ""),
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: page.name, url: page.url }
  ]
});
