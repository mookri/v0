export function ProcessSteps() {
  const steps = [
    ["Select Your Ritual", "Choose from our comprehensive puja catalog"],
    ["Pick Date & Time", "Select auspicious muhurat or get consultation"],
    ["We Handle Everything", "Pandit, samagri, setup — arrive & experience divinity"],
  ]
  return (
    <section id="process" aria-labelledby="process-title" className="bg-muted/50">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <h2 id="process-title" className="font-serif text-2xl md:text-3xl">
          Simple 3-Step Process
        </h2>
        <ol className="mt-6 grid md:grid-cols-3 gap-6">
          {steps.map(([title, desc], i) => (
            <li key={title} className="p-5 rounded-lg border bg-card relative">
              <div className="absolute -top-3 -left-3 size-8 rounded-full bg-accent text-accent-foreground grid place-items-center text-sm">
                {i + 1}
              </div>
              <h3 className="font-serif text-lg">{title}</h3>
              <p className="text-sm text-muted-foreground mt-2">{desc}</p>
            </li>
          ))}
        </ol>
      </div>
    </section>
  )
}
