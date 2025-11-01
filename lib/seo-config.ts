// SEO Configuration and Schema Markup for Navdhya
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
export const BREADCRUMB_SCHEMA = (items: Array<{ name: string; url: string }>) => ({
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: items.map((item, index) => ({
    "@type": "ListItem",
    position: index + 1,
    name: item.name,
    item: item.url
  }))
});

// FAQ Schema for FAQ pages
export const FAQ_SCHEMA = (faqItems: Array<{ question: string; answer: string }>) => ({
  "@context": "https://schema.org",
  "@type": "FAQPage",
  mainEntity: faqItems.map(item => ({
    "@type": "Question",
    name: item.question,
    acceptedAnswer: {
      "@type": "Answer",
      text: item.answer
    }
  }))
});

// Service Schema for puja services
export const SERVICE_SCHEMA = (service: {
  name: string;
  description: string;
  url: string;
  image?: string;
  price?: string;
}) => ({
  "@context": "https://schema.org",
  "@type": "Service",
  serviceType: "Hindu Ritual Services",
  provider: {
    "@type": "Organization",
    name: "Navdhya"
  },
  name: service.name,
  description: service.description,
  url: service.url,
  ...(service.image && {
    image: [service.image]
  }),
  ...(service.price && {
    offers: {
      "@type": "Offer",
      priceCurrency: "INR",
      price: service.price
    }
  })
});

// Article Schema for blog posts
export const ARTICLE_SCHEMA = (article: {
  headline: string;
  description: string;
  datePublished: string;
  dateModified: string;
  author: string;
  image?: string;
}) => ({
  "@context": "https://schema.org",
  "@type": "Article",
  headline: article.headline,
  description: article.description,
  datePublished: article.datePublished,
  dateModified: article.dateModified,
  author: {
    "@type": "Person",
    name: article.author
  },
  publisher: {
    "@type": "Organization",
    name: "Navdhya",
    logo: {
      "@type": "ImageObject",
      url: "https://www.navdhya.com/favicon/android-chrome-512x512.png"
    }
  },
  ...(article.image && {
    image: article.image
  })
});

// Legal Page Schema Markup
export const LEGAL_PAGE_SCHEMA = (page: {
  name: string;
  url: string;
  description: string;
}) => ({
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: page.name,
  url: page.url,
  description: page.description,
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: page.name, url: page.url }
  ])
});
