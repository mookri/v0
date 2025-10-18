export function TrustStats() {
  const stats = [
    ["2,000+", "Pujas Completed"],
    ["500+", "Families Served"],
    ["50+", "Certified Pandits"],
    ["98%", "Satisfaction Rate"],
  ]
  return (
    <section aria-labelledby="stats-title" className="bg-secondary text-secondary-foreground">
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <h2 id="stats-title" className="font-serif text-2xl md:text-3xl text-center">
          Trust Statistics
        </h2>
        <div className="grid md:grid-cols-4 gap-6 mt-6 w-full">
          {stats.map(([num, label]) => (
            <div key={label} className="p-5 rounded-lg bg-secondary/30 border border-secondary-foreground/20 flex flex-col items-center">
              <div className="font-serif text-3xl">{num}</div>
              <div className="text-sm mt-1 text-center">{label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
