"use client";

import {
    ChevronLeft,
    ChevronRight,
} from "lucide-react";

interface Props {
    onPrev: () => void;
    onNext: () => void;
}

export default function CarouselControls({
    onPrev,
    onNext,
}: Props) {
    const baseClass = `
    flex
    h-7
    w-7
    items-center
    justify-center
    rounded-md
    border
    border-neutral-200
    bg-white
    text-neutral-500
    transition-colors
    duration-150
    hover:border-neutral-300
    hover:bg-neutral-50
    hover:text-neutral-900
    focus-visible:outline-none
    focus-visible:ring-2
    focus-visible:ring-orange-500/20
  `;

    return (
        <div className="flex items-center gap-1">
            <button
                type="button"
                aria-label="Previous pujas"
                onClick={onPrev}
                className={baseClass}
            >
                <ChevronLeft className="h-3.5 w-3.5" />
            </button>

            <button
                type="button"
                aria-label="Next pujas"
                onClick={onNext}
                className={baseClass}
            >
                <ChevronRight className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}