
"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  ArrowRight,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ShieldCheck,
  CreditCard,
  Home,
  Headset,
  MapPin,
  Grid2X2,
} from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

/* ----------------------------------------
   Types
---------------------------------------- */

type Slide = {
  id: number;
  image: string;
};

type TrustItem = {
  title: string;
  description: string;
  icon: React.ElementType;
};

/* ----------------------------------------
   Data
---------------------------------------- */

const slides: Slide[] = [
  {
    id: 1,
    image: "/hindu-puja-ceremony-peaceful-home-setting.jpg",
  },
  {
    id: 2,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
  },
  {
    id: 3,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
  },
];

const trustItems: TrustItem[] = [
  {
    title: "Verified Pandits",
    description: "Trusted & Experienced",
    icon: ShieldCheck,
  },
  {
    title: "Secure Payments",
    description: "100% Safe & Secure",
    icon: CreditCard,
  },
  {
    title: "At Home or Temple",
    description: "Your Choice",
    icon: Home,
  },
  {
    title: "Dedicated Support",
    description: "Always Here for You",
    icon: Headset,
  },
];

/* ----------------------------------------
   Component
---------------------------------------- */

export default function HeroCarousel() {
  const [current, setCurrent] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);

  const isInteracting = useRef(false);

  const nextSlide = useCallback(() => {
    setCurrent((prev) => (prev + 1) % slides.length);
  }, []);

  const prevSlide = useCallback(() => {
    setCurrent((prev) => (prev - 1 + slides.length) % slides.length);
  }, []);

  const goToSlide = useCallback((index: number) => {
    setCurrent(index);
  }, []);

  /* ----------------------------------------
     Auto-play
  ---------------------------------------- */

  useEffect(() => {
    if (!isAutoPlaying || isInteracting.current) return;

    const timer = window.setInterval(() => {
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 6000);

    return () => window.clearInterval(timer);
  }, [isAutoPlaying]);

  /* ----------------------------------------
     Pause while interacting
  ---------------------------------------- */

  const handleMouseEnter = () => {
    isInteracting.current = true;
    setIsAutoPlaying(false);
  };

  const handleMouseLeave = () => {
    isInteracting.current = false;
    setIsAutoPlaying(true);
  };

  const handleFocus = () => {
    isInteracting.current = true;
    setIsAutoPlaying(false);
  };

  const handleBlur = () => {
    isInteracting.current = false;
    setIsAutoPlaying(true);
  };

  return (
    <section
      className="w-full bg-[#fafafa] px-3 py-2 sm:px-4 md:px-6 lg:px-8"
      aria-label="Navdhya puja booking"
    >
      <div
        className="
          relative mx-auto w-full max-w-[1440px]
          overflow-hidden rounded-[10px]
          bg-[#F6F0E4]
        "
        onMouseEnter={handleMouseEnter}
        onMouseLeave={handleMouseLeave}
        onFocus={handleFocus}
        onBlur={handleBlur}
      >
        {/* =====================================
            BACKGROUND IMAGE
        ====================================== */}

        <AnimatePresence mode="wait">
          <motion.div
            key={slides[current].id}
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -15 }}
            transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
            className="absolute inset-0"
            aria-hidden="true"
          >
            <div
              className="
                absolute inset-0
                bg-cover bg-center
                lg:bg-[position:72%_center]
              "
              style={{
                backgroundImage: `url(${slides[current].image})`,
              }}
            />

            {/* Cream content readability overlay */}
            <div
              className="
                absolute inset-0
                bg-gradient-to-r
                from-[#F6F0E4] from-[15%]
                via-[#F6F0E4]/80 via-[35%]
                to-transparent to-[60%]
              "
            />

            {/* Right image dark overlay */}
            <div
              className="
                absolute inset-y-0 right-0
                hidden w-[42%]
                bg-gradient-to-l
                from-black/75 via-black/30 to-transparent
                lg:block
              "
            />
          </motion.div>
        </AnimatePresence>

        {/* =====================================
            CONTENT WRAPPER
        ====================================== */}

        <div
          className="
            relative z-10
            flex min-h-[430px] flex-col
            justify-between
            px-5 py-6
            sm:px-8 sm:py-7
            md:min-h-[400px]
            lg:min-h-[370px]
            lg:px-[34px] lg:py-5
          "
        >
          {/* =====================================
              MAIN CONTENT ROW
          ====================================== */}

          <div className="flex flex-1 items-center">
            {/* LEFT CONTENT */}
            <div className="w-full max-w-[570px]">
              {/* Eyebrow */}
              <p
                className="
                  mb-2
                  text-[9px] font-bold
                  uppercase tracking-[0.22em]
                  text-[#667085]
                  sm:text-[10px]
                "
              >
                Tradition Lives Forever
              </p>

              {/* Heading */}
              <h1
                className="
                  max-w-[520px]
                  text-[28px] font-bold
                  leading-[1.08]
                  tracking-[-0.8px]
                  text-[#0A1A2F]
                  sm:text-[34px]
                  md:text-[38px]
                  lg:text-[36px]
                "
              >
                Book Authentic Pujas
                <br />
                with Verified Pandits
              </h1>

              {/* Description */}
              <p
                className="
                  mt-2 max-w-[480px]
                  text-[12px] leading-[1.5]
                  text-[#344054]
                  sm:text-[13px]
                "
              >
                From everyday rituals to life&apos;s special moments —
                <br className="hidden sm:block" />
                experience peace, positivity and blessings.
              </p>

              {/* =====================================
                  BOOKING SEARCH
              ====================================== */}

              <div
                className="
                  mt-4 flex w-full max-w-[500px]
                  flex-col gap-1.5
                  rounded-lg border border-[#E5E7EB]
                  bg-white p-1
                  sm:flex-row sm:gap-0
                "
              >
                {/* LOCATION */}
                <Link
                  href="/book"
                  className="
                    flex min-h-[40px] flex-1
                    items-center gap-2
                    rounded-md px-3
                    text-left
                    transition-colors
                    hover:bg-[#FAFAFA]
                    focus-visible:outline-none
                    focus-visible:ring-2
                    focus-visible:ring-[#FF4500]
                  "
                  aria-label="Choose location for puja booking"
                  role="button"
                >
                  <MapPin
                    className="h-4 w-4 shrink-0 text-[#2563EB]"
                    strokeWidth={2}
                  />

                  <span className="flex-1 text-[11px] font-semibold text-[#1D2939]">
                    Delhi NCR
                  </span>

                  <ChevronDown className="h-3.5 w-3.5 text-[#667085]" />
                </Link>

                <div className="hidden w-px bg-[#E5E7EB] sm:block" />

                {/* PUJA */}
                <Link
                  href="/book"
                  className="
                    flex min-h-[40px] flex-1
                    items-center gap-2
                    rounded-md px-3
                    text-left
                    transition-colors
                    hover:bg-[#FAFAFA]
                    focus-visible:outline-none
                    focus-visible:ring-2
                    focus-visible:ring-[#FF4500]
                  "
                  aria-label="Choose puja"
                  role="button"
                >
                  <Grid2X2
                    className="h-[15px] w-[15px] shrink-0 text-[#475467]"
                    strokeWidth={1.8}
                  />

                  <span className="flex-1 text-[11px] font-semibold text-[#1D2939]">
                    Select Puja
                  </span>

                  <ChevronDown className="h-3.5 w-3.5 text-[#667085]" />
                </Link>

                {/* SEARCH CTA */}
                <Link href="/book" className="sm:ml-1">
                  <Button
                    type="button"
                    className="
                      group
                      h-[40px] w-full
                      rounded-md
                      bg-[#FF4500]
                      px-5
                      text-[11px] font-semibold
                      text-white
                      hover:bg-[#E63E00]
                      hover:-translate-y-px
                      hover:shadow-md
                      transition-all
                      duration-200
                      sm:w-auto
                    "
                  >
                    Search Now
                    <ArrowRight
                      className="ml-1.5 h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1"
                      strokeWidth={2.5}
                    />
                  </Button>
                </Link>
              </div>

              {/* =====================================
                  TRUST INDICATORS
              ====================================== */}

              <div
                className="
                  mt-5 flex flex-wrap
                  items-center
                  gap-x-6 gap-y-3
                  lg:mt-6 lg:gap-x-7
                "
              >
                {trustItems.map((item, index) => {
                  const Icon = item.icon;

                  return (
                    <div
                      key={item.title}
                      className={`
                        flex items-center gap-2
                        ${index > 1 ? "hidden md:flex" : ""}
                      `}
                    >
                      <Icon
                        className="h-[19px] w-[19px] shrink-0 text-[#344054]"
                        strokeWidth={1.6}
                      />

                      <div>
                        <p className="text-[10px] font-semibold leading-tight text-[#1D2939]">
                          {item.title}
                        </p>

                        <p className="mt-0.5 text-[9px] leading-tight text-[#667085]">
                          {item.description}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* =====================================
                RIGHT MESSAGE
            ====================================== */}

            <div
              className="
                ml-auto hidden w-[150px]
                shrink-0
                lg:block
                xl:mr-10
              "
            >
              <h2
                className="
                  text-[20px] font-medium
                  leading-[1.35]
                  text-white
                "
              >
                Faith
                <br />
                Brings Us
                <br />
                Together
              </h2>

              <div className="my-4 h-[2px] w-7 bg-[#FF4500]" />

              <p className="text-[11px] leading-[1.5] text-white/90">
                Navdhya
                <br />
                Your Partner
                <br />
                in Every Puja
              </p>
            </div>
          </div>
        </div>

        {/* =====================================
            CAROUSEL CONTROLS
        ====================================== */}

        <div
          className="
            absolute bottom-4 right-4
            z-20 flex items-center gap-2
            lg:bottom-5 lg:right-5
          "
        >
          {/* PREVIOUS */}
          <button
            type="button"
            onClick={prevSlide}
            className="
              flex h-7 w-7 items-center justify-center
              rounded-full
              border border-white/60
              bg-black/10
              text-white
              transition-colors
              hover:bg-white hover:text-[#101828]
              focus-visible:outline-none
              focus-visible:ring-2
              focus-visible:ring-white
            "
            aria-label="Previous slide"
          >
            <ChevronLeft className="h-3.5 w-3.5" />
          </button>

          {/* INDICATORS */}
          <div className="flex items-center gap-1.5">
            {slides.map((slide, index) => (
              <button
                key={slide.id}
                type="button"
                onClick={() => goToSlide(index)}
                className={`
                  rounded-full
                  transition-all duration-300
                  focus-visible:outline-none
                  focus-visible:ring-2
                  focus-visible:ring-white
                  ${index === current
                    ? "h-1.5 w-6 bg-[#FF4500]"
                    : "h-1.5 w-1.5 bg-white/70 hover:bg-white"
                  }
                `}
                aria-label={`Go to slide ${index + 1}`}
                aria-current={index === current ? "true" : undefined}
              />
            ))}
          </div>

          {/* NEXT */}
          <button
            type="button"
            onClick={nextSlide}
            className="
              flex h-7 w-7 items-center justify-center
              rounded-full
              border border-white/60
              bg-black/10
              text-white
              transition-colors
              hover:bg-white hover:text-[#101828]
              focus-visible:outline-none
              focus-visible:ring-2
              focus-visible:ring-white
            "
            aria-label="Next slide"
          >
            <ChevronRight className="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </section>
  );
}
