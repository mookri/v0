import { seoManager } from "@/lib/seo-manager"
import BookConsultationClient from "./client"
import Script from "next/script"

// Schema markup for the book consultation page
const BOOK_CONSULTATION_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Book Pandit Consultation - Navdhya",
  description: "Book a personalized consultation with verified Vedic pandits for puja services, astrology readings, and spiritual guidance in Delhi NCR.",
  path: "/pandits/book",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" },
    { name: "Book Consultation", url: "https://www.navdhya.com/pandits/book" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/pandits/book",
    title: "Book Pandit Consultation - Navdhya",
    description: "Book a personalized consultation with verified Vedic pandits for puja services, astrology readings, and spiritual guidance in Delhi NCR.",
    keywords: ["book pandit", "vedic consultation", "puja services", "astrology reading"]
  })
}

export default function BookConsultationPage() {
  const consultationSchema = JSON.stringify(BOOK_CONSULTATION_SCHEMA)
  
  return (
    <>
      <BookConsultationClient consultationSchema={consultationSchema} />
    </>
  )
}