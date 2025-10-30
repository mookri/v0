import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
  description: "Learn about Navdhya's mission to make authentic Hindu rituals accessible, respectful, and effortless for families in Delhi with our trusted pandits for every occasion.",
  openGraph: {
    title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Learn about Navdhya's mission to make authentic Hindu rituals accessible, respectful, and effortless for families in Delhi with our trusted pandits for every occasion.",
    url: `https://www.navdhya.com/about`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "About Navdhya - Hindu Ritual Services",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Learn about Navdhya's mission to make authentic Hindu rituals accessible, respectful, and effortless for families in Delhi with our trusted pandits for every occasion.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/about",
  },
}

// Schema markup for the about page
const ABOUT_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "AboutPage",
  name: "About Us - Navdhya",
  url: `https://www.navdhya.com/about`,
  description: "Learn about Navdhya's mission to make authentic Hindu rituals accessible, respectful, and effortless for families in Delhi with our trusted pandits for every occasion.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "About Us", url: `https://www.navdhya.com/about` }
  ])
}

export default function AboutPage() {
  const aboutSchema = JSON.stringify(ABOUT_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: aboutSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="our-story" className="w-full">
          <h1 id="our-story" className="font-serif text-3xl">
            Our Story
          </h1>
          <p className="mt-3 text-muted-foreground">
            Navdhya was founded with a vision to make authentic Hindu rituals accessible, respectful, and effortless for
            families everywhere.
          </p>
        </section>

        <section aria-labelledby="our-pandits">
          <h2 id="our-pandits" className="font-serif text-2xl">
            Meet Our Pandits
          </h2>
          <p className="mt-3 text-muted-foreground text-sm">
            Certified Vedic scholars with years of experience across traditions and languages.
          </p>
        </section>

        <section aria-labelledby="our-values">
          <h2 id="our-values" className="font-serif text-2xl">
            Our Values
          </h2>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Authenticity</li>
            <li>Devotion</li>
            <li>Excellence</li>
            <li>Transparency</li>
          </ul>
        </section>

        <section aria-labelledby="certifications">
          <h2 id="certifications" className="font-serif text-2xl">
            Certifications & Affiliations
          </h2>
          <p className="mt-3 text-sm text-muted-foreground">Recognized and trusted by communities across India.</p>
        </section>

        <section aria-labelledby="behind-the-scenes">
          <h2 id="behind-the-scenes" className="font-serif text-2xl">
            Behind the Scenes
          </h2>
          <p className="mt-3 text-sm text-muted-foreground">
            From sourcing premium samagri to meticulous setup, we prepare every detail for a serene, sacred experience.
          </p>
        </section>

        <section aria-labelledby="coverage">
          <h2 id="coverage" className="font-serif text-2xl">
            Service Coverage Map
          </h2>
          <img
            src="/india-service-coverage-map.jpg"
            alt="Map showing service coverage areas"
            className="rounded-md border mt-3 w-full h-auto"
          />
        </section>
      </main>
      <SiteFooter />
    </>
  )
}