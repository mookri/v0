import { seoManager } from "@/lib/seo-manager"
import BookConsultationClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

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

export default async function BookConsultationPage() {
  const consultationSchema = JSON.stringify(BOOK_CONSULTATION_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/pandits/book');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: consultationSchema }}
        />
        <BookConsultationClient consultationSchema={consultationSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}