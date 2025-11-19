import { seoManager } from "@/lib/seo-manager"
import ToolsClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the tools page
const TOOLS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Tools & Calculators - Navdhya",
  description: "Horoscope, Muhurat, and Panchang calculators for determining auspicious timings and astrological insights.",
  path: "/resources/tools",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Tools & Calculators", url: "https://www.navdhya.com/resources/tools" }
  ]
})

export default async function ToolsPage() {
  const toolsSchema = JSON.stringify(TOOLS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/tools');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ToolsClient toolsSchema={toolsSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}