import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import GalleryClient from './client'

// Schema markup for the gallery page
const GALLERY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Gallery - Navdhya",
  description: "Explore our collection of spiritual events, rituals, and community gatherings through photos and videos.",
  path: "/gallery",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Gallery", url: "https://www.navdhya.com/gallery" }
  ]
})

export default async function GalleryPage() {
  const gallerySchema = JSON.stringify(GALLERY_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/gallery');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: gallerySchema }}
        />
        <GalleryClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}