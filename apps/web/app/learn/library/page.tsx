import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import LibraryClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Self-Learning Library - Navdhya",
  description: "Access our extensive collection of spiritual resources including videos, PDFs, and podcasts. Learn at your own pace with materials from verified Vedic experts.",
  path: "/learn/library",
  keywords: ["self-learning library", "spiritual resources", "vedic materials", "downloadable content"]
})

// Schema markup for the library page
const LIBRARY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Self-Learning Library - Navdhya",
  description: "Access our extensive collection of spiritual resources including videos, PDFs, and podcasts. Learn at your own pace with materials from verified Vedic experts.",
  path: "/learn/library",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "Self-Learning Library", url: "https://www.navdhya.com/learn/library" }
  ]
})

export default async function LibraryPage() {
  const librarySchema = JSON.stringify(LIBRARY_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/learn/library');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: librarySchema }}
        />
        <LibraryClient librarySchema={librarySchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}