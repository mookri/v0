"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { ArrowLeft, ArrowRight } from "lucide-react";
import Link from "next/link";

const slides = [
  {
    id: 1,
    title: "Experience the Divine Touch",
    subtitle: "Authentic Hindu rituals performed by certified Vedic priests — bringing peace and prosperity to your home.",
    label: "Divine Blessings",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Book a Puja",
    ctaLink: "/contact",
    secondaryCta: "Browse Catalog",
    secondaryCtaLink: "#catalog",
  },
  {
    id: 2,
    title: "Tradition Meets Convenience",
    subtitle: "Expert pandits, genuine samagri, and a seamless booking process for your sacred rituals.",
    label: "Sacred Traditions",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Consult Expert",
    ctaLink: "/consultation",
    secondaryCta: "View Pujas",
    secondaryCtaLink: "#pujas",
  },
  {
    id: 3,
    title: "Auspicious Beginnings",
    subtitle: "From housewarmings to weddings — guided rituals for harmony and blessings.",
    label: "Holy Ceremonies",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Schedule Puja",
    ctaLink: "/booking",
    secondaryCta: "Learn More",
    secondaryCtaLink: "#about",
  },
];

export default function HeroCarousel() {
  const [current, setCurrent] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);
  const [isTransitioning, setIsTransitioning] = useState(false);
  const [isClient, setIsClient] = useState(false);

  // Set isClient to true after mounting to avoid hydration issues
  useEffect(() => {
    setIsClient(true);
  }, []);

  // Auto slide every 8 seconds
  useEffect(() => {
    if (!isClient || !isAutoPlaying || isTransitioning) return;

    const timer = setInterval(() => {
      setIsTransitioning(true);
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 8000);
    
    return () => clearInterval(timer);
  }, [isClient, isAutoPlaying, isTransitioning]);

  const handleSlideChange = (newIndex: number) => {
    if (!isClient) return;
    setIsAutoPlaying(false);
    setIsTransitioning(true);
    setCurrent(newIndex);
    setTimeout(() => {
      setIsAutoPlaying(true);
      setIsTransitioning(false);
    }, 1200);
  };

  const next = () => {
    handleSlideChange((current + 1) % slides.length);
  };

  const prev = () => {
    handleSlideChange((current - 1 + slides.length) % slides.length);
  };

  const goToSlide = (index: number) => {
    handleSlideChange(index);
  };

  // Don't render anything on the server to prevent hydration mismatch
  if (!isClient) {
    return (
      <section 
        className="relative w-full h-[60vh] md:h-[85vh] overflow-hidden bg-gradient-to-br from-background to-muted"
      >
        <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/75 to-black/65" />
        <div className="relative h-full container mx-auto px-6 lg:px-8">
          <div className="flex h-full items-center">
            <div className="max-w-3xl lg:max-w-4xl">
              <div className="inline-flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-lg text-xs mb-4 text-white font-semibold tracking-wider border border-white/20 backdrop-blur-sm">
                <span className="w-1.5 h-1.5 bg-white rounded-full"></span>
                <span>{slides[0].label.toUpperCase()}</span>
              </div>
              <h1 className="text-3xl md:text-4xl lg:text-6xl font-semibold leading-tight mb-4 tracking-tight text-white">
                {slides[0].title}
              </h1>
              <p className="text-base md:text-lg text-gray-200 leading-relaxed mb-6 max-w-3xl font-medium">
                {slides[0].subtitle}
              </p>
            </div>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section 
      className="relative w-full h-[60vh] md:h-[85vh] overflow-hidden bg-gradient-to-br from-background to-muted"
      onMouseEnter={() => setIsAutoPlaying(false)}
      onMouseLeave={() => setIsAutoPlaying(true)}
    >
      {/* Slides */}
      <AnimatePresence mode="wait" onExitComplete={() => setIsTransitioning(false)}>
        <motion.div
          key={slides[current].id}
          initial={isClient ? { opacity: 0 } : undefined}
          animate={isClient ? { opacity: 1 } : undefined}
          exit={isClient ? { opacity: 0 } : undefined}
          transition={{ duration: 0.6, ease: "easeInOut" }}
          className="absolute inset-0"
        >
          {/* Background Image with Parallax Effect */}
          <div className="absolute inset-0">
            <div 
              className="w-full h-full bg-cover bg-center"
              style={{ 
                backgroundImage: `url(${slides[current].image})`,
                transform: 'scale(1.05)',
              }}
            />
            
            {/* Modern Overlay Gradient */}
            <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/75 to-black/65" />
            
            {/* Subtle Pattern Overlay */}
            <div className="absolute inset-0 opacity-[0.02]" />
          </div>

          {/* Content Container */}
          <div className="relative h-full container mx-auto px-6 lg:px-8">
            <div className="flex h-full items-center">
              <div className="max-w-3xl lg:max-w-4xl text-white">
                {/* Label Badge - Transparent sleek design */}
                <motion.div
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.5, delay: 0.1 }}
                  className="inline-flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-lg text-xs mb-4 text-white font-semibold tracking-wider border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all duration-300 ease-in-out transform hover:scale-105 backdrop-blur-sm"
                >
                  <span className="w-1.5 h-1.5 bg-white rounded-full animate-pulse" />
                  {slides[current].label.toUpperCase()}
                </motion.div>

                {/* Main Title - Compact font size */}
                <motion.h1
                  key={`title-${current}`}
                  initial={{ y: 30, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.7, delay: 0.2 }}
                  className="text-3xl md:text-4xl lg:text-6xl font-semibold leading-tight mb-4 tracking-tight text-white"
                >
                  {slides[current].title.split(' ').map((word, index) => 
                    word === "Divine" || word === "Traditional" || word === "Auspicious" ? (
                      <span key={index} className="text-primary">{word} </span>
                    ) : (
                      <span key={index}>{word} </span>
                    )
                  )}
                </motion.h1>

                {/* Subtitle - Smaller font size */}
                <motion.p
                  key={`subtitle-${current}`}
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.7, delay: 0.4 }}
                  className="text-base md:text-lg text-gray-200 leading-relaxed mb-6 max-w-3xl font-medium"
                >
                  {slides[current].subtitle}
                </motion.p>

                {/* CTA Buttons - More rounded */}
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.7, delay: 0.6 }}
                  className="flex flex-col sm:flex-row gap-4"
                >
                  <Link
                    href={slides[current].ctaLink}
                    className="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-primary-foreground font-semibold text-sm shadow-lg hover:shadow-xl hover:bg-primary/90 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background group"
                  >
                    {slides[current].cta}
                    <ArrowRight className="ml-2 w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" />
                  </Link>
                  
                  <Link
                    href={slides[current].secondaryCtaLink}
                    className="inline-flex items-center justify-center rounded-full border border-white/30 bg-white/10 px-6 py-3 text-white font-semibold text-sm hover:bg-white/20 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-background/50"
                  >
                    {slides[current].secondaryCta}
                  </Link>
                </motion.div>

                {/* Trust Indicator */}
                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ duration: 0.7, delay: 0.8 }}
                  className="pt-8 flex items-center gap-3 font-sans"
                >
                  <div className="flex -space-x-2">
                    {[1, 2, 3].map((item) => (
                      <div key={item} className="w-8 h-8 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center shadow-sm">
                        <div className="w-2 h-2 rounded-full bg-white"></div>
                      </div>
                    ))}
                  </div>
                  <span className="text-base leading-snug tracking-wide max-w-[200px] md:max-w-none font-sans text-gray-200">Trusted by thousands of families worldwide</span>
                </motion.div>
              </div>
            </div>
          </div>
        </motion.div>
      </AnimatePresence>

      {/* Navigation Arrows - Hidden on mobile, visible on desktop */}
      <button
        onClick={prev}
        className="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 text-white p-2 transition-all duration-300 hover:text-primary focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-background/50 rounded-full border border-white/30 hover:border-white hover:bg-white/10"
        aria-label="Previous slide"
      >
        <ArrowLeft className="w-5 h-5" />
      </button>

      <button
        onClick={next}
        className="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 text-white p-2 transition-all duration-300 hover:text-primary focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-background/50 rounded-full border border-white/30 hover:border-white hover:bg-white/10"
        aria-label="Next slide"
      >
        <ArrowRight className="w-5 h-5" />
      </button>

      {/* Animated Capsule-shaped Progress Indicators */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3">
        {slides.map((_, index) => (
          <button
            key={index}
            onClick={() => goToSlide(index)}
            className={`h-1.5 rounded-full transition-all duration-500 ease-in-out focus:outline-none focus:ring-2 focus:ring-white/50 ${
              index === current 
                ? "bg-white w-8" 
                : "bg-white/30 w-4 hover:brightness-125"
            }`}
            aria-label={`Go to slide ${index + 1}`}
          />
        ))}
      </div>
    </section>
  );
}
