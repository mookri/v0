import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import CertificationClient from "./client"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Certification Programs - Navdhya",
  description: "Earn authorized cultural training certifications in Vedic rituals, astrology, and spiritual practices. Advance your knowledge and credentials with our professional certification programs.",
  path: "/learn/certified",
  keywords: ["certification programs", "vedic rituals", "astrology certification", "spiritual practices"]
})

// Schema markup for the certification page
const CERTIFICATION_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Certification Programs - Navdhya",
  description: "Earn authorized cultural training certifications in Vedic rituals, astrology, and spiritual practices. Advance your knowledge and credentials with our professional certification programs.",
  path: "/learn/certified",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "Certification Programs", url: "https://www.navdhya.com/learn/certified" }
  ]
})

export default function CertificationPage() {
  const certificationSchema = JSON.stringify(CERTIFICATION_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: certificationSchema }}
      />
      <CertificationClient certificationSchema={certificationSchema} />
    </>
  )
}