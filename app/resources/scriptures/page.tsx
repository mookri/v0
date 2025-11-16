import ScripturesClient from './client'
import { seoManager } from "@/lib/seo-manager"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/scriptures",
    title: "Scriptures & Texts - Navdhya",
    description: "Rich spiritual library of ancient texts, scriptures, and sacred writings with translations and commentary.",
    keywords: ["sacred scriptures", "vedic texts", "hindu scriptures", "ancient wisdom", "spiritual texts"]
  })
}

export default function ScripturesPage() {
  return <ScripturesClient />
}