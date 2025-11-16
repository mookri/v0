import { seoManager } from "@/lib/seo-manager"
import VolunteerClient from "./client"
import Script from "next/script"

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

export default function VolunteerProgramsPage() {
  const volunteerSchema = JSON.stringify(VOLUNTEER_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: volunteerSchema }}
      />
      <VolunteerClient volunteerSchema={volunteerSchema} />
    </>
  )
}