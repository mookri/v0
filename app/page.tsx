import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
// import { Hero } from "@/components/hero"
import { FeaturedPujas } from "@/components/sections/featured-pujas"
import { WhyChoose } from "@/components/sections/why-choose"
import { ProcessSteps } from "@/components/sections/process-steps"
import { Catalog } from "@/components/sections/catalog"
import { Testimonials } from "@/components/sections/testimonials"
import { TrustStats } from "@/components/sections/trust-stats"
import { BlogPreview } from "@/components/sections/blog"
import { FAQ } from "@/components/sections/faq"
import Hero from "@/components/sections/hero"
import { SEO_CONFIG } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  openGraph: {
    title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
    description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
    url: "https://www.navdhya.com",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya - Delhi's Trusted Puja & Spiritual Wellness",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
    description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com",
  },
}

// Schema markup for the homepage
const HOME_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Home - Navdhya",
  url: "https://www.navdhya.com",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  breadcrumb: {
    "@type": "BreadcrumbList",
    itemListElement: [
      {
        "@type": "ListItem",
        position: 1,
        name: "Home",
        item: "https://www.navdhya.com"
      }
    ]
  }
}

export default function HomePage() {
  const homeSchema = JSON.stringify(HOME_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: homeSchema }}
      />
      <SiteHeader />
      <main className="overflow-x-hidden w-full">
        <Hero />
        <FeaturedPujas />
        <WhyChoose />
        <ProcessSteps />
        <Catalog />
        <Testimonials />
        <TrustStats />
        <BlogPreview />
        <FAQ />
        {/* <Booking /> */}
      </main>
      <SiteFooter />
   </>
  )
}