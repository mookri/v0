import { seoManager } from "@/lib/seo-manager"
import JoinPanditClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the join as pandit page
const JOIN_PANDIT_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Join as Pandit - Navdhya",
  description: "Become a verified pandit with Navdhya. Join our network of certified Vedic experts and serve families in Delhi NCR with authentic puja services and spiritual guidance.",
  path: "/pandits/join",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" },
    { name: "Join as Pandit", url: "https://www.navdhya.com/pandits/join" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/pandits/join",
    title: "Join as Pandit - Navdhya",
    description: "Become a verified pandit with Navdhya. Join our network of certified Vedic experts and serve families in Delhi NCR with authentic puja services and spiritual guidance.",
    keywords: ["join as pandit", "certified pandit", "vedic expert", "puja services"]
  })
}

export default async function JoinPanditPage() {
  const joinSchema = JSON.stringify(JOIN_PANDIT_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/pandits/join');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: joinSchema }}
        />
        <JoinPanditClient joinSchema={joinSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}