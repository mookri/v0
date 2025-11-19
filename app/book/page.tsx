import { seoManager } from "@/lib/seo-manager"
import BookingClient from './client'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Book a Puja",
  description: "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi. Easy online booking for home pujas, temple ceremonies, and spiritual consultations.",
  path: "/booking",
  keywords: [
    "Book Puja Delhi",
    "Online Puja Booking",
    "Vedic Pandit Booking",
    "Spiritual Service Booking",
    "Hindu Ritual Booking"
  ]
})

// Schema markup for the booking page
const BOOKING_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Book a Puja",
  description: "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi",
  path: "/booking"
})

export default function BookingPage() {
  const bookingSchema = JSON.stringify(BOOKING_SCHEMA)
  
  return <BookingClient bookingSchema={bookingSchema} />
}