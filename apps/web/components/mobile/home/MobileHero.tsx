// app/components/mobile/home/MobileHero.tsx

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Image from "next/image";
import { ChevronRight } from "lucide-react";
import MobileAnimatedSearchBar from "./MobileAnimatedSearchBar";

type Slide = {
  id: number;
  title: React.ReactNode;
  subtitle: string;
  image: string;
  imagePosition: string;
  buttonLabel: string;
  buttonHref: string;
};

const slides: Slide[] = [
  {
    id: 1,
    title: (
      <>
        Your Journey.
        <br />
        Rooted in Tradition.
      </>
    ),
    subtitle:
      "Book rituals, get guidance and feel the divine connect.",
    image: "/traditional-puja-ceremony-setup.jpg",
    imagePosition: "70% center",
    buttonLabel: "Explore Rituals",
    buttonHref: "/pujas",
  },
  {
    id: 2,
    title: (
      <>
        Authentic Pujas.
        <br />
        Verified Pandits.
      </>
    ),
    subtitle:
      "Experience sacred rituals in the comfort of your home.",
    image: "/traditional-hindu-puja-mandap.jpg",
    imagePosition: "72% center",
    buttonLabel: "Find a Pandit",
    buttonHref: "/pandits",
  },
  {
    id: 3,
    title: (
      <>
        Vedic Astrology.
        <br />
        Guiding Light.
      </>
    ),
    subtitle:
      "Get personalized insights from expert astrologers.",
    image: "/puja-ceremony-mandap.jpg",
    imagePosition: "76% center",
    buttonLabel: "Consult Astrologer",
    buttonHref: "/astrology",
  },
];

const AUTO_PLAY_MS = 5000;

export default function MobileHero() {
  const scrollRef = useRef<HTMLDivElement>(null);

  const [activeIndex, setActiveIndex] = useState(0);
  const [paused, setPaused] = useState(false);

  const goToSlide = useCallback((index: number) => {
    const container = scrollRef.current;

    if (!container) return;

    container.scrollTo({
      left: container.clientWidth * index,
      behavior: "smooth",
    });

    setActiveIndex(index);
  }, []);

  const nextSlide = useCallback(() => {
    const next =
      activeIndex === slides.length - 1
        ? 0
        : activeIndex + 1;

    goToSlide(next);
  }, [activeIndex, goToSlide]);

  useEffect(() => {
    if (paused) return;

    const timer = window.setInterval(
      nextSlide,
      AUTO_PLAY_MS
    );

    return () => window.clearInterval(timer);
  }, [paused, nextSlide]);

  const handleScroll = () => {
    const container = scrollRef.current;

    if (!container) return;

    const width = container.clientWidth;

    if (!width) return;

    const index = Math.round(
      container.scrollLeft / width
    );

    if (
      index >= 0 &&
      index < slides.length &&
      index !== activeIndex
    ) {
      setActiveIndex(index);
    }
  };

  return (
    <section
      className="relative w-full"
      aria-label="Navdhya featured services"
    >
      {/* =====================================================
          FULL-BLEED IMAGE SLIDER
      ====================================================== */}

      <div
        ref={scrollRef}
        onScroll={handleScroll}
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
        onTouchStart={() => setPaused(true)}
        onTouchEnd={() => setPaused(false)}
        className="
          no-scrollbar
          flex
          w-full
          overflow-x-auto
          snap-x
          snap-mandatory
          overscroll-x-contain
          scroll-smooth
        "
        style={{
          scrollbarWidth: "none",
          msOverflowStyle: "none",
        }}
      >
        {slides.map((slide, index) => (
          <article
            key={slide.id}
            className="
              relative
              min-h-[236px]
              min-w-full
              shrink-0
              snap-center
              overflow-hidden
              sm:min-h-[250px]
            "
          >
            {/* =================================================
                1. ACTUAL IMAGE — FULL HERO
            ================================================== */}

            <div className="absolute inset-0">
              <Image
                src={slide.image}
                alt=""
                fill
                priority={index === 0}
                sizes="100vw"
                className="object-cover"
                style={{
                  objectPosition: slide.imagePosition,
                }}
              />
            </div>

            {/* =================================================
                2. VERY LIGHT OVERLAY
                ONLY FOR CONTRAST
            ================================================== */}

            <div
              className="
                pointer-events-none
                absolute
                inset-0
                bg-black/[0.12]
              "
            />

            {/* =================================================
                3. ENHANCED LEFT READABILITY OVERLAY
            ================================================== */}

            <div
              className="
                pointer-events-none
                absolute
                inset-y-0
                left-0
                w-full
                bg-gradient-to-r
                from-[#023020]
                from-[20%]
                via-[#023020]/85
                via-[55%]
                to-transparent
              "
            />

            {/* =================================================
                4. VERY SUBTLE BOTTOM OVERLAY
            ================================================== */}

            <div
              className="
                pointer-events-none
                absolute
                inset-x-0
                bottom-0
                h-[25%]
                bg-gradient-to-t
                from-black/30
                to-transparent
              "
            />

            {/* =================================================
                CONTENT
            ================================================== */}

            <div
              className="
                relative
                z-10
                min-h-[280px]
                px-5
                pb-[80px]
                pt-10
                sm:min-h-[294px]
                sm:px-6
                sm:pt-14
              "
            >
              {/* Heading */}
              <h1
                className="
                  max-w-[250px]
                  font-playfair
                  text-[27px]
                  font-medium
                  leading-[1.04]
                  tracking-[-0.5px]
                  text-white
                  sm:text-[30px]
                "
              >
                {slide.title}
              </h1>

              {/* Subtitle */}
              <p
                className="
                  mt-3
                  max-w-[215px]
                  text-[10px]
                  leading-[1.45]
                  text-white/80
                  sm:text-[11px]
                "
              >
                {slide.subtitle}
              </p>

              {/* Action Button */}
              <div className="mt-5">
                <button
                  type="button"
                  className="flex h-[34px] w-fit items-center gap-2 rounded-full bg-white px-5 text-[11px] font-bold tracking-wide text-[#023020] shadow-[0_4px_15px_rgba(0,0,0,0.2)] transition-transform active:scale-95"
                >
                  {slide.buttonLabel}
                  <ChevronRight className="h-3.5 w-3.5" strokeWidth={2.5} />
                </button>
              </div>

            </div>
          </article>
        ))}
      </div>

      {/* =====================================================
          PERSISTENT SEARCH BAR
      ====================================================== */}

      <div
        className="
          absolute
          bottom-[14px]
          left-5
          right-5
          z-20
          sm:left-6
          sm:right-6
        "
      >
        <MobileAnimatedSearchBar />
      </div>

      {/* =====================================================
          INDICATORS
      ====================================================== */}

      <div
        className="
          hidden
          pointer-events-none
          absolute
          bottom-[58px]
          left-1/2
          z-30
          flex
          -translate-x-1/2
          items-center
          gap-1.5
        "
      >
        {slides.map((slide, index) => (
          <button
            key={slide.id}
            type="button"
            onClick={() => goToSlide(index)}
            aria-label={`Go to slide ${index + 1}`}
            aria-current={
              activeIndex === index ? "true" : undefined
            }
            className="
              pointer-events-auto
              flex
              h-3
              items-center
              justify-center
              rounded-full
            "
          >
            <span
              className={`
                block
                rounded-full
                transition-all
                duration-300
                ${activeIndex === index
                  ? "h-[4px] w-[18px] bg-[#E8A846]"
                  : "h-[4px] w-[4px] bg-white/55"
                }
              `}
            />
          </button>
        ))}
      </div>
    </section>
  );
}