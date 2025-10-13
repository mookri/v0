export function TrustStats() {
  const stats = [
    ["2,000+", "Pujas Completed"],
    ["500+", "Families Served"],
    ["50+", "Certified Pandits"],
    ["98%", "Satisfaction Rate"],
  ]
  return (
    <section aria-labelledby="stats-title" className="bg-secondary text-secondary-foreground">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <h2 id="stats-title" className="font-serif text-2xl md:text-3xl">
          Trust Statistics
        </h2>
        <div className="grid md:grid-cols-4 gap-6 mt-6">
          {stats.map(([num, label]) => (
            <div key={label} className="p-5 rounded-lg bg-secondary/30 border border-secondary-foreground/20">
              <div className="font-serif text-3xl">{num}</div>
              <div className="text-sm mt-1">{label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
