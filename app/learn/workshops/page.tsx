import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import WorkshopsClient from "./client"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Spiritual Workshops - Navdhya",
  description: "Join our live spiritual workshops with expert mentors. Experience interactive sessions on meditation, rituals, and Vedic wisdom in an engaging group setting.",
  path: "/learn/workshops",
  keywords: ["spiritual workshops", "live sessions", "vedic wisdom", "interactive learning"]
})

// Schema markup for the workshops page
const WORKSHOPS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Spiritual Workshops - Navdhya",
  description: "Join our live spiritual workshops with expert mentors. Experience interactive sessions on meditation, rituals, and Vedic wisdom in an engaging group setting.",
  path: "/learn/workshops",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "Workshops", url: "https://www.navdhya.com/learn/workshops" }
  ]
})

export default function WorkshopsPage() {
  const workshopsSchema = JSON.stringify(WORKSHOPS_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: workshopsSchema }}
      />
      <WorkshopsClient workshopsSchema={workshopsSchema} />
    </>
  )
}