import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import { Phone, Mail, MessageCircle } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Contact Us",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
  path: "/contact"
})

// Schema markup for the contact page
const CONTACT_SCHEMA = seoManager.generateSchema({
  type: "ContactPage",
  name: "Contact Us - Navdhya",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi",
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
  ]
}

export default function ContactPage() {
  const contactSchema = JSON.stringify(CONTACT_SCHEMA)
  const localBusinessSchema = JSON.stringify(LOCAL_BUSINESS_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: contactSchema }}
      />
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: localBusinessSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 grid md:grid-cols-2 gap-8 overflow-x-hidden w-full">
        <section aria-labelledby="contact-us" className="flex flex-col">
          <h1 id="contact-us" className="font-serif text-3xl">
            Contact Us
          </h1>
          <p className="mt-3 text-muted-foreground">
            We're here to help you choose the right ritual and plan your ceremony.
          </p>
          <form className="mt-6 space-y-4">
            <div>
              <Label htmlFor="name">Full Name</Label>
              <Input id="name" required />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" required />
            </div>
            <div>
              <Label htmlFor="message">Message</Label>
              <Textarea id="message" required />
            </div>
            <Button type="submit" className="bg-primary text-primary-foreground">
              Send
            </Button>
          </form>
          <div className="mt-6 text-sm">
            Or reach us directly:{" "}
            <a className="underline" href="tel:+917289000377">
              +91-7289000377
            </a>{" "}
            •{" "}
            <a className="underline" href="mailto:help@navdhya.com">
              help@navdhya.com
            </a>{" "}
            •{" "}
            <a className="underline" href="https://wa.me/917289000377" target="_blank" rel="noopener noreferrer">
              WhatsApp
            </a>
          </div>
        </section>
        <aside aria-label="Office and Map" className="flex flex-col">
          <div className="space-y-4">
            <div className="flex items-start space-x-3">
              <Phone className="mt-1 h-5 w-5 text-primary" />
              <div>
                <h3 className="font-semibold">Call Us</h3>
                <a className="underline" href="tel:+917289000377">
                  +91-7289000377
                </a>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <Mail className="mt-1 h-5 w-5 text-primary" />
              <div>
                <h3 className="font-semibold">Email Us</h3>
                <a className="underline" href="mailto:help@navdhya.com">
                  help@navdhya.com
                </a>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <MessageCircle className="mt-1 h-5 w-5 text-primary" />
              <div>
                <h3 className="font-semibold">WhatsApp</h3>
                <a className="underline" href="https://wa.me/917289000377" target="_blank" rel="noopener noreferrer">
                  +91-7289000377
                </a>
              </div>
            </div>
            <div className="pt-4">
              <h3 className="font-semibold">Office Address</h3>
              <p>Pandav Nagar Delhi 110092</p>
              <p className="text-sm text-muted-foreground mt-2">Registered Office — New Delhi</p>
            </div>
          </div>
          <div className="rounded-lg border bg-card p-5 flex-grow">
            <h2 className="font-serif text-xl">Office Location</h2>
            <p className="text-sm text-muted-foreground mt-2">Registered Office — New Delhi</p>
            <img
              src="/office-location-map-new-delhi.jpg"
              alt="Map showing office location in New Delhi"
              className="rounded-md border mt-4 w-full h-auto"
            />
          </div>
        </aside>
      </main>
    </>
  )
}