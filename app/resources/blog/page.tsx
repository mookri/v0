import { seoManager } from "@/lib/seo-manager"
import BlogClient from "./client"
import Script from "next/script"

// Schema markup for the blog page
const BLOG_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Articles & Blogs - Navdhya",
  description: "SEO content covering spiritual topics, rituals, philosophy, and practical guidance for your spiritual journey.",
  path: "/resources/blog",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Articles & Blogs", url: "https://www.navdhya.com/resources/blog" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/blog",
    title: "Articles & Blogs - Navdhya",
    description: "SEO content covering spiritual topics, rituals, philosophy, and practical guidance for your spiritual journey.",
    keywords: ["spiritual articles", "vedic wisdom", "hindu philosophy", "rituals", "spiritual guidance"]
  })
}

export default function BlogPage() {
  const blogSchema = JSON.stringify(BLOG_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: blogSchema }}
      />
      <BlogClient blogSchema={blogSchema} />
    </>
  )
}