import { seoManager } from "@/lib/seo-manager"
import ForumsClient from "./client"
import Script from "next/script"

// Schema markup for the forums page
const FORUMS_SCHEMA = seoManager.generateSchema({
  type: "DiscussionForum",
  name: "Community Forums - Navdhya",
  description: "Join discussions with fellow spiritual seekers. Share experiences, ask questions, and connect with our community of practitioners and learners.",
  path: "/community/forums",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" },
    { name: "Forums", url: "https://www.navdhya.com/community/forums" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/community/forums",
    title: "Community Forums - Navdhya",
    description: "Join discussions with fellow spiritual seekers. Share experiences, ask questions, and connect with our community of practitioners and learners.",
    keywords: ["spiritual forums", "community discussions", "vedic discussions", "spiritual seekers"]
  })
}

export default function ForumsPage() {
  const forumsSchema = JSON.stringify(FORUMS_SCHEMA)
  
  return (
    <>
      <ForumsClient forumsSchema={forumsSchema} />
    </>
  )
}