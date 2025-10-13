import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Hero } from "@/components/hero"
import { FeaturedPujas } from "@/components/sections/featured-pujas"
import { WhyChoose } from "@/components/sections/why-choose"
import { ProcessSteps } from "@/components/sections/process-steps"
import { Catalog } from "@/components/sections/catalog"
import { Testimonials } from "@/components/sections/testimonials"
import { TrustStats } from "@/components/sections/trust-stats"
import { BlogPreview } from "@/components/sections/blog"
import { FAQ } from "@/components/sections/faq"
import { Booking } from "@/components/sections/booking"
import { InfoBar } from "@/components/sections/info-bar"
import Link from "next/link"

export default function HomePage() {
  return (
    <>
      <SiteHeader />
      <main>
        <Hero />
        <FeaturedPujas />
        <WhyChoose />
        <ProcessSteps />
        <Catalog />
        <Testimonials />
        <TrustStats />
        <BlogPreview />
        <FAQ />
        <Booking />
        <InfoBar />
      </main>
      <SiteFooter />

      <Link
        href="https://wa.me/910000000000"
        aria-label="Chat on WhatsApp"
        className="fixed bottom-5 right-5 rounded-full bg-primary text-primary-foreground px-4 py-3 shadow-lg"
        target="_blank"
      >
        WhatsApp
      </Link>
    </>
  )
}
