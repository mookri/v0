"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

const slides = [
  {
    id: 1,
    titleHighlight: "Authentic",
    titleRest: "Vedic Pujas.",
    titleLine2: "Verified Priests.",
    subtitle: "Experience the power of Vedic rituals performed with devotion, purity, and absolute tradition.",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Book a Puja",
    ctaLink: "/contact",
    secondaryCta: "Browse Catalog",
    secondaryCtaLink: "#catalog",
  },
  {
    id: 2,
    titleHighlight: "Sacred",
    titleRest: "Hindu Rituals.",
    titleLine2: "Expert Pandits.",
    subtitle: "Genuine samagri, transparent pricing, and verified priests ensure a seamless spiritual experience.",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Consult Expert",
    ctaLink: "/consultation",
    secondaryCta: "View Pujas",
    secondaryCtaLink: "#pujas",
  },
  {
    id: 3,
    titleHighlight: "Auspicious",
    titleRest: "Life Events.",
    titleLine2: "Guided Blessings.",
    subtitle: "From Griha Pravesh to Satyanarayan Katha, we provide guided rituals for enduring harmony.",
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    cta: "Schedule Puja",
    ctaLink: "/book",
    secondaryCta: "Learn More",
    secondaryCtaLink: "#about",
  },
];

export default function HeroCarousel() {
  const [current, setCurrent] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    setIsClient(true);
  }, []);

  useEffect(() => {
    if (!isClient || !isAutoPlaying) return;

    const timer = setInterval(() => {
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 6000);
    
    return () => clearInterval(timer);
  }, [isClient, isAutoPlaying]);

  const goToSlide = (index: number) => {
    if (index !== current) {
      setCurrent(index);
    }
  };

  if (!isClient) {
    return (
      <section className="w-full bg-background hidden md:block border-b border-border">
        <div className="relative w-full h-[500px] lg:h-[550px] bg-black"></div>
      </section>
    );
  }

  return (
    <section 
      className="w-full bg-background hidden md:block border-b border-border"
      onMouseEnter={() => setIsAutoPlaying(false)}
      onMouseLeave={() => setIsAutoPlaying(true)}
    >
      <div className="relative w-full h-[500px] lg:h-[550px] bg-black overflow-hidden">
        
        {/* Slides */}
        <AnimatePresence mode="wait">
          <motion.div
            key={slides[current].id}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.8 }}
            className="absolute inset-0"
          >
            {/* Full Bleed Background Image Container */}
            <div className="absolute inset-0">
              <div 
                className="w-full h-full bg-cover bg-center"
                style={{ backgroundImage: `url(${slides[current].image})` }}
              />
              {/* Refined sleek gradient: smooth dark fade from left to right */}
              <div className="absolute inset-0 bg-black/30" />
              <div className="absolute inset-0 bg-gradient-to-r from-black/90 via-black/50 to-transparent w-full md:w-3/4" />
            </div>

            {/* Content Container */}
            <div className="relative h-full w-full max-w-[1400px] mx-auto flex flex-col justify-center px-8 md:px-12 lg:px-16">
              
              {/* Strict layout container to maintain exact ratios */}
              <div className="max-w-[700px] flex flex-col">
                
                {/* Typography matching design system */}
                <motion.h1
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.6, delay: 0.1 }}
                  className="text-4xl lg:text-6xl font-serif text-white leading-[1.15] mb-5 tracking-tight min-h-[90px] lg:min-h-[140px]"
                >
                  <span className="text-primary">{slides[current].titleHighlight}</span> {slides[current].titleRest}
                  <br />
                  {slides[current].titleLine2}
                </motion.h1>

                <motion.p
                  initial={{ y: 20, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ duration: 0.6, delay: 0.2 }}
                  className="text-white/90 font-sans text-lg lg:text-xl mb-8 leading-relaxed max-w-lg min-h-[60px] lg:min-h-[80px]"
                >
                  {slides[current].subtitle}
                </motion.p>

                {/* Buttons using UI components and design pattern */}
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.6, delay: 0.3 }}
                  className="flex items-center gap-4"
                >
                  <Link href={slides[current].ctaLink}>
                    <Button 
                      className="bg-primary text-primary-foreground font-sans text-base h-12 hover:bg-primary/90 hover:scale-[1.02] transition-transform duration-200 shadow-md px-8 rounded-md"
                    >
                      {slides[current].cta}
                      <ArrowRight className="ml-2 w-4 h-4" />
                    </Button>
                  </Link>
                  
                  <Link href={slides[current].secondaryCtaLink}>
                    <Button 
                      variant="outline"
                      className="bg-transparent border-2 border-white/30 text-white font-sans text-base h-12 hover:bg-white/10 hover:text-white hover:border-white/50 hover:scale-[1.02] transition-all duration-200 px-8 rounded-md"
                    >
                      {slides[current].secondaryCta}
                    </Button>
                  </Link>
                </motion.div>
                
              </div>
            </div>
          </motion.div>
        </AnimatePresence>

        {/* Brand-aligned Pagination Dots */}
        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2.5 z-10">
          {slides.map((_, index) => (
            <button
              key={index}
              onClick={() => goToSlide(index)}
              className={`h-1.5 rounded-full transition-all duration-300 focus:outline-none ${
                index === current 
                  ? "bg-primary w-8" 
                  : "bg-white/40 w-2 hover:bg-white/70"
              }`}
              aria-label={`Go to slide ${index + 1}`}
            />
          ))}
        </div>

      </div>
    </section>
  );
}
