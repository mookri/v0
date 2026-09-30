"use client";

import { ChevronRight } from "lucide-react";
import Link from "next/link";

export default function SacredSpaces() {
    return (
        <div className="relative h-full overflow-hidden rounded-lg border border-neutral-200 bg-white p-4 flex flex-col items-start justify-between group">
            
            {/* Abstract Background Temple Pattern */}
            <div className="absolute bottom-0 right-2 h-[140px] w-[180px] opacity-[0.15] pointer-events-none transform translate-y-2">
                <svg viewBox="0 0 200 100" preserveAspectRatio="xMaxYMax meet" className="h-full w-full fill-orange-400">
                    <path d="M10,100 L10,80 L20,70 L20,50 L30,20 L40,50 L40,70 L50,80 L50,100 Z M60,100 L60,60 L70,50 L70,30 L80,10 L90,30 L90,50 L100,60 L100,100 Z M110,100 L110,75 L120,65 L120,45 L130,25 L140,45 L140,65 L150,75 L150,100 Z" />
                </svg>
            </div>

            <div className="flex flex-col min-w-0 relative z-10">
                <h3 className="text-[18px] sm:text-[20px] font-bold tracking-tight text-neutral-900 leading-tight">
                    Sacred Spaces<br />Across India
                </h3>
                <p className="text-[12px] sm:text-[13px] text-neutral-500 mt-2 max-w-[160px] leading-snug">
                    Book pujas at renowned temples with ease.
                </p>
            </div>
            
            <Link 
                href="/temples"
                className="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-neutral-200 bg-white text-neutral-600 transition-colors self-end mt-4"
            >
                <ChevronRight className="h-5 w-5" />
            </Link>
        </div>
    );
}
