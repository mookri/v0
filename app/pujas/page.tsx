import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import PujaCatalogClient from "./client"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Puja Services Catalog",
  description: "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas",
  keywords: [
    "puja services",
    "hindu rituals",
    "vedic pandits",
    "spiritual ceremonies",
    "delhi puja services"
  ]
})

// Schema markup for the puja catalog page
const PUJA_CATALOG_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Puja Services Catalog - Navdhya",
  description: "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" }
  ]
})

export default function PujaCatalogPage() {
  const pujaSchema = JSON.stringify(PUJA_CATALOG_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: pujaSchema }}
      />
      <PujaCatalogClient />
    </>
  )
}