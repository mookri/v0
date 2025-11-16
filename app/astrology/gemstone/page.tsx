import { seoManager } from "@/lib/seo-manager"
import GemstoneClient from "./client"
import Script from "next/script"

// Schema markup for the Gemstone page
const GEMSTONE_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Gemstone Recommendations - Navdhya",
  description: "Personalized Vedic gemstone recommendations based on your birth chart for enhanced positive energies and protection.",
  path: "/astrology/gemstone",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Gemstone Recommendations", url: "https://www.navdhya.com/astrology/gemstone" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/gemstone",
    title: "Gemstone Recommendations - Navdhya",
    description: "Personalized Vedic gemstone recommendations based on your birth chart for enhanced positive energies and protection.",
    keywords: ["gemstone recommendations", "vedic gemstones", "birth chart analysis", "astrology"]
  })
}

export default function GemstonePage() {
  const gemstoneSchema = JSON.stringify(GEMSTONE_SCHEMA)
  
  return (
    <>
      <GemstoneClient gemstoneSchema={gemstoneSchema} />
    </>
  )
}