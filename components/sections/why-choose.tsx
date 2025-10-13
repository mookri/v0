export function WhyChoose() {
  const points = [
    ["Vedic Certified Pandits", "Scholars trained in authentic Vedic traditions"],
    ["Complete Puja Samagri", "Premium-quality ritual materials included"],
    ["Professional Mandap Setup", "Beautiful, traditional decorations arranged"],
    ["Flexible Scheduling", "Book 365 days, choose your muhurat"],
    ["Transparent Pricing", "No hidden costs, clear packages"],
    ["Multilingual Pandits", "Hindi, English, regional languages"],
  ]
  return (
    <section aria-labelledby="why-choose" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <h2 id="why-choose" className="font-serif text-2xl md:text-3xl">
          Why Choose Navdhya
        </h2>
        <div className="mt-6 grid md:grid-cols-2 gap-6">
          {points.map(([title, desc]) => (
            <div key={title} className="p-5 rounded-lg border bg-card">
              <h3 className="font-serif text-lg">{title}</h3>
              <p className="text-sm text-muted-foreground mt-2">{desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
