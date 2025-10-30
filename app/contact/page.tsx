import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import { Phone, Mail, MessageCircle } from "lucide-react"
import { SEO_CONFIG, LOCAL_BUSINESS_SCHEMA, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Contact Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
  openGraph: {
    title: "Contact Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
    url: `https://www.navdhya.com/contact`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Contact Navdhya for Hindu Ritual Services",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Contact Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/contact",
  },
}

// Schema markup for the contact page
const CONTACT_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "ContactPage",
  name: "Contact Us - Navdhya",
  url: `https://www.navdhya.com/contact`,
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Contact Us", url: `https://www.navdhya.com/contact` }
  ])
}

export default function ContactPage() {
  const contactSchema = JSON.stringify(CONTACT_SCHEMA)
  const localBusinessSchema = JSON.stringify({
    ...LOCAL_BUSINESS_SCHEMA,
    address: {
      "@type": "PostalAddress",
      streetAddress: "Pandav Nagar",
      addressLocality: "New Delhi",
      postalCode: "110092",
      addressCountry: "IN"
    }
  })
  
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
      <SiteHeader />
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
      <SiteFooter />
    </>
  )
}