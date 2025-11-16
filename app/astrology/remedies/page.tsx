import { seoManager } from "@/lib/seo-manager"
import RemediesClient from "./client"
import Script from "next/script"

// Schema markup for the Remedies page
const REMEDIES_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Astrological Remedies - Navdhya",
  description: "Personalized Vedic astrological remedies to overcome challenges and enhance positive planetary influences in your life.",
  path: "/astrology/remedies",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Astrological Remedies", url: "https://www.navdhya.com/astrology/remedies" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/remedies",
    title: "Astrological Remedies - Navdhya",
    description: "Personalized Vedic astrological remedies to overcome challenges and enhance positive planetary influences in your life.",
    keywords: ["astrological remedies", "vedic remedies", "planetary remedies", "mantra remedies"]
  })
}

export default function RemediesPage() {
  const remediesSchema = JSON.stringify(REMEDIES_SCHEMA)
  
  return (
    <>
      <RemediesClient remediesSchema={remediesSchema} />
    </>
  )
}