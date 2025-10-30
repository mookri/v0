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
import BottomMenu from "@/components/bottom-menu"

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
      </main>
      <SiteFooter />
      <BottomMenu />
   </>
  )
}