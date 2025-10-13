export function Testimonials() {
  const items = [
    {
      quote: "Pandit Ji performed our Grihapravesh with such devotion. Everything was arranged perfectly.",
      name: "Rajesh & Priya Sharma",
      location: "Gurgaon",
    },
    {
      quote: "Flawless Satyanarayan Puja. The team handled samagri and mandap beautifully.",
      name: "Ananya Verma",
      location: "Delhi NCR",
    },
    {
      quote: "Authentic rituals and punctual service. Highly recommended for families.",
      name: "Kumar Family",
      location: "Noida",
    },
  ]
  return (
    <section aria-labelledby="testimonials-title" className="bg-muted/50">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <h2 id="testimonials-title" className="font-serif text-2xl md:text-3xl">
          Testimonials
        </h2>
        <div className="grid md:grid-cols-3 gap-6 mt-6">
          {items.map((it) => (
            <figure key={it.name} className="p-5 rounded-lg border bg-card">
              <blockquote className="text-sm leading-relaxed">“{it.quote}”</blockquote>
              <figcaption className="mt-3 text-sm text-muted-foreground">
                {it.name} — {it.location}
              </figcaption>
            </figure>
          ))}
        </div>
      </div>
    </section>
  )
}
