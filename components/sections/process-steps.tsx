export function ProcessSteps() {
  const steps = [
    {
      title: "Select Your Ritual",
      description: "Choose from our comprehensive puja catalog"
    },
    {
      title: "Pick Date & Time",
      description: "Select auspicious muhurat or get consultation"
    },
    {
      title: "We Handle Everything",
      description: "Pandit, samagri, setup — arrive & experience divinity"
    },
  ];

  return (
    <section id="process" aria-labelledby="process-title" className="bg-gradient-to-b from-slate-50 to-white py-16 lg:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2
            id="process-title"
            className="font-serif text-3xl md:text-4xl font-bold text-foreground relative inline-block"
          >
            Simple 3-Step Process
            <span className="absolute -bottom-3 left-1/2 transform -translate-x-1/2 w-20 h-1 bg-gradient-to-r from-primary to-secondary rounded-full"></span>
          </h2>
          <p className="text-muted-foreground mt-6 text-lg max-w-2xl mx-auto">
            Experience the divine with our streamlined booking process designed for your convenience
          </p>
        </div>
        <ol className="relative">
          <div className="grid gap-8 md:grid-cols-3 relative z-10">
            {steps.map((step, index) => (
              <li key={step.title} className="relative group">
                <div className="relative bg-white/80 rounded-md p-8 border transition-all duration-500 hover:-translate-y-3 h-full flex flex-col items-center text-center border-primary/20">
                  <div className="relative mb-8">
                    <div className="relative size-20 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center shadow-2xl shadow-primary/30 group-hover:shadow-primary/40 group-hover:scale-110 transition-all duration-500">
                      <span className="text-3xl filter text-white">{index}</span>
                    </div>
                  </div>

                  {/* Content */}
                  <div className="flex-1 space-y-4">
                    <h3 className="font-serif text-2xl font-bold text-slate-900 mb-2 group-hover:text-primary transition-colors duration-300">
                      {step.title}
                    </h3>
                    <p className="text-slate-600 leading-relaxed text-lg">
                      {step.description}
                    </p>
                  </div>

                  <div className="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-full text-sm font-medium mt-4">
                    <span>Next Step</span>
                    <svg
                      className="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                      />
                    </svg>
                  </div>
                </div>

                {/* Desktop Connection Arrows */}
                {index < steps.length - 1 && (
                  <div className="hidden md:block absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 z-20">
                    <div className="w-12 h-12 bg-gradient-to-r from-primary/10 to-secondary/10 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/20 shadow-sm group-hover:scale-110 transition-transform duration-300">
                      <svg
                        className="w-6 h-6 text-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                      </svg>
                    </div>
                  </div>
                )}
              </li>
            ))}
          </div>
        </ol>
      </div>
    </section>
  );
}