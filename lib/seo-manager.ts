// /lib/seo-manager.ts
// Master-slave SEO strategy for dynamic metadata generation

import { SEO_CONFIG } from "@/lib/seo-config"

// Master SEO template - acts as the base configuration
const MASTER_SEO_TEMPLATE = {
  siteName: SEO_CONFIG.siteName,
  siteUrl: SEO_CONFIG.siteUrl,
  defaultImage: SEO_CONFIG.defaultImage,
  locale: SEO_CONFIG.locale,
  twitter: SEO_CONFIG.twitter,
  organization: SEO_CONFIG.organization
}

// SEO Manager class to handle the master-slave relationship
export class SEOManager {
  private masterConfig: typeof MASTER_SEO_TEMPLATE

  constructor() {
    this.masterConfig = MASTER_SEO_TEMPLATE
  }

  // Generate page-specific metadata by merging with master template
  generateMetadata(pageData: {
    title: string
    description: string
    path: string
    image?: string
    keywords?: string[]
    type?: string
    publishedTime?: string
    modifiedTime?: string
  }) {
    const canonicalUrl = `${this.masterConfig.siteUrl}${pageData.path}`
    const imageUrl = pageData.image || this.masterConfig.defaultImage
    
    return {
      title: `${pageData.title} | ${this.masterConfig.siteName}`,
      description: pageData.description,
      keywords: [
        pageData.title,
        this.masterConfig.siteName,
        ...(pageData.keywords || [])
      ],
      openGraph: {
        title: `${pageData.title} | ${this.masterConfig.siteName}`,
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
        ...(pageData.publishedTime && { publishedTime: pageData.publishedTime }),
        ...(pageData.modifiedTime && { modifiedTime: pageData.modifiedTime }),
      },
      twitter: {
        card: "summary_large_image",
        title: `${pageData.title} | ${this.masterConfig.siteName}`,
        description: pageData.description,
        images: [imageUrl],
        site: this.masterConfig.twitter.site,
      },
      alternates: {
        canonical: canonicalUrl,
      },
    }
  }

  // Generate structured data schema by merging with master template
  generateSchema(schemaData: {
    type: string
    name: string
    description: string
    path: string
    image?: string
    breadcrumbs?: Array<{ name: string; url: string }>
    faqs?: Array<{ question: string; answer: string }>
    price?: string
    currency?: string
  }) {
    const canonicalUrl = `${this.masterConfig.siteUrl}${schemaData.path}`
    const imageUrl = schemaData.image || this.masterConfig.defaultImage
    
    const baseSchema = {
      "@context": "https://schema.org",
      "@type": schemaData.type,
      name: schemaData.name,
      url: canonicalUrl,
      description: schemaData.description,
    }

    // Add image if available
    if (imageUrl) {
      (baseSchema as any).image = imageUrl
    }

    // Add breadcrumbs if available
    if (schemaData.breadcrumbs && schemaData.breadcrumbs.length > 0) {
      (baseSchema as any).breadcrumb = {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        itemListElement: schemaData.breadcrumbs.map((item, index) => ({
          "@type": "ListItem",
          position: index + 1,
          name: item.name,
          item: item.url
        }))
      }
    }

    // Add FAQ data if available
    if (schemaData.faqs && schemaData.faqs.length > 0) {
      (baseSchema as any).mainEntity = schemaData.faqs.map(faq => ({
        "@type": "Question",
        name: faq.question,
        acceptedAnswer: {
          "@type": "Answer",
          text: faq.answer
        }
      }))
    }

    // Add offer information for services
    if (schemaData.price) {
      (baseSchema as any).offers = {
        "@type": "Offer",
        priceCurrency: schemaData.currency || "INR",
        price: schemaData.price
      }
    }

    // Add organization information
    (baseSchema as any).provider = {
      "@type": "Organization",
      name: this.masterConfig.organization.name,
      url: this.masterConfig.organization.url,
      logo: this.masterConfig.organization.logo
    }

    return baseSchema
  }

  // Generate breadcrumb schema
  generateBreadcrumb(items: Array<{ name: string; url: string }>) {
    return {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: items.map((item, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: item.name,
        item: item.url
      }))
    }
  }

  // Generate FAQ schema
  generateFAQSchema(faqItems: Array<{ question: string; answer: string }>) {
    return {
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
    }
  }
  
  // Generate SiteNavigationElement schema for sitelinks
  generateSiteNavigation(navigationItems: Array<{ name: string; url: string; position: number }>) {
    return {
      "@context": "https://schema.org",
      "@type": "ItemList",
      itemListElement: navigationItems.map(item => ({
        "@type": "SiteNavigationElement",
        position: item.position,
        name: item.name,
        url: item.url
      }))
    }
  }
}

// Create a singleton instance for the application
export const seoManager = new SEOManager()

// Helper function for client components to generate JSON-LD
export function generateJsonLd(schema: any): string {
  return JSON.stringify(schema)
}

// Default SEO configuration for pages that don't need custom metadata
export const DEFAULT_SEO = {
  title: `Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion`,
  description: `Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.`,
  keywords: [
    "Navdhya Puja in Delhi",
    "Puja Services in Delhi",
    "Spiritual Wellness",
    "Vedic Rituals",
    "Grah Shanti",
    "Homam",
    "Book Pandit Online",
    "Trusted Pandits in Delhi",
    "Hindu Puja",
    "Puja for Peace and Prosperity",
  ],
}