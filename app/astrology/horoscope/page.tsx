import { seoManager } from "@/lib/seo-manager"
import HoroscopeClient from "./client"
import Script from "next/script"

// Schema markup for the Horoscope page
const HOROSCOPE_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Daily Horoscope - Navdhya",
  description: "Accurate daily, weekly, and monthly horoscope predictions based on Vedic astrology for guidance in all aspects of life.",
  path: "/astrology/horoscope",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Horoscope", url: "https://www.navdhya.com/astrology/horoscope" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/horoscope",
    title: "Daily Horoscope - Navdhya",
    description: "Accurate daily, weekly, and monthly horoscope predictions based on Vedic astrology for guidance in all aspects of life.",
    keywords: ["daily horoscope", "weekly horoscope", "monthly horoscope", "vedic astrology predictions"]
  })
}

export default function HoroscopePage() {
  const horoscopeSchema = JSON.stringify(HOROSCOPE_SCHEMA)
  
  return (
    <>
      <HoroscopeClient horoscopeSchema={horoscopeSchema} />
    </>
  )
}