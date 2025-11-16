import { seoManager } from "@/lib/seo-manager"
import StoriesClient from "./client"
import Script from "next/script"

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

export default function UserStoriesPage() {
  const userStoriesSchema = JSON.stringify(USER_STORIES_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: userStoriesSchema }}
      />
      <StoriesClient userStoriesSchema={userStoriesSchema} />
    </>
  )
}