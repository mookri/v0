/**
 * @deprecated Use HeroAd from ./NativeAds instead — that version uses the
 * Adtokens CSS variable system and supports light/dark image detection.
 * This file exists only for backwards compatibility.
 */
/* ═══════════════════════════════════════════════════════════════════════════
   AD LAYOUT  ·  HeroAd.tsx
   Full editorial bleed — flagship placement
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import { useRef, useState, useEffect } from "react";
import { ArrowRight, ShieldCheck } from "lucide-react";
import { Ad } from "./core/types";

interface Props {
    ad: Ad;
    onImpression?: () => void;
    onClick?: () => void;
}

export default function HeroAd({ ad, onImpression, onClick }: Props) {
    const ref = useRef<HTMLElement>(null);
    const [visible, setVisible] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const [error, setError] = useState(false);

    const imageUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const bgColor = ad.creative?.bgColor ?? "#0e2a1e";

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const obs = new IntersectionObserver(
            ([e]) => { if (e.isIntersecting) { setVisible(true); obs.disconnect(); } },
            { rootMargin: "200px" }
        );
        obs.observe(el);
        return () => obs.disconnect();
    }, []);

    // BUG-06 FIX: onImpression added to deps — was stale closure.
    // Also: this file is superseded by NativeAds.tsx HeroAd which uses the
    // token system. This standalone version is kept for legacy compat only.
    useEffect(() => {
        if (visible && loaded && onImpression) onImpression();
    }, [visible, loaded, onImpression]);

    return (
        <section ref={ref} className="mb-10 w-full">
            <div className="relative -mx-6 md:-mx-10 overflow-hidden">
                <div className="relative h-[380px] sm:h-[440px] md:h-[480px] group">

                    {/* Lazy image */}
                    {visible && imageUrl && !error && (
                        <img
                            src={imageUrl}
                            alt={ad.creative?.alt ?? ad.title}
                            loading="lazy"
                            decoding="async"
                            onLoad={() => setLoaded(true)}
                            onError={() => setError(true)}
                            className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                        />
                    )}

                    {/* Fallback */}
                    {(error || !imageUrl) && (
                        <div className="absolute inset-0" style={{ background: `linear-gradient(135deg, ${bgColor}, #000)` }} />
                    )}

                    {/* Gradient overlays */}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent" />
                    <div className="absolute inset-0 bg-gradient-to-r from-black/35 via-transparent to-transparent" />

                    {/* Sponsor tag */}
                    <div className="absolute top-5 left-6 md:left-10">
                        <span className="text-[9px] uppercase tracking-[0.35em] text-white/60 font-medium">
                            Sponsored{ad.sponsorName ? ` · ${ad.sponsorName}` : ""}
                        </span>
                    </div>

                    {/* Content */}
                    <div className="absolute inset-0 flex flex-col justify-end">
                        <div className="px-6 md:px-10 pb-10 max-w-[1200px] mx-auto w-full">
                            <div className="max-w-[680px]">
                                {ad.eyebrow && (
                                    <p className="text-[11px] uppercase tracking-[0.25em] text-white/60 mb-3 font-medium">
                                        {ad.eyebrow}
                                    </p>
                                )}
                                <h3 className="text-white font-serif text-[clamp(1.7rem,3.2vw,2.8rem)] leading-[1.05] font-semibold mb-4">
                                    {ad.title}
                                </h3>
                                {ad.description && (
                                    <p className="text-white/80 text-[15px] leading-relaxed mb-7 max-w-[560px]">
                                        {ad.description}
                                    </p>
                                )}
                                <div className="flex items-center gap-5 flex-wrap">
                                    <a
                                        href={ad.ctaUrl}
                                        onClick={onClick}
                                        className="inline-flex items-center gap-2 bg-white text-black px-6 h-11 text-[13px] font-semibold transition-all duration-300 hover:gap-3 shadow-md hover:shadow-lg"
                                    >
                                        {ad.ctaText}
                                        <ArrowRight size={14} />
                                    </a>
                                    {ad.verified && (
                                        <span className="flex items-center gap-1.5 text-[11px] text-white/55">
                                            <ShieldCheck size={12} className="text-emerald-400" />
                                            Verified partner
                                        </span>
                                    )}
                                    {ad.badge && (
                                        <span className="px-2.5 py-1 bg-amber-400 text-black text-[10px] font-bold uppercase tracking-wide">
                                            {ad.badge}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
