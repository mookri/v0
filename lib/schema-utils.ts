// /lib/schema-utils.ts
// Utility functions for generating schema markup without circular dependencies

import { SEO_CONFIG } from "@/lib/seo-config"

// Generate breadcrumb schema
export const generateBreadcrumbSchema = (items: Array<{ name: string; url: string }>) => {
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
export const generateFAQSchema = (faqItems: Array<{ question: string; answer: string }>) => {
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

// Generate generic schema
export const generateGenericSchema = (schemaData: {
  type: string
  name: string
  description: string
  path: string
  image?: string
  breadcrumbs?: Array<{ name: string; url: string }>
  faqs?: Array<{ question: string; answer: string }>
  price?: string
  currency?: string
}) => {
  const canonicalUrl = `${SEO_CONFIG.siteUrl}${schemaData.path}`
  const imageUrl = schemaData.image || SEO_CONFIG.defaultImage
  
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
    (baseSchema as any).breadcrumb = generateBreadcrumbSchema(schemaData.breadcrumbs)
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
    name: SEO_CONFIG.organization.name,
    url: SEO_CONFIG.organization.url,
    logo: SEO_CONFIG.organization.logo
  }

  return baseSchema
}