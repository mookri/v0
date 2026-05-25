import { seoManager } from "@/lib/seo-manager"
import HelpClient from "./client"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'

export const dynamic = 'force-dynamic'

export const metadata = seoManager.generateMetadata({
  title: "Help and Support",
  description: "Find answers to frequently asked questions about our puja services, pandit consultations, booking process, and spiritual guidance.",
  path: "/help",
  keywords: ["help", "support", "faq", "customer service", "spiritual guidance"],
})

const HELP_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Help and Support - Navdhya",
  description:
    "Find answers to frequently asked questions about our puja services, pandit consultations, booking process, and spiritual guidance.",
  path: "/help",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Help and Support", url: "https://www.navdhya.com/help" },
  ],
})

export default async function HelpPage() {
  const context = await resolveContext('/help')

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(HELP_SCHEMA) }}
        />
        <HelpClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
