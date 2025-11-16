import ToolsClient from './client'
import { seoManager } from "@/lib/seo-manager"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/tools",
    title: "Tools & Calculators - Navdhya",
    description: "Horoscope, Muhurat, and Panchang calculators for determining auspicious timings and astrological insights.",
    keywords: ["astrology tools", "muhurat calculator", "panchang calculator", "horoscope calculator", "spiritual tools"]
  })
}

export default function ToolsPage() {
  return <ToolsClient />
}