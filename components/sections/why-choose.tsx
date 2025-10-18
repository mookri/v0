export function WhyChoose() {
  const points = [
    {
      icon: "🕉️",
      title: "Vedic Certified Pandits",
      description: "Scholars trained in authentic Vedic traditions with years of experience performing rituals across India"
    },
    {
      icon: "📿",
      title: "Complete Puja Samagri",
      description: "Premium-quality ritual materials sourced directly from trusted suppliers and blessed by senior priests"
    },
    {
      icon: "🎪",
      title: "Professional Mandap Setup",
      description: "Beautiful, traditional decorations arranged by expert decorators with authentic Vedic elements"
    },
    {
      icon: "📅",
      title: "Flexible Scheduling",
      description: "Book 365 days a year with customizable timings to match your family's auspicious muhurat"
    },
    {
      icon: "💰",
      title: "Transparent Pricing",
      description: "No hidden costs, clear packages with detailed breakdowns and post-puja service guarantees"
    },
    {
      icon: "🗣️",
      title: "Multilingual Pandits",
      description: "Hindi, English, and regional language pandits to ensure clear communication and understanding"
    },
  ];

  return (
    <section aria-labelledby="why-choose" className="bg-background py-16 lg:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {/* Enhanced Header */}
        <div className="text-center mb-16">
          <h2
            id="why-choose"
            className="font-serif text-3xl md:text-4xl lg:text-5xl font-bold text-foreground relative inline-block mb-4"
          >
            Why Choose Navdhara
            <span className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-24 h-1 bg-gradient-to-r from-primary to-secondary rounded-full"></span>
          </h2>
          <p className="text-muted-foreground mt-6 text-lg md:text-xl max-w-3xl mx-auto leading-relaxed">
            Experience the divine difference with our authentic, professional, and comprehensive puja services
            that honor tradition while embracing modern convenience
          </p>
        </div>

        {/* Enhanced Grid Layout */}
        <div className="mt-12 grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          {points.map((point, index) => (
            <div
              key={point.title}
              className="group relative"
            >
              {/* Animated Background Effect */}
              <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-secondary/5 rounded-2xl transform group-hover:scale-105 transition-all duration-500 opacity-0 group-hover:opacity-100 -z-10" />

              {/* Main Card */}
              <div className="p-8 rounded-2xl border border-border/50 bg-card/60 backdrop-blur-sm transition-all duration-500 hover:-translate-y-2 group-hover:border-primary/30 h-full flex flex-col relative overflow-hidden">

                {/* Floating Icon Background */}
                <div className="absolute top-4 right-4 text-6xl opacity-5 transform rotate-12 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-0">
                  {point.icon}
                </div>

                {/* Number Indicator with Enhanced Design */}
                <div className="flex items-start justify-between mb-6">
                  <div className="flex items-center gap-4">
                    <div className="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-secondary shadow-lg shadow-primary/25 flex-shrink-0 transition-all duration-300 group-hover:scale-110">
                      <span className="font-bold text-white text-sm">
                        {(index + 1).toString().padStart(2, '0')}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Content */}
                <div className="flex-1">
                  <h3 className="font-serif text-xl font-bold text-foreground mb-3 leading-tight group-hover:text-primary transition-colors duration-300">
                    {point.title}
                  </h3>
                  <p className="text-muted-foreground leading-relaxed text-sm md:text-base">
                    {point.description}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}