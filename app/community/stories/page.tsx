import { seoManager } from "@/lib/seo-manager"
import StoriesClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the user stories page
const USER_STORIES_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "User Stories - Navdhya",
  description: "Authentic engagement through real experiences shared by our community members. Read inspiring stories of spiritual growth and transformation.",
  path: "/community/stories",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" },
    { name: "User Stories", url: "https://www.navdhya.com/community/stories" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/community/stories",
    title: "User Stories - Navdhya",
    description: "Authentic engagement through real experiences shared by our community members. Read inspiring stories of spiritual growth and transformation.",
    keywords: ["user stories", "spiritual experiences", "transformation", "community"]
  })
}

export default async function UserStoriesPage() {
  const userStoriesSchema = JSON.stringify(USER_STORIES_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/community/stories');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: userStoriesSchema }}
        />
        <StoriesClient userStoriesSchema={userStoriesSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}