import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import CoursesClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Spiritual Courses - Navdhya",
  description: "Enroll in our comprehensive spiritual courses covering Gita Studies, Meditation, and Ritual Science. Learn from certified experts and deepen your understanding of Vedic wisdom.",
  path: "/learn/courses",
  keywords: ["spiritual courses", "gita studies", "meditation", "ritual science"]
})

// Schema markup for the courses page
const COURSES_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Spiritual Courses - Navdhya",
  description: "Enroll in our comprehensive spiritual courses covering Gita Studies, Meditation, and Ritual Science. Learn from certified experts and deepen your understanding of Vedic wisdom.",
  path: "/learn/courses",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "Courses", url: "https://www.navdhya.com/learn/courses" }
  ]
})

export default async function CoursesPage() {
  const coursesSchema = JSON.stringify(COURSES_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/learn/courses');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: coursesSchema }}
        />
        <CoursesClient coursesSchema={coursesSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}