import dynamic from "next/dynamic"
import { seoManager } from "@/lib/seo-manager"

// Dynamically import the client component
const ServicesClient = dynamic(() => import("./client"), {
  loading: () => <div className="min-h-screen flex items-center justify-center">Loading...</div>
})

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Puja Services",
  description: "Authentic Hindu puja services performed by certified Vedic pandits in Delhi",
  path: "/services",
  keywords: ["puja services", "vedic pandits", "hindu rituals", "spiritual services"]
})

export default function ServicesPage() {
  return <ServicesClient />
}