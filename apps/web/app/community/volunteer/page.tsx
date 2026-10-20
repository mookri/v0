import { seoManager } from "@/lib/seo-manager"
import VolunteerClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the volunteer programs page
const VOLUNTEER_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Volunteer Programs - Navdhya",
  description: "CSR & initiatives that make a difference. Join our volunteer programs to contribute to community welfare and spiritual education.",
  path: "/community/volunteer",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" },
    { name: "Volunteer Programs", url: "https://www.navdhya.com/community/volunteer" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/community/volunteer",
    title: "Volunteer Programs - Navdhya",
    description: "CSR & initiatives that make a difference. Join our volunteer programs to contribute to community welfare and spiritual education.",
    keywords: ["volunteer programs", "community service", "csr initiatives", "spiritual education"]
  })
}

export default async function VolunteerProgramsPage() {
  const volunteerSchema = JSON.stringify(VOLUNTEER_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/community/volunteer');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: volunteerSchema }}
        />
        <VolunteerClient volunteerSchema={volunteerSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}