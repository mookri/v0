import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"

export default function AboutPage() {
  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10">
        <section aria-labelledby="our-story">
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
            className="rounded-md border mt-3"
          />
        </section>
      </main>
      <SiteFooter />
    </>
  )
}
