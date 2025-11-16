import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import OnlinePujaClient from "./client"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Online Puja",
  description: "Participate in authentic Hindu rituals remotely with our online puja services. Connect with certified pandits via video call for spiritual ceremonies.",
  path: "/pujas/online",
  keywords: ["online puja", "virtual puja", "remote rituals", "video call pandit", "digital spirituality"]
})

// Schema markup for the Online Puja page
const ONLINE_PUJA_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Online Puja - Navdhya",
  description: "Participate in authentic Hindu rituals remotely with our online puja services. Connect with certified pandits via video call for spiritual ceremonies.",
  path: "/pujas/online",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Online Puja", url: "https://www.navdhya.com/pujas/online" }
  ]
})

export default function OnlinePujaPage() {
  const onlinePujaSchema = JSON.stringify(ONLINE_PUJA_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: onlinePujaSchema }}
      />
      <OnlinePujaClient />
    </>
  )
}