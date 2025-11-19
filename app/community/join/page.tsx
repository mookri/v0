import { seoManager } from "@/lib/seo-manager"
import JoinClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the join page
const JOIN_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Join Navdhya - Circle Membership",
  description: "Circle Membership gateway to exclusive spiritual resources, personalized guidance, and a supportive community of like-minded seekers.",
  path: "/community/join",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" },
    { name: "Join Navdhya", url: "https://www.navdhya.com/community/join" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/community/join",
    title: "Join Navdhya - Circle Membership",
    description: "Circle Membership gateway to exclusive spiritual resources, personalized guidance, and a supportive community of like-minded seekers.",
    keywords: ["circle membership", "spiritual community", "exclusive resources", "personalized guidance"]
  })
}

export default async function JoinNavdhyaPage() {
  const joinSchema = JSON.stringify(JOIN_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/community/join');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: joinSchema }}
        />
        <JoinClient joinSchema={joinSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}