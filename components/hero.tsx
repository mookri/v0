import { Button } from "@/components/ui/button"

export function Hero() {
  return (
    <section aria-labelledby="hero-title" className="relative overflow-hidden">
      <div className="mx-auto max-w-6xl px-4 py-14 md:py-20 grid md:grid-cols-2 gap-8 items-center">
        <div className="order-2 md:order-1">
          <h1 id="hero-title" className="font-serif text-3xl md:text-5xl leading-tight text-pretty">
            Sacred Rituals, Performed with Devotion & Precision
          </h1>
          <p className="mt-4 text-muted-foreground">
            Experienced Pandits • Complete Samagri • Hassle-Free Arrangements
          </p>
          <div className="mt-6 flex gap-3">
            <Button className="bg-primary text-primary-foreground">Book Your Puja</Button>
            <Button variant="outline">Get Free Consultation</Button>
          </div>
          <div className="mt-6 text-sm text-muted-foreground">
            2000+ Pujas Completed • 98% Satisfaction • Same-Day Booking Available
          </div>
        </div>
        <div className="order-1 md:order-2">
          <img
            src={"/placeholder.svg?height=640&width=960&query=traditional%20puja%20ceremony%20mandap%20and%20samagri"}
            alt="Traditional puja ceremony arrangement with mandap and samagri"
            className="aspect-[4/3] w-full rounded-lg border object-cover"
          />
          <div className="absolute left-6 top-6 md:left-auto md:right-6 bg-card border rounded-md px-3 py-2 text-sm shadow">
            500+ Families Blessed
          </div>
        </div>
      </div>
    </section>
  )
}
