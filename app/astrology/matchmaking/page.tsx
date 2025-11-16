import { seoManager } from "@/lib/seo-manager"
import HoroscopeMatchingClient from "./client"
import Script from "next/script"

// Schema markup for the Horoscope Matching page
const HOROSCOPE_MATCHING_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Horoscope Matching - Navdhya",
  description: "Expert Vedic astrology horoscope matching for marriage compatibility and relationship harmony.",
  path: "/astrology/matchmaking",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Horoscope Matching", url: "https://www.navdhya.com/astrology/matchmaking" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/matchmaking",
    title: "Horoscope Matching - Navdhya",
    description: "Expert Vedic astrology horoscope matching for marriage compatibility and relationship harmony.",
    keywords: ["horoscope matching", "kundli milan", "marriage compatibility", "vedic astrology"]
  })
}

export default function HoroscopeMatchingPage() {
  const horoscopeMatchingSchema = JSON.stringify(HOROSCOPE_MATCHING_SCHEMA)
  
  return (
    <>
      <HoroscopeMatchingClient horoscopeMatchingSchema={horoscopeMatchingSchema} />
    </>
  )
}