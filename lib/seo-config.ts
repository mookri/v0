import { generateBreadcrumbSchema, generateFAQSchema, generateGenericSchema } from "@/lib/schema-utils";

export const SEO_CONFIG = {
  siteName: "Navdhya",
  siteUrl: "https://www.navdhya.com",
  defaultTitle: "Navdhya | Book Verified Pandits for Home Puja Services in Delhi NCR",
  defaultDescription:
    "Navdhya helps families book verified pandits in Delhi NCR for home pujas, havan, wedding rituals, astrology consultations, and online ceremonies.",
  defaultImage: "/og/navdhya-default-og.png",
  locale: "en_IN",
  type: "website",
  twitter: {
    site: "@navdhya",
    cardType: "summary_large_image",
  },
  organization: {
    name: "Navdhya",
    url: "https://www.navdhya.com",
    logo: "/navdhya-logo.png",
  },
};

export const WEBSITE_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "https://www.navdhya.com#website",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  inLanguage: "en-IN",
  description:
    "Navdhya is a Delhi NCR ritual booking platform for verified pandit booking, home puja services, online ceremonies, and spiritual guidance.",
  publisher: {
    "@type": "Organization",
    "@id": "https://www.navdhya.com#organization",
    name: "Navdhya",
    logo: {
      "@type": "ImageObject",
      url: "https://www.navdhya.com/navdhya-logo.png",
    },
  },
};

export const ORGANIZATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://www.navdhya.com#organization",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  logo: "https://www.navdhya.com/navdhya-logo.png",
  description:
    "Navdhya is a Delhi NCR ritual booking platform for verified pandit booking, home puja services, online ceremonies, and astrology consultations.",
  foundingDate: "2023",
  knowsAbout: [
    "Vedic ritual services",
    "Verified pandit booking",
    "Home puja services",
    "Online puja services",
    "Vedic astrology",
    "Hindu ceremonies",
  ],
  areaServed: [
    { "@type": "Place", name: "Delhi NCR" },
    { "@type": "Country", name: "India" },
  ],
  contactPoint: {
    "@type": "ContactPoint",
    contactType: "Customer Service",
    telephone: "+91-7289000377",
    email: "help@navdhya.com",
    availableLanguage: ["English", "Hindi"],
  },
  sameAs: [
    "https://www.facebook.com/thenavdhya",
    "https://www.instagram.com/thenavdhya",
    "https://www.youtube.com/@thenavdhya",
  ],
};

export const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  name: "Navdhya Ritual Services",
  image: "https://www.navdhya.com/og/navdhya-default-og.png",
  "@id": "https://www.navdhya.com",
  url: "https://www.navdhya.com",
  telephone: "+91-7289000377",
  email: "help@navdhya.com",
  address: {
    "@type": "PostalAddress",
    streetAddress: "Pandav Nagar",
    addressLocality: "Delhi",
    addressRegion: "Delhi",
    postalCode: "110092",
    addressCountry: "IN",
  },
  description:
    "Verified pandit booking and authentic Vedic puja services across Delhi NCR. Home pujas, online rituals, astrology consultations, and Hindu ceremonies by Navdhya.",
  areaServed: "Delhi NCR",
  priceRange: "Rs. 2,500 - Rs. 7,500",
};

export const BREADCRUMB_SCHEMA = (items: Array<{ name: string; url: string }>) =>
  generateBreadcrumbSchema(items);

export const FAQ_SCHEMA = (faqItems: Array<{ question: string; answer: string }>) =>
  generateFAQSchema(faqItems);

export const SERVICE_SCHEMA = (service: {
  name: string;
  description: string;
  url: string;
  image?: string;
  price?: string;
}) =>
  generateGenericSchema({
    type: "Service",
    name: service.name,
    description: service.description,
    path: service.url.replace("https://www.navdhya.com", ""),
    image: service.image,
    price: service.price,
  });

export const ARTICLE_SCHEMA = (article: {
  headline: string;
  description: string;
  datePublished: string;
  dateModified: string;
  author: string;
  image?: string;
}) =>
  generateGenericSchema({
    type: "Article",
    name: article.headline,
    description: article.description,
    path: `/blog/${article.headline.toLowerCase().replace(/\s+/g, "-")}`,
    image: article.image,
  });

export const LEGAL_PAGE_SCHEMA = (page: {
  name: string;
  url: string;
  description: string;
}) =>
  generateGenericSchema({
    type: "WebPage",
    name: page.name,
    description: page.description,
    path: page.url.replace("https://www.navdhya.com", ""),
    breadcrumbs: [
      { name: "Home", url: "https://www.navdhya.com" },
      { name: page.name, url: page.url },
    ],
  });
