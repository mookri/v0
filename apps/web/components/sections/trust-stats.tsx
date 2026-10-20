export function TrustStats() {
  const stats = [
    { value: "2,000+", label: "Pujas Completed" },
    { value: "500+", label: "Families Served" },
    { value: "50+", label: "Certified Pandits" },
    { value: "98%", label: "Satisfaction Rate" },
  ]

  return (
    <section aria-labelledby="stats-title" className="bg-[#0E3F2E] py-10">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8">
          <h2
            id="stats-title"
            className="font-serif text-xl md:text-2xl lg:text-3xl font-bold tracking-tight text-white relative inline-block mb-2"
          >
            Trust Statistics
            <span className="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-16 h-0.5 bg-gradient-to-r from-white to-white/80 rounded-full"></span>
          </h2>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 w-full">
          {stats.map((stat) => (
            <div
              key={stat.label}
              className="p-4 rounded-md border border-white/10 bg-white/5 backdrop-blur-sm flex flex-col items-center transition-all duration-300 hover:-translate-y-0.5"
            >
              <div className="font-serif text-2xl font-bold text-white">{stat.value}</div>
              <div className="text-xs mt-1.5 text-center text-white/80">{stat.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
