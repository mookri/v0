"use client";

import React, { useState, useEffect, useCallback, useRef } from "react";
import Image from "next/image";
import Link from "next/link";
import { ChevronLeft, ChevronRight, ArrowRight } from "lucide-react";

export interface SlideData {
  id: string | number;
  eyebrow: string;
  title: string;
  description: string;
  buttonText: string;
  buttonLink: string;
  bgImage: string;
  mainGraphic: string;
  badgeGraphic?: string;
  bulletLabel: string;
}

export interface PromoCardData {
  id: string;
  badgeText: string;
  badgeColorClass: string;
  badgeBg: string;
  title: string;
  subtitle: string;
  buttonText: string;
  buttonLink: string;
  cardBgColor: string;
  cardBgImage?: string;
  productImage: string;
  imageAlt: string;
}

const DEFAULT_SLIDES: SlideData[] = [
  {
    id: 1,
    eyebrow: "KEEP YOUR VISION CLEAR",
    title: "WIPER BLADES",
    description: "Plus Price Lock on 100s of lines",
    buttonText: "Shop now",
    buttonLink: "https://enovathemes.com/mobex/shop/?ca=oils-and-fluids&ajax=true",
    bgImage: "https://enovathemes.com/mobex/wp-content/uploads/slide-2.webp",
    mainGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-6.webp",
    badgeGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-5.webp",
    bulletLabel: "Wipers",
  },
  {
    id: 2,
    eyebrow: "FULL SYNTHETIC",
    title: "MOBIL 1",
    description: "It’s more than just oil. It’s liquid engineering.",
    buttonText: "Shop now",
    buttonLink: "https://enovathemes.com/mobex/shop/?ca=oils-and-fluids&ajax=true",
    bgImage: "https://enovathemes.com/mobex/wp-content/uploads/slide-4.webp",
    mainGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-2.webp",
    badgeGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-3.webp",
    bulletLabel: "Mobil 1",
  },
  {
    id: 3,
    eyebrow: "START BATTERIES IN SECONDS",
    title: "JUMP STARTERS",
    description: "Up to 45% Off",
    buttonText: "Shop now",
    buttonLink: "https://enovathemes.com/mobex/shop/?ca=oils-and-fluids&ajax=true",
    bgImage: "https://enovathemes.com/mobex/wp-content/uploads/slide-5.webp",
    mainGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-7.webp",
    badgeGraphic: "https://enovathemes.com/mobex/wp-content/uploads/slider-asset-8.webp",
    bulletLabel: "Jump Starters",
  },
];

const DEFAULT_PROMO_CARDS: PromoCardData[] = [
  {
    id: "batteries",
    badgeText: "Top brands",
    badgeColorClass: "bg-[#bf3617] text-white",
    badgeBg: "#bf3617",
    title: "BATTERIES",
    subtitle: "Stay charged up!",
    buttonText: "Shop now",
    buttonLink: "https://enovathemes.com/mobex/shop/?ca=battery&ajax=true",
    cardBgColor: "#f29f05",
    cardBgImage: "https://enovathemes.com/mobex/wp-content/uploads/highlight.png",
    productImage: "https://enovathemes.com/mobex/wp-content/uploads/banner5-img.webp",
    imageAlt: "Car Battery Top Brands",
  },
  {
    id: "tires",
    badgeText: "Buy 3 Get 1 For Free",
    badgeColorClass: "bg-[#04bf33] text-white",
    badgeBg: "#04bf33",
    title: "TIRES & WHEELS",
    subtitle: "Stay safe on road!",
    buttonText: "Shop now",
    buttonLink: "https://enovathemes.com/mobex/shop/?ca=tires-and-related-products&ajax=true",
    cardBgColor: "#e9f3fb",
    cardBgImage: "https://enovathemes.com/mobex/wp-content/uploads/banner6-img.webp",
    productImage: "https://enovathemes.com/mobex/wp-content/uploads/banner7-img.webp",
    imageAlt: "Tires and Alloy Wheel",
  },
];

