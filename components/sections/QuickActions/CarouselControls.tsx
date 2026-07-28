"use client";

import { ChevronLeft, ChevronRight } from "lucide-react";

interface Props {
    onPrev: () => void;
    onNext: () => void;
}

export default function CarouselControls({
    onPrev,
    onNext,
}: Props) {
    return (
        <>
            {/* Left */}

            <button
                aria-label="Previous"
                onClick={onPrev}
                className="
          absolute
          left-0
          top-1/2
          z-30
          -translate-y-1/2

          hidden
          lg:flex

          h-11
          w-11
          items-center
          justify-center

          rounded-full

          border
          border-neutral-200

          bg-white/90
          backdrop-blur-xl

          shadow-lg

          transition-all
          duration-300

          hover:scale-105
          hover:bg-white

          dark:border-white/10
          dark:bg-neutral-900/90
        "
            >
                <ChevronLeft className="h-5 w-5" />
            </button>

            {/* Right */}

            <button
                aria-label="Next"
                onClick={onNext}
                className="
          absolute
          right-0
          top-1/2
          z-30
          -translate-y-1/2

          hidden
          lg:flex

          h-11
          w-11
          items-center
          justify-center

          rounded-full

          border
          border-neutral-200

          bg-white/90
          backdrop-blur-xl

          shadow-lg

          transition-all
          duration-300

          hover:scale-105
          hover:bg-white

          dark:border-white/10
          dark:bg-neutral-900/90
        "
            >
                <ChevronRight className="h-5 w-5" />
            </button>
        </>
    );
}