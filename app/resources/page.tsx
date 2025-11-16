import ResourcesClient from './client'
import { seoManager } from "@/lib/seo-manager"

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources",
    title: "Resources - Navdhya",
    description: "Comprehensive spiritual resources including articles, scriptures, tools, news, and a digital library for your spiritual journey.",
    keywords: ["spiritual resources", "hindu scriptures", "vedic texts", "spiritual tools"]
  })
}

export default function ResourcesPage() {
  return <ResourcesClient />
}