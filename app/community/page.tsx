import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"
import CommunityClient from './client'

// Schema markup for the main community page
const COMMUNITY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Community - Navdhya",
  description: "Connect with fellow spiritual seekers, share experiences, and participate in community initiatives. Join our circle of like-minded individuals dedicated to spiritual growth.",
  path: "/community",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" }
  ]
})

export default async function CommunityPage() {
  const communitySchema = JSON.stringify(COMMUNITY_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/community');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: communitySchema }}
        />
        <CommunityClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}