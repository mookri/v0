import NewsClient from './client'
import { seoManager } from "@/lib/seo-manager"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/news",
    title: "News & Updates - Navdhya",
    description: "Real-time temple information, spiritual event updates, and latest developments in our community.",
    keywords: ["spiritual news", "temple updates", "vedic events", "community news", "spiritual updates"]
  })
}

export default function NewsPage() {
  return <NewsClient />
}