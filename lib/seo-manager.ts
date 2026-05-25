import { SEO_CONFIG } from "@/lib/seo-config";
import { toAbsoluteUrl, withSiteName } from "@/lib/seo/site";
import { normalizePublicPath, toPublicUrl } from "@/lib/seo/routes";

const MASTER_SEO_TEMPLATE = {
  siteName: SEO_CONFIG.siteName,
  siteUrl: SEO_CONFIG.siteUrl,
  defaultImage: SEO_CONFIG.defaultImage,
  locale: SEO_CONFIG.locale,
  twitter: SEO_CONFIG.twitter,
  organization: SEO_CONFIG.organization,
};

export class SEOManager {
  private masterConfig: typeof MASTER_SEO_TEMPLATE;

  constructor() {
    this.masterConfig = MASTER_SEO_TEMPLATE;
  }

  generateMetadata(pageData: {
    title: string;
    description: string;
    path: string;
    image?: string;
    keywords?: string[];
    type?: string;
    publishedTime?: string;
    modifiedTime?: string;
  }) {
    const canonicalUrl = toPublicUrl(pageData.path);
    const imageUrl = toAbsoluteUrl(pageData.image || this.masterConfig.defaultImage);
    const fullTitle = withSiteName(pageData.title);

    return {
      title: fullTitle,
      description: pageData.description,
      keywords: [pageData.title, this.masterConfig.siteName, ...(pageData.keywords || [])],
      robots: {
        index: true,
        follow: true,
        googleBot: {
          index: true,
          follow: true,
          "max-image-preview": "large" as const,
          "max-snippet": -1,
          "max-video-preview": -1,
        },
      },
      openGraph: {
        title: fullTitle,
        description: pageData.description,
        url: canonicalUrl,
        siteName: this.masterConfig.siteName,
        images: [
          {
            url: imageUrl,
            width: 1200,
            height: 630,
            alt: `${pageData.title} - ${this.masterConfig.siteName}`,
          },
        ],
        locale: this.masterConfig.locale,
        type: pageData.type || "website",
        ...(pageData.publishedTime ? { publishedTime: pageData.publishedTime } : {}),
        ...(pageData.modifiedTime ? { modifiedTime: pageData.modifiedTime } : {}),
      },
      twitter: {
        card: "summary_large_image",
        title: fullTitle,
        description: pageData.description,
        images: [imageUrl],
        site: this.masterConfig.twitter.site,
      },
      alternates: {
        canonical: canonicalUrl,
      },
      other: {
        "x-canonical-path": normalizePublicPath(pageData.path),
      },
    };
  }

  generateSchema(schemaData: {
    type: string;
    name: string;
    description: string;
    path: string;
    image?: string;
    breadcrumbs?: Array<{ name: string; url: string }>;
    faqs?: Array<{ question: string; answer: string }>;
    price?: string;
    currency?: string;
  }) {
    const canonicalUrl = toPublicUrl(schemaData.path);
    const imageUrl = toAbsoluteUrl(schemaData.image || this.masterConfig.defaultImage);

    const baseSchema: Record<string, unknown> = {
      "@context": "https://schema.org",
      "@type": schemaData.type,
      "@id": `${canonicalUrl}#${String(schemaData.type).toLowerCase()}`,
      name: schemaData.name,
      url: canonicalUrl,
      description: schemaData.description,
      image: imageUrl,
      provider: {
        "@type": "Organization",
        "@id": `${this.masterConfig.siteUrl}#organization`,
        name: this.masterConfig.organization.name,
        url: this.masterConfig.organization.url,
        logo: toAbsoluteUrl(this.masterConfig.organization.logo),
      },
    };

    if (schemaData.breadcrumbs?.length) {
      baseSchema.breadcrumb = {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        itemListElement: schemaData.breadcrumbs.map((item, index) => ({
          "@type": "ListItem",
          position: index + 1,
          name: item.name,
          item: item.url,
        })),
      };
    }

    if (schemaData.faqs?.length) {
      baseSchema.mainEntity = schemaData.faqs.map((faq) => ({
        "@type": "Question",
        name: faq.question,
        acceptedAnswer: {
          "@type": "Answer",
          text: faq.answer,
        },
      }));
    }

    if (schemaData.price) {
      baseSchema.offers = {
        "@type": "Offer",
        priceCurrency: schemaData.currency || "INR",
        price: schemaData.price,
        availability: "https://schema.org/InStock",
        url: canonicalUrl,
      };
    }

    if (schemaData.type === "Service") {
      baseSchema.serviceType = schemaData.name;
      baseSchema.areaServed = [
        { "@type": "Place", name: "Delhi NCR" },
        { "@type": "Country", name: "India" },
      ];
      baseSchema.availableChannel = {
        "@type": "ServiceChannel",
        serviceUrl: canonicalUrl,
        availableLanguage: ["English", "Hindi"],
        serviceLocation: {
          "@type": "Place",
          name: "Delhi NCR and online",
        },
      };
    }

    return baseSchema;
  }

  generateBreadcrumb(items: Array<{ name: string; url: string }>) {
    return {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: items.map((item, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: item.name,
        item: item.url,
      })),
    };
  }

  generateFAQSchema(faqItems: Array<{ question: string; answer: string }>) {
    return {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      mainEntity: faqItems.map((item) => ({
        "@type": "Question",
        name: item.question,
        acceptedAnswer: {
          "@type": "Answer",
          text: item.answer,
        },
      })),
    };
  }

  generateSiteNavigation(navigationItems: Array<{ name: string; url: string; position: number }>) {
    return {
      "@context": "https://schema.org",
      "@type": "ItemList",
      itemListElement: navigationItems.map((item) => ({
        "@type": "SiteNavigationElement",
        position: item.position,
        name: item.name,
        url: item.url,
      })),
    };
  }
}

export const seoManager = new SEOManager();

export function generateJsonLd(schema: unknown): string {
  return JSON.stringify(schema);
}

export const DEFAULT_SEO = {
  title: "Navdhya | Verified Pandit Booking and Vedic Puja Services",
  description: "Book verified pandits for authentic home pujas, online rituals, astrology consultations, and Hindu ceremonies with Navdhya across Delhi NCR.",
  keywords: [
    "Navdhya",
    "verified pandit booking",
    "Vedic puja services",
    "home puja services Delhi NCR",
    "online puja services",
    "book pandit online Delhi NCR",
    "Hindu ceremonies",
    "astrology consultation",
    "spiritual guidance",
    "pandit in Delhi NCR",
  ],
};
