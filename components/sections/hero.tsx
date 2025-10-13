import Link from "next/link"

export default function Hero() {
  return (
    <section className="bg-background">
      <div className="container mx-auto px-4 py-14 grid gap-8 md:grid-cols-2 items-center">
        <div>
          <h1 className="text-pretty font-serif text-4xl md:text-5xl font-semibold">
            Premium Hindu Ritual & Puja Services, Done Right.
          </h1>
          <p className="mt-4 text-muted-foreground leading-relaxed">
            Authentic Vedic rituals, punctual priests, and clear guidance. Book with confidence and bring peace, purity,
            and blessings to your home.
          </p>
          <div className="mt-6 flex flex-col sm:flex-row gap-3">
            <Link
              href="/contact"
              className="inline-flex items-center justify-center rounded-md bg-primary px-5 py-3 text-primary-foreground font-medium hover:bg-primary/90"
            >
              Book a Puja
            </Link>
            <a
              href="#catalog"
              className="inline-flex items-center justify-center rounded-md border border-border px-5 py-3 hover:bg-muted"
            >
              Browse Catalog
            </a>
          </div>
          <div className="mt-6 text-sm text-muted-foreground">
            Trusted by families for Grih Pravesh, Satyanarayan, Annaprashan, and more.
          </div>
        </div>

        <div className="aspect-video rounded-lg border border-border bg-muted">
          <img
            src="/hindu-puja-ceremony-peaceful-home-setting.jpg"
            alt="Puja ceremony in a serene home setting"
            className="h-full w-full object-cover rounded-lg"
          />
        </div>
      </div>
    </section>
  )
}
