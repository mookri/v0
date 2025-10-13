export function WhyChoose() {
  const points = [
    ["Vedic Certified Pandits", "Scholars trained in authentic Vedic traditions with years of experience performing rituals across India"],
    ["Complete Puja Samagri", "Premium-quality ritual materials sourced directly from trusted suppliers and blessed by senior priests"],
    ["Professional Mandap Setup", "Beautiful, traditional decorations arranged by expert decorators with authentic Vedic elements"],
    ["Flexible Scheduling", "Book 365 days a year with customizable timings to match your family's auspicious muhurat"],
    ["Transparent Pricing", "No hidden costs, clear packages with detailed breakdowns and post-puja service guarantees"],
    ["Multilingual Pandits", "Hindi, English, and regional language pandits to ensure clear communication and understanding"],
  ]
  return (
    <section aria-labelledby="why-choose" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 overflow-x-hidden w-full">
        <div className="text-center mb-10">
          <h2 id="why-choose" className="font-serif text-2xl md:text-3xl">
            Why Choose Navdhya
            <span className="absolute bottom-0 left-0 w-full h-1 bg-primary/20 rounded-full"></span>
          </h2>
          <p className="text-muted-foreground mt-4 text-lg max-w-2xl mx-auto">
            Experience the difference with our premium puja services
          </p>
        </div>
        <div className="mt-6 grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 w-full">
          {points.map(([title, desc], index) => (
            <div 
              key={title} 
              className="p-6 rounded-xl border bg-card/80 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg group relative overflow-hidden"
            >
              <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-primary/5 to-secondary/5 -z-10"></div>
              <div className="flex items-start gap-4">
                <div className="flex flex-col items-center">
                  <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 flex-shrink-0 mt-1">
                    <span className="font-serif font-bold text-primary">
                      {(index + 1).toString().padStart(2, '0')}
                    </span>
                  </div>
                  <div className="h-full w-px bg-border mt-2 mb-4"></div>
                </div>
                <div>
                  <h3 className="font-serif text-lg font-semibold text-foreground">{title}</h3>
                  <p className="text-sm text-muted-foreground mt-2">{desc}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}