// /app/seo-demo/page.tsx
// Demo page showcasing the master-slave SEO strategy

import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "SEO Demo Page",
  description: "Demonstration of the master-slave SEO strategy implementation for Navdhya.",
  path: "/seo-demo",
  keywords: ["seo", "demo", "master-slave", "structured data", "metadata"]
})

// Schema markup for the demo page
const DEMO_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "SEO Demo Page - Navdhya",
  description: "Demonstration of the master-slave SEO strategy implementation for Navdhya.",
  path: "/seo-demo",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "SEO Demo", url: "https://www.navdhya.com/seo-demo" }
  ]
})

export default function SEODemoPage() {
  const demoSchema = JSON.stringify(DEMO_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: demoSchema }}
      />
      <div className="max-w-4xl mx-auto px-4 py-12">
        <div className="text-center mb-12">
          <h1 className="text-4xl font-serif font-bold mb-6">SEO Master-Slave Strategy Demo</h1>
          <p className="text-xl text-muted-foreground max-w-2xl mx-auto">
            This page demonstrates the implementation of our master-slave SEO strategy for MNC-grade optimization.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-2xl font-serif font-semibold mb-4">Master Component</h2>
            <ul className="space-y-2 text-muted-foreground">
              <li>• Centralized SEO configuration</li>
              <li>• Global settings management</li>
              <li>• Consistent metadata generation</li>
              <li>• Structured data templates</li>
              <li>• Located in /lib/seo-manager.ts</li>
            </ul>
          </div>
          
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-2xl font-serif font-semibold mb-4">Slave Component</h2>
            <ul className="space-y-2 text-muted-foreground">
              <li>• Page-specific content injection</li>
              <li>• Unique metadata generation</li>
              <li>• Custom structured data</li>
              <li>• Path and URL management</li>
              <li>• Located in individual pages</li>
            </ul>
          </div>
        </div>

        <div className="bg-card border border-border rounded-lg p-8 mb-12">
          <h2 className="text-2xl font-serif font-semibold mb-6">Implementation Benefits</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center">
              <div className="w-16 h-16 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl font-bold">1</span>
              </div>
              <h3 className="font-medium text-lg mb-2">Eliminates Duplicate SEO</h3>
              <p className="text-sm text-muted-foreground">
                Each page generates unique metadata based on its specific content
              </p>
            </div>
            
            <div className="text-center">
              <div className="w-16 h-16 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl font-bold">2</span>
              </div>
              <h3 className="font-medium text-lg mb-2">Maintains Consistency</h3>
              <p className="text-sm text-muted-foreground">
                All pages follow the same SEO template and standards
              </p>
            </div>
            
            <div className="text-center">
              <div className="w-16 h-16 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl font-bold">3</span>
              </div>
              <h3 className="font-medium text-lg mb-2">Enables Rich Results</h3>
              <p className="text-sm text-muted-foreground">
                Generates structured data for enhanced search results
              </p>
            </div>
          </div>
        </div>

        <div className="bg-muted border border-border rounded-lg p-8">
          <h2 className="text-2xl font-serif font-semibold mb-4">Technical Implementation</h2>
          <pre className="bg-background border border-border rounded-lg p-4 text-sm overflow-x-auto">
            {`// Master component usage
import { seoManager } from "@/lib/seo-manager"

export const metadata = seoManager.generateMetadata({
  title: "Page Title",
  description: "Page description",
  path: "/page-path",
  keywords: ["keyword1", "keyword2"]
})

const PAGE_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Page Name",
  description: "Page description",
  path: "/page-path"
})`}
          </pre>
        </div>
      </div>
    </>
  )
}