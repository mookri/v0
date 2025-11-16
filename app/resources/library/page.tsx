import DigitalLibraryClient from './client'
import { seoManager } from "@/lib/seo-manager"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/library",
    title: "Digital Library - Navdhya",
    description: "Downloadable wisdom including spiritual texts, guided meditations, ritual instructions, and educational resources.",
    keywords: ["digital library", "spiritual resources", "vedic texts", "meditation guides", "ritual instructions"]
  })
}

export default function DigitalLibraryPage() {
  return <DigitalLibraryClient />
}