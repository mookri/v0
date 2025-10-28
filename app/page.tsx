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
import { Booking } from "@/components/sections/booking"
import { InfoBar } from "@/components/sections/info-bar"
import Link from "next/link"
import Hero from "@/components/sections/hero"

export default function HomePage() {
  return (
    <>
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
        <InfoBar />
      </main>
      <SiteFooter />

      <Link
        href="https://wa.me/910000000000"
        aria-label="Chat on WhatsApp"
        className="fixed bottom-5 right-5 rounded-full bg-primary text-primary-foreground px-4 py-3 shadow-lg md:bottom-8 md:right-8 max-w-[calc(100vw-40px)] overflow-hidden whitespace-nowrap"
        target="_blank"
      >
        WhatsApp
      </Link>
    </>
  )
}