import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Phone, Mail, MessageCircle, Clock, MapPin, User, Building, Send, Upload, Shield, Award, Headphones } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'
import { ContactForm, ContactMap } from '@/components/sections/contact'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Contact Us - Premium Spiritual Services | Navdhya",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi NCR. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies. 24/7 support available.",
  path: "/contact",
  keywords: [
    "Contact Navdhya",
    "Vedic Pandit Booking",
    "Puja Services Delhi",
    "Spiritual Consultation",
    "24/7 Support"
  ]
})

// Schema markup for the contact page
const CONTACT_SCHEMA = seoManager.generateSchema({
  type: "ContactPage",
  name: "Contact Us - Navdhya",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi NCR. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
  path: "/contact",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Contact Us", url: "https://www.navdhya.com/contact" }
  ]
})

// Local business schema with address information
const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Navdhya",
  "image": "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
  "telephone": "+91-7289000377",
  "email": "help@navdhya.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Pandav Nagar",
    "addressLocality": "New Delhi",
    "postalCode": "110092",
    "addressCountry": "IN"
  },
  "url": "https://www.navdhya.com",
  "sameAs": [
    "https://www.facebook.com/navdhya",
    "https://www.instagram.com/navdhya",
    "https://twitter.com/navdhya"
  ],
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday"
      ],
      "opens": "09:00",
      "closes": "20:00"
    }
  ]
}

export default async function ContactPage() {
  const contactSchema = JSON.stringify(CONTACT_SCHEMA)
  const localBusinessSchema = JSON.stringify(LOCAL_BUSINESS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/contact');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: contactSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: localBusinessSchema }}
        />
        <ContactForm />
        <ContactMap />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}