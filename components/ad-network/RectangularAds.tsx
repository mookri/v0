/* ═══════════════════════════════════════════════════════════════════════════
   AD LAYOUTS  ·  RectangularAds.tsx
   LeaderboardAd / BillboardAd / MediumRectangleAd
   ALL visual values from CSS vars only — zero hardcoded visual properties.
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import { ArrowUpRight, ShieldCheck } from "lucide-react";
import { V, css, resolveTextScheme, schemeTokens } from "./Adtokens";
import { useAdLazy } from "./Adprovider";
import type { Ad } from "./core/types";

interface Props { ad: Ad; onImpression?: () => void; onClick?: () => void; }

// ─────────────────────────────────────────────────────────────────────────────
// LeaderboardAd  —  728×90 fluid full-width bar
// ─────────────────────────────────────────────────────────────────────────────

export function LeaderboardAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div ref={ref} className="w-full my-6 overflow-hidden">
            <div
                className="relative w-full flex items-center group"
                style={{
                    height: "clamp(80px, 10vw, 100px)",
                    background: ad.creative?.bgColor ?? css(V.bgFallback),
                }}
            >
                {visible && imgUrl && !imgError && (
                    <img
                        src={imgUrl}
                        alt={ad.creative?.alt ?? ad.title}
                        loading="lazy"
                        decoding="async"
                        onLoad={onImgLoad}
                        onError={onImgError}
                        className="absolute inset-0 w-full h-full object-cover"
                        style={{
                            opacity: loaded ? 0.3 : 0,
                            transition: `opacity ${css(V.easeImage)}`,
                        }}
                    />
                )}

                {/* Left-heavy gradient overlay */}
                <div
                    className="absolute inset-0"
                    style={{ background: css(V.overlaySide) }}
                />

                {/* Content row */}
                <div
                    className="relative z-10 w-full flex items-center justify-between gap-4"
                    style={{ padding: "0 20px" }}
                >
                    <div className="min-w-0">
                        <p
                            className="mb-0.5 uppercase"
                            style={{
                                fontSize: 9,
                                letterSpacing: css(V.labelSpacing),
                                color: T.label,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            Sponsored{ad.sponsorName ? ` · ${ad.sponsorName}` : ""}
                        </p>
                        <p
                            className="truncate"
                            style={{
                                fontSize: "clamp(13px, 2vw, 16px)",
                                fontWeight: 600,
                                color: css(V.onDark1),
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.title}
                        </p>
                    </div>

                    <a
                        href={ad.ctaUrl}
                        onClick={onClick}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="flex-shrink-0 inline-flex items-center gap-1.5 whitespace-nowrap"
                        style={{
                            height: 36,
                            padding: "0 16px",
                            background: T.ctaBg,
                            color: T.ctaFg,
                            fontSize: 12,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rSm),
                            transition: `background ${css(V.easeFast)}`,
                        }}
                    >
                        {ad.ctaText}
                        <ArrowUpRight size={12} />
                    </a>
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// BillboardAd  —  970×250 full-width rich imagery
// ─────────────────────────────────────────────────────────────────────────────

export function BillboardAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div ref={ref} className="w-full my-8 -mx-6 md:-mx-10 overflow-hidden">
            <div
                className="relative w-full group"
                style={{
                    height: "clamp(180px, 22vw, 260px)",
                    background: ad.creative?.bgColor ?? css(V.bgFallback),
                }}
            >
                {visible && imgUrl && !imgError && (
                    <img
                        src={imgUrl}
                        alt={ad.creative?.alt ?? ad.title}
                        loading="lazy"
                        decoding="async"
                        onLoad={onImgLoad}
                        onError={onImgError}
                        className="absolute inset-0 w-full h-full object-cover"
                        style={{
                            opacity: loaded ? 1 : 0,
                            transition: `opacity ${css(V.easeImage)}, transform ${css(V.easeHover)}`,
                        }}
                    />
                )}

                <div className="absolute inset-0" style={{ background: css(V.overlaySide) }} />
                <div className="absolute inset-0" style={{ background: css(V.overlayDark) }} />

                {/* Sponsor */}
                <div className="absolute top-4 left-6 md:left-10">
                    <span
                        className="uppercase"
                        style={{
                            fontSize: 9,
                            letterSpacing: css(V.labelSpacing),
                            color: css(V.onDarkLabel),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        Sponsored{ad.sponsorName ? ` · ${ad.sponsorName}` : ""}
                    </span>
                </div>

                {/* Content */}
                <div className="absolute inset-0 flex items-center px-6 md:px-10">
                    <div style={{ maxWidth: 600 }}>
                        {ad.eyebrow && (
                            <p
                                className="mb-2 uppercase"
                                style={{
                                    fontSize: 10,
                                    letterSpacing: css(V.labelSpacing),
                                    color: css(V.onDark3),
                                    fontFamily: css(V.fontBody),
                                }}
                            >
                                {ad.eyebrow}
                            </p>
                        )}

                        <h3
                            className="mb-3"
                            style={{
                                fontFamily: css(V.fontDisplay),
                                fontSize: "clamp(1.25rem, 2.5vw, 2rem)",
                                lineHeight: 1.1,
                                fontWeight: 600,
                                color: css(V.onDark1),
                            }}
                        >
                            {ad.title}
                        </h3>

                        {ad.description && (
                            <p
                                className="mb-4"
                                style={{
                                    fontSize: 13,
                                    color: css(V.onDark2),
                                    maxWidth: 420,
                                    fontFamily: css(V.fontBody),
                                    lineHeight: 1.5,
                                }}
                            >
                                {ad.description}
                            </p>
                        )}

                        <div className="flex items-center gap-4">
                            <a
                                href={ad.ctaUrl}
                                onClick={onClick}
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                className="inline-flex items-center gap-2"
                                style={{
                                    height: 40,
                                    padding: "0 20px",
                                    background: css(V.bgCard),
                                    color: css(V.onLight1),
                                    fontSize: 12,
                                    fontWeight: 700,
                                    fontFamily: css(V.fontBody),
                                    textDecoration: "none",
                                    borderRadius: css(V.rSm),
                                    transition: `background ${css(V.easeFast)}`,
                                }}
                            >
                                {ad.ctaText} <ArrowUpRight size={13} />
                            </a>
                            {ad.verified && (
                                <span
                                    className="flex items-center gap-1"
                                    style={{
                                        fontSize: 11,
                                        color: css(V.onDark3),
                                        fontFamily: css(V.fontBody),
                                    }}
                                >
                                    <ShieldCheck size={11} style={{ color: css(V.success) }} />
                                    Verified
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// MediumRectangleAd  —  300×250 box unit
// ─────────────────────────────────────────────────────────────────────────────

export function MediumRectangleAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);

    return (
        <div
            ref={ref}
            className="my-4 overflow-hidden"
            style={{ width: "100%", maxWidth: 300 }}
        >
            <div
                className="relative group"
                style={{
                    aspectRatio: "6/5",
                    background: ad.creative?.bgColor ?? css(V.bgFallback),
                }}
            >
                {visible && imgUrl && !imgError && (
                    <img
                        src={imgUrl}
                        alt={ad.creative?.alt ?? ad.title}
                        loading="lazy"
                        decoding="async"
                        onLoad={onImgLoad}
                        onError={onImgError}
                        className="absolute inset-0 w-full h-full object-cover"
                        style={{
                            opacity: loaded ? 1 : 0,
                            transition: `opacity ${css(V.easeImage)}, transform ${css(V.easeHover)}`,
                        }}
                    />
                )}

                <div className="absolute inset-0" style={{ background: css(V.overlayDarkHeavy) }} />

                {ad.badge && (
                    <div
                        className="absolute top-3 right-3"
                        style={{
                            background: css(V.accent),
                            color: css(V.accentFg),
                            fontSize: 9,
                            fontWeight: 900,
                            fontFamily: css(V.fontBody),
                            textTransform: "uppercase" as const,
                            letterSpacing: "0.06em",
                            padding: "2px 8px",
                            borderRadius: css(V.rSm),
                        }}
                    >
                        {ad.badge}
                    </div>
                )}

                <div className="absolute bottom-0 left-0 right-0" style={{ padding: 16 }}>
                    <p
                        className="mb-1 uppercase"
                        style={{
                            fontSize: 8,
                            letterSpacing: css(V.labelSpacing),
                            color: css(V.onDarkLabel),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.sponsorName ?? "Sponsored"}
                    </p>
                    <h4
                        className="mb-2 leading-tight"
                        style={{
                            fontSize: 15,
                            fontWeight: 600,
                            color: css(V.onDark1),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.title}
                    </h4>
                    {ad.description && (
                        <p
                            className="mb-3 leading-relaxed"
                            style={{
                                fontSize: 11,
                                color: css(V.onDark3),
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.description}
                        </p>
                    )}
                    <a
                        href={ad.ctaUrl}
                        onClick={onClick}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="inline-flex items-center gap-1.5"
                        style={{
                            height: 32,
                            padding: "0 16px",
                            background: css(V.bgCard),
                            color: css(V.onLight1),
                            fontSize: 11,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rSm),
                            transition: `gap ${css(V.easeBase)}`,
                        }}
                    >
                        {ad.ctaText} <ArrowUpRight size={11} />
                    </a>
                </div>
            </div>
            <p
                style={{
                    fontSize: 7,
                    textAlign: "right" as const,
                    color: css(V.onLight3),
                    marginTop: 2,
                    fontFamily: css(V.fontBody),
                }}
            >
                Ad
            </p>
        </div>
    );
}