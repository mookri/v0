import { seoManager } from "@/lib/seo-manager"
import ScripturesClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/scriptures",
    title: "Scriptures & Texts - Navdhya",
    description: "Rich spiritual library of ancient texts, scriptures, and sacred writings with translations and commentary.",
    keywords: ["sacred scriptures", "vedic texts", "hindu scriptures", "ancient wisdom", "spiritual texts"]
  })
}

export default async function ScripturesPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/scriptures');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ScripturesClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}