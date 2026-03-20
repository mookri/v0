"use client";

/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║       NAVDHYA — HeroImage  (Next.js native · 2027)             ║
 * ║  next/image · fill · blurDataURL · custom host · SSR-safe      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * Uses next/image for:
 *   ✔ Automatic WebP/AVIF serving via Next.js image optimiser
 *   ✔ Built-in lazy loading & priority (LCP) hint
 *   ✔ blurDataURL placeholder — zero layout shift
 *   ✔ fill layout — parent drives dimensions, image fills it
 *   ✔ sizes prop — responsive CDN requests per breakpoint
 *
 * Custom hosting:
 *   Images live at  /public/images/hero/  in dev.
 *   In prod they can be served from any CDN — set NEXT_PUBLIC_CDN_URL
 *   and add the domain to next.config.ts remotePatterns.
 */

import Image from "next/image";
import { useState } from "react";

// ─── Props ────────────────────────────────────────────────────────────────────

interface HeroImageProps {
    /** Absolute URL (CDN) or root-relative path (/images/hero/foo.webp) */
    src?: string;
    alt: string;
    /** Base-64 LQIP thumbnail from hero-images.json → srcSet.thumb */
    blurDataURL?: string;
    /** true for above-fold LCP images (default). false for below-fold. */
    priority?: boolean;
    /** Tailwind / CSS classes applied to the outer wrapper div */
    className?: string;
    /**
     * Responsive sizes hint — tells browser which CDN size to fetch.
     * Defaults to a sane full-width hero value.
     */
    sizes?: string;
}

// ─── Fallback ─────────────────────────────────────────────────────────────────

function HeroFallback({ alt, className = "" }: { alt: string; className?: string }) {
    return (
        <div
            className={`relative w-full h-full bg-gradient-to-br from-[#0E3F2E]/10 to-muted
                  flex items-center justify-center overflow-hidden ${className}`}
            role="img"
            aria-label={alt}
        >
            <div className="absolute inset-0 opacity-[0.04]
                      bg-[radial-gradient(circle,_#000_1px,_transparent_1px)]
                      [background-size:18px_18px]" />
            <div className="text-center px-4 relative z-10">
                <div className="w-10 h-10 mx-auto mb-3 rounded-full bg-[#0E3F2E]/10
                        flex items-center justify-center">
                    <span className="text-[#0E3F2E] text-xs font-medium select-none">NV</span>
                </div>
                <p className="text-xs text-muted-foreground">Image unavailable</p>
            </div>
        </div>
    );
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function HeroImage({
    src,
    alt,
    blurDataURL,
    priority = true,
    className = "",
    sizes = "(max-width: 640px) 100vw, (max-width: 1024px) 100vw, 1200px",
}: HeroImageProps) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return <HeroFallback alt={alt} className={className} />;
    }

    return (
        <div className={`relative w-full h-full overflow-hidden ${className}`}>

            {/* ── Next.js Image (fill mode — parent controls dimensions) ─── */}
            <Image
                src={src}
                alt={alt}
                fill
                sizes={sizes}
                priority={priority}
                quality={85}
                placeholder={blurDataURL ? "blur" : "empty"}
                blurDataURL={blurDataURL}
                onError={() => setError(true)}
                className="object-cover"
            // Next.js handles fetchPriority internally when priority=true
            />

            {/* ── Depth vignette — bottom dark overlay ─────────────────── */}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-gradient-to-t
                   from-[#0E3F2E]/70 via-transparent to-transparent
                   pointer-events-none z-10"
            />

            {/* ── Top light vignette ────────────────────────────────────── */}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-gradient-to-b
                   from-white/5 to-transparent
                   pointer-events-none z-10"
            />
        </div>
    );
}