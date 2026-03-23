//components/ad-network/NativeAds.tsx
/* ═══════════════════════════════════════════════════════════════════════════
   AD LAYOUTS  ·  HeroAd + NativeAds
   
   KEY FIX: resolveTextScheme() + schemeTokens() used everywhere text appears
   over an image. A light ad image (like a bright architectural photo) will
   now automatically render dark navy text instead of invisible white text.
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import { useCallback, useState } from "react";
import { ArrowRight, ArrowUpRight, ShieldCheck, ExternalLink } from "lucide-react";
import { V, css, resolveTextScheme, schemeTokens } from "./Adtokens";
import { useAdLazy } from "./Adprovider";
import type { Ad } from "./core/types";

// ─────────────────────────────────────────────────────────────────────────────
// HeroAd  —  full editorial bleed, flagship placement
// ─────────────────────────────────────────────────────────────────────────────

interface AdProps {
    ad: Ad;
    onImpression?: () => void;
    onClick?: () => void;
}

export function HeroAd({ ad, onImpression, onClick }: AdProps) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression, "200px");

    // ── Resolve text scheme from ad data ────────────────────────────────────
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    const showImg = visible && imgUrl && !imgError;
    const bgFallback = ad.creative?.bgColor ?? css(V.bgFallback);

    return (
        <section
            ref={ref as React.RefObject<HTMLElement>}
            style={{ display: "block", width: "100%", marginBottom: 40 }}
        >
            <div className="relative -mx-6 md:-mx-10 overflow-hidden">
                <div
                    className="relative group"
                    style={{ height: "clamp(320px, 42vw, 520px)" }}
                >
                    {/* Fallback background */}
                    <div
                        className="absolute inset-0"
                        style={{ background: `linear-gradient(135deg, ${bgFallback}, #000)` }}
                    />

                    {/* Hero image */}
                    {showImg && (
                        <img
                            src={imgUrl}
                            alt={ad.creative?.alt ?? ad.title}
                            loading="lazy"
                            decoding="async"
                            onLoad={onImgLoad}
                            onError={onImgError}
                            className="absolute inset-0 w-full h-full object-cover"
                            style={{
                                transition: `transform ${css(V.easeHover)}, opacity ${css(V.easeImage)}`,
                                opacity: loaded ? 1 : 0,
                            }}
                        />
                    )}

                    {/* Gradient overlay — adapts direction based on scheme */}
                    <div
                        className="absolute inset-0"
                        style={{
                            background:
                                scheme === "dark"
                                    ? css(V.overlayHero)
                                    : "linear-gradient(to top,rgba(255,255,255,.70) 0%,rgba(255,255,255,.25) 50%,transparent 100%)",
                        }}
                    />
                    <div
                        className="absolute inset-0"
                        style={{
                            background:
                                scheme === "dark"
                                    ? css(V.overlaySide)
                                    : "linear-gradient(to right,rgba(255,255,255,.50) 0%,transparent 55%)",
                        }}
                    />

                    {/* Sponsor tag */}
                    <div className="absolute top-5 left-6 md:left-10">
                        <span
                            className="uppercase"
                            style={{
                                fontSize: 9,
                                letterSpacing: css(V.labelSpacing),
                                color: T.label,
                                fontFamily: css(V.fontBody),
                                fontWeight: 500,
                            }}
                        >
                            Sponsored{ad.sponsorName ? ` · ${ad.sponsorName}` : ""}
                        </span>
                    </div>

                    {/* Content */}
                    <div className="absolute inset-0 flex flex-col justify-end">
                        <div className="px-6 md:px-10 pb-10 max-w-[1200px] mx-auto w-full">
                            <div style={{ maxWidth: 680 }}>
                                {ad.eyebrow && (
                                    <p
                                        className="mb-3 uppercase"
                                        style={{
                                            fontSize: 11,
                                            letterSpacing: css(V.labelSpacing),
                                            color: T.muted,
                                            fontFamily: css(V.fontBody),
                                            fontWeight: 500,
                                        }}
                                    >
                                        {ad.eyebrow}
                                    </p>
                                )}

                                <h3
                                    className="mb-4"
                                    style={{
                                        fontFamily: css(V.fontDisplay),
                                        fontSize: "clamp(1.7rem, 3.2vw, 2.8rem)",
                                        lineHeight: 1.05,
                                        fontWeight: 600,
                                        color: T.heading,
                                    }}
                                >
                                    {ad.title}
                                </h3>

                                {ad.description && (
                                    <p
                                        className="mb-7"
                                        style={{
                                            fontSize: 15,
                                            lineHeight: 1.6,
                                            color: T.body,
                                            maxWidth: 560,
                                            fontFamily: css(V.fontBody),
                                        }}
                                    >
                                        {ad.description}
                                    </p>
                                )}

                                <div className="flex items-center gap-5 flex-wrap">
                                    <a
                                        href={ad.ctaUrl}
                                        onClick={onClick}
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        className="inline-flex items-center gap-2"
                                        style={{
                                            height: 44,
                                            padding: "0 24px",
                                            background: T.ctaBg,
                                            color: T.ctaFg,
                                            fontSize: 13,
                                            fontWeight: 600,
                                            fontFamily: css(V.fontBody),
                                            textDecoration: "none",
                                            borderRadius: css(V.rSm),
                                            boxShadow: css(V.shadowCta),
                                            transition: `background ${css(V.easeFast)}`,
                                        }}
                                    >
                                        {ad.ctaText}
                                        <ArrowRight size={14} strokeWidth={2.5} />
                                    </a>
                                    {ad.verified && (
                                        <span
                                            className="flex items-center gap-1.5"
                                            style={{ fontSize: 11, color: T.muted, fontFamily: css(V.fontBody) }}
                                        >
                                            <ShieldCheck size={12} style={{ color: css(V.success) }} />
                                            Verified partner
                                        </span>
                                    )}
                                    {ad.badge && (
                                        <span
                                            style={{
                                                padding: "4px 10px",
                                                background: css(V.accent),
                                                color: css(V.accentFg),
                                                fontSize: 10,
                                                fontWeight: 700,
                                                fontFamily: css(V.fontBody),
                                                textTransform: "uppercase" as const,
                                                letterSpacing: "0.06em",
                                                borderRadius: css(V.rSm),
                                            }}
                                        >
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

// ─────────────────────────────────────────────────────────────────────────────
// InlineTextAd  —  editorial text row, no image (always light surface)
// ─────────────────────────────────────────────────────────────────────────────

export function InlineTextAd({ ad, onImpression, onClick }: AdProps) {
    const { ref } = useAdLazy(undefined, onImpression);

    return (
        <div
            ref={ref}
            className="my-6 flex items-center justify-between gap-4 flex-wrap"
            style={{
                paddingLeft: 16,
                paddingTop: 8,
                paddingBottom: 8,
                borderLeft: `2px solid ${css(V.accent)}`,
            }}
        >
            <div className="flex items-start gap-3 min-w-0">
                <div className="min-w-0">
                    <span
                        className="uppercase mr-2"
                        style={{
                            fontSize: 9,
                            letterSpacing: css(V.labelSpacing),
                            color: css(V.onLight3),
                            fontFamily: css(V.fontBody),
                            fontWeight: 500,
                        }}
                    >
                        Sponsored{ad.sponsorName ? ` · ${ad.sponsorName}` : ""}
                    </span>
                    <span
                        style={{
                            fontSize: 14,
                            fontWeight: 500,
                            color: css(V.onLight1),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.title}
                    </span>
                    {ad.verified && (
                        <ShieldCheck
                            size={11}
                            className="inline ml-2 relative -top-px"
                            style={{ color: css(V.success) }}
                        />
                    )}
                </div>
            </div>

            <a
                href={ad.ctaUrl}
                onClick={onClick}
                target="_blank"
                rel="noopener noreferrer nofollow"
                className="flex-shrink-0 inline-flex items-center gap-1.5 whitespace-nowrap"
                style={{
                    color: css(V.accent),
                    fontSize: 13,
                    fontWeight: 700,
                    fontFamily: css(V.fontBody),
                    textDecoration: "none",
                }}
            >
                {ad.ctaText}
                <ArrowUpRight size={13} />
            </a>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// NativeFeedAd  —  editorial in-feed unit (always light surface)
// ─────────────────────────────────────────────────────────────────────────────

export function NativeFeedAd({ ad, onImpression, onClick }: AdProps) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, imgError, onImgLoad, onImgError } = useAdLazy(imgUrl, onImpression);

    return (
        <div ref={ref} className="w-full my-4">
            <div className="flex items-center gap-2 mb-3">
                <div className="flex-1 h-px" style={{ background: css(V.bgBorder) }} />
                <span
                    className="flex-shrink-0 uppercase"
                    style={{
                        fontSize: 8,
                        letterSpacing: css(V.labelSpacing),
                        color: css(V.onLight3),
                        fontFamily: css(V.fontBody),
                    }}
                >
                    Sponsored
                </span>
                <div className="flex-1 h-px" style={{ background: css(V.bgBorder) }} />
            </div>

            <a
                href={ad.ctaUrl}
                onClick={onClick}
                target="_blank"
                rel="noopener noreferrer nofollow"
                className="group flex gap-4 items-start no-underline"
            >
                {imgUrl && (
                    <div
                        className="flex-shrink-0 overflow-hidden"
                        style={{
                            width: 96,
                            height: 80,
                            borderRadius: css(V.rMd),
                            background: css(V.bgFallback),
                        }}
                    >
                        {visible && !imgError && (
                            <img
                                src={imgUrl}
                                alt={ad.creative?.alt ?? ad.title}
                                loading="lazy"
                                decoding="async"
                                onLoad={onImgLoad}
                                onError={onImgError}
                                className="w-full h-full object-cover"
                            />
                        )}
                    </div>
                )}

                <div className="flex-1 min-w-0">
                    {ad.eyebrow && (
                        <p
                            className="mb-1 uppercase"
                            style={{
                                fontSize: 9,
                                letterSpacing: "0.2em",
                                color: css(V.accent),
                                fontWeight: 600,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.eyebrow}
                        </p>
                    )}
                    <h4
                        className="mb-1.5 line-clamp-2"
                        style={{
                            fontSize: 14,
                            fontWeight: 600,
                            lineHeight: 1.3,
                            color: css(V.onLight1),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.title}
                    </h4>
                    {ad.description && (
                        <p
                            className="mb-2 line-clamp-2"
                            style={{
                                fontSize: 12,
                                lineHeight: 1.5,
                                color: css(V.onLight2),
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.description}
                        </p>
                    )}
                    <div className="flex items-center gap-2">
                        <span
                            className="inline-flex items-center gap-1"
                            style={{ fontSize: 11, fontWeight: 600, color: css(V.accent), fontFamily: css(V.fontBody) }}
                        >
                            {ad.ctaText} <ExternalLink size={10} />
                        </span>
                        {ad.sponsorName && (
                            <span style={{ fontSize: 9, color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                                {ad.sponsorName}
                            </span>
                        )}
                    </div>
                </div>
            </a>

            <div className="mt-4 h-px" style={{ background: css(V.bgBorder) }} />
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// CardGridAd  —  2–3 column card grid
// ─────────────────────────────────────────────────────────────────────────────

interface CardGridProps {
    ads: Ad[];
    columns?: 2 | 3;
    onImpression?: (ad: Ad) => void;
    onClick?: (ad: Ad) => void;
}

function GridCard({ ad, onClick }: { ad: Ad; onClick?: () => void }) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const [imgLoaded, setImgLoaded] = useState(false);
    const [imgErr, setImgErr] = useState(false);

    return (
        <a
            href={ad.ctaUrl}
            onClick={onClick}
            target="_blank"
            rel="noopener noreferrer nofollow"
            className="group block overflow-hidden"
            style={{
                borderRadius: css(V.rMd),
                boxShadow: css(V.shadowCard),
                textDecoration: "none",
                transition: `transform ${css(V.easeBase)}, box-shadow ${css(V.easeBase)}`,
            }}
            onMouseEnter={(e) => {
                const el = e.currentTarget as HTMLElement;
                el.style.transform = "translateY(-2px)";
                el.style.boxShadow = css(V.shadowFloat);
            }}
            onMouseLeave={(e) => {
                const el = e.currentTarget as HTMLElement;
                el.style.transform = "translateY(0)";
                el.style.boxShadow = css(V.shadowCard);
            }}
        >
            <div
                className="relative overflow-hidden"
                style={{ aspectRatio: "4/3", background: css(V.bgFallback) }}
            >
                {imgUrl && !imgErr && (
                    <img
                        src={imgUrl}
                        alt={ad.creative?.alt ?? ad.title}
                        loading="lazy"
                        decoding="async"
                        onLoad={() => setImgLoaded(true)}
                        onError={() => setImgErr(true)}
                        className="absolute inset-0 w-full h-full object-cover"
                        style={{ opacity: imgLoaded ? 1 : 0, transition: `opacity ${css(V.easeImage)}` }}
                    />
                )}
                {ad.badge && (
                    <div
                        className="absolute top-2 right-2"
                        style={{
                            background: css(V.accent),
                            color: css(V.accentFg),
                            fontSize: 8,
                            fontWeight: 900,
                            textTransform: "uppercase" as const,
                            padding: "2px 6px",
                            borderRadius: css(V.rSm),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.badge}
                    </div>
                )}
            </div>

            <div className="p-3" style={{ background: css(V.bgCard), borderTop: `1px solid ${css(V.bgBorder)}` }}>
                <p className="mb-1 uppercase" style={{ fontSize: 8, letterSpacing: "0.2em", color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                    {ad.sponsorName ?? "Sponsored"}
                </p>
                <h4 className="mb-1.5 leading-tight" style={{ fontSize: 13, fontWeight: 600, color: css(V.onLight1), fontFamily: css(V.fontBody) }}>
                    {ad.title}
                </h4>
                {ad.description && (
                    <p className="mb-2 line-clamp-2" style={{ fontSize: 11, lineHeight: 1.5, color: css(V.onLight2), fontFamily: css(V.fontBody) }}>
                        {ad.description}
                    </p>
                )}
                <span className="inline-flex items-center gap-1" style={{ fontSize: 11, fontWeight: 700, color: css(V.accent), fontFamily: css(V.fontBody) }}>
                    {ad.ctaText} <ArrowUpRight size={11} />
                </span>
            </div>
        </a>
    );
}

export function CardGridAd({ ads, columns = 3, onImpression, onClick }: CardGridProps) {
    // BUG-10 FIX: was calling onImpression for every ad in the array from a
    // single intersection trigger, AND the outer AdSlot fired again — double-count.
    // Fix: fire ONE impression for the grid unit (the first ad as representative).
    // Individual card clicks are tracked separately via onClick(ad).
    const firstAd = ads[0];
    const handleImpression = useCallback(() => {
        if (firstAd) onImpression?.(firstAd);
    }, [firstAd, onImpression]);

    const { ref } = useAdLazy(undefined, handleImpression);

    if (!ads.length) return null;

    return (
        <div ref={ref} className="my-8">
            <div className="flex items-center justify-between mb-3">
                <span className="uppercase" style={{ fontSize: 8, letterSpacing: css(V.labelSpacing), color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                    Sponsored{ads[0]?.sponsorName ? ` · ${ads[0].sponsorName}` : ""}
                </span>
                <span style={{ fontSize: 8, color: css(V.onLight3), fontFamily: css(V.fontBody) }}>Ad</span>
            </div>
            <div
                className="grid gap-3"
                style={{ gridTemplateColumns: `repeat(${columns}, minmax(0,1fr))` }}
            >
                {ads.map((ad) => (
                    <GridCard key={ad.id} ad={ad} onClick={() => onClick?.(ad)} />
                ))}
            </div>
        </div>
    );
}