// /hooks/use-seo.ts
// Hook for client components to generate SEO metadata and structured data

import { useEffect, useState } from 'react'
import { seoManager } from '@/lib/seo-manager'

interface SeoData {
  title: string
  description: string
  path: string
  image?: string
  keywords?: string[]
  type?: string
}

interface SchemaData {
  type: string
  name: string
  description: string
  path: string
  image?: string
  breadcrumbs?: Array<{ name: string; url: string }>
  faqs?: Array<{ question: string; answer: string }>
  price?: string
  currency?: string
}

export function useSeo() {
  const [jsonLd, setJsonLd] = useState<string>('')

  // Generate metadata for pages
  const generateMetadata = (data: SeoData) => {
    return seoManager.generateMetadata(data)
  }

  // Generate structured data schema
  const generateSchema = (data: SchemaData) => {
    const schema = seoManager.generateSchema(data)
    setJsonLd(JSON.stringify(schema))
    return schema
  }

  // Generate breadcrumb schema
  const generateBreadcrumb = (items: Array<{ name: string; url: string }>) => {
    const breadcrumb = seoManager.generateBreadcrumb(items)
    setJsonLd(JSON.stringify(breadcrumb))
    return breadcrumb
  }

  // Generate FAQ schema
  const generateFAQSchema = (faqItems: Array<{ question: string; answer: string }>) => {
    const faqSchema = seoManager.generateFAQSchema(faqItems)
    setJsonLd(JSON.stringify(faqSchema))
    return faqSchema
  }

  return {
    generateMetadata,
    generateSchema,
    generateBreadcrumb,
    generateFAQSchema,
    jsonLd
  }
}