interface AutoPartsHeroBannerProps {
  slides?: SlideData[];
  promoCards?: PromoCardData[];
  autoplayIntervalMs?: number;
  className?: string;
}

export default function AutoPartsHeroBanner({
  slides = DEFAULT_SLIDES,
  promoCards = DEFAULT_PROMO_CARDS,
  autoplayIntervalMs = 5500,
  className = "",
}: AutoPartsHeroBannerProps) {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const [progress, setProgress] = useState(0);
  const touchStartX = useRef<number | null>(null);
  const touchEndX = useRef<number | null>(null);

  const totalSlides = slides.length;

  const nextSlide = useCallback(() => {
    setCurrentSlide((prev) => (prev + 1) % totalSlides);
    setProgress(0);
  }, [totalSlides]);

  const prevSlide = useCallback(() => {
    setCurrentSlide((prev) => (prev - 1 + totalSlides) % totalSlides);
    setProgress(0);
  }, [totalSlides]);

  const goToSlide = (index: number) => {
    setCurrentSlide(index);
    setProgress(0);
  };

  // Autoplay and progress bar ticker
  useEffect(() => {
    if (isPaused) return;

    const stepMs = 50;
    const increment = (stepMs / autoplayIntervalMs) * 100;

    const timer = setInterval(() => {
      setProgress((prev) => {
        if (prev >= 100) {
          nextSlide();
          return 0;
        }
        return prev + increment;
      });
    }, stepMs);

    return () => clearInterval(timer);
  }, [isPaused, autoplayIntervalMs, nextSlide]);

  // Touch handlers for mobile swipe
  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0].clientX;
    setIsPaused(true);
  };

  const handleTouchEnd = (e: React.TouchEvent) => {
    touchEndX.current = e.changedTouches[0].clientX;
    if (touchStartX.current !== null && touchEndX.current !== null) {
      const diff = touchStartX.current - touchEndX.current;
      if (diff > 45) {
        nextSlide();
      } else if (diff < -45) {
        prevSlide();
      }
    }
    touchStartX.current = null;
    touchEndX.current = null;
    setIsPaused(false);
  };

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "ArrowLeft") {
      prevSlide();
    } else if (e.key === "ArrowRight") {
      nextSlide();
    }
  };

  return (
    <div
      className={`w-full max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-6 ${className}`}
      style={{
        fontFamily:
          "'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
      }}
    >
      <div className="flex flex-col lg:flex-row gap-6 items-stretch">
        
        {/* ============================================================== */}
        {/* LEFT COLUMN: HERO SLIDER (70% on desktop, 100% on tablet/mob) */}
        {/* ============================================================== */}
        <section
          role="region"
          aria-roledescription="carousel"
          aria-label="Featured Auto Products Showcase"
          tabIndex={0}
          onKeyDown={handleKeyDown}
          onMouseEnter={() => setIsPaused(true)}
          onMouseLeave={() => setIsPaused(false)}
          onTouchStart={handleTouchStart}
          onTouchEnd={handleTouchEnd}
          className="relative w-full lg:w-[70%] h-[420px] sm:h-[480px] lg:h-[530px] rounded-xl overflow-hidden shadow-2xl bg-slate-900 group select-none flex-shrink-0"
        >
          {/* Slides Track */}
          <div className="relative w-full h-full">
            {slides.map((slide, index) => {
              const isActive = index === currentSlide;
              return (
                <div
                  key={slide.id}
                  role="group"
                  aria-roledescription="slide"
                  aria-label={`Slide ${index + 1} of ${totalSlides}: ${slide.title}`}
                  className={`absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out flex items-center ${
                    isActive
                      ? "opacity-100 z-10 pointer-events-auto"
                      : "opacity-0 z-0 pointer-events-none"
                  }`}
                  style={{
                    backgroundImage: `url('${slide.bgImage}')`,
                    backgroundSize: "cover",
                    backgroundPosition: "center",
                  }}
                >
                  {/* Subtle Dark Gradient Overlay for optimal readability */}
                  <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/70 to-black/20 lg:to-transparent z-[1] pointer-events-none" />

                  {/* Left Content Area */}
                  <div
                    className={`relative z-[3] max-w-[500px] pl-6 sm:pl-10 lg:pl-14 pr-4 transition-all duration-700 ease-out transform ${
                      isActive ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
                    }`}
                  >
                    <span
                      className="block text-white font-bold uppercase tracking-wider text-sm sm:text-base lg:text-xl mb-1.5 drop-shadow"
                      style={{ fontFamily: "'Oswald', sans-serif" }}
                    >
                      {slide.eyebrow}
                    </span>

                    <h2
                      className="text-white font-bold uppercase tracking-tight text-3xl sm:text-5xl lg:text-6xl leading-[1.05] mb-3 drop-shadow-md"
                      style={{ fontFamily: "'Oswald', sans-serif" }}
                    >
                      {slide.title}
                    </h2>

                    <p className="text-slate-200 text-sm sm:text-base lg:text-lg font-medium leading-relaxed mb-6 max-w-sm drop-shadow">
                      {slide.description}
                    </p>

                    <Link
                      href={slide.buttonLink}
                      className="inline-flex items-center gap-2.5 px-6 py-3 rounded-md bg-white text-gray-900 font-bold text-sm sm:text-base hover:bg-slate-100 transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5 group/btn"
                    >
                      <span>{slide.buttonText}</span>
                      <ArrowRight className="w-4 h-4 transition-transform duration-200 group-hover/btn:translate-x-1" />
                    </Link>
                  </div>

                  {/* Right Graphic Area */}
                  <div className="absolute right-0 top-0 bottom-0 w-3/5 sm:w-1/2 lg:w-3/5 h-full z-[2] flex items-center justify-end pointer-events-none overflow-hidden pr-3 sm:pr-8">
                    {/* Main Product Graphic */}
                    <img
                      src={slide.mainGraphic}
                      alt={slide.title}
                      className={`max-h-[85%] lg:max-h-[92%] max-w-[90%] object-contain drop-shadow-2xl transition-transform duration-700 ease-out ${
                        isActive ? "scale-100 translate-x-0" : "scale-95 translate-x-6"
                      }`}
                      loading={index === 0 ? "eager" : "lazy"}
                    />

                    {/* Circular Badge Graphic (e.g. Price Lock or discount badge) */}
                    {slide.badgeGraphic && (
                      <img
                        src={slide.badgeGraphic}
                        alt="Promo Badge"
                        className="hidden sm:block absolute right-24 sm:right-32 bottom-20 sm:bottom-28 w-16 h-16 lg:w-24 lg:h-24 object-contain drop-shadow-xl animate-bounce duration-1000"
                        style={{ animationDuration: "3s" }}
                        loading="lazy"
                      />
                    )}
                  </div>
                </div>
              );
            })}
          </div>

          {/* Left / Right Chevron Controls */}
          <button
            onClick={prevSlide}
            aria-label="Previous Slide"
            className="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/60 hover:bg-white text-white hover:text-gray-950 backdrop-blur-md border border-white/20 flex items-center justify-center transition-all duration-200 opacity-80 sm:opacity-0 group-hover:opacity-100 shadow-md hover:scale-105"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
          <button
            onClick={nextSlide}
            aria-label="Next Slide"
            className="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/60 hover:bg-white text-white hover:text-gray-950 backdrop-blur-md border border-white/20 flex items-center justify-center transition-all duration-200 opacity-80 sm:opacity-0 group-hover:opacity-100 shadow-md hover:scale-105"
          >
            <ChevronRight className="w-5 h-5" />
          </button>

          {/* Bottom Bullet Indicators with Tooltip preview */}
          <div
            className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 bg-slate-950/70 backdrop-blur-md border border-white/15 px-3 py-1.5 rounded-full shadow-lg"
            role="tablist"
            aria-label="Carousel Slides"
          >
            {slides.map((slide, index) => {
              const isActive = index === currentSlide;
              return (
                <button
                  key={slide.id}
                  role="tab"
                  aria-selected={isActive}
                  aria-label={`Go to slide ${index + 1}: ${slide.bulletLabel}`}
                  onClick={() => goToSlide(index)}
                  className="relative group/bullet py-1 px-0.5 focus:outline-none"
                >
                  <span
                    className={`block h-2 rounded-full transition-all duration-300 ${
                      isActive
                        ? "w-7 bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)]"
                        : "w-3 bg-white/40 hover:bg-white/70"
                    }`}
                  />
                  {/* Tooltip on Hover */}
                  <span className="hidden sm:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-0.5 text-[11px] font-semibold text-gray-900 bg-white rounded shadow-md whitespace-nowrap opacity-0 group-hover/bullet:opacity-100 transition-opacity duration-150 pointer-events-none">
                    {slide.bulletLabel}
                  </span>
                </button>
              );
            })}
          </div>

          {/* Autoplay Linear Progress Bar */}
          <div
            className="absolute bottom-0 left-0 h-[3px] bg-white/80 z-20 transition-all duration-75"
            style={{ width: `${progress}%` }}
          />
        </section>

        {/* ============================================================== */}
        {/* RIGHT COLUMN: 2 PROMO CARDS (30% desktop, grid tablet, stack mobile) */}
        {/* ============================================================== */}
        <aside
          className="w-full lg:w-[30%] flex flex-col sm:grid sm:grid-cols-2 lg:flex lg:flex-col gap-6"
          aria-label="Promotional Highlights"
        >
          {promoCards.map((card) => {
            return (
              <Link
                key={card.id}
                href={card.buttonLink}
                className="group relative flex-1 min-h-[200px] sm:min-h-[220px] lg:min-h-[250px] rounded-xl overflow-hidden p-6 sm:p-7 flex flex-col justify-between items-start transition-all duration-300 hover:-translate-y-1 hover:shadow-xl shadow-md"
                style={{
                  backgroundColor: card.cardBgColor,
                  backgroundImage: card.cardBgImage
                    ? `url('${card.cardBgImage}')`
                    : undefined,
                  backgroundPosition: "center right",
                  backgroundRepeat: "no-repeat",
                  backgroundSize: "cover",
                }}
              >
                {/* Content Area */}
                <div className="relative z-10 max-w-[65%]">
                  <span
                    className={`inline-block px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wide mb-2 ${card.badgeColorClass}`}
                  >
                    {card.badgeText}
                  </span>

                  <h3
                    className="text-gray-950 font-bold uppercase text-2xl lg:text-3xl leading-tight mb-1"
                    style={{ fontFamily: "'Oswald', sans-serif" }}
                  >
                    {card.title}
                  </h3>

                  <p className="text-gray-800 text-sm font-medium leading-snug">
                    {card.subtitle}
                  </p>
                </div>

                {/* Button */}
                <div className="relative z-10 mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-md bg-white text-gray-950 text-xs font-bold group-hover:bg-gray-950 group-hover:text-white transition-colors duration-200 shadow">
                  <span>{card.buttonText}</span>
                  <ArrowRight className="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                </div>

                {/* Floating Product Image positioned bottom-right */}
                <div className="absolute right-3 bottom-3 w-1/2 h-[82%] pointer-events-none z-[5] flex items-end justify-end overflow-hidden">
                  <img
                    src={card.productImage}
                    alt={card.imageAlt}
                    className="max-h-full max-w-full object-contain object-bottom-right drop-shadow-md transition-transform duration-400 ease-out group-hover:scale-105 group-hover:-translate-y-1"
                    loading="lazy"
                  />
                </div>
              </Link>
            );
          })}
        </aside>

      </div>
    </div>
  );
}
