import { seoManager } from "@/lib/seo-manager"
import BlogClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the blog page
const BLOG_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Articles & Blogs - Navdhya",
  description: "SEO content covering spiritual topics, rituals, philosophy, and practical guidance for your spiritual journey.",
  path: "/resources/blog",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Articles & Blogs", url: "https://www.navdhya.com/resources/blog" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/blog",
    title: "Articles & Blogs - Navdhya",
    description: "SEO content covering spiritual topics, rituals, philosophy, and practical guidance for your spiritual journey.",
    keywords: ["spiritual articles", "vedic wisdom", "hindu philosophy", "rituals", "spiritual guidance"]
  })
}

export default async function BlogPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/blog');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <BlogClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}