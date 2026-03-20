"use client";

import { useState, useRef, useEffect } from "react";

interface HeroImageProps {
    src?: string;
    alt: string;
    /** Load eagerly (LCP image above fold). Defaults to true. */
    priority?: boolean;
    /** Low-quality image placeholder (base64 data URI or tiny URL) for blur-up effect. */
    blurDataUrl?: string;
    /** Aspect ratio class applied to the wrapper. Defaults to auto-fill. */
    className?: string;
}

export default function HeroImage({
    src,
    alt,
    priority = true,
    blurDataUrl,
    className = "",
}: HeroImageProps) {
    const [loaded, setLoaded] = useState(false);
    const [error, setError] = useState(false);
    const [inView, setInView] = useState(priority); // eager images start as in-view
    const imgRef = useRef<HTMLImageElement>(null);

    // Lazy images: only start loading once the container enters the viewport
    useEffect(() => {
        if (priority || inView) return;

        const el = imgRef.current?.parentElement;
        if (!el) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setInView(true);
                    observer.disconnect();
                }
            },
            { rootMargin: "200px" },
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [priority, inView]);

    // Fallback — never break the UI
    if (!src || error) {
        return (
            <div
                className={`relative w-full h-full bg-gradient-to-br from-[#0E3F2E]/10 to-muted flex items-center justify-center overflow-hidden ${className}`}
                role="img"
                aria-label={alt}
            >
                {/* Subtle dot pattern */}
                <div className="absolute inset-0 opacity-[0.04] bg-[radial-gradient(circle,_#000_1px,_transparent_1px)] [background-size:18px_18px]" />
                <div className="text-center px-4 relative z-10">
                    <div className="w-10 h-10 mx-auto mb-3 rounded-full bg-[#0E3F2E]/10 flex items-center justify-center">
                        <span className="text-[#0E3F2E] text-xs font-medium select-none">NV</span>
                    </div>
                    <p className="text-xs text-muted-foreground">Image unavailable</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`relative w-full h-full overflow-hidden bg-muted ${className}`}>

            {/* ── Blur-up placeholder ────────────────────────────────────────── */}
            {blurDataUrl && (
                <img
                    src={blurDataUrl}
                    aria-hidden="true"
                    alt=""
                    className={`
            absolute inset-0 w-full h-full object-cover
            scale-105 blur-xl
            transition-opacity duration-700
            ${loaded ? "opacity-0" : "opacity-100"}
          `}
                />
            )}

            {/* ── Skeleton shimmer (shown when no blurDataUrl) ──────────────── */}
            {!blurDataUrl && (
                <div
                    aria-hidden="true"
                    className={`
            absolute inset-0 transition-opacity duration-500
            ${loaded ? "opacity-0 pointer-events-none" : "opacity-100"}
          `}
                >
                    <div className="w-full h-full bg-gradient-to-r from-neutral-200 via-neutral-100 to-neutral-200 animate-pulse" />
                </div>
            )}

            {/* ── Main image ────────────────────────────────────────────────── */}
            {/*
       * BUG FIX: fetchPriority was cast as `any` to silence TypeScript.
       * The correct attribute name on <img> in React 18+ is `fetchPriority`
       * (camelCase) and its type is "high" | "low" | "auto". No cast needed.
       */}
            {inView && (
                <img
                    ref={imgRef}
                    src={src}
                    alt={alt}
                    loading={priority ? "eager" : "lazy"}
                    decoding="async"
                    fetchPriority={priority ? "high" : "auto"}
                    onLoad={() => setLoaded(true)}
                    onError={() => setError(true)}
                    className={`
            absolute inset-0 w-full h-full object-cover
            transition-all duration-700 ease-out will-change-transform
            ${loaded ? "opacity-100 scale-100" : "opacity-0 scale-[1.04]"}
          `}
                />
            )}

            {/* ── Depth overlay — bottom dark vignette ──────────────────────── */}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-gradient-to-t from-[#0E3F2E]/70 via-transparent to-transparent pointer-events-none"
            />

            {/* ── Top light vignette ────────────────────────────────────────── */}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent pointer-events-none"
            />
        </div>
    );
}