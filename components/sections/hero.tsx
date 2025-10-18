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

  // Auto slide every 8 seconds
  useEffect(() => {
    if (!isAutoPlaying || isTransitioning) return;

    const timer = setInterval(() => {
      setIsTransitioning(true);
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 8000);
    
    return () => clearInterval(timer);
  }, [isAutoPlaying, isTransitioning]);

  const handleSlideChange = (newIndex: number) => {
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

  return (
    <section 
      className="relative w-full h-[90vh] md:h-screen overflow-hidden bg-black"
      onMouseEnter={() => setIsAutoPlaying(false)}
      onMouseLeave={() => setIsAutoPlaying(true)}
    >
      {/* Slides */}
      <AnimatePresence mode="wait" onExitComplete={() => setIsTransitioning(false)}>
        <motion.div
          key={slides[current].id}
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.8, ease: "easeInOut" }}
          className="absolute inset-0"
        >
          {/* Background Image */}
          <div className="absolute inset-0">
            <img
              src={slides[current].image}
              alt={slides[current].title}
              className="w-full h-full object-cover"
              loading="eager"
            />
            
            {/* Overlay Gradient */}
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40" />
            
            {/* Subtle Pattern Overlay */}
            <div className="absolute inset-0 bg-[url('/sanskrit-pattern-light.svg')] bg-contain bg-center opacity-[0.03] mix-blend-overlay" />
          </div>

          {/* Content Container */}
          <div className="relative h-full container mx-auto px-6 lg:px-8">
            <div className="flex h-full items-center">
              <div className="max-w-2xl lg:max-w-3xl text-white">
                {/* Label Badge */}
                <motion.div
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.6, delay: 0.2 }}
                  className="inline-flex items-center gap-2 bg-amber-500 px-4 py-2 rounded-full text-sm mb-8 text-white font-medium"
                >
                  <span className="w-2 h-2 bg-white rounded-full animate-pulse" />
                  {slides[current].label}
                </motion.div>

                {/* Main Title */}
                <motion.h1
                  key={`title-${current}`}
                  initial={{ y: 30, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.8, delay: 0.3 }}
                  className="text-4xl md:text-5xl lg:text-6xl font-semibold leading-tight mb-6"
                >
                  {slides[current].title.split(' ').map((word, index, array) => 
                    word === "Divine" || word === "Traditional" || word === "Auspicious" ? (
                      <span key={index} className="text-amber-400">{word} </span>
                    ) : (
                      <span key={index}>{word} </span>
                    )
                  )}
                </motion.h1>

                {/* Subtitle */}
                <motion.p
                  key={`subtitle-${current}`}
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.8, delay: 0.5 }}
                  className="text-lg md:text-xl text-slate-200 leading-relaxed mb-8 max-w-3xl"
                >
                  {slides[current].subtitle}
                </motion.p>

                {/* CTA Buttons */}
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.8, delay: 0.7 }}
                  className="flex flex-col sm:flex-row gap-4"
                >
                  <Link
                    href={slides[current].ctaLink}
                    className="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 md:px-8 px-5 md:py-4 py-3 text-white font-medium text-lg shadow-lg hover:shadow-xl hover:scale-[1.03] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-black/50"
                  >
                    {slides[current].cta}
                  </Link>
                  
                  <Link
                    href={slides[current].secondaryCtaLink}
                    className="inline-flex items-center justify-center rounded-xl border-2 border-white bg-transparent md:px-8 px-5 md:py-4 py-3 text-white font-medium text-lg hover:bg-white hover:text-black transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black/50"
                  >
                    {slides[current].secondaryCta}
                  </Link>
                </motion.div>

                {/* Trust Indicator */}
                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ duration: 0.8, delay: 0.9 }}
                  className="pt-8 text-sm text-slate-300"
                >
                  <span className="inline-flex items-center gap-2">
                    <span className="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse" />
                    Trusted by thousands of families worldwide
                  </span>
                </motion.div>
              </div>
            </div>
          </div>
        </motion.div>
      </AnimatePresence>

      {/* Navigation Arrows */}
      <button
        onClick={prev}
        className="hidden md:block absolute left-4 lg:left-8 top-1/2 -translate-y-1/2 text-white rounded-md p-2.5 transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-black/50"
        aria-label="Previous slide"
      >
        <ArrowLeft className="w-5 h-5 lg:w-6 lg:h-6" />
      </button>

      <button
        onClick={next}
        className="hidden md:block absolute right-4 lg:right-8 top-1/2 -translate-y-1/2 text-white rounded-md p-2.5 transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-black/50"
        aria-label="Next slide"
      >
        <ArrowRight className="w-5 h-5 lg:w-6 lg:h-6" />
      </button>

      {/* Dots Indicator */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3">
        {slides.map((_, index) => (
          <button
            key={index}
            onClick={() => goToSlide(index)}
            className={`w-3 h-3 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white/50 ${
              index === current 
                ? "bg-amber-400 scale-125 shadow-lg" 
                : "bg-white/60 hover:bg-white/80"
            }`}
            aria-label={`Go to slide ${index + 1}`}
          />
        ))}
      </div>
    </section>
  );
}