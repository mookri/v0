// SEO Configuration and Schema Markup for Navdhya
import { generateBreadcrumbSchema, generateFAQSchema, generateGenericSchema } from "@/lib/schema-utils"

export const SEO_CONFIG = {
  siteName: "Navdhya",
  siteUrl: "https://www.navdhya.com",
  defaultTitle: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
  defaultDescription: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  defaultImage: "/favicon/android-chrome-512x512.png",
  locale: "en_US",
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
  name: "Navdhya",
  url: "https://www.navdhya.com",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  publisher: {
    "@type": "Organization",
    name: "Navdhya",
    logo: {
      "@type": "ImageObject",
      url: "https://www.navdhya.com/favicon/android-chrome-512x512.png"
    }
  },
  potentialAction: {
    "@type": "SearchAction",
    target: "https://www.navdhya.com/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
};

// Organization Schema Markup
export const ORGANIZATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Organization",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  logo: "https://www.navdhya.com/favicon/android-chrome-512x512.png",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  sameAs: [
    // Add social media links here when available
  ]
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
    postalCode: "",
    addressCountry: "IN"
  },
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
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