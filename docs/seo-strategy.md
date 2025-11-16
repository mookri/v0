# Navdhya SEO Strategy Documentation

## Overview

This document explains the master-slave SEO strategy implemented for the Navdhya Next.js 15 application. The approach ensures each page generates distinct, localized SEO metadata and rich schema results based on its specific content and context.

## Master-Slave Architecture

### Master Component: SEO Manager
- **Location**: [/lib/seo-manager.ts](file:///c%3A/Users/Victo/Downloads/v0-navdhya-ritual-services/lib/seo-manager.ts)
- **Purpose**: Centralized SEO configuration that serves as the template for all pages
- **Responsibilities**:
  - Maintains global SEO settings (site name, default image, locale, etc.)
  - Provides methods for generating page-specific metadata
  - Ensures consistency across all pages
  - Manages structured data schema generation

### Slave Components: Individual Pages
- **Purpose**: Pages act as slaves that inject their unique data into the shared SEO framework
- **Responsibilities**:
  - Provide page-specific content (title, description, keywords)
  - Define page-specific structured data
  - Maintain unique URLs and paths

## Implementation Patterns

### 1. Server Components (Recommended)
For pages that can use server-side rendering, use the SEO manager directly:

```typescript
import { seoManager } from "@/lib/seo-manager"

// Generate page metadata
export const metadata = seoManager.generateMetadata({
  title: "Page Title",
  description: "Page description",
  path: "/page-path",
  keywords: ["keyword1", "keyword2"]
})

// Generate structured data
const PAGE_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Service Name",
  description: "Service description",
  path: "/page-path",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Service Name", url: "https://www.navdhya.com/page-path" }
  ]
})
```

### 2. Client Components
For client components that need dynamic SEO data, use the SEO hook:

```typescript
import { useSeo } from "@/hooks/use-seo"

export default function ClientPage() {
  const { generateMetadata, generateSchema, jsonLd } = useSeo()
  
  // Generate metadata
  const metadata = generateMetadata({
    title: "Dynamic Page Title",
    description: "Dynamic page description",
    path: "/dynamic-path"
  })
  
  // Generate schema
  const schema = generateSchema({
    type: "Service",
    name: "Dynamic Service",
    description: "Dynamic service description",
    path: "/dynamic-path"
  })
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLd }}
      />
      {/* Page content */}
    </>
  )
}
```

## Key Benefits

1. **Eliminates Duplicate SEO Data**: Each page generates unique metadata based on its content
2. **Maintains Consistency**: All pages follow the same SEO template and standards
3. **Scalable**: New pages can easily implement the pattern without duplicating code
4. **MNC-Grade Optimization**: Implements best practices for search engine visibility
5. **Rich Results Support**: Generates structured data for enhanced search results

## Schema Types Supported

1. **WebSite**: For the main website
2. **Organization**: For company information
3. **Service**: For service pages
4. **Article**: For blog posts and articles
5. **WebPage**: For general pages
6. **FAQPage**: For FAQ sections
7. **BreadcrumbList**: For navigation breadcrumbs

## Best Practices

1. **Always use the SEO manager** instead of hardcoded values
2. **Include relevant keywords** for each page
3. **Provide accurate structured data** that matches page content
4. **Use appropriate schema types** for different content types
5. **Test SEO implementation** with Google Rich Results Test

## Migration Guide

To update existing pages to the new SEO strategy:

1. Import the SEO manager: `import { seoManager } from "@/lib/seo-manager"`
2. Replace static metadata with: `export const metadata = seoManager.generateMetadata({...})`
3. Replace static schema with: `const SCHEMA = seoManager.generateSchema({...})`
4. Ensure JSON-LD is properly implemented in the component

## Future Enhancements

1. **Dynamic metadata generation** based on content analysis
2. **SEO performance monitoring** integration
3. **Automated schema validation** during build process
4. **Internationalization support** for multilingual SEO