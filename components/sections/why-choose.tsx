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
    <section aria-labelledby="why-choose" className="bg-background py-16 lg:py-24 w-full overflow-hidden">
      <div className="mx-auto w-full px-4 md:px-8 xl:px-12">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-start">
          
          {/* Left Column (Shift-4) - Sticky Title */}
          <div className="lg:col-span-4 lg:sticky lg:top-32 text-left z-10">
            <h2
              id="why-choose"
              className="font-serif text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight text-foreground relative inline-block mb-3"
            >
              Why Choose Navdhya
              <span className="absolute -bottom-2 left-0 w-24 h-1 bg-gradient-to-r from-primary to-[#0E3F2E] rounded-full"></span>
            </h2>
            <p className="text-muted-foreground mt-6 text-base md:text-lg leading-relaxed max-w-md">
              Verified priests, complete rituals, transparent pricing — Navdhya brings authentic Vedic puja services
              to your home with the convenience modern families deserve.
            </p>
          </div>

          {/* Right Column (Shift-8) - Feature Grid */}
          <div className="lg:col-span-8 w-full">
            <div className="grid gap-5 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
          {points.map((point, index) => (
            <div
              key={point.title}
              className="group relative"
            >
              {/* Premium Sleek Card */}
              <div className="p-5 rounded-lg border border-border/50 bg-card/90 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md h-full flex flex-col relative overflow-hidden">

                {/* Floating Icon Background */}
                <div className="absolute top-2.5 right-2.5 text-4xl opacity-5 transform rotate-12 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-0">
                  {point.icon}
                </div>

                {/* Number Indicator with Premium Design */}
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-2.5">
                    <div className="flex size-8 items-center justify-center rounded-md bg-gradient-to-br from-secondary to-primary shadow-sm shadow-primary/20 flex-shrink-0 transition-all duration-300 group-hover:scale-105">
                      <span className="font-bold text-white text-xs">
                        {(index + 1).toString().padStart(2, '0')}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Content with Improved Symmetry */}
                <div className="flex-1 flex flex-col justify-between">
                  <div>
                    <h3 className="font-serif text-base font-bold tracking-tight text-foreground mb-1.5 leading-tight">
                      {point.title}
                    </h3>
                    <p className="text-muted-foreground leading-relaxed text-xs">
                      {point.description}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}