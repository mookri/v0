/* ═══════════════════════════════════════════════════════════════════════════
   AD LAYOUTS  ·  VerticalAds.tsx
   Square / Half Page / Wide Skyscraper / Story Card
   ALL visual values from CSS vars only.
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import { ArrowUpRight, ShieldCheck, Play } from "lucide-react";
import { V, css, resolveTextScheme, schemeTokens } from "./Adtokens";
import { useAdLazy } from "./Adprovider";
import type { Ad } from "./core/types";

interface Props { ad: Ad; onImpression?: () => void; onClick?: () => void; }

// ─────────────────────────────────────────────────────────────────────────────
// SquareAd  —  250×250
// ─────────────────────────────────────────────────────────────────────────────

export function SquareAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div
            ref={ref}
            className="overflow-hidden"
            style={{ width: "100%", maxWidth: 250 }}
        >
            <div
                className="relative group"
                style={{
                    aspectRatio: "1 / 1",
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
                        className="absolute"
                        style={{
                            top: 10,
                            left: 10,
                            background: css(V.accent),
                            color: css(V.accentFg),
                            fontSize: 8,
                            fontWeight: 900,
                            textTransform: "uppercase" as const,
                            letterSpacing: "0.06em",
                            padding: "2px 7px",
                            borderRadius: css(V.rSm),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.badge}
                    </div>
                )}

                <div className="absolute bottom-0 left-0 right-0" style={{ padding: 12 }}>
                    <p
                        className="mb-0.5 uppercase"
                        style={{
                            fontSize: 7,
                            letterSpacing: css(V.labelSpacing),
                            color: T.label,
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.sponsorName ?? "Ad"}
                    </p>
                    <h4
                        className="mb-2.5 leading-snug"
                        style={{
                            fontSize: 13,
                            fontWeight: 600,
                            color: T.heading,
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.title}
                    </h4>
                    <a
                        href={ad.ctaUrl}
                        onClick={onClick}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="inline-flex items-center gap-1"
                        style={{
                            height: 28,
                            padding: "0 12px",
                            background: T.ctaBg,
                            color: T.ctaFg,
                            fontSize: 10,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rSm),
                            transition: `gap ${css(V.easeBase)}`,
                        }}
                    >
                        {ad.ctaText} <ArrowUpRight size={10} />
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

// ─────────────────────────────────────────────────────────────────────────────
// HalfPageAd  —  300×600
// ─────────────────────────────────────────────────────────────────────────────

export function HalfPageAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div
            ref={ref}
            className="overflow-hidden"
            style={{ width: "100%", maxWidth: 300 }}
        >
            <div
                className="relative group"
                style={{
                    aspectRatio: "1 / 2",
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

                {/* Sponsor row at top */}
                <div
                    className="absolute flex items-center justify-between"
                    style={{ top: 16, left: 16, right: 16 }}
                >
                    <span
                        className="uppercase"
                        style={{
                            fontSize: 8,
                            letterSpacing: css(V.labelSpacing),
                            color: T.label,
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        Sponsored
                    </span>
                    {ad.sponsorName && (
                        <span
                            style={{
                                fontSize: 9,
                                fontWeight: 600,
                                color: T.muted,
                                background: "rgba(255,255,255,0.10)",
                                padding: "2px 8px",
                                borderRadius: css(V.rSm),
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.sponsorName}
                        </span>
                    )}
                </div>

                {/* Content at bottom */}
                <div className="absolute bottom-0 left-0 right-0" style={{ padding: 20 }}>
                    {ad.eyebrow && (
                        <p
                            className="mb-2 uppercase"
                            style={{
                                fontSize: 9,
                                letterSpacing: css(V.labelSpacing),
                                color: T.muted,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.eyebrow}
                        </p>
                    )}
                    <h3
                        className="mb-2 leading-tight"
                        style={{
                            fontFamily: css(V.fontDisplay),
                            fontSize: 20,
                            fontWeight: 600,
                            color: T.heading,
                        }}
                    >
                        {ad.title}
                    </h3>
                    {ad.description && (
                        <p
                            className="mb-5 leading-relaxed"
                            style={{
                                fontSize: 12,
                                color: T.muted,
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
                        className="flex items-center justify-center gap-2 w-full"
                        style={{
                            height: 44,
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
                        {ad.ctaText} <ArrowUpRight size={13} />
                    </a>
                    {ad.verified && (
                        <p
                            className="flex items-center justify-center gap-1 mt-3"
                            style={{
                                fontSize: 10,
                                color: T.label,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            <ShieldCheck size={10} style={{ color: css(V.success) }} />
                            Verified partner
                        </p>
                    )}
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

// ─────────────────────────────────────────────────────────────────────────────
// WideSkyscraperAd  —  160×600
// ─────────────────────────────────────────────────────────────────────────────

export function WideSkyscraperAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div
            ref={ref}
            className="overflow-hidden"
            style={{ width: 160 }}
        >
            <div
                className="relative"
                style={{
                    height: 600,
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
                            opacity: loaded ? 0.6 : 0,
                            transition: `opacity ${css(V.easeImage)}`,
                        }}
                    />
                )}
                <div className="absolute inset-0" style={{ background: css(V.overlayTop) }} />

                <div
                    className="absolute left-0 right-0 text-center"
                    style={{ top: 16 }}
                >
                    <span
                        className="uppercase"
                        style={{
                            fontSize: 7,
                            letterSpacing: css(V.labelSpacing),
                            color: T.label,
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        Sponsored
                    </span>
                </div>

                <div
                    className="absolute bottom-0 left-0 right-0 text-center"
                    style={{ padding: 12 }}
                >
                    {ad.sponsorName && (
                        <p
                            className="mb-2 uppercase"
                            style={{
                                fontSize: 8,
                                letterSpacing: "0.2em",
                                color: T.muted,
                                fontWeight: 500,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.sponsorName}
                        </p>
                    )}
                    <h4
                        className="mb-2 leading-tight"
                        style={{
                            fontSize: 13,
                            fontWeight: 600,
                            color: T.heading,
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        {ad.title}
                    </h4>
                    {ad.description && (
                        <p
                            className="mb-4 leading-relaxed"
                            style={{
                                fontSize: 10,
                                color: T.muted,
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
                        className="block w-full"
                        style={{
                            padding: "8px 0",
                            background: T.ctaBg,
                            color: T.ctaFg,
                            fontSize: 10,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rSm),
                            transition: `background ${css(V.easeFast)}`,
                        }}
                    >
                        {ad.ctaText}
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

// ─────────────────────────────────────────────────────────────────────────────
// StoryCardAd  —  9:16 vertical social-story format
// ─────────────────────────────────────────────────────────────────────────────

export function StoryCardAd({ ad, onImpression, onClick }: Props) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);
    const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
    const T = schemeTokens(scheme);

    return (
        <div
            ref={ref}
            className="overflow-hidden"
            style={{
                width: "100%",
                maxWidth: 340,
                borderRadius: css(V.rXl),
                boxShadow: css(V.shadowStory),
            }}
        >
            <div
                className="relative group"
                style={{
                    aspectRatio: "9 / 16",
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
                            transition: `opacity ${css(V.easeImage)}`,
                        }}
                    />
                )}

                {/* Triple-layer story gradient */}
                <div className="absolute inset-0" style={{ background: css(V.overlayStory ?? V.overlayDarkHeavy) }} />

                {/* Top bar */}
                <div
                    className="absolute flex items-center justify-between"
                    style={{ top: 16, left: 16, right: 16 }}
                >
                    <span
                        className="uppercase"
                        style={{
                            fontSize: 9,
                            fontWeight: 500,
                            letterSpacing: "0.15em",
                            color: T.body,
                            background: css(V.bgSponsorTag),
                            backdropFilter: "blur(8px)",
                            padding: "4px 12px",
                            borderRadius: css(V.rPill),
                            fontFamily: css(V.fontBody),
                        }}
                    >
                        Sponsored
                    </span>
                    {ad.badge && (
                        <span
                            style={{
                                background: css(V.accent),
                                color: css(V.accentFg),
                                fontSize: 9,
                                fontWeight: 900,
                                textTransform: "uppercase" as const,
                                padding: "4px 10px",
                                borderRadius: css(V.rPill),
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.badge}
                        </span>
                    )}
                </div>

                {/* Center sponsor logo area */}
                {ad.sponsorName && (
                    <div
                        className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center"
                    >
                        <div
                            className="mx-auto flex items-center justify-center mb-2"
                            style={{
                                width: 64,
                                height: 64,
                                borderRadius: css(V.rLg),
                                background: "rgba(255,255,255,0.12)",
                                backdropFilter: "blur(12px)",
                                border: "1px solid rgba(255,255,255,0.18)",
                            }}
                        >
                            <Play size={24} style={{ color: css(V.onDark1) }} />
                        </div>
                        <p
                            className="uppercase"
                            style={{
                                fontSize: 10,
                                letterSpacing: "0.2em",
                                color: T.muted,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.sponsorName}
                        </p>
                    </div>
                )}

                {/* Bottom content */}
                <div className="absolute bottom-0 left-0 right-0" style={{ padding: 20 }}>
                    {ad.eyebrow && (
                        <p
                            className="mb-1.5 uppercase"
                            style={{
                                fontSize: 9,
                                letterSpacing: css(V.labelSpacing),
                                color: T.muted,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            {ad.eyebrow}
                        </p>
                    )}
                    <h3
                        className="mb-2 leading-tight"
                        style={{
                            fontFamily: css(V.fontDisplay),
                            fontSize: 22,
                            fontWeight: 700,
                            color: T.heading,
                        }}
                    >
                        {ad.title}
                    </h3>
                    {ad.description && (
                        <p
                            className="mb-4"
                            style={{
                                fontSize: 12,
                                color: T.muted,
                                fontFamily: css(V.fontBody),
                                lineHeight: 1.5,
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
                        className="flex items-center justify-center gap-2 w-full"
                        style={{
                            height: 48,
                            background: T.ctaBg,
                            color: T.ctaFg,
                            fontSize: 13,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rMd),
                            transition: `background ${css(V.easeFast)}`,
                        }}
                    >
                        {ad.ctaText} <ArrowUpRight size={14} />
                    </a>
                    {ad.verified && (
                        <div
                            className="flex items-center justify-center gap-1.5 mt-3"
                            style={{
                                fontSize: 10,
                                color: T.label,
                                fontFamily: css(V.fontBody),
                            }}
                        >
                            <ShieldCheck size={10} style={{ color: css(V.success) }} />
                            Verified partner
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}