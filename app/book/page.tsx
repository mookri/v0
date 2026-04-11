import { seoManager } from "@/lib/seo-manager"
import BookingClient from './client'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Book a Puja with Verified Pandits",
  description: "Book a home puja, online ritual, or astrology session with verified pandits through Navdhya. Share your date, location, and ceremony needs in one place.",
  path: "/book",
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
  path: "/book"
})

export default function BookingPage() {
  const bookingSchema = JSON.stringify(BOOKING_SCHEMA)
  
  return <BookingClient bookingSchema={bookingSchema} />
}
