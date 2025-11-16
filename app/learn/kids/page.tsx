import { seoManager } from "@/lib/seo-manager"
import KidsLearningClient from "./client"
import Script from "next/script"

// Schema markup for the kids learning page
const KIDS_LEARNING_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Learning for Kids - Navdhya",
  description: "Mythology and value-based learning modules designed specifically for children. Engaging content that teaches traditional Indian values through stories, games, and interactive activities.",
  path: "/learn/kids",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "For Kids", url: "https://www.navdhya.com/learn/kids" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/learn/kids",
    title: "Learning for Kids - Navdhya",
    description: "Mythology and value-based learning modules designed specifically for children. Engaging content that teaches traditional Indian values through stories, games, and interactive activities.",
    keywords: ["kids learning", "mythology for kids", "value-based learning", "indian culture for children"]
  })
}

export default function KidsLearningPage() {
  const kidsSchema = JSON.stringify(KIDS_LEARNING_SCHEMA)
  
  return (
    <>
      <KidsLearningClient kidsSchema={kidsSchema} />
    </>
  )
}