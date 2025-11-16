import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import HelpClient from "./client"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Help & Support",
  description: "Find answers to frequently asked questions about our puja services, pandit consultations, and spiritual guidance.",
  path: "/help",
  keywords: ["help", "support", "faq", "customer service", "spiritual guidance"]
})

// Schema markup for the help page
const HELP_SCHEMA = seoManager.generateSchema({
  type: "FAQPage",
  name: "Help & Support - Navdhya",
  description: "Find answers to frequently asked questions about our puja services, pandit consultations, and spiritual guidance.",
  path: "/help",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Help & Support", url: "https://www.navdhya.com/help" }
  ]
})

export default function HelpPage() {
  const helpSchema = JSON.stringify(HELP_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: helpSchema }}
      />
      <HelpClient />
    </>
  )
}